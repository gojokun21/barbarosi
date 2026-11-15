<?php
/**
 * Produse sugerate sub coș, cu cardul de produs al temei.
 *
 * Suprascrie woocommerce/templates/cart/cross-sells.php. Produsele: cross-sells sau, dacă lipsesc,
 * ținutele (upsells) produselor din coș — vezi Checkout::cross_sell_ids.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 9.6.0
 */

defined( 'ABSPATH' ) || exit;

if ( $cross_sells ) {
	Timber::render(
		'partial/cart-suggestions.twig',
		[
			/** This filter is documented in woocommerce/templates/cart/cross-sells.php */
			'title'    => apply_filters( 'woocommerce_product_cross_sells_products_heading', __( 'Completează ținuta', 'barbarosi' ) ),
			'products' => array_map( [ Shop::class, 'card' ], array_values( $cross_sells ) ),
		]
	);
}
