<?php

/**
 * Căutarea din magazin: panoul cu rezultate live din header (REST) și pagina de rezultate (search.php).
 *
 * - Orice căutare din front-end caută doar produse (`/?s=…` primește post_type=product), deci
 *   interogarea, sortarea, vizibilitatea și paginarea sunt cele ale WooCommerce, ca în shop.
 * - Panoul live folosește aceeași interogare ca pagina: „Vezi toate rezultatele (7)” duce la 7 produse.
 * - Un termen găsește produsele după titlu și descriere (WordPress), dar și după SKU și după numele
 *   categoriilor / etichetelor / culorilor („hanorace” -> produsele din „Hanorace & pulovere”).
 *   Diacriticele nu contează („camasa” = „cămașă”) — le ignoră colaționarea MySQL.
 *
 * REST (public):
 *   GET /wp-json/barbarosi/v1/search?q=…         -> { query, total, count, products[], categories[] }
 *   GET /wp-json/barbarosi/v1/search/recommended -> { products[] }
 * Un produs = cardul din Shop::card + `category` (categoria cea mai specifică); `colors` e doar numărul lor.
 */
final class Search {
	public const REST_NAMESPACE = 'barbarosi/v1';

	// Sub atât panoul arată sugestii, nu rezultate (aceeași valoare în assets/js/components/header-search.js).
	public const MIN_LENGTH = 2;

	private const MAX_LENGTH        = 100;
	private const LIVE_LIMIT        = 6;  // produse în panoul din header
	private const CATEGORY_LIMIT    = 4;  // categorii sugerate în panou
	private const FACET_LIMIT       = 12; // chip-uri de categorie pe pagina de rezultate
	private const FACET_SAMPLE      = 500; // câte produse găsite intră în numărătoarea pe categorii
	private const RECOMMENDED_LIMIT = 6;

	// Taxonomiile căutate după nume. Mărimile nu — „M” ar găsi tot catalogul.
	private const SEARCH_TAXONOMIES = [ 'product_cat', 'product_tag', Shop::COLOR_TAXONOMY ];

	// Cuvinte de legătură ignorate: „geacă cu glugă” caută „geacă” și „glugă”, nu și tot ce conține „cu”.
	private const STOPWORDS = [ 'al', 'ale', 'cu', 'de', 'din', 'in', 'în', 'la', 'pe', 'pentru', 'sau', 'si', 'și', 'un', 'una' ];

	public static function init(): void {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}

		add_filter( 'request', [ self::class, 'search_products_only' ] );
		add_filter( 'posts_search', [ self::class, 'extend_search_sql' ], 10, 2 );
		add_filter( 'wp_search_stopwords', static fn( array $words ): array => array_merge( $words, self::STOPWORDS ) );

		// WooCommerce trimite direct pe produs când există un singur rezultat; noi arătăm pagina de rezultate,
		// ca Enter în căutare să ducă mereu în același loc.
		add_filter( 'woocommerce_redirect_single_search_result', '__return_false' );

		// WooCommerce încarcă archive-product.php pentru căutările de produse — noi vrem search.php.
		add_filter( 'template_include', [ self::class, 'template' ], 20 );
		add_filter( 'document_title_parts', [ self::class, 'document_title' ] );

		add_action( 'rest_api_init', [ self::class, 'register_routes' ] );
	}

	/* ---------------------------------------------------------------------
	 * Date pentru template-uri
	 * ------------------------------------------------------------------- */

	/**
	 * Pagina de rezultate (views/search.twig), din interogarea principală WooCommerce.
	 */
	public static function page(): array {
		global $wp_query;

		$term     = self::clean( get_search_query( false ) );
		$category = (string) get_query_var( 'product_cat' );

		// Fără termen WordPress ar lista tot catalogul — pagina arată doar sugestii.
		$archive  = '' !== $term ? Shop::archive() : [];
		$products = $archive['products'] ?? [];

		// Cu o categorie activă calculăm chip-urile și când ea nu are rezultate, ca să poți reveni la „Toate”.
		$facets = '' !== $term && ( $products || '' !== $category )
			? self::facets( $term, $category )
			: [ 'items' => [], 'total' => 0 ];

		return [
			'query'         => $term,
			'count'         => $archive['count'] ?? '',
			'total'         => (int) $wp_query->found_posts,
			'products'      => $products,
			'ordering'      => $archive['ordering'] ?? '',
			'pagination'    => $archive['pagination'] ?? '',
			'facets'        => $facets['items'],
			'all'           => [
				'url'    => self::url( $term, self::ordering_arg() ),
				'count'  => $facets['total'],
				'active' => '' === $category,
			],
			'category_name' => self::category_label( $category ),
			'popular'       => self::popular(),
			// Sugestii doar când nu e nimic de arătat (fără termen sau fără rezultate).
			'recommended'   => $products ? [] : array_map( [ Shop::class, 'card' ], self::recommended( 4 ) ),
		];
	}

	/**
	 * Rezultatele din panoul de căutare.
	 */
	public static function live( string $term ): array {
		$result = [
			'query'      => $term,
			'total'      => 0,
			'count'      => Checkout::count_label( 0 ),
			'products'   => [],
			'categories' => [],
		];

		if ( mb_strlen( $term ) < self::MIN_LENGTH ) {
			return $result;
		}

		$query = new WP_Query( array_merge( self::query_args( $term ), [ 'posts_per_page' => self::LIVE_LIMIT ] ) );
		$total = (int) $query->found_posts;

		return [
			'total'      => $total,
			'count'      => Checkout::count_label( $total ),
			'products'   => array_map( [ self::class, 'tile' ], self::visible( $query->posts ) ),
			'categories' => self::matching_categories( $term ),
		] + $result;
	}

	/**
	 * Produse arătate când nu există termen sau rezultate: noutățile (eticheta `nou`), completate cu cele mai noi.
	 *
	 * @return WC_Product[]
	 */
	public static function recommended( int $limit = self::RECOMMENDED_LIMIT ): array {
		$args = [
			'status'     => 'publish',
			'visibility' => 'catalog',
			'orderby'    => 'date',
			'order'      => 'DESC',
			'limit'      => $limit,
		];

		$products = wc_get_products( [ 'tag' => [ Shop::NEW_TAG ] ] + $args );

		if ( count( $products ) < $limit ) {
			$exclude  = array_map( static fn( WC_Product $product ): int => $product->get_id(), $products );
			$products = array_merge( $products, wc_get_products( [ 'limit' => $limit - count( $products ), 'exclude' => $exclude ] + $args ) );
		}

		return self::visible( $products );
	}

	/**
	 * Căutările sugerate (panou + pagina fără rezultate): [ title, url ].
	 */
	public static function popular(): array {
		return array_map(
			static fn( string $term ): array => [ 'title' => $term, 'url' => self::url( $term ) ],
			DemoContent::popular_searches()
		);
	}

	/**
	 * Link spre pagina de rezultate.
	 *
	 * @param array<string, string> $args alți parametri (product_cat, orderby)
	 */
	public static function url( string $term, array $args = [] ): string {
		return add_query_arg( array_map( 'rawurlencode', [ 's' => $term ] + array_filter( $args ) ), home_url( '/' ) );
	}

	/* ---------------------------------------------------------------------
	 * REST
	 * ------------------------------------------------------------------- */

	public static function register_routes(): void {
		register_rest_route(
			self::REST_NAMESPACE,
			'/search',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => static fn( WP_REST_Request $request ) => self::response( self::live( (string) $request['q'] ) ),
				'permission_callback' => '__return_true',
				'args'                => [
					'q' => [
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => static fn( $value ): string => self::clean( (string) $value ),
					],
				],
			]
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/search/recommended',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => static fn() => self::response( [ 'products' => array_map( [ self::class, 'tile' ], self::recommended() ) ] ),
				'permission_callback' => '__return_true',
			]
		);
	}

	/* ---------------------------------------------------------------------
	 * Filtre WordPress / WooCommerce
	 * ------------------------------------------------------------------- */

	/**
	 * Magazinul nu are blog: `/?s=…` caută produse. Setat înainte de parsarea interogării, ca WooCommerce
	 * să o trateze ca pe shop (vizibilitate, sortare după relevanță, paginare).
	 */
	public static function search_products_only( array $query_vars ): array {
		if ( isset( $query_vars['s'] ) && empty( $query_vars['post_type'] ) && ! is_admin() ) {
			$query_vars['post_type'] = 'product';
		}

		return $query_vars;
	}

	/**
	 * Refacem condiția de căutare WordPress (aceeași formă: fiecare termen trebuie găsit undeva),
	 * adăugând la titlu / rezumat / descriere SKU-ul și numele termenilor din SEARCH_TAXONOMIES.
	 */
	public static function extend_search_sql( string $search, WP_Query $query ): string {
		global $wpdb;

		$terms = (array) $query->get( 'search_terms' );

		if ( '' === $search || ! $terms || is_admin() || ! self::is_product_search( $query ) || $query->get( 'exact' ) ) {
			return $search;
		}

		$prefix   = apply_filters( 'wp_query_search_exclusion_prefix', '-' );
		$taxonomy = implode( ', ', array_map( static fn( string $name ): string => $wpdb->prepare( '%s', $name ), self::SEARCH_TAXONOMIES ) );
		$groups   = [];

		foreach ( $terms as $term ) {
			$exclude = $prefix && str_starts_with( $term, $prefix );
			$term    = $exclude ? substr( $term, strlen( $prefix ) ) : $term;
			$like    = '%' . $wpdb->esc_like( $term ) . '%';
			$op      = $exclude ? 'NOT LIKE' : 'LIKE';
			$parts   = [];

			foreach ( [ 'post_title', 'post_excerpt', 'post_content' ] as $column ) {
				$parts[] = $wpdb->prepare( "({$wpdb->posts}.{$column} {$op} %s)", $like ); // phpcs:ignore WordPress.DB.PreparedSQL
			}

			if ( ! $exclude ) {
				// SKU-ul produsului sau al unei variații (variația duce la produsul părinte).
				$parts[] = $wpdb->prepare(
					"({$wpdb->posts}.ID IN (
						SELECT IF(sku_post.post_parent > 0, sku_post.post_parent, sku_post.ID)
						FROM {$wpdb->posts} AS sku_post
						INNER JOIN {$wpdb->postmeta} AS sku_meta ON sku_meta.post_id = sku_post.ID
						WHERE sku_meta.meta_key = '_sku' AND sku_meta.meta_value LIKE %s
					))",
					$like
				);

				$parts[] = $wpdb->prepare(
					"({$wpdb->posts}.ID IN (
						SELECT tr.object_id
						FROM {$wpdb->term_relationships} AS tr
						INNER JOIN {$wpdb->term_taxonomy} AS tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
						INNER JOIN {$wpdb->terms} AS t ON t.term_id = tt.term_id
						WHERE tt.taxonomy IN ({$taxonomy}) AND t.name LIKE %s
					))", // phpcs:ignore WordPress.DB.PreparedSQL -- $taxonomy e construit cu prepare()
					$like
				);
			}

			$groups[] = '(' . implode( $exclude ? ' AND ' : ' OR ', $parts ) . ')';
		}

		$search = ' AND (' . implode( ' AND ', $groups ) . ') ';

		if ( ! is_user_logged_in() ) {
			$search .= " AND ({$wpdb->posts}.post_password = '') ";
		}

		return $search;
	}

	public static function template( string $template ): string {
		return is_search() ? ( locate_template( 'search.php' ) ?: $template ) : $template;
	}

	/**
	 * <title>: „Rezultate pentru „tricou”” în loc de „Search Results for…” (WordPress escapează titlul).
	 *
	 * @param array<string, string> $parts
	 */
	public static function document_title( array $parts ): array {
		if ( is_search() ) {
			$term = self::clean( get_search_query( false ) );

			/* translators: %s: termenul căutat */
			$parts['title'] = '' !== $term ? sprintf( __( 'Rezultate pentru „%s”', 'barbarosi' ), $term ) : __( 'Căutare', 'barbarosi' );
		}

		return $parts;
	}

	/* ---------------------------------------------------------------------
	 * Helpers
	 * ------------------------------------------------------------------- */

	/**
	 * Aceleași condiții ca interogarea principală WooCommerce pentru o căutare (WC_Query::product_query):
	 * produse publicate, vizibile în căutare (și în stoc, dacă magazinul ascunde stocul epuizat), după relevanță.
	 */
	private static function query_args( string $term ): array {
		$visibility = wc_get_product_visibility_term_ids();
		$hidden     = [ $visibility['exclude-from-search'] ];

		if ( 'yes' === get_option( 'woocommerce_hide_out_of_stock_items' ) ) {
			$hidden[] = $visibility['outofstock'];
		}

		return [
			'post_type'           => 'product',
			'post_status'         => 'publish',
			's'                   => $term,
			'orderby'             => 'relevance',
			'order'               => 'DESC',
			'ignore_sticky_posts' => true,
			'tax_query'           => [ // phpcs:ignore WordPress.DB.SlowDBQuery
				[
					'taxonomy' => 'product_visibility',
					'field'    => 'term_taxonomy_id',
					'terms'    => array_filter( $hidden ),
					'operator' => 'NOT IN',
				],
			],
		];
	}

	private static function is_product_search( WP_Query $query ): bool {
		return $query->is_search() && in_array( 'product', (array) $query->get( 'post_type' ), true );
	}

	/**
	 * Chip-urile de categorie de pe pagina de rezultate: categoriile produselor găsite (fără filtrul de
	 * categorie activ), cu numărul de produse. Un părinte e omis când apar și subcategoriile lui
	 * („Topuri” lângă „Tricouri” și „Geci” nu ajută la filtrare).
	 *
	 * @return array{items: array, total: int}
	 */
	private static function facets( string $term, string $active ): array {
		$query = new WP_Query(
			array_merge(
				self::query_args( $term ),
				[
					'fields'         => 'ids',
					'posts_per_page' => self::FACET_SAMPLE,
					'orderby'        => 'ID',
				]
			)
		);

		if ( ! $query->posts ) {
			return [ 'items' => [], 'total' => 0 ];
		}

		$terms = wp_get_object_terms( $query->posts, 'product_cat', [ 'fields' => 'all_with_object_id' ] );
		$terms = is_wp_error( $terms ) ? [] : $terms;

		/** @var array<int, WP_Term> $by_id */
		$by_id   = [];
		$counts  = [];
		$parents = [ (int) get_option( 'default_product_cat' ) => true ]; // „Uncategorized”

		foreach ( $terms as $item ) {
			$by_id[ $item->term_id ]  = $item;
			$counts[ $item->term_id ] = ( $counts[ $item->term_id ] ?? 0 ) + 1;
		}

		foreach ( array_keys( $by_id ) as $term_id ) {
			foreach ( get_ancestors( $term_id, 'product_cat', 'taxonomy' ) as $ancestor ) {
				$parents[ $ancestor ] = true;
			}
		}

		$facets = array_diff_key( $by_id, $parents );

		uasort(
			$facets,
			static fn( WP_Term $a, WP_Term $b ): int => [ $counts[ $b->term_id ], $a->name ] <=> [ $counts[ $a->term_id ], $b->name ]
		);

		$items = array_map(
			static fn( WP_Term $item ): array => [
				'name'   => self::term_name( $item ),
				'count'  => $counts[ $item->term_id ],
				'url'    => self::url( $term, [ 'product_cat' => $item->slug ] + self::ordering_arg() ),
				'active' => $item->slug === $active,
			],
			array_slice( $facets, 0, self::FACET_LIMIT )
		);

		return [
			'items' => array_values( $items ),
			'total' => (int) $query->found_posts,
		];
	}

	/**
	 * Categoriile al căror nume conține termenul — sugestiile din panou („hanorac” -> „Hanorace & pulovere”).
	 */
	private static function matching_categories( string $term ): array {
		$terms = get_terms(
			[
				'taxonomy'   => 'product_cat',
				'name__like' => $term,
				'hide_empty' => true,
				'number'     => self::CATEGORY_LIMIT,
				'orderby'    => 'count',
				'order'      => 'DESC',
				'exclude'    => [ (int) get_option( 'default_product_cat' ) ],
			]
		);

		if ( is_wp_error( $terms ) ) {
			return [];
		}

		return array_map(
			static fn( WP_Term $item ): array => [
				'name'  => self::term_name( $item ),
				'url'   => (string) get_term_link( $item ),
				'count' => Checkout::count_label( (int) $item->count ),
			],
			$terms
		);
	}

	/**
	 * Un produs din panou: cardul din shop + categoria; `colors` devine numărul de culori.
	 */
	private static function tile( WC_Product $product ): array {
		$card             = Shop::card( $product );
		$card['colors']   = count( $card['colors'] );
		$card['category'] = self::primary_category( $product );

		return $card;
	}

	/**
	 * Categoria cea mai specifică a produsului („Tricouri”, nu „Topuri”).
	 */
	private static function primary_category( WC_Product $product ): ?string {
		$best  = null;
		$depth = -1;

		foreach ( wc_get_product_terms( $product->get_id(), 'product_cat' ) as $item ) {
			$item_depth = count( get_ancestors( $item->term_id, 'product_cat', 'taxonomy' ) );

			if ( $item_depth > $depth ) {
				$best  = $item;
				$depth = $item_depth;
			}
		}

		return $best ? self::term_name( $best ) : null;
	}

	private static function category_label( string $slug ): ?string {
		$item = '' !== $slug ? get_term_by( 'slug', $slug, 'product_cat' ) : null;

		return $item instanceof WP_Term ? self::term_name( $item ) : null;
	}

	/**
	 * @param array<int, WP_Post|WC_Product|int> $items
	 * @return WC_Product[]
	 */
	private static function visible( array $items ): array {
		$products = [];

		foreach ( $items as $item ) {
			$product = $item instanceof WC_Product ? $item : wc_get_product( $item );

			if ( $product && $product->is_visible() ) {
				$products[] = $product;
			}
		}

		return $products;
	}

	/**
	 * Sortarea aleasă se păstrează când schimbi categoria.
	 *
	 * @return array<string, string>
	 */
	private static function ordering_arg(): array {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- doar citim sortarea din URL
		return isset( $_GET['orderby'] ) ? [ 'orderby' => wc_clean( wp_unslash( $_GET['orderby'] ) ) ] : [];
	}

	/**
	 * Numele termenilor e salvat cu entități HTML („Hanorace &amp; pulovere”); template-urile escapează singure.
	 */
	private static function term_name( WP_Term $item ): string {
		return html_entity_decode( $item->name, ENT_QUOTES, 'UTF-8' );
	}

	private static function clean( string $value ): string {
		return mb_substr( trim( sanitize_text_field( $value ) ), 0, self::MAX_LENGTH );
	}

	/**
	 * Răspunsul e același pentru toți vizitatorii (prețurile nu depind de sesiune): browserul îl poate refolosi.
	 */
	private static function response( array $data ): WP_REST_Response {
		$response = new WP_REST_Response( $data );
		$response->header( 'Cache-Control', 'public, max-age=300' );

		return $response;
	}
}
