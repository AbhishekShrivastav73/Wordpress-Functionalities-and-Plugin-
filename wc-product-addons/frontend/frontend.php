<?php
// frontend/frontend.php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Display add-ons on product page (No change, same as before)
add_action('woocommerce_before_add_to_cart_button', 'wc_display_addons');
function wc_display_addons() {
    global $product;
    $product_id = $product->get_id();
    $addon_groups = get_post_meta($product_id, '_product_addon_groups', true);
    $addon_groups = is_array($addon_groups) ? $addon_groups : array();

    if (!empty($addon_groups)) {
        echo '<div class="wc-product-addons">';
        foreach ($addon_groups as $group_index => $group) {
            $display_type = isset($group['display_type']) ? $group['display_type'] : 'checkbox';
            $addons = isset($group['addons']) && is_array($group['addons']) ? $group['addons'] : array();
            if (empty($addons)) {
                continue;
            }
            ?>
            <div class="addon-group" data-group-index="<?php echo esc_attr($group_index); ?>">
                <h4>Extra Items (Type <?php echo ($group_index + 1); ?>)</h4>
                <?php
                if ($display_type === 'checkbox') {
                    foreach ($addons as $addon_index => $addon) {
                        $name = esc_html($addon['name']);
                        $price = floatval($addon['price']);
                        $field_name = "group_{$group_index}_addon_{$addon_index}";
                        ?>
                        <div class="addon-item">
                            <label>
                                <input type="checkbox"
                                       class="addon-checkbox"
                                       name="addon_groups[<?php echo $group_index; ?>][addons][<?php echo $addon_index; ?>][selected]"
                                       value="1"
                                       data-price="<?php echo $price; ?>">
                                <?php echo $name . ' (+' . wc_price($price) . ')'; ?>
                            </label>
                            <input type="hidden" name="addon_groups[<?php echo $group_index; ?>][addons][<?php echo $addon_index; ?>][name]" value="<?php echo $name; ?>">
                            <input type="hidden" name="addon_groups[<?php echo $group_index; ?>][addons][<?php echo $addon_index; ?>][price]" value="<?php echo $price; ?>">
                        </div>
                        <?php
                    }
                } elseif ($display_type === 'radio') {
                    ?>
                    <div class="addon-item">
                        <label>
                            <input type="radio"
                                   class="addon-radio"
                                   name="addon_groups[<?php echo $group_index; ?>][selected]"
                                   value=""
                                   checked
                                   data-price="0">
                            None
                        </label><br>
                        <?php
                        foreach ($addons as $addon_index => $addon) {
                            $name = esc_html($addon['name']);
                            $price = floatval($addon['price']);
                            $field_name = "group_{$group_index}_addon_{$addon_index}";
                            ?>
                            <label>
                                <input type="radio"
                                       class="addon-radio"
                                       name="addon_groups[<?php echo $group_index; ?>][selected]"
                                       value="<?php echo $addon_index; ?>"
                                       data-price="<?php echo $price; ?>">
                                <?php echo $name . ' (+' . wc_price($price) . ')'; ?>
                            </label><br>
                            <input type="hidden" name="addon_groups[<?php echo $group_index; ?>][addons][<?php echo $addon_index; ?>][name]" value="<?php echo $name; ?>">
                            <input type="hidden" name="addon_groups[<?php echo $group_index; ?>][addons][<?php echo $addon_index; ?>][price]" value="<?php echo $price; ?>">
                            <?php
                        }
                        ?>
                    </div>
                    <?php
                } elseif ($display_type === 'select') {
                    ?>
                    <div class="addon-item">
                        <select class="addon-select" name="addon_groups[<?php echo $group_index; ?>][selected]" data-price="0">
                            <option value="" data-price="0">None</option>
                            <?php
                            foreach ($addons as $addon_index => $addon) {
                                $name = esc_html($addon['name']);
                                $price = floatval($addon['price']);
                                ?>
                                <option value="<?php echo $addon_index; ?>"
                                        data-price="<?php echo $price; ?>">
                                    <?php echo $name . ' (+' . wc_price($price) . ')'; ?>
                                </option>
                                <?php
                            }
                            ?>
                        </select>
                        <?php
                        foreach ($addons as $addon_index => $addon) {
                            $name = esc_html($addon['name']);
                            $price = floatval($addon['price']);
                            ?>
                            <input type="hidden" class="addon-data" name="addon_groups[<?php echo $group_index; ?>][addons][<?php echo $addon_index; ?>][name]" value="<?php echo $name; ?>">
                            <input type="hidden" class="addon-data" name="addon_groups[<?php echo $group_index; ?>][addons][<?php echo $addon_index; ?>][price]" value="<?php echo $price; ?>">
                            <?php
                        }
                        ?>
                    </div>
                    <?php
                }
                ?>
            </div>
            <?php
        }
        ?>
        <div class="addon-total">
            <strong>Total Price: <span id="addon-total-price"><?php echo wc_price($product->get_price()); ?></span></strong>
        </div>
        </div>
        <?php
    }
}

// Save add-ons to cart (No change, same as before)
add_filter('woocommerce_add_cart_item_data', 'wc_add_addons_to_cart', 10, 3);
function wc_add_addons_to_cart($cart_item_data, $product_id, $variation_id) {
    if (isset($_POST['addon_groups']) && is_array($_POST['addon_groups'])) {
        $cart_item_data['addon_groups'] = array();
        $addon_groups = get_post_meta($product_id, '_product_addon_groups', true);
        $addon_groups = is_array($addon_groups) ? $addon_groups : array();

        foreach ($_POST['addon_groups'] as $group_index => $group_data) {
            if (!isset($addon_groups[$group_index])) {
                continue;
            }
            $display_type = $addon_groups[$group_index]['display_type'];
            $addons = $addon_groups[$group_index]['addons'];
            $group_addons = array();

            if ($display_type === 'checkbox' && isset($group_data['addons'])) {
                foreach ($group_data['addons'] as $addon_index => $addon) {
                    if (isset($addon['selected']) && $addon['selected'] == '1' && isset($addons[$addon_index])) {
                        $group_addons[] = array(
                            'name' => sanitize_text_field($addons[$addon_index]['name']),
                            'price' => floatval($addons[$addon_index]['price']),
                        );
                    }
                }
            } elseif (($display_type === 'radio' || $display_type === 'select') && isset($group_data['selected']) && $group_data['selected'] !== '') {
                $addon_index = $group_data['selected'];
                if (isset($addons[$addon_index])) {
                    $group_addons[] = array(
                        'name' => sanitize_text_field($addons[$addon_index]['name']),
                        'price' => floatval($addons[$addon_index]['price']),
                    );
                }
            }

            if (!empty($group_addons)) {
                $cart_item_data['addon_groups'][$group_index] = array(
                    'display_type' => $display_type,
                    'addons' => $group_addons,
                );
            }
        }
    }
    return $cart_item_data;
}

// Update cart price with add-ons (Updated)
add_action('woocommerce_before_calculate_totals', 'wc_update_cart_price', 10, 1);
function wc_update_cart_price($cart) {
    if (is_admin() && !defined('DOING_AJAX')) {
        return;
    }

    if (did_action('woocommerce_before_calculate_totals') >= 2) {
        return;
    }

    foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
        $product = $cart_item['data'];
        // Get original price (sale or regular)
        $base_price = floatval($product->get_regular_price());
        if ($product->is_on_sale()) {
            $base_price = floatval($product->get_sale_price());
        }
        
        // Reset price to base
        $product->set_price($base_price);

        // Add add-ons price
        if (isset($cart_item['addon_groups']) && is_array($cart_item['addon_groups'])) {
            $addon_total = 0;
            foreach ($cart_item['addon_groups'] as $group) {
                foreach ($group['addons'] as $addon) {
                    $addon_price = floatval($addon['price']);
                    $addon_total += $addon_price;
                    // Debug log
                    error_log("Cart item {$cart_item_key}: Adding addon {$addon['name']} price {$addon_price}, total addon so far: {$addon_total}");
                }
            }
            $new_price = $base_price + $addon_total;
            $product->set_price($new_price);
            error_log("Cart item {$cart_item_key}: Base price {$base_price}, Addon total {$addon_total}, Final price {$new_price}");
        } else {
            error_log("Cart item {$cart_item_key}: No addons, price remains {$base_price}");
        }
    }
}

// Display add-ons in cart and checkout (No change)
add_filter('woocommerce_get_item_data', 'wc_display_addons_in_cart', 10, 2);
function wc_display_addons_in_cart($item_data, $cart_item) {
    if (isset($cart_item['addon_groups']) && is_array($cart_item['addon_groups'])) {
        foreach ($cart_item['addon_groups'] as $group_index => $group) {
            foreach ($group['addons'] as $addon) {
                $item_data[] = array(
                    'key' => esc_html($addon['name']) . ' (Type ' . ($group_index + 1) . ')',
                    'value' => wc_price($addon['price']),
                );
            }
        }
    }
    return $item_data;
}

// Save add-ons to order items (Updated)
add_action('woocommerce_checkout_create_order_line_item', 'wc_save_addons_to_order', 10, 4);
function wc_save_addons_to_order($item, $cart_item_key, $values, $order) {
    if (isset($values['addon_groups']) && is_array($values['addon_groups'])) {
        $addon_total = 0;
        foreach ($values['addon_groups'] as $group_index => $group) {
            foreach ($group['addons'] as $addon) {
                $addon_name = esc_html($addon['name']) . ' (Type ' . ($group_index + 1) . ')';
                $addon_price = floatval($addon['price']);
                $item->add_meta_data($addon_name, wc_price($addon_price));
                $addon_total += $addon_price;
                error_log("Order item {$item->get_id()}: Added meta {$addon_name} with price {$addon_price}");
            }
        }
        // Do not modify item price here to avoid double-counting
        error_log("Order item {$item->get_id()}: Total addon price {$addon_total}");
    }
}

// Display add-ons in order details (No change)
add_filter('woocommerce_order_item_name', 'wc_display_addons_in_order', 10, 2);
function wc_display_addons_in_order($item_name, $item) {
    $item_id = $item->get_id();
    $addons = wc_get_order_item_meta($item_id, '');
    if ($addons) {
        $item_name .= '<br>';
        foreach ($addons as $meta_key => $meta_value) {
            if (!is_array($meta_value) && $meta_key !== '_wc_cog_item_cost' && !empty($meta_value)) {
                $item_name .= '<small>' . esc_html($meta_key) . ': ' . wp_kses_post($meta_value) . '</small><br>';
            }
        }
    }
    return $item_name;
}

// Admin order table headers (No change)
add_action('woocommerce_admin_order_item_headers', 'wc_addons_order_item_headers');
function wc_addons_order_item_headers() {
    echo '<th class="item_addons">Add-Ons</th>';
}

// Admin order table values (No change)
add_action('woocommerce_admin_order_item_values', 'wc_addons_order_item_values', 10, 3);
function wc_addons_order_item_values($product, $item, $item_id) {
    echo '<td class="item_addons">';
    $addons = wc_get_order_item_meta($item_id, '');
    if ($addons) {
        foreach ($addons as $meta_key => $meta_value) {
            if (!is_array($meta_value) && $meta_key !== '_wc_cog_item_cost' && !empty($meta_value)) {
                echo esc_html($meta_key) . ': ' . wp_kses_post($meta_value) . '<br>';
            }
        }
    }
    echo '</td>';
}

// Enqueue frontend styles and scripts (No change)
add_action('wp_enqueue_scripts', 'wc_addons_frontend_assets');
function wc_addons_frontend_assets() {
    if (is_product()) {
        wp_enqueue_style('wc-addons-frontend', plugin_dir_url(__FILE__) . 'css/frontend.css', array(), '1.0.1');
        wp_enqueue_script('wc-addons-frontend', plugin_dir_url(__FILE__) . 'js/frontend.js', array('jquery'), '1.0.1', true);
        global $product;
        $base_price = $product ? floatval($product->get_price()) : 0;
        wp_localize_script('wc-addons-frontend', 'wc_addons_params', array(
            'base_price' => $base_price,
            'currency_symbol' => get_woocommerce_currency_symbol(),
        ));
    }
}