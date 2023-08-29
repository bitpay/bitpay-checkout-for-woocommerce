jQuery( document ).ready(
	function () {
		const logo = jQuery( "#woocommerce_bitpay_checkout_gateway_bitpay_logo" );
		if ( logo.length === 0 ) {
				return;
		}

		logo.on(
			"change",
			function () {
				const image = jQuery( "#woocommerce_bitpay_checkout_gateway_bitpay_logo_image" ).next();
				const url   = window.location.origin + '/wp-content/plugins/bitpay-checkout-for-woocommerce/images/'
				+ this.value + '.svg'

				image.html( '<img src="' + url + '"/>' );
			}
		)
	}
);
