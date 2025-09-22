<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WC_Settings_Currency_Exchange extends WC_Settings_Page {

    public function __construct() {
        $this->id    = 'currency_exchange';
        $this->label = __( 'Currency Exchange', 'wc-currency-exchange' );
        parent::__construct();
    }

    public function get_settings() {
        $settings = [
            [ 'title' => __( 'Currency Exchange', 'wc-currency-exchange' ), 'type' => 'title', 'id' => 'wc_currency_exchange_section', 'desc' => __( 'Define currencies and their exchange rates relative to the base currency (INR). Format: CODE|rate|symbol|label|enabled', 'wc-currency-exchange' ) ],
            [
                'title'   => __( 'Currencies', 'wc-currency-exchange' ),
                'id'      => 'wc_currency_exchange_table',
                'type'    => 'textarea',
                'css'     => 'width: 100%; height: 220px;',
                'default' => "USD|0.012|$|US Dollar|yes\nEUR|0.011|€|Euro|yes\nINR|1|₹|Indian Rupee|yes",
                'desc'    => __( 'One currency per line in the format CODE|rate|symbol|label|enabled. Example: USD|0.012|$|US Dollar|yes. Base currency INR must have rate 1.', 'wc-currency-exchange' ),
            ],
            [ 'type' => 'sectionend', 'id' => 'wc_currency_exchange_section' ],
        ];
        return $settings;
    }

    public function save() {
        // Save raw textarea for reference
        $raw_id = 'wc_currency_exchange_table';
        $raw = isset( $_POST[$raw_id] ) ? wp_unslash( $_POST[$raw_id] ) : '';
        update_option( $raw_id, $raw );

        // Parse and normalize
        $lines = preg_split( "/\r\n|\r|\n/", $raw );
        $parsed = [];
        foreach ( $lines as $line ) {
            $line = trim( $line );
            if ( $line === '' || strpos( $line, '|' ) === false ) { continue; }
            list( $code, $rate, $symbol, $label, $enabled ) = array_map( 'trim', array_pad( explode( '|', $line ), 5, '' ) );
            if ( $code === '' ) { continue; }
            $code = strtoupper( sanitize_text_field( $code ) );
            $rate = floatval( $rate );
            if ( $code === WC_Currency_Exchange::BASE_CURRENCY ) {
                $rate = 1; // force base to 1
            }
            $parsed[ $code ] = [
                'rate'    => $rate > 0 ? $rate : 1,
                'symbol'  => sanitize_text_field( $symbol ),
                'label'   => $label !== '' ? sanitize_text_field( $label ) : $code,
                'enabled' => ( strtolower( $enabled ) === 'yes' || strtolower( $enabled ) === 'true' ),
            ];
        }

        if ( ! isset( $parsed[ WC_Currency_Exchange::BASE_CURRENCY ] ) ) {
            $parsed[ WC_Currency_Exchange::BASE_CURRENCY ] = [
                'rate'   => 1,
                'symbol' => '₹',
                'label'  => 'Indian Rupee',
                'enabled'=> true,
            ];
        }

        update_option( WC_Currency_Exchange::OPTION_KEY, $parsed );
    }
}

return new WC_Settings_Currency_Exchange();
