<?php
/**
 * Coșul gol.
 *
 * Suprascrie woocommerce/templates/cart/cart-empty.php. Mesajul implicit (wc_empty_cart_message) e scos
 * în Checkout::move_template_parts — clasa `.wc-empty-cart-message` rămâne aici, pentru că după ce
 * se șterge ultimul produs cart.js o caută ca să înlocuiască coșul prin AJAX.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 7.0.1
 */

defined( 'ABSPATH' ) || exit;

/*
 * @hooked woocommerce_output_all_notices - 5
 */
do_action( 'woocommerce_cart_is_empty' );
?>

<div class="wc-empty-cart-message cart-empty">
	<?php echo Checkout::icon( 'bag', 'cart-empty__icon' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

	<h1 class="cart-empty__title"><?php esc_html_e( 'Coșul tău e gol', 'barbarosi' ); ?></h1>
	<p class="cart-empty__text"><?php esc_html_e( 'Nu ai adăugat încă niciun produs. Descoperă noutățile și piesele de bază din colecție.', 'barbarosi' ); ?></p>

	<?php if ( wc_get_page_id( 'shop' ) > 0 ) : ?>
		<a class="btn btn--primary wc-backward" href="<?php echo esc_url( apply_filters( 'woocommerce_return_to_shop_redirect', wc_get_page_permalink( 'shop' ) ) ); ?>">
			<?php
			/** This filter is documented in woocommerce/templates/cart/cart-empty.php */
			echo esc_html( apply_filters( 'woocommerce_return_to_shop_text', __( 'Continuă cumpărăturile', 'barbarosi' ) ) );
			?>
		</a>
	<?php endif; ?>
</div>
