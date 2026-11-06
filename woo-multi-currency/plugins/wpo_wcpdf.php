<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Class WPO_WCPDF
 * Author: WP Overnight
 */
class WOOMULTI_CURRENCY_Plugin_WPO_WCPDF {
	protected static $settings, $cache =[];

	public function __construct() {
		if (  !class_exists( 'WPO_WCPDF' ) ) {
			return;
		}
		self::$settings = WOOMULTI_CURRENCY_F_Data::get_ins();
		if ( !self::$settings->get_enable() ) {
			return;
		}
//		add_filter('wmc_set_decimals',[ $this, 'set_decimals' ], 10,2 );
	}
	public function set_decimals( $decimals ){
		global $pagenow;
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- WPO PDF plugin owns generate_wpo_wcpdf request verification.
		$wpo_action    = isset( $_REQUEST['action'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['action'] ) ) : '';
		$wpo_order_ids = isset( $_REQUEST['order_ids'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['order_ids'] ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
		if ( $pagenow === 'admin-ajax.php' && $wpo_action === 'generate_wpo_wcpdf' && $wpo_order_ids ) {
			$order_id = absint( $wpo_order_ids );
			if ( ! $order_id || isset( self::$cache['get_order_decimals'][ $order_id ] ) ) {
				return $decimals;
			}
			if ( isset( self::$cache['order_decimals'][ $order_id ] ) ) {
				return self::$cache['order_decimals'][ $order_id ];
			}
			if ( ! isset( self::$cache['order_decimals'] ) ) {
				self::$cache['order_decimals'] = [];
			}
			if ( ! isset( self::$cache['get_order_decimals'] ) ) {
				self::$cache['get_order_decimals'] = [];
			}
			self::$cache['get_order_decimals'][ $order_id ] = 1;
			$wc_order = wc_get_order( $order_id );
			if ( $wc_order ) {
				$order_currency = $wc_order->get_currency();
				$order_info     = $wc_order->get_meta( 'wmc_order_info', true );
			} else {
				$order_currency = '';
				$order_info     = array();
			}
			if ( isset( $order_info[ $order_currency ]['decimals'] ) ) {
				$decimals = $order_info[ $order_currency ]['decimals'];
			}
			unset( self::$cache['get_order_decimals'][ $order_id ] );
			self::$cache['order_decimals'][ $order_id ] = (int) $decimals;
		}
		return  $decimals;
	}
}