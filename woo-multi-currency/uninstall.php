<?php
/**
 * Uninstall CURCY - Multi Currency for WooCommerce (free).
 *
 * @package woo-multi-currency
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- Uninstall script runs in isolation.

delete_option( 'woo_multi_currency_params' );
delete_option( 'woo_multi_currency_old_version' );
delete_option( 'wmc_selected_currencies' );
delete_option( 'wmc_currency_by_country' );
delete_option( 'wmc_oder_id' );
delete_option( 'wmc_email' );
delete_option( 'wmc_currency_unit_check' );
delete_transient( 'wmc_update_exchange_rate' );
