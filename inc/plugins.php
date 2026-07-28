<?php
/**
 * Force update translations for plugins.
 *
 * @package update-force-translations
 * @author mayukojpn
 * @license GPL-2.0+
 */
class Plugin_Force_Update_Translations extends Force_Update_Translations {
	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'plugin_action_links', array( $this, 'plugin_action_links' ), 10, 2 );
		add_action( 'network_admin_plugin_action_links', array( $this, 'plugin_action_links' ), 10, 2 );
		add_action( 'admin_init', array( $this, 'handle_translation_update' ) );
	}


	/**
	 * Add plugin action link.
	 *
	 * @param array  $actions     An array of plugin action links.
	 * @param string $plugin_file Path to the plugin file relative to the plugins directory.
	 *
	 * @return array Modified array of plugin action links.
	 */
	public function plugin_action_links( $actions, $plugin_file ) {
		$url         = wp_nonce_url(
			admin_url( 'plugins.php?force_translate=' . $plugin_file ),
			'force_translate_plugin_' . $plugin_file,
			'force_translate_nonce'
		);
		$new_actions = array(
			'force_translate' => sprintf(
				'<a href="%1$s">%2$s</a>',
				esc_url( $url ),
				esc_html__( 'Update translation', 'force-update-translations' )
			),
		);

		// Check if plugin is on wordpress.org by checking if ID (from Plugin wp.org info) exists in 'response' or 'no_update'.
		$on_wporg     = false;
		$plugin_state = get_site_transient( 'update_plugins' );
		if ( isset( $plugin_state->response[ $plugin_file ]->id ) || isset( $plugin_state->no_update[ $plugin_file ]->id ) ) {
			$on_wporg = true;
		}

		// Add action if plugin is on wordpress.org and if user Locale isn't 'en_US'.
		if ( ( $on_wporg ) && ( get_user_locale() !== 'en_US' ) ) {
			$actions = array_merge( $actions, $new_actions );
		}
		return $actions;
	}


	/**
	 * Handle translation update request.
	 *
	 * @return void
	 */
	public function handle_translation_update() {
		if ( ! isset( $_GET['force_translate'] ) ) {
			return;
		}

		$plugin_file = sanitize_text_field( wp_unslash( $_GET['force_translate'] ) );

		// Verify nonce for CSRF protection.
		if ( ! isset( $_GET['force_translate_nonce'] ) ||
			! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['force_translate_nonce'] ) ), 'force_translate_plugin_' . $plugin_file ) ) {
			$this->admin_notices['error'][] = array(
				'status'  => 'error',
				'content' => esc_html__( 'Security verification failed. Please refresh the page and try again.', 'force-update-translations' ),
			);
			add_action( 'admin_notices', array( $this, 'admin_notices' ) );
			return;
		}

		// Check user permission.
		if ( ! current_user_can( 'update_plugins' ) ) {
			$this->admin_notices['error'][] = array(
				'status'  => 'error',
				'content' => esc_html__( 'You do not have permission to update translation files.', 'force-update-translations' ),
			);
			add_action( 'admin_notices', array( $this, 'admin_notices' ) );
			return;
		}

		// Validate plugin file format and prevent directory traversal.
		if ( ! preg_match( '/^([a-zA-Z0-9-_]+)\/([a-zA-Z0-9-_]+\.php)$/', $plugin_file, $plugin_slug ) ) {
			$this->admin_notices['error'][] = array(
				'status'  => 'error',
				'content' => sprintf(
					/* translators: %s: parameter */
					esc_html__( 'Invalid parameter: %s', 'force-update-translations' ),
					esc_html( $plugin_file )
				),
			);
			add_action( 'admin_notices', array( $this, 'admin_notices' ) );
			return;
		}

		// Additional security: verify the plugin file actually exists and is in the plugins directory.
		$plugin_path = WP_PLUGIN_DIR . '/' . $plugin_file;
		$real_path   = realpath( $plugin_path );
		if ( false === $real_path || 0 !== strpos( $real_path, WP_PLUGIN_DIR ) ) {
			$this->admin_notices['error'][] = array(
				'status'  => 'error',
				'content' => sprintf(
					/* translators: %s: plugin file path (e.g., plugin-name/plugin-file.php) */
					esc_html__( 'The plugin file could not be found or is invalid: %s', 'force-update-translations' ),
					esc_html( $plugin_file )
				),
			);
			add_action( 'admin_notices', array( $this, 'admin_notices' ) );
			return;
		}

		$plugin_data = get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin_file, false );

		$projects = array(
			$plugin_file => array(
				'type'        => 'plugin',
				'sub_project' => array(
					'slug' => $plugin_slug[1],
					'name' => $plugin_data['Name'],
				),
			),
		);

		parent::get_files( $projects );
	}
}

new Plugin_Force_Update_Translations();
