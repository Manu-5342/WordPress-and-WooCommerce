<?php
/**
 * Plugin Name: WooCommerce Currency Exchange
 * Description: Allows you to set custom exchange rates and display a currency switcher dropdown on the front-end.
 * Version: 1.3
 * Author: Manu Agarwal
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WC_Currency_Exchange {

    private $option_name = 'wc_currency_exchange_rates';

    public function __construct() {
        // Admin
        add_filter( 'woocommerce_settings_tabs_array', [ $this, 'add_settings_tab' ], 50 );
        add_action( 'woocommerce_settings_tabs_currency_exchange', [ $this, 'settings_tab_content' ] );
        add_action( 'woocommerce_update_options_currency_exchange', [ $this, 'update_settings' ] );

        // Frontend shortcode
        add_shortcode( 'wc_currency_exchange', [ $this, 'currency_switcher_shortcode' ] );

        // Frontend price filter
        add_filter( 'woocommerce_product_get_price', [ $this, 'convert_price' ], 10, 2 );
        add_filter( 'woocommerce_product_variation_get_price', [ $this, 'convert_price' ], 10, 2 );

        // Enqueue JS for cookie handling
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
    }

    /* ---------------- ADMIN ---------------- */

    public function add_settings_tab( $settings_tabs ) {
        $settings_tabs['currency_exchange'] = __( 'Currency Exchange', 'woocommerce-currency-exchange' );
        return $settings_tabs;
    }

    public function settings_tab_content() {
        if ( isset($_POST['wc_currency_exchange_nonce']) &&
            wp_verify_nonce($_POST['wc_currency_exchange_nonce'], 'wc_currency_exchange_save') ) {
            $this->update_settings();
            echo '<div class="updated"><p>' . __('Currencies updated.', 'woocommerce-currency-exchange') . '</p></div>';
        }

        $currencies = get_option( $this->option_name, [] );
        ?>
        <h2><?php _e('Currency Exchange Rates', 'woocommerce-currency-exchange'); ?></h2>
        <form method="post" action="">
            <?php wp_nonce_field( 'wc_currency_exchange_save', 'wc_currency_exchange_nonce' ); ?>
            <table class="widefat">
                <thead>
                    <tr>
                        <th><?php _e('Currency Code', 'woocommerce-currency-exchange'); ?></th>
                        <th><?php _e('Exchange Rate (vs INR)', 'woocommerce-currency-exchange'); ?></th>
                        <th><?php _e('Icon URL', 'woocommerce-currency-exchange'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><input type="text" name="currency_code[]" value="INR" readonly /></td>
                        <td><input type="text" name="exchange_rate[]" value="1.000000" readonly /></td>
                        <td><input type="text" name="currency_icon[]" value="" readonly /></td>
                    </tr>
                    <?php foreach ( $currencies as $code => $data ) :
                        if ( $code === 'INR' ) continue; ?>
                        <tr>
                            <td><input type="text" name="currency_code[]" value="<?php echo esc_attr( $code ); ?>" /></td>
                            <td><input type="text" name="exchange_rate[]" value="<?php echo esc_attr( $data['rate'] ); ?>" /></td>
                            <td><input type="text" name="currency_icon[]" value="<?php echo esc_attr( $data['icon'] ); ?>" /></td>
                        </tr>
                    <?php endforeach; ?>
                    <tr>
                        <td><input type="text" name="currency_code[]" placeholder="New currency code" /></td>
                        <td><input type="text" name="exchange_rate[]" placeholder="Rate" /></td>
                        <td><input type="text" name="currency_icon[]" placeholder="Icon URL" /></td>
                    </tr>
                </tbody>
            </table>
            <p><button type="submit" class="button-primary"><?php _e('Save Changes', 'woocommerce-currency-exchange'); ?></button></p>
        </form>
        <?php
    }

    public function update_settings() {
        $codes = isset($_POST['currency_code']) ? (array) $_POST['currency_code'] : [];
        $rates = isset($_POST['exchange_rate']) ? (array) $_POST['exchange_rate'] : [];
        $icons = isset($_POST['currency_icon']) ? (array) $_POST['currency_icon'] : [];

        $currencies = [];
        foreach ( $codes as $i => $code ) {
            $code = strtoupper( sanitize_text_field( $code ) );
            $rate = isset( $rates[ $i ] ) ? floatval( $rates[ $i ] ) : 0;
            $icon = isset( $icons[ $i ] ) ? esc_url_raw( $icons[ $i ] ) : '';
            if ( $code !== '' && $rate > 0 ) {
                $currencies[ $code ] = ['rate'=>$rate,'icon'=>$icon];
            }
        }
        // Ensure INR always exists
        $currencies['INR'] = ['rate'=>1,'icon'=>''];
        update_option( $this->option_name, $currencies );
    }

    /* ---------------- FRONTEND ---------------- */

    public function enqueue_scripts() {
        wp_enqueue_script( 'jquery-cookie', 'https://cdnjs.cloudflare.com/ajax/libs/jquery-cookie/1.4.1/jquery.cookie.min.js', ['jquery'], null, true );
        wp_add_inline_script( 'jquery-cookie', "
            jQuery(function($){
                $('#wc_currency_select').on('change', function(){
                    var val = $(this).val();
                    $.cookie('wc_currency', val, { path: '/' });
                    location.reload();
                });
            });
        ");
    }

    public function currency_switcher_shortcode() {
        $currencies = get_option( $this->option_name, [] );
        if ( empty( $currencies ) ) return '';

        $current = isset($_COOKIE['wc_currency']) ? sanitize_text_field($_COOKIE['wc_currency']) : 'INR';
        if ( ! isset($currencies[$current]) ) $current = 'INR';

        ob_start();
        ?>
        <div class="wc-currency-switcher">
            <label for="wc_currency_select"><?php _e('Select Currency:', 'woocommerce-currency-exchange'); ?></label>
            <select id="wc_currency_select">
                <?php foreach ( $currencies as $code => $data ): ?>
                    <option value="<?php echo esc_attr($code); ?>" <?php selected($current,$code); ?>>
                        <?php echo esc_html($code); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php
        return ob_get_clean();
    }

    public function convert_price( $price, $product ) {
        $currencies = get_option( $this->option_name, [] );
        $current = isset($_COOKIE['wc_currency']) ? sanitize_text_field($_COOKIE['wc_currency']) : 'INR';
        if ( isset($currencies[$current]) && $currencies[$current]['rate'] > 0 ) {
            $price = $price * $currencies[$current]['rate'];
        }
        return $price;
    }
}

new WC_Currency_Exchange();