<?php
// admin/admin.php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Add custom tab in product data
add_filter('woocommerce_product_data_tabs', 'wc_addons_product_tab');
function wc_addons_product_tab($tabs) {
    $tabs['addons'] = array(
        'label' => __('Add-Ons', 'wc-product-addons'),
        'target' => 'addons_product_data',
        'class' => array('show_if_simple', 'show_if_variable'),
    );
    return $tabs;
}

// Add fields in the tab
add_action('woocommerce_product_data_panels', 'wc_addons_product_fields');
function wc_addons_product_fields() {
    global $post;
    $addon_groups = get_post_meta($post->ID, '_product_addon_groups', true);
    $addon_groups = is_array($addon_groups) ? $addon_groups : array();
    ?>
    <div id="addons_product_data" class="panel woocommerce_options_panel">
        <div class="options_group">
            <div class="addon_groups">
                <?php
                if (!empty($addon_groups)) {
                    foreach ($addon_groups as $group_index => $group) {
                        $display_type = isset($group['display_type']) ? esc_attr($group['display_type']) : 'checkbox';
                        $addons = isset($group['addons']) && is_array($group['addons']) ? $group['addons'] : array();
                        ?>
                        <div class="addon_group" data-group-index="<?php echo esc_attr($group_index); ?>">
                            <h4>Add-On Type <?php echo ($group_index + 1); ?> <button type="button" class="button remove_addon_group">Remove Type</button></h4>
                            <p class="form-field">
                                <label>Display Type</label>
                                <select name="_addon_groups[<?php echo esc_attr($group_index); ?>][display_type]">
                                    <option value="checkbox" <?php selected($display_type, 'checkbox'); ?>>Checkbox</option>
                                    <option value="radio" <?php selected($display_type, 'radio'); ?>>Radio Button</option>
                                    <option value="select" <?php selected($display_type, 'select'); ?>>Dropdown</option>
                                </select>
                            </p>
                            <div class="addons_list">
                                <?php
                                if (!empty($addons)) {
                                    foreach ($addons as $addon_index => $addon) {
                                        ?>
                                        <div class="addon_row">
                                            <p class="form-field">
                                                <label>Add-On Name</label>
                                                <input type="text" name="_addon_groups[<?php echo esc_attr($group_index); ?>][addons][<?php echo esc_attr($addon_index); ?>][name]" value="<?php echo esc_attr($addon['name']); ?>" />
                                            </p>
                                            <p class="form-field">
                                                <label>Add-On Price</label>
                                                <input type="number" step="0.01" name="_addon_groups[<?php echo esc_attr($group_index); ?>][addons][<?php echo esc_attr($addon_index); ?>][price]" value="<?php echo esc_attr($addon['price']); ?>" />
                                            </p>
                                            <p class="form-field">
                                                <button type="button" class="button remove_addon">Remove</button>
                                            </p>
                                        </div>
                                        <?php
                                    }
                                } else {
                                    ?>
                                    <div class="addon_row">
                                        <p class="form-field">
                                            <label>Add-On Name</label>
                                            <input type="text" name="_addon_groups[<?php echo esc_attr($group_index); ?>][addons][0][name]" />
                                        </p>
                                        <p class="form-field">
                                            <label>Add-On Price</label>
                                            <input type="number" step="0.01" name="_addon_groups[<?php echo esc_attr($group_index); ?>][addons][0][price]" />
                                        </p>
                                        <p class="form-field">
                                            <button type="button" class="button remove_addon">Remove</button>
                                        </p>
                                    </div>
                                    <?php
                                }
                                ?>
                            </div>
                            <button type="button" class="button add_new_addon">Add Another Add-On</button>
                        </div>
                        <?php
                    }
                } else {
                    ?>
                    <div class="addon_group" data-group-index="0">
                        <h4>Add-On Type 1 <button type="button" class="button remove_addon_group">Remove Type</button></h4>
                        <p class="form-field">
                            <label>Display Type</label>
                            <select name="_addon_groups[0][display_type]">
                                <option value="checkbox">Checkbox</option>
                                <option value="radio">Radio Button</option>
                                <option value="select">Dropdown</option>
                            </select>
                        </p>
                        <div class="addons_list">
                            <div class="addon_row">
                                <p class="form-field">
                                    <label>Add-On Name</label>
                                    <input type="text" name="_addon_groups[0][addons][0][name]" />
                                </p>
                                <p class="form-field">
                                    <label>Add-On Price</label>
                                    <input type="number" step="0.01" name="_addon_groups[0][addons][0][price]" />
                                </p>
                                <p class="form-field">
                                    <button type="button" class="button remove_addon">Remove</button>
                                </p>
                            </div>
                        </div>
                        <button type="button" class="button add_new_addon">Add Another Add-On</button>
                    </div>
                    <?php
                }
                ?>
            </div>
            <button type="button" class="button add_new_addon_group">Add More Addon Type</button>
        </div>
    </div>
    <?php
}

// Save add-ons
add_action('woocommerce_process_product_meta', 'wc_save_addons');
function wc_save_addons($post_id) {
    $addon_groups = isset($_POST['_addon_groups']) ? (array) $_POST['_addon_groups'] : array();
    $sanitized_groups = array();

    foreach ($addon_groups as $group_index => $group) {
        $display_type = isset($group['display_type']) ? sanitize_text_field($group['display_type']) : 'checkbox';
        $addons = isset($group['addons']) && is_array($group['addons']) ? $group['addons'] : array();
        $sanitized_addons = array();

        foreach ($addons as $addon) {
            if (!empty($addon['name'])) {
                $sanitized_addons[] = array(
                    'name' => sanitize_text_field($addon['name']),
                    'price' => floatval($addon['price'] >= 0 ? $addon['price'] : 0),
                );
            }
        }

        if (!empty($sanitized_addons)) {
            $sanitized_groups[$group_index] = array(
                'display_type' => $display_type,
                'addons' => $sanitized_addons,
            );
        }
    }

    // Ensure array keys are sequential
    $sanitized_groups = array_values($sanitized_groups);
    update_post_meta($post_id, '_product_addon_groups', $sanitized_groups);
}

// Enqueue admin JS and CSS
add_action('admin_enqueue_scripts', 'wc_addons_admin_scripts');
function wc_addons_admin_scripts($hook) {
    if ('post.php' === $hook || 'post-new.php' === $hook) {
        global $post;
        if ($post && $post->post_type === 'product') {
            wp_enqueue_script('wc-addons-admin', plugins_url('js/admin.js', __FILE__), array('jquery'), '1.0.1', true);
            wp_enqueue_style('wc-addons-admin', plugins_url('css/admin.css', __FILE__), array(), '1.0.1');
        }
    }
}