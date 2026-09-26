<?php
/**
 * Show widget
 *
 * This template can be overridden by copying it to yourtheme/woo-multi-currency/woo-multi-currency-selector.php
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals, WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput -- Historical WOOMULTI_CURRENCY_F / wmc_ / vi_ / VillaTheme_ prefixes. Cache-compat allows a missing plugin nonce (invalid nonce is still rejected). Inputs are unslashed/sanitized; PCP does not treat wc_clean() as a sanitizer.

$currencies       = $settings->get_list_currencies();
$current_currency = $settings->get_current_currency();
$links            = $settings->get_links();
$currency_name    = get_woocommerce_currencies();
?>
<div class="woo-multi-currency wmc-shortcode">
    <div class="wmc-currency">
        <select class="wmc-nav"
                onchange="this.options[this.selectedIndex].value && (window.location = this.options[this.selectedIndex].value);">
			<?php
			foreach ( $links as $code => $link ) {
				$value = esc_url( $link );
				$name  = $shortcode == 'default' ? $currency_name[ $code ] : ( $shortcode == 'listbox_code' ? $code : '' );
				$name  = apply_filters('wmc_shortcode_currency_display_text', $name, $code);
				?>
                <option <?php selected( $current_currency, $code ) ?> value="<?php echo esc_attr( $value ) ?>"
                                                                      data-currency="<?php echo esc_attr( $code ) ?>">
					<?php echo esc_html( $name ) ?>
                </option>
			<?php } ?>

        </select>
    </div>
</div>