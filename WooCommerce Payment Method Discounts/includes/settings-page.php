<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WC_Settings_Payment_Method_Discounts extends WC_Settings_Page {

    public function __construct() {
        $this->id    = 'payment_method_discounts';
        $this->label = __( 'Payment Method Discounts', 'wc-payment-method-discounts' );
        parent::__construct();
    }

    public function get_settings() {
        $gateways = WC()->payment_gateways()->payment_gateways();
        $settings = [
            [ 'title' => __( 'Payment Method Discounts', 'wc-payment-method-discounts' ), 'type' => 'title', 'id' => 'wc_payment_method_discounts_section' ],
        ];

        foreach ( $gateways as $gateway_id => $gateway ) {
            $settings[] = [
                'title'    => sprintf( __( '%s Discount Type', 'wc-payment-method-discounts' ), $gateway->title ),
                'id'       => 'wc_payment_method_discounts_' . $gateway_id . '_type',
                'type'     => 'select',
                'options'  => [ 'none' => 'None', 'percent' => 'Percentage', 'fixed' => 'Fixed' ],
                'default'  => 'none'
            ];
            $settings[] = [
                'title'    => sprintf( __( '%s Discount Amount', 'wc-payment-method-discounts' ), $gateway->title ),
                'id'       => 'wc_payment_method_discounts_' . $gateway_id . '_amount',
                'type'     => 'number',
                'desc_tip' => true,
                'default'  => '0'
            ];
        }

        $settings[] = [
            'title'    => sprintf( __( '%s Discount Label', 'wc-payment-method-discounts' ), $gateway->title ),
            'id'       => 'wc_payment_method_discounts_' . $gateway_id . '_label',
            'type'     => 'text',
            'desc_tip' => true,
            'default'  => sprintf( __( 'Discount for %s payment', 'wc-payment-method-discounts' ), $gateway->title )
        ];
        return $settings;
    }

    public function save() {
        // Call parent save to store fields
        parent::save();
    
        // Collect all discounts and save as array
        $gateways = WC()->payment_gateways()->payment_gateways();
        $discounts = [];
    
        foreach ( $gateways as $gateway_id => $gateway ) {
            $type_option   = 'wc_payment_method_discounts_' . $gateway_id . '_type';
            $amount_option = 'wc_payment_method_discounts_' . $gateway_id . '_amount';
    
            $type   = get_option( $type_option, 'none' );
            $amount = floatval( get_option( $amount_option, 0 ) );
    
            $discounts[$gateway_id] = [
                'type'   => $type,
                'amount' => $amount,
                'label'  => get_option( 'wc_payment_method_discounts_' . $gateway_id . '_label', '' )
            ];
        }
    
        update_option( 'wc_payment_method_discounts', $discounts );
    }
    
}

return new WC_Settings_Payment_Method_Discounts();
