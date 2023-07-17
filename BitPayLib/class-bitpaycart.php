<?php

declare(strict_types=1);

namespace BitPayLib;

/**
 * Plugin Name: BitPay Checkout for WooCommerce
 * Plugin URI: https://www.bitpay.com
 * Description: BitPay Checkout Plugin
 * Version: 5.0.0
 * Author: BitPay
 * Author URI: mailto:integrations@bitpay.com?subject=BitPay Checkout for WooCommerce
 */
class BitPayCart {

	public function execute(): void {
		add_action( 'init', 'woocommerce_clear_cart_url' );
	}

	public function woocommerce_clear_cart_url(): void {
		if ( isset( $_GET['custompage'] ) ) { // phpcs:ignore
			global $woocommerce;
			$woocommerce->cart->empty_cart();
		}
	}

	public function load_cart(): void {
		if ( is_null( WC()->cart ) ) {
			wc_load_cart();
		}
	}
}
