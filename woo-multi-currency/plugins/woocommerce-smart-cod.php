<?php

/**
 * Class WOOMULTI_CURRENCY_Plugin_WooCommerce_Smart_COD
 * Plugin WooCommerce Smart COD
 * Author: woosmartcod.com
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals, WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput -- Historical WOOMULTI_CURRENCY_F / wmc_ / vi_ / VillaTheme_ prefixes. Cache-compat allows a missing plugin nonce (invalid nonce is still rejected). Inputs are unslashed/sanitized; PCP does not treat wc_clean() as a sanitizer.


class WOOMULTI_CURRENCY_F_Plugin_WooCommerce_Smart_COD {
	protected static $settings;

	public function __construct() {
		self::$settings = WOOMULTI_CURRENCY_F_Data::get_ins();
		if ( self::$settings->get_enable() ) {
			add_filter( 'wc_smart_cod_fee', array( $this, 'wc_smart_cod_fee' ) );
		}
	}

	/**
	 * WooCommerce Advanced Free Shipping
	 *
	 * @param $data
	 *
	 * @return mixed
	 */

	public function wc_smart_cod_fee( $extra_fee ) {
		if ( is_numeric( $extra_fee ) ) {
			return wmc_get_price( $extra_fee );
		}

		return $extra_fee;
	}
}