<?php

/**
 * Conținut demo pentru header, footer și prima pagină.
 *
 * TEMPORAR: imaginile și video-ul sunt servite de pe CDN-ul dailypaperclothing.com
 * până avem pozele noastre. În producție înlocuim fiecare metodă de aici cu date reale
 * (produse WooCommerce, meniuri WP, câmpuri ACF) — template-urile Twig rămân la fel,
 * atâta timp cât se păstrează forma array-urilor.
 *
 * Forma unei imagini: [ 'src' => string, 'srcset' => string, 'alt' => string ]
 */
final class DemoContent {
	public const CDN_FILES  = 'https://dailypaperclothing.com/cdn/shop/files/';
	private const CDN_VIDEO = 'https://dailypaperclothing.com/cdn/shop/videos/c/vp/';

	private const SRCSET_WIDTHS = [ 320, 480, 640, 800, 1000, 1200, 1600, 2000 ];

	/**
	 * Imagine responsive de pe CDN-ul Shopify (acceptă `?width=` pentru redimensionare).
	 */
	public static function image( string $file, string $alt = '', int $default_width = 1200 ): array {
		$base = self::CDN_FILES . $file;

		$srcset = array_map(
			static fn( int $width ): string => "{$base}?width={$width} {$width}w",
			self::SRCSET_WIDTHS
		);

		return [
			'src'    => "{$base}?width={$default_width}",
			'srcset' => implode( ', ', $srcset ),
			'alt'    => $alt,
		];
	}

	/* ---------------------------------------------------------------------
	 * URL-uri (WooCommerce dacă e activ, altfel slug-urile implicite)
	 * ------------------------------------------------------------------- */

	public static function shop_url(): string {
		return function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' );
	}

	public static function account_url(): string {
		return function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : home_url( '/my-account/' );
	}

	public static function cart_url(): string {
		return function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : home_url( '/cart/' );
	}

	/**
	 * Transformă o listă de titluri în link-uri (deocamdată toate duc spre shop).
	 *
	 * @param string[] $titles
	 */
	private static function links( array $titles, ?string $url = null ): array {
		$url = $url ?? self::shop_url();

		return array_map(
			static fn( string $title ): array => [ 'title' => $title, 'url' => $url ],
			$titles
		);
	}

	/* ---------------------------------------------------------------------
	 * Header (bară de anunțuri + navigație cu mega meniu)
	 * ------------------------------------------------------------------- */

	public static function header(): array {
		$shop = self::shop_url();

		return [
			'announcements' => [
				'Colecția FW26 Holiday e live',
				'Descoperă colecția The Foundation',
			],
			'region'        => 'România',
			'language'      => 'Română',
			'membership'    => [ 'title' => 'Membership', 'url' => '#' ],
			'account_url'   => self::account_url(),
			'cart_url'      => self::cart_url(),
			'cart_count'    => class_exists( 'Shop' ) ? Shop::cart_count() : 0,
			'nav'           => [
				[
					'key'   => 'new',
					'title' => 'Noutăți',
					'url'   => $shop,
				],
				[
					'key'   => 'shop',
					'title' => 'Magazin',
					'url'   => $shop,
					'mega'  => [
						'columns' => [
							[
								'title' => 'Topuri',
								'links' => self::links( [ 'Toate topurile', 'Hanorace & pulovere', 'Tricouri', 'Cămăși', 'Bluze cu mânecă lungă', 'Geci', 'Tricotaje' ] ),
							],
							[
								'title' => 'Pantaloni',
								'links' => self::links( [ 'Toți pantalonii', 'Pantaloni', 'Blugi', 'Pantaloni de trening', 'Pantaloni scurți', 'Costume de baie' ] ),
							],
							[
								'title' => 'Accesorii',
								'links' => self::links( [ 'Toate accesoriile', 'Genți', 'Șepci & căciuli', 'Card cadou digital', 'Alte accesorii' ] ),
							],
							[
								'title' => 'Highlights sezon',
								'links' => self::links( [ 'Ghid de cadouri', 'Colecția arhivă', 'Outerwear', 'Compleuri', 'Denim' ] ),
							],
						],
						'promos'  => [
							[
								'image'    => self::image( 'HP2-LAUNCH-FW26.jpg', 'Colecția FW26', 800 ),
								'title'    => 'Now live',
								'subtitle' => 'FW26',
								'cta'      => 'Cumpără acum',
								'url'      => $shop,
							],
							[
								'image'    => self::image( 'FW26LOOKBOOK_0338_20989a45-69c8-4592-9d56-d56c247ea41b.jpg', 'Lookbook FW26', 800 ),
								'title'    => 'Lookbook',
								'subtitle' => 'FW26',
								'cta'      => 'Inspiră-te',
								'url'      => $shop,
							],
						],
					],
				],
				[
					'key'   => 'collections',
					'title' => 'Colecții',
					'url'   => $shop,
					'mega'  => [
						'columns' => [
							[
								'title' => 'Colecții',
								'links' => self::links( [ 'FW26 Holiday', 'The Foundation', 'FW26', 'SS26 Resort', 'Essentials', 'Monogram' ] ),
							],
							[
								'title' => 'Highlights sezon',
								'links' => self::links( [ 'Noutăți', 'Bestsellers', 'Colecția arhivă', 'Lookbook' ] ),
							],
						],
						'promos'  => [
							[
								'image'    => self::image( 'P260818161424_Fullbody_Jpg_High_res_4-2.jpg', 'Colecția FW26 Holiday', 800 ),
								'title'    => 'FW26 Holiday',
								'subtitle' => 'Disponibil acum',
								'cta'      => 'Cumpără acum',
								'url'      => $shop,
							],
							[
								'image'    => self::image( 'DSCF6438-2.jpg', 'Colecția FW26', 800 ),
								'title'    => 'FW26',
								'subtitle' => 'Colecția',
								'cta'      => 'Descoperă',
								'url'      => $shop,
							],
						],
					],
				],
			],
			'nav_secondary' => self::links( [ 'FAQ', 'Magazine', 'Cariere', 'Livrare', 'Retururi', 'Plăți' ], '#' ),
			// Panoul de căutare (partial/header-search.twig)
			'search'        => [
				'popular' => class_exists( 'Search' ) ? Search::popular() : [],
			],
		];
	}

	/**
	 * Căutările sugerate în panoul de căutare și pe pagina de rezultate (fără termen / fără rezultate).
	 * TODO producție: dintr-o opțiune editabilă în admin sau din cei mai căutați termeni.
	 *
	 * @return string[]
	 */
	public static function popular_searches(): array {
		return [ 'Geacă puffer', 'Hanorac', 'Monogram', 'Blugi', 'Cămașă', 'Căciulă' ];
	}

	/* ---------------------------------------------------------------------
	 * Footer
	 * ------------------------------------------------------------------- */

	public static function footer(): array {
		return [
			'columns'  => [
				[
					'title' => 'Topuri',
					'links' => self::links( [ 'Tricouri', 'Cămăși', 'Bluze cu mânecă lungă', 'Hanorace', 'Pulovere', 'Geci', 'Toate topurile' ] ),
				],
				[
					'title' => 'Pantaloni',
					'links' => self::links( [ 'Pantaloni scurți', 'Blugi', 'Pantaloni', 'Pantaloni de trening', 'Costume de baie', 'Toți pantalonii' ] ),
				],
				[
					'title' => 'Highlights sezon',
					'links' => self::links( [ 'Noutăți', 'Bestsellers', 'Essentials', 'Monogram' ] ),
				],
				[
					'title' => 'Barbarosi',
					'links' => self::links( [ 'Despre brand', 'Membership', 'Cariere', 'Magazine', '#Barbarosi' ], '#' ),
				],
				[
					'title' => 'Ajutor',
					'links' => array_merge(
						self::links( [ 'FAQ', 'Termeni și condiții' ], '#' ),
						[ [ 'title' => 'Politica de confidențialitate', 'url' => get_privacy_policy_url() ?: '#' ] ],
						self::links( [ 'Livrare', 'Plată', 'Formular de retur' ], '#' )
					),
				],
			],
			'social'   => self::links( [ 'X', 'Facebook', 'Instagram', 'TikTok' ], '#' ),
		];
	}

	/**
	 * Logo-urile din partial/payment-icon.twig (sumarul coșului; footer-ul folosește assets/images/payment-methods.png).
	 *
	 * @return string[]
	 */
	public static function payment_methods(): array {
		return [ 'visa', 'mastercard', 'maestro', 'applepay', 'googlepay' ];
	}

	/**
	 * Link-urile din footer-ul simplificat al checkout-ului: paginile setate în WordPress / WooCommerce,
	 * altfel `#` până există paginile.
	 *
	 * @return array<int, array{title: string, url: string}>
	 */
	public static function legal_links(): array {
		$terms_id = function_exists( 'wc_terms_and_conditions_page_id' ) ? wc_terms_and_conditions_page_id() : 0;

		return [
			[ 'title' => 'Termeni și condiții', 'url' => $terms_id ? (string) get_permalink( $terms_id ) : '#' ],
			[ 'title' => 'Politica de confidențialitate', 'url' => get_privacy_policy_url() ?: '#' ],
			[ 'title' => 'Livrare', 'url' => '#' ],
			[ 'title' => 'Retur', 'url' => '#' ],
		];
	}

	/* ---------------------------------------------------------------------
	 * Prima pagină — secțiunile, în ordinea afișării
	 * ------------------------------------------------------------------- */

	public static function front_page(): array {
		$shop      = self::shop_url();
		$video_id  = 'e859363c3c604875bbbbe394c501ed03';

		return [
			'hero'            => [
				'video'        => self::CDN_VIDEO . "{$video_id}/{$video_id}.HD-1080p-7.2Mbps-98935623.mp4",
				'poster'       => self::CDN_FILES . "preview_images/{$video_id}.thumbnail.0000000000.jpg",
				'title_strong' => 'FW26 Holiday',
				'title_light'  => 'Disponibil acum',
				'cta'          => 'Cumpără acum',
				'url'          => $shop,
			],

			'new_arrivals'    => [
				'products' => self::products(
					[
						'geaca-puffer-dias-raglan-verde',
						'hanorac-boxy-stay-informed-negru',
						'bluza-raglan-contrast-portocaliu',
						'tricou-vintage-stay-informed-gri-deschis',
					]
				),
			],

			'split_banners'   => [
				[
					'image'        => self::image( '000039030016_1.jpg', 'Colecția The Foundation' ),
					'title_strong' => 'The Foundation',
					'title_light'  => 'Drop nou',
					'cta'          => 'Descoperă',
					'url'          => $shop,
				],
				[
					'image'        => self::image( 'DP_HOLIDAY_1519_1.jpg', 'Geci de iarnă' ),
					'title_strong' => 'Geci',
					'title_light'  => 'Toamnă / Iarnă 2026',
					'cta'          => 'Cumpără acum',
					'url'          => $shop,
				],
			],

			'collection'      => [
				'image'        => self::image( 'hp-hero-img.png', 'Colecția FW26', 2000 ),
				'title_strong' => 'FW26',
				'title_light'  => 'Colecția',
				'cta'          => 'Cumpără acum',
				'url'          => $shop,
			],

			'looks'           => [
				'title' => 'Shop the look',
				'items' => [
					[
						'image'    => self::image( 'DSCF6404-2.jpg', 'Ținută cu cămașă și blugi denim croc jacquard', 1000 ),
						'products' => self::products( [ 'camasa-denim-croc-jacquard-albastru', 'tricou-origami-shield-alb', 'blugi-oversized-croc-jacquard-albastru' ] ),
					],
					[
						'image'    => self::image( 'DSCF6089-2_6c2753c6-489a-4937-af04-bb5e031f30d8.jpg', 'Ținută cu pulover tricotat Shield', 1000 ),
						'products' => self::products( [ 'caciula-chapka-monogram-maro', 'pulover-tricotat-shield-fermoar-negru', 'pantaloni-cargo-washed-verde-army' ] ),
					],
					[
						'image'    => self::image( 'DSCF6438-2.jpg', 'Ținută cu geacă puffer Ayan Monogram', 1000 ),
						'products' => self::products( [ 'geaca-puffer-ayan-monogram-verde-army', 'camasa-dungi-texturate-bleu', 'pantaloni-cargo-washed-verde-army' ] ),
					],
				],
			],

			'category'        => [
				'title_strong' => 'Hanorace & pulovere',
				'title_light'  => 'FW26',
				'items'        => [
					self::category_item( 'Shield_Knit_Zip_Up_Sweater_3fbcf6a6-5a03-437d-95cf-d3843f68d7a2.png', 'Pulover tricotat Shield cu fermoar' ),
					self::category_item( 'Hawaii_Dias_Washed_Hoodie_e7f1ebc4-82eb-4b0b-b539-07a38c5b1dd2.png', 'Hanorac Dias washed' ),
					self::category_item( 'Self_Fabric_Hoodie_aeebbc27-3d85-495d-b5a9-5c838bc9f333.png', 'Hanorac Self Fabric' ),
					self::category_item( 'Orbit_Emb_Zip_Sweater_eb96c96c-d2c9-4884-b2a3-6398ea2c6dd4.png', 'Pulover Orbit cu fermoar' ),
					self::category_item( 'V-Neck_Knit_Sweater_545bc09a-d94b-4428-aca1-17caf8e69b65.png', 'Pulover tricotat cu decolteu în V' ),
					self::category_item( 'Polo_Shield_Sweater_05935275-3bbd-4d13-bb28-5ef43f31e317.png', 'Pulover polo Shield' ),
				],
			],

			'newsletter'      => [
				'title_strong' => 'Rămâi',
				'title_light'  => 'la curent',
				'text'         => 'Abonează-te ca să afli primul despre lansări, oferte speciale și multe altele.',
				'privacy_url'  => get_privacy_policy_url() ?: '#',
			],

			'tiles'           => [
				[
					'image'        => self::image( 'become_member_hp_block.png', 'Comunitatea Barbarosi', 1000 ),
					'title_strong' => 'Devino',
					'title_light'  => 'membru',
					'url'          => '#',
				],
				[
					'image'        => self::image( 'discover_store_hp_block.jpg', 'Magazinul Barbarosi', 1000 ),
					'title_strong' => 'Descoperă',
					'title_light'  => 'magazinul nostru',
					'url'          => '#',
				],
				[
					'image'        => self::image( 'join_our_channel_hp_block.png', 'Barbarosi pe Instagram', 1000 ),
					'title_strong' => 'Alătură-te',
					'title_light'  => 'canalului de Instagram',
					'url'          => '#',
				],
			],
		];
	}

	/* ---------------------------------------------------------------------
	 * Pagina de produs
	 * ------------------------------------------------------------------- */

	/**
	 * Avantajele de sub descriere (livrare, retur, plată).
	 *
	 * @return array<int, array{icon: string, text: string}>
	 */
	public static function product_perks(): array {
		return [
			[
				'icon' => 'truck',
				/* translators: %s: suma minimă pentru livrare gratuită */
				'text' => sprintf( __( 'Livrare gratuită la comenzile de peste %s', 'barbarosi' ), self::price( 500 ) ),
			],
			[
				'icon' => 'box',
				'text' => __( 'Retur gratuit în 30 de zile', 'barbarosi' ),
			],
			[
				'icon' => 'card',
				'text' => __( 'Plată securizată cu cardul, Apple Pay sau Google Pay', 'barbarosi' ),
			],
		];
	}

	/**
	 * Tabelul de mărimi după tipul produsului (meta `_barbarosi_size_guide`: tops | bottoms).
	 *
	 * @return array{title: string, columns: string[], rows: array<int, array<int, string|int>>, note: string}|null
	 */
	public static function size_guide( string $type ): ?array {
		$note = __( 'Măsurătorile sunt în centimetri, luate pe produsul așezat întins. Pot varia cu ±1–2 cm.', 'barbarosi' );

		$guides = [
			'tops'    => [
				'title'   => __( 'Ghid de mărimi — topuri', 'barbarosi' ),
				'columns' => [ __( 'Mărime', 'barbarosi' ), __( 'Piept', 'barbarosi' ), __( 'Lungime', 'barbarosi' ), __( 'Mânecă', 'barbarosi' ) ],
				'rows'    => [
					[ 'XS', 104, 68, 60 ],
					[ 'S', 108, 70, 61 ],
					[ 'M', 112, 72, 62 ],
					[ 'L', 116, 74, 63 ],
					[ 'XL', 120, 76, 64 ],
					[ 'XXL', 124, 78, 65 ],
				],
				'note'    => $note,
			],
			'bottoms' => [
				'title'   => __( 'Ghid de mărimi — pantaloni', 'barbarosi' ),
				'columns' => [ __( 'Mărime', 'barbarosi' ), __( 'Talie', 'barbarosi' ), __( 'Șold', 'barbarosi' ), __( 'Lungime', 'barbarosi' ) ],
				'rows'    => [
					[ 'XS', 72, 100, 102 ],
					[ 'S', 76, 104, 103 ],
					[ 'M', 80, 108, 104 ],
					[ 'L', 84, 112, 105 ],
					[ 'XL', 88, 116, 106 ],
					[ 'XXL', 92, 120, 107 ],
				],
				'note'    => $note,
			],
		];

		return $guides[ $type ] ?? null;
	}

	/* ---------------------------------------------------------------------
	 * Produse demo (catalogul din src/demo/products.php)
	 * ------------------------------------------------------------------- */

	/**
	 * Catalogul demo — sursa pentru `wp barbarosi seed` și pentru cardurile de mai jos.
	 */
	public static function catalog(): array {
		static $catalog = null;

		return $catalog ??= require __DIR__ . '/demo/products.php';
	}

	/**
	 * Carduri de produs după cheile din catalog.
	 *
	 * @param string[] $keys
	 */
	private static function products( array $keys ): array {
		return array_values( array_filter( array_map( [ self::class, 'product' ], $keys ) ) );
	}

	/**
	 * Produsul din WooCommerce (după seed); până atunci, un card static din catalog cu link spre shop.
	 */
	private static function product( string $key ): ?array {
		$card = class_exists( 'Shop' ) ? Shop::card_by_slug( $key ) : null;

		if ( $card ) {
			return $card;
		}

		$catalog    = self::catalog();
		$definition = $catalog['products'][ $key ] ?? null;

		if ( ! $definition ) {
			return null;
		}

		// Culoarea curentă prima, apoi celelalte culori din același grup.
		$colors = [ [ 'name' => $catalog['colors'][ $definition['color'] ], 'hex' => $definition['hex'] ] ];

		foreach ( $catalog['products'] as $other_key => $other ) {
			if ( $other_key !== $key && ! empty( $definition['group'] ) && ( $other['group'] ?? '' ) === $definition['group'] ) {
				$colors[] = [ 'name' => $catalog['colors'][ $other['color'] ], 'hex' => $other['hex'] ];
			}
		}

		$on_sale = isset( $definition['sale_price'] );

		return [
			'title'         => $definition['name'],
			'price'         => self::price( $definition['sale_price'] ?? $definition['price'] ),
			'regular_price' => $on_sale ? self::price( $definition['price'] ) : null,
			'url'           => self::shop_url(),
			'badge'         => empty( $definition['new'] ) ? null : 'Nou',
			'image'         => self::image( $definition['images'][0], $definition['name'], 800 ),
			'hover_image'   => isset( $definition['images'][1] ) ? self::image( $definition['images'][1], $definition['name'], 800 ) : null,
			'colors'        => $colors,
		];
	}

	private static function price( float $amount ): string {
		return function_exists( 'wc_price' ) ? Shop::price_text( $amount ) : number_format( $amount, 0, ',', '.' ) . ' lei';
	}

	/* ---------------------------------------------------------------------
	 * Helpers pentru structuri repetitive
	 * ------------------------------------------------------------------- */

	private static function category_item( string $file, string $title ): array {
		return [
			'title' => $title,
			'url'   => self::shop_url(),
			'image' => self::image( $file, $title, 480 ),
		];
	}
}
