<?php

declare(strict_types=1);

namespace BitPayLib;

use BitPaySDK\Client;
use BitPaySDK\Env;
use BitPaySDK\Exceptions\BitPayException;
use BitPaySDK\PosClient;

/**
 * Plugin Name: BitPay Checkout for WooCommerce
 * Plugin URI: https://www.bitpay.com
 * Description: BitPay Checkout Plugin
 * Version: 5.0.0
 * Author: BitPay
 * Author URI: mailto:integrations@bitpay.com?subject=BitPay Checkout for WooCommerce
 */
class BitPayClientFactory {

	private BitPayPaymentSettings $bitpay_payment_settings;

	public function __construct( BitPayPaymentSettings $bitpay_payment_settings ) {
		$this->bitpay_payment_settings = $bitpay_payment_settings;
	}

	public function create(): Client {
		return new PosClient(
			$this->bitpay_payment_settings->get_bitpay_token(),
			$this->get_environment()
		);
	}

	private function get_environment(): string {
		$environment = $this->bitpay_payment_settings->get_bitpay_environment();
		if ( 'test' === strtolower( $environment ) ) {
			return Env::TEST;
		}

		if ( 'production' === strtolower( $environment ) ) {
			return Env::PROD;
		}

		throw new \RuntimeException( 'Wrong environment ' . $environment );
	}
}
