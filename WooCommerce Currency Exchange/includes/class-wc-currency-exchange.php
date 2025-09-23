<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WC_Currency_Exchange {

    private $option_name = 'wc_currency_exchange_rates';

    public function __construct() {
        if ( ! class_exists( 'WooCommerce' ) ) {
            add_action( 'admin_notices', [ $this, 'admin_notice_woocommerce_required' ] );
            return;
        }

        // Settings tab
        add_filter( 'woocommerce_settings_tabs_array', [ $this, 'add_settings_tab' ], 50 );
        add_action( 'woocommerce_settings_tabs_currency_exchange', [ $this, 'settings_tab_content' ] );
        add_action( 'woocommerce_update_options_currency_exchange', [ $this, 'update_settings' ] );

        // Shortcode
        add_shortcode( 'wc_currency_exchange', [ $this, 'currency_table_shortcode' ] );

        // Convert displayed prices (visual only)
        add_filter( 'woocommerce_get_price_html', [ $this, 'convert_price_html' ], 100, 2 );
        add_filter( 'woocommerce_variable_price_html', [ $this, 'convert_variable_price_html' ], 100, 2 );
    }

    public function admin_notice_woocommerce_required() {
        echo '<div class="notice notice-error"><p><strong>WooCommerce Currency Exchange</strong> requires WooCommerce to be active.</p></div>';
    }

    /* --- Settings tab --- */
    public function add_settings_tab( $tabs ) {
        $tabs['currency_exchange'] = __( 'Currency Exchange', 'woocommerce-currency-exchange' );
        return $tabs;
    }

    public function settings_tab_content() {
        $currencies = get_option( $this->option_name, [] );
        ?>
        <h2><?php _e( 'Currency Exchange Settings (Base: INR)', 'woocommerce-currency-exchange' ); ?></h2>
        <p><?php _e( 'Add currency code, rate vs INR, and optional icon.', 'woocommerce-currency-exchange' ); ?></p>

        <form method="post">
            <?php wp_nonce_field( 'wc_currency_exchange_save', 'wc_currency_exchange_nonce' ); ?>

            <table class="widefat" id="currency-exchange-table">
                <thead>
                    <tr>
                        <th><?php _e( 'Icon', 'woocommerce-currency-exchange' ); ?></th>
                        <th><?php _e( 'Currency Code', 'woocommerce-currency-exchange' ); ?></th>
                        <th><?php _e( 'Rate vs INR', 'woocommerce-currency-exchange' ); ?></th>
                        <th><?php _e( 'Remove', 'woocommerce-currency-exchange' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( ! empty( $currencies ) ) : 
                        foreach ( $currencies as $code => $data ) : 
                            $rate = isset( $data['rate'] ) ? $data['rate'] : '';
                            $icon = isset( $data['icon'] ) ? $data['icon'] : '';
                            ?>
                            <tr>
                                <td>
                                    <input type="hidden" name="currency_icon[]" value="<?php echo esc_attr( $icon ); ?>" class="currency-icon-input" />
                                    <img src="<?php echo esc_url( $icon ); ?>" style="width:30px;height:30px;object-fit:contain;" class="currency-icon-preview"/>
                                    <button type="button" class="button upload-currency-icon"><?php _e('Upload','woocommerce-currency-exchange'); ?></button>
                                </td>
                                <td><input type="text" name="currency_code[]" value="<?php echo esc_attr( $code ); ?>" required /></td>
                                <td><input type="number" step="0.000001" min="0" name="currency_rate[]" value="<?php echo esc_attr( $rate ); ?>" required /></td>
                                <td><button class="button remove-currency" type="button">Remove</button></td>
                            </tr>
                        <?php endforeach;
                    endif; ?>
                    <tr>
                        <td>
                            <input type="hidden" name="currency_icon[]" value="" class="currency-icon-input" />
                            <img src="" style="width:30px;height:30px;object-fit:contain;" class="currency-icon-preview"/>
                            <button type="button" class="button upload-currency-icon"><?php _e('Upload','woocommerce-currency-exchange'); ?></button>
                        </td>
                        <td><input type="text" name="currency_code[]" placeholder="USD" /></td>
                        <td><input type="number" step="0.000001" min="0" name="currency_rate[]" placeholder="0.012" /></td>
                        <td></td>
                    </tr>
                </tbody>
            </table>

            <p>
                <button type="button" class="button" id="add-new-currency"><?php _e( 'Add Currency', 'woocommerce-currency-exchange' ); ?></button>
            </p>

            <?php submit_button( __( 'Save currencies', 'woocommerce-currency-exchange' ) ); ?>
        </form>

        <script>
        (function($){
            $('#add-new-currency').on('click',function(){
                $('#currency-exchange-table tbody').append(`
                    <tr>
                        <td>
                            <input type="hidden" name="currency_icon[]" value="" class="currency-icon-input" />
                            <img src="" style="width:30px;height:30px;object-fit:contain;" class="currency-icon-preview"/>
                            <button type="button" class="button upload-currency-icon"><?php _e('Upload','woocommerce-currency-exchange'); ?></button>
                        </td>
                        <td><input type="text" name="currency_code[]" placeholder="Code" required></td>
                        <td><input type="number" step="0.000001" min="0" name="currency_rate[]" placeholder="Rate" required></td>
                        <td><button class="button remove-currency" type="button">Remove</button></td>
                    </tr>`);
            });

            $('#currency-exchange-table').on('click','.remove-currency',function(){
                $(this).closest('tr').remove();
            });

            // Media uploader
            var mediaUploader;
            $('#currency-exchange-table').on('click','.upload-currency-icon',function(e){
                e.preventDefault();
                var button = $(this);
                var input = button.closest('td').find('.currency-icon-input');
                var preview = button.closest('td').find('.currency-icon-preview');

                if (mediaUploader) {
                    mediaUploader.open();
                    return;
                }

                mediaUploader = wp.media.frames.file_frame = wp.media({
                    title: 'Choose Icon',
                    button: {
                        text: 'Choose Icon'
                    },
                    multiple: false
                });

                mediaUploader.on('select', function(){
                    var attachment = mediaUploader.state().get('selection').first().toJSON();
                    input.val(attachment.url);
                    preview.attr('src', attachment.url);
                });

                mediaUploader.open();
            });
        })(jQuery);
        </script>
        <?php
    }

    public function update_settings() {
        if ( ! current_user_can( 'manage_woocommerce' ) ) return;
        if ( ! isset( $_POST['wc_currency_exchange_nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['wc_currency_exchange_nonce'] ), 'wc_currency_exchange_save' ) ) {
            return;
        }

        $codes = isset( $_POST['currency_code'] ) ? (array) $_POST['currency_code'] : [];
        $rates = isset( $_POST['currency_rate'] ) ? (array) $_POST['currency_rate'] : [];
        $icons = isset( $_POST['currency_icon'] ) ? (array) $_POST['currency_icon'] : [];
        $currencies = [];

        foreach ( $codes as $i => $code ) {
            $code = strtoupper( sanitize_text_field( $code ) );
            $rate = isset( $rates[ $i ] ) ? floatval( $rates[ $i ] ) : 0;
            $icon = isset( $icons[ $i ] ) ? esc_url_raw( $icons[ $i ] ) : '';
            if ( $code !== '' && $rate > 0 ) {
                $currencies[ $code ] = ['rate'=>$rate,'icon'=>$icon];
            }
        }

        update_option( $this->option_name, $currencies );
    }

    /* --- Shortcode table --- */
    public function currency_table_shortcode( $atts = [] ) {
        $currencies = get_option( $this->option_name, [] );
        if ( empty( $currencies ) ) {
            return '<p>' . __( 'No currencies configured. Add currencies in WooCommerce → Currency Exchange.', 'woocommerce-currency-exchange' ) . '</p>';
        }

        $selected = $this->get_selected_currency();

        ob_start();
        ?>
        <table class="wc-currency-table" style="border-collapse:collapse;width:100%;">
            <thead>
                <tr>
                    <th><?php _e('Select','woocommerce-currency-exchange'); ?></th>
                    <th><?php _e('Icon','woocommerce-currency-exchange'); ?></th>
                    <th><?php _e('Currency','woocommerce-currency-exchange'); ?></th>
                    <th><?php _e('Rate vs INR','woocommerce-currency-exchange'); ?></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><input type="radio" name="wc_selected_currency" value="" <?php checked( $selected, '' ); ?>></td>
                    <td></td>
                    <td><?php echo esc_html( get_woocommerce_currency() ); ?> (Base INR)</td>
                    <td>1.00</td>
                </tr>
                <?php foreach ( $currencies as $code => $data ) : 
                    $rate = $data['rate'];
                    $icon = $data['icon'];
                    ?>
                    <tr>
                        <td><input type="radio" name="wc_selected_currency" value="<?php echo esc_attr( $code ); ?>" <?php checked( $selected, $code ); ?>></td>
                        <td><?php if($icon): ?><img src="<?php echo esc_url( $icon ); ?>" style="width:30px;height:30px;object-fit:contain;"><?php endif; ?></td>
                        <td><?php echo esc_html( $code ); ?></td>
                        <td><?php echo esc_html( $rate ); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <script>
        (function(){
            function setCookie(name, value, days) {
                var expires = "";
                if (days) {
                    var date = new Date();
                    date.setTime(date.getTime() + (days*24*60*60*1000));
                    expires = "; expires=" + date.toUTCString();
                }
                document.cookie = name + "=" + (value || "")  + expires + "; path=/";
            }
            document.querySelectorAll('.wc-currency-table input[type=radio][name=wc_selected_currency]').forEach(function(radio){
                radio.addEventListener('change', function(){
                    var val = this.value;
                    if ( val === '' ) {
                        setCookie('wc_selected_currency', '', -1);
                    } else {
                        setCookie('wc_selected_currency', val, 7);
                    }
                    location.reload();
                });
            });
        })();
        </script>
        <?php
        return ob_get_clean();
    }

    private function get_selected_currency() {
        if ( isset( $_COOKIE['wc_selected_currency'] ) ) {
            return strtoupper( sanitize_text_field( wp_unslash( $_COOKIE['wc_selected_currency'] ) ) );
        }
        return '';
    }

    private function get_selected_currency_rate() {
        $selected = $this->get_selected_currency();
        $currencies = get_option( $this->option_name, [] );
        if ( $selected && isset( $currencies[ $selected ] ) ) {
            return floatval( $currencies[ $selected ]['rate'] );
        }
        return 1.0;
    }

    /* --- Price display conversion --- */
    public function convert_price_html( $price_html, $product ) {
        $selected = $this->get_selected_currency();
        if ( ! $selected ) return $price_html;
        $rate = $this->get_selected_currency_rate();
        if ( $rate == 1 ) return $price_html;

        $price = $product->get_price();
        if ( $price === '' ) $price = $product->get_regular_price();
        if ( $price === '' ) return $price_html;

        $converted = floatval($price) * $rate;
        return wc_price( $converted, array( 'currency' => $selected ) );
    }

    public function convert_variable_price_html( $price_html, $product ) {
        $selected = $this->get_selected_currency();
        if ( ! $selected ) return $price_html;
        $rate = $this->get_selected_currency_rate();
        if ( $rate == 1 ) return $price_html;

        $min = floatval( $product->get_variation_price( 'min', true ) );
        $max = floatval( $product->get_variation_price( 'max', true ) );

        $min_converted = $min * $rate;
        $max_converted = $max * $rate;

        if ( $min_converted === $max_converted ) {
            return wc_price( $min_converted, array( 'currency' => $selected ) );
        }

        return wc_price( $min_converted, array( 'currency' => $selected ) ) . ' - ' . wc_price( $max_converted, array( 'currency' => $selected ) );
    }
}
