<?php
/**
 * Sumarul comenzii din checkout: produsele (cu poză și cantitate) + totalurile.
 *
 * Suprascrie woocommerce/templates/checkout/review-order.php. Elementul rădăcină trebuie să aibă clasa
 * `woocommerce-checkout-review-order-table`: checkout.js îl înlocuiește cu răspunsul `update_order_review`.
 * Metoda de livrare se alege în formular (Checkout::shipping_methods_html); aici apare doar costul.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 11.2.2
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="woocommerce-checkout-review-order-table order-summary">
	<ul class="order-summary__items" role="list">
		<?php
		do_action( 'woocommerce_review_order_before_cart_contents' );

		foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
			$_product = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );

			/** This filter is documented in woocommerce/templates/checkout/review-order.php */
			$visible = apply_filters( 'woocommerce_checkout_cart_item_visible', true, $cart_item, $cart_item_key );

			if ( ! $_product instanceof WC_Product || ! $_product->exists() || $cart_item['quantity'] <= 0 || ! $visible ) {
				continue;
			}

			$cart_item_name = Checkout::item_name( $cart_item, $_product );
			/** This filter is documented in woocommerce/templates/cart/cart.php */
			$product_name = apply_filters( 'woocommerce_cart_item_name', $cart_item_name, $cart_item, $cart_item_key );
			?>
			<li class="order-summary__item <?php echo esc_attr( apply_filters( 'woocommerce_cart_item_class', 'cart_item', $cart_item, $cart_item_key ) ); ?>">
				<div class="order-summary__media">
					<?php echo Checkout::item_thumbnail( $_product, $cart_item, $cart_item_key, '64px' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php
					// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment -- hook WooCommerce existent
					echo apply_filters( 'woocommerce_checkout_cart_item_quantity', '<span class="order-summary__qty"><span class="screen-reader-text">' . esc_html__( 'Cantitate:', 'barbarosi' ) . ' </span>' . esc_html( $cart_item['quantity'] ) . '</span>', $cart_item, $cart_item_key ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					?>
				</div>

				<div class="order-summary__info">
					<p class="order-summary__name"><?php echo wp_kses_post( $product_name ); ?></p>
					<?php echo wc_get_formatted_cart_item_data( $cart_item, false, $cart_item_name ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>

				<p class="order-summary__price">
					<?php echo apply_filters( 'woocommerce_cart_item_subtotal', WC()->cart->get_product_subtotal( $_product, $cart_item['quantity'] ), $cart_item, $cart_item_key ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</p>
			</li>
			<?php
		}

		do_action( 'woocommerce_review_order_after_cart_contents' );
		?>
	</ul>

	<table class="summary-totals">
		<tr class="cart-subtotal">
			<th scope="row"><?php esc_html_e( 'Subtotal', 'barbarosi' ); ?></th>
			<td><?php wc_cart_totals_subtotal_html(); ?></td>
		</tr>

		<?php foreach ( WC()->cart->get_coupons() as $code => $coupon ) : ?>
			<tr class="cart-discount coupon-<?php echo esc_attr( sanitize_title( $code ) ); ?>">
				<th scope="row"><?php wc_cart_totals_coupon_label( $coupon ); ?></th>
				<td><?php wc_cart_totals_coupon_html( $coupon ); ?></td>
			</tr>
		<?php endforeach; ?>

		<?php if ( WC()->cart->needs_shipping() ) : ?>
			<?php do_action( 'woocommerce_review_order_before_shipping' ); ?>

			<?php $shipping_total = Checkout::shipping_total_html(); ?>
			<tr class="shipping">
				<th scope="row"><?php esc_html_e( 'Livrare', 'barbarosi' ); ?></th>
				<td>
					<?php if ( null === $shipping_total ) : ?>
						<span class="summary-totals__muted"><?php esc_html_e( 'Completează adresa', 'barbarosi' ); ?></span>
					<?php else : ?>
						<?php echo wp_kses_post( $shipping_total ); ?>
					<?php endif; ?>
				</td>
			</tr>

			<?php do_action( 'woocommerce_review_order_after_shipping' ); ?>
		<?php endif; ?>

		<?php foreach ( WC()->cart->get_fees() as $fee ) : ?>
			<tr class="fee">
				<th scope="row"><?php echo esc_html( $fee->name ); ?></th>
				<td><?php wc_cart_totals_fee_html( $fee ); ?></td>
			</tr>
		<?php endforeach; ?>

		<?php if ( wc_tax_enabled() && ! WC()->cart->display_prices_including_tax() ) : ?>
			<?php if ( 'itemized' === get_option( 'woocommerce_tax_total_display' ) ) : ?>
				<?php foreach ( WC()->cart->get_tax_totals() as $code => $tax ) : // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited ?>
					<tr class="tax-rate tax-rate-<?php echo esc_attr( sanitize_title( $code ) ); ?>">
						<th scope="row"><?php echo esc_html( $tax->label ); ?></th>
						<td><?php echo wp_kses_post( $tax->formatted_amount ); ?></td>
					</tr>
				<?php endforeach; ?>
			<?php else : ?>
				<tr class="tax-total">
					<th scope="row"><?php echo esc_html( WC()->countries->tax_or_vat() ); ?></th>
					<td><?php wc_cart_totals_taxes_total_html(); ?></td>
				</tr>
			<?php endif; ?>
		<?php endif; ?>

		<?php do_action( 'woocommerce_review_order_before_order_total' ); ?>

		<tr class="order-total">
			<th scope="row"><?php esc_html_e( 'Total', 'barbarosi' ); ?></th>
			<td data-order-total><?php wc_cart_totals_order_total_html(); ?></td>
		</tr>

		<?php do_action( 'woocommerce_review_order_after_order_total' ); ?>
	</table>
</div>
