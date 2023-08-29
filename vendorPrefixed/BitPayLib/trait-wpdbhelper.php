<?php

declare (strict_types=1);
namespace BitPayVendor\BitPayLib;

use wpdb;
/**
 * Plugin Name: BitPay Checkout for WooCommerce
 * Plugin URI: https://www.bitpay.com
 * Description: BitPay Checkout Plugin
 * Version: 5.1.0
 * Author: BitPay
 * Author URI: mailto:integrations@bitpay.com?subject=BitPay Checkout for WooCommerce
 */
trait WpDbHelper
{
    public function get_wpdb() : wpdb
    {
        global $wpdb;
        return $wpdb;
    }
}
