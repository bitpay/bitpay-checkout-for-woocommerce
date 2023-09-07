<?php
/**
 * Plugin Name: BitPay Checkout for WooCommerce
 * Plugin URI: https://www.bitpay.com
 * Description: BitPay Checkout Plugin
 * Version: 5.1.1
 * Author: BitPay
 * Author URI: mailto:integrations@bitpay.com?subject=BitPay Checkout for WooCommerce
 */

require_once __DIR__ . '/vendorPrefixed/vendor/autoload.php';
require_once __DIR__ . '/vendorPrefixed/vendor/netresearch/jsonmapper/src/JsonMapper.php';

use BitPayVendor\BitPayLib\BitPayPluginSetup;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$bitpay_plugin_setup = new BitPayPluginSetup();
$bitpay_plugin_setup->execute();
