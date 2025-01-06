<?php

declare(strict_types=1);

namespace Unit\BitPayLib;

use BitPayLib\BitPayCheckoutTransactions;
use BitPayLib\BitPayClientFactory;
use BitPayLib\BitPayCreateOrder;
use BitPayLib\BitPayIpnProcess;
use BitPayLib\BitPayLogger;
use BitPayLib\BitPayPaymentSettings;
use BitPayLib\BitPayWebhookVerifier;
use BitPayLib\BitPayWordpressHelper;
use PHPUnit\Framework\TestCase;


class BitPayIpnProcessTest extends TestCase {
	private const WC_ORDER_ID       = 'someWcId';
	private const BITPAY_INVOICE_ID = 'someId';

	/**
	 * @test
	 */
	public function it_should_do_not_allow_to_process_non_bitpay_orders() {
		// given
		$wordpress_helper             = $this->get_wordpress_helper();
		$request                      = $this->getMockBuilder( \WP_REST_Request::class )->getMock();
		$bitpay_invoice               = $this->getMockBuilder( \BitPaySDK\Model\Invoice\Invoice::class )->getMock();
		$bitpay_client                = $this->getMockBuilder( \BitPaySDK\Client::class )
			->disableOriginalConstructor()
			->getMock();
		$logger                       = $this->get_bitpay_logger();
		$bitpay_client_factory        = $this->getMockBuilder( BitPayClientFactory::class )
			->disableOriginalConstructor()->getMock();
		$wc_order                     = $this->getMockBuilder( \WC_Order::class )->getMock();
		$bitpay_checkout_transactions = $this->get_checkout_transactions();

		$wc_order->method( 'get_payment_method' )->willReturn( 'invalidMethod' );
		$wc_order->method( 'get_id' )->willReturn( self::WC_ORDER_ID );
		$request->method( 'get_body' )
			->willReturn(
				file_get_contents( __DIR__ . '/json/bitpay_paid_ipn_webhook.json' )
			);
		$request->expects(self::once())->method('get_header')->with('x-signature')->willReturn('x-signature-header-value');
		$bitpay_invoice->method( 'getOrderId' )->willReturn( self::BITPAY_INVOICE_ID );
		$bitpay_client_factory->method( 'create' )->willReturn( $bitpay_client );
		$bitpay_client->method( 'getInvoice' )->with( self::BITPAY_INVOICE_ID, \BitPaySDK\Model\Facade::POS, false )
			->willReturn( $bitpay_invoice );
		$wordpress_helper->method( 'get_order' )
			->with( self::BITPAY_INVOICE_ID )
			->willReturn( $wc_order );

		$testedClass = $this->getTestedClass(
			$wordpress_helper,
			$bitpay_client_factory,
			$bitpay_checkout_transactions,
			$logger,
			$this->get_bitpay_webhook_verifier(),
			$this->get_bitpay_payment_settings()
		);

		// then
		$logger->expects( self::exactly( 2 ) )->method( 'execute' )->with(
			self::callback(
				function ( $msg ) {
					if ( is_array( $msg ) ) {
						return true;
					}

					return $msg === 'Order id = someWcId, BitPay invoice id = someId. Current payment method = invalidMethod';
				},
			)
		);

		$bitpay_checkout_transactions->expects( self::never() )->method( 'update_transaction_status' );

		// when
		$testedClass->execute( $request );
	}

	/**
	 * @test
	 */
	public function it_should_do_not_allow_to_process_with_wrong_transaction() {
		// given
		$wordpress_helper      = $this->get_wordpress_helper();
		$request               = $this->getMockBuilder( \WP_REST_Request::class )->getMock();
		$transactions          = $this->get_checkout_transactions();
		$bitpay_invoice        = $this->getMockBuilder( \BitPaySDK\Model\Invoice\Invoice::class )->getMock();
		$bitpay_client         = $this->getMockBuilder( \BitPaySDK\Client::class )
			->disableOriginalConstructor()
			->getMock();
		$logger                = $this->get_bitpay_logger();
		$bitpay_client_factory = $this->getMockBuilder( BitPayClientFactory::class )
			->disableOriginalConstructor()->getMock();
		$wc_order              = $this->get_wc_order();

		$transactions->method( 'count_transaction_id' )->willReturn( 0 );
		$request->method( 'get_body' )
			->willReturn(
				file_get_contents( __DIR__ . '/json/bitpay_paid_ipn_webhook.json' )
			);
		$request->expects(self::once())->method('get_header')->with('x-signature')->willReturn('x-signature-header-value');
		$bitpay_invoice->method( 'getOrderId' )->willReturn( self::BITPAY_INVOICE_ID );
		$bitpay_client_factory->method( 'create' )->willReturn( $bitpay_client );
		$bitpay_client->method( 'getInvoice' )->with( self::BITPAY_INVOICE_ID, \BitPaySDK\Model\Facade::POS, false )
			->willReturn( $bitpay_invoice );
		$wordpress_helper->method( 'get_order' )
			->with( self::BITPAY_INVOICE_ID )
			->willReturn( $wc_order );

		$testedClass = $this->getTestedClass(
			$wordpress_helper,
			$bitpay_client_factory,
			$transactions,
			$logger,
			$this->get_bitpay_webhook_verifier(),
			$this->get_bitpay_payment_settings()
		);

		$logger->expects( self::exactly( 2 ) )->method( 'execute' )->with(
			self::callback(
				function ( $msg ) {
					if ( is_array( $msg ) ) {
						return true;
					}

					return $msg === 'Order id = someWcId, BitPay invoice id = someId. Wrong transaction id someId';
				},
			)
		);
		$transactions->expects( self::never() )->method( 'update_transaction_status' );

		// when
		$testedClass->execute( $request );
	}

	/**
	 * @test
	 */
	public function it_should_process_ipn_request_without_verification_when_order_has_no_saved_token() {
		// given
		$webhook_verifier = $this->get_bitpay_webhook_verifier();
		$webhook_verifier->expects(self::never())->method('verify');
		$wordpress_helper = $this->get_wordpress_helper();
		$request = $this->getMockBuilder(\WP_REST_Request::class)->getMock();
		$transactions = $this->get_checkout_transactions();
		$bitpay_invoice = $this->getMockBuilder(\BitPaySDK\Model\Invoice\Invoice::class)->getMock();
		$bitpay_client = $this->getMockBuilder(\BitPaySDK\Client::class)
			->disableOriginalConstructor()
			->getMock();
		$logger = $this->get_bitpay_logger();
		$bitpay_client_factory = $this->getMockBuilder(BitPayClientFactory::class)
			->disableOriginalConstructor()->getMock();
		$wc_order = $this->get_wc_order();

		$transactions->method('count_transaction_id')->willReturn(1);
		$bitpay_invoice->method('getStatus')->willReturn('paid');
		$bitpay_invoice->method('getId')->willReturn(self::BITPAY_INVOICE_ID);
		$request->method('get_body')
			->willReturn(
				file_get_contents(__DIR__ . '/json/bitpay_paid_ipn_webhook.json')
			);
		$request->expects(self::once())->method('get_header')->with('x-signature')->willReturn('x-signature-header-value');

		$bitpay_invoice->method('getOrderId')->willReturn( self::BITPAY_INVOICE_ID );
		$bitpay_client_factory->method('create')->willReturn( $bitpay_client );
		$bitpay_client->method('getInvoice')->with( self::BITPAY_INVOICE_ID, \BitPaySDK\Model\Facade::POS, false )
			->willReturn($bitpay_invoice);
		$wordpress_helper->method('get_order')
			->with(self::BITPAY_INVOICE_ID)
			->willReturn($wc_order);

		$testedClass = $this->getTestedClass(
			$wordpress_helper,
			$bitpay_client_factory,
			$transactions,
			$logger,
			$webhook_verifier,
			$this->get_bitpay_payment_settings()
		);

		// then
		$wc_order
			->expects(self::once())
			->method('add_order_note')
			->with('BitPay Invoice ID: <a target = "_blank" href = "//test.bitpay.com/dashboard/payments/someId">someId</a> is paid and awaiting confirmation.');
		$transactions->expects(self::once())->method('update_transaction_status');

		// when
		$testedClass->execute($request);
	}

	/**
	 * @test
	 */
	public function it_should_not_process_failed_verification_ipn_request() {
		// given
		$webhook_verifier = $this->get_bitpay_webhook_verifier();
		$webhook_verifier->expects(self::once())->method('verify')->willReturn(false);
		$wordpress_helper = $this->get_wordpress_helper();
		$request = $this->getMockBuilder(\WP_REST_Request::class)->getMock();
		$transactions = $this->get_checkout_transactions();
		$bitpay_invoice = $this->getMockBuilder(\BitPaySDK\Model\Invoice\Invoice::class)->getMock();
		$bitpay_client = $this->getMockBuilder(\BitPaySDK\Client::class)
			->disableOriginalConstructor()
			->getMock();
		$logger = $this->get_bitpay_logger();
		$bitpay_client_factory = $this->getMockBuilder(BitPayClientFactory::class)
			->disableOriginalConstructor()->getMock();
		$wc_order = $this->get_wc_order();
		$bitpay_payment_settings = $this->get_bitpay_payment_settings();

		$transactions->method('count_transaction_id')->willReturn(1);
		$bitpay_invoice->method('getStatus')->willReturn('paid');
		$bitpay_invoice->method('getId')->willReturn(self::BITPAY_INVOICE_ID);
		$request->method('get_body')
			->willReturn(
				file_get_contents(__DIR__ . '/json/bitpay_paid_ipn_webhook.json')
			);
		$request->expects(self::once())->method('get_header')->with('x-signature')->willReturn('x-signature-header-value');
		$bitpay_invoice->method('getOrderId')->willReturn( self::BITPAY_INVOICE_ID );
		$bitpay_client_factory->method('create')->willReturn( $bitpay_client );
		$bitpay_client->method('getInvoice')->with( self::BITPAY_INVOICE_ID, \BitPaySDK\Model\Facade::POS, false )
			->willReturn($bitpay_invoice);
		$wordpress_helper->method('get_order')
			->with(self::BITPAY_INVOICE_ID)
			->willReturn($wc_order);
		$wc_order->method('get_meta')->with(BitPayCreateOrder::BITPAY_TOKEN_ORDER_METADATA_KEY)->willReturn('secret_token');
		$bitpay_payment_settings->expects(self::once())->method('get_bitpay_token')->willReturn('different_token');

		$testedClass = $this->getTestedClass(
			$wordpress_helper,
			$bitpay_client_factory,
			$transactions,
			$logger,
			$webhook_verifier,
			$bitpay_payment_settings
		);

		// then
		$wc_order
			->expects(self::never())
			->method('add_order_note');
		$transactions->expects(self::never())->method('update_transaction_status');

		// when
		$testedClass->execute($request);
	}

	public function it_should_verify_order_with_saved_token_ipn_request_and_process_correctly_verified_ipn() {
		// given
		$wordpress_helper      = $this->get_wordpress_helper();
		$request               = $this->getMockBuilder( \WP_REST_Request::class )->getMock();
		$transactions          = $this->get_checkout_transactions();
		$webhook_verifier = $this->get_bitpay_webhook_verifier();
		$webhook_verifier->method('verify')->willReturn(true);
		$bitpay_invoice        = $this->getMockBuilder( \BitPaySDK\Model\Invoice\Invoice::class )->getMock();
		$bitpay_client         = $this->getMockBuilder( \BitPaySDK\Client::class )
			->disableOriginalConstructor()
			->getMock();
		$logger                = $this->get_bitpay_logger();
		$bitpay_client_factory = $this->getMockBuilder( BitPayClientFactory::class )
			->disableOriginalConstructor()->getMock();
		$wc_order              = $this->get_wc_order();
		$wc_order->method('get_meta')->with(BitPayCreateOrder::BITPAY_TOKEN_ORDER_METADATA_KEY)->willReturn('secret_token');

		$transactions->method( 'count_transaction_id' )->willReturn( 1 );
		$bitpay_invoice->method( 'getStatus' )->willReturn( 'paid' );
		$bitpay_invoice->method( 'getId' )->willReturn( self::BITPAY_INVOICE_ID );
		$request->method( 'get_body' )
			->willReturn(
				file_get_contents( __DIR__ . '/json/bitpay_paid_ipn_webhook.json' )
			);
		$request->expects(self::once())->method('get_header')->with('x-signature')->willReturn('x-signature-header-value');
		$bitpay_invoice->method( 'getOrderId' )->willReturn( self::BITPAY_INVOICE_ID );
		$bitpay_client_factory->method( 'create' )->willReturn( $bitpay_client );
		$bitpay_client->method( 'getInvoice' )->with( self::BITPAY_INVOICE_ID, \BitPaySDK\Model\Facade::POS, false )
			->willReturn( $bitpay_invoice );
		$wordpress_helper->method( 'get_order' )
			->with( self::BITPAY_INVOICE_ID )
			->willReturn( $wc_order );

		$testedClass = $this->getTestedClass(
			$wordpress_helper,
			$bitpay_client_factory,
			$transactions,
			$logger,
			$webhook_verifier,
			$this->get_bitpay_payment_settings()
		);

		// then
		$wc_order
			->expects( self::once() )
			->method( 'add_order_note' )
			->with( 'BitPay Invoice ID: <a target = "_blank" href = "//test.bitpay.com/dashboard/payments/someId">someId</a> is paid and awaiting confirmation.' );
		$transactions->expects( self::once() )->method( 'update_transaction_status' );

		// when
		$testedClass->execute( $request );
	}

	/**
	 * @test
	 */
	public function it_should_confirm_order(): void {
		// given
		$wordpress_helper      = $this->get_wordpress_helper();
		$request               = $this->getMockBuilder( \WP_REST_Request::class )->getMock();
		$transactions          = $this->get_checkout_transactions();
		$bitpay_invoice        = $this->getMockBuilder( \BitPaySDK\Model\Invoice\Invoice::class )->getMock();
		$bitpay_client         = $this->getMockBuilder( \BitPaySDK\Client::class )
			->disableOriginalConstructor()
			->getMock();
		$logger                = $this->get_bitpay_logger();
		$bitpay_client_factory = $this->getMockBuilder( BitPayClientFactory::class )
			->disableOriginalConstructor()->getMock();
		$wc_order              = $this->get_wc_order();

		$transactions->method( 'count_transaction_id' )->willReturn( 1 );
		$bitpay_invoice->method( 'getStatus' )->willReturn( 'confirmed' );
		$bitpay_invoice->method( 'getId' )->willReturn( self::BITPAY_INVOICE_ID );
		$request->method( 'get_body' )
			->willReturn(
				file_get_contents( __DIR__ . '/json/bitpay_confirmed_ipn_webhook.json' )
			);
		$request->expects(self::once())->method('get_header')->with('x-signature')->willReturn('x-signature-header-value');
		$bitpay_invoice->method( 'getOrderId' )->willReturn( self::BITPAY_INVOICE_ID );
		$bitpay_client_factory->method( 'create' )->willReturn( $bitpay_client );
		$bitpay_client->method( 'getInvoice' )->with( self::BITPAY_INVOICE_ID, \BitPaySDK\Model\Facade::POS, false )
			->willReturn( $bitpay_invoice );
		$wordpress_helper->expects( self::once() )->method( 'get_order' )
			->with( self::BITPAY_INVOICE_ID )
			->willReturn( $wc_order );

		$testedClass = $this->getTestedClass(
			$wordpress_helper,
			$bitpay_client_factory,
			$transactions,
			$logger,
			$this->get_bitpay_webhook_verifier(),
			$this->get_bitpay_payment_settings()
		);

		$wc_order
			->expects( self::exactly( 2 ) )
			->method( 'add_order_note' )
			->withConsecutive(
				array( 'BitPay Invoice ID: <a target = "_blank" href = "//test.bitpay.com/dashboard/payments/someId">someId</a> has changed to Completed.' ),
				array( 'Payment Completed' ),
			);
		$wc_order->expects( self::once() )->method( 'payment_complete' );
		$transactions->expects( self::once() )->method( 'update_transaction_status' );

		// when
		$testedClass->execute( $request );
	}

	/**
	 * @test
	 */
	public function it_should_complete_order(): void {
		// given
		$wordpress_helper      = $this->get_wordpress_helper();
		$request               = $this->getMockBuilder( \WP_REST_Request::class )->getMock();
		$transactions          = $this->get_checkout_transactions();
		$bitpay_invoice        = $this->getMockBuilder( \BitPaySDK\Model\Invoice\Invoice::class )->getMock();
		$bitpay_client         = $this->getMockBuilder( \BitPaySDK\Client::class )
			->disableOriginalConstructor()
			->getMock();
		$logger                = $this->get_bitpay_logger();
		$bitpay_client_factory = $this->getMockBuilder( BitPayClientFactory::class )
			->disableOriginalConstructor()->getMock();
		$wc_order              = $this->get_wc_order();

		$transactions->method( 'count_transaction_id' )->willReturn( 1 );
		$bitpay_invoice->method( 'getStatus' )->willReturn( 'complete' );
		$bitpay_invoice->method( 'getId' )->willReturn( self::BITPAY_INVOICE_ID );
		$wc_order->method( 'get_status' )->willReturn( 'wc-pending' );
		$request->method( 'get_body' )
			->willReturn(
				file_get_contents( __DIR__ . '/json/bitpay_completed_ipn_webhook.json' )
			);
		$request->expects(self::once())->method('get_header')->with('x-signature')->willReturn('x-signature-header-value');
		$bitpay_invoice->method( 'getOrderId' )->willReturn( self::BITPAY_INVOICE_ID );
		$bitpay_client_factory->method( 'create' )->willReturn( $bitpay_client );
		$bitpay_client->method( 'getInvoice' )->with( self::BITPAY_INVOICE_ID, \BitPaySDK\Model\Facade::POS, false )
			->willReturn( $bitpay_invoice );
		$wordpress_helper->expects( self::once() )->method( 'get_order' )
			->with( self::BITPAY_INVOICE_ID )
			->willReturn( $wc_order );

		$testedClass = $this->getTestedClass(
			$wordpress_helper,
			$bitpay_client_factory,
			$transactions,
			$logger,
			$this->get_bitpay_webhook_verifier(),
			$this->get_bitpay_payment_settings()
		);

		$wc_order
			->expects( self::exactly( 2 ) )
			->method( 'add_order_note' )
			->withConsecutive(
				array( 'BitPay Invoice ID: <a target = "_blank" href = "//test.bitpay.com/dashboard/payments/someId">someId</a> has changed to Completed.' ),
				array( 'Payment Completed' ),
			);
		$wc_order->expects( self::once() )->method( 'payment_complete' );
		$transactions->expects( self::once() )->method( 'update_transaction_status' );

		// when
		$testedClass->execute( $request );
	}

	/**
	 * @test
	 */
	public function it_should_decline_order(): void {
		// given
		$wordpress_helper      = $this->get_wordpress_helper();
		$request               = $this->getMockBuilder( \WP_REST_Request::class )->getMock();
		$transactions          = $this->get_checkout_transactions();
		$bitpay_invoice        = $this->getMockBuilder( \BitPaySDK\Model\Invoice\Invoice::class )->getMock();
		$bitpay_client         = $this->getMockBuilder( \BitPaySDK\Client::class )
			->disableOriginalConstructor()
			->getMock();
		$logger                = $this->get_bitpay_logger();
		$bitpay_client_factory = $this->getMockBuilder( BitPayClientFactory::class )
			->disableOriginalConstructor()->getMock();
		$wc_order              = $this->get_wc_order();

		$transactions->method( 'count_transaction_id' )->willReturn( 1 );
		$bitpay_invoice->method( 'getStatus' )->willReturn( 'declined' );
		$bitpay_invoice->method( 'getId' )->willReturn( self::BITPAY_INVOICE_ID );
		$request->method( 'get_body' )
			->willReturn(
				file_get_contents( __DIR__ . '/json/bitpay_declined_ipn_webhook.json' )
			);
		$request->expects(self::once())->method('get_header')->with('x-signature')->willReturn('x-signature-header-value');
		$bitpay_invoice->method( 'getOrderId' )->willReturn( self::BITPAY_INVOICE_ID );
		$bitpay_client_factory->method( 'create' )->willReturn( $bitpay_client );
		$bitpay_client->method( 'getInvoice' )->with( self::BITPAY_INVOICE_ID, \BitPaySDK\Model\Facade::POS, false )
			->willReturn( $bitpay_invoice );
		$wordpress_helper->expects( self::once() )->method( 'get_order' )
			->with( self::BITPAY_INVOICE_ID )
			->willReturn( $wc_order );

		$testedClass = $this->getTestedClass(
			$wordpress_helper,
			$bitpay_client_factory,
			$transactions,
			$logger,
			$this->get_bitpay_webhook_verifier(),
			$this->get_bitpay_payment_settings()
		);

		$wc_order
			->expects( self::once() )
			->method( 'add_order_note' )
			->with( 'BitPay Invoice ID: <a target = "_blank" href = "//test.bitpay.com/dashboard/payments/someId">someId</a> has been declined.' );
		$wc_order->expects( self::once() )->method( 'update_status' )->with( 'failed' );
		$transactions->expects( self::once() )->method( 'update_transaction_status' );

		// when
		$testedClass->execute( $request );
	}

	/**
	 * @test
	 */
	public function it_should_fail_order(): void {
		// given
		$wordpress_helper      = $this->get_wordpress_helper();
		$request               = $this->getMockBuilder( \WP_REST_Request::class )->getMock();
		$transactions          = $this->get_checkout_transactions();
		$bitpay_invoice        = $this->getMockBuilder( \BitPaySDK\Model\Invoice\Invoice::class )->getMock();
		$bitpay_client         = $this->getMockBuilder( \BitPaySDK\Client::class )
			->disableOriginalConstructor()
			->getMock();
		$logger                = $this->get_bitpay_logger();
		$bitpay_client_factory = $this->getMockBuilder( BitPayClientFactory::class )
			->disableOriginalConstructor()->getMock();
		$wc_order              = $this->get_wc_order();

		$transactions->method( 'count_transaction_id' )->willReturn( 1 );
		$bitpay_invoice->method( 'getStatus' )->willReturn( 'invalid' );
		$bitpay_invoice->method( 'getId' )->willReturn( self::BITPAY_INVOICE_ID );
		$request->method( 'get_body' )
			->willReturn(
				file_get_contents( __DIR__ . '/json/bitpay_invalid_ipn_webhook.json' )
			);
		$request->expects(self::once())->method('get_header')->with('x-signature')->willReturn('x-signature-header-value');
		$bitpay_invoice->method( 'getOrderId' )->willReturn( self::BITPAY_INVOICE_ID );
		$bitpay_client_factory->method( 'create' )->willReturn( $bitpay_client );
		$bitpay_client->method( 'getInvoice' )->with( self::BITPAY_INVOICE_ID, \BitPaySDK\Model\Facade::POS, false )
			->willReturn( $bitpay_invoice );
		$wordpress_helper->expects( self::once() )->method( 'get_order' )
			->with( self::BITPAY_INVOICE_ID )
			->willReturn( $wc_order );

		$testedClass = $this->getTestedClass(
			$wordpress_helper,
			$bitpay_client_factory,
			$transactions,
			$logger,
			$this->get_bitpay_webhook_verifier(),
			$this->get_bitpay_payment_settings()
		);

		$wc_order
			->expects( self::once() )
			->method( 'add_order_note' )
			->with( 'BitPay Invoice ID: <a target = "_blank" href = "//test.bitpay.com/dashboard/payments/someId">someId</a> has become invalid because of network congestion. Order will automatically update when the status changes.' );
		$wc_order->expects( self::once() )->method( 'update_status' )->with( 'failed' );
		$transactions->expects( self::once() )->method( 'update_transaction_status' );

		// when
		$testedClass->execute( $request );
	}

	/**
	 * @test
	 */
	public function it_should_expire_order(): void {
		// given
		$wordpress_helper      = $this->get_wordpress_helper();
		$request               = $this->getMockBuilder( \WP_REST_Request::class )->getMock();
		$transactions          = $this->get_checkout_transactions();
		$bitpay_invoice        = $this->getMockBuilder( \BitPaySDK\Model\Invoice\Invoice::class )->getMock();
		$bitpay_client         = $this->getMockBuilder( \BitPaySDK\Client::class )
			->disableOriginalConstructor()
			->getMock();
		$logger                = $this->get_bitpay_logger();
		$bitpay_client_factory = $this->getMockBuilder( BitPayClientFactory::class )
			->disableOriginalConstructor()->getMock();
		$wc_order              = $this->get_wc_order();

		$transactions->method( 'count_transaction_id' )->willReturn( 1 );
		$bitpay_invoice->method( 'getStatus' )->willReturn( 'expired' );
		$bitpay_invoice->method( 'getId' )->willReturn( self::BITPAY_INVOICE_ID );
		$request->method( 'get_body' )
			->willReturn(
				file_get_contents( __DIR__ . '/json/bitpay_expired_ipn_webhook.json' )
			);
		$request->expects(self::once())->method('get_header')->with('x-signature')->willReturn('x-signature-header-value');
		$bitpay_invoice->method( 'getOrderId' )->willReturn( self::BITPAY_INVOICE_ID );
		$bitpay_client_factory->method( 'create' )->willReturn( $bitpay_client );
		$bitpay_client->method( 'getInvoice' )->with( self::BITPAY_INVOICE_ID, \BitPaySDK\Model\Facade::POS, false )
			->willReturn( $bitpay_invoice );
		$wordpress_helper->expects( self::once() )->method( 'get_order' )
			->with( self::BITPAY_INVOICE_ID )
			->willReturn( $wc_order );

		$testedClass = $this->getTestedClass(
			$wordpress_helper,
			$bitpay_client_factory,
			$transactions,
			$logger,
			$this->get_bitpay_webhook_verifier(),
			$this->get_bitpay_payment_settings()
		);

		$wc_order
			->expects( self::once() )
			->method( 'add_order_note' )
			->with( 'BitPay Invoice ID: <a target = "_blank" href = "//test.bitpay.com/dashboard/payments/someId">someId</a> has expired.' );
		$wc_order->expects( self::once() )->method( 'update_status' )->with( 'cancelled' );
		$transactions->expects( self::once() )->method( 'update_transaction_status' );

		// when
		$testedClass->execute( $request );
	}

	/**
	 * @test
	 */
	public function it_should_refund_order(): void {
		$wordpress_helper      = $this->get_wordpress_helper();
		$request               = $this->getMockBuilder( \WP_REST_Request::class )->getMock();
		$transactions          = $this->get_checkout_transactions();
		$bitpay_invoice        = $this->getMockBuilder( \BitPaySDK\Model\Invoice\Invoice::class )->getMock();
		$bitpay_client         = $this->getMockBuilder( \BitPaySDK\Client::class )
			->disableOriginalConstructor()
			->getMock();
		$logger                = $this->get_bitpay_logger();
		$bitpay_client_factory = $this->getMockBuilder( BitPayClientFactory::class )
			->disableOriginalConstructor()->getMock();
		$wc_order              = $this->get_wc_order();

		$transactions->method( 'count_transaction_id' )->willReturn( 1 );
		$bitpay_invoice->method( 'getStatus' )->willReturn( 'invalid' );
		$bitpay_invoice->method( 'getId' )->willReturn( self::BITPAY_INVOICE_ID );
		$request->method( 'get_body' )
			->willReturn(
				file_get_contents( __DIR__ . '/json/bitpay_refunded_ipn_webhook.json' )
			);
		$request->expects(self::once())->method('get_header')->with('x-signature')->willReturn('x-signature-header-value');
		$bitpay_invoice->method( 'getOrderId' )->willReturn( self::BITPAY_INVOICE_ID );
		$bitpay_client_factory->method( 'create' )->willReturn( $bitpay_client );
		$bitpay_client->method( 'getInvoice' )->with( self::BITPAY_INVOICE_ID, \BitPaySDK\Model\Facade::POS, false )
			->willReturn( $bitpay_invoice );
		$wordpress_helper->expects( self::once() )->method( 'get_order' )
			->with( self::BITPAY_INVOICE_ID )
			->willReturn( $wc_order );

		$testedClass = $this->getTestedClass(
			$wordpress_helper,
			$bitpay_client_factory,
			$transactions,
			$logger,
			$this->get_bitpay_webhook_verifier(),
			$this->get_bitpay_payment_settings()
		);

		$wc_order
			->expects( self::once() )
			->method( 'add_order_note' )
			->with( 'BitPay Invoice ID: <a target = "_blank" href = "//test.bitpay.com/dashboard/payments/someId">someId</a> has been refunded.' );
		$wc_order->expects( self::once() )->method( 'update_status' )->with( 'refunded' );
		$transactions->expects( self::once() )->method( 'update_transaction_status' );

		// when
		$testedClass->execute( $request );
	}

	/**
	 * @test
	 */
	public function it_should_complete_for_wcorder_wccomplete_status_and_wccompleted_for_complete_action_in_admin() {
		// given
		$wordpress_helper      = $this->get_wordpress_helper();
		$request               = $this->getMockBuilder( \WP_REST_Request::class )->getMock();
		$transactions          = $this->get_checkout_transactions();
		$bitpay_invoice        = $this->getMockBuilder( \BitPaySDK\Model\Invoice\Invoice::class )->getMock();
		$bitpay_client         = $this->getMockBuilder( \BitPaySDK\Client::class )
			->disableOriginalConstructor()
			->getMock();
		$logger                = $this->get_bitpay_logger();
		$bitpay_client_factory = $this->getMockBuilder( BitPayClientFactory::class )
			->disableOriginalConstructor()->getMock();
		$wc_order              = $this->get_wc_order();

		$transactions->method( 'count_transaction_id' )->willReturn( 1 );
		$bitpay_invoice->method( 'getStatus' )->willReturn( 'complete' );
		$bitpay_invoice->method( 'getId' )->willReturn( self::BITPAY_INVOICE_ID );
		$wc_order->method( 'get_status' )->willReturn( 'wc-completed' );
		$request->method( 'get_body' )
			->willReturn(
				file_get_contents( __DIR__ . '/json/bitpay_completed_ipn_webhook.json' )
			);
		$request->expects(self::once())->method('get_header')->with('x-signature')->willReturn('x-signature-header-value');
		$bitpay_invoice->method( 'getOrderId' )->willReturn( self::BITPAY_INVOICE_ID );
		$bitpay_client_factory->method( 'create' )->willReturn( $bitpay_client );
		$bitpay_client->method( 'getInvoice' )->with( self::BITPAY_INVOICE_ID, \BitPaySDK\Model\Facade::POS, false )
			->willReturn( $bitpay_invoice );
		$wordpress_helper->expects( self::once() )->method( 'get_order' )
			->with( self::BITPAY_INVOICE_ID )
			->willReturn( $wc_order );

		$testedClass = $this->getTestedClass(
			$wordpress_helper,
			$bitpay_client_factory,
			$transactions,
			$logger,
			$this->get_bitpay_webhook_verifier(),
			$this->get_bitpay_payment_settings()
		);

		$wc_order
			->expects( self::exactly( 2 ) )
			->method( 'add_order_note' )
			->withConsecutive(
				array( 'BitPay Invoice ID: <a target = "_blank" href = "//test.bitpay.com/dashboard/payments/someId">someId</a> has changed to Completed.' ),
				array( 'Payment Completed' ),
			);
		$wc_order->expects( self::once() )->method( 'payment_complete' );
		$transactions->expects( self::once() )->method( 'update_transaction_status' );

		// when
		$testedClass->execute( $request );
	}

	/**
	 * @test
	 */
	public function it_should_complete_for_wcorder_wcprocessing_status_and_wccompleted_for_complete_action_in_admin() {
		// given
		$wordpress_helper      = $this->get_wordpress_helper();
		$request               = $this->getMockBuilder( \WP_REST_Request::class )->getMock();
		$transactions          = $this->get_checkout_transactions();
		$bitpay_invoice        = $this->getMockBuilder( \BitPaySDK\Model\Invoice\Invoice::class )->getMock();
		$bitpay_client         = $this->getMockBuilder( \BitPaySDK\Client::class )
			->disableOriginalConstructor()
			->getMock();
		$logger                = $this->get_bitpay_logger();
		$bitpay_client_factory = $this->getMockBuilder( BitPayClientFactory::class )
			->disableOriginalConstructor()->getMock();

		$transactions->method( 'count_transaction_id' )->willReturn( 1 );
		$bitpay_invoice->method( 'getStatus' )->willReturn( 'complete' );
		$bitpay_invoice->method( 'getId' )->willReturn( self::BITPAY_INVOICE_ID );
		$wc_order = $this->get_wc_order();
		$wc_order->method( 'get_status' )->willReturn( 'wc-processing' );
		$request->method( 'get_body' )
			->willReturn(
				file_get_contents( __DIR__ . '/json/bitpay_completed_ipn_webhook.json' )
			);
		$request->expects(self::once())->method('get_header')->with('x-signature')->willReturn('x-signature-header-value');
		$bitpay_invoice->method( 'getOrderId' )->willReturn( self::BITPAY_INVOICE_ID );
		$bitpay_client_factory->method( 'create' )->willReturn( $bitpay_client );
		$bitpay_client->method( 'getInvoice' )->with( self::BITPAY_INVOICE_ID, \BitPaySDK\Model\Facade::POS, false )
			->willReturn( $bitpay_invoice );
		$wordpress_helper->expects( self::once() )->method( 'get_order' )
			->with( self::BITPAY_INVOICE_ID )
			->willReturn( $wc_order );

		$testedClass = $this->getTestedClass(
			$wordpress_helper,
			$bitpay_client_factory,
			$transactions,
			$logger,
			$this->get_bitpay_webhook_verifier(),
			$this->get_bitpay_payment_settings()
		);

		$wc_order
			->expects( self::exactly( 2 ) )
			->method( 'add_order_note' )
			->withConsecutive(
				array( 'BitPay Invoice ID: <a target = "_blank" href = "//test.bitpay.com/dashboard/payments/someId">someId</a> has changed to Completed.' ),
				array( 'Payment Completed' ),
			);
		$wc_order->expects( self::once() )->method( 'payment_complete' );
		$transactions->expects( self::once() )->method( 'update_transaction_status' );

		// when
		$testedClass->execute( $request );
	}

	/**
	 * @return (BitPayLogger|\PHPUnit\Framework\MockObject\MockObject)
	 */
	private function get_bitpay_logger() {
		return $this->getMockBuilder( BitPayLogger::class )->disableOriginalConstructor()->getMock();
	}

	/**
	 * @return (BitPayWebhookVerifier|\PHPUnit\Framework\MockObject\MockObject)
	 */
	private function get_bitpay_webhook_verifier() {
		return $this->getMockBuilder(BitPayWebhookVerifier::class)->disableOriginalConstructor()->getMock();
	}

	/**
	 * @return (BitPayPaymentSettings|\PHPUnit\Framework\MockObject\MockObject)
	 */
	private function get_bitpay_payment_settings() {
		return $this->getMockBuilder(BitPayPaymentSettings::class)->disableOriginalConstructor()->getMock();
	}

	/**
	 * @return (BitPayCheckoutTransactions&\PHPUnit\Framework\MockObject\MockObject)
	 */
	private function get_checkout_transactions(): BitPayCheckoutTransactions|\PHPUnit\Framework\MockObject\MockObject {
		return $this->getMockBuilder( BitPayCheckoutTransactions::class )
			->disableOriginalConstructor()->getMock();
	}

	/**
	 * @return \PHPUnit\Framework\MockObject\MockObject|(\WC_Order&\PHPUnit\Framework\MockObject\MockObject)
	 */
	private function get_wc_order(): \PHPUnit\Framework\MockObject\MockObject|\WC_Order {
		$wc_order = $this->getMockBuilder( \WC_Order::class )->getMock();
		$wc_order->method( 'get_payment_method' )->willReturn( 'bitpay_checkout_gateway' );
		$wc_order->method( 'get_id' )->willReturn( self::WC_ORDER_ID );

		return $wc_order;
	}

	/**
	 * @return (BitPayWordpressHelper&\PHPUnit\Framework\MockObject\MockObject)
	 */
	private function get_wordpress_helper(): BitPayWordpressHelper|\PHPUnit\Framework\MockObject\MockObject {
		$helper = $this->getMockBuilder( BitPayWordpressHelper::class )->getMock();
		$helper->method( 'get_bitpay_gateway_option' )
			->willReturnCallback(
				function ( $name ) {
					return match ( $name ) {
						'bitpay_checkout_endpoint' => 'test',
						'bitpay_checkout_order_process_complete_status', 'bitpay_checkout_order_process_confirmed_status' => 'wc-completed',
						'bitpay_checkout_order_process_paid_status' => 'wc-processing',
						'bitpay_checkout_order_expired_status' => '1',
						default => throw new RuntimeException( 'Wrong option' ),
					};
				}
			);

		return $helper;
	}

	private function getTestedClass(
		BitPayWordpressHelper $wordpress_helper,
		BitPayClientFactory $bitpay_client_factory,
		BitPayCheckoutTransactions $bitpay_checkout_transactions,
		BitPayLogger $logger,
		BitPayWebhookVerifier $webhook_verifier,
		BitPayPaymentSettings $payment_settings
	): BitPayIpnProcess {
		return new BitPayIpnProcess( $bitpay_checkout_transactions, $bitpay_client_factory, $wordpress_helper, $logger , $webhook_verifier, $payment_settings);
	}
}
