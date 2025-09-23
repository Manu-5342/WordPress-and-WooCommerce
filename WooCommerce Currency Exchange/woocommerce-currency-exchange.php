<?php
/*
Plugin Name: WooCommerce Currency Exchange
Description: Adds a WooCommerce settings tab to manage custom currencies and display them via shortcode.
Version: 1.1.0
Author: Manu Agarwal
*/

if ( ! defined( 'ABSPATH' ) ) exit;

require_once plugin_dir_path( __FILE__ ) . 'includes/class-wc-currency-exchange.php';

add_action( 'plugins_loaded', function() {
    if ( class_exists( 'WooCommerce' ) ) {
        new WC_Currency_Exchange();
    } else {
        // If WooCommerce isn't active, still set up the admin notice from the class.
        $instance = new WC_Currency_Exchange();
    }
});
