<?php

declare(strict_types=1);

namespace BitPayLib\Blocks;

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;
use BitPayLib\BitPayPluginSetup;
use BitPayLib\WcGatewayBitpay;

/**
 * Plugin Name: BitPay Checkout for WooCommerce
 * Plugin URI: https://www.bitpay.com
 * Description: BitPay Checkout Plugin
 * Version: 5.4.0
 * Author: BitPay
 * Author URI: mailto:integrations@bitpay.com?subject=BitPay Checkout for WooCommerce
 */
class BitPayPaymentsBlocks extends AbstractPaymentMethodType {
	protected $name = WcGatewayBitpay::GATEWAY_NAME;
	private ?WcGatewayBitpay $gateway;

	public function initialize() {
		$this->settings = get_option( 'woocommerce_bitpay_checkout_gateway_settings', array() );
		$gateways       = WC()->payment_gateways->payment_gateways();
		$this->gateway  = $gateways[ $this->name ];
	}
	public function is_active() {
		return $this->gateway->is_available();
	}

	public function get_payment_method_script_handles() {
		$version      = BitPayPluginSetup::VERSION;
		$path         = plugins_url( '../../../js/bitpay_payments_blocks.js', __FILE__ );
		$handle       = 'bitpay-checkout-block';
		$dependencies = array( 'wp-hooks' );

		wp_register_script( $handle, $path, $dependencies, $version, true );

		return array( 'bitpay-checkout-block' );
	}

	public function get_payment_method_data() {
		return array(
			'title'       => WcGatewayBitpay::TITLE,
			'description' => $this->get_setting( 'description' ),
			'supports'    => array_filter(
				$this->gateway->supports,
				array(
					$this->gateway,
					'supports',
				)
			),
		);
	}
}
