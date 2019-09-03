<?php
/**
 * Plugin Name: BitPay Checkout for WooCommerce
 * Plugin URI: https://www.bitpay.com
 * Description: Create Invoices and process through BitPay.  Configure in your <a href ="admin.php?page=wc-settings&tab=checkout&section=bitpay_checkout_gateway">WooCommerce->Payments plugin</a>.
 * Version: 3.0.5.18
 * Author: BitPay
 * Author URI: mailto:integrations@bitpay.com?subject=BitPay Checkout for WooCommerce
 */
if (!defined('ABSPATH')): exit;endif;
global $current_user;
#add_filter('wp_enqueue_scripts', 'enable_bitpaycheckout_js',0);
#add_action( 'init', 'enable_bitpaycheckout_js', 0 );
function enable_bitpaycheckout_js()
{
    wp_enqueue_script('remote-bitpaycheckout-js', '//bitpay.com/bitpay.min.js', null, null, true);
}

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
        `order_id` int(11) NOT NULL,
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
        BPC_Logger('Missing values' . PHP_EOL . 'order id: ' . $order_id . PHP_EOL . 'transaction id: ' . $transaction_id . PHP_EOL . 'transaction status: ' . $transaction . PHP_EOL, 'error', false, true);
    endif;
}

function bitpay_checkout_get_order_transaction($order_id, $transaction_id)
{
    global $wpdb;
    $table_name = '_bitpay_checkout_transactions';
    $rowcount = $wpdb->get_var("SELECT COUNT(order_id) FROM $table_name WHERE order_id = '$order_id'
    AND transaction_id = '$transaction_id' LIMIT 1");
    return $rowcount;

}

function bitpay_checkout_delete_order_transaction($order_id)
{
    global $wpdb;
    $table_name = '_bitpay_checkout_transactions';
    $wpdb->query("DELETE FROM $table_name WHERE order_id = '$order_id'");

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
                $this->method_description = __('Expand your payment options by accepting instant BTC and BCH payments without risk or price fluctuations.', 'wc-bitpay');

                if (empty($_GET['woo-bitpay-return'])) {
                    $this->order_button_text = __('Pay with BitPay', 'woocommerce-gateway-bitpay_checkout_gateway');

                }
                // Load the settings.
                $this->init_form_fields();
                $this->init_settings();

                // Define user set variables
                $this->title = $this->get_option('title');
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
                    'title' => array(
                        'title' => __('Title', 'woocommerce'),
                        'type' => 'text',
                        'description' => __('This controls the title which the user sees during checkout.', 'woocommerce'),
                        'default' => __('BitPay', 'woocommerce'),

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

                    'bitpay_checkout_brand' => array(
                        'title' => __('Branding', 'woocommerce'),
                        'type' => 'select',
                        'description' => __('Choose from one of our branded buttons<br>' . BPC_getBitPayBrands(), 'woocommerce'),
                        'options' => BPC_getBitPayBrandOptions(),
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

                    'bitpay_checkout_order_paid' => array(
                        'title' => __('Paid Status', 'woocommerce'),
                        'type' => 'select',
                        'description' => __('Customize the order status when a user intially <b>Pays</b> the invoice.  This step is not yet confirmed on the blockchain, but has received a crytpocurrency payment.  Defaults to <b>Processing</b>.', 'woocommerce'),
                        'options' => $this->BPC_getOrderStatus(),
                        'default' => 'wc-processing',
                    ),
                    'bitpay_checkout_order_confirmed' => array(
                        'title' => __('Confirmed Status', 'woocommerce'),
                        'type' => 'select',
                        'description' => __('The payment has been <b></b>confirmed</b> based on transaction speeds.  Defaults to <b>Completed</b>.', 'woocommerce'),
                        'options' => $this->BPC_getOrderStatus(),
                        'default' => 'wc-completed',
                    ),
                    'bitpay_checkout_auto_delete' => array(
                        'title' => __('Auto-Delete Expired Invoices/Orders', 'woocommerce'),
                        'type' => 'select',
                        'description' => __('If the invoice has <b>expired</b>, ie a user started the checkout but never completed payment, should the order be automatically removed during the IPN notification?  If set to <b>Yes</b> then the order will be completely removed.  If set to <b>No</b> then the status will be set to <b>Cancelled</b>.  Defaults to <b>Yes</b>.', 'woocommerce'),
                        'options' => array(
                            '1' => 'Yes',
                            '0' => 'No',

                        ),
                        'default' => '1',
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
            function BPC_getOrderStatus()
            {
                $order_statuses = wc_get_order_statuses();
                return apply_filters('wc_order_statuses', $order_statuses);

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

//this is an error message incase a token isnt set
    add_action('admin_notices', 'bitpay_checkout_check_token');
    function bitpay_checkout_check_token()
{

        if (isset($_GET['section'])):
            if ($_GET['section'] == 'bitpay_checkout_gateway' && $_POST && is_admin()):
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

#http://<host>/wp-json/bitpay/ipn/status
add_action('rest_api_init', function () {
    register_rest_route('bitpay/ipn', '/status', array(
        'methods' => 'POST,GET',
        'callback' => 'bitpay_checkout_ipn',
    ));
    register_rest_route('bitpay/cartfix', '/restore', array(
        'methods' => 'POST,GET',
        'callback' => 'bitpay_checkout_cart_restore',
    ));
});

function bitpay_checkout_cart_restore(WP_REST_Request $request)
{
    WC()->frontend_includes();
    WC()->cart = new WC_Cart();
    WC()->session = new WC_Session_Handler();
    WC()->session->init();
    $data = $request->get_params();
    $order_id = $data['orderid'];
    $order = new WC_Order($order_id);
    $items = $order->get_items();

    BPC_Logger('User canceled order: ' . $order_id . ', removing from WooCommerce', 'USER CANCELED ORDER', true);

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
    wp_delete_post($order_id, true);
    bitpay_checkout_delete_order_transaction($order_id);
    setcookie("bitpay-invoice-id", "", time() - 3600);
}

function bitpay_checkout_ipn_status($status)
{
    global $woocommerce;
    $bitpay_checkout_options = get_option('woocommerce_bitpay_checkout_gateway_settings');
    switch ($status) {
        case 'invoice_confirmed':
            if ($bitpay_checkout_options['bitpay_checkout_order_confirmed'] == ''):
                return 'completed';
            else:
                return str_replace('wc-', '', $bitpay_checkout_options['bitpay_checkout_order_confirmed']);
            endif;
            break;
        case 'invoice_paidInFull':
            if ($bitpay_checkout_options['bitpay_checkout_order_paid'] == ''):
                return 'processing';
            else:
                return str_replace('wc-', '', $bitpay_checkout_options['bitpay_checkout_order_paid']);
            endif;
            break;

    }
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

    $orderid = $data->orderId;
    $order_status = $data->status;
    $invoiceID = $data->id;
    BPC_Logger($data, 'INCOMING IPN', true);

    #check the hash to make sure it comes from the right place

    #verify the ipn matches the status of the actual invoice

    if (bitpay_checkout_get_order_transaction($orderid, $invoiceID)):
        $bitpay_checkout_options = get_option('woocommerce_bitpay_checkout_gateway_settings');
        //dev or prod token
        $bitpay_checkout_token = BPC_getBitPayToken($bitpay_checkout_options['bitpay_checkout_endpoint']);
        $config = new BPC_Configuration($bitpay_checkout_token, $bitpay_checkout_options['bitpay_checkout_endpoint']);
        $bitpay_checkout_endpoint = $bitpay_checkout_options['bitpay_checkout_endpoint'];

        #verify the hash before moving on
        #disable this for awhile so new orders can start creating them
        #if(!$config->BPC_checkHash($orderid,$hash_key)):
        #    die();
        #endif;

        $params = new stdClass();
        $params->extension_version = BPC_getBitPayVersionInfo();
        $params->invoiceID = $invoiceID;

        $item = new BPC_Item($config, $params);

        $invoice = new BPC_Invoice($item); //this creates the invoice with all of the config params
        $orderStatus = json_decode($invoice->BPC_checkInvoiceStatus($invoiceID));
        #update the lookup table
        bitpay_checkout_update_order_note($orderid, $invoiceID, $order_status);
        switch ($event->name) {
            case 'invoice_confirmed':
                #if ($orderStatus->data->status == 'confirmed'):
                $order = new WC_Order($orderid);
                //private order note with the invoice id
                $order->add_order_note('BitPay Invoice ID: <a target = "_blank" href = "' . BPC_getBitPayDashboardLink($bitpay_checkout_endpoint, $invoiceID) . '">' . $invoiceID . '</a> processing has been completed.');

                $order_status = bitpay_checkout_ipn_status('invoice_confirmed');
                $order->update_status($order_status, __('BitPay payment ' . $order_status, 'woocommerce'));
                // Reduce stock levels
                $order->reduce_order_stock();

                // Remove cart
                WC()->cart->empty_cart();
                #endif;
                break;

            case 'invoice_paidInFull': #pending
                # if ($orderStatus->data->status == 'paid'):
                $order = new WC_Order($orderid);
                //private order note with the invoice id
                $order->add_order_note('BitPay Invoice ID: <a target = "_blank" href = "' . BPC_getBitPayDashboardLink($bitpay_checkout_endpoint, $invoiceID) . '">' . $invoiceID . '</a> is processing.');

                $order_status = bitpay_checkout_ipn_status('invoice_paidInFull');
                $order->update_status($order_status, __('BitPay payment ' . $order_status, 'woocommerce'));
                # endif;
                break;

            case 'invoice_failedToConfirm':
                if ($orderStatus->data->status == 'invalid'):
                    $order = new WC_Order($orderid);
                    //private order note with the invoice id
                    $order->add_order_note('BitPay Invoice ID: <a target = "_blank" href = "' . BPC_getBitPayDashboardLink($bitpay_checkout_endpoint, $invoiceID) . '">' . $invoiceID . '</a> has become invalid because of network congestion.  Order will automatically update when the status changes.');

                    $order->update_status('failed', __('BitPay payment invalid', 'woocommerce'));
                endif;
                break;
            case 'invoice_expired':
                #if ($orderStatus->data->status == 'expired'):
                //delete the previous order
                $order = new WC_Order($orderid);
                if ($bitpay_checkout_options['bitpay_checkout_auto_delete'] == 1):
                    wp_delete_post($orderid, true);
                else:
                    $order->update_status('cancelled', __('BitPay cancelled the order.', 'woocommerce'));
                endif;
                #endif;
                break;

            case 'invoice_refundComplete':
                $order = new WC_Order($orderid);
                //private order note with the invoice id
                $order->add_order_note('BitPay Invoice ID: <a target = "_blank" href = "' . BPC_getBitPayDashboardLink($bitpay_checkout_endpoint, $invoiceID) . '">' . $invoiceID . ' </a> has been refunded.');

                $order->update_status('refunded', __('BitPay payment refunded', 'woocommerce'));
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

            if ($order->payment_method == 'bitpay_checkout_gateway' && $show_bitpay == true):
                $config = new BPC_Configuration($bitpay_checkout_token, $bitpay_checkout_options['bitpay_checkout_endpoint']);
                //sample values to create an item, should be passed as an object'
                $params = new stdClass();
                $current_user = wp_get_current_user();
                #$params->fullNotifications = 'true';
                $params->extension_version = BPC_getBitPayVersionInfo();
                $params->price = $order->total;
                $params->currency = $order->currency; //set as needed
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
                $params->orderId = trim($order_id);
                //redirect and ipn stuff
                $checkout_slug = $bitpay_checkout_options['bitpay_checkout_slug'];
                if (empty($checkout_slug)):
                    $checkout_slug = 'checkout';
                endif;
                $params->redirectURL = get_home_url() . '/' . $checkout_slug . '/order-received/' . $order_id . '/?key=' . $order->order_key . '&redirect=false';

                #create a hash for the ipn
                $hash_key = $config->BPC_generateHash($params->orderId);

                $params->notificationURL = get_home_url() . '/wp-json/bitpay/ipn/status';
                #http://<host>/wp-json/bitpay/ipn/status
                $params->extendedNotifications = true;

                $item = new BPC_Item($config, $params);
                $invoice = new BPC_Invoice($item);
                //this creates the invoice with all of the config params from the item
                $invoice->BPC_createInvoice();
                #BPC_Logger(json_decode($invoice->BPC_getInvoiceData()), 'NEW BITPAY INVOICE',true);

                $invoiceData = json_decode($invoice->BPC_getInvoiceData());
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
// Replacing the Place order button when total volume exceed 68 m3
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

function BPC_getBitPayBrandOptions()
{
    if (isset($_GET['section'])):
        if (is_admin() && $_GET['section'] == 'bitpay_checkout_gateway'):

            $buttonObj = new BPC_Buttons;
            $buttons = json_decode($buttonObj->BPC_getButtons());
            $output = [];
            foreach ($buttons->data as $key => $b):

                $names = preg_split('/(?=[A-Z])/', $b->name);
                $names = implode(" ", $names);
                $names = ucwords($names);
                if (strpos($names, "Donate") === 0):
                    continue;
                else:
                    $names = str_replace(" Button", "", $names);
                    $output['//' . $b->url] = $names;
                endif;
            endforeach;
            #add a 'no image' option
            $output['-'] = 'No Image';

            return $output;
        endif;
    endif;
}

#brand returned from API
function BPC_getBitPayBrands()
{
    if (isset($_GET['section'])):
        if (is_admin() && $_GET['section'] == 'bitpay_checkout_gateway'):
            $buttonObj = new BPC_Buttons;
            $buttons = json_decode($buttonObj->BPC_getButtons());
            $brand = '<div>';
            foreach ($buttons->data as $key => $b):
                $names = preg_split('/(?=[A-Z])/', $b->name);
                $names = implode(" ", $names);
                $names = ucwords($names);

                if (strpos($names, "Donate") === 0):
                    continue;
                else:
                    $names = str_replace(" Button", "", $names);
                    $brand .= '<figure style = "float:left;"><img src = "//' . $b->url . '"  style = "width:150px;padding:1px;">';
                    $brand .= '<figcaption style = "text-align:left;font-style:italic"><b>' . $names . '</b><br>' . $b->description . '</figcaption>';
                    $brand .= '</figure>';
                endif;
            endforeach;

            $brand .= '</div>';
            return $brand;
        endif;
    endif;
}

function BPC_getBitPayLogo($endpoint = null)
{
    if (is_admin() && $_GET['section'] == 'bitpay_checkout_gateway'):
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
    $cart_url = get_home_url() . '/cart';
    $test_mode = false;
    if ($bitpay_checkout_test_mode == 'test'):
        $test_mode = true;
    endif;

    #use the modal
    if ($order->payment_method == 'bitpay_checkout_gateway' && $use_modal == 1):
        $invoiceID = $_COOKIE['bitpay-invoice-id'];
        ?>
										        <script type = "text/javascript" src = "//bitpay.com/bitpay.min.js"></script>
												<script type='text/javascript'>
												jQuery("#primary").hide()
												var payment_status = null;
										        var is_paid = false
												window.addEventListener("message", function(event) {
												    payment_status = event.data.status;
				                                    console.log('payment_status',event.data)

										            if(payment_status == 'paid'){
										                is_paid = true
										            }
												}, false);
												//hide the order info
												bitpay.onModalWillEnter(function() {
												    jQuery("primary").hide()
												});
												//show the order info
												bitpay.onModalWillLeave(function() {

												    if (is_paid == true) {
												        jQuery("#primary").fadeIn("slow");
												    } else {
												        var myKeyVals = {
												            orderid: '<?php echo $order_id; ?>'
												        }
										                console.log('payment_status leave 2',payment_status)
												        var redirect = '<?php echo $cart_url; ?>';
												        var api = '<?php echo $restore_url; ?>';
												        var saveData = jQuery.ajax({
												            type: 'POST',
												            url: api,
												            data: myKeyVals,
												            dataType: "text",
												            success: function(resultData) {
												                window.location = redirect;
												            }
												        });
												    }
												});
												//show the modal
										        <?php if ($test_mode): ?>
												bitpay.enableTestMode()
										        <?php endif;?>
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
    if ($order->payment_method == 'bitpay_checkout_gateway'):
        $bitpay_checkout_options = get_option('woocommerce_bitpay_checkout_gateway_settings');
        $checkout_message = $bitpay_checkout_options['bitpay_checkout_checkout_message'];
        if ($checkout_message != ''):
            echo '<hr><b>' . $checkout_message . '</b><br><br><hr>';
        endif;
    endif;
}

#bitpay image on payment page
function BPC_getBitPaymentIcon()
{
    $bitpay_checkout_options = get_option('woocommerce_bitpay_checkout_gateway_settings');
    $brand = $bitpay_checkout_options['bitpay_checkout_brand'];
    if ($brand != '-'):
        $icon = $brand . '" class="bitpay_logo"';
        return $icon;
    endif;
}

#add the gatway to woocommerce
add_filter('woocommerce_payment_gateways', 'wc_bitpay_checkout_add_to_gateways');
function wc_bitpay_checkout_add_to_gateways($gateways)
{
    $gateways[] = 'WC_Gateway_BitPay';
    return $gateways;
}
