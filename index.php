<?php
/**
 * Plugin Name: BitPay Checkout Plugin
 * Plugin URI: http://www.bitpay.com
 * Description: Create Invoices and process through BitPay.  Configure in your <a href ="admin.php?page=wc-settings&tab=checkout&section=bitpay_gateway">WooCommerce->Payments plugin</a>.
 * Version: 1.0
 * Author: Joshua Lewis
 * Author URI: http://www.bitpay.com
 */

add_action('plugins_loaded', 'wc_bitpay_gateway_init', 11);
function wc_bitpay_gateway_init()
{
    class WC_Gateway_BitPay extends WC_Payment_Gateway
    {

        public function __construct()
        {
            $bitpay_options = get_option('woocommerce_bitpay_gateway_settings');
            #print_r($bitpay_options);

            $this->id = 'bitpay_gateway';
            $this->icon = getLogo($bitpay_options['bitpay_endpoint']);
            $this->has_fields = true;
            $this->method_title = __('BitPay', 'wc-bitpay');
            $this->method_label = __('ALO', 'wc-bitpay');
            $this->method_description = __('Expand your payment options by accepting instant BTC and BCH payments without risk or price fluctuations.', 'wc-bitpay');

            if (empty($_GET['woo-bitpay-return'])) {
                $this->order_button_text = __('Continue to payment', 'woocommerce-gateway-bitpay_gateway');
            }

            // Load the settings.
            $this->init_form_fields();
            $this->init_settings();

            // Define user set variables
            $this->title = $this->get_option('title');
            $this->description = $this->get_option('description');
            $this->instructions = $this->get_option('instructions', $this->description);

            // Actions
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
            add_action('woocommerce_thankyou_' . $this->id, array($this, 'thankyou_page'));

            // Customer Emails
            add_action('woocommerce_email_before_order_table', array($this, 'email_instructions'), 10, 3);
        }

        public function init_form_fields()
        {
            $this->form_fields = array(
                'enabled' => array(
                    'title' => __('Enable/Disable', 'woocommerce'),
                    'label' => __('Enable BitPay Checkout', 'woocommerce'),
                    'type' => 'checkbox',
                    'description' => '',
                    'default' => 'no',
                ),
                'bitpay_info' => array(
                    'description' => __('You should not ship any products until BitPay has finalized your transaction.<br>The order will stay in a <b>Hold</b> and/or <b>Processing</b> state, and will automatically change to <b>Completed</b> after the payment has been confirmed.', 'woocommerce'),
                    'type' => 'title',
                ),

                'bitpay_merchant_info' => array(
                    'description' => __('If you have not created a BitPay Merchant Token, you can create one at BitPay.com<br><a href = "https://test.bitpay.com/dashboard/merchant/api-tokens" target = "_blank">(Test)</a>  or <a href= "https://www.bitpay.com/dashboard/merchant/api-tokens" target = "_blank">(Production)</a> </p>', 'woocommerce'),
                    'type' => 'title',
                ),
                'title' => array(
                    'title' => __('Title', 'woocommerce'),
                    'type' => 'text',
                    'description' => __('This controls the title which the user sees during checkout.', 'woocommerce'),
                    'default' => __('Pay With BitPay', 'woocommerce'),

                ),
                'description' => array(
                    'title' => __('Description', 'woocommerce'),
                    'type' => 'text',
                    'description' => __('This is the message box that will appear on the <b>checkout page</b> when they select BitPay.', 'woocommerce'),
                    'default' => 'Checkout with BitPay',

                ),

                'bitpay_token_dev' => array(
                    'title' => __('BitPay Merchant Token (Dev)', 'woocommerce'),
                    'label' => __('BitPay Merchant Token (Dev)', 'woocommerce'),
                    'type' => 'text',
                    'description' => 'Your <b>development</b> merchant token.  <a href = "https://test.bitpay.com/dashboard/merchant/api-tokens" target = "_blank">Create one here</a> and <b>uncheck</b> require authentication',
                    'default' => '',

                ),
                'bitpay_token_prod' => array(
                    'title' => __('BitPay Merchant Token (Prod)', 'woocommerce'),
                    'label' => __('BitPay Merchant Token (Prod)', 'woocommerce'),
                    'type' => 'text',
                    'description' => 'Your <b>production</b> merchant token.  <a href = "https://www.bitpay.com/dashboard/merchant/api-tokens" target = "_blank">Create one here</a> and <b>uncheck</b> require authentication',
                    'default' => '',

                ),

                'bitpay_endpoint' => array(
                    'title' => __('BitPay Server Endpoint', 'woocommerce'),
                    'type' => 'select',
                    'description' => __('Select <b>Test</b> for testing the plugin, <b>Production</b> when you are ready to go live.'),
                    'options' => array(
                        'production' => 'Production',
                        'test' => 'Test',
                    ),
                    'default' => 'test',
                ),
                'bitpay_currency' => array(
                    'title' => __('Accepted Cryptocurrencies', 'woocommerce'),
                    'type' => 'select',
                    'description' => __('Bitcoin (BTC), Bitcoin Cash (BCH), or All.'),
                    'options' => array(
                        '1' => '--All--',
                        'BTC' => 'BTC',
                        'BCH' => 'BCH',
                    ),
                    'default' => '1',
                ),

                'bitpay_flow' => array(
                    'title' => __('Checkout Flow', 'woocommerce'),
                    'type' => 'select',
                    'description' => __('If this is set to <b>Redirect</b>, then the customer will be redirected to <b>BitPay</b> to checkout, and return to the checkout page once the payment is made.<br>If this is set to <b>Popup Modal</b>, the user will stay on <b>' . get_bloginfo('name', null) . '</b> and complete the transaction.', 'woocommerce'),
                    'options' => array(
                        '1' => 'Popup Modal',
                        '2' => 'Redirect',
                    ),
                    'default' => '2',
                ),
                'bitpay_checkout_message' => array(
                    'title' => __('Checkout Message', 'woocommerce'),
                    'type' => 'textarea',
                    'description' => __('Insert your custom message for the <b>Order Received</b> page, so the customer knows that the order will not be completed until BitPay releases the funds.', 'woocommerce'),
                    'default' => 'Thank you.  We will notify you when BitPay has processed your transaction.',
                ),
            );
        }

        function process_payment($order_id)
        {
            #this is the one that is called intially when someone checks out
            global $woocommerce;
            $order = new WC_Order($order_id);

            // Mark as on-hold (we're awaiting the cheque)
            $order->update_status('on-hold', __('Awaiting BitPay payment', 'woocommerce'));
            // Return thankyou redirect
            return array(
                'result' => 'success',
                'redirect' => $this->get_return_url($order),
            );
        }
    } // end \WC_Gateway_Offline class
}

//this is an error message incase a token isnt set
add_action('admin_notices', 'no_token_set');

function no_token_set()
{
    //lookup the token based on the environment
    $bitpay_options = get_option('woocommerce_bitpay_gateway_settings');
    //dev or prod token
    $bitpay_token = getToken($bitpay_options['bitpay_endpoint']);
    $bitpay_endpoint = $bitpay_options['bitpay_endpoint'];
    if (empty($bitpay_token)): ?>

   
    <div class="error notice">
        <p>
            <?php _e('There is no token set for your <b>' . strtoupper($bitpay_endpoint) . '</b> environment.  BitPay Checkout will not function if this is not set.');?>
        </p>
    </div>
<?php 
##check and see if the token is valid
else: 
    if($_POST && !empty($bitpay_token) && !empty($bitpay_endpoint)){
         if(!checkToken($bitpay_token,$bitpay_endpoint)):?>
        <div class="error notice">
        <p>
            <?php _e('The token for <b>'.strtoupper($bitpay_endpoint).'</b> is invalid.  Please verify your settings.');?>
        </p>
    </div>
         <?php endif;
    } 
   
?>    
<?php endif;
}


//http://bp.local.wpbase.com/wp-json/bitpay/ipn/status
add_action('rest_api_init', function () {
    register_rest_route('bitpay/ipn', '/status', array(
        'methods' => 'POST,GET',
        'callback' => 'bitpay_ipn',
    ));
    register_rest_route('bitpay/cartfix', '/restore', array(
        'methods' => 'POST,GET',
        'callback' => 'bitpay_cart_restore',
    ));
});

function bitpay_cart_restore(WP_REST_Request $request)
{
    global $woocommerce;
    $data = $request->get_params();
    $order_id = $data['orderid'];
    $order = new WC_Order($order_id);
    $items = $order->get_items();

    //clear the cart first so things dont double up

    $woocommerce->cart->empty_cart();
    foreach ($items as $item) {
        //now insert for each quantity
        $item_count = $item->get_quantity();
        for ($i = 0; $i < $item_count; $i++):
            WC()->cart->add_to_cart($item->get_product_id());
        endfor;
    }
    //delete the previous order
    wp_delete_post($order_id, true);
    setcookie("bitpay-invoice-id", "", time() - 3600);
}

//http://bp.local.wpbase.com/wp-json/bitpay/ipn/status
function bitpay_ipn(WP_REST_Request $request)
{
    $data = $request->get_body();
    $data = json_decode($data);

    $orderid = $data->orderId;
    $order_status = $data->status;

    if ($data->status == 'confirmed'):

        global $woocommerce;
        $order = new WC_Order($orderid);
        // Mark as on-hold (we're awaiting the cheque)
        $order->update_status('completed', __('BitPay payment completed', 'woocommerce'));

        // Reduce stock levels
        $order->reduce_order_stock();

        // Remove cart
        $woocommerce->cart->empty_cart();
    endif;
    die();
}

function updateOrderStatus($invoiceID, $orderid)
{
    global $woocommerce;
    $bitpay_options = get_option('woocommerce_bitpay_gateway_settings');
    //dev or prod token
    $bitpay_token = getToken($bitpay_options['bitpay_endpoint']);
    $config = new Configuration($bitpay_token, $bitpay_options['bitpay_endpoint']);
    $bitpay_endpoint = $bitpay_options['bitpay_endpoint'];

    $params = new stdClass();
    $params->invoiceID = $invoiceID;

    $item = new Item($config, $params);

    $invoice = new Invoice($item); //this creates the invoice with all of the config params
    $orderStatus = json_decode($invoice->checkInvoiceStatus($invoiceID));

    if ($orderStatus->data->status == 'paid'):
        $order = new WC_Order($orderid);
        //private order note with the invoice id
        $order->add_order_note('BitPay Invoice ID: <a target = "_blank" href = "'.getDashboardLink($bitpay_endpoint,$invoiceID).'">' . $invoiceID.'</a>');
        // Mark as on-hold (we're awaiting the cheque)
        $order->update_status('processing', __('BitPay payment processing', 'woocommerce'));
    endif;

}

add_action('template_redirect', 'woo_custom_redirect_after_purchase');
function woo_custom_redirect_after_purchase()
{
    global $wp;
    $bitpay_options = get_option('woocommerce_bitpay_gateway_settings');
    $bitpay_token = getToken($bitpay_options['bitpay_endpoint']);

    if (is_checkout() && !empty($wp->query_vars['order-received'])) {

        require 'classes/Config.php';
        require 'classes/Client.php';
        require 'classes/Item.php';
        require 'classes/Invoice.php';
        $order_id = $wp->query_vars['order-received'];
        $order = new WC_Order($order_id);
        $order->update_status('pending-payment', __('BitPay payment pending', 'woocommerce'));

        //this means if the user is using bitpay AND this is not the redirect
        $show_bitpay = true;

        if (isset($_GET['redirect']) && $_GET['redirect'] == 'false'):
            $show_bitpay = false;
            $invoiceID = $_COOKIE['bitpay-invoice-id'];

            //clear the cookie
            setcookie("bitpay-invoice-id", "", time() - 3600);
            updateOrderStatus($invoiceID, $order_id);
        endif;

        if ($order->payment_method == 'bitpay_gateway' && $show_bitpay == true):
            $config = new Configuration($bitpay_token, $bitpay_options['bitpay_endpoint']); 
            //sample values to create an item, should be passed as an object'
            $params = new stdClass();
            $params->price = $order->total;
            $params->currency = $order->currency; //set as needed
            //$params->buyers_email = 'jlewis@bitpay.com'; //set as needed

            //use other fields as needed from API Doc
            //which crytpo does the merchant accept? set it in the config
            $bitpay_currency = $bitpay_options['bitpay_currency'];
            switch ($bitpay_currency) {
                default:
                case 1:
                    break;
                case 'BTC':
                    $params->buyerSelectedTransactionCurrency = 'BTC';
                    break;
                case 'BCH':
                    $params->buyerSelectedTransactionCurrency = 'BCH';
                    break;
            }
            //orderid
            $params->orderId = trim($order_id);
            //redirect and ipn stuff
            $params->redirectURL = get_home_url() . '/checkout/order-received/' . $order_id . '/?key=' . $order->order_key . '&redirect=false';

            $params->notificationURL = get_home_url() . '/wp-json/bitpay/ipn/status';
            //http://bp.local.wpbase.com/wp-json/bitpay/ipn/status

            $item = new Item($config, $params);
            $invoice = new Invoice($item);

            //this creates the invoice with all of the config params from the item
            $invoice->createInvoice();
            $invoiceData = json_decode($invoice->getInvoiceData());
            //now we have to append the invoice transaction id for the callback verification
            $invoiceID = $invoiceData->data->id;
            //set a cookie for redirects and updating the order status
            $cookie_name = "bitpay-invoice-id";
            $cookie_value = $invoiceID;
            setcookie($cookie_name, $cookie_value, time() + (86400 * 30), "/");

            $bitpay_options = get_option('woocommerce_bitpay_gateway_settings');
            $use_modal = intval($bitpay_options['bitpay_flow']);

            //use the modal if '1', otherwise redirect
            if ($use_modal == 2):
                wp_redirect($invoice->getInvoiceURL());
            else:
                wp_redirect($params->redirectURL);

            endif;
            exit;
        endif;
    }
}

//retrieves the token based on the endpoint

function getDashboardLink($endpoint,$invoiceID)
{     //dev or prod token
    switch ($endpoint) {
        case 'test':
        default:
            return 'http://test.bitpay.com/dashboard/payments/'.$invoiceID;
            break;
        case 'production':
        return 'https://bitpay.com/dashboard/payments/'.$invoiceID;
        break;
    }
}

function getLogo($endpoint)
{     //dev or prod token
    switch ($endpoint) {
        case 'test':
        default:
            return '//test.bitpay.com/cdn/en_US/bp-btn-pay-currencies.svg';
            break;
        case 'production':
        return '//bitpay.com/cdn/en_US/bp-btn-pay-currencies.svg';
        break;
    }
}

function getToken($endpoint)
{
    $bitpay_options = get_option('woocommerce_bitpay_gateway_settings');
    //dev or prod token
    switch ($bitpay_options['bitpay_endpoint']) {
        case 'test':
        default:
            return $bitpay_options['bitpay_token_dev'];
            break;
        case 'production':
            return $bitpay_options['bitpay_token_prod'];
            break;
    }

}

function checkToken($bitpay_token,$bitpay_endpoint){
   
    require 'classes/Config.php';
    require 'classes/Client.php';
    require 'classes/Item.php';
    require 'classes/Invoice.php';
    
    #we're going to see if we can create an invoice
    $config = new Configuration($bitpay_token, $bitpay_endpoint); 
    //sample values to create an item, should be passed as an object'
    $params = new stdClass();
    $params->price = '.50';
    $params->currency = 'USD'; //set as needed

    $item = new Item($config, $params);
    $invoice = new Invoice($item);

    //this creates the invoice with all of the config params from the item
    $invoice->createInvoice();
    $invoiceData = json_decode($invoice->getInvoiceData());
    //now we have to append the invoice transaction id for the callback verification
    $invoiceID = $invoiceData->data->id;
    if(empty($invoiceID)):
       return false;
    else:
       return true;
    endif;   
}

//hook into the order recieved page and re-add to cart of modal canceled
add_action('woocommerce_thankyou', 'bitpay_thankyou', 10, 1);
function bitpay_thankyou($order_id)
{
   
    global $woocommerce;
    $order = new WC_Order($order_id);

    $bitpay_options = get_option('woocommerce_bitpay_gateway_settings');
    $use_modal = intval($bitpay_options['bitpay_flow']);
    $bitpay_test_mode = $bitpay_options['bitpay_endpoint'];
    $test_mode = false;
    $restore_url = get_home_url() . '/wp-json/bitpay/cartfix/restore';
    $cart_url = get_home_url() . '/cart';
    

    if ($bitpay_test_mode == 'test'):
        $test_mode = true;
    endif;

    //use the modal,
    if ($order->payment_method == 'bitpay_gateway' && $use_modal == 1):
        $invoiceID = $_COOKIE['bitpay-invoice-id'];
        ?>
	<script src="https://bitpay.com/bitpay.js"></script>
	<script type='text/javascript'>
	    jQuery("#primary").hide()
	    var payment_status = null;
	    window.addEventListener("message", function (event) {
	        payment_status = event.data.status;
	    }, false);
	    //hide the order info
	    bitpay.onModalWillEnter(function () {
	        jQuery("primary").hide()
	    });
	    //show the order info
	    bitpay.onModalWillLeave(function () {
	        if (payment_status == 'paid') {
	            jQuery("#primary").fadeIn("slow");
	        } else {
	            var myKeyVals = {
	                orderid: '<?php echo $order_id; ?>'
	            }
	            var redirect = '<?php echo $cart_url;?>';
	            var api = '<?php echo $restore_url;?>';
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
	    bitpay.enableTestMode(<?php echo $test_mode; ?>)
	    bitpay.showInvoice('<?php echo $invoiceID; ?>');
	</script>
	<?php
endif;
}

//custom info for bitpay checkout
add_action('woocommerce_thankyou', 'bitpay_custom_message');
function bitpay_custom_message()
{
    $bitpay_options = get_option('woocommerce_bitpay_gateway_settings');
    echo '<hr><b>' . $bitpay_options['bitpay_checkout_message'] . '</b>';
}

//add the gatway to woocommerce
add_filter('woocommerce_payment_gateways', 'wc_bitpay_add_to_gateways');
function wc_bitpay_add_to_gateways($gateways)
{
    $gateways[] = 'WC_Gateway_BitPay';
    return $gateways;
}