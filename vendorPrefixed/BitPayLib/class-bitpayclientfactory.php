<?php

declare (strict_types=1);
namespace BitPayVendor\BitPayLib;

use BitPayVendor\BitPaySDK\Client;
use BitPayVendor\BitPaySDK\Env;
use BitPayVendor\BitPaySDK\Exceptions\BitPayException;
use BitPayVendor\BitPaySDK\PosClient;
/**
 * Plugin Name: BitPay Checkout for WooCommerce
 * Plugin URI: https://www.bitpay.com
 * Description: BitPay Checkout Plugin
 * Version: 5.2.0
 * Author: BitPay
 * Author URI: mailto:integrations@bitpay.com?subject=BitPay Checkout for WooCommerce
 */
class BitPayClientFactory
{
    private BitPayPaymentSettings $bitpay_payment_settings;
    public function __construct(BitPayPaymentSettings $bitpay_payment_settings)
    {
        $this->bitpay_payment_settings = $bitpay_payment_settings;
    }
    public function create() : Client
    {
        $token = $this->bitpay_payment_settings->get_bitpay_token();
        if (!$token) {
            wc_add_notice('<strong>' . esc_html(__('Missing BitPay Token')), 'error');
            throw new \RuntimeException('Missing BitPay Token');
        }
        return new PosClient($token, $this->get_environment());
    }
    private function get_environment() : string
    {
        $environment = $this->bitpay_payment_settings->get_bitpay_environment();
        if ('test' === \strtolower($environment)) {
            return Env::TEST;
        }
        if ('production' === \strtolower($environment)) {
            return Env::PROD;
        }
        throw new \RuntimeException('Wrong environment ' . esc_html($environment));
    }
}
