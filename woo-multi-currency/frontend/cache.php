<?php

/**
 * Class WOOMULTI_CURRENCY_F_Frontend_Cache
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WOOMULTI_CURRENCY_F_Frontend_Cache {
	protected $settings;
	protected $price_args;
	protected static $theme_data;

	public function __construct() {
		$this->settings = WOOMULTI_CURRENCY_F_Data::get_ins();
		if ( $this->settings->get_enable() ) {
			add_action( 'init', array( $this, 'clear_browser_cache' ) );
			add_action( 'wp_ajax_wmc_get_products_price', array( $this, 'get_products_price' ) );
			add_action( 'wp_ajax_nopriv_wmc_get_products_price', array( $this, 'get_products_price' ) );
			if ( $this->settings->get_param( 'cache_compatible' ) ) {
				add_filter( 'woocommerce_get_price_html', array( $this, 'compatible_cache_plugin' ), PHP_INT_MAX, 2 );
			}
		}
	}

	/**
	 * @param $price
	 * @param $product WC_Product
	 *
	 * @return string
	 */
	public function compatible_cache_plugin( $price, $product ) {
		if ( wp_doing_ajax() ) {
			return $price;
		} else {
			if ( ! self::$theme_data ) {
				self::$theme_data = wp_get_theme();
			}
			if ( self::$theme_data && is_object( self::$theme_data ) ) {
				if ( in_array( self::$theme_data->name, array( 'Woodmart Child', 'Woodmart', 'Goya', 'Alukas' ), true ) ) {
					$requested_with = isset( $_SERVER['HTTP_X_REQUESTED_WITH'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_REQUESTED_WITH'] ) ) : '';
					$is_ajax        = '' !== $requested_with && strcasecmp( $requested_with, 'xmlhttprequest' ) === 0;
					if ( $is_ajax ) {
						return $price;
					}
				}
			}
		}

		if ( ! is_object( $product ) || ! method_exists( $product, 'get_id' ) ) {
			return $price;
		}

		$price_text = is_string( $price ) ? $price : '';
		$wrap       = 'span';
		if ( strpos( $price_text, '<div' ) !== false || strpos( $price_text, '<p' ) !== false ) {
			$wrap = 'div';
		}

		$loading = $this->settings->get_param( 'loading_price_mask' ) ? 'wmc-cache-loading' : '';
		$inner   = is_scalar( $price ) ? (string) $price : '';

		return '<' . $wrap . ' class="wmc-cache-pid ' . $loading . '" data-wmc_product_id="' . $product->get_id() . '">' . $inner . '</' . $wrap . '>';
	}

	/**
	 * Clear cache browser
	 */
	public function clear_browser_cache() {
		if ( isset( $_REQUEST['_woo_multi_currency_nonce'] ) && ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['_woo_multi_currency_nonce'] ) ), 'wmc_currency_nonce' ) ) {
			return;
		}
		if ( isset( $_GET['wmc-currency'] ) ) {
			header( 'Cache-Control: no-cache, must-revalidate' );
			header( 'Expires: Mon, 26 Jul 1997 05:00:00 GMT' );
			header( 'Content-Type: application/xml; charset=utf-8' );
		}
	}

	public function get_products_price() {
		if ( isset( $_REQUEST['_woo_multi_currency_nonce'] ) && ! check_ajax_referer( 'wmc_currency_nonce', '_woo_multi_currency_nonce' ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Nonce invalid', 'woo-multi-currency' ) ) );
		}
		do_action( 'wmc_get_products_price_ajax_handle_before' );
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$pids = ! empty( $_POST['pids'] ) ? wc_clean( wp_unslash( $_POST['pids'] ) ) : array();
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$shortcodes = ! empty( $_POST['shortcodes'] ) ? wc_clean( wp_unslash( $_POST['shortcodes'] ) ) : array();
		if ( ! is_array( $pids ) ) {
			$pids = array();
		}
		if ( ! is_array( $shortcodes ) ) {
			$shortcodes = array();
		}
		$current_currency = $this->settings->get_current_currency();
		$list_currencies  = $this->settings->get_list_currencies();
		$result           = array( 'shortcodes' => array() );

		$data   = ( is_array( $list_currencies ) && isset( $list_currencies[ $current_currency ] ) && is_array( $list_currencies[ $current_currency ] ) ) ? $list_currencies[ $current_currency ] : array();
		$format = WOOMULTI_CURRENCY_F_Data::get_price_format( isset( $data['pos'] ) ? $data['pos'] : '' );
		$args   = array( 'currency' => $current_currency, 'price_format' => $format );

		if ( isset( $data['decimals'] ) ) {
			$args['decimals'] = absint( $data['decimals'] );
		}

		if ( ! empty( $pids ) ) {
			$this->price_args = $args;
			add_filter( 'wc_price_args', array( $this, 'change_price_format_by_specific_currency' ), PHP_INT_MAX );
			foreach ( $pids as $pid ) {
				$product = wc_get_product( $pid );
				if ( ! $product ) {
					continue;
				}
				$product_id  = $product->get_id();
				$viewable_id = $product->get_parent_id() ? $product->get_parent_id() : $product_id;
				if ( ! is_post_publicly_viewable( $viewable_id ) && ! current_user_can( 'read_post', $viewable_id ) ) {
					continue;
				}
				if ( $product->is_type( 'variation' ) ) {
					$result['prices'][ $pid ] = '<span class="price">' . $product->get_price_html() . '</span>';
				} else {
					$result['prices'][ $pid ] = $product->get_price_html();
				}
			}
			remove_filter( 'wc_price_args', array( $this, 'change_price_format_by_specific_currency' ), PHP_INT_MAX );
			$this->price_args = array();
		}

		$result['current_currency'] = $current_currency;
		$country                    = $this->settings->get_country_data( $current_currency );
		$country_code               = ( is_array( $country ) && isset( $country['code'] ) && ( is_string( $country['code'] ) || is_numeric( $country['code'] ) ) ) ? (string) $country['code'] : '';
		$result['current_country']  = strtolower( $country_code );
		$shortcodes_list            = $this->settings->get_list_shortcodes();

		if ( is_array( $shortcodes ) && count( $shortcodes ) ) {
			foreach ( $shortcodes as $shortcode ) {
				if ( ! is_array( $shortcode ) ) {
					$shortcode = array();
				}
				$layout_value = isset( $shortcode['layout'] ) ? $shortcode['layout'] : '';
				$sc_layout    = ( is_string( $layout_value ) || is_numeric( $layout_value ) ) ? sanitize_text_field( (string) $layout_value ) : '';
				if ( isset( $shortcodes_list[ $sc_layout ] ) ) {
					$flag_size     = isset( $shortcode['flag_size'] ) ? floatval( $shortcode['flag_size'] ) : '';
					$dropdown_icon = isset( $shortcode['dropdown_icon'] ) ? sanitize_text_field( $shortcode['dropdown_icon'] ) : '';
					$custom_format = isset( $shortcode['custom_format'] ) ? absint( $shortcode['custom_format'] ) : '';
					if ( $flag_size ) {
						$flag_size = "flag_size='{$flag_size}'";
					}
					if ( ! in_array( $dropdown_icon, array( 'arrow', 'triangle' ), true ) ) {
						$dropdown_icon = '';
					}
					$result['shortcodes'][] = do_shortcode( "[woo_multi_currency_" . esc_attr( $sc_layout ) . " {$flag_size} dropdown_icon='{$dropdown_icon}' custom_format='{$custom_format}']" );
				} else {
					$result['shortcodes'][] = do_shortcode( '[woo_multi_currency]' );
				}
			}
		}

		if ( ! empty( $_POST['exchange'] ) ) {
			$exchange_sc      = array();
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$exchange_arr     = wc_clean( wp_unslash( $_POST['exchange'] ) );
			$list_currencies  = $this->settings->get_list_currencies();
			$current_currency = $this->settings->get_current_currency();
			if ( ! is_array( $exchange_arr ) ) {
				$exchange_arr = array();
			}
			foreach ( $exchange_arr as $ex ) {
				if ( ! is_array( $ex ) ) {
					continue;
				}
				$ex_sc_product_id     = isset( $ex['product_id'] ) ? absint( $ex['product_id'] ) : '';
				$ex_sc_keep_format    = isset( $ex['keep_format'] ) ? absint( $ex['keep_format'] ) : '';
				$ex_sc_price          = isset( $ex['price'] ) ? floatval( $ex['price'] ) : '';
				$ex_sc_original_price = isset( $ex['original_price'] ) ? floatval( $ex['original_price'] ) : '';
				$ex_sc_currency       = isset( $ex['currency'] ) ? sanitize_text_field( $ex['currency'] ) : '';
				if ( ! isset( $list_currencies[ $ex_sc_currency ] ) ) {
					$ex_sc_currency = $current_currency;
				}
				if ( $ex_sc_product_id ) {
					$product = wc_get_product( $ex_sc_product_id );
					if ( ! $product ) {
						continue;
					}
					$viewable_id = $product->get_parent_id() ? $product->get_parent_id() : $product->get_id();
					if ( ! is_post_publicly_viewable( $viewable_id ) && ! current_user_can( 'read_post', $viewable_id ) ) {
						continue;
					}
				}
				$exchange_sc[] = array_merge( $ex, array(
					'shortcode' => do_shortcode( "[woo_multi_currency_exchange product_id='" . esc_attr( $ex_sc_product_id ) . "' keep_format='" . esc_attr( $ex_sc_keep_format ) . "' price='" . esc_attr( $ex_sc_price ) . "' original_price='" . esc_attr( $ex_sc_original_price ) . "' currency='" . esc_attr( $ex_sc_currency ) . "']" ),
				) );
			}
			$result['exchange'] = $exchange_sc;
		}
		do_action( 'wmc_get_products_price_ajax_handle_after' );
		wp_send_json_success( apply_filters( 'wmc_get_products_price_ajax_handle_response', $result ) );
	}

	public function change_price_format_by_specific_currency( $args ) {
		if ( is_array( $this->price_args ) && count( $this->price_args ) ) {
			$args = wp_parse_args( $this->price_args, $args );
		}

		return $args;
	}
}
