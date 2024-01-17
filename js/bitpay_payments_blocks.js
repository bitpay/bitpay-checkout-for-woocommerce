const settings = window.wc.wcSettings.getSetting( 'bitpay_checkout_gateway_data', {} )
const label    = window.wp.htmlEntities.decodeEntities( settings.title ) || 'BitPay';
const content  = () => {
	return window.wp.htmlEntities.decodeEntities( settings.description || '' );
};

/**
 * BitPay payment method config object.
 */
const BitPay = {
	name: "bitpay_checkout_gateway",
	label: label,
	content: Object( window.wp.element.createElement )( content, null ),
	edit: Object( window.wp.element.createElement )( content, null ),
	canMakePayment: () => true,
	ariaLabel: label,
	supports: {
		features: settings.supports
	},
};

window.wc.wcBlocksRegistry.registerPaymentMethod( BitPay )
