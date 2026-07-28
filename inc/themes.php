<?php
/**
 * Theme translation update handler.
 *
 * @package Force_Update_Translations
 */

/**
 * Theme translation update handler class.
 */
class Theme_Force_Update_Translations extends Force_Update_Translations {
	/**
	 * Constructor.
	 */
	public function __construct() {
		// Add theme translation option if target locale is not 'en_US'.
		if ( $this->get_target_locale() !== 'en_US' ) {
			add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		}
	}


	/**
	 * Generate theme translation update option.
	 */
	public function admin_menu() {
		$theme_page = add_theme_page(
			esc_html__( 'Update translation', 'force-update-translations' ),
			esc_html__( 'Update translation', 'force-update-translations' ),
			'edit_theme_options',
			'force_translate',
			array( $this, 'get_theme_translations' )
		);
	}

	/**
	 * Get theme translations and handle update requests.
	 *
	 * @return void
	 */
	public function get_theme_translations() {

		// Get current theme data.
		$current_theme = wp_get_theme();

		// Add current theme.
		$themes[ $current_theme->get_stylesheet() ] = $current_theme;

		// Get parent theme data.
		$parent_theme = $current_theme->parent();

		// Check if has a parent theme and it exists.
		if ( $parent_theme && $parent_theme->exists() ) {
			// Add parent theme.
			$themes[ $parent_theme->get_stylesheet() ] = $parent_theme;
		}

		// Get installed themes update transient.
		$installed_themes = get_site_transient( 'update_themes' );

		$projects = array();

		foreach ( $themes as $stylesheet => $theme ) {

			// Check if theme is on wordpress.org by checking if the stylesheet (from Theme wp.org info) exists in 'response' or 'no_update'.
			if ( isset( $installed_themes->response[ $theme->get_stylesheet() ] ) || isset( $installed_themes->no_update[ $theme->get_stylesheet() ] ) ) {

				$projects[ $stylesheet ] = array(
					'type'        => 'theme',
					'sub_project' => array(
						'slug' => $theme->get( 'TextDomain' ),
						'name' => $theme->get( 'Name' ),
					),
				);

			}
		}

		// Check if form was submitted.
		$show_results = false;
		if ( isset( $_POST['force_translate_themes'] ) ) {
			// Verify nonce for CSRF protection.
			if ( ! isset( $_POST['force_translate_themes_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['force_translate_themes_nonce'] ) ), 'force_translate_themes' ) ) {
				wp_die( esc_html__( 'Security verification failed. Please refresh the page and try again.', 'force-update-translations' ) );
			}

			// Check user permission.
			if ( ! current_user_can( 'edit_theme_options' ) ) {
				wp_die( esc_html__( 'You do not have permission to update translation files.', 'force-update-translations' ) );
			}

			// Get projects translation files.
			parent::get_files( $projects );
			$show_results = true;
		}

		?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Update translation', 'force-update-translations' ); ?></h1>
		<p>
			<?php
			// Check if has a parent theme and it exists.
			if ( $parent_theme && $parent_theme->exists() ) {
				esc_html_e( 'Translation updates for your active child and parent themes.', 'force-update-translations' );
			} else {
				esc_html_e( 'Translation updates for your active theme.', 'force-update-translations' );
			}
			?>
		</p>

		<?php if ( ! empty( $projects ) ) : ?>
			<form method="post" action="">
				<?php wp_nonce_field( 'force_translate_themes', 'force_translate_themes_nonce' ); ?>
				<?php submit_button( __( 'Update translation', 'force-update-translations' ), 'primary', 'force_translate_themes' ); ?>
			</form>
		<?php else : ?>
			<p><?php esc_html_e( 'Your active theme is not available on WordPress.org. Only themes from WordPress.org support translation updates.', 'force-update-translations' ); ?></p>
		<?php endif; ?>

		<?php if ( $show_results ) : ?>
			<div class="update-messages">
			<!-- Results are displayed via admin_notices in parent class -->
		</div>
		<?php endif; ?>
	</div>
		<?php
	}
}

new Theme_Force_Update_Translations();
