<?php

declare(strict_types=1);

namespace BitPayLib\Tests\EndToEnd\Support;

use BitPayLib\Tests\EndToEnd\Support\_generated\EndToEndTesterActions;

/**
 * Inherited Methods
 *
 * @method void wantTo($text)
 * @method void wantToTest($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 * @method void pause($vars = [])
 *
 * @SuppressWarnings(PHPMD)
 */
class EndToEndTester extends \Codeception\Actor {

	use EndToEndTesterActions;

	public function getShopPageSlug(): string {
		return $_ENV['WORDPRESS_SHOP_PAGE_SLUG'] ?? 'shop';
	}

	public function getProductPageSlug(): string {
		return $_ENV['WORDPRESS_PRODUCT_PAGE_SLUG'] ?? 'products';
	}

	public function amOnSomeProductPage() {
		$this->amOnAdminProductPage();
		$productName = $this->grabTextFrom( 'td.name a' );

		$this->amOnPage( $this->getProductPageSlug() . '/' . $productName );
	}

	public function addAnyProductToCart() {
		$this->amOnSomeProductPage();
		$priceValue = $this->grabTextFrom( '.woocommerce-Price-amount.amount' );
		$price      = (float) preg_replace( '/[^0-9.]/', '', $priceValue );

		$qtyRequired = max( 1, (int) ( round( 10 / $price, 0, PHP_ROUND_HALF_UP ) ) );
		$this->fillField( 'input[name="quantity"]', $qtyRequired );
		$this->click( '.single_add_to_cart_button' );
	}

	private function amOnAdminProductPage() {
		$this->amOnAdminPage( 'edit.php?post_type=product' );
	}

	public function amOnOrderPage() {
		$this->amOnPage( 'checkout' );
	}

	public function fillOrderInformation() {
		if ( $this->grabValueFrom( '#billing-first_name' ) === '' ) {
			$this->fillField( '#billing-first_name', 'Bruce' );
		}
		if ( $this->grabValueFrom( '#billing-last_name' ) === '' ) {
			$this->fillField( '#billing-last_name', 'Wayne' );
		}
		if ( $this->grabValueFrom( '#billing-address_1' ) === '' ) {
			$this->fillField( '#billing-address_1', 'Dark Knight 1/2' );
		}
		if ( $this->grabValueFrom( '#billing-city' ) === '' ) {
			$this->fillField( '#billing-city', 'Gotham City' );
		}
		if ( $this->grabValueFrom( '#billing-postcode' ) === '' ) {
			$this->fillField( '#billing-postcode', '12345' );
		}
		if ( $this->grabValueFrom( '#billing-phone' ) === '' ) {
			$this->fillField( '#billing-phone', '12345' );
		}
	}

	public function selectBitPayPaymentMethod() {
		$this->click( 'label[for="radio-control-wc-payment-method-options-bitpay_checkout_gateway"]' );
	}

	public function placeOrder() {
		$this->click( '.wc-block-components-checkout-place-order-button' );
	}

	public function resetBitPayAdminSettings(): void {
		$this->fillField( '#woocommerce_bitpay_checkout_gateway_bitpay_close_url', null );
		$this->fillField( '#woocommerce_bitpay_checkout_gateway_bitpay_custom_redirect', null );
	}

	public function amOnBitPayAdminSettings(): void {
		$this->amOnPage( '/wp-admin/admin.php?page=wc-settings&tab=checkout&section=bitpay_checkout_gateway' );
		$endpoint = $this->grabValueFrom( '#woocommerce_bitpay_checkout_gateway_bitpay_checkout_endpoint' );
		if ( 'test' !== $endpoint ) {
			throw new \RuntimeException( 'Tests can be run ONLY on test endpoint. Please change it manually' );
		}
	}

	public function prepareDataToMakeAnOrder(): void {
		$this->addAnyProductToCart();
		$this->amOnOrderPage();
		$this->fillOrderInformation();
		$this->selectBitPayPaymentMethod();
	}

	private function waitForDisappearElement( string $element, int $maxTries = 10, int $waitTimeInSeconds = 1 ): void {
		for ( $i = 0; $i <= $maxTries; $i++ ) {
			$isPresent = $this->executeJS( 'return document.querySelector("' . $element . '") !== null' );
			if ( ! $isPresent ) {
				return;
			}
			$this->wait( $waitTimeInSeconds );
		}
		throw new \RuntimeException( 'Element ' . $element . ' still exists after wait ' . $waitTimeInSeconds . ' in sec' );
	}

	public function saveBitPayAdminSettings() {
		$this->click( 'button[name="save"]' );
	}
}
