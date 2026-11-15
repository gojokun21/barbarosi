<?php
/**
 * Livrarea la altă adresă + observațiile comenzii.
 *
 * Suprascrie woocommerce/templates/checkout/form-shipping.php. checkout.js afișează / ascunde
 * `div.shipping_address` după `#ship-to-different-address input` — id-ul și clasa rămân.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 11.2.0
 * @global WC_Checkout $checkout
 */

defined( 'ABSPATH' ) || exit;

// Observațiile sunt pliate; le deschidem dacă au deja conținut sau dacă un plugin a adăugat un câmp obligatoriu.
$order_fields = $checkout->get_checkout_fields( 'order' );
$notes_open   = (bool) array_filter(
	$order_fields,
	static fn( $field, $key ) => ! empty( $field['required'] ) || '' !== (string) $checkout->get_value( $key ),
	ARRAY_FILTER_USE_BOTH
);
?>
<div class="woocommerce-shipping-fields">
	<?php if ( true === WC()->cart->needs_shipping_address() ) : ?>

		<div id="ship-to-different-address" class="checkout-ship-toggle">
			<label class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox">
				<input id="ship-to-different-address-checkbox" class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox" <?php checked( apply_filters( 'woocommerce_ship_to_different_address_checked', 'shipping' === get_option( 'woocommerce_ship_to_destination' ) ? 1 : 0 ), 1 ); ?> type="checkbox" name="ship_to_different_address" value="1" /> <span><?php esc_html_e( 'Livrează la o altă adresă', 'barbarosi' ); ?></span>
			</label>
		</div>

		<div class="shipping_address checkout-section" role="group" aria-labelledby="checkout-shipping-address-title">
			<h2 class="checkout-section__title" id="checkout-shipping-address-title"><?php esc_html_e( 'Adresa de livrare', 'barbarosi' ); ?></h2>

			<?php do_action( 'woocommerce_before_checkout_shipping_form', $checkout ); ?>

			<div class="woocommerce-shipping-fields__field-wrapper checkout-fields">
				<?php
				$fields = $checkout->get_checkout_fields( 'shipping' );

				foreach ( $fields as $key => $field ) {
					woocommerce_form_field( $key, $field, $checkout->get_value( $key ) );
				}
				?>
			</div>

			<?php do_action( 'woocommerce_after_checkout_shipping_form', $checkout ); ?>
		</div>

	<?php endif; ?>
</div>

<div class="woocommerce-additional-fields">
	<?php do_action( 'woocommerce_before_order_notes', $checkout ); ?>

	<?php if ( ! empty( $order_fields ) ) : ?>
		<details class="checkout-notes" <?php echo $notes_open ? 'open' : ''; ?>>
			<summary class="checkout-notes__toggle"><?php esc_html_e( 'Adaugă observații la comandă', 'barbarosi' ); ?></summary>

			<div class="woocommerce-additional-fields__field-wrapper checkout-fields">
				<?php foreach ( $order_fields as $key => $field ) : ?>
					<?php woocommerce_form_field( $key, $field, $checkout->get_value( $key ) ); ?>
				<?php endforeach; ?>
			</div>
		</details>
	<?php endif; ?>

	<?php do_action( 'woocommerce_after_order_notes', $checkout ); ?>
</div>
