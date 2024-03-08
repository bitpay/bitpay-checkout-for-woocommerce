<?php

declare(strict_types=1);

namespace BitPayLib\Tests\EndToEnd;

use BitPayLib\Tests\EndToEnd\Support\EndToEndTester;

class CreateOrderWithBitPayCest
{
    public function it_should_use_redirect_flow_for_create_invoice(EndToEndTester $i): void {
        $i->loginAsAdmin();
        $i->amOnBitPayAdminSettings();
        $i->resetBitPayAdminSettings();
        $i->selectOption('select#woocommerce_bitpay_checkout_gateway_bitpay_checkout_flow', 'Redirect');
        $i->saveBitPayAdminSettings();
        $i->prepareDataToMakeAnOrder();
        $i->placeOrder();
        $i->wait(5);
        $i->seeInTitle('BitPay Invoice');
    }

    public function it_should_use_modal_flow_for_create_invoice(EndToEndTester $i): void {
        $i->loginAsAdmin();
        $i->amOnBitPayAdminSettings();
        $i->resetBitPayAdminSettings();
        $i->selectOption('select#woocommerce_bitpay_checkout_gateway_bitpay_checkout_flow', 'Modal');
        $i->saveBitPayAdminSettings();
        $i->prepareDataToMakeAnOrder();
        $i->placeOrder();
        $i->wait(5);

        $bitPayIframe = $i->executeJS('return document.querySelector(\'iframe[name="bitpay"]\') !== null');
        if (!$bitPayIframe) {
            throw new \RuntimeException('Missing BitPay Iframe. Modal does not work');
        }
    }

    public function it_should_redirect_to_close_url_after_purchase(EndToEndTester $i): void {
        $url = 'https://developer.bitpay.com/docs/getting-started';

        $i->loginAsAdmin();
        $i->amOnBitPayAdminSettings();
        $i->resetBitPayAdminSettings();
        // Close URL should works only with Redirect but unfortunately Javascript doesn't work on BitPay order page
        // (so we cannot close invoice and redirect to /order/order-received shop page)
        // Modal is used ONLY to redirect to /order/order-received like from BitPay Invoice page
        $i->selectOption('select#woocommerce_bitpay_checkout_gateway_bitpay_checkout_flow', 'Modal');
        $i->fillField('#woocommerce_bitpay_checkout_gateway_bitpay_close_url', $url);
        $i->saveBitPayAdminSettings();
        $i->prepareDataToMakeAnOrder();
        $i->placeOrder();
        $i->wait(5);

        if ($i->grabFullUrl() !== $url) {
            throw new \RuntimeException('Wrong url ' . $url);
        }
    }

    public function it_should_use_custom_checkout_page(EndToEndTester $i): void {
        $slug = 'mytestslug';
        $i->loginAsAdmin();
        $i->amOnBitPayAdminSettings();
        $i->selectOption('select#woocommerce_bitpay_checkout_gateway_bitpay_checkout_flow', 'Modal');
        $i->fillField('#woocommerce_bitpay_checkout_gateway_bitpay_checkout_slug', $slug);
        $i->saveBitPayAdminSettings();
        $i->prepareDataToMakeAnOrder();
        $i->placeOrder();
        $i->wait(5);
        $url = $i->grabFullUrl();
        if (!str_contains($url, $slug . '/order-received')) {
            throw new \RuntimeException('Wrong slug ' . $slug . ' for url ' . $url);
        }
    }
}
