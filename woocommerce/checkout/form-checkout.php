<?php
/**
 * Checkout: formularul în pași (contact → adresă → livrare → plată) + sumarul comenzii.
 *
 * Suprascrie woocommerce/templates/checkout/form-checkout.php.
 *
 * Sumarul (`woocommerce_order_review` + codul de reducere) stă în afara formularului, în coloana din
 * dreapta (pe mobil: pliat, deasupra formularului). În `#order_review` rămâne doar plata — vezi
 * Checkout::move_template_parts. checkout.js înlocuiește prin AJAX `.woocommerce-checkout-review-order-table`,
 * `.woocommerce-checkout-payment` și `.checkout-shipping`, oriunde s-ar afla în pagină.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 9.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

do_action( 'woocommerce_before_checkout_form', $checkout );

// If checkout registration is disabled and not logged in, the user cannot checkout.
if ( ! $checkout->is_registration_enabled() && $checkout->is_registration_required() && ! is_user_logged_in() ) {
	echo esc_html( apply_filters( 'woocommerce_checkout_must_be_logged_in_message', __( 'Trebuie să fii autentificat ca să finalizezi comanda.', 'barbarosi' ) ) );
	return;
}
?>

<div class="checkout-layout">
	<aside class="checkout-layout__summary checkout-summary" x-data="{ open: false }" aria-labelledby="checkout-summary-title">
		<button
			type="button"
			class="checkout-summary__toggle"
			@click="open = !open"
			:aria-expanded="open.toString()"
			aria-expanded="false"
			aria-controls="checkout-summary-panel"
		>
			<span class="checkout-summary__toggle-label">
				<?php echo Checkout::icon( 'bag' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<span x-show="!open"><?php esc_html_e( 'Vezi sumarul comenzii', 'barbarosi' ); ?></span>
				<span x-show="open" x-cloak><?php esc_html_e( 'Ascunde sumarul', 'barbarosi' ); ?></span>
				<?php echo Checkout::icon( 'chevron', 'checkout-summary__chevron' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</span>
			<span class="checkout-summary__toggle-total" data-summary-total><?php echo wp_kses_post( WC()->cart->get_total() ); ?></span>
		</button>

		<div class="checkout-summary__panel woocommerce-checkout-review-order" id="checkout-summary-panel" :class="{ 'is-open': open }">
			<h2 class="checkout-summary__title" id="checkout-summary-title"><?php esc_html_e( 'Sumarul comenzii', 'barbarosi' ); ?></h2>

			<?php woocommerce_order_review(); ?>
			<?php woocommerce_checkout_coupon_form(); ?>
		</div>
	</aside>

	<form name="checkout" method="post" class="checkout woocommerce-checkout checkout-layout__main" action="<?php echo esc_url( wc_get_checkout_url() ); ?>" enctype="multipart/form-data" aria-label="<?php esc_attr_e( 'Finalizare comandă', 'barbarosi' ); ?>">

		<?php if ( $checkout->get_checkout_fields() ) : ?>

			<?php do_action( 'woocommerce_checkout_before_customer_details' ); ?>

			<div id="customer_details" class="checkout-details">
				<?php do_action( 'woocommerce_checkout_billing' ); ?>
				<?php do_action( 'woocommerce_checkout_shipping' ); ?>
			</div>

			<?php do_action( 'woocommerce_checkout_after_customer_details' ); ?>

		<?php endif; ?>

		<?php if ( WC()->cart->needs_shipping() ) : ?>
			<section class="checkout-section" aria-labelledby="checkout-shipping-title">
				<h2 class="checkout-section__title" id="checkout-shipping-title"><?php esc_html_e( 'Metoda de livrare', 'barbarosi' ); ?></h2>
				<?php echo Checkout::shipping_methods_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</section>
		<?php endif; ?>

		<?php do_action( 'woocommerce_checkout_before_order_review_heading' ); ?>

		<section class="checkout-section" aria-labelledby="order_review_heading">
			<h2 class="checkout-section__title" id="order_review_heading"><?php esc_html_e( 'Plata', 'barbarosi' ); ?></h2>

			<?php do_action( 'woocommerce_checkout_before_order_review' ); ?>

			<div id="order_review">
				<?php
				/**
				 * @hooked woocommerce_checkout_payment - 20
				 * (woocommerce_order_review e afișat în sumar, nu aici)
				 */
				do_action( 'woocommerce_checkout_order_review' );
				?>
			</div>

			<?php do_action( 'woocommerce_checkout_after_order_review' ); ?>
		</section>
	</form>
</div>

<?php do_action( 'woocommerce_after_checkout_form', $checkout ); ?>
