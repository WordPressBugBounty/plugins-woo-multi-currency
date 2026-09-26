<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals, WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput -- Historical WOOMULTI_CURRENCY_F / wmc_ / vi_ / VillaTheme_ prefixes. Cache-compat allows a missing plugin nonce (invalid nonce is still rejected). Inputs are unslashed/sanitized; PCP does not treat wc_clean() as a sanitizer.
 // Exit if accessed directly
if ( ! is_plugin_active( 'elementor/elementor.php' ) ) {
	return;
}
// The Widget_Base class is not available immediately after plugins are loaded, so
// we delay the class' use until Elementor widgets are registered
if ( version_compare( ELEMENTOR_VERSION, '3.5.0', '>=' )){
	add_action( 'elementor/widgets/register', function () {
		require_once( 'widget.php' );

		$drop_down_widget = new WOOMULTI_CURRENCY_Elementor_Widget();

		Elementor\Plugin::instance()->widgets_manager->register( $drop_down_widget );
	} );
} else {
	add_action( 'elementor/widgets/widgets_registered', function () {
		require_once( 'widget.php' );

		$drop_down_widget = new WOOMULTI_CURRENCY_Elementor_Widget();

		Elementor\Plugin::instance()->widgets_manager->register_widget_type( $drop_down_widget );
	} );
}