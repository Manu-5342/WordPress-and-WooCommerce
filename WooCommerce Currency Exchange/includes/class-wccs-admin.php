<?php
class WCCS_Admin {
    private $option_name = 'wccs_currencies';
    private $defaults;

    public function __construct() {
        $this->defaults = include WCCS_PATH.'includes/currencies.php';
        add_filter('woocommerce_settings_tabs_array', [$this,'tab'],50);
        add_action('woocommerce_settings_tabs_currency_switcher', [$this,'content']);
    }

    public function tab($tabs){
        $tabs['currency_switcher'] = __('Currency Switcher','wccs');
        return $tabs;
    }

    public function content(){
        if(isset($_POST['wccs_nonce']) && wp_verify_nonce($_POST['wccs_nonce'],'save_wccs')){
            $this->save();
            echo '<div class="updated"><p>'.__('Saved','wccs').'</p></div>';
        }

        $currencies = get_option($this->option_name,$this->defaults);
        ?>
        <h2><?php _e('Currency Switcher Settings','wccs'); ?></h2>
        <form method="post">
            <?php wp_nonce_field('save_wccs','wccs_nonce'); ?>
            <table class="widefat">
                <thead><tr>
                    <th><?php _e('Currency Code','wccs'); ?></th>
                    <th><?php _e('Symbol','wccs'); ?></th>
                    <th><?php _e('Exchange Rate','wccs'); ?></th>
                    <th><?php _e('Country','wccs'); ?></th>
                    <th><?php _e('Remove','wccs'); ?></th>
                </tr></thead>
                <tbody id="wccs-table-body">
                <?php foreach($currencies as $code=>$data): ?>
                <tr>
                    <td>
                        <select name="code[]">
                            <?php foreach($this->defaults as $c=>$d): ?>
                                <option value="<?php echo esc_attr($c); ?>" <?php selected($c,$code); ?>><?php echo esc_html($c); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td><input type="text" name="symbol[]" value="<?php echo esc_attr($data['symbol']); ?>"></td>
                    <td><input type="number" step="0.000001" min="0" name="rate[]" value="<?php echo esc_attr($data['rate']); ?>"></td>
                    <td><input type="text" name="country[]" value="<?php echo esc_attr($data['country']); ?>"></td>
                    <td><button type="button" class="button remove-row">Remove</button></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <p>
                <button type="button" class="button" id="wccs-add-row"><?php _e('Add Currency','wccs'); ?></button>
            </p>
            <p><button class="button-primary"><?php _e('Save','wccs'); ?></button></p>
        </form>
        <script>
        jQuery(function($){
            $('#wccs-add-row').click(function(){
                var row = `<tr>
                    <td><select name="code[]"><?php foreach($this->defaults as $c=>$d): ?><option value="<?php echo esc_attr($c); ?>"><?php echo esc_html($c); ?></option><?php endforeach; ?></select></td>
                    <td><input type="text" name="symbol[]" value=""></td>
                    <td><input type="number" step="0.000001" min="0" name="rate[]" value=""></td>
                    <td><input type="text" name="country[]" value=""></td>
                    <td><button type="button" class="button remove-row">Remove</button></td>
                </tr>`;
                $('#wccs-table-body').append(row);
            });
            $(document).on('click','.remove-row',function(){
                $(this).closest('tr').remove();
            });
        });
        </script>
        <?php
    }

    public function save(){
        $codes  = $_POST['code'] ?? [];
        $symbols= $_POST['symbol'] ?? [];
        $rates  = $_POST['rate'] ?? [];
        $countries = $_POST['country'] ?? [];
        $currencies=[];
        foreach($codes as $i=>$c){
            $c= strtoupper(sanitize_text_field($c));
            if(!$c) continue;
            $s= sanitize_text_field($symbols[$i]);
            $r= floatval($rates[$i]);
            $country= sanitize_text_field($countries[$i]);
            $currencies[$c]=['symbol'=>$s,'rate'=>$r,'country'=>$country];
        }
        update_option($this->option_name,$currencies);
    }
}