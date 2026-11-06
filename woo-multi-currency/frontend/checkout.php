<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WOOMULTI_CURRENCY_F_Frontend_Checkout
 */
class WOOMULTI_CURRENCY_F_Frontend_Checkout {
	public $settings;
	public $rate;

	public function __construct() {
		$this->settings = WOOMULTI_CURRENCY_F_Data::get_ins();
		if ( $this->settings->get_enable() ) {
			add_action( 'woocommerce_checkout_update_order_review', array(
				$this,
				'woocommerce_checkout_update_order_review'
			), 99 );
			add_action( 'woocommerce_checkout_create_order', array(
				$this,
				'enforce_default_order_currency'
			), 10, 2 );
			add_action( 'woocommerce_store_api_checkout_update_order_from_request', array(
				$this,
				'store_api_checkout_update_order_from_request'
			), 10, 2 );

			//Set order currency correctly
			add_filter( 'woocommerce_paypal_args', array( $this, 'woocommerce_paypal_args' ), 10, 2 );
			add_filter( 'woocommerce_twoco_args', array( $this, 'woocommerce_twoco_args' ) );
		}
	}

	/**
	 * When Pay in many currencies is off, order currency must match default (totals currency).
	 *
	 * @param WC_Order $order Order being created or updated.
	 */
	public function enforce_default_order_currency( $order ) {
		if ( ! $order instanceof WC_Order || $this->settings->get_enable_multi_payment() ) {
			return;
		}
		$default_currency = $this->settings->get_default_currency();
		if ( ! $default_currency ) {
			return;
		}
		if ( $this->settings->get_current_currency() !== $default_currency ) {
			$this->settings->set_current_currency( $default_currency, false );
		}
		if ( $order->get_currency() !== $default_currency ) {
			$order->set_currency( $default_currency );
		}
	}

	/**
	 * Enforce default currency on Store API checkout when multi-currency payment is disabled.
	 *
	 * @param WC_Order        $order   Order being updated.
	 * @param WP_REST_Request $request Request object.
	 */
	public function store_api_checkout_update_order_from_request( $order, $request ) {
		$this->enforce_default_order_currency( $order );
	}

	/**
	 * Change currency to default and reload page if the payment method is the new PayPal gateway
	 *
	 * @param $data
	 */
	public function woocommerce_checkout_update_order_review( $data ) {
		if ( isset( $_REQUEST['_woo_multi_currency_nonce'] ) && ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['_woo_multi_currency_nonce'] ) ), 'woo_multi_currency_checkout' ) ) {
			return;
		}
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$payment_method   = isset( $_POST['payment_method'] ) ? wc_clean( wp_unslash( $_POST['payment_method'] ) ) : '';
		$current_currency = $this->settings->get_current_currency();
		if ( $payment_method === 'ppcp-gateway' ) {
			$default_currency = $this->settings->get_default_currency();
			if ( ! $this->settings->get_enable_multi_payment() && ( $current_currency !== $default_currency || $this->settings->getcookie( 'wmc_current_currency_old' ) !== $default_currency ) ) {
				$this->settings->set_current_currency( $default_currency, true );
				$this->reload_after_update_order_review( true );
			}
		} elseif ( ! $this->settings->get_enable_multi_payment() ) {
			$default_currency = $this->settings->get_default_currency();
			if ( $current_currency !== $default_currency ) {
				$this->settings->set_current_currency( $default_currency, false );
			}
		}
	}

	/**
	 * @param bool $reload
	 * @param bool $update_checkout
	 */
	public function reload_after_update_order_review( $reload = false, $update_checkout = false ) {
		WC()->cart->calculate_shipping();
		WC()->cart->calculate_totals();
		// Get order review fragment
		ob_start();
		woocommerce_order_review();
		$woocommerce_order_review = ob_get_clean();

		// Get checkout payment fragment
		ob_start();
		woocommerce_checkout_payment();
		$woocommerce_checkout_payment = ob_get_clean();
		$args                         = array(
			'result'              => 'success',
			'messages'            => '',
			'reload'              => $reload,
			'wmc_update_checkout' => $update_checkout,
			'fragments'           => apply_filters(
				'woocommerce_update_order_review_fragments', array(
					'.woocommerce-checkout-review-order-table' => $woocommerce_order_review,
					'.woocommerce-checkout-payment'            => $woocommerce_checkout_payment,
				)
			),
		);
		if ( is_plugin_active( 'checkout-for-woocommerce/checkout-for-woocommerce.php' ) ) {
			$_cfw__settings = get_option( '_cfw__settings' );
			if ( isset( $_cfw__settings['enable'] ) && $_cfw__settings['enable'] === 'yes' ) {
				$args['redirect'] = wc_get_checkout_url();
			}
		}

		unset( WC()->session->refresh_totals, WC()->session->reload_checkout );
		wp_send_json( $args );
	}

	/**
	 * PayPal args
	 *
	 * @param $payment_args
	 * @param $order WC_Order
	 *
	 * @return mixed
	 */
	public function woocommerce_paypal_args( $payment_args, $order ) {
		if ( isset( $_REQUEST['_woo_multi_currency_nonce'] ) && ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['_woo_multi_currency_nonce'] ) ), 'woo_multi_currency_checkout' ) ) {
			return $payment_args;
		}
		if ( ! empty( $_GET['pay_for_order'] ) ) {
			$payment_args['currency_code'] = $order->get_currency();
		}

		return $payment_args;
	}

	/**
	 * WooCommerce 2Checkout Payment Gateway
	 *
	 * @param $payment_args
	 *
	 * @return mixed
	 */
	public function woocommerce_twoco_args( $payment_args ) {
		if ( isset( $_REQUEST['_woo_multi_currency_nonce'] ) && ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['_woo_multi_currency_nonce'] ) ), 'woo_multi_currency_checkout' ) ) {
			return $payment_args;
		}
		if ( ! empty( $_GET['pay_for_order'] ) ) {
			$order_id = isset( $payment_args['merchant_order_id'] ) ? $payment_args['merchant_order_id'] : '';
			if ( $order_id ) {
				$order = wc_get_order( $order_id );
				if ( $order ) {
					$payment_args['currency_code'] = $order->get_currency();
				}
			}
		}

		return $payment_args;
	}
}
