<?php

/**
 * Încarcă assets compilate de Vite.
 *
 * - Dev  (`npm run dev`):   există fișierul `hot` -> assets servite de Vite cu HMR.
 * - Prod (`npm run build`): citește dist/.vite/manifest.json și încarcă fișierele cu hash.
 *
 * Fișierul `hot` e ignorat pe mediul `production`, ca un `hot` uitat să nu strice site-ul live.
 */
class Vite {
	private const DIST_DIR = 'dist';

	/** @var string[] Handle-uri care trebuie printate cu type="module". */
	private static array $module_handles = [];

	private static ?array $manifest = null;

	/**
	 * @param string $handle Prefix pentru handle-urile WordPress.
	 * @param string $entry  Calea entry-ului relativă la temă, ex. 'assets/js/main.js'.
	 */
	public static function enqueue( string $handle, string $entry ): void {
		self::register_module_filter();

		$dev_server = self::dev_server_url();

		if ( $dev_server ) {
			self::enqueue_dev( $handle, $entry, $dev_server );
			return;
		}

		self::enqueue_build( $handle, $entry );
	}

	private static function enqueue_dev( string $handle, string $entry, string $dev_server ): void {
		if ( ! wp_script_is( 'vite-client', 'enqueued' ) ) {
			wp_enqueue_script( 'vite-client', $dev_server . '/@vite/client', [], null, false );
			self::$module_handles[] = 'vite-client';
		}

		$url = $dev_server . '/' . $entry;

		if ( self::is_css( $entry ) ) {
			wp_enqueue_style( $handle, $url, [], null );
			return;
		}

		wp_enqueue_script( $handle, $url, [ 'vite-client' ], null, true );
		self::$module_handles[] = $handle;
	}

	private static function enqueue_build( string $handle, string $entry ): void {
		$manifest = self::manifest();

		if ( ! isset( $manifest[ $entry ] ) ) {
			if ( current_user_can( 'manage_options' ) ) {
				trigger_error( "Vite: entry-ul „{$entry}” lipsește din manifest. Rulează `npm run build`.", E_USER_WARNING );
			}
			return;
		}

		$chunk    = $manifest[ $entry ];
		$dist_uri = get_theme_file_uri( self::DIST_DIR );

		if ( self::is_css( $entry ) ) {
			wp_enqueue_style( $handle, $dist_uri . '/' . $chunk['file'], [], null );
			return;
		}

		wp_enqueue_script( $handle, $dist_uri . '/' . $chunk['file'], [], null, true );
		self::$module_handles[] = $handle;

		// CSS importat direct din JS (dacă există)
		foreach ( $chunk['css'] ?? [] as $i => $css_file ) {
			wp_enqueue_style( "{$handle}-{$i}", $dist_uri . '/' . $css_file, [], null );
		}
	}

	private static function dev_server_url(): ?string {
		if ( wp_get_environment_type() === 'production' ) {
			return null;
		}

		$hot_file = get_theme_file_path( 'hot' );

		if ( ! is_file( $hot_file ) ) {
			return null;
		}

		return rtrim( trim( (string) file_get_contents( $hot_file ) ), '/' );
	}

	private static function manifest(): array {
		if ( self::$manifest === null ) {
			$path           = get_theme_file_path( self::DIST_DIR . '/.vite/manifest.json' );
			self::$manifest = is_file( $path )
				? (array) json_decode( (string) file_get_contents( $path ), true )
				: [];
		}

		return self::$manifest;
	}

	private static function is_css( string $entry ): bool {
		return (bool) preg_match( '/\.(s?css|sass|less)$/', $entry );
	}

	private static function register_module_filter(): void {
		static $registered = false;

		if ( $registered ) {
			return;
		}
		$registered = true;

		add_filter(
			'script_loader_tag',
			static function ( string $tag, string $handle ): string {
				if ( ! in_array( $handle, self::$module_handles, true ) ) {
					return $tag;
				}

				// Tag-ul poate conține și script-uri inline (wp_localize_script etc.) — modificăm doar pe cel principal.
				$processor = new WP_HTML_Tag_Processor( $tag );
				while ( $processor->next_tag( 'script' ) ) {
					if ( $processor->get_attribute( 'id' ) === "{$handle}-js" ) {
						$processor->set_attribute( 'type', 'module' );
					}
				}

				return $processor->get_updated_html();
			},
			10,
			2
		);
	}
}
