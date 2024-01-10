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
class BitPayLogger {

	public function execute( $msg, string $type, bool $is_array = false, $error = false ): void {
		$bitpay_checkout_options = get_option( 'woocommerce_bitpay_checkout_gateway_settings' );
		$log_directory           = plugin_dir_path( __FILE__ ) . '..' . DIRECTORY_SEPARATOR . '..'
			. DIRECTORY_SEPARATOR . 'logs/';
		if ( ! file_exists( $log_directory ) && ! mkdir( $log_directory ) && ! is_dir( $log_directory ) ) {
			throw new \RuntimeException( sprintf( 'Directory "%s" was not created', esc_html( $log_directory ) ) );
		}

		$transaction_log = $log_directory . date( 'Ymd' ) . '_transactions.log'; // phpcs:ignore
		$error_log       = $log_directory . date( 'Ymd' ) . '_error.log';

		$header = PHP_EOL . '======================' . $type . '===========================' . PHP_EOL;
		$footer = PHP_EOL . '=================================================' . PHP_EOL;

		if ( $is_array ) {
			$msg = print_r( $msg, true ); // phpcs:ignore
		}

		// @codingStandardsIgnoreStart
		if ( $error ) {
			error_log( $header, 3, $error_log );
			error_log( $msg, 3, $error_log );
			error_log( $footer, 3, $error_log );
			return;
		}

		if ( (int) $bitpay_checkout_options['bitpay_log_mode'] === 1 ) {
			error_log( $header, 3, $transaction_log );
			error_log( $msg, 3, $transaction_log );
			error_log( $footer, 3, $transaction_log );
		}
		// @codingStandardsIgnoreEnd
	}
}
