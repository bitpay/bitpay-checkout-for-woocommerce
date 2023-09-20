<?php

declare(strict_types=1);

namespace BitPayLib;

use BitPaySDK\Model\Invoice\Invoice;
use wpdb;

/**
 * Plugin Name: BitPay Checkout for WooCommerce
 * Plugin URI: https://www.bitpay.com
 * Description: BitPay Checkout Plugin
 * Version: 5.3.0
 * Author: BitPay
 * Author URI: mailto:integrations@bitpay.com?subject=BitPay Checkout for WooCommerce
 */
class BitPayCheckoutTransactions {

	private const TABLE_NAME = '_bitpay_checkout_transactions';

	public function create_table(): void {
		$table_name = '_bitpay_checkout_transactions';

		$charset_collate = $this->get_wpdb()->get_charset_collate();
		$sql             = "CREATE TABLE IF NOT EXISTS $table_name(
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `order_id` varchar(255) NOT NULL,
        `transaction_id` varchar(255) NOT NULL,
        `transaction_status` varchar(50) NOT NULL DEFAULT 'new',
        `date_added` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
        ) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	private function get_wpdb(): wpdb {
		global $wpdb;
		return $wpdb;
	}

	public function create_transaction(
		string $order_id,
		string $transaction_id,
		string $status
	): void {
		$query = $this->get_wpdb()->prepare(
			'INSERT INTO ' . self::TABLE_NAME . ' (order_id,transaction_id,transaction_status) VALUES(%s,%s,%s)',
			$order_id,
			$transaction_id,
			$status
		);
		$this->get_wpdb()->query( $query );
	}

	public function update_db_1(): void {
		$section    = $_GET['section'] ?? null; // phpcs:ignore
		$db1_option = (int) get_option( 'bitpay_wc_checkout_db1', 0 );
		if ( ! $section || 'bitpay_checkout_gateway' !== $section || 1 === $db1_option || ! is_admin() ) {
			return;
		}

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$table_name = self::TABLE_NAME;

		$charset_collate = $this->get_wpdb()->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS $table_name(
		`id` int(11) NOT NULL AUTO_INCREMENT,
		`order_id` varchar(255) NOT NULL,
		`transaction_id` varchar(255) NOT NULL,
		`transaction_status` varchar(50) NOT NULL DEFAULT 'new',
		`date_added` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
		PRIMARY KEY (`id`)
		) $charset_collate;";

		dbDelta( $sql );
		$sql = "ALTER TABLE `$table_name` CHANGE `order_id` `order_id` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL; ";
		$this->get_wpdb()->query( $sql );
		update_option( 'bitpay_wc_checkout_db1', 1 );
	}

	public function get_order_id_by_invoice_id( string $invoice_id ): ?int {
		$table_name = self::TABLE_NAME;
		$wp_db      = $this->get_wpdb();
		$sql        = $wp_db->prepare(
			"SELECT order_id FROM $table_name WHERE transaction_id = %s LIMIT 1",
			$invoice_id
		);
		$order_id   = $wp_db->get_var( $sql );
		if ( ! $order_id ) {
			return null;
		}
		return (int) $order_id;
	}

	public function count_transaction_id( string $invoice_id ): int {
		$table_name = self::TABLE_NAME;
		$wp_db      = $this->get_wpdb();
		$sql        = $wp_db->prepare(
			"SELECT COUNT(order_id) FROM $table_name WHERE transaction_id = %s",
			$invoice_id
		);

		return (int) $wp_db->get_var( $sql );
	}

	public function update_transaction_status( Invoice $invoice ): void {
		$this->get_wpdb()->update(
			self::TABLE_NAME,
			array( 'transaction_status' => $invoice->getStatus() ),
			array(
				'order_id'       => $invoice->getOrderId(),
				'transaction_id' => $invoice->getId(),
			)
		);
	}
}
