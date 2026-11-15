<?php

/**
 * Integrare WooCommerce: transformă produsele în array-urile folosite de template-uri
 * și ajustează comportamentul WooCommerce pentru markup-ul temei.
 *
 * Forma unui card (partial/product-card.twig):
 *   [ id, title, url, price, regular_price, badge, image, hover_image, colors[] ]
 * Forma unei imagini (partial/image.twig):
 *   [ src, srcset, alt, width, height ]
 * Forma unei culori:
 *   [ name, hex, url, current ]
 *
 * Culorile sunt produse separate (ca în referință), legate prin meta `_barbarosi_color_group`;
 * mărimile sunt variații (atributul `pa_marime`).
 */
final class Shop {
	public const META_COLOR_GROUP = '_barbarosi_color_group';
	public const META_SWATCH      = '_barbarosi_swatch';
	public const META_SIZE_GUIDE  = '_barbarosi_size_guide';

	public const COLOR_TAXONOMY = 'pa_culoare';
	public const SIZE_TAXONOMY  = 'pa_marime';
	public const NEW_TAG        = 'nou';

	private const IMAGE_SIZE = 'large';

	/** @var array<string, int[]> */
	private static array $color_groups = [];

	public static function init(): void {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}

		// Shop-ul, produsul, coșul și checkout-ul au markup și stiluri proprii — CSS-ul WooCommerce doar ar intra în conflict.
		add_filter( 'woocommerce_enqueue_styles', static fn( $styles ) => is_woocommerce() || is_cart() || is_checkout() ? [] : $styles );

		// Tema nu are sidebar (sidebar.php din starter e gol) — nu-l afișăm în paginile WooCommerce.
		add_action( 'wp', static fn() => remove_action( 'woocommerce_sidebar', 'woocommerce_get_sidebar', 10 ) );

		// „1.599 lei” în loc de „1.599,00 lei” (zecimalele apar doar când există).
		add_filter( 'woocommerce_price_trim_zeros', '__return_true' );

		add_filter( 'woocommerce_add_to_cart_redirect', [ self::class, 'redirect_after_add_to_cart' ], 10, 2 );
		add_filter( 'wc_add_to_cart_message_html', [ self::class, 'add_to_cart_message' ], 10, 2 );
	}

	/* ---------------------------------------------------------------------
	 * Date pentru template-uri
	 * ------------------------------------------------------------------- */

	public static function card( WC_Product $product ): array {
		$gallery = $product->get_gallery_image_ids();

		return [
			'id'          => $product->get_id(),
			'title'       => $product->get_name(),
			'url'         => $product->get_permalink(),
			'badge'       => self::badge( $product ),
			'image'       => self::image( $product->get_image_id(), $product->get_name() ),
			'hover_image' => $gallery ? self::image( $gallery[0], $product->get_name() ) : null,
			'colors'      => self::colors( $product ),
		] + self::prices( $product );
	}

	/**
	 * Card după slug — folosit de DemoContent pentru prima pagină.
	 */
	public static function card_by_slug( string $slug ): ?array {
		if ( ! function_exists( 'wc_get_product' ) ) {
			return null;
		}

		$post    = get_page_by_path( $slug, OBJECT, 'product' );
		$product = $post && 'publish' === $post->post_status ? wc_get_product( $post ) : null;

		return $product ? self::card( $product ) : null;
	}

	/**
	 * Shop-ul și arhivele de produse (views/archive-product.twig), din interogarea principală WooCommerce.
	 * Sortarea și paginarea sunt cele din WooCommerce (aceleași filtre și parametri în URL).
	 */
	public static function archive(): array {
		global $wp_query;

		$products = [];

		foreach ( $wp_query->posts as $post ) {
			$product = wc_get_product( $post );

			if ( $product && $product->is_visible() ) {
				$products[] = self::card( $product );
			}
		}

		return [
			// „Shop”, numele categoriei, „Rezultatele căutării: „…”” — vine cu entități HTML, îl vrem text simplu.
			'title'      => html_entity_decode( wp_strip_all_tags( woocommerce_page_title( false ) ), ENT_QUOTES, 'UTF-8' ),
			'count'      => Checkout::count_label( (int) $wp_query->found_posts ),
			'products'   => $products,
			'ordering'   => self::capture( 'woocommerce_catalog_ordering' ),
			'pagination' => self::capture( 'woocommerce_pagination' ),
		];
	}

	/**
	 * Tot ce are nevoie pagina de produs (views/single-product.twig).
	 */
	public static function single( WC_Product $product ): array {
		$image_ids = array_filter( array_merge( [ $product->get_image_id() ], $product->get_gallery_image_ids() ) );
		$colors    = self::colors( $product );

		return [
			'id'                => $product->get_id(),
			'title'             => $product->get_name(),
			'url'               => $product->get_permalink(),
			'badge'             => self::badge( $product ),
			'images'            => array_values( array_filter( array_map( static fn( $id ) => self::image( (int) $id, $product->get_name() ), $image_ids ) ) ),
			'color'             => self::color( $product ),
			'colors'            => count( $colors ) > 1 ? $colors : [],
			'sizes'             => self::sizes( $product ),
			'purchasable'       => $product->is_purchasable() && $product->is_in_stock(),
			'short_description' => wc_format_content( $product->get_short_description() ),
			'description'       => wc_format_content( $product->get_description() ),
			'size_guide'        => DemoContent::size_guide( (string) $product->get_meta( self::META_SIZE_GUIDE ) ),
			'perks'             => DemoContent::product_perks(),
			'combine'           => self::combine( $product ),
		] + self::prices( $product );
	}

	/**
	 * Imagine din Media Library, cu srcset generat de WordPress.
	 */
	public static function image( int $attachment_id, string $fallback_alt = '' ): ?array {
		$image = $attachment_id ? wp_get_attachment_image_src( $attachment_id, self::IMAGE_SIZE ) : false;

		if ( ! $image ) {
			return null;
		}

		$alt = (string) get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );

		return [
			'src'    => $image[0],
			'srcset' => (string) wp_get_attachment_image_srcset( $attachment_id, self::IMAGE_SIZE ),
			'alt'    => $alt ?: $fallback_alt,
			'width'  => $image[1],
			'height' => $image[2],
		];
	}

	/**
	 * Preț formatat după setările WooCommerce, ca text simplu (template-urile îl escapează).
	 */
	public static function price_text( float $amount ): string {
		return html_entity_decode( wp_strip_all_tags( wc_price( $amount ) ), ENT_QUOTES, 'UTF-8' );
	}

	public static function cart_count(): int {
		return function_exists( 'WC' ) && WC()->cart ? WC()->cart->get_cart_contents_count() : 0;
	}

	/**
	 * Notificările WooCommerce (ex. „adăugat în coș”), golite după citire.
	 *
	 * @return array<int, array{type: string, message: string}>
	 */
	public static function notices(): array {
		if ( ! function_exists( 'wc_get_notices' ) || ! WC()->session ) {
			return [];
		}

		$notices = [];

		foreach ( wc_get_notices() as $type => $items ) {
			foreach ( $items as $item ) {
				$notices[] = [
					'type'    => $type,
					'message' => wp_kses_post( $item['notice'] ),
				];
			}
		}

		wc_clear_notices();

		return $notices;
	}

	/* ---------------------------------------------------------------------
	 * Filtre WooCommerce
	 * ------------------------------------------------------------------- */

	/**
	 * Formularul din pagina de produs face POST; după adăugare redirecționăm înapoi (GET),
	 * ca refresh-ul să nu retrimită formularul. Notificarea rămâne în sesiune.
	 *
	 * @param string|false    $url
	 * @param WC_Product|null $product
	 */
	public static function redirect_after_add_to_cart( $url, $product = null ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- doar un marcaj, nu procesăm date
		if ( $url || empty( $_POST['barbarosi_pdp'] ) || ! $product instanceof WC_Product ) {
			return $url;
		}

		return $product->get_permalink();
	}

	/**
	 * @param string            $message
	 * @param array<int, float> $products product_id => cantitate
	 */
	public static function add_to_cart_message( $message, $products ): string {
		$titles = array_map(
			static fn( $id ): string => '„' . wp_strip_all_tags( get_the_title( $id ) ) . '”',
			array_keys( (array) $products )
		);

		$text = 1 === count( $titles )
			/* translators: %s: numele produsului */
			? sprintf( __( '%s a fost adăugat în coș.', 'barbarosi' ), $titles[0] )
			: __( 'Produsele au fost adăugate în coș.', 'barbarosi' );

		return sprintf( '%s <a href="%s">%s</a>', esc_html( $text ), esc_url( wc_get_cart_url() ), esc_html__( 'Vezi coșul', 'barbarosi' ) );
	}

	/* ---------------------------------------------------------------------
	 * Helpers
	 * ------------------------------------------------------------------- */

	/**
	 * Output-ul unei funcții de template WooCommerce care face echo (sortare, paginare).
	 */
	private static function capture( callable $template_function ): string {
		ob_start();
		$template_function();

		return trim( (string) ob_get_clean() );
	}

	/**
	 * Prețul curent și prețul întreg (null dacă produsul nu e la reducere).
	 * La produsele variabile: cea mai mică variație.
	 *
	 * @return array{0: float, 1: ?float}
	 */
	private static function amounts( WC_Product $product ): array {
		$price   = (float) $product->get_price();
		$regular = $product instanceof WC_Product_Variable
			? (float) $product->get_variation_regular_price( 'min' )
			: (float) $product->get_regular_price();

		return [ $price, $product->is_on_sale() && $regular > $price ? $regular : null ];
	}

	/**
	 * @return array{price: string, regular_price: ?string}
	 */
	private static function prices( WC_Product $product ): array {
		[ $price, $regular ] = self::amounts( $product );

		return [
			'price'         => self::price_text( $price ),
			'regular_price' => $regular ? self::price_text( $regular ) : null,
		];
	}

	/**
	 * „-24%” la reducere, „Nou” pentru produsele cu tag-ul `nou`.
	 */
	private static function badge( WC_Product $product ): ?string {
		[ $price, $regular ] = self::amounts( $product );

		if ( $regular ) {
			return sprintf( '-%d%%', round( ( 1 - $price / $regular ) * 100 ) );
		}

		return has_term( self::NEW_TAG, 'product_tag', $product->get_id() ) ? __( 'Nou', 'barbarosi' ) : null;
	}

	/**
	 * @return array{name: string, hex: string}|null
	 */
	private static function color( WC_Product $product ): ?array {
		$name = $product->get_attribute( self::COLOR_TAXONOMY );

		if ( '' === $name ) {
			return null;
		}

		return [
			'name' => $name,
			'hex'  => (string) $product->get_meta( self::META_SWATCH ) ?: '#000',
		];
	}

	/**
	 * Culoarea produsului + celelalte culori din același grup (produsul curent primul).
	 */
	private static function colors( WC_Product $product ): array {
		$current = self::color( $product );

		if ( ! $current ) {
			return [];
		}

		$colors = [ $current + [ 'url' => $product->get_permalink(), 'current' => true ] ];
		$group  = (string) $product->get_meta( self::META_COLOR_GROUP );

		if ( '' === $group ) {
			return $colors;
		}

		self::$color_groups[ $group ] ??= get_posts(
			[
				'post_type'      => 'product',
				'post_status'    => 'publish',
				'meta_key'       => self::META_COLOR_GROUP, // phpcs:ignore WordPress.DB.SlowDBQuery
				'meta_value'     => $group, // phpcs:ignore WordPress.DB.SlowDBQuery
				'orderby'        => 'menu_order',
				'order'          => 'ASC',
				'fields'         => 'ids',
				'posts_per_page' => 20,
				'no_found_rows'  => true,
			]
		);

		foreach ( self::$color_groups[ $group ] as $sibling_id ) {
			$sibling = $sibling_id !== $product->get_id() ? wc_get_product( $sibling_id ) : null;
			$color   = $sibling ? self::color( $sibling ) : null;

			if ( $color ) {
				$colors[] = $color + [ 'url' => $sibling->get_permalink(), 'current' => false ];
			}
		}

		return $colors;
	}

	/**
	 * Mărimile = variațiile produsului, în ordinea termenilor din atributul `pa_marime`.
	 *
	 * @return array<int, array{label: string, variation_id: int, in_stock: bool}>
	 */
	private static function sizes( WC_Product $product ): array {
		if ( ! $product instanceof WC_Product_Variable ) {
			return [];
		}

		$variations = [];

		foreach ( $product->get_children() as $variation_id ) {
			$variation = wc_get_product( $variation_id );

			if ( $variation instanceof WC_Product_Variation && 'publish' === $variation->get_status() && $variation->is_purchasable() ) {
				$variations[ $variation->get_attributes()[ self::SIZE_TAXONOMY ] ?? '' ] = $variation;
			}
		}

		$sizes = [];

		foreach ( wc_get_product_terms( $product->get_id(), self::SIZE_TAXONOMY ) as $term ) {
			$variation = $variations[ $term->slug ] ?? null;

			if ( $variation ) {
				$sizes[] = [
					'label'        => $term->name,
					'variation_id' => $variation->get_id(),
					'in_stock'     => $variation->is_in_stock(),
				];
			}
		}

		return $sizes;
	}

	/**
	 * „Combină cu”: upsells (setate din admin sau din ținutele demo), altfel produse similare.
	 */
	private static function combine( WC_Product $product, int $limit = 6 ): array {
		$ids = $product->get_upsell_ids() ?: wc_get_related_products( $product->get_id(), $limit );

		$cards = [];

		foreach ( array_slice( $ids, 0, $limit ) as $id ) {
			$item = wc_get_product( $id );

			if ( $item && $item->is_visible() ) {
				$cards[] = self::card( $item );
			}
		}

		return $cards;
	}
}
