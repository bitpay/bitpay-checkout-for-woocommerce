<?php

declare(strict_types=1);

namespace BitPayLib\Tests\EndToEnd;

use BitPayLib\Tests\EndToEnd\Support\EndToEndTester;

class CreateOrderWithBitPayCest {

	public function it_should_use_redirect_flow_for_create_invoice( EndToEndTester $i ): void {
		$i->loginAsAdmin();
		$i->amOnBitPayAdminSettings();
		$i->resetBitPayAdminSettings();
		$i->saveBitPayAdminSettings();
		$i->prepareDataToMakeAnOrder();
		$i->placeOrder();
		$i->wait( 5 );
		$i->seeInTitle( 'BitPay Invoice' );
	}
}
