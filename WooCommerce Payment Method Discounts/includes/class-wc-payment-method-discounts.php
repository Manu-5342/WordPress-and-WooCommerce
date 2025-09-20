<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WC_Payment_Method_Discounts {

    public function __construct() {
        add_filter( 'woocommerce_get_settings_pages', [ $this, 'add_settings_page' ] );
        add_action( 'woocommerce_cart_calculate_fees', [ $this, 'apply_payment_method_discount' ] );
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
    }

    public function add_settings_page( $settings ) {
        $settings[] = include 'settings-page.php';
        return $settings;
    }

    public function enqueue_scripts() {
        if ( is_cart() || is_checkout() ) {
            wp_enqueue_script( 'wc-payment-method-discounts', plugin_dir_url( dirname( __FILE__ ) ) . 'assets/js/payment-method-discounts.js', [ 'jquery', 'wc-checkout' ], '1.0', true );
        }
    }

    public function apply_payment_method_discount( $cart ) {
        if ( is_admin() && ! defined( 'DOING_AJAX' ) ) return;
        if ( empty( $cart->get_cart() ) ) return;
    
        $chosen_method = WC()->session->get( 'chosen_payment_method' );
        if ( empty( $chosen_method ) ) return;
    
        $discounts = get_option( 'wc_payment_method_discounts', [] );
        
        // Remove any existing payment method discounts first
        $this->remove_existing_discounts( $cart );
    
        if ( isset( $discounts[$chosen_method]['type'] ) && $discounts[$chosen_method]['type'] !== 'none' ) {
            $type   = $discounts[$chosen_method]['type'];
            $amount = floatval( $discounts[$chosen_method]['amount'] );
    
            if ( $type === 'percent' ) {
                $discount = $cart->get_subtotal() * ( $amount / 100 );
            } elseif ( $type === 'fixed' ) {
                $discount = $amount;
            } else {
                $discount = 0;
            }
    
            if ( $discount > 0 ) {
                // Use custom label if available
                $label = !empty( $discounts[$chosen_method]['label'] )
                    ? $discounts[$chosen_method]['label']
                    : sprintf( __( 'Discount for %s payment', 'wc-payment-method-discounts' ), $chosen_method );
    
                $cart->add_fee( $label, -$discount );
            }
        }
    }
    
    private function remove_existing_discounts( $cart ) {
        $discounts = get_option( 'wc_payment_method_discounts', [] );
        $fees = $cart->get_fees();
        
        foreach ( $fees as $fee_key => $fee ) {
            // Check if this fee is a payment method discount
            foreach ( $discounts as $gateway_id => $discount_config ) {
                $default_label = sprintf( __( 'Discount for %s payment', 'wc-payment-method-discounts' ), $gateway_id );
                $custom_label = !empty( $discount_config['label'] ) ? $discount_config['label'] : $default_label;
                
                if ( $fee->name === $custom_label || $fee->name === $default_label ) {
                    $cart->remove_fee( $fee_key );
                    break;
                }
            }
        }
    }
}
