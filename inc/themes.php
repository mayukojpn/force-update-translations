<?php
/**
 * @package update-force-translations
 * @author mayukojpn
 * @license GPL-2.0+
 */

class Theme_Force_Update_Translations extends Force_Update_Translations {

  /**
   * Constructor.
   */
  function __construct() {
    // Add theme translation option if user Locale is not 'en_US'.
    if ( get_user_locale() !== 'en_US' ) {
      add_action( 'admin_menu', array( $this, 'admin_menu' ) );
    };
  }

  /**
   * Generate theme translation update option.
   *
   */
  function admin_menu() {
  	$theme_page = add_theme_page(
  		esc_html__( 'Update translation', 'force-update-translations' ),
  		esc_html__( 'Update translation', 'force-update-translations' ),
  		'edit_theme_options',
  		'force_translate',
  		array( $this, 'get_theme_translations' )
  	);
  }

  function get_theme_translations() {

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

        $projects[ $stylesheet ] = array (
          'type'   => 'theme',
          'sub_project'  => array(
            'slug' => $theme->get( 'TextDomain' ),
            'name' => $theme->get( 'Name' )
          )
        );

      };

    }

	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Themes Translations', 'force-update-translations' ); ?></h1>
		<div class="update-messages">
			<?php
			// Get projects translation files.
		    parent::get_files( $projects );
			?>
		</div>
	</div>
	<?php

  }

}
new Theme_Force_Update_Translations();
?>
