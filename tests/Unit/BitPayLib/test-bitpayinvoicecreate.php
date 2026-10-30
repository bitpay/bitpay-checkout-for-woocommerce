<?php

declare(strict_types=1);

namespace Unit\BitPayLib;

use BitPayLib\BitPayCheckoutTransactions;
use BitPayLib\BitPayClientFactory;
use BitPayLib\BitPayInvoiceCreate;
use BitPayLib\BitPayInvoiceFactory;
use BitPayLib\BitPayLogger;
use BitPayLib\BitPayWordpressHelper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class BitPayInvoiceCreateTest extends TestCase {

	private const WC_ORDER_ID  = 1;
	private const WC_ORDER_KEY = 'wc_order_valid_key';

	/**
	 * execute() runs on template_redirect and bails early unless is_checkout()
	 * is true. In the unit suite no page is loaded, so is_checkout() would return
	 * false and every test here would pass without reaching the code under test.
	 * This filter is WooCommerce's own extension point for that.
	 */
	protected function setUp(): void {
		parent::setUp();
		add_filter( 'woocommerce_is_checkout', '__return_true' );
	}

	protected function tearDown(): void {
		remove_filter( 'woocommerce_is_checkout', '__return_true' );
		parent::tearDown();
	}

	/**
	 * A hit on the order-received page of a cancelled order must not create a new
	 * BitPay invoice. Reported in issue #153: crawlers and mail scanners re-fetch
	 * that URL for months, and every hit used to mint another invoice.
	 *
	 * @test
	 */
	public function it_should_not_create_an_invoice_for_a_cancelled_order(): void {
		$this->assert_no_invoice_is_created_for_status( 'cancelled' );
	}

	/**
	 * Same guard, for the case the original report was about: an order that was
	 * already paid and shipped.
	 *
	 * @test
	 */
	public function it_should_not_create_an_invoice_for_a_completed_order(): void {
		$this->assert_no_invoice_is_created_for_status( 'completed' );
	}

	/**
	 * The order-received URL WooCommerce gives the buyer carries the order key.
	 * A request with a wrong key must not create an invoice for an unpaid order.
	 *
	 * @test
	 */
	public function it_should_not_create_an_invoice_when_the_order_key_is_wrong(): void {
		$wc_order = $this->get_wc_order( 'pending', true );
		$wc_order->expects( self::once() )->method( 'key_is_valid' )
			->with( 'wc_order_wrong_key' )
			->willReturn( false );

		$this->assert_no_invoice_is_created( $wc_order, 'wc_order_wrong_key' );
	}

	/**
	 * Same check when the request has no key at all, for example
	 * /checkout/order-received/{order_id}/ with nothing after it.
	 *
	 * @test
	 */
	public function it_should_not_create_an_invoice_when_the_order_key_is_missing(): void {
		$wc_order = $this->get_wc_order( 'pending', true );
		$wc_order->expects( self::never() )->method( 'key_is_valid' );

		$this->assert_no_invoice_is_created( $wc_order, null );
	}

	private function assert_no_invoice_is_created_for_status( string $order_status ): void {
		$wc_order = $this->get_wc_order( $order_status, false );
		$wc_order->method( 'key_is_valid' )->with( self::WC_ORDER_KEY )->willReturn( true );

		// the needs_payment() guard logs before returning. Asserting the log proves
		// execute() reached that guard instead of bailing out at an earlier return.
		$this->assert_no_invoice_is_created( $wc_order, self::WC_ORDER_KEY, self::once() );
	}

	/**
	 * @return \WC_Order&MockObject
	 */
	private function get_wc_order( string $order_status, bool $needs_payment ): \WC_Order {
		$wc_order = $this->getMockBuilder( \WC_Order::class )->getMock();
		$wc_order->method( 'get_id' )->willReturn( self::WC_ORDER_ID );
		$wc_order->method( 'get_payment_method' )->willReturn( 'bitpay_checkout_gateway' );
		$wc_order->method( 'get_status' )->willReturn( $order_status );
		$wc_order->method( 'needs_payment' )->willReturn( $needs_payment );

		return $wc_order;
	}

	private function assert_no_invoice_is_created(
		\WC_Order $wc_order,
		?string $order_key_parameter,
		?\PHPUnit\Framework\MockObject\Rule\InvocationOrder $expected_log_calls = null
	): void {
		// given
		$wordpress_helper = $this->getMockBuilder( BitPayWordpressHelper::class )->getMock();
		$wordpress_helper->method( 'get_query_var' )->with( 'order-received' )
			->willReturn( (string) self::WC_ORDER_ID );
		$wordpress_helper->method( 'get_url_parameter' )->willReturnMap(
			array(
				array( 'redirect', null ),
				array( 'key', $order_key_parameter ),
			)
		);
		$wordpress_helper->method( 'get_order' )->willReturn( $wc_order );

		$invoice_factory = $this->getMockBuilder( BitPayInvoiceFactory::class )
			->disableOriginalConstructor()->getMock();
		$client_factory  = $this->getMockBuilder( BitPayClientFactory::class )
			->disableOriginalConstructor()->getMock();
		$transactions    = $this->getMockBuilder( BitPayCheckoutTransactions::class )
			->disableOriginalConstructor()->getMock();
		$logger          = $this->getMockBuilder( BitPayLogger::class )
			->disableOriginalConstructor()->getMock();

		// then
		// create_by_wc_order() is the first call past the guards, so it is the tripwire.
		// It throws instead of carrying an expects( never() ) on purpose: execute() ends
		// with a blanket catch ( \Exception ), which swallows PHPUnit's own expectation
		// failure and reports an unrelated redirect error instead. \Error is not an
		// \Exception, so it escapes both catch blocks and the failure names the problem.
		$invoice_factory->method( 'create_by_wc_order' )->willThrowException(
			new \Error( 'create_by_wc_order() must not be reached in this test.' )
		);

		$client_factory->expects( self::never() )->method( 'create' );
		$transactions->expects( self::never() )->method( 'create_transaction' );
		$logger->expects( $expected_log_calls ?? self::never() )->method( 'execute' );

		// when
		$tested_class = new BitPayInvoiceCreate(
			$client_factory,
			$invoice_factory,
			$transactions,
			$wordpress_helper,
			$logger
		);

		$tested_class->execute();
	}
}
