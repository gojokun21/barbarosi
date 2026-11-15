<?php
/**
 * Butonul „Finalizează comanda” din sumarul coșului.
 *
 * Suprascrie woocommerce/templates/cart/proceed-to-checkout-button.php (butonul temei + text).
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 7.0.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
?>

<a href="<?php echo esc_url( wc_get_checkout_url() ); ?>" class="checkout-button btn btn--primary btn--block wc-forward">
	<?php esc_html_e( 'Finalizează comanda', 'barbarosi' ); ?>
</a>
