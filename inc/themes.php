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
    add_action( 'admin_menu', array( $this, 'admin_menu' ) );
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

    $current_theme = wp_get_theme();

    $project = array (
      'type'   => 'theme',
      'sub_project'  => array(
        'slug' => $current_theme->get( 'TextDomain' ),
        'name' => $current_theme->get( 'Name' )
      )
    );

    parent::get_files( $project );

  }

}
new Theme_Force_Update_Translations();
?>
