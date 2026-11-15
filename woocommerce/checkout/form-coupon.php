<?php
/**
 * Codul de reducere din sumarul checkout-ului.
 *
 * Suprascrie woocommerce/templates/checkout/form-coupon.php. E un formular separat (în afara formularului
 * de checkout), afișat în sumar — vezi checkout/form-checkout.php. checkout.js îl ascunde la încărcare,
 * îl deschide din `a.showcoupon` și îl trimite prin AJAX; eroarea apare sub câmp (`.coupon-error-notice`).
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 9.8.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! wc_coupons_enabled() ) { // @codingStandardsIgnoreLine.
	return;
}
?>
<div class="checkout-coupon">
	<a href="#" role="button" class="showcoupon checkout-coupon__toggle" aria-controls="woocommerce-checkout-form-coupon" aria-expanded="false">
		<?php esc_html_e( 'Ai un cod de reducere?', 'barbarosi' ); ?>
	</a>

	<form class="checkout_coupon woocommerce-form-coupon checkout-coupon__form" method="post" style="display:none" id="woocommerce-checkout-form-coupon">
		<label for="coupon_code" class="screen-reader-text"><?php esc_html_e( 'Cod de reducere', 'barbarosi' ); ?></label>
		<div class="checkout-coupon__row">
			<input type="text" name="coupon_code" class="input-text" placeholder="<?php esc_attr_e( 'Cod de reducere', 'barbarosi' ); ?>" id="coupon_code" value="" autocomplete="off" />
			<button type="submit" class="btn btn--outline" name="apply_coupon" value="<?php esc_attr_e( 'Aplică', 'barbarosi' ); ?>"><?php esc_html_e( 'Aplică', 'barbarosi' ); ?></button>
		</div>
	</form>
</div>
