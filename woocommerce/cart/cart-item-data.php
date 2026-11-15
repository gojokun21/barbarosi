<?php
/**
 * Detaliile unui produs din coș / sumar (ex. „Mărime: M”), pe un rând.
 *
 * Suprascrie woocommerce/templates/cart/cart-item-data.php (fără paragrafele adăugate de wpautop).
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 2.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<dl class="item-meta variation">
	<?php foreach ( $item_data as $data ) : ?>
		<div class="item-meta__row">
			<dt class="<?php echo sanitize_html_class( 'variation-' . $data['key'] ); ?>"><?php echo wp_kses_post( $data['key'] ); ?>:</dt>
			<dd class="<?php echo sanitize_html_class( 'variation-' . $data['key'] ); ?>"><?php echo wp_kses_post( $data['display'] ); ?></dd>
		</div>
	<?php endforeach; ?>
</dl>
