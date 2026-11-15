<?php

/**
 * Produse demo în WooCommerce, din catalogul src/demo/products.php.
 *
 *   wp barbarosi seed                 creează / actualizează produsele
 *   wp barbarosi seed --fresh         șterge întâi produsele demo, apoi le creează din nou
 *   wp barbarosi unseed [--images]    șterge produsele demo (și pozele, cu --images)
 *
 * Idempotent: produsele sunt găsite după meta `_barbarosi_demo` (cheia din catalog), iar pozele
 * după fișierul sursă — rularea repetată actualizează datele fără să descarce pozele din nou.
 * Atributele (Culoare, Mărime), categoriile și tag-ul „Nou” rămân și după `unseed`.
 *
 * TEMPORAR, ca tot conținutul demo — vezi src/DemoContent.php.
 */
final class DemoSeeder {
	private const META_KEY    = '_barbarosi_demo';
	private const META_SOURCE = '_barbarosi_demo_source';
	private const IMAGE_WIDTH = 1400;

	public static function register(): void {
		if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
			return;
		}

		$seeder = new self();
		WP_CLI::add_command( 'barbarosi seed', [ $seeder, 'seed' ] );
		WP_CLI::add_command( 'barbarosi unseed', [ $seeder, 'unseed' ] );
	}

	/**
	 * Creează sau actualizează produsele demo.
	 *
	 * ## OPTIONS
	 *
	 * [--fresh]
	 * : Șterge întâi produsele demo existente (pozele rămân și sunt refolosite).
	 *
	 * ## EXAMPLES
	 *
	 *     wp barbarosi seed
	 *     wp barbarosi seed --fresh
	 *
	 * @param string[]             $args
	 * @param array<string, mixed> $assoc_args
	 */
	public function seed( array $args, array $assoc_args ): void {
		$this->require_woocommerce();

		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$catalog = DemoContent::catalog();

		if ( WP_CLI\Utils\get_flag_value( $assoc_args, 'fresh', false ) ) {
			$this->delete_products( false );
		}

		$colors     = $this->ensure_attribute( 'culoare', 'Culoare', $catalog['colors'] );
		$sizes      = $this->ensure_attribute( 'marime', 'Mărime', $catalog['sizes'] );
		$categories = $this->ensure_categories( $catalog['categories'] );
		$new_tag    = $this->ensure_term( 'product_tag', 'nou', 'Nou' );

		$ids      = [];
		$position = 0;

		foreach ( $catalog['products'] as $key => $definition ) {
			WP_CLI::log( "→ {$definition['name']}" );

			$ids[ $key ] = $this->upsert_product( $key, $definition, $position++, $colors, $sizes, $categories, $new_tag );
		}

		$this->link_outfits( $ids, $catalog['outfits'] );

		wc_delete_product_transients();

		WP_CLI::success( sprintf( '%d produse demo create / actualizate.', count( $ids ) ) );
	}

	/**
	 * Șterge produsele demo (cu variațiile lor).
	 *
	 * ## OPTIONS
	 *
	 * [--images]
	 * : Șterge și pozele importate la seed.
	 *
	 * @param string[]             $args
	 * @param array<string, mixed> $assoc_args
	 */
	public function unseed( array $args, array $assoc_args ): void {
		$this->require_woocommerce();

		$count = $this->delete_products( (bool) WP_CLI\Utils\get_flag_value( $assoc_args, 'images', false ) );

		wc_delete_product_transients();

		WP_CLI::success( sprintf( '%d produse demo șterse.', $count ) );
	}

	/* ---------------------------------------------------------------------
	 * Produse
	 * ------------------------------------------------------------------- */

	/**
	 * @param array{id: int, taxonomy: string, terms: array<string, int>} $colors
	 * @param array{id: int, taxonomy: string, terms: array<string, int>} $sizes
	 * @param array<string, int>                                          $categories
	 */
	private function upsert_product( string $key, array $definition, int $position, array $colors, array $sizes, array $categories, int $new_tag ): int {
		$product = $this->find_product( $key ) ?? new WC_Product_Variable();

		$product->set_name( $definition['name'] );
		$product->set_slug( $key );
		$product->set_status( 'publish' );
		$product->set_catalog_visibility( 'visible' );
		$product->set_sku( $definition['sku'] );
		$product->set_menu_order( $position );
		$product->set_description( $definition['description'] );
		$product->set_short_description( $definition['model'] ?? '' );
		$product->set_category_ids( array_map( static fn( string $slug ): int => $categories[ $slug ], $definition['categories'] ) );
		$product->set_tag_ids( empty( $definition['new'] ) ? [] : [ $new_tag ] );
		$product->set_attributes(
			[
				$this->product_attribute( $colors, [ $definition['color'] ], 0, false ),
				$this->product_attribute( $sizes, $definition['sizes'], 1, true ),
			]
		);

		$product->update_meta_data( self::META_KEY, $key );
		$product->update_meta_data( Shop::META_SWATCH, $definition['hex'] );
		$product->update_meta_data( Shop::META_COLOR_GROUP, $definition['group'] ?? '' );
		$product->update_meta_data( Shop::META_SIZE_GUIDE, $definition['size_guide'] ?? '' );

		$product_id = $product->save();

		// Pozele au nevoie de ID-ul produsului (devin „atașate” lui în Media Library).
		$image_ids = $this->import_images( $definition['images'], $product_id, $key, $definition['name'] );
		$product->set_image_id( array_shift( $image_ids ) ?? 0 );
		$product->set_gallery_image_ids( $image_ids );
		$product->save();

		$this->sync_variations( $product, $definition, $sizes['taxonomy'] );

		return $product_id;
	}

	private function find_product( string $key ): ?WC_Product_Variable {
		$ids = get_posts(
			[
				'post_type'      => 'product',
				'post_status'    => 'any',
				'meta_key'       => self::META_KEY, // phpcs:ignore WordPress.DB.SlowDBQuery
				'meta_value'     => $key, // phpcs:ignore WordPress.DB.SlowDBQuery
				'fields'         => 'ids',
				'posts_per_page' => 1,
			]
		);

		$product = $ids ? wc_get_product( $ids[0] ) : null;

		return $product instanceof WC_Product_Variable ? $product : null;
	}

	/**
	 * O variație pe mărime; variațiile pentru mărimi scoase din catalog sunt șterse.
	 */
	private function sync_variations( WC_Product_Variable $product, array $definition, string $size_taxonomy ): void {
		$existing = [];

		foreach ( $product->get_children() as $variation_id ) {
			$variation = wc_get_product( $variation_id );
			if ( $variation instanceof WC_Product_Variation ) {
				$existing[ $variation->get_attributes()[ $size_taxonomy ] ?? '' ] = $variation;
			}
		}

		foreach ( $definition['sizes'] as $index => $size ) {
			$variation = $existing[ $size ] ?? new WC_Product_Variation();
			unset( $existing[ $size ] );

			$variation->set_parent_id( $product->get_id() );
			$variation->set_attributes( [ $size_taxonomy => $size ] );
			$variation->set_status( 'publish' );
			$variation->set_menu_order( $index );
			$variation->set_sku( $definition['sku'] . '-' . strtoupper( $size ) );
			$variation->set_regular_price( (string) $definition['price'] );
			$variation->set_sale_price( isset( $definition['sale_price'] ) ? (string) $definition['sale_price'] : '' );
			$variation->set_manage_stock( false );
			$variation->set_stock_status( in_array( $size, $definition['sold_out'] ?? [], true ) ? 'outofstock' : 'instock' );
			$variation->save();
		}

		foreach ( $existing as $variation ) {
			$variation->delete( true );
		}

		WC_Product_Variable::sync( $product->get_id() );
	}

	/**
	 * Upsells („Combină cu”) = celelalte produse din aceleași ținute.
	 *
	 * @param array<string, int>  $ids
	 * @param array<int, string[]> $outfits
	 */
	private function link_outfits( array $ids, array $outfits ): void {
		$combine = [];

		foreach ( $outfits as $outfit ) {
			foreach ( $outfit as $key ) {
				foreach ( $outfit as $other ) {
					if ( $other !== $key && isset( $ids[ $other ] ) ) {
						$combine[ $key ][ $ids[ $other ] ] = true;
					}
				}
			}
		}

		foreach ( $ids as $key => $id ) {
			$product = wc_get_product( $id );
			$product->set_upsell_ids( array_keys( $combine[ $key ] ?? [] ) );
			$product->save();
		}
	}

	private function delete_products( bool $with_images ): int {
		$ids = get_posts(
			[
				'post_type'      => 'product',
				'post_status'    => 'any',
				'meta_key'       => self::META_KEY, // phpcs:ignore WordPress.DB.SlowDBQuery
				'fields'         => 'ids',
				'posts_per_page' => -1,
			]
		);

		foreach ( $ids as $id ) {
			$product = wc_get_product( $id );
			if ( ! $product ) {
				continue;
			}

			foreach ( $product->get_children() as $variation_id ) {
				wc_get_product( $variation_id )?->delete( true );
			}

			$product->delete( true );
		}

		if ( $with_images ) {
			$attachments = get_posts(
				[
					'post_type'      => 'attachment',
					'post_status'    => 'inherit',
					'meta_key'       => self::META_SOURCE, // phpcs:ignore WordPress.DB.SlowDBQuery
					'fields'         => 'ids',
					'posts_per_page' => -1,
				]
			);

			foreach ( $attachments as $attachment_id ) {
				wp_delete_attachment( $attachment_id, true );
			}

			WP_CLI::log( sprintf( '%d poze șterse.', count( $attachments ) ) );
		}

		return count( $ids );
	}

	/* ---------------------------------------------------------------------
	 * Poze
	 * ------------------------------------------------------------------- */

	/**
	 * Descarcă pozele de pe CDN în Media Library (o singură dată per fișier).
	 *
	 * @param string[] $files
	 * @return int[] ID-urile atașamentelor, în ordinea din catalog
	 */
	private function import_images( array $files, int $product_id, string $key, string $alt ): array {
		$ids = [];

		foreach ( $files as $index => $file ) {
			$existing = get_posts(
				[
					'post_type'      => 'attachment',
					'post_status'    => 'inherit',
					'meta_key'       => self::META_SOURCE, // phpcs:ignore WordPress.DB.SlowDBQuery
					'meta_value'     => $file, // phpcs:ignore WordPress.DB.SlowDBQuery
					'fields'         => 'ids',
					'posts_per_page' => 1,
				]
			);

			if ( $existing ) {
				$ids[] = $existing[0];
				continue;
			}

			$tmp = download_url( DemoContent::CDN_FILES . $file . '?width=' . self::IMAGE_WIDTH, 60 );

			if ( is_wp_error( $tmp ) ) {
				WP_CLI::warning( "{$file}: {$tmp->get_error_message()}" );
				continue;
			}

			$extension     = strtolower( pathinfo( $file, PATHINFO_EXTENSION ) );
			$attachment_id = media_handle_sideload(
				[
					'name'     => sprintf( '%s-%d.%s', $key, $index + 1, $extension ),
					'tmp_name' => $tmp,
				],
				$product_id,
				$alt
			);

			if ( is_wp_error( $attachment_id ) ) {
				wp_delete_file( $tmp );
				WP_CLI::warning( "{$file}: {$attachment_id->get_error_message()}" );
				continue;
			}

			update_post_meta( $attachment_id, self::META_SOURCE, $file );
			update_post_meta( $attachment_id, '_wp_attachment_image_alt', $alt );

			WP_CLI::log( "  poză: {$file}" );
			$ids[] = $attachment_id;
		}

		return $ids;
	}

	/* ---------------------------------------------------------------------
	 * Taxonomii
	 * ------------------------------------------------------------------- */

	/**
	 * Atribut global WooCommerce (pa_*) cu termenii lui, în ordinea dată.
	 *
	 * @param array<string, string> $terms slug => nume
	 * @return array{id: int, taxonomy: string, terms: array<string, int>}
	 */
	private function ensure_attribute( string $slug, string $label, array $terms ): array {
		$taxonomy     = wc_attribute_taxonomy_name( $slug );
		$attribute_id = wc_attribute_taxonomy_id_by_name( $slug );

		if ( ! $attribute_id ) {
			$attribute_id = wc_create_attribute(
				[
					'name'         => $label,
					'slug'         => $slug,
					'type'         => 'select',
					'order_by'     => 'menu_order',
					'has_archives' => false,
				]
			);

			if ( is_wp_error( $attribute_id ) ) {
				WP_CLI::error( "Atributul {$label}: {$attribute_id->get_error_message()}" );
			}
		}

		// Taxonomia unui atribut nou se înregistrează abia la următorul request — o înregistrăm acum.
		if ( ! taxonomy_exists( $taxonomy ) ) {
			register_taxonomy( $taxonomy, [ 'product' ], [ 'hierarchical' => false, 'rewrite' => false ] );
		}

		$ids   = [];
		$order = 0;

		foreach ( $terms as $term_slug => $name ) {
			$term_id = $this->ensure_term( $taxonomy, $term_slug, $name );
			update_term_meta( $term_id, 'order', $order++ ); // ordinea folosită de WooCommerce (order_by = menu_order)
			$ids[ $term_slug ] = $term_id;
		}

		return [
			'id'       => (int) $attribute_id,
			'taxonomy' => $taxonomy,
			'terms'    => $ids,
		];
	}

	/**
	 * @param array{id: int, taxonomy: string, terms: array<string, int>} $attribute
	 * @param string[]                                                    $slugs
	 */
	private function product_attribute( array $attribute, array $slugs, int $position, bool $variation ): WC_Product_Attribute {
		$product_attribute = new WC_Product_Attribute();
		$product_attribute->set_id( $attribute['id'] );
		$product_attribute->set_name( $attribute['taxonomy'] );
		$product_attribute->set_options( array_map( static fn( string $slug ): int => $attribute['terms'][ $slug ], $slugs ) );
		$product_attribute->set_position( $position );
		$product_attribute->set_visible( true );
		$product_attribute->set_variation( $variation );

		return $product_attribute;
	}

	/**
	 * @param array<string, array{0: string, 1: ?string}> $categories slug => [ nume, slug părinte ]
	 * @return array<string, int>
	 */
	private function ensure_categories( array $categories ): array {
		$ids = [];

		foreach ( $categories as $slug => [ $name, $parent ] ) {
			$ids[ $slug ] = $this->ensure_term( 'product_cat', $slug, $name, $parent ? $ids[ $parent ] : 0 );
		}

		return $ids;
	}

	private function ensure_term( string $taxonomy, string $slug, string $name, int $parent = 0 ): int {
		$term = get_term_by( 'slug', $slug, $taxonomy );

		if ( $term ) {
			wp_update_term( $term->term_id, $taxonomy, [ 'name' => $name, 'parent' => $parent ] );
			return (int) $term->term_id;
		}

		$result = wp_insert_term( $name, $taxonomy, [ 'slug' => $slug, 'parent' => $parent ] );

		if ( is_wp_error( $result ) ) {
			WP_CLI::error( "{$taxonomy} „{$name}”: {$result->get_error_message()}" );
		}

		return (int) $result['term_id'];
	}

	private function require_woocommerce(): void {
		if ( ! class_exists( 'WooCommerce' ) ) {
			WP_CLI::error( 'WooCommerce nu e activ.' );
		}
	}
}
