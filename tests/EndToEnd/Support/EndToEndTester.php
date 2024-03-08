<?php

declare(strict_types=1);

namespace BitPayLib\Tests\EndToEnd\Support;

use BitPayLib\Tests\EndToEnd\Support\_generated\EndToEndTesterActions;

/**
 * Inherited Methods
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
class EndToEndTester extends \Codeception\Actor
{
    use EndToEndTesterActions;

    public function getShopPageSlug(): string {
        return $_ENV['WORDPRESS_SHOP_PAGE_SLUG'] ?? 'shop';
    }

    public function getProductPageSlug(): string {
        return $_ENV['WORDPRESS_PRODUCT_PAGE_SLUG'] ?? 'products';
    }

    public function amOnSomeProductPage()
    {
        $this->amOnAdminProductPage();
        $productName = $this->grabTextFrom('td.name');

        $this->amOnPage($this->getProductPageSlug() . '/' . $productName);
    }

    public function addAnyProductToCart()
    {
        $this->amOnSomeProductPage();
        $priceValue = $this->grabTextFrom('.woocommerce-Price-amount.amount');
        $firstDigitIndex = strcspn($priceValue, '0123456789');
        $price = substr($priceValue, $firstDigitIndex);

        $qtyRequired = (int)(round(10 / $price, 0, PHP_ROUND_HALF_UP));
        $this->fillField('input[name="quantity"]', $qtyRequired);
        $this->click('.single_add_to_cart_button');
    }

    private function amOnAdminProductPage()
    {
        $this->amOnAdminPage('edit.php?post_type=product');
    }

    public function amOnOrderPage()
    {
        $this->amOnPage('zamowienie'); // @todo
    }

    public function fillOrderInformation()
    {
        $this->fillField('#billing_first_name', 'Bruce');
        $this->fillField('#billing_last_name', 'Wayne');
        $this->fillField('#billing_address_1', 'Dark Knight 1/2');
        $this->fillField('#billing_city', 'Gotham City');
        $this->fillField('#billing_postcode', '12345');
        $this->fillField('#billing_phone', '12345');
        $this->fillField('#billing_email', 'office@batman.com');
    }

    public function selectBitPayPaymentMethod()
    {
        $this->waitForElementClickable('.woocommerce-checkout-payment', 30);

        $this->waitForDisappearElement('.blockUI', 10);
        $this->click('label[for="payment_method_bitpay_checkout_gateway"]');
    }

    public function placeOrder()
    {
        $this->click('#place_order');
    }

    public function resetBitPayAdminSettings(): void {
        $this->fillField('#woocommerce_bitpay_checkout_gateway_bitpay_close_url', null);
        $this->fillField('#woocommerce_bitpay_checkout_gateway_bitpay_checkout_slug', null);
        $this->fillField('#woocommerce_bitpay_checkout_gateway_bitpay_custom_redirect', null);
    }

    public function amOnBitPayAdminSettings(): void
    {
        $this->amOnPage('/wp-admin/admin.php?page=wc-settings&tab=checkout&section=bitpay_checkout_gateway');
        $endpoint = $this->grabValueFrom('#woocommerce_bitpay_checkout_gateway_bitpay_checkout_endpoint');
        if ('test' !== $endpoint) {
            throw new \RuntimeException('Tests can be run ONLY on test endpoint. Please change it manually');
        }
    }

    public function prepareDataToMakeAnOrder(): void
    {
        $this->addAnyProductToCart();
        $this->amOnOrderPage();
        $this->fillOrderInformation();
        $this->selectBitPayPaymentMethod();
    }

    private function waitForDisappearElement(string $element, int $maxTries = 10, int $waitTimeInSeconds = 1): void
    {
        for($i=0; $i<=$maxTries; $i++) {
            $isPresent = $this->executeJS('return document.querySelector("' . $element . '") !== null');
            if (!$isPresent) {
                return;
            }
            $this->wait($waitTimeInSeconds);
        }
        throw new \RuntimeException('Element ' . $element . ' still exists after wait ' . $waitTimeInSeconds . ' in sec');
    }

    public function saveBitPayAdminSettings()
    {
        $this->click('button[name="save"]');
    }
}
