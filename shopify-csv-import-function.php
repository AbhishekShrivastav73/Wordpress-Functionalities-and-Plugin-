<?php

// Add Shopify Importer page to admin menu
add_action('admin_menu', 'shopify_importer_menu');
function shopify_importer_menu() {
    add_menu_page(
        'Shopify CSV Importer', // Page title
        'Shopify Importer',     // Menu title
        'manage_options',       // Capability
        'shopify-importer',     // Menu slug
        'render_shopify_importer_page', // Function to render page
        'dashicons-upload',     // Icon
        30                      // Position
    );
}

// Render the importer page with upload form and product table
function render_shopify_importer_page() {
    ?>
    <div class="wrap">
        <h1>Shopify to WooCommerce Importer</h1>
        <form method="post" enctype="multipart/form-data" action="<?php echo esc_url(admin_url('admin.php?page=shopify-importer')); ?>">
            <table class="form-table">
                <tr>
                    <th><label for="csv_file">Upload Shopify CSV File</label></th>
                    <td><input type="file" name="csv_file" id="csv_file" accept=".csv" required></td>
                </tr>
            </table>
            <?php wp_nonce_field('shopify_import_nonce', 'shopify_import_nonce'); ?>
            <input type="submit" name="import_csv" class="button button-primary" value="Import Products">
        </form>
        <h2>Imported Products</h2>
        <?php process_shopify_csv_upload(); ?>
        <?php display_imported_products_table(); ?>
    </div>
    <?php
}

// Add CSS styling for the importer page and table
add_action('admin_enqueue_scripts', 'shopify_importer_styles');
function shopify_importer_styles($hook) {
    if ($hook !== 'toplevel_page_shopify-importer') {
        return;
    }
    wp_add_inline_style('wp-admin', '
        .wrap { max-width: 1000px; margin: 20px auto; }
        .form-table th { width: 200px; }
        .notice { margin-top: 20px; }
        .imported-products-table { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 14px; }
        .imported-products-table th, .imported-products-table td { border: 1px solid #e1e1e1; padding: 12px; text-align: left; vertical-align: middle; }
        .imported-products-table th { background-color: #0073aa; color: #fff; font-weight: 600; }
        .imported-products-table tr:nth-child(even) { background-color: #f7f7f7; }
        .imported-products-table tr:hover { background-color: #e5f3ff; }
        .imported-products-table td { color: #333; }
        .pagination { margin-top: 20px; text-align: center; }
        .pagination a, .pagination span { display: inline-block; padding: 8px 12px; margin: 0 5px; border: 1px solid #ddd; border-radius: 4px; text-decoration: none; color: #0073aa; }
        .pagination a:hover { background-color: #0073aa; color: #fff; border-color: #0073aa; }
        .pagination .current { background-color: #0073aa; color: #fff; border-color: #0073aa; font-weight: bold; }
        @media screen and (max-width: 782px) {
            .imported-products-table th, .imported-products-table td { display: block; width: 100%; box-sizing: border-box; }
            .imported-products-table tr { margin-bottom: 10px; }
        }
    ');
}

// Process CSV file upload
function process_shopify_csv_upload() {
    // Only process if form is submitted
    if (!isset($_POST['import_csv'])) {
        return;
    }

    // Verify nonce
    if (!isset($_POST['shopify_import_nonce']) || !wp_verify_nonce($_POST['shopify_import_nonce'], 'shopify_import_nonce')) {
        error_log('Shopify Importer: Nonce verification failed at ' . date('Y-m-d H:i:s'));
        echo '<div class="notice notice-error"><p>Security check failed. Please try again.</p></div>';
        return;
    }

    // Check if file is uploaded
    if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
        $error_messages = [
            UPLOAD_ERR_INI_SIZE => 'File size exceeds PHP upload limit.',
            UPLOAD_ERR_FORM_SIZE => 'File size exceeds form limit.',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded.',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
            UPLOAD_ERR_NO_TMP_DIR => 'Temporary folder missing.',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
            UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload.'
        ];
        $error_code = $_FILES['csv_file']['error'] ?? UPLOAD_ERR_NO_FILE;
        $message = $error_messages[$error_code] ?? 'An error occurred during file upload.';
        error_log('Shopify Importer: File upload error - ' . $message);
        echo '<div class="notice notice-error"><p>' . esc_html($message) . '</p></div>';
        return;
    }

    // Validate file type
    $file_type = mime_content_type($_FILES['csv_file']['tmp_name']);
    if ($file_type !== 'text/csv' && $file_type !== 'text/plain') {
        error_log('Shopify Importer: Invalid file type - ' . $file_type);
        echo '<div class="notice notice-error"><p>Only CSV files are allowed.</p></div>';
        return;
    }

    $file = $_FILES['csv_file']['tmp_name'];
    error_log('Shopify Importer: Processing CSV file - ' . $file);
    import_products_from_csv($file);
}

// Import products from CSV
function import_products_from_csv($file) {
    // Check if file exists and is readable
    if (!file_exists($file) || !is_readable($file)) {
        error_log('Shopify Importer: File not found or not readable - ' . $file);
        echo '<div class="notice notice-error"><p>CSV file not found or cannot be read: ' . esc_html($file) . '</p></div>';
        return;
    }

    // Open CSV file
    $handle = fopen($file, 'r');
    if ($handle === false) {
        error_log('Shopify Importer: Failed to open CSV file - ' . $file);
        echo '<div class="notice notice-error"><p>Failed to open CSV file. Please check the file path.</p></div>';
        return;
    }

    // Read headers
    $headers = fgetcsv($handle, 1000, ',');
    if ($headers === false || empty($headers)) {
        error_log('Shopify Importer: No headers found or CSV file is empty');
        echo '<div class="notice notice-error"><p>No headers found or CSV file is empty.</p></div>';
        fclose($handle);
        return;
    }

    // Log headers for debugging
    error_log('Shopify Importer: CSV Headers - ' . implode(', ', $headers));

    // Check for required columns
    $required_columns = ['Title'];
    foreach ($required_columns as $column) {
        if (!in_array($column, $headers)) {
            error_log('Shopify Importer: Missing required column - ' . $column);
            echo '<div class="notice notice-error"><p>Missing required column: ' . esc_html($column) . '</p></div>';
        }
    }

    $imported = 0;
    $skipped = 0;
    $row_number = 1; // Row counting (after header)

    // Prevent timeout
    set_time_limit(0);

    // Process each row
    while (($data = fgetcsv($handle, 1000, ',')) !== false) {
        $row_number++;
        // Log raw data for debugging
        error_log('Shopify Importer: Processing row ' . $row_number . ' - Raw data: ' . implode(', ', array_map('strval', $data)));

        // Combine headers and data
        $product_data = array_combine($headers, $data);
        if ($product_data === false) {
            error_log('Shopify Importer: Failed to combine headers with data at row ' . $row_number . '. Expected ' . count($headers) . ' columns, got ' . count($data));
            $skipped++;
            continue;
        }

        // Skip if Title is empty or invalid
        if (empty(trim($product_data['Title']))) {
            error_log('Shopify Importer: Empty or invalid Title at row ' . $row_number . '. Title: ' . ($product_data['Title'] ?? 'not set'));
            $skipped++;
            continue;
        }

        // Create product
        $result = create_woocommerce_product($product_data);
        if ($result === 'skipped') {
            $skipped++;
        } else {
            $imported++;
        }
    }

    fclose($handle);

    // Display result
    error_log('Shopify Importer: Import completed. Imported: ' . $imported . ', Skipped: ' . $skipped);
    echo '<div class="notice notice-success"><p>Import completed! Imported: ' . $imported . ', Skipped: ' . $skipped . '</p></div>';
}

// Create WooCommerce product
function create_woocommerce_product($product_data) {
    // Get SKU if provided
    $sku = !empty($product_data['Variant SKU']) ? $product_data['Variant SKU'] : (!empty($product_data['Handle']) ? $product_data['Handle'] : '');
    $title = trim($product_data['Title'] ?? 'Untitled Product');

    // Check for existing product by SKU (if provided) or title
    $args = [
        'post_type' => 'product',
        'post_status' => ['publish', 'draft', 'pending', 'private'],
        'posts_per_page' => 1,
    ];

    if (!empty($sku)) {
        $args['meta_query'] = [
            [
                'key' => '_sku',
                'value' => $sku,
                'compare' => '=',
            ],
        ];
    } else {
        $args['title'] = $title;
        $args['post_status'] = ['publish', 'draft', 'pending', 'private'];
    }

    $existing_product = get_posts($args);

    if (!empty($existing_product)) {
        error_log('Shopify Importer: Product already exists with ' . ($sku ? 'SKU ' . $sku : 'title ' . $title));
        return 'skipped';
    }

    // Create new simple product as draft
    $product = new WC_Product_Simple();
    $product->set_name($title);
    $product->set_description($product_data['Body (HTML)'] ?? '');
    $product->set_short_description($product_data['Body (HTML)'] ?? '');

    // Set SKU only if provided
    if (!empty($sku)) {
        try {
            $product->set_sku($sku);
        } catch (WC_Data_Exception $e) {
            error_log('Shopify Importer: Failed to set SKU ' . $sku . ' for product ' . $title . '. Error: ' . $e->getMessage());
            // Continue without setting SKU
        }
    }

    $product->set_regular_price($product_data['Variant Price'] ?? 0);
    $product->set_manage_stock(true);
    $product->set_stock_quantity($product_data['Variant Inventory Qty'] ?? 0);
    $product->set_status('draft'); // Save as draft

    // Handle categories and tags
    if (!empty($product_data['Tags'])) {
        $tags = explode(',', $product_data['Tags']);
        $product->set_tag_ids(get_term_ids($tags, 'product_tag'));
    }

    if (!empty($product_data['Type'])) {
        $categories = [$product_data['Type']];
        $product->set_category_ids(get_term_ids($categories, 'product_cat'));
    }

    // Handle product image
    if (!empty($product_data['Image Src'])) {
        $image_id = upload_image($product_data['Image Src']);
        if ($image_id) {
            $product->set_image_id($image_id);
        }
    }

    // Save product and mark as CSV-imported
    $product_id = $product->save();
    if ($product_id) {
        update_post_meta($product_id, '_imported_via_csv', 'yes');
        error_log('Shopify Importer: Product created' . ($sku ? ' with SKU ' . $sku : ' without SKU') . ' as draft (ID: ' . $product_id . ')');
        return 'created';
    }

    error_log('Shopify Importer: Failed to save product ' . $title);
    return 'skipped';
}

// Get term IDs for categories/tags
function get_term_ids($terms, $taxonomy) {
    $term_ids = [];
    foreach ($terms as $term_name) {
        $term_name = trim($term_name);
        if (empty($term_name)) {
            continue;
        }
        $term = get_term_by('name', $term_name, $taxonomy);
        if (!$term) {
            $term = wp_insert_term($term_name, $taxonomy);
            if (!is_wp_error($term)) {
                $term_ids[] = $term['term_id'];
            }
        } else {
            $term_ids[] = $term->term_id;
        }
    }
    return $term_ids;
}

// Upload image
function upload_image($image_url) {
    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';

    $image_id = media_sideload_image($image_url, 0, null, 'id');
    if (is_wp_error($image_id)) {
        error_log('Shopify Importer: Failed to upload image - ' . $image_url . '. Error: ' . $image_id->get_error_message());
        return false;
    }
    return $image_id;
}

// Display paginated table of imported products
function display_imported_products_table() {
    $paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
    $posts_per_page = 10;

    $args = [
        'post_type' => 'product',
        'post_status' => ['publish', 'draft', 'pending', 'private'],
        'posts_per_page' => $posts_per_page,
        'paged' => $paged,
        'meta_query' => [
            [
                'key' => '_imported_via_csv',
                'value' => 'yes',
                'compare' => '=',
            ],
        ],
    ];

    $query = new WP_Query($args);

    if ($query->have_posts()) {
        ?>
        <table class="imported-products-table">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Stock</th>
                    <th>Category</th>
                    <th>Created Date</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($query->have_posts()) : $query->the_post(); ?>
                    <?php
                    $product = wc_get_product(get_the_ID());
                    $stock = $product->get_stock_quantity() !== null ? $product->get_stock_quantity() : ($product->is_in_stock() ? 'In stock' : 'Out of stock');
                    $categories = wp_get_post_terms(get_the_ID(), 'product_cat', ['fields' => 'names']);
                    $category = !empty($categories) ? implode(', ', $categories) : '-';
                    $created_date = get_the_date('Y-m-d H:i:s');
                    ?>
                    <tr>
                        <td><?php echo esc_html($product->get_name()); ?></td>
                        <td><?php echo esc_html($stock); ?></td>
                        <td><?php echo esc_html($category); ?></td>
                        <td><?php echo esc_html($created_date); ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <div class="pagination">
            <?php
            echo paginate_links([
                'total' => $query->max_num_pages,
                'current' => $paged,
                'base' => esc_url(add_query_arg('paged', '%#%')),
                'format' => '?paged=%#%',
            ]);
            ?>
        </div>
        <?php
        wp_reset_postdata();
    } else {
        echo '<p>No products imported yet.</p>';
    }
}
?>
