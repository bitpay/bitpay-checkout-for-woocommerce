<?php

declare(strict_types=1);

namespace Unit\BitPayLib;

use BitPayLib\BitPayInvoiceFactory;
use BitPayLib\BitPayPaymentSettings;
use BitPayLib\BitPayWordpressHelper;
use WP_UnitTestCase;

class BitPayInvoiceFactoryTest extends WP_UnitTestCase {

	/**
	 * @test
	 */
	public function it_should_create_bitpay_invoice_by_wc_order_id(): void {
		// given
		$expected_price = 12.34;
		$expected_currency = 'USD';
		$expected_order_id = '123';
		$buyer_name = 'SomeName';
		$buyer_email = 'some@email.com';
		$wc_order_id = 44444;
		$checkout_url = 'https://checkout-url.com';

		$payment_settings = $this->getMockBuilder(BitPayPaymentSettings::class)->getMock();
		$wordpress_helper = $this->getMockBuilder(BitPayWordpressHelper::class)
			->disableOriginalConstructor()->getMock();
		$wc_order = $this->getMockBuilder(\WC_Order::class)->getMock();
		$tested_class = new BitPayInvoiceFactory($payment_settings, $wordpress_helper);

		$wc_order->method('get_total')->willReturn($expected_price);
		$wc_order->method('get_currency')->willReturn($expected_currency);
		$wc_order->method('get_order_number')->willReturn($expected_order_id);
		$wc_order->method('get_order_key')->willReturn('123');
		$wc_order->method('get_id')->willReturn($wc_order_id);
		$wp_user = new \WP_User();
		$wp_user->display_name = $buyer_name;
		$wp_user->user_email = $buyer_email;
		$wordpress_helper->method('get_home_url')->willReturn('https://some-url.com');
		$wordpress_helper->method('wp_get_current_user')->willReturn($wp_user);
		$wordpress_helper->method('get_checkout_url')->willReturn($checkout_url);
		$payment_settings->method('should_capture_email')->willReturn(true);
		$payment_settings->method('get_custom_redirect_page')->willReturn(null);
		$payment_settings->method('get_checkout_slug')->willReturn(null);

		$endpoint_url = 'https://some-endpoint_url.com';
		$wordpress_helper->method('get_endpoint_url')
			->with('order-received', (string) $wc_order_id, $checkout_url)
			->willReturn($endpoint_url);

		// when
		$bitpay_invoice = $tested_class->create_by_wc_order($wc_order);

		// then
		self::assertSame( $expected_price, $bitpay_invoice->getPrice() );
		self::assertSame( $expected_currency, $bitpay_invoice->getCurrency() );
		self::assertSame( $expected_order_id, $bitpay_invoice->getOrderId() );
		self::assertSame( $buyer_email, $bitpay_invoice->getBuyer()->getEmail() );
		self::assertSame( $buyer_name, $bitpay_invoice->getBuyer()->getName() );
		self::assertSame( 'https://some-url.com/wp-json/bitpay/ipn/status', $bitpay_invoice->getNotificationURL() );
		self::assertSame( 'https://some-endpoint_url.com?key=123&redirect=false', $bitpay_invoice->getRedirectURL() );
	}

	/**
	 * @test
	 */
	public function it_should_use_custom_checkout_page_for_redirect_url(): void {
		// given
		$checkout_url = 'https://checkout-url.com';

		$payment_settings = $this->getMockBuilder(BitPayPaymentSettings::class)->getMock();
		$wordpress_helper = $this->getMockBuilder(BitPayWordpressHelper::class)
			->disableOriginalConstructor()->getMock();
		$wc_order = $this->getMockBuilder(\WC_Order::class)->getMock();
		$tested_class = new BitPayInvoiceFactory($payment_settings, $wordpress_helper);

		$wc_order->method('get_total')->willReturn(12.34);
		$wc_order->method('get_currency')->willReturn('USD');
		$wc_order->method('get_order_number')->willReturn('123');
		$wc_order->method('get_order_key')->willReturn('1234');
		$wc_order->method('get_id')->willReturn(44444);
		$wordpress_helper->method('get_home_url')->willReturn('https://some-url.com');
		$wordpress_helper->method('wp_get_current_user')->willReturn(new \WP_User());
		$wordpress_helper->method('get_checkout_url')->willReturn($checkout_url);
		$payment_settings->method('should_capture_email')->willReturn(true);
		$payment_settings->method('get_custom_redirect_page')->willReturn('https://some-custom.com');
		$payment_settings->method('get_checkout_slug')->willReturn(null);

		// when
		$bitpay_invoice = $tested_class->create_by_wc_order($wc_order);

		// then
		self::assertSame( 'https://some-custom.com?custompage=true', $bitpay_invoice->getRedirectURL() );
	}
}
