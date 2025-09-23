<?php
class WCCS_Frontend {
    private $option_name='wccs_currencies';

    public function __construct(){
        // Convert numeric prices
        add_filter('woocommerce_product_get_price',[$this,'convert'],10,2);
        add_filter('woocommerce_product_get_regular_price',[$this,'convert'],10,2);
        add_filter('woocommerce_product_get_sale_price',[$this,'convert'],10,2);
        add_filter('woocommerce_product_variation_get_price',[$this,'convert'],10,2);
        add_filter('woocommerce_product_variation_get_regular_price',[$this,'convert'],10,2);
        add_filter('woocommerce_product_variation_get_sale_price',[$this,'convert'],10,2);

        // Override WooCommerce symbol
        add_filter('woocommerce_currency_symbol',[$this,'currency_symbol'],10,2);

        // Shortcode dropdown
        add_shortcode('wccs_switcher',[$this,'shortcode']);
        add_action('wp_enqueue_scripts',[$this,'assets']);
    }

    public function assets(){
        wp_enqueue_style('wccs-style',WCCS_URL.'assets/css/wccs-style.css');
        wp_enqueue_script('wccs-script',WCCS_URL.'assets/js/wccs-script.js',['jquery'],null,true);
    }

    private function get_selected_currency(){
        return isset($_COOKIE['wccs_currency']) ? sanitize_text_field($_COOKIE['wccs_currency']) : 'INR';
    }

    private function get_currencies(){
        return get_option($this->option_name,include WCCS_PATH.'includes/currencies.php');
    }

    public function convert($price,$product){
        $currencies = $this->get_currencies();
        $current = $this->get_selected_currency();
        if(isset($currencies[$current]) && $currencies[$current]['rate']>0){
            $price = $price * $currencies[$current]['rate'];
        }
        return $price;
    }

    public function currency_symbol($symbol,$currency){
        $currencies = $this->get_currencies();
        $current = $this->get_selected_currency();
        if(isset($currencies[$current])){
            return $currencies[$current]['symbol'];
        }
        return $symbol;
    }

    public function shortcode($atts,$content=null){
        $currencies = $this->get_currencies();
        $current = $this->get_selected_currency();
        ob_start();
        ?>
        <div class="wccs-switcher">
            <label><?php _e('Currency','wccs'); ?>:</label>
            <select id="wccs_currency_select">
                <?php foreach($currencies as $code=>$data): ?>
                    <option value="<?php echo esc_attr($code); ?>" <?php selected($current,$code); ?>>
                        <?php echo esc_html($data['symbol'].' '.$code); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php
        return ob_get_clean();
    }
}