<?php
/**
 * Tema Barbarosi — Timber 2 + Vite (SCSS, Alpine.js)
 */

// Load Composer dependencies.
require_once __DIR__ . '/vendor/autoload.php';

require_once __DIR__ . '/src/Vite.php';
require_once __DIR__ . '/src/DemoContent.php';
require_once __DIR__ . '/src/DemoSeeder.php';
require_once __DIR__ . '/src/Shop.php';
require_once __DIR__ . '/src/Checkout.php';
require_once __DIR__ . '/src/Search.php';
require_once __DIR__ . '/src/StarterSite.php';

Timber\Timber::init();

// Sets the directories (inside your theme) to find .twig files.
Timber::$dirname = [ 'templates', 'views' ];

new StarterSite();

// WooCommerce: datele pentru template-uri + ajustări (src/Shop.php). Plugin-urile sunt deja încărcate aici.
Shop::init();

// Coș și checkout: filtre + helpers pentru template-urile din woocommerce/ (src/Checkout.php).
Checkout::init();

// Căutare: panoul live din header (REST) + pagina de rezultate (src/Search.php, search.php).
Search::init();

// `wp barbarosi seed` — produsele demo (src/DemoSeeder.php).
DemoSeeder::register();

/**
 * Fonturi: Archivo (variabil pe lățime — îl folosim „expanded”, aproape de Commuters Sans)
 * și Cinzel pentru logo-ul text.
 * TODO producție: self-host în assets/fonts (GDPR — Google Fonts trimite IP-ul vizitatorului).
 */
const BARBAROSI_FONTS_URL = 'https://fonts.googleapis.com/css2?family=Archivo:wdth,wght@62..125,100..900&family=Cinzel:wght@700..900&display=swap';

add_action(
	'wp_enqueue_scripts',
	static function () {
		wp_enqueue_style( 'barbarosi-fonts', BARBAROSI_FONTS_URL, [], null );
		Vite::enqueue( 'barbarosi-style', 'assets/scss/main.scss' );
		Vite::enqueue( 'barbarosi-main', 'assets/js/main.js' );
	}
);

add_filter(
	'wp_resource_hints',
	static function ( array $urls, string $relation ): array {
		if ( 'preconnect' === $relation ) {
			$urls[] = 'https://fonts.googleapis.com';
			$urls[] = [ 'href' => 'https://fonts.gstatic.com', 'crossorigin' ];
			// TEMPORAR: imaginile demo vin de pe CDN-ul Daily Paper (vezi src/DemoContent.php).
			$urls[] = 'https://dailypaperclothing.com';
		}

		return $urls;
	},
	10,
	2
);
