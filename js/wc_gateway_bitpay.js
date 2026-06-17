jQuery( document ).ready(
	function () {
		const logo = jQuery( "#woocommerce_bitpay_checkout_gateway_bitpay_logo" );
		if ( logo.length === 0 ) {
				return;
		}

		logo.on(
			"change",
			function () {
				const white = jQuery( "#woocommerce_bitpay_checkout_gateway_bitpay_logo_image_white" ).next();
				const dark  = jQuery( "#woocommerce_bitpay_checkout_gateway_bitpay_logo_image_dark" ).next();
				const url   = window.location.origin + '/wp-content/plugins/bitpay-checkout-for-woocommerce/images/'
				+ this.value + '.svg'

				white.html( '<img src="' + url + '" style="background-color: white"/>' );
				dark.html( '<img src="' + url + '" style="background-color: black"/>' );
			}
		)

		function downloadZipFile(blob, name) {
			const a    = document.createElement( 'a' );
			a.href     = URL.createObjectURL( blob );
			a.download = name;
			a.click();
		}

		document.getElementById( 'download_support_package' ).addEventListener(
			'click',
			async function () {
				const nonce = document.getElementById( '_wpnonce' );
				wp.apiFetch.use( wp.apiFetch.createNonceMiddleware( nonce ) );

				const response = await wp.apiFetch(
					{
						path: '/bitpay/site/health-status',
						parse: false
					}
				);
				const blob     = await response.blob();

				downloadZipFile( blob, 'bitpay-support-package.zip' );
			}
		);
	}
);
