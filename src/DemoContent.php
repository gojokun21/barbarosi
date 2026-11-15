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
	private const CDN_FILES = 'https://dailypaperclothing.com/cdn/shop/files/';
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
		];
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
			'region'   => 'România',
			'payments' => [ 'visa', 'mastercard', 'maestro', 'applepay', 'googlepay' ],
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
				'products' => [
					self::product(
						'Geacă puffer Dias cu mâneci raglan — verde',
						'1.599 lei',
						'P260818123721_Fullbody_Jpg_High_res_5.jpg',
						'RAGLAN_SLEEVE_DIAS_PUFFER_JACKET_GRASSWHOPER_Front.jpg',
						[ [ 'name' => 'Verde', 'hex' => '#73a073' ], [ 'name' => 'Negru', 'hex' => '#504949' ] ]
					),
					self::product(
						'Hanorac boxy cu fermoar Stay Informed — negru',
						'849 lei',
						'P260818120630_Top_Jpg_High_res_4.jpg',
						'VINTAGE_STAY_INFORMED_BOXY_HOODIE_BLACK_Front.jpg'
					),
					self::product(
						'Bluză raglan cu contrast — portocaliu',
						'429 lei',
						'P260818112513_Top_Jpg_High_res_3.jpg',
						'CONTRAST_RAGLAN_LS_T-SHIRT_GOLDEN_ORANGE_Front.jpg',
						[ [ 'name' => 'Portocaliu', 'hex' => '#f0a830' ], [ 'name' => 'Verde', 'hex' => '#3f5b41' ] ]
					),
					self::product(
						'Tricou vintage Stay Informed — gri deschis',
						'299 lei',
						'P260818102746_Top_Jpg_High_res_12.jpg',
						'VINTAGE_STAY_INFORMED_T-SHIRT_LIGHT_GREY_Front.jpg'
					),
				],
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
						'products' => [
							self::product( 'Cămașă denim croc jacquard — albastru', '899 lei', 'FW26_-_CROC_JACQUARD_OVERSIZED_JEANS_Mid_BlueP2606101637171-12.jpg', 'FW26_-_CROC_JACQUARD_DENIM_SHIRT_Mid_Blue_Front_d84246b3-063e-4662-8eaa-bad53b32466e.jpg' ),
							self::product( 'Tricou Origami Shield — alb', '329 lei', 'White_Origami_Shield_T-Shirt.jpg', 'ORIGAMI_SHIELD_T-SHIRT_WHITE_BACK.jpg' ),
							self::product( 'Blugi oversized croc jacquard — albastru', '799 lei', 'FW26_-_CROC_JACQUARD_OVERSIZED_JEANS_Mid_BlueP260610163717_Fullbody_Jpg_High_res_13.jpg', 'FW26_-_CROC_JACQUARD_OVERSIZED_JEANS_Mid_Blue_Front_e6f1c52d-03a4-4f4b-91bd-5dadfb0a3e80.jpg' ),
						],
					],
					[
						'image'    => self::image( 'DSCF6089-2_6c2753c6-489a-4937-af04-bb5e031f30d8.jpg', 'Ținută cu pulover tricotat Shield', 1000 ),
						'products' => [
							self::product( 'Căciulă chapka Monogram — maro', '649 lei', 'FW26_-_MONOGRAM_CHAPKA_MADDER_BROWNP260610122202.jpg', 'FW26_-_MONOGRAM_CHAPKA_MADDER_BROWN__Front_2.jpg', [ [ 'name' => 'Maro', 'hex' => '#6b4a3a' ], [ 'name' => 'Vișiniu', 'hex' => '#7a1010' ] ] ),
							self::product( 'Pulover tricotat Shield cu fermoar — negru', '899 lei', 'FW26_-_SHIELD_KNIT_ZIP_UP_BLACK-BROWN_DETAILP2606101222021.jpg', 'FW26_-_SHIELD_KNIT_ZIP_UP_BLACK-BROWN_FRONT_14012695-f153-4f9e-9014-21b14bc272b6.jpg' ),
							self::product( 'Pantaloni cargo washed — verde army', '799 lei', 'FW26_-_WASHED_TWO_POCKET_CARGO_PANTS_GreenP260610102908.jpg', 'FW26_-_WASHED_TWO_POCKET_CARGO_PANTS_Green_Front_feaceee1-f7d6-40d4-8efd-a1dc28bfc6c6.jpg' ),
						],
					],
					[
						'image'    => self::image( 'DSCF6438-2.jpg', 'Ținută cu geacă puffer Ayan Monogram', 1000 ),
						'products' => [
							self::product( 'Geacă puffer Ayan Monogram — verde army', '1.699 lei', 'FW26_-_AYAN_MONOGRAM_JACKET_Stone_GrayP260610101621.jpg' ),
							self::product( 'Cămașă cu dungi texturate — bleu', '749 lei', 'FW26_-_TEXTURED_STRIPE_LS_SHIRT_LIGHT_BLUEP2606101232331.jpg' ),
							self::product( 'Pantaloni cargo washed — verde army', '799 lei', 'FW26_-_WASHED_TWO_POCKET_CARGO_PANTS_GreenP260610102908.jpg', 'FW26_-_WASHED_TWO_POCKET_CARGO_PANTS_Green_Front_feaceee1-f7d6-40d4-8efd-a1dc28bfc6c6.jpg' ),
						],
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
	 * Helpers pentru structuri repetitive
	 * ------------------------------------------------------------------- */

	/**
	 * @param array<int, array{name: string, hex: string}> $colors Prima culoare = varianta curentă.
	 */
	private static function product( string $title, string $price, string $image, ?string $hover_image = null, array $colors = [] ): array {
		return [
			'title'       => $title,
			'price'       => $price,
			'url'         => self::shop_url(),
			'badge'       => 'Nou',
			'image'       => self::image( $image, $title, 800 ),
			'hover_image' => $hover_image ? self::image( $hover_image, $title, 800 ) : null,
			'colors'      => $colors,
		];
	}

	private static function category_item( string $file, string $title ): array {
		return [
			'title' => $title,
			'url'   => self::shop_url(),
			'image' => self::image( $file, $title, 480 ),
		];
	}
}
