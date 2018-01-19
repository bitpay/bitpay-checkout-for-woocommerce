
=== bitpay-for-woocommerce ===
Contributors: bitpay
Tags: bitcoin, payments, bitpay, cryptocurrency, payment
Requires at least: 4.3.1
Tested up to: 4.9.2
Requires PHP: 5.5
Stable tag: 2.2.14
License: MIT License (MIT)
License URI: https://opensource.org/licenses/MIT
 
BitPay allows you to accept bitcoin payments on your WooCommerce store.
 
== Description ==

Use BitPay's plugin to accept Bitcoin payments from customers anywhere on earth.

Key features:

* Support all bitcoin wallets that support payment protocol
* Price in your local currency, let customers pay with bitcoin
* Have an overview of all your bitcoin payments in your BitPay merchant dashboard at https://bitpay.com
* Refund your customers in bitcoin in your BitPay merchant dashboard at https://bitpay.com
 
= Installation =
This plugin requires Woocommerce. Please make sure you have Woocommerce installed.

1. Get started by signing up for a [BitPay merchant account.](https://bitpay.com/dashboard/signup)
1. Download the latest version of the BitPay plugin from the [Wordpress site.](https://downloads.wordpress.org/plugin/bitpay-for-woocommerce.2.2.14.zip)
1. Install the latest version of the BitPay plugin for Woocommerce:
	* Navigate to your WordPress Admin Panel and select Plugins > Add New > Upload Plugin.
	* Select the downloaded plugin and click "Install Now".
	* Select "Activate Plugin" to complete installation. 

= Connecting BitPay and Woocommerce =
After you have installed the BitPay plugin, you can configure the plugin:

1. Create a BitPay pairing code in your BitPay merchant dashboard:
	* Login to your [BitPay merchant account](https://bitpay.com/dashboard/login/) and select Payment Tools -> Manage API Tokens -> Add New Token -> Add Token
	* Copy the 7 character pairing code
2. Log in to your WordPress admin panel and select "Plugins" -> "Settings" link for the BitPay plugin.
	* Paste the 7 character pairing code into the "Pairing Code" field in your BitPay plugin and click "Find"
	* Click "Save changes" at the bottom

Pairing codes need to be used once and are only valid for 24 hours. If a code expires before you get to use it, you can always create a new one and pair with it.

Nice work! Your customers will now be able to check out with bitcoin on your WordPress site.

== Frequently Asked Questions ==

= How do I pay a BitPay invoice? =
You can pay a BitPay invoice with a Bitcoin wallet. You can either scan the QR code or copy/paste the payment link in your Bitcoin wallet.

More information about paying a BitPay invoice can be found [here.](https://support.bitpay.com/hc/en-us/articles/203281456-How-do-I-pay-a-BitPay-invoice-)

= Does BitPay have a test environment? =
BitPay allows you to create a test merchant account and a testnet Bitcoin wallet.

More information about the test environment can be found [here.](https://bitpay.com/docs/testing)

= The BitPay plugin does not work =
If BitPay invoices are not created, please check the following:

* The minimum invoice amount is USD 5. Please make sure you are trying to create a BitPay invoice for USD 5 or more (or your currency equivalent).
* Please make sure your BitPay merchant account is enabled for your transaction amounts. In your [BitPay merchant account](https://bitpay.com/dashboard/login/), go to Settings -> General -> Increase Processing Volume

= I need support from BitPay =
When contacting BitPay support, please describe your issue and attach screenshots and the BitPay logs.

BitPay logs can be retrieved in your Wordpress / Woocommerce environment:

* Enable logging in your BitPay plugin: Plugins -> Settings -> Debug Log -> Enable logging
* Download the logs from Plugins -> Logs

You can email your issue report to support@bitpay.com


== Changelog ==
= 2.2.14 =
* (fixed via PHP package update) Price must be formatted as a float (#78)
* Fixed WC 2.5 compatibility, with get_billing_email() error (#83)

= 2.2.13 = 
* Fixed wrong function call resulting in undefined wc_reduce_stock_levels() (#84)
* Fixed syntax error in class-wc-gateway-bitpay.php (#80)
* Fixed price must be formatted as a float (#78)
* Added redirect page, displaying 'payment successful' even for unpaid invoices (#81)

= 2.2.12 =
* Removed non-working option to disable BitPay from the BitPay plugin config page
* Populate buyer email when creating BitPay invoice
* WC v3 compatibility fixes
* Change Mcrypt to OpenSSL (#77)
* Improve logging around updating order states
