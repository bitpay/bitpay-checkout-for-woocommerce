<?php

declare(strict_types=1);

namespace BitPayLib;

use BitPayLib\Blocks\BitPayPaymentsBlocks;
use WP_REST_Request;

/**
 * Plugin Name: BitPay Checkout for WooCommerce
 * Plugin URI: https://www.bitpay.com
 * Description: BitPay Checkout Plugin
 * Version: 6.0.0
 * Author: BitPay
 * Author URI: mailto:integrations@bitpay.com?subject=BitPay Checkout for WooCommerce
 */
class BitPayPluginSetup {

	public const VERSION = '6.0.0';

	private BitPayIpnProcess $bitpay_ipn_process;
	private BitPayCancelOrder $bitpay_cancel_order;
	private BitPayPaymentSettings $bitpay_payment_settings;
	private BitPayInvoiceCreate $bitpay_invoice_create;
	private BitPayCheckoutTransactions $bitpay_checkout_transactions;

	public function __construct() {
		$this->bitpay_payment_settings      = new BitPayPaymentSettings();
		$factory                            = new BitPayClientFactory( $this->bitpay_payment_settings );
		$cart                               = new BitPayCart();
		$logger                             = new BitPayLogger();
		$this->bitpay_checkout_transactions = new BitPayCheckoutTransactions();
		$this->bitpay_ipn_process           = new BitPayIpnProcess( $this->bitpay_checkout_transactions, $factory, $logger );
		$this->bitpay_cancel_order          = new BitPayCancelOrder( $cart, $this->bitpay_checkout_transactions, $logger );
		$this->bitpay_invoice_create        = new BitPayInvoiceCreate(
			$factory,
			$this->bitpay_checkout_transactions,
			$this->bitpay_payment_settings,
			$logger
		);
	}

	public function execute(): void {
		register_activation_hook( __FILE__, array( $this, 'setup_plugin' ) );
		register_activation_hook( __FILE__, array( $this, 'add_error_page' ) );

		add_action( 'plugins_loaded', array( $this, 'validate_wc_payment_gateway' ), 11 );
		add_action( 'woocommerce_widget_shopping_cart_buttons', array( $this, 'bitpay_mini_checkout' ), 20 );
		add_action( 'template_redirect', array( $this, 'create_bitpay_invoice' ) );
		add_action( 'admin_notices', array( $this, 'update_db' ) );
		add_action( 'admin_notices', array( $this, 'bitpay_checkout_check_token' ) );
		add_action( 'woocommerce_thankyou', array( $this, 'bitpay_checkout_thankyou_page' ), 10, 1 );
		add_action( 'woocommerce_thankyou', array( $this, 'bitpay_checkout_custom_message' ) );
		add_filter( 'woocommerce_payment_gateways', array( $this, 'wc_bitpay_checkout_add_to_gateways' ) );
		add_filter( 'woocommerce_order_button_html', array( $this, 'bitpay_checkout_replace_order_button_html' ), 10, 2 );
		add_action( 'woocommerce_blocks_loaded', array( $this, 'register_payment_block' ) );

		// http://<host>/wp-json/bitpay/ipn/status url.
		// http://<host>/wp-json/bitpay/cartfix/restore url.
		add_action(
			'rest_api_init',
			function () {
				register_rest_route(
					'bitpay/ipn',
					'/status',
					array(
						'methods'             => 'POST,GET',
						'callback'            => array( $this, 'process_ipn' ),
						'permission_callback' => '__return_true',
					)
				);
				register_rest_route(
					'bitpay/cartfix',
					'/restore',
					array(
						'methods'             => 'POST,GET',
						'callback'            => array( $this, 'cancel_order' ),
						'permission_callback' => '__return_true',
					)
				);
			}
		);
	}

	public function setup_plugin(): void {
		$this->check_requirements();
		$this->bitpay_checkout_transactions->create_table();
	}

	public function update_db(): void {
		$this->bitpay_checkout_transactions->update_db_1();
	}

	public function bitpay_checkout_check_token(): void {
		$this->bitpay_payment_settings->check_token();
	}

	public function check_requirements(): void {
		$errors = $this->validate_woo_commerce();

		if ( ! function_exists( 'curl_version' ) ) {
			$errors[] = 'cUrl needs to be installed/enabled for BitPay Checkout to function';
		}

		$plugins = get_plugins();
		foreach ( $plugins as $file => $plugin ) {
			if ( 'Bitpay Woocommerce' === $plugin['Name'] && true === is_plugin_active( $file ) ) {
				deactivate_plugins( plugin_basename( __FILE__ ) );
				$errors[] = 'BitPay for WooCommerce requires that the old plugin, <b>Bitpay Woocommerce</b>, is deactivated and deleted.';
			}
		}

		if ( empty( $errors ) ) {
			return;
		}

		wp_die( implode( "<br>\n", $errors ) . '<br><a href="' . admin_url( 'plugins.php' ) . '">Return to plugins screen</a>' ); // phpcs:ignore
	}

	public function validate_wc_payment_gateway(): void {
		if ( class_exists( '\WC_Payment_Gateway' ) ) {
			return;
		}

		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$plugins_url = admin_url( 'plugins.php' );
		$plugins     = get_plugins();
		foreach ( $plugins as $file => $plugin ) {

			if ( 'BitPay Checkout for WooCommerce' === $plugin['Name'] && true === is_plugin_active( $file ) ) {

				deactivate_plugins( plugin_basename( 'bitpay-checkout-for-woocommerce/index.php' ) );
				wp_die( 'WooCommerce needs to be installed and activated before BitPay Checkout for WooCommerce can be activated.<br><a href="' . $plugins_url . '">Return to plugins screen</a>' ); // phpcs:ignore
			}
		}
	}

	public function bitpay_mini_checkout(): void {
		$this->bitpay_payment_settings->bitpay_mini_checkout();
	}

	public function wc_bitpay_checkout_add_to_gateways( array $gateways ): array {
		return $this->bitpay_payment_settings->wc_bitpay_checkout_add_to_gateways( $gateways );
	}

	public function bitpay_checkout_replace_order_button_html( $order_button, $override = false ) {
		return $this->bitpay_payment_settings->bitpay_checkout_replace_order_button_html( $order_button, $override );
	}

	public function create_bitpay_invoice(): void {
		$this->bitpay_invoice_create->execute();
	}

	public function process_ipn( WP_REST_Request $request ): void {
		$this->bitpay_ipn_process->execute( $request );
	}

	public function cancel_order( WP_REST_Request $request ): void {
		$this->bitpay_cancel_order->execute( $request );
	}

	public function bitpay_checkout_thankyou_page( $order_id ): void {
		$page = new BitPayPages( $this->bitpay_payment_settings );
		$page->checkout_thank_you( (int) $order_id );
	}

	public function bitpay_checkout_custom_message( $order_id ): void {
		$this->bitpay_payment_settings->redirect_after_purchase( $order_id );
	}

	public function add_error_page(): void {

		$my_post = array(
			'post_title'   => wp_strip_all_tags( 'Order Cancelled' ),
			'post_content' => 'Your order stands cancelled. Please go back to <a href="/shop">Shop page</a> and reorder.',
			'post_status'  => 'publish',
			'post_author'  => 'Bitpay',
			'post_type'    => 'page',
		);

		// Insert the post into the database.
		wp_insert_post( $my_post );
	}

	private function validate_woo_commerce(): array {
		global $woocommerce;
		if ( null === $woocommerce ) {
			return array( 'The WooCommerce plugin for WordPress needs to be installed and activated. Please contact your web server administrator for assistance.' );
		}

		if ( true === version_compare( $woocommerce->version, '2.2', '<' ) ) {
			return array( 'Your WooCommerce version is too old. The BitPay payment plugin requires WooCommerce 2.2 or higher to function. Your version is ' . $woocommerce->version . '. Please contact your web server administrator for assistance.' );
		}

		return array();
	}

	public function register_payment_block(): void {
		add_action(
			'woocommerce_blocks_payment_method_type_registration',
			function ( \Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry ) {
				$container = \Automattic\WooCommerce\Blocks\Package::container();

				$container->register(
					BitPayPaymentsBlocks::class,
					function () {
						return new BitPayPaymentsBlocks();
					}
				);
				$payment_method_registry->register(
					$container->get( BitPayPaymentsBlocks::class )
				);
			},
			5
		);
	}
}
