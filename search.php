<?php
/**
 * Pagina de rezultate ale căutării -> views/search.twig
 *
 * Interogarea e cea principală (căutare de produse, vezi Search::search_products_only), deci sortarea și
 * paginarea vin din WooCommerce, ca în shop. Datele: Search::page().
 */

$context            = Timber::context();
$context['search']  = Search::page();
$context['notices'] = Shop::notices();

Timber::render( 'search.twig', $context );
