<?php
/**
 * Plugin Name: BitPay Checkout for WooCommerce
 * Plugin URI: https://www.bitpay.com
 * Description: Create Invoices and process through BitPay.  Configure in your <a href ="admin.php?page=wc-settings&tab=checkout&section=bitpay_checkout_gateway">WooCommerce->Payments plugin</a>.
 * Version: 4.1.0
 * Author: BitPay
 * Author URI: mailto:integrations@bitpay.com?subject=BitPay Checkout for WooCommerce
 */
if (!defined('ABSPATH')): exit;endif;
add_action('wp_enqueue_scripts', 'enable_bitpayquickpay_js');
global $current_user;
function add_error_page() {
    
    $my_post = array(
      'post_title'    => wp_strip_all_tags( 'Order Cancelled' ),
      'post_content'  => 'Your order stands cancelled. Please go back to <a href="/shop">Shop page</a> and reorder.',
      'post_status'   => 'publish',
      'post_author'   => "Bitpay",
      'post_type'     => 'page',
    );

    // Insert the post into the database
    wp_insert_post( $my_post );
}

register_activation_hook(__FILE__, 'add_error_page');
#autoloader
function BPC_autoloader($class)
{
    if (strpos($class, 'BPC_') !== false):
        if (!class_exists('BitPayLib/' . $class, false)):
            #doesnt exist so include it
            include 'BitPayLib/' . $class . '.php';
        endif;
    endif;
}
function BPC_Logger($msg, $type = null, $isJson = false, $error = false)
{
    $bitpay_checkout_options = get_option('woocommerce_bitpay_checkout_gateway_settings');
    $structure = plugin_dir_path(__FILE__) . 'logs/';
    if (!file_exists($structure)) {
        mkdir($structure);
    }
    $transaction_log = plugin_dir_path(__FILE__) . 'logs/' . date('Ymd') . '_transactions.log';
    $error_log = plugin_dir_path(__FILE__) . 'logs/' . date('Ymd') . '_error.log';

    $header = PHP_EOL . '======================' . $type . '===========================' . PHP_EOL;
    $footer = PHP_EOL . '=================================================' . PHP_EOL;

    if ($error):
        error_log($header, 3, $error_log);
        error_log($msg, 3, $error_log);
        error_log($footer, 3, $error_log);
    else:
        if ($bitpay_checkout_options['bitpay_log_mode'] == 1):
            error_log($header, 3, $transaction_log);
            if ($isJson):
                error_log(print_r($msg, true), 3, $transaction_log);
            else:
                error_log($msg, 3, $transaction_log);
            endif;
            error_log($footer, 3, $transaction_log);
        endif;
    endif;
}

spl_autoload_register('BPC_autoloader');

#check and see if requirements are met for turning on plugin
function _isCurl()
{
    return function_exists('curl_version');
}

function bitpay_checkout_woocommerce_bitpay_failed_requirements()
{
    global $wp_version;
    global $woocommerce;
    $errors = array();

    // WooCommerce required
    if (true === empty($woocommerce)) {
        $errors[] = 'The WooCommerce plugin for WordPress needs to be installed and activated. Please contact your web server administrator for assistance.';
    } elseif (true === version_compare($woocommerce->version, '2.2', '<')) {
        $errors[] = 'Your WooCommerce version is too old. The BitPay payment plugin requires WooCommerce 2.2 or higher to function. Your version is ' . $woocommerce->version . '. Please contact your web server administrator for assistance.';
    } elseif (!_isCurl()) {
        $errors[] = 'cUrl needs to be installed/enabled for BitPay Checkout to function';
    }
    if (empty($errors)):
        return false;
    else:
        return implode("<br>\n", $errors);
    endif;
}

add_action('plugins_loaded', 'wc_bitpay_checkout_gateway_init', 11);
#create the table if it doesnt exist

#clear the cart if using a custom page
add_action( 'init', 'woocommerce_clear_cart_url' );
function woocommerce_clear_cart_url() {
	if ( isset( $_GET['custompage'] ) ) {
		global $woocommerce;
		$woocommerce->cart->empty_cart();
	}
}

function bitpay_checkout_plugin_setup()
{

    $failed = bitpay_checkout_woocommerce_bitpay_failed_requirements();
    $plugins_url = admin_url('plugins.php');

    if ($failed === false) {

        global $wpdb;
        $table_name = '_bitpay_checkout_transactions';

        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE IF NOT EXISTS $table_name(
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `order_id` varchar(255) NOT NULL,
        `transaction_id` varchar(255) NOT NULL,
        `transaction_status` varchar(50) NOT NULL DEFAULT 'new',
        `date_added` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);

        #check out of date plugins
        $plugins = get_plugins();
        foreach ($plugins as $file => $plugin) {
            if ('Bitpay Woocommerce' === $plugin['Name'] && true === is_plugin_active($file)) {
                deactivate_plugins(plugin_basename(__FILE__));
                wp_die('BitPay for WooCommerce requires that the old plugin, <b>Bitpay Woocommerce</b>, is deactivated and deleted.<br><a href="' . $plugins_url . '">Return to plugins screen</a>');
            }
        }

    } else {

        // Requirements not met, return an error message
        wp_die($failed . '<br><a href="' . $plugins_url . '">Return to plugins screen</a>');

    }

}
register_activation_hook(__FILE__, 'bitpay_checkout_plugin_setup');

function bitpay_checkout_insert_order_note($order_id = null, $transaction_id = null)
{
    global $wpdb;

    if ($order_id != null && $transaction_id != null):
        global $woocommerce;

    //Retrieve the order
    $order = new WC_Order($order_id);
    $order->set_transaction_id($transaction_id);
    $order->save();
    //Retrieve the transaction ID

        $table_name = '_bitpay_checkout_transactions';
        $wpdb->insert(
            $table_name,
            array(
                'order_id' => $order_id,
                'transaction_id' => $transaction_id,
            )
        );
    else:
        BPC_Logger('Missing values' . PHP_EOL . 'order id: ' . $order_id . PHP_EOL . 'transaction id: ' . $transaction_id, 'error', false, true);
    endif;

}

function bitpay_checkout_update_order_note($order_id = null, $transaction_id = null, $transaction_status = null)
{
    global $wpdb;
    $table_name = '_bitpay_checkout_transactions';
    if ($order_id != null && $transaction_id != null && $transaction_status != null):
        $wpdb->update($table_name, array('transaction_status' => $transaction_status), array("order_id" => $order_id, 'transaction_id' => $transaction_id));
    else:
        BPC_Logger('Missing values' . PHP_EOL . 'order id: ' . $order_id . PHP_EOL . 'transaction id: ' . $transaction_id . PHP_EOL . 'transaction status: ' . $transaction_status . PHP_EOL, 'error', false, true);
    endif;
}

function bitpay_checkout_get_order_transaction($order_id, $transaction_id)
{
    global $wpdb;
    $table_name = '_bitpay_checkout_transactions';
    $rowcount = $wpdb->get_var($wpdb->prepare("SELECT COUNT(order_id) FROM $table_name WHERE transaction_id = %s",$transaction_id));
    return $rowcount;

}
function bitpay_checkout_get_order_id_bitpay_invoice_id($transaction_id)
{
    global $wpdb;
    $table_name = '_bitpay_checkout_transactions';
    $order_id = $wpdb->get_var($wpdb->prepare("SELECT order_id FROM $table_name WHERE transaction_id = %s LIMIT 1", $transaction_id));
    return $order_id;
}
function bitpay_checkout_delete_order_transaction($order_id)
{
    global $wpdb;
    $table_name = '_bitpay_checkout_transactions';
    $wpdb->query($wpdb->prepare("DELETE FROM $table_name WHERE order_id = %s",$order_id));

}

function wc_bitpay_checkout_gateway_init()
{     

    if (class_exists('WC_Payment_Gateway')) {
        class WC_Gateway_BitPay extends WC_Payment_Gateway
        {

            public function __construct()
            {
                $bitpay_checkout_options = get_option('woocommerce_bitpay_checkout_gateway_settings');

                $this->id = 'bitpay_checkout_gateway';
                $this->icon = BPC_getBitPaymentIcon();

                $this->has_fields = true;
                $this->method_title = __(BPC_getBitPayVersionInfo($clean = true), 'wc-bitpay');
                $this->method_label = __('BitPay', 'wc-bitpay');
                $this->method_description = __('Expand your payment options by accepting cryptocurrency payments (BTC, BCH, ETH, and Stable Coins) without risk or price fluctuations.', 'wc-bitpay');

                if (empty($_GET['woo-bitpay-return'])) {
                    $this->order_button_text = __('Pay with BitPay', 'woocommerce-gateway-bitpay_checkout_gateway');

                    
                }
                // Load the settings.
                $this->init_form_fields();
                $this->init_settings();

                // Define user set variables
                $this->title = 'BitPay';
                $this->description = $this->get_option('description') . '<br>';
                $this->instructions = $this->get_option('instructions', $this->description);

                // Actions
                add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));

                // Customer Emails
                add_action('woocommerce_email_before_order_table', array($this, 'email_instructions'), 10, 3);
            }
            public function email_instructions($order, $sent_to_admin, $plain_text = false)
            {
                if ($this->instructions && !$sent_to_admin && 'bitpay_checkout_gateway' === $order->get_payment_method() && $order->has_status('processing')) {
                    echo wp_kses_post(wpautop(wptexturize($this->instructions)) . PHP_EOL);
                }
            }
            public function init_form_fields()
            {
                $wc_statuses_arr = wc_get_order_statuses();
                unset($wc_statuses_arr['wc-cancelled']);
                unset($wc_statuses_arr['wc-refunded']);
                unset($wc_statuses_arr['wc-failed']);
                #add an ignore option
                $wc_statuses_arr['bitpay-ignore'] = "Do not change status";
                

               
                $this->form_fields = array(
                    'enabled' => array(
                        'title' => __('Enable/Disable', 'woocommerce'),
                        'label' => __('Enable BitPay', 'woocommerce'),
                        'type' => 'checkbox',
                        'description' => '',
                        'default' => 'no',
                    ),
                    'bitpay_checkout_info' => array(
                        'description' => __('You should not ship any products until BitPay has finalized your transaction.<br>The order will stay in a <b>Hold</b> and/or <b>Processing</b> state, and will automatically change to <b>Completed</b> after the payment has been confirmed.', 'woocommerce'),
                        'type' => 'title',
                    ),

                    'bitpay_checkout_merchant_info' => array(
                        'description' => __('If you have not created a BitPay Merchant Token, you can create one on your BitPay Dashboard.<br><a href = "https://test.bitpay.com/dashboard/merchant/api-tokens" target = "_blank">(Test)</a>  or <a href= "https://www.bitpay.com/dashboard/merchant/api-tokens" target = "_blank">(Production)</a> </p>', 'woocommerce'),
                        'type' => 'title',
                    ),

                    'bitpay_checkout_tier_info' => array(
                        'description' => __('<em><b>*** </b>If you are having trouble creating BitPay invoices, verify your Tier settings on your <a href = "https://support.bitpay.com/hc/en-us/articles/206003676-How-do-I-raise-my-approved-processing-volume-tier-limit-" target = "_blank">BitPay Dashboard</a>.</em>', 'woocommerce'),
                        'type' => 'title',
                    ),
                   
                    'description' => array(
                        'title' => __('Description', 'woocommerce'),
                        'type' => 'text',
                        'description' => __('This is the message box that will appear on the <b>checkout page</b> when they select BitPay.', 'woocommerce'),
                        'default' => 'Pay with BitPay using one of the supported cryptocurrencies',

                    ),

                    'bitpay_checkout_token_dev' => array(
                        'title' => __('Development Token', 'woocommerce'),
                        'label' => __('Development Token', 'woocommerce'),
                        'type' => 'text',
                        'description' => 'Your <b>development</b> merchant token.  <a href = "https://test.bitpay.com/dashboard/merchant/api-tokens" target = "_blank">Create one here</a> and <b>uncheck</b> `Require Authentication`.',
                        'default' => '',

                    ),
                    'bitpay_checkout_token_prod' => array(
                        'title' => __('Production Token', 'woocommerce'),
                        'label' => __('Production Token', 'woocommerce'),
                        'type' => 'text',
                        'description' => 'Your <b>production</b> merchant token.  <a href = "https://www.bitpay.com/dashboard/merchant/api-tokens" target = "_blank">Create one here</a> and <b>uncheck</b> `Require Authentication`.',
                        'default' => '',

                    ),

                    'bitpay_checkout_endpoint' => array(
                        'title' => __('Endpoint', 'woocommerce'),
                        'type' => 'select',
                        'description' => __('Select <b>Test</b> for testing the plugin, <b>Production</b> when you are ready to go live.'),
                        'options' => array(
                            'production' => 'Production',
                            'test' => 'Test',
                        ),
                        'default' => 'test',
                    ),

                    'bitpay_checkout_flow' => array(
                        'title' => __('Checkout Flow', 'woocommerce'),
                        'type' => 'select',
                        'description' => __('If this is set to <b>Redirect</b>, then the customer will be redirected to <b>BitPay</b> to checkout, and return to the checkout page once the payment is made.<br>If this is set to <b>Modal</b>, the user will stay on <b>' . get_bloginfo('name', null) . '</b> and complete the transaction.', 'woocommerce'),
                        'options' => array(
                            '1' => 'Modal',
                            '2' => 'Redirect',
                        ),
                        'default' => '2',
                    ),
                    'bitpay_checkout_slug' => array(
                        'title' => __('Checkout Page', 'woocommerce'),
                        'type' => 'text',
                        'description' => __('By default, this will be "checkout".  If you have a different Checkout page, enter the <b>page slug</b>. <br>ie. ' . get_home_url() . '/<b>checkout</b><br><br>View your pages <a target = "_blank" href  = "/wp-admin/edit.php?post_type=page">here</a>, your current checkout page should have <b>Checkout Page</b> next to the title.<br><br>Click the "quick edit" and copy and paste a custom slug here if needed.', 'woocommerce'),

                        'default' => 'checkout',
                    ),
                    'bitpay_custom_redirect' => array(
                        'title' => __('Custom Redirect Page', 'woocommerce'),
                        'type' => 'text',
                        'description' => __('Set the full url  (ie. <i>https://yoursite.com/custompage</i>) if you would like the customer to be redirected to a custom page after completing the purchase.<br>Leave this empty to redirect customers to the default Woocommerce order completed page.<br><b>Note: this will only work if the REDIRECT mode is used</b> ', 'woocommerce'),
                    ),
					'bitpay_close_url' => array(
                        'title' => __('Close URL', 'woocommerce'),
                        'type' => 'text',
                        'description' => __('Set the close url.<br>Leave this empty to redirect customers to the default Woocommerce order payment failed page.<br /><b>Note: this will only work if the REDIRECT mode is used</b> ', 'woocommerce'),
                    ),
                    'bitpay_checkout_mini' => array(
                        'title' => __('Show in mini cart ', 'woocommerce'),
                        'type' => 'select',
                        'description' => __('Set to YES if you would like to show BitPay as an immediate checkout option in the mini cart', 'woocommerce'),
                        'options' => array(
                            '1' => 'Yes',
                            '2' => 'No',
                        ),
                        'default' => '2',
                    ),
          

                    'bitpay_checkout_capture_email' => array(
                        'title' => __('Auto-Capture Email', 'woocommerce'),
                        'type' => 'select',
                        'description' => __('Should BitPay try to auto-add the client\'s email address?  If <b>Yes</b>, the client will not be able to change the email address on the BitPay invoice.  If <b>No</b>, they will be able to add their own email address when paying the invoice.', 'woocommerce'),
                        'options' => array(
                            '1' => 'Yes',
                            '0' => 'No',

                        ),
                        'default' => '1',
                    ),
                    'bitpay_checkout_checkout_message' => array(
                        'title' => __('Checkout Message', 'woocommerce'),
                        'type' => 'textarea',
                        'description' => __('Insert your custom message for the <b>Order Received</b> page, so the customer knows that the order will not be completed until BitPay releases the funds.', 'woocommerce'),
                        'default' => 'Thank you.  We will notify you when BitPay has processed your transaction.',
                    ),
                    'bitpay_checkout_error' => array(
                        'title' => __('Error handling', 'woocommerce'),
                        'type' => 'text',
                        'description' => __('If there is an error with creating the invoice, enter the <b>page slug</b>.<br>Leave this empty to redirect customers to the default Woocommerce order payment failed page. <br>ie. ' . get_home_url() . '/<b>error</b><br><br>View your pages <a target = "_blank" href  = "/wp-admin/edit.php?post_type=page">here</a>,.<br><br>Click the "quick edit" and copy and paste a custom slug here.', 'woocommerce'),
                       
                    ),
					'bitpay_checkout_error_message' => array(
                        'title' => __('Error Message', 'woocommerce'),
                        'type' => 'textarea',
                        'description' => __('Insert your custom message for the <b>Error</b> page, so the customer knows that there is some issue in paying the invoice', 'woocommerce'),
                        'default' => 'Transaction Cancelled',
                    ),
                    'bitpay_checkout_order_process_confirmed_status' => array(
                        'title' => __('BitPay Confirmed Invoice Status', 'woocommerce'),
                        'type' => 'select',
                        'description' => __('Configure your Transaction Speeds on your <a href = "'.BPC_getProcessingLink().'" target = "_blank">BitPay Dashboard</a>, and map the BitPay <b>confirmed</b> invoice status to one of the available WooCommerce order states.<br>All WooCommerce status options are listed here for your convenience.<br><br><em>Note: setting the status to <b>Completed</b> will reduce stock levels included in the order.  <b>BitPay Complete Invoice Status</b> should <b>NOT</b> be set to <b>Completed</b>, if using <b>BitPay Confirmed Invoice Status</b> to mark the order as complete.</em><br><br><em>Click <a href = "https://bitpay.com/docs/invoice-states" target = "_blank">here</a> for more information about BitPay invoice statuses.</em>', 'woocommerce'),
                       'options' =>$wc_statuses_arr,
                        'default' => 'wc-processing',
                    ),
                    'bitpay_checkout_order_process_complete_status' => array(
                        'title' => __('BitPay Complete Invoice Status', 'woocommerce'),
                        'type' => 'select',
                        'description' => __('Configure your Transaction Speeds on your <a href = "'.BPC_getProcessingLink().'" target = "_blank">BitPay Dashboard</a>, and map the BitPay <b>complete</b> invoice status to one of the available WooCommerce order states.<br>All WooCommerce status options are listed here for your convenience.<br><br><em>Note: setting the status to <b>Completed</b> will reduce stock levels included in the order.  <b>BitPay Confirmed Invoice Status</b> should <b>NOT</b> be set to <b>Completed</b>, if using <b>BitPay Complete Invoice Status</b> to mark the order as complete.</em><br><br><em>Click <a href = "https://bitpay.com/docs/invoice-states" target = "_blank">here</a> for more information about BitPay invoice statuses.</em>', 'woocommerce'),
                       'options' =>$wc_statuses_arr,
                        'default' => 'wc-processing',
                    ),
                    'bitpay_checkout_order_expired_status' => array(
                        'title' => __('BitPay Expired Status', 'woocommerce'),
                        'type' => 'select',
                        'description' => __('If set to <b>Yes</b>,  automatically set the order to canceled when the invoice has expired and has been notified by the BitPay IPN.', 'woocommerce'),
                       
                        'options' => array(
                            '0'=>'No',
                            '1'=>'Yes'
                        ),
                        'default' => '0',
                    ),
                   

                    'bitpay_log_mode' => array(
                        'title' => __('Developer Logging', 'woocommerce'),
                        'type' => 'select',
                        'description' => __('Errors will be logged to the plugin <b>log</b> directory automatically.  Set to <b>Enabled</b> to also log transactions, ie invoices and IPN updates', 'woocommerce'),
                        'options' => array(
                            '0' => 'Disabled',
                            '1' => 'Enabled',
                        ),
                        'default' => '1',
                    ),

                );
            }
            
            function process_payment($order_id)
            {
                #this is the one that is called intially when someone checks out
                global $woocommerce;
                $order = new WC_Order($order_id);
                // Return thankyou redirect
                return array(
                    'result' => 'success',
                    'redirect' => $this->get_return_url($order),
                );
            }
        } // end \WC_Gateway_Offline class
    } //end check for class existence
    else {
            global $wpdb;
            if (!function_exists('get_plugins')) {
                require_once ABSPATH . 'wp-admin/includes/plugin.php';
            }

            $plugins_url = admin_url('plugins.php');
            $plugins = get_plugins();
            foreach ($plugins as $file => $plugin) {

                if ('BitPay Checkout for WooCommerce' === $plugin['Name'] && true === is_plugin_active($file)) {

                    deactivate_plugins(plugin_basename(__FILE__));
                    wp_die('WooCommerce needs to be installed and activated before BitPay Checkout for WooCommerce can be activated.<br><a href="' . $plugins_url . '">Return to plugins screen</a>');

                }
            }

        }

    }


//update the order_id field in the custom table, try and create the table if this is called before the original
add_action('admin_notices', 'update_db_1');
function update_db_1()
{
   

    if (isset($_GET['section'])  && $_GET['section'] == 'bitpay_checkout_gateway'  && is_admin()):
        if(get_option('bitpay_wc_checkout_db1') != 1):
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $table_name = '_bitpay_checkout_transactions';
       
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table_name(
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `order_id` varchar(255) NOT NULL,
            `transaction_id` varchar(255) NOT NULL,
            `transaction_status` varchar(50) NOT NULL DEFAULT 'new',
            `date_added` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
            ) $charset_collate;";

        dbDelta($sql);
        $sql = "ALTER TABLE `$table_name` CHANGE `order_id` `order_id` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL; ";
        $wpdb->query($sql);
        update_option('bitpay_wc_checkout_db1',1);
        endif;
      
    endif;
}

add_action('admin_notices', 'bitpay_checkout_check_token');
function bitpay_checkout_check_token()
{

    if (isset($_GET['section'])):
        if ($_GET['section'] == 'bitpay_checkout_gateway' && $_POST && is_admin()):
            if (!file_exists(plugin_dir_path(__FILE__) . 'logs')) {
                mkdir(plugin_dir_path(__FILE__) . 'logs', 0755, true);
            }
            //lookup the token based on the environment
            $bitpay_checkout_options = get_option('woocommerce_bitpay_checkout_gateway_settings');
            //dev or prod token

            $bitpay_checkout_token = BPC_getBitPayToken($bitpay_checkout_options['bitpay_checkout_endpoint']);
            $bitpay_checkout_endpoint = $bitpay_checkout_options['bitpay_checkout_endpoint'];
            if (empty($bitpay_checkout_token)): ?>
<?php _e('There is no token set for your <b>' . strtoupper($bitpay_checkout_endpoint) . '</b> environment.  <b>BitPay</b> will not function if this is not set.');?>
<?php
        ##check and see if the token is valid
        else:
            if ($_POST && !empty($bitpay_checkout_token) && !empty($bitpay_checkout_endpoint)) {
                if (!BPC_isValidBitPayToken($bitpay_checkout_token, $bitpay_checkout_endpoint)): ?>
<div class="error notice">
    <p>
        <?php _e('The token for <b>' . strtoupper($bitpay_checkout_endpoint) . '</b> is invalid.  Please verify your settings.');?>
    </p>
</div>
<?php endif;
        }

    endif;

    endif;
    endif;

}

//show on the mini cart 
function bitpay_mini_checkout() {
    $bitpay_checkout_options = get_option('woocommerce_bitpay_checkout_gateway_settings');
    $bitpay_checkout_mini = $bitpay_checkout_options['bitpay_checkout_mini'];
    if($bitpay_checkout_mini == 1):
    $url = get_permalink( get_option( 'woocommerce_checkout_page_id' ) ); 
    $url.='?payment=bitpay';
    
    ?>
<script type="text/javascript">
    //widget_shopping_cart_content
    var obj = document.createElement("div");
    // obj.style.cssText = 'margin:0 auto;cursor:pointer';
    obj.innerHTML = '<img style = "margin:0 auto;cursor:pointer;padding-bottom:10px;" onclick = "bpMiniCheckout()" src = "//bitpay.com/cdn/merchant-resources/pay-with-bitpay-card-group.svg">'

    var miniCart = document.getElementsByClassName("widget_shopping_cart_content")[0];
    miniCart.appendChild(obj);

    function bpMiniCheckout() {
        let checkoutUrl = '<?php echo $url;?>';
        window.location = checkoutUrl

    }
</script>
<?php
    endif;
}

add_action( 'woocommerce_widget_shopping_cart_buttons', 'bitpay_mini_checkout', 20 );

//redirect to cart if bitpay single page enabled
function bp_redirect_to_checkout( $url ) {

   
    $url = get_permalink( get_option( 'woocommerce_checkout_page_id' ) ); 
    $url.='?payment=bitpay';

    $bitpay_checkout_options = get_option('woocommerce_bitpay_checkout_gateway_settings');
    $bitpay_checkout_product = $bitpay_checkout_options['bitpay_checkout_product'];
   
    if($bitpay_checkout_product == 1):
       return $url;
    endif;
 }
#add_filter( 'woocommerce_add_to_cart_redirect', 'bp_redirect_to_checkout' );

 function bitpay_default_payment_gateway(){
     if( is_checkout() && ! is_wc_endpoint_url() ) {
        global $woocommerce;
        unset($gateways['WC_Gateway_BitPay']);
         // HERE define the default payment gateway ID
        $bitpay_checkout_options = get_option('woocommerce_bitpay_checkout_gateway_settings');
        $bitpay_checkout_product = $bitpay_checkout_options['bitpay_checkout_product'];
        $default_payment_id = 'bitpay_checkout_gateway';
        if($bitpay_checkout_product == 1 && isset($_GET['payment']) && $_GET['payment'] == 'bitpay'):
           
            WC()->session->set( 'chosen_payment_method', $default_payment_id );
        
        endif;
        
     }
 }
 function enable_bitpayquickpay_js()
{
    wp_enqueue_script('bitpayquickpay-js', plugins_url('/js/bitpayquickpay_js.js', __FILE__));
}

 add_action( 'template_redirect', 'bitpay_default_payment_gateway' );

#http://<host>/wp-json/bitpay/ipn/status
add_action('rest_api_init', function () {
    register_rest_route('bitpay/ipn', '/status', array(
        'methods' => 'POST,GET',
        'callback' => 'bitpay_checkout_ipn',
        'permission_callback' => '__return_true',
    ));
    register_rest_route('bitpay/cartfix', '/restore', array(
        'methods' => 'POST,GET',
        'callback' => 'bitpay_checkout_cart_restore',
        'permission_callback' => '__return_true',
    ));
});

function bitpay_checkout_cart_restore(WP_REST_Request $request)
{
    // Load cart functions which are loaded only on the front-end.
    include_once WC_ABSPATH . 'includes/wc-cart-functions.php';
    include_once WC_ABSPATH . 'includes/class-wc-cart.php';

    if ( is_null( WC()->cart ) ) {
        wc_load_cart();
    }
    $data = $request->get_params();
    $order_id = $data['orderid'];
    $order = new WC_Order($order_id);
    $items = $order->get_items();

    BPC_Logger('User canceled order: ' . $order_id . ', removing from WooCommerce', 'USER CANCELED ORDER', true);
    $order->add_order_note('User closed the modal, the order will be set to canceled state');
    $order->update_status('canceled', __('BitPay payment canceled by user', 'woocommerce'));

    //clear the cart first so things dont double up
    WC()->cart->empty_cart();
    foreach ($items as $item) {
        //now insert for each quantity
        $item_count = $item->get_quantity();
        for ($i = 0; $i < $item_count; $i++):
            WC()->cart->add_to_cart($item->get_product_id());
        endfor;
    }
    //delete the previous order
   // wp_delete_post($order_id, true);
   // bitpay_checkout_delete_order_transaction($order_id);
   
   



    setcookie("bitpay-invoice-id", "", time() - 3600);
}

//http://<host>/wp-json/bitpay/ipn/status
function bitpay_checkout_ipn(WP_REST_Request $request)
{
    global $woocommerce;
    
    WC()->frontend_includes();
    WC()->cart = new WC_Cart();
    WC()->session = new WC_Session_Handler();
    WC()->session->init();
    #$hash_key = $_REQUEST['hash_key'];
    $data = $request->get_body();

    $data = json_decode($data);
    $event = $data->event;
    $data = $data->data;
    
    //$orderid = $data->orderId;
    $invoiceID = $data->id;
    $orderid = bitpay_checkout_get_order_id_bitpay_invoice_id($invoiceID);
    $order_status = $data->status;

    BPC_Logger($data, 'INCOMING IPN', true);

    $order = new WC_Order($orderid);
    if ($order->get_payment_method() != 'bitpay_checkout_gateway'){
        #ignore the IPN when the order payment method is (no longer) bitpay
        BPC_Logger("Order id = ".$orderid.", BitPay invoice id = ".$invoiceID.". Current payment method = " . $order->get_payment_method(), 'Ignore IPN', true);
        die();
    }
       

    #verify the ipn matches the status of the actual invoice

    if (bitpay_checkout_get_order_transaction($orderid, $invoiceID) == 1):
      
        $bitpay_checkout_options = get_option('woocommerce_bitpay_checkout_gateway_settings');
        //dev or prod token
        $bitpay_checkout_token = BPC_getBitPayToken($bitpay_checkout_options['bitpay_checkout_endpoint']);
        $bitpay_checkout_order_process_confirmed_status = $bitpay_checkout_options['bitpay_checkout_order_process_confirmed_status'];
        $bitpay_checkout_order_process_complete_status = $bitpay_checkout_options['bitpay_checkout_order_process_complete_status'];
        $bitpay_checkout_order_expired_status = $bitpay_checkout_options['bitpay_checkout_order_expired_status'];


        $config = new BPC_Configuration($bitpay_checkout_token, $bitpay_checkout_options['bitpay_checkout_endpoint']);
        $bitpay_checkout_endpoint = $bitpay_checkout_options['bitpay_checkout_endpoint'];

        $params = new stdClass();
        $params->extension_version = BPC_getBitPayVersionInfo();
        $params->invoiceID = $invoiceID;

        $item = new BPC_Item($config, $params);
        $invoice = new BPC_Invoice($item); //this creates the invoice with all of the config params
        $orderStatus = json_decode($invoice->BPC_checkInvoiceStatus($invoiceID,$bitpay_checkout_token));
        if($orderStatus->data->status != $order_status){
          die();
        }
        #update the lookup table
        $note_set = null;
             
        bitpay_checkout_update_order_note($orderid, $invoiceID, $order_status);
        $wc_statuses_arr = wc_get_order_statuses();
        $wc_statuses_arr['bitpay-ignore'] = "Do not change status";
        switch ($event->name) {
         
            case 'invoice_confirmed':
                if ( $bitpay_checkout_order_process_confirmed_status != 'bitpay-ignore' ):
                        $lbl = $wc_statuses_arr[ $bitpay_checkout_order_process_confirmed_status ];
                    if ( !isset( $lbl ) ):
                        $lbl = "Processing";
                        $bitpay_checkout_order_process_confirmed_status = 'wc-pending';
                    endif;
                    $order->add_order_note( 'BitPay Invoice ID: <a target = "_blank" href = "' . BPC_getBitPayDashboardLink( $bitpay_checkout_endpoint, $invoiceID ) . '">' . $invoiceID . '</a> has changed to ' . $lbl . '.' );
                    $order_status = $bitpay_checkout_order_process_confirmed_status;
                    if ( $order_status == 'wc-completed' ) {
                        $order->payment_complete( );
                        $order->add_order_note( 'Payment Completed' );
                        
                    } else {
                        $order->update_status( $order_status, __( 'BitPay payment ', 'woocommerce' ) );
                    }
                    WC()->cart->empty_cart();
                   
                else :
                    $order->add_order_note( 'BitPay Invoice ID: <a target = "_blank" href = "' . BPC_getBitPayDashboardLink( $bitpay_checkout_endpoint, $invoiceID ) . '">' . $invoiceID . '</a> has changed to Confirmed.  The order status has not been updated due to your settings.' );
                endif;
                http_response_code(200);
                break;

            case 'invoice_completed':
                if ( $bitpay_checkout_order_process_complete_status != 'bitpay-ignore' ):
                    $lbl = $wc_statuses_arr[ $bitpay_checkout_order_process_complete_status ];
                if ( !isset( $lbl ) ):
                    $lbl = "Processing";
                $bitpay_checkout_order_process_complete_status = 'wc-pending';
                endif;
                $order->add_order_note( 'BitPay Invoice ID: <a target = "_blank" href = "' . BPC_getBitPayDashboardLink( $bitpay_checkout_endpoint, $invoiceID ) . '">' . $invoiceID . '</a> has changed to ' . $lbl . '.' );
                $order_status = $bitpay_checkout_order_process_complete_status;
                if ( $order_status == 'wc-completed' ) {
                    $order->payment_complete( );
                    $order->add_order_note( 'Payment Completed' );
                    // Reduce stock levels
                    
                } else {
                $order->update_status( $order_status, __( 'BitPay payment ', 'woocommerce' ) );
                }
                
                // Remove cart
                WC()->cart->empty_cart();
                wc_reduce_stock_levels( $orderid );
                else :
                    $order->add_order_note( 'BitPay Invoice ID: <a target = "_blank" href = "' . BPC_getBitPayDashboardLink( $bitpay_checkout_endpoint, $invoiceID ) . '">' . $invoiceID . '</a> has changed to Completed.  The order status has not been updated due to your settings.' );
                endif;
                http_response_code(200);
                break;
            case 'invoice_failedToConfirm':
                if ($orderStatus->data->status == 'invalid'):
                    $order->add_order_note('BitPay Invoice ID: <a target = "_blank" href = "' . BPC_getBitPayDashboardLink($bitpay_checkout_endpoint, $invoiceID) . '">' . $invoiceID . '</a> has become invalid because of network congestion.  Order will automatically update when the status changes.');
                    $order->update_status('failed', __('BitPay payment invalid', 'woocommerce'));
                endif;
                http_response_code(200);
            break;
            case 'invoice_declined':
                if ($orderStatus->data->status == 'declined'):
                    $order->add_order_note('BitPay Invoice ID: <a target = "_blank" href = "' . BPC_getBitPayDashboardLink($bitpay_checkout_endpoint, $invoiceID) . '">' . $invoiceID . '</a> has been declined.');
                    $order->update_status('failed', __('BitPay payment invalid', 'woocommerce'));
                endif;
                http_response_code(200);
            break;

            case 'invoice_expired':
                if(property_exists($orderStatus->data,'underpaidAmount')):
                    $order->add_order_note('BitPay Invoice ID: <a target = "_blank" href = "' . BPC_getBitPayDashboardLink($bitpay_checkout_endpoint, $invoiceID) . '">' . $invoiceID . ' </a> has been refunded.');
                    $order->update_status('refunded', __('BitPay payment refunded', 'woocommerce'));
                else:
                    $order_status = "wc-cancelled";
                    $order->add_order_note('BitPay Invoice ID: <a target = "_blank" href = "' . BPC_getBitPayDashboardLink($bitpay_checkout_endpoint, $invoiceID) . '">' . $invoiceID . '</a> has expired.');
                    if($bitpay_checkout_order_expired_status == 1):
                         $order->update_status($order_status, __('BitPay payment invalid', 'woocommerce'));
                    endif;
                    http_response_code(200);                    
                endif;
                
               
               
                
            break;

            case 'invoice_refundComplete':             
                $order->add_order_note('BitPay Invoice ID: <a target = "_blank" href = "' . BPC_getBitPayDashboardLink($bitpay_checkout_endpoint, $invoiceID) . '">' . $invoiceID . ' </a> has been refunded.');
                $order->update_status('refunded', __('BitPay payment refunded', 'woocommerce'));
                break;
                http_response_code(200);
            default:
            break;
        }
        die();
    endif;
}

add_action('template_redirect', 'woo_custom_redirect_after_purchase');
function woo_custom_redirect_after_purchase()
{

    global $wp;
    $bitpay_checkout_options = get_option('woocommerce_bitpay_checkout_gateway_settings');
    $bitpay_checkout_token = BPC_getBitPayToken($bitpay_checkout_options['bitpay_checkout_endpoint']);

    if (is_checkout() && !empty($wp->query_vars['order-received'])) {

        $order_id = $wp->query_vars['order-received'];

        try {
            $order = new WC_Order($order_id);
           

            //this means if the user is using bitpay AND this is not the redirect
            $show_bitpay = true;

            if (isset($_GET['redirect']) && $_GET['redirect'] == 'false'):
                $show_bitpay = false;
                $invoiceID = $_COOKIE['bitpay-invoice-id'];

                //clear the cookie
                setcookie("bitpay-invoice-id", "", time() - 3600);
            endif;

            if ($order->get_payment_method() == 'bitpay_checkout_gateway' && $show_bitpay == true):
                $config = new BPC_Configuration($bitpay_checkout_token, $bitpay_checkout_options['bitpay_checkout_endpoint']);
                //sample values to create an item, should be passed as an object'
                $params = new stdClass();
                $current_user = wp_get_current_user();

                $params->extension_version = BPC_getBitPayVersionInfo();
                $params->price = $order->get_total();
                $params->currency = $order->get_currency(); //set as needed
                if ($bitpay_checkout_options['bitpay_checkout_capture_email'] == 1):
                    $current_user = wp_get_current_user();

                    if ($current_user->user_email):
                        $buyerInfo = new stdClass();
                        $buyerInfo->name = $current_user->display_name;
                        $buyerInfo->email = $current_user->user_email;
                        $params->buyer = $buyerInfo;
                    endif;
                endif;

                //orderid
                $params->orderId = $order->get_order_number($order_id);
               
                //redirect and ipn stuff
                $checkout_slug = $bitpay_checkout_options['bitpay_checkout_slug'];
                if (empty($checkout_slug)):
                    $checkout_slug = 'checkout';
                endif;

                if($bitpay_checkout_options['bitpay_custom_redirect'] == ""):
                $params->redirectURL = get_home_url() . '/' . $checkout_slug . '/order-received/' . $order_id . '/?key=' . $order->get_order_key() . '&redirect=false';
                else:
                $params->redirectURL = $bitpay_checkout_options['bitpay_custom_redirect']."?custompage=true";
                endif;
                #create a hash for the ipn
                $hash_key = $config->BPC_generateHash($params->orderId);
                $params->acceptanceWindow = 1200000;
                $params->notificationURL = get_home_url() . '/wp-json/bitpay/ipn/status';
                #http://<host>/wp-json/bitpay/ipn/status
                $params->extendedNotifications = true;

                $item = new BPC_Item($config, $params);
                $invoice = new BPC_Invoice($item);
                //this creates the invoice with all of the config params from the item
                $invoice->BPC_createInvoice();
                #BPC_Logger(json_decode($invoice->BPC_getInvoiceData()), 'NEW BITPAY INVOICE',true);

                $invoiceData = json_decode($invoice->BPC_getInvoiceData());				
                if (property_exists($invoiceData, 'error')):
                    $bitpay_checkout_options = get_option('woocommerce_bitpay_checkout_gateway_settings');

                    $errorSlug = $bitpay_checkout_options['bitpay_checkout_error'];
                    $errorURL = get_home_url() . '/' . $errorSlug;
                    if (empty($errorSlug)):
                        // If Error slug is left empty, redirect the customer his order checkout payment URL.
                        // This should be the default behaviour for better customer experience and better conversion. Noone wants to create his cart once again.
                        $errorURL = $order->get_checkout_payment_url( $on_checkout = false );
                    else :                    
                        $order_status = "wc-cancelled";
                        $order = new WC_Order($order_id);
                        $items = $order->get_items();
                        $order->update_status($order_status, __($invoiceData->error.'.', 'woocommerce'));

                        //clear the cart first so things dont double up
                        WC()->cart->empty_cart();
                        foreach ($items as $item) {
                            //now insert for each quantity
                            $item_count = $item->get_quantity();
                            for ($i = 0; $i < $item_count; $i++):
                                WC()->cart->add_to_cart($item->get_product_id());
                            endfor;
                        }
                    endif;
                    wp_redirect($errorURL);
                    die();
                endif;
              
                BPC_Logger($invoiceData, 'NEW BITPAY INVOICE', true);
                //now we have to append the invoice transaction id for the callback verification
                
              
                $invoiceID = $invoiceData->data->id;
                //set a cookie for redirects and updating the order status
                $cookie_name = "bitpay-invoice-id";
                $cookie_value = $invoiceID;
                setcookie($cookie_name, $cookie_value, time() + (86400 * 30), "/");

                $bitpay_checkout_options = get_option('woocommerce_bitpay_checkout_gateway_settings');
                $use_modal = intval($bitpay_checkout_options['bitpay_checkout_flow']);

                #insert into the database
                bitpay_checkout_insert_order_note($order_id, $invoiceID);
                

                //use the modal if '1', otherwise redirect
                if ($use_modal == 2):
                    wp_redirect($invoice->BPC_getInvoiceURL());
                else:
                    wp_redirect($params->redirectURL);

                endif;

                exit;
            endif;
        } catch (Exception $e) {
            global $woocommerce;
            $cart_url = $woocommerce->cart->get_cart_url();
            wp_redirect($cart_url);
            exit;
        }
    }
}
// Replacing the Place order 
add_filter('woocommerce_order_button_html', 'bitpay_checkout_replace_order_button_html', 10, 2);
function bitpay_checkout_replace_order_button_html($order_button, $override = false)
{
    if ($override):
        return;
    else:
        return $order_button;
    endif;
}

function BPC_getBitPayVersionInfo($clean = null)
{
    $plugin_data = get_file_data(__FILE__, array('Version' => 'Version', 'Plugin_Name' => 'Plugin Name'), false);
    $plugin_name = $plugin_data['Plugin_Name'];
    if ($clean):
        $plugin_version = $plugin_name . ' ' . $plugin_data['Version'];
    else:
        $plugin_name = str_replace(" ", "_", $plugin_name);
        $plugin_name = str_replace("_for_", "_", $plugin_name);
        $plugin_version = $plugin_name . '_' . $plugin_data['Version'];
    endif;
   
    return $plugin_version;
}

#retrieves the invoice token based on the endpoint
function BPC_getBitPayDashboardLink($endpoint, $invoiceID)
{ //dev or prod token
    switch ($endpoint) {
        case 'test':
        default:
            return '//test.bitpay.com/dashboard/payments/' . $invoiceID;
            break;
        case 'production':
            return '//bitpay.com/dashboard/payments/' . $invoiceID;
            break;
    }
}

#retrieves the invoice token based on the endpoint
function BPC_getProcessingLink()
{ //dev or prod token
    $bitpay_checkout_options = get_option('woocommerce_bitpay_checkout_gateway_settings');
    $bitpay_checkout_endpoint = $bitpay_checkout_options['bitpay_checkout_endpoint'];
    switch ($bitpay_checkout_endpoint) {
        case 'test':
        default:
            return 'https://test.bitpay.com/dashboard/settings/edit/order';
            break;
        case 'production':
        return 'https://www.bitpay.com/dashboard/settings/edit/order';
            break;
    }
}


function BPC_getBitPayLogo($endpoint = null)
{
    if (is_admin() && isset( $_GET['section']) &&  $_GET['section'] == 'bitpay_checkout_gateway'):
        $buttonObj = new BPC_Buttons;
        $buttons = $buttonObj->BPC_getButtons();
        $bitpay_checkout_options = get_option('woocommerce_bitpay_checkout_gateway_settings');
        $brand = $bitpay_checkout_options['bitpay_checkout_brand'];
        if ($brand == '-'):
            return null;
        elseif ($brand == ''):
            return $buttons[0];
        else:
            return $brand;
        endif;
    endif;

}

function BPC_getBitPayToken($endpoint)
{
    $bitpay_checkout_options = get_option('woocommerce_bitpay_checkout_gateway_settings');
    //dev or prod token
    switch ($bitpay_checkout_options['bitpay_checkout_endpoint']) {
        case 'test':
        default:
            return $bitpay_checkout_options['bitpay_checkout_token_dev'];
            break;
        case 'production':
            return $bitpay_checkout_options['bitpay_checkout_token_prod'];
            break;
    }

}

function BPC_isValidBitPayToken($bitpay_checkout_token, $bitpay_checkout_endpoint)
{
    $api_test = new BPC_Token($bitpay_checkout_endpoint, $bitpay_checkout_token);
    $api_response = json_decode($api_test->BPC_checkToken());

    if ($api_response->error == 'Object not found'):
        #valid token, no invoice
        return true;
    endif;
    BPC_Logger('Invalid token: ' . $bitpay_checkout_token . ' for ' . $bitpay_checkout_endpoint . ' environment', 'token', false, true);

    return false;
}

//hook into the order recieved page and re-add to cart of modal canceled
add_action('woocommerce_thankyou', 'bitpay_checkout_thankyou_page', 10, 1);
function bitpay_checkout_thankyou_page($order_id)
{
    global $woocommerce;
    $order = new WC_Order($order_id);

    $bitpay_checkout_options = get_option('woocommerce_bitpay_checkout_gateway_settings');
    $use_modal = intval($bitpay_checkout_options['bitpay_checkout_flow']);
    $bitpay_checkout_test_mode = $bitpay_checkout_options['bitpay_checkout_endpoint'];
    $restore_url = get_home_url() . '/wp-json/bitpay/cartfix/restore';
    $cart_url = wc_get_cart_url() . '/cart';
    $test_mode = false;
    $js_script = "https://bitpay.com/bitpay.min.js";
    if ($bitpay_checkout_test_mode == 'test'):
        $test_mode = true;
        $js_script = "https://test.bitpay.com/bitpay.min.js";
    endif;

    #use the modal
    if ($order->get_payment_method() == 'bitpay_checkout_gateway' && $use_modal == 1):
        $invoiceID = $_COOKIE['bitpay-invoice-id'];
        ?>
<script type="text/javascript" src="<?php echo $js_script;?>"></script>
<script type='text/javascript'>
    jQuery("#primary").hide()
    var payment_status = null;
    var is_paid = false
    window.addEventListener("message", function (event) {
        payment_status = event.data.status;

        if (payment_status == 'paid') {
            is_paid = true
        }
    }, false);
    //hide the order info
    bitpay.onModalWillEnter(function () {
        jQuery("primary").hide()
    });
    //show the order info
    bitpay.onModalWillLeave(function () {

        if (is_paid == true) {
            jQuery("#primary").fadeIn("slow");
        } else {
            var myKeyVals = {
                orderid: '<?php echo $order_id; ?>'
            }
            var redirect = '<?php echo $cart_url; ?>';
            var api = '<?php echo $restore_url; ?>';
            var saveData = jQuery.ajax({
                type: 'POST',
                url: api,
                data: myKeyVals,
                dataType: "text",
                success: function (resultData) {
                    window.location = redirect;
                }
            });
        }
    });
    //show the modal

    <?php
    if ($test_mode): ?>
        bitpay.enableTestMode(); 
    <?php endif; ?>
    bitpay.showInvoice('<?php echo $invoiceID; ?>');
</script>
<?php
endif;
}

#custom info for BitPay
add_action('woocommerce_thankyou', 'bitpay_checkout_custom_message');
function bitpay_checkout_custom_message($order_id)
{
    $order = new WC_Order($order_id);
    if ($order->get_payment_method() == 'bitpay_checkout_gateway'):
        $bitpay_checkout_options = get_option('woocommerce_bitpay_checkout_gateway_settings');
		$checkout_message = $bitpay_checkout_options['bitpay_checkout_checkout_message'];
		if ($order->get_status() == "pending") {    
			$params = new stdClass();
            if($bitpay_checkout_options['bitpay_close_url'] == "") {
                $checkout_slug = $bitpay_checkout_options['bitpay_checkout_slug'];
                if (empty($checkout_slug)):
                    $checkout_slug = 'checkout';
                endif;

                // If close URL is left empty, redirect the customer his order checkout payment URL.
                // This should be the default behaviour for better customer experience and better conversion. Noone wants to create his cart once again.
                $params->closeURL = $order->get_checkout_payment_url( $on_checkout = false );
            	wp_redirect($params->closeURL);	
			} else {
				wp_redirect($bitpay_checkout_options['bitpay_close_url']);
			}
        }
        if ($checkout_message != ''):
            echo '<hr><b>' . $checkout_message . '</b><br><br><hr>';
        endif;
    endif;
}

#bitpay image on payment page
function BPC_getBitPaymentIcon()
{

    $brand = "//bitpay.com/cdn/merchant-resources/pay-with-bitpay-card-group.svg";
    $icon = $brand . '" class="bitpay_logo"';
    return $icon;

    $bitpay_checkout_options = get_option('woocommerce_bitpay_checkout_gateway_settings');
    $bitpay_checkout_show_logo = $bitpay_checkout_options['bitpay_checkout_show_logo'];
    $icon = null;
    if($bitpay_checkout_show_logo  != 2):

    $brand = '//bitpay.com/cdn/merchant-resources/pay-with-bitpay-card-group.svg';
    $icon = $brand . '" class="bitpay_logo"';
    endif;
    return $icon;
   
}

#add the gatway to woocommerce
add_filter('woocommerce_payment_gateways', 'wc_bitpay_checkout_add_to_gateways');
function wc_bitpay_checkout_add_to_gateways($gateways)
{
    $gateways[] = 'WC_Gateway_BitPay';
    return $gateways;
}
