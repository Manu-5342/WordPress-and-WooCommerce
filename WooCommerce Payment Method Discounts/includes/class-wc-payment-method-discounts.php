<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WC_Payment_Method_Discounts {

    public function __construct() {
        add_filter( 'woocommerce_get_settings_pages', [ $this, 'add_settings_page' ] );
        add_action( 'woocommerce_cart_calculate_fees', [ $this, 'apply_payment_method_discount' ] );
    }

    public function add_settings_page( $settings ) {
        $settings[] = include 'settings-page.php';
        return $settings;
    }

    public function apply_payment_method_discount( $cart ) {
        if ( is_admin() && ! defined( 'DOING_AJAX' ) ) return;
        if ( empty( $cart->get_cart() ) ) return;
    
        $chosen_method = WC()->session->get( 'chosen_payment_method' );
        if ( empty( $chosen_method ) ) return;
    
        $discounts = get_option( 'wc_payment_method_discounts', [] );
    
        if ( isset( $discounts[$chosen_method]['type'] ) ) {
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
}
