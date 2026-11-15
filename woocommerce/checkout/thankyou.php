<?php
/**
 * Confirmarea comenzii („Comandă primită”).
 *
 * Suprascrie woocommerce/templates/checkout/thankyou.php. Detaliile comenzii (produse, adrese) vin în
 * continuare din hook-ul `woocommerce_thankyou` (order/order-details.php), stilizate în
 * assets/scss/pages/_order-received.scss.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 11.2.0
 *
 * @var WC_Order|false $order
 */

defined( 'ABSPATH' ) || exit;
?>

<div class="woocommerce-order order-confirmation">

	<?php
	if ( $order ) :

		do_action( 'woocommerce_before_thankyou', $order->get_id() );
		?>

		<?php if ( $order->has_status( 'failed' ) ) : ?>

			<?php
			$default_order_failed_text = esc_html__( 'Banca sau procesatorul de plăți a refuzat tranzacția, așa că nu am putut procesa comanda. Te rugăm să încerci din nou.', 'barbarosi' );

			/** This filter is documented in woocommerce/templates/checkout/thankyou.php */
			$order_failed_text = apply_filters( 'woocommerce_thankyou_order_failed_text', $default_order_failed_text, $order );
			$order_failed_text = is_string( $order_failed_text ) ? $order_failed_text : $default_order_failed_text;
			?>

			<header class="order-confirmation__header">
				<h1 class="order-confirmation__title"><?php esc_html_e( 'Plata nu a reușit', 'barbarosi' ); ?></h1>
				<p class="woocommerce-notice woocommerce-notice--error woocommerce-thankyou-order-failed order-confirmation__text"><?php echo wp_kses_post( $order_failed_text ); ?></p>
			</header>

			<p class="woocommerce-thankyou-order-failed-actions order-confirmation__actions">
				<a href="<?php echo esc_url( $order->get_checkout_payment_url() ); ?>" class="btn btn--primary pay"><?php esc_html_e( 'Încearcă din nou', 'barbarosi' ); ?></a>
				<?php if ( is_user_logged_in() ) : ?>
					<a href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>" class="btn btn--outline"><?php esc_html_e( 'Contul meu', 'barbarosi' ); ?></a>
				<?php endif; ?>
			</p>

		<?php else : ?>

			<header class="order-confirmation__header">
				<span class="order-confirmation__icon"><?php echo Checkout::icon( 'check' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
				<h1 class="order-confirmation__title">
					<?php
					$first_name = $order->get_billing_first_name();
					/* translators: %s: prenumele clientului */
					echo esc_html( $first_name ? sprintf( __( 'Mulțumim, %s!', 'barbarosi' ), $first_name ) : __( 'Mulțumim!', 'barbarosi' ) );
					?>
				</h1>
				<div class="order-confirmation__text">
					<?php wc_get_template( 'checkout/order-received.php', array( 'order' => $order ) ); ?>
				</div>
			</header>

			<ul class="woocommerce-order-overview woocommerce-thankyou-order-details order_details order-confirmation__overview" role="list">
				<li class="woocommerce-order-overview__order order">
					<?php esc_html_e( 'Număr comandă', 'barbarosi' ); ?>
					<strong><?php echo $order->get_order_number(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></strong>
				</li>

				<li class="woocommerce-order-overview__date date">
					<?php esc_html_e( 'Data', 'barbarosi' ); ?>
					<strong><?php echo wc_format_datetime( $order->get_date_created() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></strong>
				</li>

				<?php if ( is_user_logged_in() && $order->get_user_id() === get_current_user_id() && $order->get_billing_email() ) : ?>
					<li class="woocommerce-order-overview__email email">
						<?php esc_html_e( 'Email', 'barbarosi' ); ?>
						<strong><?php echo $order->get_billing_email(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></strong>
					</li>
				<?php endif; ?>

				<li class="woocommerce-order-overview__total total">
					<?php esc_html_e( 'Total', 'barbarosi' ); ?>
					<strong><?php echo $order->get_formatted_order_total(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></strong>
				</li>

				<?php if ( $order->get_payment_method_title() ) : ?>
					<li class="woocommerce-order-overview__payment-method method">
						<?php esc_html_e( 'Plată', 'barbarosi' ); ?>
						<strong><?php echo wp_kses_post( $order->get_payment_method_title() ); ?></strong>
					</li>
				<?php endif; ?>
			</ul>

		<?php endif; ?>

		<?php do_action( 'woocommerce_thankyou_' . $order->get_payment_method(), $order->get_id() ); ?>
		<?php do_action( 'woocommerce_thankyou', $order->get_id() ); ?>

		<p class="order-confirmation__continue">
			<a class="btn btn--outline" href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>"><?php esc_html_e( 'Continuă cumpărăturile', 'barbarosi' ); ?></a>
		</p>

	<?php else : ?>

		<header class="order-confirmation__header">
			<h1 class="order-confirmation__title"><?php esc_html_e( 'Mulțumim!', 'barbarosi' ); ?></h1>
			<div class="order-confirmation__text">
				<?php wc_get_template( 'checkout/order-received.php', array( 'order' => false ) ); ?>
			</div>
		</header>

	<?php endif; ?>

</div>
