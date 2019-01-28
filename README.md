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

#Plugin Fields

After the plugin is activated, BitPay will appear in the WooCommerce->Payments section.

* **Title** - This will be the title that appears on the checkout page
* **Description** - This appears on the checkout page describing the payment method
* **Merchant Tokens**
	* A ***development*** or ***production*** token will need to be set
* **BitPay Server Endpoint**
	* Choose **Test** or **Production**, depending on your current setup.  Your matching API Token must be set.
	* **Accepted Cryptocurrencies** - You can choose ***BitCoin***, ***BitCoin Cash***, or ***All***
	* **Checkout Flow**
		* **Redirect** - This will send the user to the BitPay invoice screen, and they will be redirected after the transaction to the Order Completed page
		* **Modal** - This will open a popup modal on your site, and will display the order details once the transaction is completed.
	* **Checkout Message** - Because the transaction may take time to complete, this message should let users know that their order will be on hold until BitPay finishes processing

This plugin also includes an IPN endpoint that will update  your WooCommerce order status.

* Initially your order will be in a **Pending Payment/On-Hold** status when it is intially created
* After the invoice is paid by the user, it will change to a **Processing** status
* When BitPay finalizes the transaction, it will change to a **Completed** status, and your order will be safe to ship, allow access to downloadable products, etc.



## Support

**BitPay Support:**

* Last Cart Version Tested: Wordpress 5.0.3 WP e-commerce 3.5.4
* [GitHub Issues](https://github.com/bitpay/wordpress-ecommerce-plugin/issues)
  * Open an issue if you are having issues with this plugin.
* [Support](https://help.bitpay.com)
  * BitPay merchant support documentation

**WP eCommerce Support:**

* [Homepage](https://wpecommerce.org/)
* [Documentation](http://docs.wpecommerce.org/)
* [Support Forums](https://wordpress.org/support/plugin/wp-e-commerce)

## Troubleshooting

The latest version of this plugin can always be downloaded from the official BitPay repository located here: https://github.com/bitpay/wordpress-ecommerce-plugin

* This plugin requires PHP 5.4 or higher to function correctly. Contact your webhosting provider or server administrator if you are unsure which version is installed on your web server.
* Ensure a valid SSL certificate is installed on your server. Also ensure your root CA cert is updated. If your CA cert is not current, you will see curl SSL verification errors.
* Verify that your web server is not blocking POSTs from servers it may not recognize. Double check this on your firewall as well, if one is being used.
* Check the system error log file (usually the web server error log) for any errors during BitPay payment attempts. If you contact BitPay support, they will ask to see the log file to help diagnose the problem.
* Check the version of this plugin against the official plugin repository to ensure you are using the latest version. Your issue might have been addressed in a newer version!

**NOTE:** When contacting support it will help us if you provide:

* Wordpress Version
* WP eCommerce Version
* PHP Version
* Other plugins you have installed

## Contribute

Would you like to help with this project?  Great!  You don't have to be a developer, either.  If you've found a bug or have an idea for an improvement, please open an [issue](https://github.com/bitpay/wordpress-ecommerce-plugin/issues) and tell us about it.

If you *are* a developer wanting contribute an enhancement, bugfix or other patch to this project, please fork this repository and submit a pull request detailing your changes.  We review all PRs!

This open source project is released under the [MIT license](http://opensource.org/licenses/MIT) which means if you would like to use this project's code in your own project you are free to do so.  Speaking of, if you have used our code in a cool new project we would like to hear about it!  Please send us an [email](mailto:integrations@bitpay.com).

## License

Please refer to the [LICENSE](https://github.com/bitpay/wordpress-ecommerce-plugin/blob/master/LICENSE) file that came with this project.
