<?php

declare(strict_types=1);

namespace BitPayLib;

class BitPayCreateOrder {

	public const BITPAY_TOKEN_ORDER_METADATA_KEY = '_bitpay_token';

	private BitPayPaymentSettings $bitpay_payment_settings;

	public function __construct(
		BitPayPaymentSettings $bitpay_payment_settings,
	) {
		$this->bitpay_payment_settings = $bitpay_payment_settings;
	}

	public function execute( int $order_id ): void {
		$token = $this->bitpay_payment_settings->get_bitpay_token();
		$order = new \WC_Order( $order_id );

		$order->update_meta_data( self::BITPAY_TOKEN_ORDER_METADATA_KEY, $token );
		$order->save_meta_data();
	}
}
