<?php
/*
Plugin Name: WooCommerce Product Add-Ons
Description: Allows adding custom add-ons to WooCommerce products with prices and display options (checkbox, radio, select).
Version: 1.0
Author: Abhishek
License: GPL2
*/

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Check if WooCommerce is active
if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    // Include admin and frontend files
    require_once plugin_dir_path(__FILE__) . 'admin/admin.php';
    require_once plugin_dir_path(__FILE__) . 'frontend/frontend.php';
} else {
    add_action('admin_notices', function() {
        echo '<div class="error"><p>WooCommerce is required for this plugin to work.</p></div>';
    });
}