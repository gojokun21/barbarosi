<?php

/**
 * Coșul și checkout-ul WooCommerce (flux clasic, cu shortcode).
 *
 * Markup-ul e în template-urile suprascrise din woocommerce/cart/ și woocommerce/checkout/; aici sunt
 * filtrele (câmpuri, texte, ordinea elementelor) și helper-ele folosite de acele template-uri.
 *
 * Template-urile păstrează hook-urile și clasele de care depind cart.js / checkout.js din WooCommerce
 * (actualizările AJAX), așa că plugin-urile de plată și livrare funcționează în continuare.
 */
final class Checkout {
	/** Câmpurile de facturare afișate în secțiunea „Contact” (restul formează adresa). */
	public const CONTACT_FIELDS = [ 'billing_email', 'billing_phone' ];

	private const LAYOUT_CLASSES = [ 'form-row-first', 'form-row-last', 'form-row-wide' ];

	public static function init(): void {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}

		add_action( 'after_setup_theme', [ self::class, 'move_template_parts' ], 20 );
		add_action( 'wp_enqueue_scripts', [ self::class, 'dequeue_assets' ], 100 );

		// Câmpurile formularului
		add_filter( 'woocommerce_default_address_fields', [ self::class, 'address_fields' ] );
		add_filter( 'woocommerce_billing_fields', [ self::class, 'billing_fields' ] );
		add_filter( 'woocommerce_checkout_fields', [ self::class, 'checkout_fields' ] );
		add_filter( 'woocommerce_get_country_locale', [ self::class, 'country_locale' ] );

		// Metodele de livrare stau în coloana formularului — le reîmprospătăm odată cu sumarul.
		add_filter( 'woocommerce_update_order_review_fragments', [ self::class, 'shipping_fragment' ] );

		// Texte
		add_filter( 'woocommerce_order_button_text', static fn() => __( 'Plasează comanda', 'barbarosi' ) );
		add_filter( 'woocommerce_cart_totals_coupon_label', [ self::class, 'coupon_label' ], 10, 2 );
		add_filter( 'woocommerce_cart_totals_coupon_html', [ self::class, 'coupon_html' ], 10, 3 );
		add_filter( 'woocommerce_thankyou_order_received_text', [ self::class, 'order_received_text' ], 10, 2 );

		// Fără „Cart updated.” la fiecare +/−: cart.js ar derula pagina la mesaj și i-ar muta focusul.
		// Totalurile actualizate sunt feedback-ul vizual; cititoarele de ecran anunță numărul de produse (aria-live).
		add_filter( 'woocommerce_add_success', [ self::class, 'skip_cart_updated_notice' ] );

		// „Completează ținuta” sub coș: cross-sells, iar dacă nu sunt setate, upsells (ținutele din „Combină cu”).
		add_filter( 'woocommerce_cart_crosssell_ids', [ self::class, 'cross_sell_ids' ], 10, 2 );
		add_filter( 'woocommerce_cross_sells_total', static fn() => 4 );
	}

	/**
	 * Mută elementele pe care WooCommerce le afișează prin hook-uri în alt loc decât vrem noi.
	 * (după `after_setup_theme`, când hook-urile implicite sunt deja înregistrate)
	 */
	public static function move_template_parts(): void {
		// Codul de reducere stă în sumarul comenzii (checkout/form-checkout.php), nu deasupra formularului.
		remove_action( 'woocommerce_before_checkout_form', 'woocommerce_checkout_coupon_form', 10 );

		// Sumarul e în coloana din dreapta; în `#order_review` rămâne doar plata.
		remove_action( 'woocommerce_checkout_order_review', 'woocommerce_order_review', 10 );

		// Produsele sugerate apar pe toată lățimea, sub coș — nu lângă totaluri.
		remove_action( 'woocommerce_cart_collaterals', 'woocommerce_cross_sell_display' );
		add_action( 'woocommerce_after_cart', 'woocommerce_cross_sell_display' );

		// Coșul gol are mesajul lui (cart/cart-empty.php).
		remove_action( 'woocommerce_cart_is_empty', 'wc_empty_cart_message', 10 );
	}

	/**
	 * Select-uri native pentru țară / județ (mai bune pe mobil, fără CSS-ul select2).
	 * Stilurile generale WooCommerce sunt scoase în Shop::init().
	 */
	public static function dequeue_assets(): void {
		if ( is_cart() || is_checkout() ) {
			wp_dequeue_script( 'selectWoo' );
			wp_dequeue_style( 'select2' );
		}
	}

	/* ---------------------------------------------------------------------
	 * Câmpuri
	 * ------------------------------------------------------------------- */

	/**
	 * Adresa (facturare, livrare, contul meu): etichete, ordine și așezare pe două coloane.
	 * Ordinea: prenume | nume, țară, adresă, bloc/ap., cod poștal | localitate, județ.
	 *
	 * @param array<string, array<string, mixed>> $fields
	 */
	public static function address_fields( array $fields ): array {
		$overrides = [
			'first_name' => [ 'label' => __( 'Prenume', 'barbarosi' ), 'priority' => 10, 'layout' => 'form-row-first' ],
			'last_name'  => [ 'label' => __( 'Nume', 'barbarosi' ), 'priority' => 20, 'layout' => 'form-row-last' ],
			'company'    => [ 'label' => __( 'Companie', 'barbarosi' ), 'priority' => 25, 'layout' => 'form-row-wide' ],
			'country'    => [ 'label' => __( 'Țara', 'barbarosi' ), 'priority' => 30, 'layout' => 'form-row-wide' ],
			'address_1'  => [
				'label'       => __( 'Adresa', 'barbarosi' ),
				'placeholder' => __( 'Strada și numărul', 'barbarosi' ),
				'priority'    => 40,
				'layout'      => 'form-row-wide',
			],
			'address_2'  => [
				'label'       => __( 'Bloc, scară, apartament', 'barbarosi' ),
				'label_class' => [],
				'placeholder' => '',
				'priority'    => 50,
				'layout'      => 'form-row-wide',
			],
			'postcode'   => [ 'label' => __( 'Cod poștal', 'barbarosi' ), 'priority' => 60, 'layout' => 'form-row-first' ],
			'city'       => [ 'label' => __( 'Localitate', 'barbarosi' ), 'priority' => 70, 'layout' => 'form-row-last' ],
			'state'      => [ 'label' => __( 'Județ', 'barbarosi' ), 'priority' => 80, 'layout' => 'form-row-wide' ],
			// Din WC 11 telefonul e câmp de adresă: address-i18n.js îi reaplică eticheta și clasa de aici.
			// La facturare stă în „Contact”, lângă email (vezi billing_fields).
			'phone'      => [ 'label' => __( 'Telefon', 'barbarosi' ), 'layout' => 'form-row-last' ],
		];

		foreach ( $overrides as $key => $override ) {
			if ( ! isset( $fields[ $key ] ) ) {
				continue;
			}

			$fields[ $key ]['class'] = self::with_layout( (array) ( $fields[ $key ]['class'] ?? [] ), $override['layout'] );
			unset( $override['layout'] );

			$fields[ $key ] = array_merge( $fields[ $key ], $override );
		}

		return $fields;
	}

	/**
	 * Emailul și telefonul — secțiunea „Contact”, una lângă alta.
	 *
	 * @param array<string, array<string, mixed>> $fields
	 */
	public static function billing_fields( array $fields ): array {
		$has_phone = isset( $fields['billing_phone'] );

		if ( isset( $fields['billing_email'] ) ) {
			$fields['billing_email']['label'] = __( 'Email', 'barbarosi' );
			$fields['billing_email']['class'] = self::with_layout( (array) $fields['billing_email']['class'], $has_phone ? 'form-row-first' : 'form-row-wide' );
		}

		if ( $has_phone ) {
			$fields['billing_phone']['label'] = __( 'Telefon', 'barbarosi' );
			$fields['billing_phone']['class'] = self::with_layout( (array) $fields['billing_phone']['class'], 'form-row-last' );
		}

		return $fields;
	}

	/**
	 * @param array<string, array<string, array<string, mixed>>> $fields
	 */
	public static function checkout_fields( array $fields ): array {
		if ( isset( $fields['order']['order_comments'] ) ) {
			$fields['order']['order_comments']['label']       = __( 'Observații', 'barbarosi' );
			$fields['order']['order_comments']['placeholder'] = __( 'Detalii pentru curier sau despre comandă', 'barbarosi' );
		}

		return $fields;
	}

	/**
	 * WooCommerce schimbă eticheta județului după țară (address-i18n.js) — o traducem pentru piețele noastre.
	 *
	 * @param array<string, array<string, array<string, mixed>>> $locale
	 */
	public static function country_locale( array $locale ): array {
		$locale['RO']['state']['label'] = __( 'Județ', 'barbarosi' );
		$locale['MD']['state']['label'] = __( 'Raion / municipiu', 'barbarosi' );

		return $locale;
	}

	/**
	 * Înlocuiește clasa de așezare (first / last / wide), păstrând restul claselor (ex. `address-field`).
	 *
	 * @param string[] $classes
	 * @return string[]
	 */
	private static function with_layout( array $classes, string $layout ): array {
		return array_merge( [ $layout ], array_values( array_diff( $classes, self::LAYOUT_CLASSES ) ) );
	}

	/* ---------------------------------------------------------------------
	 * Livrare
	 * ------------------------------------------------------------------- */

	/**
	 * Metodele de livrare din formular (checkout/form-checkout.php). Rândurile vin din cart/cart-shipping.php.
	 */
	public static function shipping_methods_html(): string {
		ob_start();

		echo '<div class="checkout-shipping">';

		if ( WC()->cart->show_shipping() ) {
			wc_cart_totals_shipping_html();
		} else {
			echo '<p class="checkout-shipping__message">' . esc_html__( 'Completează adresa pentru a vedea opțiunile de livrare.', 'barbarosi' ) . '</p>';
		}

		echo '</div>';

		return (string) ob_get_clean();
	}

	/**
	 * La fiecare `update_order_review` (schimbare de adresă, metodă etc.) WooCommerce înlocuiește
	 * fragmentele după selector — adăugăm și lista metodelor de livrare.
	 *
	 * @param array<string, string> $fragments
	 */
	public static function shipping_fragment( array $fragments ): array {
		if ( WC()->cart->needs_shipping() ) {
			$fragments['.checkout-shipping'] = self::shipping_methods_html();
		}

		return $fragments;
	}

	/**
	 * Costul livrării pentru rândul din sumar: preț, „Gratuită” sau null dacă nu e încă aleasă o metodă.
	 */
	public static function shipping_total_html(): ?string {
		$cart = WC()->cart;

		if ( ! $cart->show_shipping() ) {
			return null;
		}

		$packages = WC()->shipping()->get_packages();
		$chosen   = (array) WC()->session->get( 'chosen_shipping_methods', [] );

		if ( ! $packages ) {
			return null;
		}

		foreach ( $packages as $index => $package ) {
			if ( empty( $chosen[ $index ] ) || ! isset( $package['rates'][ $chosen[ $index ] ] ) ) {
				return null;
			}
		}

		$total = (float) $cart->get_shipping_total();

		if ( $cart->display_prices_including_tax() ) {
			$total += (float) $cart->get_shipping_tax();
		}

		return $total > 0 ? wc_price( $total ) : esc_html__( 'Gratuită', 'barbarosi' );
	}

	/**
	 * Cât mai e până la livrarea gratuită — doar dacă zona clientului are „Free shipping” cu sumă minimă.
	 *
	 * @return array{remaining: string, percent: int}|null  remaining = '' când pragul e atins
	 */
	public static function free_shipping_progress(): ?array {
		$cart      = WC()->cart;
		$packages  = $cart->needs_shipping() ? $cart->get_shipping_packages() : [];
		$threshold = 0.0;
		$method    = null;

		if ( $packages ) {
			$zone = WC_Shipping_Zones::get_zone_matching_package( reset( $packages ) );

			foreach ( $zone->get_shipping_methods( true ) as $candidate ) {
				if ( $candidate instanceof WC_Shipping_Free_Shipping && in_array( $candidate->requires, [ 'min_amount', 'either' ], true ) && (float) $candidate->min_amount > 0 ) {
					$method    = $candidate;
					$threshold = (float) $candidate->min_amount;
					break;
				}
			}
		}

		if ( ! $method ) {
			return null;
		}

		// Același calcul ca WC_Shipping_Free_Shipping::is_available().
		$total = (float) $cart->get_displayed_subtotal();

		if ( 'no' === $method->ignore_discounts ) {
			$total -= (float) $cart->get_discount_total();

			if ( $cart->display_prices_including_tax() ) {
				$total -= (float) $cart->get_discount_tax();
			}
		}

		$total     = round( $total, wc_get_price_decimals() );
		$remaining = max( 0, $threshold - $total );

		return [
			'remaining' => $remaining > 0 ? Shop::price_text( $remaining ) : '',
			'percent'   => (int) min( 100, floor( $total / $threshold * 100 ) ),
		];
	}

	/* ---------------------------------------------------------------------
	 * Coduri de reducere
	 * ------------------------------------------------------------------- */

	/**
	 * @param string    $label
	 * @param WC_Coupon $coupon
	 */
	public static function coupon_label( $label, $coupon ): string {
		/* translators: %s: codul de reducere */
		return sprintf( __( 'Reducere (%s)', 'barbarosi' ), strtoupper( $coupon->get_code() ) );
	}

	/**
	 * Suma reducerii + link „Elimină” (clasa `woocommerce-remove-coupon` e folosită de cart.js / checkout.js).
	 *
	 * @param string    $html
	 * @param WC_Coupon $coupon
	 * @param string    $discount_amount_html
	 */
	public static function coupon_html( $html, $coupon, $discount_amount_html ): string {
		$url = add_query_arg( 'remove_coupon', rawurlencode( $coupon->get_code() ), is_checkout() ? wc_get_checkout_url() : wc_get_cart_url() );

		return sprintf(
			'<span class="summary-totals__discount">%s</span><a href="%s" class="woocommerce-remove-coupon summary-totals__remove" data-coupon="%s" role="button">%s</a>',
			$discount_amount_html,
			esc_url( $url ),
			esc_attr( $coupon->get_code() ),
			esc_html__( 'Elimină', 'barbarosi' )
		);
	}

	/* ---------------------------------------------------------------------
	 * Diverse
	 * ------------------------------------------------------------------- */

	/**
	 * „1 produs”, „3 produse”, „20 de produse” (regula de plural din română).
	 */
	public static function count_label( int $count ): string {
		if ( 1 === $count ) {
			return __( '1 produs', 'barbarosi' );
		}

		$rest = $count % 100;

		/* translators: %d: numărul de produse */
		return 0 === $count || ( $rest > 0 && $rest < 20 )
			? sprintf( __( '%d produse', 'barbarosi' ), $count )
			: sprintf( __( '%d de produse', 'barbarosi' ), $count );
	}

	/**
	 * Iconiță din partial/icon.twig, pentru template-urile PHP.
	 */
	public static function icon( string $name, string $class = '' ): string {
		return (string) Timber::compile( 'partial/icon.twig', array_filter( [ 'name' => $name, 'class' => $class ] ) );
	}

	/**
	 * Numele produsului în coș / sumar. La variații WooCommerce salvează numele cu mărimea inclusă
	 * („Geacă … — verde - M”); afișăm titlul produsului, iar mărimea apare dedesubt, în detalii
	 * (wc_get_formatted_cart_item_data ascunde doar atributele deja prezente în nume).
	 *
	 * @param array<string, mixed> $cart_item
	 */
	public static function item_name( array $cart_item, WC_Product $product ): string {
		return $product instanceof WC_Product_Variation
			? $product->get_title()
			: WC()->cart->get_item_product_name( $cart_item, $product );
	}

	/**
	 * Poza produsului în coș / sumar. Folosim dimensiunea `medium` (proporțiile originale) —
	 * `woocommerce_thumbnail` e decupată pătrat, iar pozele noastre sunt portret.
	 *
	 * @param array<string, mixed> $cart_item
	 */
	public static function item_thumbnail( WC_Product $product, array $cart_item, string $cart_item_key, string $sizes ): string {
		$image = $product->get_image( 'medium', [ 'sizes' => $sizes, 'class' => 'item-thumb' ] );

		return (string) apply_filters( 'woocommerce_cart_item_thumbnail', $image, $cart_item, $cart_item_key );
	}

	/**
	 * Fără cross-sells setate din admin, sugerăm ținutele (upsells) produselor din coș.
	 *
	 * @param int[]   $ids
	 * @param WC_Cart $cart
	 * @return int[]
	 */
	public static function cross_sell_ids( $ids, $cart ): array {
		if ( $ids ) {
			return $ids;
		}

		$in_cart = [];
		$upsells = [];

		foreach ( $cart->get_cart() as $item ) {
			$in_cart[] = (int) $item['product_id'];
			$product   = wc_get_product( $item['product_id'] );

			if ( $product ) {
				$upsells = array_merge( $upsells, $product->get_upsell_ids() );
			}
		}

		return array_values( array_diff( array_unique( array_map( 'intval', $upsells ) ), $in_cart ) );
	}

	/**
	 * @param string $message
	 */
	public static function skip_cart_updated_notice( $message ) {
		return __( 'Cart updated.', 'woocommerce' ) === $message ? '' : $message; // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch -- comparăm cu textul WooCommerce
	}

	/**
	 * @param string         $text
	 * @param WC_Order|false $order
	 */
	public static function order_received_text( $text, $order ): string {
		return $order
			? __( 'Am primit comanda și ne apucăm de pregătit coletul. Îți trimitem pe email confirmarea, iar apoi detaliile de livrare.', 'barbarosi' )
			: __( 'Am primit comanda ta.', 'barbarosi' );
	}
}
