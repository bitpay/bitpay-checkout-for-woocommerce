<?php

declare(strict_types=1);

namespace BitPayLib;

use WC_Logger;

/**
 * Plugin Name: BitPay Checkout for WooCommerce
 * Plugin URI: https://www.bitpay.com
 * Description: BitPay Checkout Plugin
 * Version: 7.1.3
 * Author: BitPay
 * Author URI: mailto:integrations@bitpay.com?subject=BitPay Checkout for WooCommerce
 */
class BitPayLogger {
	private ?WC_Logger $logger = null;

	private function get_logger(): WC_Logger {
		if ( null === $this->logger ) {
			$this->logger = wc_get_logger();
		}
		return $this->logger;
	}

	public function execute( $msg, string $type, bool $is_array = false, bool $error = false ): void {
		$bitpay_checkout_options = get_option( 'woocommerce_bitpay_checkout_gateway_settings' );

		if ( $is_array ) {
			$msg = print_r( $msg, true ); // phpcs:ignore
		}

		$header = '======================' . $type . '===========================';
		$footer = '=================================================';

		if ( $error ) {
			$this->get_logger()->error( $header, array( 'source' => 'bitpay_error' ) );
			$this->get_logger()->error( $msg, array( 'source' => 'bitpay_error' ) );
			$this->get_logger()->error( $footer, array( 'source' => 'bitpay_error' ) );
			return;
		}

		// Log to `bitpay_transactions` only if `bitpay_log_mode` is set to 1.
		if ( 1 === (int) $bitpay_checkout_options['bitpay_log_mode'] ) {
			$this->get_logger()->info( $header, array( 'source' => 'bitpay_transactions' ) );
			$this->get_logger()->info( $msg, array( 'source' => 'bitpay_transactions' ) );
			$this->get_logger()->info( $footer, array( 'source' => 'bitpay_transactions' ) );
		}
	}
}
