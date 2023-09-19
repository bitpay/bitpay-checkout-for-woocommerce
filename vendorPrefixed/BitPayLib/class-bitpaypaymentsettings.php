<?php

declare (strict_types=1);
namespace BitPayVendor\BitPayLib;

/**
 * Plugin Name: BitPay Checkout for WooCommerce
 * Plugin URI: https://www.bitpay.com
 * Description: BitPay Checkout Plugin
 * Version: 5.2.0
 * Author: BitPay
 * Author URI: mailto:integrations@bitpay.com?subject=BitPay Checkout for WooCommerce
 */
class BitPayPaymentSettings
{
    public function define_payment_gateway() : void
    {
        if (is_checkout() && !is_wc_endpoint_url()) {
            global $woocommerce;
            $bitpay_checkout_options = get_option('woocommerce_bitpay_checkout_gateway_settings');
            $bitpay_checkout_product = $bitpay_checkout_options['bitpay_checkout_product'];
            $default_payment_id = 'bitpay_checkout_gateway';
            if ((int) $bitpay_checkout_product === 1 && isset($_GET['payment']) && $_GET['payment'] === 'bitpay') {
                // phpcs:ignore
                WC()->session->set('chosen_payment_method', $default_payment_id);
            }
        }
    }
    public function wc_bitpay_checkout_add_to_gateways(array $gateways) : array
    {
        $gateways[] = new WcGatewayBitpay();
        return $gateways;
    }
    public function bitpay_mini_checkout() : void
    {
        $bitpay_checkout_options = get_option('woocommerce_bitpay_checkout_gateway_settings');
        $bitpay_checkout_mini = $bitpay_checkout_options['bitpay_checkout_mini'];
        if (1 !== (int) $bitpay_checkout_mini) {
            return;
        }
        $url = get_permalink(get_option('woocommerce_checkout_page_id'));
        $url .= '?payment=bitpay';
        ?>
			<script type="text/javascript">
				//widget_shopping_cart_content
				var obj = document.createElement("div");
				// obj.style.cssText = 'margin:0 auto;cursor:pointer';
				obj.innerHTML = '<img style = "margin:0 auto;cursor:pointer;padding-bottom:10px;" onclick = "bpMiniCheckout()" src = "//bitpay.com/cdn/merchant-resources/pay-with-bitpay-card-group.svg">'

				var miniCart = document.getElementsByClassName("widget_shopping_cart_content")[0];
				miniCart.appendChild(obj);

				function bpMiniCheckout() {
					let checkoutUrl = '<?php 
        echo esc_js($url);
        ?>';
					window.location = checkoutUrl

				}
			</script>
		<?php 
    }
    public function bitpay_checkout_replace_order_button_html($order_button, $override = \false) : ?string
    {
        if ($override) {
            return null;
        }
        return $order_button;
    }
    public function redirect_after_purchase(int $order_id) : void
    {
        $order = new \WC_Order($order_id);
        if ($order->get_payment_method() !== 'bitpay_checkout_gateway') {
            return;
        }
        $checkout_message = $this->get_checkout_message();
        if ($order->get_status() === 'pending') {
            $close_url = $this->get_close_url();
            if ($close_url) {
                wp_redirect($close_url);
                // phpcs:ignore
                die;
            }
        }
        if ('' !== $checkout_message) {
            echo '<hr><b>' . $checkout_message . '</b><br><br><hr>';
            // phpcs:ignore
        }
    }
    public function check_token() : void
    {
        $section = $_GET['section'] ?? null;
        // phpcs:ignore
        if (!$section || 'bitpay_checkout_gateway' !== $section || !empty($_POST) || !is_admin()) {
            // phpcs:ignore
            return;
        }
        if (!\file_exists(plugin_dir_path(__FILE__) . 'logs')) {
            \mkdir(plugin_dir_path(__FILE__) . 'logs', 0755, \true);
        }
        $bitpay_checkout_token = $this->get_bitpay_token();
        $bitpay_checkout_endpoint = $this->get_bitpay_gateway_setting('bitpay_checkout_endpoint');
        if (!$bitpay_checkout_token) {
            $message = 'There is no token set for your ' . \strtoupper($bitpay_checkout_endpoint) . ' environment. BitPay will not function if this is not set.';
            \WC_Admin_Settings::add_error($message);
        }
    }
    public function get_bitpay_environment() : ?string
    {
        return $this->get_bitpay_gateway_setting('bitpay_checkout_endpoint');
    }
    public function get_bitpay_token() : ?string
    {
        $env = $this->get_bitpay_environment();
        $suffix = '';
        if ('test' === $env) {
            $suffix = 'dev';
        }
        if ('production' === $env) {
            $suffix = 'prod';
        }
        return $this->get_bitpay_gateway_setting('bitpay_checkout_token_' . $suffix, null);
    }
    public function should_use_modal() : bool
    {
        $option = $this->get_bitpay_gateway_setting('bitpay_checkout_flow');
        return 1 === (int) $option;
    }
    public function get_checkout_message() : string
    {
        return $this->get_bitpay_gateway_setting('bitpay_checkout_checkout_message', '');
    }
    public function get_close_url() : ?string
    {
        return $this->get_bitpay_gateway_setting('bitpay_close_url', null);
    }
    public function should_capture_email() : bool
    {
        return (bool) $this->get_bitpay_gateway_setting('bitpay_checkout_capture_email', '0');
    }
    public function get_custom_redirect_page() : ?string
    {
        $custom_redirect_page = $this->get_bitpay_gateway_setting('bitpay_custom_redirect', null);
        if ('' === $custom_redirect_page) {
            return null;
        }
        return $custom_redirect_page;
    }
    public function get_checkout_slug() : ?string
    {
        $slug = $this->get_bitpay_gateway_setting('bitpay_checkout_slug', null);
        if ('' === $slug) {
            return null;
        }
        return $slug;
    }
    public function get_payment_logo_url() : string
    {
        $logo = $this->get_bitpay_gateway_setting('bitpay_logo', 'BitPay-Accepted-CardGroup');
        return plugins_url('../../images/', __FILE__) . $logo . '.svg';
    }
    private function get_bitpay_gateway_setting(string $setting_name, $default_value = null) : ?string
    {
        return $this->get_bitpay_gateway_settings()[$setting_name] ?? $default_value;
    }
    private function get_bitpay_gateway_settings() : array
    {
        return get_option('woocommerce_bitpay_checkout_gateway_settings', array());
    }
}
