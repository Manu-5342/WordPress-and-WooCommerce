<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WC_Currency_Exchange {

    const OPTION_KEY = 'wc_currency_exchange_rates';
    const SESSION_KEY = 'wc_ce_currency';
    const BASE_CURRENCY = 'INR';

    public function __construct() {
        add_filter( 'woocommerce_get_settings_pages', [ $this, 'add_settings_page' ] );

        // Session and selection handlers
        add_action( 'init', [ $this, 'maybe_set_default_currency' ] );
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
        add_action( 'wp_footer', [ $this, 'render_currency_selector' ] );

        add_action( 'wp_ajax_wc_ce_set_currency', [ $this, 'ajax_set_currency' ] );
        add_action( 'wp_ajax_nopriv_wc_ce_set_currency', [ $this, 'ajax_set_currency' ] );

        // Display-only conversion using wc_price filters (keeps order/base currency intact)
        add_filter( 'wc_price_args', [ $this, 'filter_wc_price_args' ], 99, 2 );
        add_filter( 'wc_price', [ $this, 'filter_wc_price' ], 99, 4 );
        add_filter( 'woocommerce_currency_symbol', [ $this, 'filter_currency_symbol' ], 99, 2 );
    }

    public function add_settings_page( $settings ) {
        $settings[] = include 'settings-page.php';
        return $settings;
    }

    public function get_rates() {
        $rates = get_option( self::OPTION_KEY, [] );
        // Ensure base currency exists
        if ( empty( $rates ) || ! isset( $rates[ self::BASE_CURRENCY ] ) ) {
            $rates[ self::BASE_CURRENCY ] = [
                'rate'   => 1,
                'symbol' => '₹',
                'label'  => 'Indian Rupee',
                'enabled'=> true,
            ];
        }
        return $rates;
    }

    public function get_selected_currency() {
        $selected = WC()->session ? WC()->session->get( self::SESSION_KEY ) : null;
        if ( empty( $selected ) ) {
            $selected = self::BASE_CURRENCY;
        }
        return $selected;
    }

    public function maybe_set_default_currency() {
        if ( is_admin() ) { return; }
        if ( ! WC()->session ) { return; }
        if ( ! WC()->session->get( self::SESSION_KEY ) ) {
            WC()->session->set( self::SESSION_KEY, self::BASE_CURRENCY );
        }
    }

    public function enqueue_scripts() {
        if ( ! ( is_shop() || is_product() || is_cart() || is_checkout() || is_account_page() || is_front_page() || is_home() ) ) {
            return;
        }
        wp_enqueue_script( 'wc-currency-exchange', plugin_dir_url( dirname( __FILE__ ) ) . 'assets/js/currency-exchange.js', [ 'jquery' ], '1.0.0', true );
        wp_localize_script( 'wc-currency-exchange', 'WCCE', [
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'wc_ce_nonce' ),
        ] );
        $rates = $this->get_rates();
        $selected = $this->get_selected_currency();
        wp_add_inline_script( 'wc-currency-exchange', 'window.WCCE_DATA = ' . wp_json_encode( [ 'rates' => $rates, 'selected' => $selected ] ) . ';', 'before' );
    }

    public function render_currency_selector() {
        $rates = $this->get_rates();
        if ( empty( $rates ) ) return;
        $selected = $this->get_selected_currency();
        echo '<div id="wcce-selector" style="position:fixed;bottom:16px;right:16px;z-index:9999;background:#fff;border:1px solid #ddd;border-radius:6px;padding:8px 10px;box-shadow:0 6px 16px rgba(0,0,0,0.12);">';
        echo '<label style="margin-right:6px;">Currency:</label>';
        echo '<select id="wcce-select" style="min-width:140px;">';
        foreach ( $rates as $code => $data ) {
            if ( isset( $data['enabled'] ) && ! $data['enabled'] ) { continue; }
            $label = isset( $data['label'] ) ? $data['label'] : $code;
            printf( '<option value="%s" %s>%s</option>', esc_attr( $code ), selected( $selected, $code, false ), esc_html( $label . ' (' . $code . ')' ) );
        }
        echo '</select>';
        echo '</div>';
    }

    public function ajax_set_currency() {
        check_ajax_referer( 'wc_ce_nonce', 'nonce' );
        $code = isset( $_POST['code'] ) ? sanitize_text_field( $_POST['code'] ) : '';
        $rates = $this->get_rates();
        if ( $code && isset( $rates[ $code ] ) ) {
            if ( WC()->session ) {
                WC()->session->set( self::SESSION_KEY, $code );
            }
            wp_send_json_success( [ 'code' => $code ] );
        }
        wp_send_json_error( [ 'message' => 'Invalid currency' ] );
    }

    public function get_rate_for( $code ) {
        $rates = $this->get_rates();
        if ( isset( $rates[ $code ] ) ) {
            $rate = floatval( $rates[ $code ]['rate'] );
            return $rate > 0 ? $rate : 1;
        }
        return 1;
    }

    public function filter_wc_price_args( $args, $price ) {
        // Only adjust display on the frontend (including AJAX), keep admin/backend intact
        if ( is_admin() && ! wp_doing_ajax() ) {
            return $args;
        }
        $selected = $this->get_selected_currency();
        $rates = $this->get_rates();
        if ( isset( $rates[$selected] ) ) {
            $args['currency'] = $selected; // tells wc_price to render with this currency
        }
        return $args;
    }

    public function filter_wc_price( $return, $price, $args, $unformatted_price ) {
        // Only adjust display on the frontend (including AJAX)
        if ( is_admin() && ! wp_doing_ajax() ) {
            return $return;
        }
        $selected = $this->get_selected_currency();
        $rate = $this->get_rate_for( $selected );
        if ( $rate == 1 ) return $return;

        // Rebuild the formatted price using converted amount to avoid affecting calculations
        remove_filter( 'wc_price', [ $this, 'filter_wc_price' ], 99 );
        $converted = floatval( $unformatted_price ) * floatval( $rate );
        $new = wc_price( $converted, $args );
        add_filter( 'wc_price', [ $this, 'filter_wc_price' ], 99, 4 );
        return $new;
    }

    public function filter_currency_symbol( $currency_symbol, $currency ) {
        $rates = $this->get_rates();
        if ( isset( $rates[ $currency ]['symbol'] ) && $rates[ $currency ]['symbol'] !== '' ) {
            return $rates[ $currency ]['symbol'];
        }
        return $currency_symbol;
    }
}
