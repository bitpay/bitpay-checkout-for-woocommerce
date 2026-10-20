<?php
/**
 * Plugin Name: BitPay Checkout for WooCommerce
 * Plugin URI: https://www.bitpay.com
 * Description: BitPay Checkout Plugin
 * Version: 7.1.4
 * Author: BitPay
 * Author URI: mailto:integrations@bitpay.com?subject=BitPay Checkout for WooCommerce
 */

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/vendor/netresearch/jsonmapper/src/JsonMapper.php';

use BitPayLib\BitPayPluginSetup;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'BITPAY_CHECKOUT_FOR_WC_PLUGIN_FILE' ) ) {
	define( 'BITPAY_CHECKOUT_FOR_WC_PLUGIN_FILE', __FILE__ );
}

$bitpay_plugin_setup = new BitPayPluginSetup();
$bitpay_plugin_setup->execute();
