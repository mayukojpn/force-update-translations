<?php
/**
 * Settings screen, locale preference, and bulk plugin updates.
 *
 * @package Force_Update_Translations
 */

/**
 * Admin settings for Force Update Translations.
 */
class Force_Update_Translations_Settings extends Force_Update_Translations {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_init', array( $this, 'handle_bulk_update' ) );
		add_action( 'admin_init', array( $this, 'handle_clear_forced' ) );
	}

	/**
	 * Register settings page.
	 *
	 * @return void
	 */
	public function admin_menu() {
		add_options_page(
			__( 'Force Update Translations', 'force-update-translations' ),
			__( 'Force Update Translations', 'force-update-translations' ),
			'manage_options',
			'force-update-translations',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Register Settings API fields.
	 *
	 * @return void
	 */
	public function register_settings() {
		register_setting(
			'fut_settings_group',
			self::SETTINGS_OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
				'default'           => self::default_settings(),
			)
		);

		add_settings_section(
			'fut_general',
			__( 'General', 'force-update-translations' ),
			'__return_false',
			'force-update-translations'
		);

		add_settings_field(
			'locale_source',
			__( 'Locale source', 'force-update-translations' ),
			array( $this, 'render_locale_source_field' ),
			'force-update-translations',
			'fut_general'
		);

		add_settings_field(
			'protect_from_packs',
			__( 'Language pack protection', 'force-update-translations' ),
			array( $this, 'render_protect_field' ),
			'force-update-translations',
			'fut_general'
		);
	}

	/**
	 * Sanitize settings array.
	 *
	 * @param array $input Raw input.
	 * @return array
	 */
	public function sanitize_settings( $input ) {
		$defaults = self::default_settings();
		$output   = $defaults;

		if ( ! is_array( $input ) ) {
			return $output;
		}

		$output['locale_source']      = ( isset( $input['locale_source'] ) && 'site' === $input['locale_source'] ) ? 'site' : 'user';
		$output['protect_from_packs'] = empty( $input['protect_from_packs'] ) ? 0 : 1;

		return $output;
	}

	/**
	 * Render locale source field.
	 *
	 * @return void
	 */
	public function render_locale_source_field() {
		$settings = $this->get_settings();
		$current  = $settings['locale_source'];
		?>
		<fieldset>
			<label>
				<input type="radio" name="<?php echo esc_attr( self::SETTINGS_OPTION ); ?>[locale_source]" value="user" <?php checked( $current, 'user' ); ?> />
				<?php
				printf(
					/* translators: %s: locale code */
					esc_html__( 'User language (%s)', 'force-update-translations' ),
					esc_html( get_user_locale() )
				);
				?>
			</label>
			<br />
			<label>
				<input type="radio" name="<?php echo esc_attr( self::SETTINGS_OPTION ); ?>[locale_source]" value="site" <?php checked( $current, 'site' ); ?> />
				<?php
				printf(
					/* translators: %s: locale code */
					esc_html__( 'Site language (%s)', 'force-update-translations' ),
					esc_html( get_locale() )
				);
				?>
			</label>
			<p class="description">
				<?php esc_html_e( 'Controls which locale is used when downloading translations and detecting the installed Stable/Development source.', 'force-update-translations' ); ?>
			</p>
		</fieldset>
		<?php
	}

	/**
	 * Render overwrite-protection field.
	 *
	 * @return void
	 */
	public function render_protect_field() {
		$settings = $this->get_settings();
		?>
		<label>
			<input type="checkbox" name="<?php echo esc_attr( self::SETTINGS_OPTION ); ?>[protect_from_packs]" value="1" <?php checked( ! empty( $settings['protect_from_packs'] ) ); ?> />
			<?php esc_html_e( 'Prevent official WordPress.org language packs from overwriting forced translations', 'force-update-translations' ); ?>
		</label>
		<p class="description">
			<?php esc_html_e( 'When enabled, translation updates for domains you forced through this plugin are skipped until you clear their protection.', 'force-update-translations' ); ?>
		</p>
		<?php
	}

	/**
	 * Handle bulk plugin translation updates.
	 *
	 * @return void
	 */
	public function handle_bulk_update() {
		if ( ! isset( $_POST['fut_bulk_update'] ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to update translation files.', 'force-update-translations' ) );
		}

		check_admin_referer( 'fut_bulk_update', 'fut_bulk_update_nonce' );

		$branch = isset( $_POST['fut_bulk_branch'] ) ? sanitize_key( wp_unslash( $_POST['fut_bulk_branch'] ) ) : 'stable';
		if ( ! in_array( $branch, array( 'stable', 'dev' ), true ) ) {
			$branch = 'stable';
		}

		$selected = array();
		if ( isset( $_POST['fut_plugins'] ) ) {
			$selected = array_map( 'sanitize_text_field', wp_unslash( (array) $_POST['fut_plugins'] ) );
		}

		$projects = array();
		foreach ( $selected as $plugin_file ) {
			if ( ! preg_match( '/^([a-zA-Z0-9-_]+)\/([a-zA-Z0-9-_]+\.php)$/', $plugin_file, $plugin_slug ) ) {
				continue;
			}

			$plugin_path = WP_PLUGIN_DIR . '/' . $plugin_file;
			$real_path   = realpath( $plugin_path );
			if ( false === $real_path || 0 !== strpos( $real_path, WP_PLUGIN_DIR ) ) {
				continue;
			}

			$plugin_data              = get_plugin_data( $plugin_path, false );
			$projects[ $plugin_file ] = array(
				'type'        => 'plugin',
				'branch'      => $branch,
				'sub_project' => array(
					'slug' => $plugin_slug[1],
					'name' => $plugin_data['Name'],
				),
			);
		}

		if ( empty( $projects ) ) {
			$this->admin_notices['bulk'][] = array(
				'status'  => 'error',
				'content' => esc_html__( 'No valid plugins were selected.', 'force-update-translations' ),
			);
			add_action( 'admin_notices', array( $this, 'admin_notices' ) );
			return;
		}

		$this->get_files( $projects );
	}

	/**
	 * Clear forced-translation protection entries.
	 *
	 * @return void
	 */
	public function handle_clear_forced() {
		if ( ! isset( $_POST['fut_clear_forced'] ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to update translation files.', 'force-update-translations' ) );
		}

		check_admin_referer( 'fut_clear_forced', 'fut_clear_forced_nonce' );

		$removed                        = $this->clear_forced_translations();
		$this->admin_notices['clear'][] = array(
			'status'  => 'success',
			'content' => sprintf(
				/* translators: %d: number of entries */
				esc_html( _n( 'Cleared protection for %d translation.', 'Cleared protection for %d translations.', $removed, 'force-update-translations' ) ),
				(int) $removed
			),
		);
		add_action( 'admin_notices', array( $this, 'admin_notices' ) );
	}

	/**
	 * WordPress.org plugins available for bulk update.
	 *
	 * @return array plugin_file => plugin name
	 */
	protected function get_wporg_plugins() {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$all          = get_plugins();
		$plugin_state = get_site_transient( 'update_plugins' );
		$available    = array();

		foreach ( $all as $plugin_file => $data ) {
			$on_wporg = isset( $plugin_state->response[ $plugin_file ]->id ) || isset( $plugin_state->no_update[ $plugin_file ]->id );
			if ( ! $on_wporg ) {
				continue;
			}
			if ( ! preg_match( '/^([a-zA-Z0-9-_]+)\//', $plugin_file ) ) {
				continue;
			}
			$available[ $plugin_file ] = $data['Name'];
		}

		asort( $available, SORT_NATURAL | SORT_FLAG_CASE );
		return $available;
	}

	/**
	 * Render settings page.
	 *
	 * @return void
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$plugins = $this->get_wporg_plugins();
		$forced  = $this->get_forced_translations();
		$locale  = $this->get_target_locale();
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<form method="post" action="options.php">
				<?php
				settings_fields( 'fut_settings_group' );
				do_settings_sections( 'force-update-translations' );
				submit_button( __( 'Save settings', 'force-update-translations' ) );
				?>
			</form>

			<hr />

			<h2><?php esc_html_e( 'Bulk update plugin translations', 'force-update-translations' ); ?></h2>
			<p>
				<?php
				printf(
					/* translators: %s: locale code */
					esc_html__( 'Downloads will use locale: %s', 'force-update-translations' ),
					'<code>' . esc_html( $locale ) . '</code>'
				);
				?>
			</p>

			<?php if ( 'en_US' === $locale ) : ?>
				<p><?php esc_html_e( 'The target locale is en_US, so translation downloads are not available.', 'force-update-translations' ); ?></p>
			<?php elseif ( empty( $plugins ) ) : ?>
				<p><?php esc_html_e( 'No WordPress.org plugins were found. Visit Plugins to refresh update data, then return here.', 'force-update-translations' ); ?></p>
			<?php else : ?>
				<form method="post" action="">
					<?php wp_nonce_field( 'fut_bulk_update', 'fut_bulk_update_nonce' ); ?>
					<p>
						<label for="fut_bulk_branch"><?php esc_html_e( 'Source', 'force-update-translations' ); ?></label>
						<select name="fut_bulk_branch" id="fut_bulk_branch">
							<option value="stable"><?php echo esc_html( $this->get_branch_label( 'stable' ) ); ?></option>
							<option value="dev"><?php echo esc_html( $this->get_branch_label( 'dev' ) ); ?></option>
						</select>
					</p>
					<table class="widefat striped">
						<thead>
							<tr>
								<td class="manage-column column-cb check-column"><input type="checkbox" id="fut-select-all" /></td>
								<th><?php esc_html_e( 'Plugin', 'force-update-translations' ); ?></th>
								<th><?php esc_html_e( 'Installed source', 'force-update-translations' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $plugins as $plugin_file => $name ) : ?>
								<?php
								$slug   = dirname( $plugin_file );
								$branch = $this->detect_installed_branch( 'plugin', $slug, $locale );
								?>
								<tr>
									<th scope="row" class="check-column">
										<input type="checkbox" name="fut_plugins[]" value="<?php echo esc_attr( $plugin_file ); ?>" />
									</th>
									<td>
										<strong><?php echo esc_html( $name ); ?></strong>
										<br /><code><?php echo esc_html( $plugin_file ); ?></code>
									</td>
									<td>
										<?php
										echo '' !== $branch
											? esc_html( $this->get_branch_label( $branch ) )
											: esc_html__( 'Unknown / not forced yet', 'force-update-translations' );
										?>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
					<?php submit_button( __( 'Update selected translations', 'force-update-translations' ), 'primary', 'fut_bulk_update' ); ?>
				</form>
				<script>
				(function () {
					var master = document.getElementById('fut-select-all');
					if (!master) { return; }
					master.addEventListener('change', function () {
						document.querySelectorAll('input[name="fut_plugins[]"]').forEach(function (el) {
							el.checked = master.checked;
						});
					});
				})();
				</script>
			<?php endif; ?>

			<hr />

			<h2><?php esc_html_e( 'Protected forced translations', 'force-update-translations' ); ?></h2>
			<?php if ( empty( $forced ) ) : ?>
				<p><?php esc_html_e( 'No forced translations are currently protected.', 'force-update-translations' ); ?></p>
			<?php else : ?>
				<table class="widefat striped">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Type', 'force-update-translations' ); ?></th>
							<th><?php esc_html_e( 'Slug', 'force-update-translations' ); ?></th>
							<th><?php esc_html_e( 'Locale', 'force-update-translations' ); ?></th>
							<th><?php esc_html_e( 'Source', 'force-update-translations' ); ?></th>
							<th><?php esc_html_e( 'Updated', 'force-update-translations' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $forced as $entry ) : ?>
							<tr>
								<td><?php echo esc_html( isset( $entry['type'] ) ? $entry['type'] : '' ); ?></td>
								<td><?php echo esc_html( isset( $entry['slug'] ) ? $entry['slug'] : '' ); ?></td>
								<td><?php echo esc_html( isset( $entry['locale'] ) ? $entry['locale'] : '' ); ?></td>
								<td>
									<?php
									$branch = isset( $entry['branch'] ) ? $entry['branch'] : '';
									echo '' !== $branch ? esc_html( $this->get_branch_label( $branch ) ) : '&mdash;';
									?>
								</td>
								<td>
									<?php
									echo isset( $entry['updated'] )
										? esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), (int) $entry['updated'] ) )
										: '&mdash;';
									?>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
				<form method="post" action="" style="margin-top: 1em;">
					<?php wp_nonce_field( 'fut_clear_forced', 'fut_clear_forced_nonce' ); ?>
					<?php submit_button( __( 'Clear all protection', 'force-update-translations' ), 'secondary', 'fut_clear_forced' ); ?>
				</form>
			<?php endif; ?>
		</div>
		<?php
	}
}

new Force_Update_Translations_Settings();
