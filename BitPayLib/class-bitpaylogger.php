<?php

declare(strict_types=1);

namespace BitPayLib;

use WC_Logger_Interface;

/**
 * Plugin Name: BitPay Checkout for WooCommerce
 * Plugin URI: https://www.bitpay.com
 * Description: BitPay Checkout Plugin
 * Version: 7.0.2
 * Author: BitPay
 * Author URI: mailto:integrations@bitpay.com?subject=BitPay Checkout for WooCommerce
 */
class BitPayLogger {
	private ?WC_Logger_Interface $logger = null;

	private function get_logger(): WC_Logger_Interface {
		if ( $this->logger === null ) {
			$this->logger = wc_get_logger();
		}
		return $this->logger;
	}

	public function execute( $msg, string $type, bool $is_array = false, bool $error = false ): void {
		$bitpay_checkout_options = get_option( 'woocommerce_bitpay_checkout_gateway_settings' );

		if ( $is_array ) {
			$msg = print_r( $msg, true ); // Replace with proper logging if needed.
		}

		if ( $error ) {
			$type = 'error';
		}

		$valid_levels = array( 'emergency', 'alert', 'critical', 'error', 'warning', 'notice', 'info', 'debug' );
		if ( ! in_array( $type, $valid_levels, true ) ) {
			$type = 'info';
		}

		if ( $error ) {
			$this->get_logger()->log( $type, $msg, array( 'source' => 'bitpay_error' ) );
			return;
		}

		// Log to `bitpay_transactions` only if `bitpay_log_mode` is set to 1.
		if ( 1 === (int) $bitpay_checkout_options['bitpay_log_mode'] ) {
			$this->get_logger()->log( $type, $msg, array( 'source' => 'bitpay_transactions' ) );
		}
	}
}
