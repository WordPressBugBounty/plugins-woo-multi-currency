<?php

/**
 * Class WOOMULTI_CURRENCY_F_Plugin_WooCommerce_Product_Bundles
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals, WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput -- Historical WOOMULTI_CURRENCY_F / wmc_ / vi_ / VillaTheme_ prefixes. Cache-compat allows a missing plugin nonce (invalid nonce is still rejected). Inputs are unslashed/sanitized; PCP does not treat wc_clean() as a sanitizer.


class WOOMULTI_CURRENCY_F_Plugin_WooCommerce_Product_Bundles {
	protected $settings;

	public function __construct() {

		$this->settings = WOOMULTI_CURRENCY_F_Data::get_ins();
		if ( $this->settings->get_enable() ) {
			add_filter( 'woocommerce_bundle_front_end_params', array( $this, 'woocommerce_bundle_front_end_params' ) );
		}
	}

	/**
	 * Integrate with Yith Product Bundles
	 * @return bool
	 */
	public function woocommerce_bundle_front_end_params( $data ) {
		if ( isset( $data['currency_symbol'] ) ) {
			preg_match( '/#PRICE#/i', $data['currency_symbol'], $result );
			if ( count( array_filter( $result ) ) ) {
				$data['currency_symbol'] = str_replace( '#PRICE#', '', $data['currency_symbol'] );
			}
		}

		return $data;
	}
}