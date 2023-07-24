<?php
/**
 * Plugin Name: BitPay Checkout for WooCommerce
 * Plugin URI: https://www.bitpay.com
 * Description: BitPay Checkout Plugin
 * Version: 5.0.1
 * Author: BitPay
 * Author URI: mailto:integrations@bitpay.com?subject=BitPay Checkout for WooCommerce
 */

require_once __DIR__ . '/vendor/autoload.php';

use BitPayLib\BitPayPluginSetup;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$bitpay_plugin_setup = new BitPayPluginSetup();
$bitpay_plugin_setup->execute();
