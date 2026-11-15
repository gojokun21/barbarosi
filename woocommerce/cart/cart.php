<?php
/**
 * Coșul: lista de produse (stânga) + sumarul comenzii (dreapta, sticky pe desktop).
 *
 * Suprascrie woocommerce/templates/cart/cart.php. Markup-ul e al temei, dar păstrează hook-urile și
 * clasele de care depinde cart.js din WooCommerce pentru actualizările AJAX:
 * .woocommerce-cart-form, .woocommerce-cart-form__contents, .cart_item, .product-remove > a,
 * .coupon, #coupon_code, [name="apply_coupon"], [name="update_cart"].
 *
 * Cantitatea se actualizează automat (assets/js/shop/cart.js apasă butonul „Actualizează coșul”,
 * vizibil doar fără JavaScript).
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 11.3.0
 */

defined( 'ABSPATH' ) || exit;

$cart_count  = WC()->cart->get_cart_contents_count();
$count_label = Checkout::count_label( $cart_count );
?>

<header class="cart-page__header">
	<h1 class="cart-page__title"><?php esc_html_e( 'Coșul tău', 'barbarosi' ); ?></h1>
	<p class="cart-page__count" data-cart-count-label aria-live="polite"><?php echo esc_html( $count_label ); ?></p>
</header>

<?php do_action( 'woocommerce_before_cart' ); ?>

<div class="cart-page__layout">
	<form
		class="woocommerce-cart-form cart-page__items"
		action="<?php echo esc_url( wc_get_cart_url() ); ?>"
		method="post"
		data-count="<?php echo esc_attr( $cart_count ); ?>"
		data-count-label="<?php echo esc_attr( $count_label ); ?>"
	>
		<?php do_action( 'woocommerce_before_cart_table' ); ?>

		<ul class="woocommerce-cart-form__contents cart-lines" role="list">
			<?php do_action( 'woocommerce_before_cart_contents' ); ?>

			<?php
			foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
				$_product   = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );
				$product_id = apply_filters( 'woocommerce_cart_item_product_id', $cart_item['product_id'], $cart_item, $cart_item_key );

				/** This filter is documented in woocommerce/templates/cart/cart.php */
				$visible = apply_filters( 'woocommerce_cart_item_visible', true, $cart_item, $cart_item_key );

				if ( ! $_product instanceof WC_Product || ! $_product->exists() || $cart_item['quantity'] <= 0 || ! $visible ) {
					continue;
				}

				$quantity          = (int) $cart_item['quantity'];
				$cart_item_name    = Checkout::item_name( $cart_item, $_product );
				$product_name      = apply_filters( 'woocommerce_cart_item_name', $cart_item_name, $cart_item, $cart_item_key );
				$product_permalink = apply_filters( 'woocommerce_cart_item_permalink', $_product->is_visible() ? $_product->get_permalink( $cart_item ) : '', $cart_item, $cart_item_key );
				$thumbnail         = Checkout::item_thumbnail( $_product, $cart_item, $cart_item_key, '(min-width: 768px) 120px, 96px' );

				// Prețul întreg tăiat, dacă produsul e la reducere (ca pe card și în pagina de produs).
				$line_regular = $_product->is_on_sale()
					? (float) wc_get_price_to_display(
						$_product,
						[
							'price'           => (float) $_product->get_regular_price(),
							'qty'             => $quantity,
							'display_context' => 'cart',
						]
					)
					: 0;
				$line_price   = (float) wc_get_price_to_display( $_product, [ 'qty' => $quantity, 'display_context' => 'cart' ] );
				?>
				<li class="cart-line woocommerce-cart-form__cart-item <?php echo esc_attr( apply_filters( 'woocommerce_cart_item_class', 'cart_item', $cart_item, $cart_item_key ) ); ?>">
					<div class="cart-line__media">
						<?php
						if ( $product_permalink ) {
							printf( '<a href="%s" tabindex="-1" aria-hidden="true">%s</a>', esc_url( $product_permalink ), $thumbnail ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						} else {
							echo $thumbnail; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						}
						?>
					</div>

					<div class="cart-line__body">
						<div class="cart-line__top">
							<div class="cart-line__info product-name">
								<h2 class="cart-line__title">
									<?php
									if ( $product_permalink ) {
										echo wp_kses_post( apply_filters( 'woocommerce_cart_item_name', sprintf( '<a href="%s">%s</a>', esc_url( $product_permalink ), $cart_item_name ), $cart_item, $cart_item_key ) );
									} else {
										echo wp_kses_post( $product_name );
									}
									?>
								</h2>

								<?php
								do_action( 'woocommerce_after_cart_item_name', $cart_item, $cart_item_key );

								// Mărimea (atributele variației) — cart/cart-item-data.php.
								echo wc_get_formatted_cart_item_data( $cart_item, false, $cart_item_name ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

								if ( $quantity > 1 ) {
									printf(
										'<p class="cart-line__unit product-price">%s</p>',
										/* translators: %s: prețul unei bucăți */
										sprintf( esc_html__( '%s / buc.', 'barbarosi' ), apply_filters( 'woocommerce_cart_item_price', WC()->cart->get_product_price( $_product ), $cart_item, $cart_item_key ) ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
									);
								}

								if ( $_product->backorders_require_notification() && $_product->is_on_backorder( $quantity ) ) {
									/** This filter is documented in woocommerce/templates/cart/cart.php */
									echo wp_kses_post( apply_filters( 'woocommerce_cart_item_backorder_notification', '<p class="backorder_notification cart-line__note">' . esc_html__( 'Disponibil la comandă — se livrează când revine în stoc.', 'barbarosi' ) . '</p>', $product_id, $cart_item, $cart_item_key ) );
								}
								?>
							</div>

							<p class="cart-line__subtotal product-subtotal">
								<?php if ( $line_regular > $line_price ) : ?>
									<del class="cart-line__price-old"><span class="screen-reader-text"><?php esc_html_e( 'Preț inițial:', 'barbarosi' ); ?> </span><?php echo wc_price( $line_regular ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></del>
								<?php endif; ?>
								<?php echo apply_filters( 'woocommerce_cart_item_subtotal', WC()->cart->get_product_subtotal( $_product, $quantity ), $cart_item, $cart_item_key ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							</p>
						</div>

						<div class="cart-line__actions">
							<div class="cart-line__quantity product-quantity">
								<?php
								if ( $_product->is_sold_individually() ) {
									$min_quantity = 1;
									$max_quantity = 1;
								} else {
									$min_quantity = 0;
									$max_quantity = $_product->get_max_purchase_quantity();
								}

								$product_quantity = woocommerce_quantity_input(
									[
										'input_name'   => "cart[{$cart_item_key}][qty]",
										'input_value'  => $quantity,
										'max_value'    => $max_quantity,
										'min_value'    => $min_quantity,
										'product_name' => $product_name,
									],
									$_product,
									false
								);

								echo apply_filters( 'woocommerce_cart_item_quantity', $product_quantity, $cart_item_key, $cart_item ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
								?>
							</div>

							<div class="product-remove">
								<?php
								echo apply_filters( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
									'woocommerce_cart_item_remove_link',
									sprintf(
										'<a role="button" href="%s" class="remove cart-line__remove" aria-label="%s" data-product_id="%s" data-product_sku="%s">%s</a>',
										esc_url( wc_get_cart_remove_url( $cart_item_key ) ),
										/* translators: %s: numele produsului */
										esc_attr( sprintf( __( 'Elimină %s din coș', 'barbarosi' ), wp_strip_all_tags( $product_name ) ) ),
										esc_attr( $product_id ),
										esc_attr( $_product->get_sku() ),
										esc_html__( 'Elimină', 'barbarosi' )
									),
									$cart_item_key
								);
								?>
							</div>
						</div>
					</div>
				</li>
				<?php
			}
			?>

			<?php do_action( 'woocommerce_cart_contents' ); ?>
		</ul>

		<div class="cart-page__actions">
			<?php if ( wc_coupons_enabled() ) : ?>
				<div class="cart-coupon coupon">
					<label class="cart-coupon__label" for="coupon_code"><?php esc_html_e( 'Cod de reducere', 'barbarosi' ); ?></label>
					<div class="cart-coupon__row">
						<input type="text" name="coupon_code" class="input-text" id="coupon_code" value="" placeholder="<?php esc_attr_e( 'Introdu codul', 'barbarosi' ); ?>" autocomplete="off" />
						<button type="submit" class="btn btn--outline" name="apply_coupon" value="<?php esc_attr_e( 'Aplică', 'barbarosi' ); ?>"><?php esc_html_e( 'Aplică', 'barbarosi' ); ?></button>
					</div>
					<?php do_action( 'woocommerce_cart_coupon' ); ?>
				</div>
			<?php endif; ?>

			<button type="submit" class="btn btn--outline cart-page__update" name="update_cart" value="<?php esc_attr_e( 'Actualizează coșul', 'barbarosi' ); ?>"><?php esc_html_e( 'Actualizează coșul', 'barbarosi' ); ?></button>

			<?php do_action( 'woocommerce_cart_actions' ); ?>

			<?php wp_nonce_field( 'woocommerce-cart', 'woocommerce-cart-nonce' ); ?>
		</div>

		<?php do_action( 'woocommerce_after_cart_contents' ); ?>
		<?php do_action( 'woocommerce_after_cart_table' ); ?>
	</form>

	<?php do_action( 'woocommerce_before_cart_collaterals' ); ?>

	<aside class="cart-collaterals cart-page__summary" aria-label="<?php esc_attr_e( 'Sumarul comenzii', 'barbarosi' ); ?>">
		<?php
		/**
		 * @hooked woocommerce_cart_totals - 10
		 * (cross-sells sunt mutate în woocommerce_after_cart — vezi Checkout::move_template_parts)
		 */
		do_action( 'woocommerce_cart_collaterals' );
		?>
	</aside>
</div>

<?php do_action( 'woocommerce_after_cart' ); ?>
