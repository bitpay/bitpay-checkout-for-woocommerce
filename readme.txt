=== bitpay-for-woocommerce ===
Contributors: bitpay
Tags: bitcoin, payments, bitpay, cryptocurrency, payment
Requires at least: 4.3.1
Tested up to: 4.8.1
Requires PHP: 5.5
Stable tag: trunk
License: MIT License (MIT)
License URI: https://opensource.org/licenses/MIT
 
BitPay allows you to accept bitcoin payments on your WooCommerce store.
 
== Description ==
 
Use BitPay's plugin to accept Bitcoin payments from customers anywhere on earth. You don't need to worry about bitcoin as a currency, because the plugin uses your local currency to create a BitPay invoice. 
Key features
* Support all bitcoin wallets by offering QR code scanning, a clickable bitcoin URI and a bitcoin address/amount that can be copy/pasted
* Price in your local currency, let customers pay with bitcoin
* Receive instant payment confirmation (e.g. for virtual goods)
* Have an overview of all your bitcoin payments in your BitPay merchant dashboard at https://bitpay.com
* Refund your customers in bitcoin in your BitPay merchant dashboard at https://bitpay.com
 
== Installation ==
 
Get started by signing up for a [BitPay merchant account.](https://bitpay.com/dashboard/signup)

You must also have already installed the Woocommerce plugin from the WordPress Plugin Directory. 

Install the latest version of the BitPay plugin for Woocommerce.
1. Navigate to your WordPress Admin Panel and select Plugins > Add New > Upload Plugin.
1. Select the downloaded plugin and click "Install Now". Select "Activate" to complete installation. 

**Connecting BitPay and Woocommerce**

Log in to your WordPress admin panel and select "WooCommerce" > "Settings" > "Checkout" > "BitPay" to access the configuration settings for the plugin.
You can also access the configuration settings by navigating to "Plugins" and clicking the "Settings" link for this plugin.
Now create a pairing code:
1. Create a pairing code in your BitPay merchant dashboard.
1. Copy and paste this pairing code into the "Pairing Code" field in your WordPress plugin admin dashboard to create an API token for BitPay transactions.
NOTE: Pairing codes are only valid for a short period of time. If a code expires before you get to use it, you can always create a new one and pair with it.

You will only need to do this once since each time you do this, the extension will generate public and private keys that are used to identify you when using the API.

Nice work! Your customers will now be able to check out with bitcoin on your WordPress site.
 
== Changelog ==

= 2.2.13
Fixed
* wrong function call resulting in undefined wc_reduce_stock_levels() (#84)
* syntax error in class-wc-gateway-bitpay.php (#80)
* Price must be formatted as a float (#78)
Added
* Redirect page displays 'payment successful' even for unpaid invoices (#81)

= 2.2.12 =
Fixed
* Removed non-working option to disable BitPay from the BitPay plugin config page
* Populate buyer email when creating BitPay invoice
* WC v3 compatibility fixes
* Change Mcrypt to OpenSSL (#77)
* Improve logging around updating order states
