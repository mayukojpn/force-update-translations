<?php
/**
 * Plugin Name: Force Update Translations
 * Description: Apply WordPress.org theme and plugin translations to a site even if translations are not yet approved or language packs have not been released.
 * Author:      Mayo Moriyama & Contributors
 * Author URI:  https://github.com/mayukojpn/force-update-translations/graphs/contributors
 * Version:     0.6.4
 * Requires at least: 4.7
 * Requires PHP: 5.6
 * Text Domain: force-update-translations
 * Domain Path: /languages
 *
 * @package Force_Update_Translations
 */

/**
 * Force Update Translations main class.
 *
 * Handles manual translation updates for WordPress themes and plugins.
 */
class Force_Update_Translations {

	/**
	 * Option name for plugin settings.
	 *
	 * @var string
	 */
	const SETTINGS_OPTION = 'fut_settings';

	/**
	 * Option name for tracked forced translations.
	 *
	 * @var string
	 */
	const FORCED_OPTION = 'fut_forced_translations';

	/**
	 * Admin notices array.
	 *
	 * @var array
	 */
	public $admin_notices = array();

	/**
	 * Constructor.
	 */
	public function __construct() {

		include_once __DIR__ . '/lib/glotpress/locales.php';
		include_once __DIR__ . '/inc/plugins.php';
		include_once __DIR__ . '/inc/themes.php';
		include_once __DIR__ . '/inc/settings.php';

		add_filter( 'site_transient_update_plugins', array( $this, 'filter_translation_updates' ) );
		add_filter( 'site_transient_update_themes', array( $this, 'filter_translation_updates' ) );
		add_filter( 'site_transient_update_core', array( $this, 'filter_translation_updates' ) );
		add_filter( 'auto_update_translation', array( $this, 'filter_auto_update_translation' ), 10, 2 );
	}

	/**
	 * Default settings.
	 *
	 * @return array
	 */
	public static function default_settings() {
		return array(
			'locale_source'      => 'user',
			'protect_from_packs' => 1,
		);
	}

	/**
	 * Get plugin settings merged with defaults.
	 *
	 * @return array
	 */
	public function get_settings() {
		$settings = get_option( self::SETTINGS_OPTION, array() );
		if ( ! is_array( $settings ) ) {
			$settings = array();
		}
		return array_merge( self::default_settings(), $settings );
	}

	/**
	 * Locale used for downloads and installed-branch detection.
	 *
	 * @return string
	 */
	public function get_target_locale() {
		$settings = $this->get_settings();
		if ( isset( $settings['locale_source'] ) && 'site' === $settings['locale_source'] ) {
			return get_locale();
		}
		return get_user_locale();
	}

	/**
	 * Whether official language-pack updates should skip forced translations.
	 *
	 * @return bool
	 */
	public function should_protect_from_packs() {
		$settings = $this->get_settings();
		return ! empty( $settings['protect_from_packs'] );
	}

	/**
	 * Build storage key for a forced translation.
	 *
	 * @param string $type   plugin|theme.
	 * @param string $slug   Project slug.
	 * @param string $locale Locale.
	 * @return string
	 */
	public function get_forced_key( $type, $slug, $locale ) {
		return $type . '/' . $slug . '/' . $locale;
	}

	/**
	 * Remember a successfully forced translation so language packs do not overwrite it.
	 *
	 * @param array  $project Project data.
	 * @param string $locale  Locale.
	 * @param string $branch  Branch slug when known.
	 * @return void
	 */
	public function remember_forced_translation( $project, $locale, $branch = '' ) {
		$type = isset( $project['type'] ) ? $project['type'] : '';
		$slug = isset( $project['sub_project']['slug'] ) ? $project['sub_project']['slug'] : '';
		if ( '' === $type || '' === $slug || '' === $locale ) {
			return;
		}

		$key    = $this->get_forced_key( $type, $slug, $locale );
		$forced = get_option( self::FORCED_OPTION, array() );
		if ( ! is_array( $forced ) ) {
			$forced = array();
		}

		$forced[ $key ] = array(
			'type'      => $type,
			'slug'      => $slug,
			'locale'    => $locale,
			'branch'    => $branch,
			'updated'   => time(),
			'protected' => 1,
		);

		update_option( self::FORCED_OPTION, $forced, false );
	}

	/**
	 * Get all tracked forced translations.
	 *
	 * @return array
	 */
	public function get_forced_translations() {
		$forced = get_option( self::FORCED_OPTION, array() );
		return is_array( $forced ) ? $forced : array();
	}

	/**
	 * Remove forced-translation tracking entries.
	 *
	 * @param string $type Optional type filter.
	 * @param string $slug Optional slug filter.
	 * @return int Number of removed entries.
	 */
	public function clear_forced_translations( $type = '', $slug = '' ) {
		$forced = $this->get_forced_translations();
		$before = count( $forced );

		if ( '' === $type && '' === $slug ) {
			delete_option( self::FORCED_OPTION );
			return $before;
		}

		foreach ( $forced as $key => $entry ) {
			if ( '' !== $type && ( ! isset( $entry['type'] ) || $entry['type'] !== $type ) ) {
				continue;
			}
			if ( '' !== $slug && ( ! isset( $entry['slug'] ) || $entry['slug'] !== $slug ) ) {
				continue;
			}
			unset( $forced[ $key ] );
		}

		update_option( self::FORCED_OPTION, $forced, false );
		return $before - count( $forced );
	}

	/**
	 * Strip protected domains from language-pack update payloads.
	 *
	 * @param object|mixed $value Transient value.
	 * @return object|mixed
	 */
	public function filter_translation_updates( $value ) {
		if ( ! $this->should_protect_from_packs() || ! is_object( $value ) || empty( $value->translations ) || ! is_array( $value->translations ) ) {
			return $value;
		}

		$forced = $this->get_forced_translations();
		if ( empty( $forced ) ) {
			return $value;
		}

		$value->translations = array_values(
			array_filter(
				$value->translations,
				array( $this, 'is_translation_update_allowed' )
			)
		);

		return $value;
	}

	/**
	 * Whether a language-pack update entry may proceed.
	 *
	 * @param array $update Translation update row.
	 * @return bool
	 */
	public function is_translation_update_allowed( $update ) {
		if ( ! is_array( $update ) || empty( $update['type'] ) || empty( $update['slug'] ) || empty( $update['language'] ) ) {
			return true;
		}

		$key    = $this->get_forced_key( $update['type'], $update['slug'], $update['language'] );
		$forced = $this->get_forced_translations();
		return empty( $forced[ $key ] );
	}

	/**
	 * Block automatic language-pack updates for forced translations.
	 *
	 * @param bool|null $update Whether to update.
	 * @param object    $item   Translation update item.
	 * @return bool|null
	 */
	public function filter_auto_update_translation( $update, $item ) {
		if ( ! $this->should_protect_from_packs() || ! is_object( $item ) ) {
			return $update;
		}

		$type     = isset( $item->type ) ? $item->type : '';
		$slug     = isset( $item->slug ) ? $item->slug : '';
		$language = isset( $item->language ) ? $item->language : '';
		if ( '' === $type || '' === $slug || '' === $language ) {
			return $update;
		}

		$key    = $this->get_forced_key( $type, $slug, $language );
		$forced = $this->get_forced_translations();
		if ( ! empty( $forced[ $key ] ) ) {
			return false;
		}

		return $update;
	}

	/**
	 * Get translation files.
	 *
	 * @param array $projects Array of translation projects.
	 *
	 * @return void
	 */
	public function get_files( $projects ) {
		foreach ( $projects as $key => $project ) {
			$locale  = $this->get_target_locale();
			$sources = array();

			foreach ( array( 'po', 'mo' ) as $format ) {
				$file = $this->get_file( $project, $locale, $format );
				if ( is_wp_error( $file ) ) {
					$this->admin_notices[ $key ][] = array(
						'status'  => 'error',
						'content' => $file->get_error_message(),
					);
				} elseif ( is_string( $file ) && '' !== $file ) {
					$sources[] = $file;
				}
			}

			if ( empty( $this->admin_notices[ $key ] ) ) {
				$derived = $this->generate_derived_translation_files( $project, $locale );
				if ( is_wp_error( $derived ) ) {
					$this->admin_notices[ $key ][] = array(
						'status'  => 'error',
						'content' => $derived->get_error_message(),
					);
				} else {
					$branch = ! empty( $sources ) ? $sources[0] : ( isset( $project['branch'] ) ? $project['branch'] : '' );
					$this->remember_forced_translation( $project, $locale, $branch );
					$this->admin_notices[ $key ][] = array(
						'status'  => 'success',
						'content' => $this->get_download_success_message( $project, $sources ),
					);
				}
			}
		}

		// Defer notices until admin_notices when that hook has not yet run
		// (e.g. plugin updates during admin_init). Print immediately when the
		// hook already fired (e.g. theme translation settings page callback).
		if ( ! did_action( 'admin_notices' ) ) {
			add_action( 'admin_notices', array( $this, 'admin_notices' ) );
		} else {
			$this->admin_notices();
		}
	}

	/**
	 * Get translation source file.
	 *
	 * @param array  $project File project.
	 * @param string $locale  File locale.
	 * @param string $format  File format.
	 *
	 * @return string|WP_Error Project branch used (`dev`, `stable`, or empty string for themes) on success.
	 */
	public function get_file( $project, $locale = '', $format = 'mo' ) {

		if ( empty( $locale ) ) {
			$locale = $this->get_target_locale();
		}

		$target_path   = '';
		$project_paths = array();

		switch ( $project['type'] ) {
			case 'plugin':
				$target_path = 'plugins/' . $project['sub_project']['slug'];
				$branch      = isset( $project['branch'] ) ? $project['branch'] : '';
				if ( 'stable' === $branch || 'dev' === $branch ) {
					$project_paths = array( 'wp-' . $target_path . '/' . $branch );
				} else {
					// Development first (includes newest / waiting strings), then Stable.
					$project_paths = array(
						'wp-' . $target_path . '/dev',
						'wp-' . $target_path . '/stable',
					);
				}
				break;
			case 'theme':
				$target_path   = 'themes/' . $project['sub_project']['slug'];
				$project_paths = array( 'wp-' . $target_path );
				break;
		}

		$target = sprintf(
			'%s-%s.%s',
			$target_path,
			$locale,
			$format
		);

		$last_source = '';
		foreach ( $project_paths as $project_path ) {
			$source      = $this->get_source_path( $project_path, $locale, $format );
			$last_source = $source;
			$response    = wp_remote_get(
				$source,
				array(
					'timeout' => 60,
				)
			);

			$content_type = '';
			if ( is_array( $response ) && isset( $response['headers']['content-type'] ) ) {
				$content_type = $response['headers']['content-type'];
			}

			if ( ! is_array( $response )
				|| false === strpos( $content_type, 'application/octet-stream' ) ) {
				continue;
			}

			$translation_path = WP_LANG_DIR . '/' . $target;

			if ( ! file_exists( pathinfo( $translation_path, PATHINFO_DIRNAME ) ) ) {
				wp_mkdir_p( pathinfo( $translation_path, PATHINFO_DIRNAME ) );
			}

			file_put_contents( $translation_path, $response['body'] ); // phpcs:ignore
			return $this->get_project_branch( $project_path );
		}

		return new WP_Error(
			'fdt-source-not-found',
			sprintf(
				/* translators: %s: Translation file. */
				__( 'Cannot get source file: %s', 'force-update-translations' ),
				'<b>' . esc_html( $last_source ) . '</b>'
			)
		);
	}

	/**
	 * Extract Stable/Development branch from a translate.wordpress.org project path.
	 *
	 * @param string $project_path Project path such as wp-plugins/slug/dev.
	 * @return string `dev`, `stable`, or empty string when not applicable.
	 */
	protected function get_project_branch( $project_path ) {
		if ( preg_match( '#/(dev|stable)$#', $project_path, $matches ) ) {
			return $matches[1];
		}

		return '';
	}

	/**
	 * Detect Stable/Development branch from a local PO file header.
	 *
	 * @param string $po_path Path to PO file.
	 * @return string `dev`, `stable`, or empty string when unknown.
	 */
	public function detect_branch_from_po( $po_path ) {
		if ( ! is_readable( $po_path ) ) {
			return '';
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$headers = file_get_contents( $po_path, false, null, 0, 4096 );
		if ( false === $headers ) {
			return '';
		}

		if ( preg_match( '/Project-Id-Version:.*\bDevelopment\b/i', $headers ) ) {
			return 'dev';
		}

		if ( preg_match( '/Project-Id-Version:.*\bStable\b/i', $headers ) ) {
			return 'stable';
		}

		return '';
	}

	/**
	 * Detect branch for an installed plugin or theme translation.
	 *
	 * @param string $type `plugin` or `theme`.
	 * @param string $slug Text domain / slug.
	 * @param string $locale Locale. Defaults to the current user locale.
	 * @return string `dev`, `stable`, or empty string when unknown.
	 */
	public function detect_installed_branch( $type, $slug, $locale = '' ) {
		if ( empty( $locale ) ) {
			$locale = $this->get_target_locale();
		}

		$subdir = ( 'theme' === $type ) ? 'themes' : 'plugins';
		$po     = WP_LANG_DIR . '/' . $subdir . '/' . $slug . '-' . $locale . '.po';

		return $this->detect_branch_from_po( $po );
	}

	/**
	 * Human-readable label for a GlotPress project branch.
	 *
	 * @param string $branch Branch slug (`dev` or `stable`).
	 * @return string
	 */
	protected function get_branch_label( $branch ) {
		switch ( $branch ) {
			case 'dev':
				return __( 'Development', 'force-update-translations' );
			case 'stable':
				return __( 'Stable', 'force-update-translations' );
			default:
				return $branch;
		}
	}

	/**
	 * Build the success notice after translation files are downloaded.
	 *
	 * @param array    $project Project data.
	 * @param string[] $sources Branch slugs used for downloads.
	 * @return string
	 */
	protected function get_download_success_message( $project, $sources ) {
		$name    = '<b>' . esc_html( $project['sub_project']['name'] ) . '</b>';
		$sources = array_values( array_unique( array_filter( $sources ) ) );

		if ( empty( $sources ) ) {
			return sprintf(
				/* translators: %s: Theme or plugin name. */
				__( 'Translation files have been downloaded: %s', 'force-update-translations' ),
				$name
			);
		}

		$labels = array();
		foreach ( $sources as $source ) {
			$labels[] = $this->get_branch_label( $source );
		}
		$branch_label = '<b>' . esc_html( implode( ', ', $labels ) ) . '</b>';

		return sprintf(
			/* translators: 1: Theme or plugin name. 2: Translation project branch (Development or Stable). */
			__( 'Translation files have been downloaded: %1$s (source: %2$s)', 'force-update-translations' ),
			$name,
			$branch_label
		);
	}

	/**
	 * Generate a file path to get translation file.
	 *
	 * @param string $project File project.
	 * @param string $locale  File locale.
	 * @param string $format  File format.
	 * @return string File path to get source.
	 */
	public function get_source_path( $project, $locale, $format = 'mo' ) {
		$locale = GP_Locales::by_field( 'wp_locale', $locale );

		// Defaults to 'slug/default' if is a Root Locale, 'slug/variant' if is variant.
		$locale_slug = $locale->slug;
		if ( ! isset( $locale->root_slug ) ) {
			$locale_slug .= '/default';
		}

		$path = sprintf(
			'https://translate.wordpress.org/projects/%1$s/%2$s/export-translations?filters[status]=current_or_waiting_or_fuzzy',
			$project,
			$locale_slug
		);
		$path = ( 'po' === $format ) ? $path : $path . '&format=' . $format;
		$path = esc_url_raw( $path );
		return $path;
	}

	/**
	 * Build .json (JS) and .l10n.php files from the downloaded PO/MO.
	 *
	 * Modern WordPress loads JS strings from Jed JSON files and prefers
	 * .l10n.php over .mo for PHP strings. Without these, unapproved
	 * translations in the PO/MO often appear not to apply.
	 *
	 * @param array  $project Project data.
	 * @param string $locale  Locale.
	 * @return true|WP_Error
	 */
	public function generate_derived_translation_files( $project, $locale ) {
		$subdir = ( 'theme' === $project['type'] ) ? 'themes' : 'plugins';
		$slug   = $project['sub_project']['slug'];
		$base   = WP_LANG_DIR . '/' . $subdir . '/' . $slug . '-' . $locale;
		$po     = $base . '.po';
		$mo     = $base . '.mo';

		if ( ! is_readable( $po ) ) {
			return new WP_Error(
				'fdt-po-missing',
				sprintf(
					/* translators: %s: File path. */
					__( 'Cannot generate translation artifacts; PO file missing: %s', 'force-update-translations' ),
					'<b>' . esc_html( $po ) . '</b>'
				)
			);
		}

		$this->delete_existing_json_files( $subdir, $slug, $locale );

		$json_result = $this->make_json_files( $po, dirname( $po ), $slug . '-' . $locale );
		if ( is_wp_error( $json_result ) ) {
			return $json_result;
		}

		if ( is_readable( $mo ) && class_exists( 'WP_Translation_File', false ) ) {
			$php = WP_Translation_File::transform( $mo, 'php' );
			if ( is_string( $php ) && '' !== $php ) {
				file_put_contents( $base . '.l10n.php', $php ); // phpcs:ignore
			}
		}

		return true;
	}

	/**
	 * Remove previously generated Jed JSON files for a domain/locale.
	 *
	 * @param string $subdir plugins or themes.
	 * @param string $slug   Text domain / slug.
	 * @param string $locale Locale.
	 * @return void
	 */
	protected function delete_existing_json_files( $subdir, $slug, $locale ) {
		$pattern = WP_LANG_DIR . '/' . $subdir . '/' . $slug . '-' . $locale . '-*.json';
		$files   = glob( $pattern );
		if ( empty( $files ) ) {
			return;
		}
		foreach ( $files as $file ) {
			unlink( $file ); // phpcs:ignore
		}
	}

	/**
	 * Split a PO file into Jed JSON files (one per JS source file).
	 *
	 * @param string $po_file         Path to PO file.
	 * @param string $destination_dir Destination directory.
	 * @param string $base_file_name  Filename prefix (domain-locale).
	 * @return int|WP_Error Number of JSON files created.
	 */
	protected function make_json_files( $po_file, $destination_dir, $base_file_name ) {
		if ( ! class_exists( 'PO', false ) ) {
			require_once ABSPATH . WPINC . '/pomo/po.php';
		}

		$po = new PO();
		if ( ! $po->import_from_file( $po_file ) ) {
			return new WP_Error(
				'fdt-po-parse',
				sprintf(
					/* translators: %s: File path. */
					__( 'Could not parse PO file: %s', 'force-update-translations' ),
					'<b>' . esc_html( $po_file ) . '</b>'
				)
			);
		}

		$js_extensions = array( 'js', 'jsx', 'ts', 'tsx' );
		$mapping       = array();

		foreach ( $po->entries as $entry ) {
			if ( empty( $entry->translations ) || '' === $entry->translations[0] ) {
				continue;
			}

			$sources = array();
			foreach ( (array) $entry->references as $reference ) {
				$file = $reference;
				if ( preg_match( '/^(.+):(\d+)$/', $reference, $matches ) ) {
					$file = $matches[1];
				}

				$extension = pathinfo( $file, PATHINFO_EXTENSION );
				if ( ! in_array( $extension, $js_extensions, true ) ) {
					continue;
				}

				// Normalize foo.min.js -> foo.js (matches wp i18n make-json).
				$file      = preg_replace( '/\.min\.' . preg_quote( $extension, '/' ) . '$/', '.' . $extension, $file );
				$sources[] = $file;
			}

			$sources = array_unique( $sources );
			foreach ( $sources as $source ) {
				if ( ! isset( $mapping[ $source ] ) ) {
					$mapping[ $source ] = array();
				}
				$mapping[ $source ][] = $entry;
			}
		}

		$created = 0;
		foreach ( $mapping as $source => $entries ) {
			$jed  = $this->build_jed_json( $po, $entries, $source );
			$file = $destination_dir . '/' . $base_file_name . '-' . md5( $source ) . '.json';
			$json = wp_json_encode( $jed );
			if ( false === $json ) {
				continue;
			}
			file_put_contents( $file, $json ); // phpcs:ignore
			++$created;
		}

		return $created;
	}

	/**
	 * Build a Jed 1.x compatible data structure for script translations.
	 *
	 * @param PO                  $po      Parsed PO (for headers).
	 * @param Translation_Entry[] $entries Entries for one JS source file.
	 * @param string              $source  Relative JS source path.
	 * @return array
	 */
	protected function build_jed_json( $po, $entries, $source ) {
		$lang         = isset( $po->headers['Language'] ) ? $po->headers['Language'] : '';
		$plural_forms = isset( $po->headers['Plural-Forms'] ) ? $po->headers['Plural-Forms'] : 'nplurals=2; plural=n != 1;';
		$revision     = isset( $po->headers['PO-Revision-Date'] ) ? $po->headers['PO-Revision-Date'] : '';

		$messages = array(
			'' => array(
				'domain'       => 'messages',
				'lang'         => $lang,
				'plural-forms' => $plural_forms,
			),
		);

		foreach ( $entries as $entry ) {
			$key = $entry->singular;
			if ( ! empty( $entry->context ) ) {
				$key = $entry->context . "\4" . $entry->singular;
			}
			$messages[ $key ] = array_values( $entry->translations );
		}

		return array(
			'translation-revision-date' => $revision,
			'generator'                 => 'Force Update Translations/' . $this->get_plugin_version(),
			'source'                    => $source,
			'domain'                    => 'messages',
			'locale_data'               => array(
				'messages' => $messages,
			),
		);
	}

	/**
	 * Plugin version from the main file header.
	 *
	 * @return string
	 */
	protected function get_plugin_version() {
		if ( ! function_exists( 'get_plugin_data' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		$data = get_plugin_data( __FILE__, false, false );
		return isset( $data['Version'] ) ? $data['Version'] : '0.6.4';
	}

	/**
	 * Prints admin screen notices.
	 */
	public function admin_notices() {
		if ( empty( $this->admin_notices ) ) {
			return;
		}
		foreach ( $this->admin_notices as $project ) {
			foreach ( $project as $notice ) {
				?>
				<div class="notice notice-<?php echo esc_attr( $notice['status'] ); ?> is-dismissible">
					<p><?php echo wp_kses_post( $notice['content'] ); ?></p>
				</div>
				<?php
			}
		}
	}
}

new Force_Update_Translations();
