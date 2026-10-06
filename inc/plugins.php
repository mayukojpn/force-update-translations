<?php
/**
 * Force update translations for plugins.
 *
 * @package Force_Update_Translations
 * @author mayukojpn
 * @license GPL-2.0+
 */

/**
 * Plugin translation update handler class.
 */
class Plugin_Force_Update_Translations extends Force_Update_Translations {
	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'plugin_action_links', array( $this, 'plugin_action_links' ), 10, 2 );
		add_action( 'network_admin_plugin_action_links', array( $this, 'plugin_action_links' ), 10, 2 );
		add_action( 'admin_init', array( $this, 'handle_translation_update' ) );
		add_action( 'admin_print_footer_scripts-plugins.php', array( $this, 'admin_footer' ) );
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
		// Check if plugin is on wordpress.org by checking if ID (from Plugin wp.org info) exists in 'response' or 'no_update'.
		$on_wporg     = false;
		$plugin_state = get_site_transient( 'update_plugins' );
		if ( isset( $plugin_state->response[ $plugin_file ]->id ) || isset( $plugin_state->no_update[ $plugin_file ]->id ) ) {
			$on_wporg = true;
		}

		// Add action if plugin is on wordpress.org and if user Locale isn't 'en_US'.
		if ( ! $on_wporg || get_user_locale() === 'en_US' ) {
			return $actions;
		}

		// "Update translation" picks the source automatically (Stable, then Development).
		// The arrow opens a small dropdown with explicit Stable / Development links
		// (GlotPress project names are English-only, so deliberately not translated).
		// WordPress wraps this in <span class="force_translate">, which anchors the dropdown.
		$actions['force_translate'] = sprintf(
			'%1$s<button type="button" class="button-link fut-source-toggle" aria-expanded="false" aria-label="%2$s"></button><span class="fut-source-menu">%3$s%4$s</span>',
			$this->translate_link( $plugin_file, '', __( 'Update translation', 'force-update-translations' ) ),
			esc_attr__( 'Choose translation source', 'force-update-translations' ),
			$this->translate_link( $plugin_file, 'stable', 'Stable' ),
			$this->translate_link( $plugin_file, 'dev', 'Development' )
		);

		return $actions;
	}

	/**
	 * Dropdown behaviour and styles for the source chooser in plugin row actions.
	 *
	 * Opens on hover over the plus icon (hover devices) or on tap of it (touch devices).
	 */
	public function admin_footer() {
		?>
		<style>
			/* nowrap keeps the icon on the same line as the label, whatever the label length in the locale. */
			.row-actions .force_translate { position: relative; white-space: nowrap; }
			/* Chevron via ::before; core styles .dashicons inside .plugin-title as 64px plugin icons. Black, not link blue: it is a toggle, not a link. */
			.row-actions .fut-source-toggle { vertical-align: middle; margin-left: 2px; }
			.row-actions .fut-source-toggle,
			.row-actions .fut-source-toggle:hover,
			.row-actions .fut-source-toggle:focus { color: #000; }
			.row-actions .fut-source-toggle::before { font: normal 12px/1 dashicons; content: "\f347"; }
			.row-actions .is-open .fut-source-toggle::before { content: "\f343"; }
			.row-actions .fut-source-menu { display: none; position: absolute; top: 100%; left: 0; z-index: 2; margin-top: 4px; padding: 4px 0; min-width: 10em; background: #fff; border: 1px solid #c3c4c7; box-shadow: 0 2px 6px rgba( 0, 0, 0, 0.12 ); }
			/* Bridge the gap above the menu so moving the pointer from the icon keeps it open. */
			.row-actions .fut-source-menu::before { content: ""; position: absolute; top: -5px; left: 0; right: 0; height: 5px; }
			.row-actions .fut-source-menu a { display: block; padding: 6px 12px; white-space: nowrap; }
			.row-actions .fut-source-menu a:hover,
			.row-actions .fut-source-menu a:focus { background: #f0f0f1; }
			.row-actions .is-open .fut-source-menu { display: block; }
			/* Hover devices: hover (or keyboard focus) opens the menu; the icon is not clickable. */
			@media (hover: hover) {
				.row-actions .fut-source-toggle { cursor: default; }
				.row-actions .fut-source-toggle:focus:not(:focus-visible) { box-shadow: none; outline: 0; }
				.row-actions .fut-source-toggle:hover + .fut-source-menu,
				.row-actions .fut-source-toggle:focus-visible + .fut-source-menu,
				.row-actions .fut-source-menu:hover,
				.row-actions .fut-source-menu:focus-within { display: block; }
			}
			@media screen and (max-width: 782px) {
				/* Core gives row-action links/button-links `padding: 4px 16px 4px 0` here. Drop the link's right padding so the arrow hugs the text, and align tops. */
				.row-actions .force_translate > a:first-child { padding-right: 0; }
				.row-actions .fut-source-toggle { vertical-align: top; }
			}
		</style>
		<script>
			// Touch devices only; where hover works, the menu is CSS-only.
			document.addEventListener( 'click', function ( e ) {
				if ( window.matchMedia( '(hover: hover)' ).matches ) {
					return;
				}
				var toggle = e.target.closest( '.fut-source-toggle' );
				document.querySelectorAll( '.force_translate.is-open' ).forEach( function ( open ) {
					if ( ! toggle || open !== toggle.parentNode ) {
						open.classList.remove( 'is-open' );
						open.querySelector( '.fut-source-toggle' ).setAttribute( 'aria-expanded', 'false' );
					}
				} );
				if ( toggle ) {
					var isOpen = toggle.parentNode.classList.toggle( 'is-open' );
					toggle.setAttribute( 'aria-expanded', String( isOpen ) );
				}
			} );
		</script>
		<?php
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
		$branch      = isset( $_GET['force_translate_branch'] ) ? sanitize_key( wp_unslash( $_GET['force_translate_branch'] ) ) : '';
		if ( ! in_array( $branch, array( 'stable', 'dev' ), true ) ) {
			$branch = ''; // Automatic: Stable first, then Development.
		}

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
				'branch'      => $branch,
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
