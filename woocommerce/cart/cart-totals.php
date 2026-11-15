<?php
/**
 * Sumarul coșului: livrare gratuită, totaluri, „Finalizează comanda”, garanții.
 *
 * Suprascrie woocommerce/templates/cart/cart-totals.php. Containerul `.cart_totals` e înlocuit prin AJAX
 * de cart.js la fiecare modificare a coșului.
 *
 * Livrarea: în coș afișăm doar costul metodei alese (sau „la pasul următor”); metoda se alege în checkout.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 2.3.6
 */

defined( 'ABSPATH' ) || exit;

$free_shipping = Checkout::free_shipping_progress();
?>
<div class="cart_totals summary-card <?php echo ( WC()->customer->has_calculated_shipping() ) ? 'calculated_shipping' : ''; ?>">

	<?php do_action( 'woocommerce_before_cart_totals' ); ?>

	<h2 class="summary-card__title"><?php esc_html_e( 'Sumarul comenzii', 'barbarosi' ); ?></h2>

	<?php if ( $free_shipping ) : ?>
		<div class="free-shipping">
			<p class="free-shipping__text">
				<?php
				if ( $free_shipping['remaining'] ) {
					/* translators: %s: suma rămasă */
					printf( esc_html__( 'Mai adaugă produse de %s și ai livrare gratuită.', 'barbarosi' ), '<strong>' . esc_html( $free_shipping['remaining'] ) . '</strong>' );
				} else {
					esc_html_e( 'Comanda ta se califică pentru livrare gratuită.', 'barbarosi' );
				}
				?>
			</p>
			<div class="free-shipping__bar" role="progressbar" aria-label="<?php esc_attr_e( 'Progres spre livrarea gratuită', 'barbarosi' ); ?>" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?php echo esc_attr( $free_shipping['percent'] ); ?>">
				<span style="width: <?php echo esc_attr( $free_shipping['percent'] ); ?>%"></span>
			</div>
		</div>
	<?php endif; ?>

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
			<?php do_action( 'woocommerce_cart_totals_before_shipping' ); ?>

			<?php $shipping_total = Checkout::shipping_total_html(); ?>
			<tr class="shipping">
				<th scope="row"><?php esc_html_e( 'Livrare', 'barbarosi' ); ?></th>
				<td>
					<?php if ( null === $shipping_total ) : ?>
						<span class="summary-totals__muted"><?php esc_html_e( 'Se calculează la pasul următor', 'barbarosi' ); ?></span>
					<?php else : ?>
						<?php echo wp_kses_post( $shipping_total ); ?>
					<?php endif; ?>
				</td>
			</tr>

			<?php do_action( 'woocommerce_cart_totals_after_shipping' ); ?>
		<?php endif; ?>

		<?php foreach ( WC()->cart->get_fees() as $fee ) : ?>
			<tr class="fee">
				<th scope="row"><?php echo esc_html( $fee->name ); ?></th>
				<td><?php wc_cart_totals_fee_html( $fee ); ?></td>
			</tr>
		<?php endforeach; ?>

		<?php
		if ( wc_tax_enabled() && ! WC()->cart->display_prices_including_tax() ) {
			if ( 'itemized' === get_option( 'woocommerce_tax_total_display' ) ) {
				foreach ( WC()->cart->get_tax_totals() as $code => $tax ) { // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
					?>
					<tr class="tax-rate tax-rate-<?php echo esc_attr( sanitize_title( $code ) ); ?>">
						<th scope="row"><?php echo esc_html( $tax->label ); ?></th>
						<td><?php echo wp_kses_post( $tax->formatted_amount ); ?></td>
					</tr>
					<?php
				}
			} else {
				?>
				<tr class="tax-total">
					<th scope="row"><?php echo esc_html( WC()->countries->tax_or_vat() ); ?></th>
					<td><?php wc_cart_totals_taxes_total_html(); ?></td>
				</tr>
				<?php
			}
		}
		?>

		<?php do_action( 'woocommerce_cart_totals_before_order_total' ); ?>

		<tr class="order-total">
			<th scope="row"><?php esc_html_e( 'Total', 'barbarosi' ); ?></th>
			<td><?php wc_cart_totals_order_total_html(); ?></td>
		</tr>

		<?php do_action( 'woocommerce_cart_totals_after_order_total' ); ?>
	</table>

	<div class="wc-proceed-to-checkout">
		<?php do_action( 'woocommerce_proceed_to_checkout' ); ?>
	</div>

	<?php
	Timber::render(
		'partial/cart-assurance.twig',
		[
			'perks'    => DemoContent::product_perks(),
			'payments' => DemoContent::payment_methods(),
		]
	);
	?>

	<?php do_action( 'woocommerce_after_cart_totals' ); ?>

</div>
