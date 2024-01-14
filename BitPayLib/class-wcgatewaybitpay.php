<?php

declare(strict_types=1);

namespace BitPayLib;

/**
 * Plugin Name: BitPay Checkout for WooCommerce
 * Plugin URI: https://www.bitpay.com
 * Description: BitPay Checkout Plugin
 * Version: 5.3.2
 * Author: BitPay
 * Author URI: mailto:integrations@bitpay.com?subject=BitPay Checkout for WooCommerce
 */
class WcGatewayBitpay extends \WC_Payment_Gateway {

	public const IGNORE_STATUS_VALUE = 'bitpay-ignore';
	private string $instructions;

	public function __construct() {
		$this->id   = 'bitpay_checkout_gateway';
		$this->icon = $this->get_icon_on_payment_page();

		$this->has_fields         = true;
		$this->method_title       = __( $this->get_bitpay_version_info(), 'wc-bitpay' ); // phpcs:ignore
		$this->method_label       = __( 'BitPay', 'wc-bitpay' );
		$this->method_description = __( 'Expand your payment options by accepting cryptocurrency payments (BTC, BCH, ETH, and Stable Coins) without risk or price fluctuations.', 'wc-bitpay' );

		if ( empty( $_GET['woo-bitpay-return'] ) ) { // phpcs:ignore
			$this->order_button_text = __( 'Pay with BitPay', 'woocommerce-gateway-bitpay_checkout_gateway' );
		}

		$this->init_form_fields();
		$this->init_settings();

		$this->title        = 'BitPay';
		$this->description  = $this->get_option( 'description' ) . '<br>';
		$this->instructions = $this->get_option( 'instructions', $this->description );

		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'woocommerce_email_before_order_table', array( $this, 'email_instructions' ), 10, 3 );
		wp_enqueue_script( 'bitpay_wc_gateway', plugins_url( '../../js/wc_gateway_bitpay.js', __FILE__ ), null, 1, false );
	}
	public function email_instructions( $order, $sent_to_admin, $plain_text = false ) {
		if ( $this->instructions && ! $sent_to_admin && 'bitpay_checkout_gateway' === $order->get_payment_method() && $order->has_status( 'processing' ) ) {
			echo wp_kses_post( wpautop( wptexturize( $this->instructions ) ) . PHP_EOL );
		}
	}
	public function init_form_fields() {
		$settings        = new BitPayPaymentSettings();
		$wc_statuses_arr = wc_get_order_statuses();
		unset( $wc_statuses_arr['wc-cancelled'] );
		unset( $wc_statuses_arr['wc-refunded'] );
		unset( $wc_statuses_arr['wc-failed'] );

		$wc_statuses_arr[ self::IGNORE_STATUS_VALUE ] = 'Do not change status'; // add an ignore option.

		$this->form_fields = array(
			'enabled'                                   => array(
				'title'       => __( 'Enable/Disable', 'woocommerce' ),
				'label'       => __( 'Enable BitPay', 'woocommerce' ),
				'type'        => 'checkbox',
				'description' => '',
				'default'     => 'no',
			),
			'bitpay_logo'                               => array(
				'title'       => __( 'BitPay Logo', 'woocommerce' ),
				'type'        => 'select',
				'description' => '',
				'options'     => array(
					'BitPay-Accepted-CardGroup'          => 'BitPay Accepted',
					'BitPay-Accepted-CardGroup-DarkMode' => 'BitPay Accepted (Dark mode)',
					'Pay-with-BitPay-CardGroup'          => 'Pay with BitPay',
					'Pay-with-BitPay-CardGroup-DarkMode' => 'Pay with BitPay (Dark mode)',
					'BitPay-Accepted-Card-Alt'           => 'BitPay Accepted Card - Alt',
					'BitPay-Accepted-Card-Alt-DarkMode'  => 'BitPay Accepted Card - Alt (Dark mode)',
					'BitPay-Accepted-Card'               => 'BitPay Accepted Card',
					'BitPay-Accepted-Card-DarkMode'      => 'BitPay Accepted Card (Dark mode)',
					'BitPay-Accepted-Card-GrayScale'     => 'BitPay Accepted Card - Grayscale',
					'PayWith-BitPay-Card2x'              => 'Pay with BitPay Card',
					'PayWith-BitPay-Card-Alt'            => 'Pay with BitPay Card - Alt',
					'PayWith-BitPay-Card-GrayScale'      => 'Pay with BitPay Card - Grayscale',
					'PayWith-BitPay-Card-DarkMode'       => 'Pay with BitPay Card (Dark mode)',
				),
				'default'     => 'BitPay-Accepted-CardGroup',
			),
			'bitpay_logo_image_white'                   => array(
				'id'          => 'bitpay_logo',
				'description' => '<img src="' . $settings->get_payment_logo_url()
					. '" style="background-color: white;"/>',
				'type'        => 'title',
			),
			'bitpay_logo_image_dark'                    => array(
				'id'          => 'bitpay_logo',
				'description' => '<img src="' . $settings->get_payment_logo_url()
					. '" style="background-color: black;"/>',
				'type'        => 'title',
			),
			'bitpay_checkout_info'                      => array(
				'description' => __( 'You should not ship any products until BitPay has finalized your transaction.<br>The order will stay in a <b>Hold</b> and/or <b>Processing</b> state, and will automatically change to <b>Completed</b> after the payment has been confirmed.', 'woocommerce' ),
				'type'        => 'title',
			),

			'bitpay_checkout_merchant_info'             => array(
				'description' => __( 'If you have not created a BitPay Merchant Token, you can create one on your BitPay Dashboard.<br><a href = "https://test.bitpay.com/dashboard/merchant/api-tokens" target = "_blank">(Test)</a>  or <a href= "https://www.bitpay.com/dashboard/merchant/api-tokens" target = "_blank">(Production)</a> </p>', 'woocommerce' ),
				'type'        => 'title',
			),

			'bitpay_checkout_tier_info'                 => array(
				'description' => __( '<em><b>*** </b>If you are having trouble creating BitPay invoices, verify your Tier settings on your <a href = "https://support.bitpay.com/hc/en-us/articles/206003676-How-do-I-raise-my-approved-processing-volume-tier-limit-" target = "_blank">BitPay Dashboard</a>.</em>', 'woocommerce' ),
				'type'        => 'title',
			),

			'description'                               => array(
				'title'       => __( 'Description', 'woocommerce' ),
				'type'        => 'text',
				'description' => __( 'This is the message box that will appear on the <b>checkout page</b> when they select BitPay.', 'woocommerce' ),
				'default'     => 'Pay with BitPay using one of the supported cryptocurrencies',

			),

			'bitpay_checkout_token_dev'                 => array(
				'title'       => __( 'Development Token', 'woocommerce' ),
				'label'       => __( 'Development Token', 'woocommerce' ),
				'type'        => 'text',
				'description' => 'Your <b>development</b> merchant token.  <a href = "https://test.bitpay.com/dashboard/merchant/api-tokens" target = "_blank">Create one here</a> and <b>uncheck</b> `Require Authentication`.',
				'default'     => '',

			),
			'bitpay_checkout_token_prod'                => array(
				'title'       => __( 'Production Token', 'woocommerce' ),
				'label'       => __( 'Production Token', 'woocommerce' ),
				'type'        => 'text',
				'description' => 'Your <b>production</b> merchant token.  <a href = "https://www.bitpay.com/dashboard/merchant/api-tokens" target = "_blank">Create one here</a> and <b>uncheck</b> `Require Authentication`.',
				'default'     => '',

			),

			'bitpay_checkout_endpoint'                  => array(
				'title'       => __( 'Endpoint', 'woocommerce' ),
				'type'        => 'select',
				'description' => __( 'Select <b>Test</b> for testing the plugin, <b>Production</b> when you are ready to go live.' ),
				'options'     => array(
					'production' => 'Production',
					'test'       => 'Test',
				),
				'default'     => 'test',
			),

			'bitpay_checkout_flow'                      => array(
				'title'       => __( 'Checkout Flow', 'woocommerce' ),
				'type'        => 'select',
				'description' => __( 'If this is set to <b>Redirect</b>, then the customer will be redirected to <b>BitPay</b> to checkout, and return to the checkout page once the payment is made.<br>If this is set to <b>Modal</b>, the user will stay on <b>' . get_bloginfo( 'name', null ) . '</b> and complete the transaction.', 'woocommerce' ), // phpcs:ignore
				'options'     => array(
					'1' => 'Modal',
					'2' => 'Redirect',
				),
				'default'     => '2',
			),
			'bitpay_checkout_slug'                      => array(
				'title'       => __( 'Checkout Page', 'woocommerce' ),
				'type'        => 'text',
				'description' => __( 'If you have a different custom checkout page, enter the <b>page slug</b>. <br>ie. ' . get_home_url() . '/<b>checkout</b><br><br>View your pages <a target = "_blank" href  = "/wp-admin/edit.php?post_type=page">here</a>, your current checkout page should have <b>Checkout Page</b> next to the title.<br><br>Click the "quick edit" and copy and paste a custom slug here if needed.', 'woocommerce' ), // phpcs:ignore
			),
			'bitpay_custom_redirect'                    => array(
				'title'       => __( 'Custom Redirect Page', 'woocommerce' ),
				'type'        => 'text',
				'description' => __( 'Set the full url  (ie. <i>https://yoursite.com/custompage</i>) if you would like the customer to be redirected to a custom page after completing theh purchase.  <b>Note: this will only work if the REDIRECT mode is used</b> ', 'woocommerce' ),
			),
			'bitpay_close_url'                          => array(
				'title'       => __( 'Close URL', 'woocommerce' ),
				'type'        => 'text',
				'description' => __( 'Set the close url <br /><b>Note: this will only work if the REDIRECT mode is used</b> ', 'woocommerce' ),
			),
			'bitpay_checkout_mini'                      => array(
				'title'       => __( 'Show in mini cart ', 'woocommerce' ),
				'type'        => 'select',
				'description' => __( 'Set to YES if you would like to show BitPay as an immediate checkout option in the mini cart', 'woocommerce' ),
				'options'     => array(
					'1' => 'Yes',
					'2' => 'No',
				),
				'default'     => '2',
			),

			'bitpay_checkout_capture_email'             => array(
				'title'       => __( 'Auto-Capture Email', 'woocommerce' ),
				'type'        => 'select',
				'description' => __( 'Should BitPay try to auto-add the client\'s email address?  If <b>Yes</b>, the client will not be able to change the email address on the BitPay invoice.  If <b>No</b>, they will be able to add their own email address when paying the invoice.', 'woocommerce' ),
				'options'     => array(
					'1' => 'Yes',
					'0' => 'No',

				),
				'default'     => '1',
			),
			'bitpay_checkout_checkout_message'          => array(
				'title'       => __( 'Checkout Message', 'woocommerce' ),
				'type'        => 'textarea',
				'description' => __( 'Insert your custom message for the <b>Order Received</b> page, so the customer knows that the order will not be completed until BitPay releases the funds.', 'woocommerce' ),
				'default'     => 'Thank you.  We will notify you when BitPay has processed your transaction.',
			),
			'bitpay_checkout_error'                     => array(
				'title'       => __( 'Error handling', 'woocommerce' ),
				'type'        => 'text',
				'description' => __( 'If there is an error with creating the invoice, enter the <b>page slug</b>. <br>ie. ' . get_home_url() . '/<b>error</b><br><br>View your pages <a target = "_blank" href  = "/wp-admin/edit.php?post_type=page">here</a>,.<br><br>Click the "quick edit" and copy and paste a custom slug here.', 'woocommerce' ), // phpcs:ignore

			),
			'bitpay_checkout_error_message'             => array(
				'title'       => __( 'Error Message', 'woocommerce' ),
				'type'        => 'textarea',
				'description' => __( 'Insert your custom message for the <b>Error</b> page, so the customer knows that there is some issue in paying the invoice', 'woocommerce' ),
				'default'     => 'Transaction Cancelled',
			),
			'bitpay_checkout_order_process_paid_status' => array(
				'title'       => __( 'BitPay Paid Invoice Status', 'woocommerce' ),
				'type'        => 'select',
				'description' => __(
					'Map the BitPay <b>paid</b> invoice status to one of the available WooCommerce order states.<br>All WooCommerce status options are listed here for your convenience.<br><br><br><em>Click <a href = "https://bitpay.com/docs/invoice-states" target = "_blank">here</a> for more information about BitPay invoice statuses.</em>', // phpcs:ignore
					'woocommerce',
				),
				'options'     => $wc_statuses_arr,
				'default'     => 'wc-processing',
			),
			'bitpay_checkout_order_process_confirmed_status' => array(
				'title'       => __( 'BitPay Confirmed Invoice Status', 'woocommerce' ),
				'type'        => 'select',
				'description' => __(
					'Configure your Transaction Speeds on your <a href = "' . $this->get_processing_link() . '" target = "_blank">BitPay Dashboard</a>, and map the BitPay <b>confirmed</b> invoice status to one of the available WooCommerce order states.<br>All WooCommerce status options are listed here for your convenience.<br><br><em>Note: setting the status to <b>Completed</b> will reduce stock levels included in the order.  <b>BitPay Complete Invoice Status</b> should <b>NOT</b> be set to <b>Completed</b>, if using <b>BitPay Confirmed Invoice Status</b> to mark the order as complete.</em><br><br><em>Click <a href = "https://bitpay.com/docs/invoice-states" target = "_blank">here</a> for more information about BitPay invoice statuses.</em>', // phpcs:ignore
					'woocommerce',
				),
				'options'     => $wc_statuses_arr,
				'default'     => 'wc-processing',
			),
			'bitpay_checkout_order_process_complete_status' => array(
				'title'       => __( 'BitPay Complete Invoice Status', 'woocommerce' ),
				'type'        => 'select',
				'description' => __(
						'Configure your Transaction Speeds on your <a href = "' // phpcs:ignore
						. $this->get_processing_link() . '" target = "_blank">BitPay Dashboard</a>, and map the BitPay'
						. '<b>complete</b> invoice status to one of the available WooCommerce order states.'
						. '<br>All WooCommerce status options are listed here for your convenience.<br><br><em>'
						. 'Note: setting the status to <b>Completed</b> will reduce stock levels included in the order.  '
						. '<b>BitPay Confirmed Invoice Status</b> should <b>NOT</b> be set to <b>Completed</b>, if using '
						. '<b>BitPay Complete Invoice Status</b> to mark the order as complete.</em><br><br><em>Click '
						. '<a href = "https://bitpay.com/docs/invoice-states" target = "_blank">here</a>'
						. 'for more information about BitPay invoice statuses.</em>',
					'woocommerce',
				),
				'options'     => $wc_statuses_arr,
				'default'     => 'wc-processing',
			),
			'bitpay_checkout_order_expired_status'      => array(
				'title'       => __( 'BitPay Expired Status', 'woocommerce' ),
				'type'        => 'select',
				'description' => __( 'If set to <b>Yes</b>,  automatically set the order to canceled when the invoice has expired and has been notified by the BitPay IPN.', 'woocommerce' ),

				'options'     => array(
					'0' => 'No',
					'1' => 'Yes',
				),
				'default'     => '0',
			),

			'bitpay_log_mode'                           => array(
				'title'       => __( 'Developer Logging', 'woocommerce' ),
				'type'        => 'select',
				'description' => __( 'Errors will be logged to the plugin <b>log</b> directory automatically.  Set to <b>Enabled</b> to also log transactions, ie invoices and IPN updates', 'woocommerce' ),
				'options'     => array(
					'0' => 'Disabled',
					'1' => 'Enabled',
				),
				'default'     => '1',
			),

		);
	}

	public function process_payment( $order_id ) {
		// this is the one that is called initially when someone checks out.
		global $woocommerce;
		$order = new \WC_Order( $order_id );
		// Return thankyou redirect.
		return array(
			'result'   => 'success',
			'redirect' => $this->get_return_url( $order ),
		);
	}

	private function get_icon_on_payment_page(): string {
		$settings = new BitPayPaymentSettings();

		return $settings->get_payment_logo_url() . '" id="bitpay_logo';
	}

	private function get_processing_link(): string {
		$test = 'https://test.bitpay.com/dashboard/settings/edit/order';

		$bitpay_checkout_options = get_option( 'woocommerce_bitpay_checkout_gateway_settings' );
		if ( ! $bitpay_checkout_options ) { // not configured settings.
			return $test;
		}

		$bitpay_checkout_endpoint = $bitpay_checkout_options['bitpay_checkout_endpoint'] ?? null;
		if ( ! $bitpay_checkout_endpoint ) {
			return $test;
		}

		return match ( $bitpay_checkout_endpoint ) {
			'production' => 'https://www.bitpay.com/dashboard/settings/edit/order',
			default => $test
		};
	}

	private function get_bitpay_version_info(): string {
		$plugin_data = get_file_data(
			__FILE__,
			array(
				'Version'     => 'Version',
				'Plugin_Name' => 'Plugin Name',
			),
			false
		);
		$plugin_name = $plugin_data['Plugin_Name'];

		return $plugin_name . ' ' . $plugin_data['Version'];
	}
}
