<?php

declare(strict_types=1);

namespace BitPayLib;

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
class BitPayInvoiceFactory {

	private BitPayPaymentSettings $bitpay_payment_settings;
	private BitPayWordpressHelper $wordpress_helper;

	public function __construct(
		BitPayPaymentSettings $bitpay_payment_settings,
		BitPayWordpressHelper $wordpress_helper
	) {
		$this->bitpay_payment_settings = $bitpay_payment_settings;
		$this->wordpress_helper        = $wordpress_helper;
	}

	public function create_by_wc_order( \WC_Order $wc_order ): Invoice {
		$bitpay_invoice = new Invoice();
		$bitpay_invoice->setPrice( (float) $wc_order->get_total() );
		$bitpay_invoice->setCurrency( $wc_order->get_currency() );
		$bitpay_invoice->setOrderId( $wc_order->get_order_number() );
		$bitpay_invoice->setAcceptanceWindow( 1200000 );
		$bitpay_invoice->setNotificationURL( $this->wordpress_helper->get_home_url() . '/wp-json/bitpay/ipn/status' );
		$bitpay_invoice->setExtendedNotifications( true );
		$this->add_buyer_to_invoice( $bitpay_invoice );
		$this->add_redirect_url( $wc_order, $bitpay_invoice );

		return $bitpay_invoice;
	}

	private function add_buyer_to_invoice( Invoice $bitpay_invoice ): void {
		if ( ! $this->bitpay_payment_settings->should_capture_email() ) {
			return;
		}

		/** @var \WP_User $current_user */ // phpcs:ignore
		$current_user = $this->wordpress_helper->wp_get_current_user();

		if ( $current_user->user_email ) {
			$buyer = new Buyer();
			$buyer->setName( $current_user->display_name );
			$buyer->setEmail( $current_user->user_email );
			$bitpay_invoice->setBuyer( $buyer );
		}
	}

	private function add_redirect_url( \WC_Order $order, Invoice $bitpay_invoice ): void {
		$bitpay_invoice->setRedirectURL( $this->get_redirect_url( $order ) );
	}

	private function get_redirect_url( \WC_Order $order ): string {
		$custom_redirect_page = $this->bitpay_payment_settings->get_custom_redirect_page();
		if ( $custom_redirect_page ) {
			return $custom_redirect_page . '?custompage=true';
		}

		$url_suffix    = '?key=' . $order->get_order_key() . '&redirect=false';
		$checkout_slug = $this->bitpay_payment_settings->get_checkout_slug();
		if ( $checkout_slug ) {
			return get_home_url() . DIRECTORY_SEPARATOR . $checkout_slug . '/order-received/'
				. $order->get_id() . DIRECTORY_SEPARATOR . $url_suffix;
		}

		return $this->wordpress_helper->get_endpoint_url(
			'order-received',
			(string) $order->get_id(),
			$this->wordpress_helper->get_checkout_url()
		) . $url_suffix;
	}
}
