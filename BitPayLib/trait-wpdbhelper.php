<?php

declare(strict_types=1);

namespace BitPayLib;

use wpdb;

/**
 * Plugin Name: BitPay Checkout for WooCommerce
 * Plugin URI: https://www.bitpay.com
 * Description: BitPay Checkout Plugin
 * Version: 5.5.1
 * Author: BitPay
 * Author URI: mailto:integrations@bitpay.com?subject=BitPay Checkout for WooCommerce
 */
trait WpDbHelper {

	public function get_wpdb(): wpdb {
		global $wpdb;
		return $wpdb;
	}
}
