<?php
/**
 * @package update-force-translations
 * @author mayukojpn
 * @license GPL-2.0+
 */

class Plugin_Force_Update_Translations extends Force_Update_Translations {

  /**
   * Constructor.
   */
  function __construct() {
		add_action( 'plugin_action_links',               array( $this, 'plugin_action_links'        ), 10, 2 );
		add_action( 'network_admin_plugin_action_links', array( $this, 'plugin_action_links'        ), 10, 2 );
		add_action( 'pre_current_active_plugins',        array( $this, 'pre_current_active_plugins' ) );
  }
	/**
	 * Add plugin action link.
	 *
	 * @param string $actions
	 * @param string $plugin_file
	 * @return array $actions    File path to get source.
	 */
	function plugin_action_links( $actions, $plugin_file ) {
		$url         = admin_url( 'plugins.php?force_translate=' . $plugin_file );
		$new_actions = array (
			'force_translate' => sprintf(
				'<a href="%1$s">%2$s</a>',
				esc_url( $url ),
				esc_html__( 'Update translation', 'force-update-translations' )
			)
		);
		// Check if plugin is on wordpress.org by checking if ID (from Plugin wp.org info) exists in 'response' or 'no_update'
		$on_wporg = false;
		$plugin_state = get_site_transient( 'update_plugins' );
		if ( isset( $plugin_state->response[ $plugin_file ]->id ) || isset( $plugin_state->no_update[ $plugin_file ]->id ) ) {
			$on_wporg = true;
		};
		// Add action if plugin is on wordpress.org and if user Locale isn't 'en_US'
		if ( ( $on_wporg ) && ( get_user_locale() != 'en_US' ) ) {
			$actions  = array_merge( $actions, $new_actions );
		};
		return $actions;

	}
	/**
	 * Main plugin action.
	 *
	 * @return null
	 */
	function pre_current_active_plugins() {
		if ( !isset( $_GET['force_translate'] ) ) {
			return;
		}

		$plugin_file = $_GET['force_translate'];
		if ( !preg_match("/^([a-zA-Z0-9-_]+)\/([a-zA-Z0-9-_.]+.php)$/", $plugin_file, $plugin_slug) ){
			$this->admin_notices[] = array(
				'status'  => 'error',
				'content' => sprintf(
					/* Translators: %s: parameter */
					esc_html__( 'Invalid parameter: %s', 'force-update-translations' ),
					esc_html( $plugin_file )
				)
			);
			static::admin_notices();
			return;
		}

    $plugin_data = get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin_file, false );

    $project = array (
      'type'   => 'plugin',
      'sub_project'  => array(
        'slug' => $plugin_slug[1],
        'name' => $plugin_data['Name']
      )
    );

    parent::get_files( $project );
	}
}
new Plugin_Force_Update_Translations;
