<?php

declare(strict_types=1);

namespace BitPayLib;

use wpdb;

/**
 * Plugin Name: BitPay Checkout for WooCommerce
 * Plugin URI: https://www.bitpay.com
 * Description: BitPay Checkout Plugin
 * Version: 5.4.1
 * Author: BitPay
 * Author URI: mailto:integrations@bitpay.com?subject=BitPay Checkout for WooCommerce
 */
class BitPayWordpressHelper {

	private array $gateway_settings = array();

	public function get_wpdb(): wpdb {
		global $wpdb;
		return $wpdb;
	}

	public function get_wp() {
		global $wp;
		return $wp;
	}

	public function get_order( string $order_id ): \WC_Order {
		return new \WC_Order( $order_id );
	}

	public function get_bitpay_gateway_option( string $name, $default_value = null ): ?string {
		if ( ! $this->gateway_settings ) {
			$this->gateway_settings = get_option( 'woocommerce_bitpay_checkout_gateway_settings' );
		}
		return $this->gateway_settings[ $name ] ?? $default_value;
	}

	public function get_query_var( string $field ): ?string {
		return $this->get_wp()->query_vars[ $field ] ?? null;
	}

	public function get_url_parameter( string $parameter ): ?string {
		return $_GET[ $parameter ] ?? null; // phpcs:ignore
	}

	public function get_checkout_url(): ?string {
		return wc_get_checkout_url();
	}

	public function get_endpoint_url(
		string $endpoint,
		string $value,
		string $permalink
	): ?string {
		return wc_get_endpoint_url( $endpoint, $value, $permalink );
	}

	public function get_home_url(): string {
		return get_home_url();
	}

	public function wp_get_current_user(): \WP_User {
		return wp_get_current_user();
	}
}
