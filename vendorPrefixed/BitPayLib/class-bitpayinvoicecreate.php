<?php

declare (strict_types=1);
namespace BitPayVendor\BitPayLib;

use BitPayVendor\BitPaySDK\Exceptions\BitPayException;
use BitPayVendor\BitPaySDK\Model\Facade;
use BitPayVendor\BitPaySDK\Model\Invoice\Buyer;
use BitPayVendor\BitPaySDK\Model\Invoice\Invoice;
/**
 * Plugin Name: BitPay Checkout for WooCommerce
 * Plugin URI: https://www.bitpay.com
 * Description: BitPay Checkout Plugin
 * Version: 5.1.1
 * Author: BitPay
 * Author URI: mailto:integrations@bitpay.com?subject=BitPay Checkout for WooCommerce
 */
class BitPayInvoiceCreate
{
    private const COOKIE_INVOICE_ID_NAME = 'bitpay-invoice-id';
    private BitPayClientFactory $client_factory;
    private BitPayLogger $bitpay_logger;
    private BitPayCheckoutTransactions $bitpay_checkout_transactions;
    private BitPayPaymentSettings $bitpay_payment_settings;
    public function __construct(BitPayClientFactory $client_factory, BitPayCheckoutTransactions $bitpay_checkout_transactions, BitPayPaymentSettings $bitpay_payment_settings, BitPayLogger $bitpay_logger)
    {
        $this->client_factory = $client_factory;
        $this->bitpay_checkout_transactions = $bitpay_checkout_transactions;
        $this->bitpay_payment_settings = $bitpay_payment_settings;
        $this->bitpay_logger = $bitpay_logger;
    }
    public function execute() : void
    {
        global $wp;
        $bitpay_checkout_options = get_option('woocommerce_bitpay_checkout_gateway_settings');
        if (!is_checkout() || empty($wp->query_vars['order-received'])) {
            return;
        }
        $order_id = $wp->query_vars['order-received'];
        try {
            $order = new \WC_Order($order_id);
            if (isset($_GET['redirect']) && $_GET['redirect'] === 'false') {
                // phpcs:ignore
                $this->clear_invoice_id_cookie();
                return;
            }
            if ($order->get_payment_method() !== 'bitpay_checkout_gateway') {
                return;
            }
            $bitpay_invoice = new Invoice();
            $bitpay_invoice->setPrice((float) $order->get_total());
            $bitpay_invoice->setCurrency($order->get_currency());
            $bitpay_invoice->setOrderId($order->get_order_number());
            $bitpay_invoice->setAcceptanceWindow(1200000);
            $bitpay_invoice->setNotificationURL(get_home_url() . '/wp-json/bitpay/ipn/status');
            $bitpay_invoice->setExtendedNotifications(\true);
            $this->add_buyer_to_invoice($bitpay_invoice);
            $this->add_redirect_url($order, $bitpay_invoice);
            $bitpay_invoice = $this->client_factory->create()->createInvoice($bitpay_invoice, Facade::POS, \false);
            $this->bitpay_logger->execute($bitpay_invoice->toArray(), 'NEW BITPAY INVOICE', \true);
            $invoice_id = $bitpay_invoice->getId();
            $this->set_cookie_for_redirects_and_updating_order_status($invoice_id);
            $use_modal = (int) $bitpay_checkout_options['bitpay_checkout_flow'];
            $this->bitpay_checkout_insert_order_note($order_id, $invoice_id);
            if (2 === $use_modal) {
                wp_redirect($bitpay_invoice->getUrl());
                // phpcs:ignore
                exit;
            }
            wp_redirect($bitpay_invoice->getRedirectURL());
            // phpcs:ignore
            exit;
        } catch (BitPayException $e) {
            $error_url = get_home_url() . '/' . $bitpay_checkout_options['bitpay_checkout_error'];
            $order = new \WC_Order($order_id);
            $items = $order->get_items();
            $order->update_status('wc-cancelled', __($e->getMessage() . '.', 'woocommerce'));
            // phpcs:ignore
            // clear the cart first so things dont double up.
            WC()->cart->empty_cart();
            foreach ($items as $item) {
                // now insert for each quantity.
                $item_count = $item->get_quantity();
                for ($i = 0; $i < $item_count; $i++) {
                    WC()->cart->add_to_cart($item->get_product_id());
                }
            }
            wp_redirect($error_url);
            // phpcs:ignore
            die;
        } catch (\Exception $e) {
            global $woocommerce;
            $cart_url = $woocommerce->cart->get_cart_url();
            wp_redirect($cart_url);
            // phpcs:ignore
            exit;
        }
    }
    private function bitpay_checkout_insert_order_note($order_id = null, $transaction_id = null) : void
    {
        $this->bitpay_checkout_transactions->create_transaction($order_id, $transaction_id, 'pending');
        if (null === $order_id || null === $transaction_id) {
            $this->bitpay_logger->execute('Missing values' . \PHP_EOL . 'order id: ' . $order_id . \PHP_EOL . 'transaction id: ' . $transaction_id, 'error', \false, \true);
            return;
        }
        $order = new \WC_Order($order_id);
        $order->set_transaction_id($transaction_id);
        $order->save();
    }
    private function clear_invoice_id_cookie() : void
    {
        \setcookie(self::COOKIE_INVOICE_ID_NAME, '', \time() - 3600);
    }
    private function add_buyer_to_invoice(Invoice $bitpay_invoice) : void
    {
        if (!$this->bitpay_payment_settings->should_capture_email()) {
            return;
        }
        /** @var \WP_User $current_user */
        // phpcs:ignore
        $current_user = wp_get_current_user();
        if ($current_user->user_email) {
            $buyer = new Buyer();
            $buyer->setName($current_user->display_name);
            $buyer->setEmail($current_user->user_email);
            $bitpay_invoice->setBuyer($buyer);
        }
    }
    private function add_redirect_url(\WC_Order $order, Invoice $bitpay_invoice) : void
    {
        $bitpay_invoice->setRedirectURL($this->get_redirect_url($order));
    }
    private function set_cookie_for_redirects_and_updating_order_status(?string $invoice_id) : void
    {
        $cookie_name = self::COOKIE_INVOICE_ID_NAME;
        $cookie_value = $invoice_id;
        \setcookie($cookie_name, $cookie_value, \time() + 86400 * 30, '/');
    }
    private function get_redirect_url(\WC_Order $order) : string
    {
        $custom_redirect_page = $this->bitpay_payment_settings->get_custom_redirect_page();
        if ($custom_redirect_page) {
            return $custom_redirect_page . '?custompage=true';
        }
        $url_suffix = '?key=' . $order->get_order_key() . '&redirect=false';
        $checkout_slug = $this->bitpay_payment_settings->get_checkout_slug();
        if ($checkout_slug) {
            return get_home_url() . \DIRECTORY_SEPARATOR . $checkout_slug . '/order-received/' . $order->get_id() . \DIRECTORY_SEPARATOR . $url_suffix;
        }
        return wc_get_endpoint_url('order-received', $order->get_id(), wc_get_checkout_url()) . $url_suffix;
    }
}
