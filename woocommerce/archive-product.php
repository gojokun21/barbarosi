<?php
/**
 * Shop + arhivele de produse (categorii, etichete, atribute) -> views/archive-product.twig
 *
 * Suprascrie woocommerce/templates/archive-product.php. E aici (nu în rădăcina temei) pentru că la
 * categorii și etichete WooCommerce încarcă taxonomy-product-cat.php din plugin, care cere tot acest fișier.
 *
 * Interogarea (sortare, paginare, vizibilitate) rămâne a WooCommerce; produsele sunt transformate în
 * aceleași carduri ca pe prima pagină (Shop::archive -> Shop::card).
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 8.6.0
 */

defined( 'ABSPATH' ) || exit;

$context            = Timber::context();
$context['shop']    = Shop::archive();
$context['notices'] = Shop::notices();

Timber::render( 'archive-product.twig', $context );
