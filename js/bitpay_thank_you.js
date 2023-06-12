function showBitPayInvoice(testMode, invoiceID, orderId, cartUrl, restoreUrl) {

		jQuery( "#primary" ).hide()
		let payment_status = null;
		let is_paid        = false
		window.addEventListener(
			"message",
			function (event) {
				payment_status = event.data.status;
				if (payment_status === 'paid') {
					is_paid = true
				}
			},
			false
		);

		// hide the order info.
		bitpay.onModalWillEnter(
			function () {
				jQuery( "primary" ).hide()
			}
		);

		// show the order info.
		bitpay.onModalWillLeave(
			function () {
				if (is_paid === true) {
					jQuery( "#primary" ).fadeIn( "slow" );
				} else {
					const myKeyVals = {
						invoiceid: invoiceID
					}

					jQuery.ajax(
						{
							type: 'POST',
							url: restoreUrl,
							data: myKeyVals,
							dataType: "text",
							success: function (resultData) {
								window.location = cartUrl;
							}
						}
					);
				}
			}
		);

	if ( testMode === true ) {
		bitpay.enableTestMode();
	}

	bitpay.showInvoice( invoiceID );
}