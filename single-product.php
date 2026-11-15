<?php
/**
 * Pagina de produs WooCommerce -> views/single-product.twig
 *
 * WooCommerce folosește fișierul acesta în locul template-ului lui (necesită add_theme_support( 'woocommerce' )).
 * Datele produsului sunt pregătite în src/Shop.php.
 */

$wc_product = wc_get_product( get_queried_object_id() );

if ( ! $wc_product ) {
	require get_404_template();
	return;
}

$context            = Timber::context();
$context['post']    = Timber::get_post();
$context['product'] = Shop::single( $wc_product );
$context['notices'] = Shop::notices();

if ( post_password_required( $wc_product->get_id() ) ) {
	Timber::render( 'single-password.twig', $context );
	return;
}

// Date structurate schema.org/Product (Google) — WooCommerce le afișează în wp_footer.
if ( isset( WC()->structured_data ) ) {
	WC()->structured_data->generate_product_data( $wc_product );
}

Timber::render( 'single-product.twig', $context );
