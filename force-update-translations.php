<?php
/*
Plugin Name: Force Update Translations
Description: Download WordPress theme/plugin translations and apply them to your site manually even if their language pack haven't been released or reviewed on translate.wordpress.org
Author:      Mayo Moriyama
Version:     0.2.5
*/

class Force_Update_Translations {

	public $admin_notices = [];

  /**
   * Constructor.
   */
  function __construct() {

		include 'lib/glotpress/locales.php';
		include 'inc/plugins.php';

  }

	/**
	 * Import translation file.
	 *
	 * @param string $project   File project
	 * @param string $locale    File locale
	 * @param string $format    File format
	 * @return null|WP_Error    File path to get source.
	 */
	function import( $project_slug, $locale = '', $format = 'mo' ) {

		if ( empty( $locale ) ) {
			$locale = get_user_locale();
		}

		preg_match("/wp-(.*)/", $project_slug, $project_path);

		$source = $this->get_source_path( $project_slug, $locale, $format );
		$target = sprintf(
			'%s-%s.%s',
			$project_path[1],
			$locale,
			$format
		);
		$response = wp_remote_get( $source );

		if ( !is_array( $response )
			|| $response['headers']['content-type'] !== 'application/octet-stream' ) {
			return new WP_Error( 'fdt-source-not-found', sprintf(
				__( 'Cannot get source file: %s', 'force-update-translations' ),
				'<b>' . esc_html( $source ) . '</b>'
			) );
		}
		else {
			file_put_contents( WP_LANG_DIR . '/' . $target, $response['body'] );
			return;
		}
	}
	/**
	 * Generate a file path to get translation file.
	 *
	 * @param string $project   File project
	 * @param string $locale    File locale
	 * @param string $type      File type
	 * @param string $format    File format
	 * @return $path            File path to get source.
	 */
	function get_source_path( $project, $locale, $format = 'mo', $type = 'dev' ) {
		$locale = GP_Locales::by_field( 'wp_locale', $locale );
		$path = sprintf( 'https://translate.wordpress.org/projects/%1$s/%2$s/%3$s/default/export-translations?filters[status]=current_or_waiting_or_fuzzy',
			$project,
			$type,
			$locale->slug
		);
		$path = ( $format == 'po' ) ? $path : $path . '&format=' . $format;
		$path = esc_url_raw( $path );
		return $path;
	}

	/**
	 * Prints admin screen notices.
	 *
	 */
	function admin_notices() {
		if ( empty( $this->admin_notices ) ) {
			return;
		}
		foreach ( $this->admin_notices as $notice ) {
			?>
			<div class="notice notice-<?php echo esc_attr( $notice['status'] ); ?>">
					<p><?php echo $notice['content']; // WPCS: XSS OK. ?></p>
			</div>
			<?php
		}
	}
}

new Force_Update_Translations;
