<?php

declare(strict_types=1);

namespace Unit\BitPayLib;

use BitPayLib\BitPayCheckoutTransactions;
use BitPayLib\BitPayClientFactory;
use BitPayLib\BitPayInvoiceCreate;
use BitPayLib\BitPayInvoiceFactory;
use BitPayLib\BitPayLogger;
use BitPayLib\BitPayWordpressHelper;
use PHPUnit\Framework\TestCase;

class BitPayInvoiceCreateTest extends TestCase {

	private const WC_ORDER_ID = '1';

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

	private function assert_no_invoice_is_created_for_status( string $order_status ): void {
		// given
		$wc_order = $this->getMockBuilder( \WC_Order::class )->getMock();
		$wc_order->method( 'get_id' )->willReturn( self::WC_ORDER_ID );
		$wc_order->method( 'get_payment_method' )->willReturn( 'bitpay_checkout_gateway' );
		$wc_order->method( 'get_status' )->willReturn( $order_status );
		$wc_order->method( 'needs_payment' )->willReturn( false );

		$wordpress_helper = $this->getMockBuilder( BitPayWordpressHelper::class )->getMock();
		$wordpress_helper->method( 'get_query_var' )->with( 'order-received' )
			->willReturn( self::WC_ORDER_ID );
		$wordpress_helper->method( 'get_url_parameter' )->with( 'redirect' )
			->willReturn( null );
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
		// create_by_wc_order() is the first call past the guard, so it is the tripwire.
		// It throws instead of carrying an expects( never() ) on purpose: execute() ends
		// with a blanket catch ( \Exception ), which swallows PHPUnit's own expectation
		// failure and reports an unrelated redirect error instead. \Error is not an
		// \Exception, so it escapes both catch blocks and the failure names the problem.
		$invoice_factory->method( 'create_by_wc_order' )->willThrowException(
			new \Error(
				'create_by_wc_order() must not be reached for an order that does not need payment.'
			)
		);

		$client_factory->expects( self::never() )->method( 'create' );
		$transactions->expects( self::never() )->method( 'create_transaction' );

		// the guard logs before returning. Asserting the log proves execute()
		// reached the guard instead of bailing out at an earlier return.
		$logger->expects( self::once() )->method( 'execute' );

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
