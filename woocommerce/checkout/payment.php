<?php
/**
 * Metodele de plată + termenii + „Plasează comanda”.
 *
 * Suprascrie woocommerce/templates/checkout/payment.php. `#payment.woocommerce-checkout-payment` e înlocuit
 * prin AJAX (update_order_review); metodele folosesc template-ul standard checkout/payment-method.php,
 * stilizat ca listă de opțiuni (assets/scss/components/_choice-list.scss).
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 10.9.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! wp_doing_ajax() ) {
	do_action( 'woocommerce_review_order_before_payment' );
}
?>
<div id="payment" class="woocommerce-checkout-payment checkout-payment">
	<?php if ( WC()->cart && WC()->cart->needs_payment() ) : ?>
		<ul class="wc_payment_methods payment_methods methods choice-list" aria-label="<?php esc_attr_e( 'Metode de plată', 'barbarosi' ); ?>">
			<?php
			if ( ! empty( $available_gateways ) ) {
				foreach ( $available_gateways as $gateway ) {
					wc_get_template( 'checkout/payment-method.php', array( 'gateway' => $gateway ) );
				}
			} else {
				echo '<li class="choice-list__empty">';
				wc_print_notice(
					// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment -- hook WooCommerce existent
					apply_filters(
						'woocommerce_no_available_payment_methods_message',
						WC()->customer->get_billing_country()
							? esc_html__( 'Momentan nu e disponibilă nicio metodă de plată. Scrie-ne și te ajutăm să finalizezi comanda.', 'barbarosi' )
							: esc_html__( 'Completează datele de mai sus ca să vezi metodele de plată.', 'barbarosi' )
					),
					'notice'
				);
				echo '</li>';
			}
			?>
		</ul>
	<?php endif; ?>

	<div class="form-row place-order checkout-payment__submit">
		<noscript>
			<?php esc_html_e( 'Browserul tău nu rulează JavaScript. Apasă „Actualizează totalul” înainte de a plasa comanda, ca prețul final să fie corect.', 'barbarosi' ); ?>
			<br/><button type="submit" class="btn btn--outline" name="woocommerce_checkout_update_totals" value="<?php esc_attr_e( 'Actualizează totalul', 'barbarosi' ); ?>"><?php esc_html_e( 'Actualizează totalul', 'barbarosi' ); ?></button>
		</noscript>

		<?php wc_get_template( 'checkout/terms.php' ); ?>

		<?php do_action( 'woocommerce_review_order_before_submit' ); ?>

		<?php echo apply_filters( 'woocommerce_order_button_html', '<button type="submit" class="btn btn--primary btn--block checkout-payment__button" name="woocommerce_checkout_place_order" id="place_order" value="' . esc_attr( $order_button_text ) . '" data-value="' . esc_attr( $order_button_text ) . '">' . esc_html( $order_button_text ) . '</button>' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

		<p class="checkout-payment__secure">
			<?php echo Checkout::icon( 'lock' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php esc_html_e( 'Datele tale sunt transmise securizat.', 'barbarosi' ); ?>
		</p>

		<?php do_action( 'woocommerce_review_order_after_submit' ); ?>

		<?php wp_nonce_field( 'woocommerce-process_checkout', 'woocommerce-process-checkout-nonce' ); ?>
	</div>
</div>
<?php
if ( ! wp_doing_ajax() ) {
	do_action( 'woocommerce_review_order_after_payment' );
}
