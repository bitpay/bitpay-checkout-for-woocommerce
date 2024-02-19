<?php

declare(strict_types=1);

namespace BitPayLib;

use BitPaySDK\Exceptions\BitPayException;
use BitPaySDK\Model\Facade;
use BitPaySDK\Model\Invoice\Buyer;
use BitPaySDK\Model\Invoice\Invoice;

/**
 * Plugin Name: BitPay Checkout for WooCommerce
 * Plugin URI: https://www.bitpay.com
 * Description: BitPay Checkout Plugin
 * Version: 6.0.0
 * Author: BitPay
 * Author URI: mailto:integrations@bitpay.com?subject=BitPay Checkout for WooCommerce
 */
class BitPayInvoiceCreate {

	private BitPayClientFactory $client_factory;
	private BitPayLogger $bitpay_logger;
	private BitPayCheckoutTransactions $bitpay_checkout_transactions;
	private BitPayWordpressHelper $wordpress_helper;
	private BitPayInvoiceFactory $bitpay_invoice_factory;

	public function __construct(
		BitPayClientFactory $client_factory,
		BitPayInvoiceFactory $bitpay_invoice_factory,
		BitPayCheckoutTransactions $bitpay_checkout_transactions,
		BitPayWordpressHelper $wordpress_helper,
		BitPayLogger $bitpay_logger
	) {
		$this->client_factory               = $client_factory;
		$this->bitpay_invoice_factory       = $bitpay_invoice_factory;
		$this->bitpay_checkout_transactions = $bitpay_checkout_transactions;
		$this->wordpress_helper             = $wordpress_helper;
		$this->bitpay_logger                = $bitpay_logger;
	}

	public function execute(): void {
		$bitpay_checkout_options = get_option( 'woocommerce_bitpay_checkout_gateway_settings' );

		$order_id = $this->wordpress_helper->get_query_var( 'order-received' );

		if ( ! $order_id || ! is_checkout() ) {
			return;
		}

		try {
			$order = $this->wordpress_helper->get_order( $order_id );

			$redirect = $this->wordpress_helper->get_url_parameter( 'redirect' );
			if ( $redirect === 'false' ) { // phpcs:ignore
				$this->clear_invoice_id_cookie();
				return;
			}

			if ( $order->get_payment_method() !== 'bitpay_checkout_gateway' ) {
				return;
			}
			$bitpay_invoice = $this->bitpay_invoice_factory->create_by_wc_order( $order );
			$bitpay_invoice = $this->client_factory->create()->createInvoice( $bitpay_invoice, Facade::POS, false );

			$this->bitpay_logger->execute( $bitpay_invoice->toArray(), 'NEW BITPAY INVOICE', true );

			$invoice_id = $bitpay_invoice->getId();
			$this->set_cookie_for_redirects_and_updating_order_status( $invoice_id );

			$use_modal = (int) $bitpay_checkout_options['bitpay_checkout_flow'];

			$this->bitpay_checkout_insert_order_note( $order_id, $invoice_id );

			if ( 2 === $use_modal ) {
				wp_redirect( $bitpay_invoice->getUrl() ); // phpcs:ignore
				exit();
			}

			wp_redirect( $bitpay_invoice->getRedirectURL() ); // phpcs:ignore
			exit();
		} catch ( BitPayException $e ) {
			$this->bitpay_logger->execute( $e->getMessage(), 'NEW BITPAY INVOICE', false, true );
			$error_url = get_home_url() . '/' . $bitpay_checkout_options['bitpay_checkout_error'];
			$order     = $this->wordpress_helper->get_order( $order_id );
			$items     = $order->get_items();
			$order->update_status( 'wc-cancelled', __( $e->getMessage() . '.', 'woocommerce' ) ); // phpcs:ignore

			// clear the cart first so things dont double up.
			WC()->cart->empty_cart();
			foreach ( $items as $item ) {
				// now insert for each quantity.
				$item_count = $item->get_quantity();
				for ( $i = 0; $i < $item_count; $i++ ) {
					WC()->cart->add_to_cart( $item->get_product_id() );
				}
			}
			wp_redirect( $error_url ); // phpcs:ignore
			die();
		} catch ( \Exception $e ) {
			$this->bitpay_logger->execute( $e->getMessage(), 'NEW BITPAY INVOICE', false, true );
			global $woocommerce;
			$cart_url = $woocommerce->cart->get_cart_url();
			wp_redirect( $cart_url ); // phpcs:ignore
			exit;
		}
	}

	private function bitpay_checkout_insert_order_note( $order_id = null, $transaction_id = null ): void {
		$this->bitpay_checkout_transactions->create_transaction( $order_id, $transaction_id, 'pending' );

		if ( null === $order_id || null === $transaction_id ) {
			$this->bitpay_logger->execute( 'Missing values' . PHP_EOL . 'order id: ' . $order_id . PHP_EOL . 'transaction id: ' . $transaction_id, 'error', false, true );
			return;
		}

		$order = $this->wordpress_helper->get_order( $order_id );
		$order->set_transaction_id( $transaction_id );
		$order->save();
	}

	private function clear_invoice_id_cookie(): void {
		setcookie( BitPayPluginSetup::COOKIE_INVOICE_ID_NAME, '', time() - 3600 );
	}

	private function set_cookie_for_redirects_and_updating_order_status( ?string $invoice_id ): void {
		$cookie_name  = BitPayPluginSetup::COOKIE_INVOICE_ID_NAME;
		$cookie_value = $invoice_id;
		setcookie( $cookie_name, $cookie_value, time() + ( 86400 * 30 ), '/' );
	}
}
