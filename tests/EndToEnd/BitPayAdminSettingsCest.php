<?php

declare(strict_types=1);

namespace BitPayLib\Tests\EndToEnd;

use BitPayLib\Tests\EndToEnd\Support\EndToEndTester;

class BitPayAdminSettingsCest {

    public function it_should_shows_the_description_on_the_frontend(EndToEndTester $i): void {
        $expectedDescription = 'Pay with BitPay using one of the supported cryptocurrencies ' . rand();

        $i->loginAsAdmin();
        $i->amOnBitPayAdminSettings();
        $i->resetBitPayAdminSettings();
        $i->fillField('#woocommerce_bitpay_checkout_gateway_description', $expectedDescription);
        $i->saveBitPayAdminSettings();
        $i->prepareDataToMakeAnOrder();
        $i->amOnOrderPage();
        $i->selectBitPayPaymentMethod();
        $i->seeInSource($expectedDescription);
    }

    public function it_should_use_custom_checkout_message(EndToEndTester $i): void {
        $expectedText = 'Thank you. We will notify you when BitPay has processed your transaction. ' . rand();

        $i->loginAsAdmin();
        $i->amOnBitPayAdminSettings();
        $i->resetBitPayAdminSettings();
        $i->selectOption('select#woocommerce_bitpay_checkout_gateway_bitpay_checkout_flow', 'Modal');
        $i->fillField('#woocommerce_bitpay_checkout_gateway_bitpay_checkout_checkout_message', $expectedText);
        $i->saveBitPayAdminSettings();
        $i->prepareDataToMakeAnOrder();
        $i->placeOrder();
        $i->wait(5);
        $i->seeInSource($expectedText);
    }
}
