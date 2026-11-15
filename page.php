<?php
/**
 * The template for displaying all pages.
 *
 * This is the template that displays all pages by default.
 * Please note that this is the WordPress construct of pages
 * and that other 'pages' on your WordPress site will use a
 * different template.
 *
 * To generate specific templates for your pages you can use:
 * /mytheme/templates/page-mypage.twig
 * (which will still route through this PHP file)
 * OR
 * /mytheme/page-mypage.php
 * (in which case you'll want to duplicate this file and save to the above path)
 *
 * Methods for TimberHelper can be found in the /lib sub-directory
 *
 * @package  WordPress
 * @subpackage  Timber
 * @since    Timber 0.1
 */

$context = Timber::context();

$timber_post     = Timber::get_post();
$context['post'] = $timber_post;
$templates       = array( 'page-' . $timber_post->post_name . '.twig', 'page.twig' );

// Paginile WooCommerce „Coș” și „Finalizare comandă” au layout propriu (indiferent de slug).
if ( function_exists( 'is_cart' ) && is_cart() ) {
	array_unshift( $templates, 'cart.twig' );
} elseif ( function_exists( 'is_checkout' ) && is_checkout() ) {
	$context['order_received'] = is_order_received_page();
	$context['legal_links']    = DemoContent::legal_links();
	array_unshift( $templates, 'checkout.twig' );
}

Timber::render( $templates, $context );
