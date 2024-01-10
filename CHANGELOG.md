# Change Log
All notable changes to this project will be documented in this file.
This project adheres to [Semantic Versioning](http://semver.org/).

# 5.3.2
* fix typo "completed" for BitPay available statuses
* checking if there is a card before triggering empty_cart() method 

# 5.3.1
* Deploy to WooCommerce WordPress.org when released

# 5.3.0
* Removed dead code that caused notice
* Downgrade & adapt php-scoper for PHP 8.0

# 5.2.0
* Add admin option to allow users to select their BitPay button
* log create invoice issues

# 5.1.0
* Generate vendors to avoid potential conflicts between plugins (inconsistent version of same vendor)

# 5.0.1
* Fix support for PHP 8.0

# 5.0.0
* Improve code quality
* Use BitPay SDK

# 4.1.0
* Corrected the cancel invoice flow

# 4.0.2111
* Logo update
* Moved to new VCS

# 3.46.0
* Added Declined state

# 3.45.2104
* Added response code for IPN notifications

# 3.44.2103
* Updated Confirmed/Completed options with WooCommerce functionality to complete an order in the system (if set)

# 3.42.2103
* PHP notice cleanup

# 3.41.2102 
* Route fixes

# 3.39.2012
* Added option for custom redirect page

# 3.38.2012
* Added option for custom redirect page

# 3.37.2012
* Formatting Fix

# 3.35.2008
* Updated order status mapping for Confirmed and Completed (please review your settings in the configuration)

# 3.34.2008
* Added default mapping for confirmed/completed orders if one isnt set in the plugin configuration

# 3.33.2008
* UX updates

# 3.32.2004
* Limit the time a user has to complete  a purchase

# 3.31.2004
* IPN Updates

# 3.30.2004
* Bug fixes and code cleanup

# 3.20.2003
* Updated config to allow merchants to map order states.  You will need to save your BitPay Checkout settings

# 3.19.2003
* Fixed issue where BitPay may stay persistent as a payment method

# 3.18.2002
* Fixed issue where VIEW CART returned an null url after using the AJAX add-to-cart

# 3.17.2002
* Added an ERROR redirection if there is an issue creating a new invoice.  Merchants will need to setup an ERROR page and add the page slug to the configuration

# 3.16.2001
* Fixed WooCommerce notices

# 3.15.2001
* Removed unused code

# 3.14.1912
* Allow merchants to disable the BitPay logo in the mini cart

# 3.13.1912
* Added support for future release of BitPay Chrome extension

# 3.12.1912
* Fixed issue where cart might not be restored after canceling the payment invoice

# 3.11.1912
* Fixed button issue clickability on pages with configurable options

# 3.10.1912
* Let the user decide to hide or show the logo on checkout

# 3.9.1912
* Added option to show BitPay on product pages for faster checkout

# 3.8.1911
* Add option for merchant to set their order as Complete when the invoice has been confirmed

# 3.7.1911
* Updated IPN messaging

# 3.6.1911
* Allow users to optionally map IPN status updates for Expired invoices

# 3.5.1911
* Loads different bitpay.js files based on dev or production setting

# 3.4.1911
* Performance updates

# 3.3.1911
* Removed old code that was unneded

# 3.2.1911
* Fixed issue with IPN setting orders to "on-hold"

# 3.1.1911
* Fixed issue with IPN updates

# 3.1.1910
* Fixed issue with IPN deleting orders
* Added more descriptive label for Order Status mapping

# 3.0.1910
* Fixed issue with IPN deleting orders

# 3.0.5.22
* Changed branding to default icon, updated IPN changes

# 3.0.5.21
* bug cleanup

# 3.0.5.20
* bug cleanup

# 3.0.5.19
* bug cleanup

# 3.0.5.18
* fixed undefined errors in logs

# 3.0.5.17
* Added redirect to cart if order becomes invalid when a user hasn't completed a purchase

# 3.0.5.16
* Added redirect to cart if order becomes invalid when a user hasn't completed a purchase

# 3.0.5.15
* Changed speed setting so users can defined in BitPay dashboard

# 3.0.5.14
* Added API token validation

# 3.0.5.13
* Fixed issue where BitPay logo was causing other logos to be hidden.  Add / modify the "bitpay_logo" CSS class in your theme if needed.

# 3.0.5.12
* Added optional BitPay logo on checkout page with a css class "bitpay_logo".  Adjust the "max-height" in your css to resize as needed

# 3.0.5.11
* Code cleanup

# 3.0.5.10
* Code cleanup

# 3.0.5.9
* Admin updates

# 3.0.5.8
* Added information and links to Tier settings

# 3.0.5.7
* Added transaction and error logging

# 3.0.5.6
* Code cleanup

# 3.0.5.5
* Allow overrides in IPN messages for order statuses

# 3.0.5.4
* IPN Updates

# 3.0.5.3
* Bug squashing

# 3.0.5.2
* Updated to check for server requirements.  To verify, deactivate then reactivate the plugin (your settings will be saved)

# 3.0.5.1
* Added option to have no image on checkout page


# 3.0.5.0
* Hotfix to support WooCommerce 3.6.x update

# 3.0.4.4
* Added option to override the "checkout" slug and add your own if needed

# 3.0.4.3
* Token verification update

# 3.0.4.2
* Security update for issues where API could be called repeatedly

# 3.0.4.0
* Changed loading of bitpay.min.js library

# 3.0.3.9
* Fixed issue where some users are experience errors on the modal invoice

# 3.0.3.8
* Fixed error log warnings
* Fixed issue where the BitPay checkout message would appear with other payment methods

# 3.0.3.6
* IPN Updates

# 3.0.3.5
* Added IPN security updates to verify order verification originates from IPN