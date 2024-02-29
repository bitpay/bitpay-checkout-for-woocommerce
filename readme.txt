=== BitPay Checkout for WooCommerce ===
Contributors: bitpay
Tags: bitcoin, ether, ripple, bitcoin cash, ERC20, payments, bitpay, cryptocurrency, payment gateway
Requires at least: 6.0
Tested up to: 6.4.2
Requires PHP: 8.0
Recommended PHP: 8.0
Stable tag: 5.4.0
License: MIT License (MIT)
License URI: https://github.com/bitpay/bitpay-checkout-for-woocommerce/blob/master/LICENSE

The most secure and fastest way to accept crypto payments.

== Description ==

== BitPay Payment Gateway plugin for WooCommerce ==

= Key features =

* Accept cryptocurrency payments from your customers, such as Bitcoin, Ether, Ripple, Bitcoin Cash and USD stable coins.
* Price in your local currency.
* Get settled via Bank transfer (EUR, USD, GBP or any of the supported [fiat currencies](https://developer.bitpay.com/docs/settlement)), BTC, BCH, XRP or USD stable coins (GUSD, USDC, BUSD, PAX)
* No chargebacks
* View all incoming payments and have the possibility to refund orders via your [BitPay merchant dashboard](https://bitpay.com/dashboard/payments)

= Customer journey =

1. The customer is adding items to his shopping card and proceeds to checkout. Let's say the total order amount is $100 USD as an example.
2. The customer selects BitPay as checkout method.
3. A BitPay invoice is generated, the customer selects one of the supported cryptocurrency to complete the payment. The invoice will display an amount to pay in the selected cryptocurrency, at an exchange rate locked for 15 minutes.
4. The customer completes the payment using his cryptocurrency wallet within the 15 min window.
5. Once the transaction is fully confirmed on the blockchain, BitPay notifies the merchant and the corresponding amount is credited to the BitPay merchant account minus our 1% processing fee - thus $99 USD in this example which will be paid out to the merchant's bank account.

== Installation ==

= Requirements =

* This plugin requires [WooCommerce](https://wordpress.org/plugins/woocommerce/).
* A BitPay merchant account ([Test](http://test.bitpay.com) and [Production](http://www.bitpay.com))

NOTE: If you were using a previous version of this plugin, this version (3.0) was completely rewritten to improve the user experience and the security.

= Plugin installation =

1. Get started by signing up for a [BitPay merchant account](https://bitpay.com/dashboard/signup)
2. Look for the BitPay plugin via the [WordPress Plugin Manager](https://codex.wordpress.org/Plugins_Add_New_Screen). From your WordPress admin panel, go to Plugins > Add New > Search plugins and type **BitPay**
3. Select **BitPay for WooCommerce** and click on **Install Now** and then on **Activate Plugin**

After the plugin is activated, BitPay will appear in the WooCommerce > Settings > Payments section.

= Plugin configuration =

After you have installed the BitPay plugin, the configuration steps are:

1. Create an API token from your BitPay merchant dashboard:
	* Login to your BitPay merchant account and go to the [API token settings](https://bitpay.com/dashboard/merchant/api-tokens)
	* click on the **Add new token** button: indicate a token label (for instance: WooCommerce), uncheck "Require Authentication" and click on the **Add Token** button
	* Copy the token value
2. Log in to your WordPress admin panel, select WooCommerce > Payments and click on the **Set up** button next to the BitPay Payment methods
	* Paste the token value into the appropriate field: **Development Token** for token copied from the sandbox environment (test.bitpay.com) and **Production Token** for token copied from the live environment (bitpay.com)
	* select the endpoint - Test or Production
	* Click "Save changes" at the bottom of the page

= Order fulfilment =
	
This plugin also includes an IPN (Instant Payment Notification) endpoint that will update your WooCommerce order status. An order note will automatically be added with a link to the BitPay invoice *(will open in a new window)*:

* When the customer decides to pay with BitPay, he is presented with a BitPay invoice while the WooCommerce order will be set to "Pending"
* The customer initiates a transaction from his wallet to pay the BitPay invoice, the status of the WooCommerce order will change to **Processing** or **Completed** depending how the merchant configured the status in the plugin settings.
* If a BitPay invoice expires before the customer completed the payment, the merchant has the possibility to automatically mark the WooCommerce order as **Cancelled** via the plugin settings.

== Frequently Asked Questions ==

= How do I pay a BitPay invoice? =
Select the wallet you want to use to complete the payment, BitPay will indicate the available currencies and provide compatible instruction for each wallet. You can either scan the QR code, click on the "pay in wallet" button or copy/paste the payment URL / cryptocurrency address depending on the wallet you are using to complete the payment.

= Does BitPay have a test environment? =
Yes, you can create an account on BitPay's sandbox environment to process payments on testnet. You will also need to setup a wallet on testnet to make test transactions. More information about the test environment can be found [here.](https://bitpay.com/docs/testing)

= The BitPay plugin does not work =
If BitPay invoices are not created, please check the following:

* The minimum invoice amount is $1 USD. Please make sure you are trying to create a BitPay invoice for $1 USD or more (or your currency equivalent).
* Check your current approved processing limits in your [BitPay merchant account](https://bitpay.com/dashboard/verification)

= I need support from BitPay =
When contacting BitPay support, please describe your issue and attach screenshots and the BitPay logs.

BitPay logs can be retrieved in your WordPress / WooCommerce environment:

* Enable logging in your BitPay plugin: Plugins > Settings > Debug Log > Enable logging
* Download the logs from Plugins > Logs

You can contact our support team via the following form https://bitpay.com/request-help/wizard

== Screenshots ==

1. BitPay merchant dashboard - create a new POS token
2. BitPay merchant dashboard - Point of Sale token created
3. WordPress WooCommerce admin dashboard - BitPay plugin settings
4. WordPress website - BitPay checkout option
5. BitPay hosted invoice - modal option. Displayed to the user after he clicked the "Pay with BitPay" button
6. BitPay hosted invoice - wallet selected. Displayed to the user after he clicked the "Pay with BitPay" button
7. BitPay hosted invoice - cryptocurrency selected
8. BitPay hosted invoice - Customer clicked on the "pay in wallet", this opens the compatible wallet installed on the device which automatically retrieves the payment information.
9. The customer confirmed the payment via his compatible wallet. The BitPay invoice is then marked as paid.
10. WordPress website - payment successful
11. Wordpress WooCommerce admin dashboard - order view
12. BitPay merchant dashboard - the invoice previously paid is recorded under the "Payments" section
13. BitPay merchant dashboard - detailed invoice view

== Changelog ==

= 5.4.0 =
* Added compatibility with Checkout Blocks
* Fixed Checkout Flow (BitPay Modal)
* Tested compatibility with WordPress 6.4.2
* Fixed issue with exception for missing DB data for plugin in admin panel

= 5.3.2 =
* Fix typo "completed" for BitPay available statuses
* Checking if there is a cart before triggering empty_cart() method

= 5.3.1 =
* Deploy to WooCommerce WordPress.org when released

= 5.3.0 =
* Removed dead code that caused notice
* Downgrade & adapt php-scoper for PHP 8.0

= 5.2.0 =
* Add admin option to allow users to select their BitPay button
* log create invoice issues

= 5.1.0 =
* Generate vendors to avoid potential conflicts between plugins (inconsistent version of same vendor)

= 5.0.1 =
* Fix support for PHP 8.0

= 5.0.0 =
* Improve code quality
* Use BitPay SDK

= 4.1.0 =
* Corrected the cancel invoice flow

= 4.0.2111 =
* Logo update
* Moved to new VCS

= 3.46.0 =
* Added Declined state

= 3.45.2104 =
* Added response code for IPN notifications

 = 3.44.2103 =
* Updated Confirmed/Completed options with WooCommerce functionality to complete an order in the system (if set)

 = 3.42.2103 =
* PHP notice cleanup

= 3.41.2102  =
* Route fixes

= 3.39.2012 =
* Added option for custom redirect page

= 3.38.2012 =
* Added option for custom redirect page

= 3.37.2012 =
* Formatting Fix

= 3.35.2008 =
* Updated order status mapping for Confirmed and Completed (please review your settings in the configuration)

= 3.34.2008 =
* Added default mapping for confirmed/completed orders if one isnt set in the plugin configuration

= 3.33.2008 =
* UX updates

= 3.32.2004 =
* Limit the time a user has to complete  a purchase

= 3.31.2004 =
* IPN Updates

= 3.30.2004 =
* Bug fixes and code cleanup

= 3.20.2003 =
* Updated config to allow merchants to map order states.  You will need to save your BitPay Checkout settings

= 3.19.2003 =
* Fixed issue where BitPay may stay persistent as a payment method

= 3.18.2002 =
* Fixed issue where VIEW CART returned an null url after using the AJAX add-to-cart

= 3.17.2002 =
* Added an ERROR redirection if there is an issue creating a new invoice.  Merchants will need to setup an ERROR page and add the page slug to the configuration

= 3.16.2001 =
* Fixed WooCommerce notices

= 3.15.2001 =
* Removed unused code

= 3.14.1912 =
* Allow merchants to disable the BitPay logo in the mini cart

= 3.13.1912 =
* Added support for future release of BitPay Chrome extension

= 3.12.1912 =
* Fixed issue where cart might not be restored after canceling the payment invoice

= 3.11.1912 =
* Fixed button issue clickability on pages with configurable options

= 3.10.1912 =
* Let the user decide to hide or show the logo on checkout

= 3.9.1912 =
* Added option to show BitPay on product pages for faster checkout

= 3.8.1911 =
* Add option for merchant to set their order as Complete when the invoice has been confirmed

= 3.7.1911 =
* Updated IPN messaging

= 3.6.1911 =
* Allow users to optionally map IPN status updates for Expired invoices

= 3.5.1911 =
* Loads different bitpay.js files based on dev or production setting

= 3.4.1911 =
* Performance updates

= 3.3.1911 =
* Removed old code that was unneded

= 3.2.1911 =
* Fixed issue with IPN setting orders to "on-hold"

= 3.1.1911 =
* Fixed issue with IPN updates

= 3.1.1910 =
* Fixed issue with IPN deleting orders
* Added more descriptive label for Order Status mapping

= 3.0.1910 =
* Fixed issue with IPN deleting orders

= 3.0.5.22 =
* Changed branding to default icon, updated IPN changes

= 3.0.5.21 =
* bug cleanup

= 3.0.5.20 =
* bug cleanup

= 3.0.5.19 =
* bug cleanup

= 3.0.5.18 =
* fixed undefined errors in logs

= 3.0.5.17 =
* Added redirect to cart if order becomes invalid when a user hasn't completed a purchase

= 3.0.5.16 =
* Added redirect to cart if order becomes invalid when a user hasn't completed a purchase

= 3.0.5.15 =
* Changed speed setting so users can defined in BitPay dashboard

= 3.0.5.14 =
* Added API token validation

= 3.0.5.13 =
* Fixed issue where BitPay logo was causing other logos to be hidden.  Add / modify the "bitpay_logo" CSS class in your theme if needed.

= 3.0.5.12 =
* Added optional BitPay logo on checkout page with a css class "bitpay_logo".  Adjust the "max-height" in your css to resize as needed

= 3.0.5.11 =
* Code cleanup

= 3.0.5.10 =
* Code cleanup

= 3.0.5.9 =
* Admin updates

= 3.0.5.8 =
* Added information and links to Tier settings

= 3.0.5.7 =
* Added transaction and error logging

= 3.0.5.6 =
* Code cleanup

= 3.0.5.5 =
* Allow overrides in IPN messages for order statuses

= 3.0.5.4 =
* IPN Updates

= 3.0.5.3 =
* Bug squashing

= 3.0.5.2 =
* Updated to check for server requirements.  To verify, deactivate then reactivate the plugin (your settings will be saved)

= 3.0.5.1 =
* Added option to have no image on checkout page


= 3.0.5.0 =
* Hotfix to support WooCommerce 3.6.x update

= 3.0.4.4 =
* Added option to override the "checkout" slug and add your own if needed

= 3.0.4.3 =
* Token verification update

= 3.0.4.2 =
* Security update for issues where API could be called repeatedly

= 3.0.4.0 =
* Changed loading of bitpay.min.js library

= 3.0.3.9 =
* Fixed issue where some users are experience errors on the modal invoice

= 3.0.3.8 =
* Fixed error log warnings
* Fixed issue where the BitPay checkout message would appear with other payment methods

= 3.0.3.6 =
* IPN Updates

= 3.0.3.5 =
* Added IPN security updates to verify order verification originates from IPN
