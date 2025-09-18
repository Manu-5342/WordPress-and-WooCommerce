<?php
/*
Plugin Name: WC Payment Method Discounts
Description: Provide different discounts based on payment methods in WooCommerce.
Version: 1.2
Author: Manu Agarwal
*/

if ( !defined( 'ABSPATH' ) ) exit;

require_once plugin_dir_path(__FILE__) . 'includes/class-wc-payment-method-discounts.php';

add_action( 'plugins_loaded', function() {
    if ( class_exists( 'WooCommerce' ) ) {
        new WC_Payment_Method_Discounts();
    }
});
