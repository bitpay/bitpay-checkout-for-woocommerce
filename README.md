# Notice

This is a Community-supported project.

If you are interested in becoming a maintainer of this project, please contact us at integrations@bitpay.com. Developers at BitPay will attempt to work along the new maintainers to ensure the project remains viable for the foreseeable future.

# Description

Bitcoin payment plugin for [WordPress eCommerce](https://wordpress.org/plugins/wp-e-commerce/) using the bitpay.com service.

# Quick Setup

This version requires the following

* A BitPay merchant account ([Test](http://test.bitpay.com) or [Production](http://www.bitpay.com))
* An API Token ([Test](https://test.bitpay.com/dashboard/merchant/api-tokens) or [Production](https://bitpay.com/dashboard/merchant/api-tokens)
	* When setting up your token, **uncheck** the *Require Authentication button*
* WooCommerce

# Plugin Fields

After the plugin is activated, BitPay will appear in the WooCommerce->Payments section.

* **Title** - This will be the title that appears on the checkout page
* **Description** - This appears on the checkout page describing the payment method
* **Merchant Tokens**
	* A ***development*** or ***production*** token will need to be set
* **BitPay Server Endpoint**
	* Choose **Test** or **Production**, depending on your current setup.  Your matching API Token must be set.

* **Checkout Flow**
	*  **Redirect** - This will send the user to the BitPay invoice screen, and they will be redirected after the transaction to the Order Completed page
	* **Modal** - This will open a popup modal on your site, and will display the order details once the transaction is completed.
* **Checkout Message** - Because the transaction may take time to complete, this message should let users know that their order will be on hold until BitPay finishes processing
* **Auto Capture Email** - If enabled, the plugin will attempt to auto-fill the buyer's email address when paying the invoice
* **Branding** - Choose the button that matches your site design

	
This plugin also includes an IPN endpoint that will update  your WooCommerce order status.

An order note will automatically be added with a link to the invoice *(will open in a new window)*

* Initially your order will be in a **Pending Payment/On-Hold** status when it is intially created
* After the invoice is paid by the user, it will change to a **Processing** status
* When BitPay finalizes the transaction, it will change to a **Completed** status, and your order will be safe to ship, allow access to downloadable products, etc.