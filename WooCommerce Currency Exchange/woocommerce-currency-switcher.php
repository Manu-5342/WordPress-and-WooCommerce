<?php
/**
 * Plugin Name: WooCommerce Currency Switcher
 * Description: Add custom currencies with exchange rates and frontend switcher dropdown.
 * Version: 2.1
 * Author: Manu Agarwal
 */

if (!defined('ABSPATH')) exit;

define('WCCS_PATH', plugin_dir_path(__FILE__));
define('WCCS_URL', plugin_dir_url(__FILE__));

require_once WCCS_PATH.'includes/class-wccs-admin.php';
require_once WCCS_PATH.'includes/class-wccs-frontend.php';

new WCCS_Admin();
new WCCS_Frontend();