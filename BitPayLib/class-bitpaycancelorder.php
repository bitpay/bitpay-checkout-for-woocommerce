<?php

declare(strict_types=1);

namespace BitPayLib;

/**
 * Plugin Name: BitPay Checkout for WooCommerce
 * Plugin URI: https://www.bitpay.com
 * Description: BitPay Checkout Plugin
 * Version: 6.0.0
 * Author: BitPay
 * Author URI: mailto:integrations@bitpay.com?subject=BitPay Checkout for WooCommerce
 */
class BitPayCancelOrder {

	private BitPayCart $bitpay_cart;
	private BitPayCheckoutTransactions $transactions;
	private BitPayLogger $logger;

	public function __construct( BitPayCart $cart, BitPayCheckoutTransactions $transactions, BitPayLogger $logger ) {
		$this->bitpay_cart  = $cart;
		$this->transactions = $transactions;
		$this->logger       = $logger;
	}

	public function execute( \WP_REST_Request $request ): void {
		$invoice_id = $request->get_param( 'invoiceid' );
		if ( ! $invoice_id ) {
			die();
		}

		$this->bitpay_cart->load_cart();
		$order_id = $this->transactions->get_order_id_by_invoice_id( $invoice_id );
		if ( ! $order_id ) {
			die();
		}
		$order = new \WC_Order( $order_id );
		$items = $order->get_items();

		$this->logger->execute( 'User canceled order: ' . $order_id . ', removing from WooCommerce', 'USER CANCELED ORDER', true );
		$order->add_order_note( 'User closed the modal, the order will be set to canceled state' );
		$order->update_status( 'cancelled', __( 'BitPay payment canceled by user', 'woocommerce' ) );

		// clear the cart first so things dont double up.
		WC()->cart->empty_cart();
		foreach ( $items as $item ) {
			// now insert for each quantity.
			$item_count = $item->get_quantity();
			for ( $i = 0; $i < $item_count; $i++ ) :
				WC()->cart->add_to_cart( $item->get_product_id() );
			endfor;
		}

		$this->clear_cookie_for_invoice_id();
	}

	private function clear_cookie_for_invoice_id(): void {
		setcookie( BitPayPluginSetup::COOKIE_INVOICE_ID_NAME, '', time() - 3600 );
	}
}
