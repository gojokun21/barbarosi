<?php
/**
 * Câmpul de cantitate cu butoane − / +.
 *
 * Suprascrie woocommerce/templates/global/quantity-input.php. Butoanele apar doar când cantitatea se poate
 * modifica (input numeric, nu readonly); le leagă assets/js/shop/cart.js, care schimbă valoarea și emite `change`.
 * Inputul rămâne cel standard (`input.qty`) — Enter / tastarea directă funcționează ca înainte.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 10.1.0
 */

defined( 'ABSPATH' ) || exit;

$product_label = ! empty( $args['product_name'] ) ? wp_strip_all_tags( $args['product_name'] ) : '';
/* translators: %s: numele produsului */
$label   = $product_label ? sprintf( __( 'Cantitate pentru %s', 'barbarosi' ), $product_label ) : __( 'Cantitate', 'barbarosi' );
$stepper = 'number' === $type && ! $readonly;
?>
<div class="quantity<?php echo $stepper ? ' quantity--stepper' : ''; ?>">
	<?php
	/**
	 * Hook to output something before the quantity input field.
	 *
	 * @since 7.2.0
	 */
	do_action( 'woocommerce_before_quantity_input_field' );
	?>
	<label class="screen-reader-text" for="<?php echo esc_attr( $input_id ); ?>"><?php echo esc_html( $label ); ?></label>

	<?php if ( $stepper ) : ?>
		<button type="button" class="quantity__step" data-qty-step="-1" aria-label="<?php esc_attr_e( 'Scade cantitatea', 'barbarosi' ); ?>" aria-controls="<?php echo esc_attr( $input_id ); ?>">
			<?php echo Checkout::icon( 'minus' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</button>
	<?php endif; ?>

	<input
		type="<?php echo esc_attr( $type ); ?>"
		<?php echo $readonly ? 'readonly="readonly"' : ''; ?>
		id="<?php echo esc_attr( $input_id ); ?>"
		class="<?php echo esc_attr( join( ' ', (array) $classes ) ); ?>"
		name="<?php echo esc_attr( $input_name ); ?>"
		value="<?php echo esc_attr( $input_value ); ?>"
		<?php if ( in_array( $type, array( 'text', 'search', 'tel', 'url', 'email', 'password' ), true ) ) : ?>
			size="4"
		<?php endif; ?>
		min="<?php echo esc_attr( $min_value ); ?>"
		<?php if ( 0 < $max_value ) : ?>
			max="<?php echo esc_attr( $max_value ); ?>"
		<?php endif; ?>
		<?php if ( ! $readonly ) : ?>
			step="<?php echo esc_attr( $step ); ?>"
			placeholder="<?php echo esc_attr( $placeholder ); ?>"
			inputmode="<?php echo esc_attr( $inputmode ); ?>"
			autocomplete="<?php echo esc_attr( isset( $autocomplete ) ? $autocomplete : 'on' ); ?>"
		<?php endif; ?>
	/>

	<?php if ( $stepper ) : ?>
		<button type="button" class="quantity__step" data-qty-step="1" aria-label="<?php esc_attr_e( 'Crește cantitatea', 'barbarosi' ); ?>" aria-controls="<?php echo esc_attr( $input_id ); ?>">
			<?php echo Checkout::icon( 'plus' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</button>
	<?php endif; ?>

	<?php
	/**
	 * Hook to output something after quantity input field
	 *
	 * @since 3.6.0
	 */
	do_action( 'woocommerce_after_quantity_input_field' );
	?>
</div>
