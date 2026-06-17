<?php

declare(strict_types=1);

namespace BitPayLib\Tests\EndToEnd;

use BitPayLib\Tests\EndToEnd\Support\EndToEndTester;

class BitPayAdminSettingsCest {

	public function it_should_show_the_description_on_the_frontend( EndToEndTester $i ): void {
		$expectedDescription = 'Pay with BitPay using one of the supported cryptocurrencies ' . rand();

		$i->loginAsAdmin();
		$i->amOnBitPayAdminSettings();
		$i->resetBitPayAdminSettings();
		$i->fillField( '#woocommerce_bitpay_checkout_gateway_description', $expectedDescription );
		$i->saveBitPayAdminSettings();
		$i->prepareDataToMakeAnOrder();
		$i->amOnOrderPage();
		$i->selectBitPayPaymentMethod();
		$i->seeInSource( $expectedDescription );
	}
}
