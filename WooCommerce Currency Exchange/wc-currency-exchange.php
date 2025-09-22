<?php
/*
Plugin Name: WC Currency Exchange
Description: Allow customers to select a currency and display WooCommerce prices converted using admin-defined exchange rates. Base currency is INR by default.
Version: 1.0.0
Author: Manu Agarwal
*/

if ( ! defined( 'ABSPATH' ) ) { exit; }

add_action( 'plugins_loaded', function() {
    if ( class_exists( 'WooCommerce' ) ) {
        require_once plugin_dir_path( __FILE__ ) . 'includes/class-wc-currency-exchange.php';
        new WC_Currency_Exchange();
    }
});