<?php
/**
 * Metodele de livrare pentru un pachet, ca listă de opțiuni (carduri cu radio).
 *
 * Suprascrie woocommerce/templates/cart/cart-shipping.php. În temă e afișat doar în secțiunea
 * „Metodă de livrare” din checkout (Checkout::shipping_methods_html), nu în tabelul de totaluri.
 * Input-urile `shipping_method[...]` trebuie să rămână în formular — checkout.js le trimite la fiecare actualizare.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 11.2.0
 */

defined( 'ABSPATH' ) || exit;

$formatted_destination   = isset( $formatted_destination ) ? $formatted_destination : WC()->countries->get_formatted_address( $package['destination'], ', ' );
$has_calculated_shipping = ! empty( $has_calculated_shipping );
$include_tax             = WC()->cart->display_prices_including_tax();
?>
<div class="shipping-package woocommerce-shipping-totals shipping">
	<?php if ( $show_package_details ) : ?>
		<p class="shipping-package__name"><?php echo wp_kses_post( $package_name ); ?></p>
	<?php endif; ?>

	<?php if ( ! empty( $available_methods ) && is_array( $available_methods ) ) : ?>
		<ul id="shipping_method" class="woocommerce-shipping-methods choice-list" role="list">
			<?php foreach ( $available_methods as $method ) : ?>
				<?php
				$input_id = sprintf( 'shipping_method_%d_%s', $index, sanitize_title( $method->id ) );
				$cost     = (float) $method->get_cost() + ( $include_tax ? (float) $method->get_shipping_tax() : 0 );
				$label    = sprintf(
					'<span class="choice__title">%s</span><span class="choice__aside">%s</span>',
					esc_html( $method->get_label() ),
					$cost > 0 ? wc_price( $cost ) : esc_html__( 'Gratuit', 'barbarosi' )
				);
				?>
				<li class="choice">
					<?php
					if ( 1 < count( $available_methods ) ) {
						printf( '<input type="radio" name="shipping_method[%1$d]" data-index="%1$d" id="%2$s" value="%3$s" class="shipping_method choice__input" %4$s />', (int) $index, esc_attr( $input_id ), esc_attr( $method->id ), checked( $method->id, $chosen_method, false ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					} else {
						printf( '<input type="hidden" name="shipping_method[%1$d]" data-index="%1$d" id="%2$s" value="%3$s" class="shipping_method" />', (int) $index, esc_attr( $input_id ), esc_attr( $method->id ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					}

					/** This filter is documented in woocommerce/includes/wc-cart-functions.php */
					printf( '<label class="choice__label" for="%s">%s</label>', esc_attr( $input_id ), apply_filters( 'woocommerce_cart_shipping_method_full_label', $label, $method ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

					do_action( 'woocommerce_after_shipping_rate', $method, $index );
					?>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php elseif ( ! $has_calculated_shipping || ! $formatted_destination ) : ?>
		<p class="checkout-shipping__message">
			<?php echo wp_kses_post( apply_filters( 'woocommerce_shipping_may_be_available_html', __( 'Completează adresa pentru a vedea opțiunile de livrare.', 'barbarosi' ) ) ); ?>
		</p>
	<?php else : ?>
		<p class="checkout-shipping__message">
			<?php echo wp_kses_post( apply_filters( 'woocommerce_no_shipping_available_html', __( 'Momentan nu livrăm la adresa introdusă. Verifică adresa sau scrie-ne și găsim o soluție.', 'barbarosi' ) ) ); ?>
		</p>
	<?php endif; ?>

	<?php if ( $show_package_details ) : ?>
		<p class="shipping-package__contents"><?php echo esc_html( $package_details ); ?></p>
	<?php endif; ?>
</div>
