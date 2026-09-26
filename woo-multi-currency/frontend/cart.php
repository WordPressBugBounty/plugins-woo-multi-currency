<?php

/**
 * Class WOOMULTI_CURRENCY_F_Frontend_Cart
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals, WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput -- Historical WOOMULTI_CURRENCY_F / wmc_ / vi_ / VillaTheme_ prefixes. Cache-compat allows a missing plugin nonce (invalid nonce is still rejected). Inputs are unslashed/sanitized; PCP does not treat wc_clean() as a sanitizer.


class WOOMULTI_CURRENCY_F_Frontend_Cart {
	protected $settings;

	public function __construct() {
		$this->settings = WOOMULTI_CURRENCY_F_Data::get_ins();
		if ( $this->settings->get_enable() ) {
			/*Fix round function with case default decimals = 0 and other currency decimal > 0*/
			add_filter( 'woocommerce_calculated_total', array( $this, 'woocommerce_calculated_total' ), 10, 2 );
			add_action( 'template_redirect', array( $this, 'maybe_fallback_unsafe_cart_total' ), 20 );
		}
	}

	/**
	 * Fall back to default currency when cart/checkout totals would round to zero.
	 */
	public function maybe_fallback_unsafe_cart_total() {
		if ( is_admin() && ! wp_doing_ajax() ) {
			return;
		}
		if ( ! function_exists( 'is_cart' ) || ( ! is_cart() && ! is_checkout() ) ) {
			return;
		}
		if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
			return;
		}

		$current_currency = $this->settings->get_current_currency();
		$default_currency = $this->settings->get_default_currency();
		if ( $current_currency === $default_currency ) {
			return;
		}

		if ( $this->settings->maybe_fallback_unsafe_currency( $current_currency ) ) {
			return;
		}

		$unrounded = 0.0;
		foreach ( WC()->cart->get_cart() as $cart_item ) {
			if ( empty( $cart_item['data'] ) || ! is_a( $cart_item['data'], 'WC_Product' ) ) {
				continue;
			}
			$unrounded += (float) $cart_item['data']->get_price( 'edit' ) * (float) $cart_item['quantity'];
		}

		$this->settings->maybe_fallback_unsafe_converted_total( $unrounded );
	}

	/**
	 * @param $total
	 * @param $cart WC_Cart
	 *
	 * @return string
	 */
	public function woocommerce_calculated_total( $total, $cart ) {
		$list_currencies  = $this->settings->get_list_currencies();
		$current_currency = $this->settings->get_current_currency();
		$default_currency = $this->settings->get_default_currency();
		if ( ! isset( $list_currencies[ $default_currency ], $list_currencies[ $current_currency ] ) ) {
			return $total;
		}
		if ( (int) $list_currencies[ $default_currency ]['decimals'] > 0 ) {
			return $total;
		}

		if ( ! empty( $list_currencies[ $current_currency ]['decimals'] ) ) {
			$new_total = $cart->get_cart_contents_total() + $cart->get_fee_total() + $cart->get_shipping_total() + $cart->get_total_tax();
			if ( $new_total > $total ) {
				$total = number_format( $new_total, (int) $list_currencies[ $current_currency ]['decimals'], wc_get_price_decimal_separator(), '' );
			}
		}

		return $total;
	}
}