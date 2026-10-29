<?php

/**
 * Class WOOMULTI_CURRENCY_F_Frontend_Cart
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WOOMULTI_CURRENCY_F_Frontend_Cart {
	protected $settings;

	public function __construct() {
		$this->settings = WOOMULTI_CURRENCY_F_Data::get_ins();
		if ( $this->settings->get_enable() ) {
			add_action( 'woocommerce_before_calculate_totals', array( $this, 'note_cart_decimal_places' ), 1 );
			add_filter( 'woocommerce_calculated_total', array( $this, 'woocommerce_calculated_total' ), 10, 2 );
			add_filter( 'woocommerce_widget_shopping_cart_total', array( $this, 'woocommerce_widget_shopping_cart_total' ), 10 );
		}
	}

	/**
	 * @param $cart WC_Cart
	 */
	public function note_cart_decimal_places( $cart ) {
		if ( class_exists( 'WOOMULTI_CURRENCY_F_Frontend_Price' ) ) {
			WOOMULTI_CURRENCY_F_Frontend_Price::note_cart_decimal_places( $cart );
		}
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
		$default_decimals = ( is_array( $list_currencies ) && isset( $list_currencies[ $default_currency ] ) && is_array( $list_currencies[ $default_currency ] ) && isset( $list_currencies[ $default_currency ]['decimals'] ) ) ? $list_currencies[ $default_currency ]['decimals'] : null;
		if ( $default_decimals > 0 ) {
			return $total;
		}
		$current_decimals = ( is_array( $list_currencies ) && isset( $list_currencies[ $current_currency ] ) && is_array( $list_currencies[ $current_currency ] ) && isset( $list_currencies[ $current_currency ]['decimals'] ) ) ? $list_currencies[ $current_currency ]['decimals'] : null;

		if ( $current_decimals ) {
			$new_total = $cart->get_cart_contents_total() + $cart->get_fee_total() + $cart->get_shipping_total() + $cart->get_total_tax();
			if ( $new_total > $total ) {
				$total = number_format( $new_total, (int) $current_decimals, wc_get_price_decimal_separator(), '' );
			}
		}

		return $total;
	}

	public function woocommerce_widget_shopping_cart_total( $total ) {
		if ( ! function_exists( 'wmc_get_price' ) || ! function_exists( 'wmc_revert_price' ) ) {
			return $total;
		}

		$current_currency = $this->settings->get_current_currency();
		$default_currency = $this->settings->get_default_currency();
		if ( $current_currency === $default_currency ) {
			return $total;
		}

		$cart = WC()->cart;
		if ( ! $cart ) {
			return $total;
		}

		$shipping = $cart->get_shipping_total();
		$fees     = $cart->get_fee_total();
		$subtotal = $cart->get_subtotal() + $shipping + $fees;

		if ( $cart->display_prices_including_tax() ) {
			$subtotal += $cart->get_subtotal_tax() + $cart->get_shipping_tax() + $cart->get_fee_tax();
		}

		return wc_price( $subtotal );
	}
}
