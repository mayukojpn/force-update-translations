<?php
/**
 * Plugin Name: Force Update Translations
 * Description: Apply WordPress.org theme and plugin translations to a site even if translations are not yet approved or language packs have not been released.
 * Author:      Mayo Moriyama & Contributors
 * Author URI:  https://github.com/mayukojpn/force-update-translations/graphs/contributors
 * Version:     0.6.0
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
	 * Admin notices array.
	 *
	 * @var array<string, array<int, array<string, string>>>
	 */
	public $admin_notices = array();

	/**
	 * Constructor.
	 */
	public function __construct() {

		include 'lib/glotpress/locales.php';
		include 'inc/plugins.php';
		include 'inc/themes.php';
	}

	/**
	 * Get translation files.
	 *
	 * @param array $projects     Array of translation projects.
	 *
	 * @return void
	 */
	public function get_files( $projects ) {
		foreach ( $projects as $key => $project ) {
			foreach ( array( 'po', 'mo' ) as $format ) {
				$file = $this->get_file( $project, get_user_locale(), $format );
				if ( is_wp_error( $file ) ) {
					$this->admin_notices[ $key ][] = array(
						'status'  => 'error',
						'content' => $file->get_error_message(),
					);
				}
			} // endforeach;

			if ( empty( $this->admin_notices[ $key ] ) ) {
				$this->admin_notices[ $key ][] = array(
					'status'  => 'success',
					'content' => sprintf(
						/* translators: %s: Translation file. */
						__( 'Translation files have been downloaded: %s', 'force-update-translations' ),
						'<b>' . esc_html( $project['sub_project']['name'] ) . '</b>'
					),
				);
			}
		}

		// Show admin notices of the projects translation update.
		self::admin_notices();
	}

	/**
	 * Get translation source file.
	 *
	 * @param array  $project   File project.
	 * @param string $locale    File locale.
	 * @param string $format    File format.
	 *
	 * @return null|WP_Error    File path to get source..
	 */
	public function get_file( $project, $locale = '', $format = 'mo' ) {

		if ( empty( $locale ) ) {
			$locale = get_user_locale();
		}

		switch ( $project['type'] ) {
			case 'plugin':
				$target_path  = 'plugins/' . $project['sub_project']['slug'];
				$project_path = 'wp-' . $target_path . '/dev';
				break;
			case 'theme':
				$target_path  = 'themes/' . $project['sub_project']['slug'];
				$project_path = 'wp-' . $target_path;
				break;
		}

		$source = $this->get_source_path( $project_path, $locale, $format );
		$target = sprintf(
			'%s-%s.%s',
			$target_path,
			$locale,
			$format
		);

		$response = wp_remote_get( $source );

		if ( ! is_array( $response )
			|| 'application/octet-stream' !== $response['headers']['content-type'] ) {
			return new WP_Error(
				'fdt-source-not-found',
				sprintf(
					/* translators: %s: Translation file. */
					__( 'Cannot get source file: %s', 'force-update-translations' ),
					'<b>' . esc_html( $source ) . '</b>'
				)
			);
		} else {
			$translation_path = WP_LANG_DIR . '/' . $target;

			if ( ! file_exists( pathinfo( $translation_path, PATHINFO_DIRNAME ) ) ) {
				mkdir( pathinfo( $translation_path, PATHINFO_DIRNAME ), 0777, true );
			}

            file_put_contents( $translation_path, $response['body'] ); // phpcs:ignore
			return;
		}
	}

	/**
	 * Generate a file path to get translation file.
	 *
	 * @param string $project   File project.
	 * @param string $locale    File locale.
	 * @param string $format    File format.
	 * @return $path            File path to get source.
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
