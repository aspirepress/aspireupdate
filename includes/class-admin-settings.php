<?php
/**
 * The Class for Admin Settings Page and functions to access Settings Values.
 *
 * @package aspire-update
 */

namespace AspireUpdate;

/**
 * The Class for Admin Settings Page and functions to access Settings Values.
 */
class Admin_Settings {

	/**
	 * Hold a single instance of the class.
	 *
	 * @var object
	 */
	private static $instance = null;

	/**
	 * The Name of the Option Group.
	 *
	 * @var string
	 */
	private $option_group = 'aspireupdate_settings';

	/**
	 * The Name of the Option.
	 *
	 * @var string
	 */
	private $option_name = 'aspireupdate_settings';

	/**
	 * An Array containing the values of the Options.
	 *
	 * @var array
	 */
	private $options = null;

	/**
	 * The base page for the options.
	 *
	 * @var string
	 */
	private $options_base;

	/**
	 * The Constructor.
	 */
	public function __construct() {
		$this->options_base = is_multisite() ? 'settings.php' : 'options-general.php';
		add_action( is_multisite() ? 'network_admin_menu' : 'admin_menu', [ $this, 'register_admin_menu' ] );
		add_filter( 'plugin_action_links_aspireupdate/aspire-update.php', [ $this, 'plugin_action_links' ] );
		add_action( 'admin_init', [ $this, 'reset_settings' ] );
		add_action( 'admin_init', [ $this, 'register_settings' ] );
		add_action( is_multisite() ? 'network_admin_notices' : 'admin_notices', [ $this, 'admin_notices' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'admin_enqueue_scripts' ] );
		add_action( 'update_option_' . $this->option_name, [ $this, 'update_option' ], 10, 2 );

		add_action( 'admin_init', [ $this, 'update_settings' ] );
		add_action( 'network_admin_edit_aspireupdate-settings', [ $this, 'update_settings' ] );
	}

	/**
	 * Initialize Class.
	 *
	 * @return object
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Get the default values for the settings.
	 *
	 * @return array The default values.
	 */
	private function get_default_settings() {
		$options                  = [];
		$options['api_host']      = 'https://api.aspirecloud.net';
		$options['compatibility'] = [
			'skip_rewriting_on_existing_response' => true,
		];
		return $options;
	}

	/**
	 * Handles the Reset Functionality and triggers a Notice to inform the same.
	 *
	 * @return void
	 */
	public function reset_settings() {
		$capability = is_multisite() ? 'manage_network_options' : 'manage_options';
		if (
			current_user_can( $capability ) &&
			isset( $_GET['reset'] ) &&
			( 'reset' === $_GET['reset'] ) &&
			isset( $_GET['reset-nonce'] ) &&
			wp_verify_nonce( sanitize_key( $_GET['reset-nonce'] ), 'aspireupdate-reset-nonce' )
		) {
			$options = $this->get_default_settings();
			update_site_option( $this->option_name, $options );
			update_site_option( 'aspireupdate-reset', 'true' );

			wp_safe_redirect(
				add_query_arg(
					[
						'reset-success'       => 'success',
						'reset-success-nonce' => wp_create_nonce( 'aspireupdate-reset-success-nonce' ),

					],
					network_admin_url( $this->options_base . '?page=aspireupdate-settings' )
				)
			);
			! defined( 'AP_RUN_TESTS' ) && exit;
		}
	}

	/**
	 * Delete all settings.
	 *
	 * @return void
	 */
	public function delete_all_settings() {
		delete_site_option( $this->option_name );
	}

	/**
	 * Show Admin notices.
	 *
	 * @return void
	 */
	public function admin_notices() {
		$capability = is_multisite() ? 'manage_network_options' : 'manage_options';
		if ( ! current_user_can( $capability ) ) {
			return;
		}

		/**
		 * The Admin Notice to convey a Reset Operation has happened.
		 */
		if (
			( 'true' === get_site_option( 'aspireupdate-reset' ) ) &&
			isset( $_GET['reset-success'] ) &&
			( 'success' === $_GET['reset-success'] ) &&
			isset( $_GET['reset-success-nonce'] ) &&
			wp_verify_nonce( sanitize_key( $_GET['reset-success-nonce'] ), 'aspireupdate-reset-success-nonce' )
		) {
			add_settings_error(
				$this->option_name,
				'aspireupdate_settings_reset',
				esc_html__( 'Settings have been reset to default.', 'aspireupdate' ),
				'success'
			);
			delete_site_option( 'aspireupdate-reset' );
		}

		/**
		 * The Admin Notice to convey settings have been successsfully saved.
		 */
		if (
			isset( $_GET['settings-updated-wpnonce'] ) &&
			wp_verify_nonce( sanitize_key( wp_unslash( $_GET['settings-updated-wpnonce'] ) ), 'aspireupdate-settings-updated-nonce' )
		) {
			add_settings_error(
				$this->option_name,
				'aspireupdate_settings_saved',
				esc_html__( 'Settings Saved', 'aspireupdate' ),
				'success'
			);
		}
	}

	/**
	 * Get the value of a Setting by giving priority to hard coded values.
	 *
	 * @param string $setting_name The name of the settings field.
	 * @param mixed  $default_value The Default value to return if the field is not defined.
	 *
	 * @return string The value of the settings field.
	 */
	public function get_setting( $setting_name, $default_value = false ) {
		if ( null === $this->options ) {
			$options          = get_site_option( $this->option_name, false );
			$default_settings = $this->get_default_settings();
			/**
			 * If the options are not set load defaults.
			 */
			if ( false === $options || ! empty( array_diff_key( $options, $default_settings ) ) ) {
				$options = is_array( $options ) ? wp_parse_args( $options, $default_settings ) : $default_settings;
				update_site_option( $this->option_name, $options );
			}
			$config_file_options = $this->get_settings_from_config_file();
			if ( is_array( $options ) ) {
				/**
				 * If User Options are saved do some processing to make it match the structure of the data from the config file.
				 */
				if ( isset( $options['api_host'] ) && ( 'other' === $options['api_host'] ) ) {
					$options['api_host'] = $options['api_host_other'];
				}

				if ( isset( $options['enable_debug_type'] ) && is_array( $options['enable_debug_type'] ) ) {
					$debug_types = [];
					foreach ( $options['enable_debug_type'] as $debug_type_name => $debug_type_enabled ) {
						if ( $debug_type_enabled ) {
							$debug_types[] = $debug_type_name;
						}
					}
					$options['enable_debug_type'] = $debug_types;
				}

				$this->options = wp_parse_args( $config_file_options, $options );
			}
		}
		return $this->options[ $setting_name ] ?? $default_value;
	}

	/**
	 * Get the values defined in the config file.
	 *
	 * @return array An array of values as defined in the Config File.
	 */
	private function get_settings_from_config_file() {
		$options = [];

		if ( ! defined( 'AP_ENABLE' ) ) {
			define( 'AP_ENABLE', false );
		} elseif ( AP_ENABLE ) {
			$options['enable'] = AP_ENABLE;
		}

		if ( ! defined( 'AP_HOST' ) ) {
			define( 'AP_HOST', '' );
		} else {
			$options['api_host'] = AP_HOST;
		}

		if ( ! defined( 'AP_API_KEY' ) ) {
			define( 'AP_API_KEY', '' );
		} else {
			$options['api_key'] = AP_API_KEY;
		}

		if ( ! defined( 'AP_COMPATIBILITY' ) ) {
			define( 'AP_COMPATIBILITY', [ 'skip_rewriting_on_existing_response' => true ] );
		} else {
			$options['compatibility'] = AP_COMPATIBILITY;
		}

		if ( ! defined( 'AP_DEBUG' ) ) {
			define( 'AP_DEBUG', false );
		} elseif ( AP_DEBUG ) {
			$options['enable_debug'] = AP_DEBUG;
		}

		if ( ! defined( 'AP_DEBUG_TYPES' ) ) {
			define( 'AP_DEBUG_TYPES', [] );
		} elseif ( is_array( AP_DEBUG_TYPES ) ) {
			$options['enable_debug_type'] = AP_DEBUG_TYPES;
		}

		if ( ! defined( 'AP_DISABLE_SSL' ) ) {
			define( 'AP_DISABLE_SSL', false );
		} elseif ( AP_DISABLE_SSL ) {
			$options['disable_ssl_verification'] = AP_DISABLE_SSL;
		}

		return $options;
	}

	/**
	 * Update settings for single site or network activated.
	 *
	 * @link http://wordpress.stackexchange.com/questions/64968/settings-api-in-multisite-missing-update-message
	 * @link http://benohead.com/wordpress-network-wide-plugin-settings/
	 *
	 * @return void
	 */
	public function update_settings() {
		// Exit if improper privileges.
		$capability = is_multisite() ? 'manage_network_options' : 'manage_options';
		if (
			! current_user_can( $capability ) ||
			! isset( $_POST['_wpnonce'] ) ||
			! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['_wpnonce'] ) ), 'aspireupdate-settings' )
		) {
			return;
		}

		// Save settings and redirect.
		if ( ( isset( $_POST['option_page'], $_POST['aspireupdate_settings'] ) && 'aspireupdate_settings' === $_POST['option_page'] ) ) {
			update_site_option(
				$this->option_name,
				// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Contents are sanitized in Admin_Settings::sanitize_settings.
				$this->sanitize_settings( wp_unslash( $_POST['aspireupdate_settings'] ) )
			);

			wp_safe_redirect(
				add_query_arg(
					[
						'settings-updated-wpnonce' => wp_create_nonce( 'aspireupdate-settings-updated-nonce' ),
					],
					network_admin_url( $this->options_base . '?page=aspireupdate-settings' )
				)
			);
			! defined( 'AP_RUN_TESTS' ) && exit;
		}
	}

	/**
	 * Set the plugin row's action links.
	 *
	 * @param array $links The existing action links.
	 * @return array The modified action links.
	 */
	public function plugin_action_links( $links ) {
		$settings_url = network_admin_url( $this->options_base . '?page=aspireupdate-settings' );
		return array_merge(
			[
				'settings' => '<a href="' . esc_url( $settings_url ) . '">' . __( 'Settings', 'aspireupdate' ) . '</a>',
			],
			$links
		);
	}

	/**
	 * Register the Admin Menu.
	 *
	 * @return void
	 */
	public function register_admin_menu() {
		if ( ! defined( 'AP_REMOVE_UI' ) ) {
			define( 'AP_REMOVE_UI', false );
		}
		if ( false === AP_REMOVE_UI ) {
			add_submenu_page(
				$this->options_base,
				'AspireUpdate',
				'AspireUpdate',
				is_multisite() ? 'manage_network_options' : 'manage_options',
				'aspireupdate-settings',
				[ $this, 'the_settings_page' ]
			);
		}
	}

	/**
	 * Enqueue the Scripts and Styles.
	 *
	 * @param string $hook The page identifier.
	 * @return void
	 */
	public function admin_enqueue_scripts( $hook ) {
		if ( 'settings_page_aspireupdate-settings' !== $hook ) {
			return;
		}
		wp_enqueue_style( 'aspire_update_settings_css', plugin_dir_url( __DIR__ ) . 'assets/css/aspire-update.css', [], AP_VERSION );
		wp_enqueue_script( 'aspire_update_settings_js', plugin_dir_url( __DIR__ ) . 'assets/js/aspire-update.js', [ 'jquery' ], AP_VERSION, true );
		wp_localize_script(
			'aspire_update_settings_js',
			'aspireupdate',
			[
				'ajax_url'             => admin_url( 'admin-ajax.php' ),
				'nonce'                => wp_create_nonce( 'aspireupdate-ajax' ),
				'domain'               => Utilities::get_site_domain(),
				'line_ending'          => PHP_EOL,
				'unexpected_error'     => esc_html__( 'Unexpected Error', 'aspireupdate' ),
				'api_host_other_error' => esc_html__( 'Please enter a valid URL', 'aspireupdate' ),
			]
		);
	}

	/**
	 * The Settings Page Markup.
	 *
	 * @return void
	 */
	public function the_settings_page() {
		$reset_url = add_query_arg(
			[
				'reset'       => 'reset',
				'reset-nonce' => wp_create_nonce( 'aspireupdate-reset-nonce' ),

			],
			network_admin_url( $this->options_base . '?page=aspireupdate-settings' )
		);
		Utilities::include_file(
			'page-admin-settings.php',
			[
				'options_base' => $this->options_base,
				'reset_url'    => $reset_url,
				'option_group' => $this->option_group,
			]
		);
	}

	/**
	 * Register all Settings.
	 *
	 * @return void
	 */
	public function register_settings() {
		$nonce   = wp_create_nonce( 'aspireupdate-settings' );
		$options = get_site_option( $this->option_name, false );
		/**
		 * If the options are not set load defaults.
		 */
		if ( false === $options ) {
			$options = $this->get_default_settings();
			update_site_option( $this->option_name, $options );
		}

		register_setting(
			$this->option_group,
			$this->option_name,
			[
				'sanitize_callback' => [ $this, 'sanitize_settings' ],
			]
		);

		add_settings_section(
			'aspireupdate_settings_section',
			esc_html__( 'API Configuration', 'aspireupdate' ),
			null,
			'aspireupdate-settings',
			[
				'before_section' => '<div class="%s">',
				'after_section'  => '</div>',
			]
		);

		add_settings_field(
			'enable',
			esc_html__( 'API Rewriting', 'aspireupdate' ),
			[ $this, 'add_settings_field_callback' ],
			'aspireupdate-settings',
			'aspireupdate_settings_section',
			[
				'id'    => 'enable',
				'type'  => 'checkbox',
				'data'  => $options,
				'label' => esc_html__( 'Rewrite API requests', 'aspireupdate' ),
			]
		);

		add_settings_field(
			'api_host',
			esc_html__( 'API Host', 'aspireupdate' ),
			[ $this, 'add_settings_field_callback' ],
			'aspireupdate-settings',
			'aspireupdate_settings_section',
			[
				'id'        => 'api_host',
				'type'      => 'hosts',
				'data'      => $options,
				'label_for' => 'aspireupdate-settings-field-api_host',
				'options'   => [
					[
						'value'           => 'https://api.aspirecloud.net',
						'label'           => sprintf(
							/* translators: 1: The name of the API Service */
							__( 'AspireCloud (%1$s)', 'aspireupdate' ),
							'https://api.aspirecloud.net'
						),
						'require-api-key' => 'false',
						'api-key-url'     => 'https://api.aspirecloud.net/v1/apitoken',
					],
					[
						'value'           => 'https://api.aspirecloud.io',
						'label'           => sprintf(
							/* translators: 1: The name of the API Service */
							__( 'AspireCloud Bleeding Edge (%1$s)', 'aspireupdate' ),
							'https://api.aspirecloud.io'
						),
						'require-api-key' => 'false',
						'api-key-url'     => 'https://api.aspirecloud.net/v1/apitoken',
					],
					[
						'value'           => 'other',
						'label'           => esc_html__( 'Other', 'aspireupdate' ),
						'require-api-key' => 'false',
					],
				],
			]
		);

		add_settings_field(
			'api_key',
			esc_html__( 'API Key', 'aspireupdate' ),
			[ $this, 'add_settings_field_callback' ],
			'aspireupdate-settings',
			'aspireupdate_settings_section',
			[
				'id'          => 'api_key',
				'type'        => 'api-key',
				'data'        => $options,
				'description' => esc_html__( 'Some repositories may require an API key for authentication.', 'aspireupdate' ),
				'label_for'   => 'aspireupdate-settings-field-api_key',
			]
		);

		add_settings_field(
			'compatibility',
			'<span id="compatibility-field-group-label">' . esc_html__( 'Compatibility', 'aspireupdate' ) . '</span>',
			[ $this, 'add_settings_field_callback' ],
			'aspireupdate-settings',
			'aspireupdate_settings_section',
			[
				'id'      => 'compatibility',
				'type'    => 'checkbox-group',
				'data'    => $options,
				'options' => [
					'skip_rewriting_on_existing_response' => esc_html__( 'Skip API rewriting if another plugin already appears to be rewriting API requests', 'aspireupdate' ),
				],
			]
		);

		add_settings_section(
			'aspireupdate_debug_settings_section',
			esc_html__( 'API Debug Configuration', 'aspireupdate' ),
			null,
			'aspireupdate-settings',
			[
				'before_section' => '<div class="%s">',
				'after_section'  => '</div>',
			]
		);

		add_settings_field(
			'enable_debug',
			esc_html__( 'Debug Mode', 'aspireupdate' ),
			[ $this, 'add_settings_field_callback' ],
			'aspireupdate-settings',
			'aspireupdate_debug_settings_section',
			[
				'id'    => 'enable_debug',
				'type'  => 'checkbox',
				'data'  => $options,
				'label' => esc_html__( 'Enable logging and other debugging functionality', 'aspireupdate' ),
			]
		);

		add_settings_field(
			'enable_debug_type',
			'<span id="enable_debug_type-field-group-label">' . esc_html__( 'Log Contents', 'aspireupdate' ) . '</span>',
			[ $this, 'add_settings_field_callback' ],
			'aspireupdate-settings',
			'aspireupdate_debug_settings_section',
			[
				'id'      => 'enable_debug_type',
				'type'    => 'checkbox-group',
				'data'    => $options,
				'options' => [
					'request'  => esc_html__( 'Include request arguments', 'aspireupdate' ),
					'response' => esc_html__( 'Include response headers and body', 'aspireupdate' ),
					'string'   => esc_html__( 'Include a simple description of each step in the rewriting process', 'aspireupdate' ),
				],
			]
		);

		add_settings_field(
			'disable_ssl_verification',
			esc_html__( 'SSL Verification', 'aspireupdate' ),
			[ $this, 'add_settings_field_callback' ],
			'aspireupdate-settings',
			'aspireupdate_debug_settings_section',
			[
				'id'    => 'disable_ssl_verification',
				'type'  => 'checkbox',
				'data'  => $options,
				'class' => 'advanced-setting',
				'label' => esc_html__( 'Disable SSL verification to allow for local testing', 'aspireupdate' ),
			]
		);
	}

	/**
	 * The Fields API which any CMS should have in its core but something we dont, hence this ugly hack.
	 *
	 * @codeCoverageIgnore Test with E2E tests instead.
	 *
	 * @param array $args The Field Parameters.
	 *
	 * @return void Echos the Field HTML.
	 */
	public function add_settings_field_callback( $args = [] ) {

		$defaults       = [
			'id'          => '',
			'type'        => 'text',
			'description' => '',
			'data'        => [],
			'options'     => [],
		];
		$args           = wp_parse_args( $args, $defaults );
		$id             = $args['id'];
		$type           = $args['type'];
		$description    = $args['description'];
		$group_options  = $args['options'];
		$options        = $args['data'];
		$description_id = "{$id}-description";

		echo '<div class="aspireupdate-settings-field-wrapper aspireupdate-settings-field-wrapper-' . esc_attr( $id ) . '">';
		switch ( $type ) {
			case 'text':
				?>
					<input
						type="text"
						id="aspireupdate-settings-field-<?php echo esc_attr( $id ); ?>"
						name="<?php echo esc_attr( $this->option_name ); ?>[<?php echo esc_attr( $id ); ?>]"
						value="<?php echo esc_attr( $options[ $id ] ?? '' ); ?>"
						class="regular-text"
						<?php if ( $description ) : ?>
							aria-describedby="<?php echo esc_attr( $description_id ); ?>"
						<?php endif; ?>
					/>
					<p class="description" id="<?php echo esc_attr( $description_id ); ?>"><?php echo esc_html( $description ); ?></p>
					<?php
				break;

			case 'textarea':
				?>
					<textarea
						id="aspireupdate-settings-field-<?php echo esc_attr( $id ); ?>"
						name="<?php echo esc_attr( $this->option_name ); ?>[<?php echo esc_attr( $id ); ?>]"
						rows="5"
						cols="50"
						<?php if ( $description ) : ?>
							aria-describedby="<?php echo esc_attr( $description_id ); ?>"
						<?php endif; ?>
					>
						<?php echo esc_textarea( $options[ $id ] ?? '' ); ?>
					</textarea>
					<p class="description" id="<?php echo esc_attr( $description_id ); ?>"><?php echo esc_html( $description ); ?></p>
					<?php
				break;

			case 'checkbox':
				?>
					<input
						type="checkbox"
						id="aspireupdate-settings-field-<?php echo esc_attr( $id ); ?>"
						name="<?php echo esc_attr( $this->option_name ); ?>[<?php echo esc_attr( $id ); ?>]"
						value="1"
						<?php checked( 1, $options[ $id ] ?? 0 ); ?>
					/>
					<?php if ( isset( $args['label'] ) ) : ?>
						<label for="aspireupdate-settings-field-<?php echo esc_attr( $id ); ?>">
							<?php echo esc_html( $args['label'] ); ?>
						</label>
					<?php endif; ?>
					<?php
				break;

			case 'checkbox-group':
				?>
				<div role="group" aria-labelledby="<?php echo esc_attr( $id ); ?>-field-group-label">
				<?php foreach ( $group_options as $key => $label ) : ?>
					<?php $item_id = "aspireupdate-settings-field-{$id}-{$key}"; ?>
					<p>
						<input type="checkbox" id="<?php echo esc_attr( $item_id ); ?>" name="<?php echo esc_attr( $this->option_name ); ?>[<?php echo esc_attr( $id ); ?>][<?php echo esc_attr( $key ); ?>]" value="1" <?php checked( 1, $options[ $id ][ $key ] ?? 0 ); ?> />
						<label for="<?php echo esc_attr( $item_id ); ?>"><?php echo esc_html( $label ); ?></label>
					</p>
				<?php endforeach; ?>
				</div>
				<?php
				break;

			case 'api-key':
				?>
					<input
						type="text"
						id="aspireupdate-settings-field-<?php echo esc_attr( $id ); ?>"
						name="<?php echo esc_attr( $this->option_name ); ?>[<?php echo esc_attr( $id ); ?>]"
						value="<?php echo esc_attr( $options[ $id ] ?? '' ); ?>"
						class="regular-text"
						aria-describedby="aspireupdate-api-key-notice <?php echo $description ? esc_attr( $description_id ) : ''; ?>"
					/>
					<button id="aspireupdate-generate-api-key" class="button button-secondary">
						<span class="dashicons dashicons-admin-network" aria-hidden="true"></span>
						<span><?php esc_html_e( 'Generate API Key', 'aspireupdate' ); ?></span>
					</button>
					<p id="<?php echo esc_attr( $description_id ); ?>"><?php echo esc_html( $description ); ?></p>
					<div id="aspireupdate-api-key-notice"></div>
					<?php
				break;

			case 'hosts':
				?>
				<select
					id="aspireupdate-settings-field-<?php echo esc_attr( $id ); ?>"
					name="<?php echo esc_attr( $this->option_name ); ?>[<?php echo esc_attr( $id ); ?>]"
					class="regular-text"
				>
					<?php
					foreach ( $group_options as $group_option ) {
						?>
							<option
								data-api-key-url="<?php echo esc_html( $group_option['api-key-url'] ?? '' ); ?>"
								data-require-api-key="<?php echo esc_html( $group_option['require-api-key'] ?? 'false' ); ?>"
								value="<?php echo esc_attr( $group_option['value'] ?? '' ); ?>"
								<?php selected( esc_attr( $group_option['value'] ?? '' ), esc_attr( $options[ $id ] ?? '' ) ); ?>
							>
								<?php echo esc_html( $group_option['label'] ?? '' ); ?>
							</option>
						<?php
					}
					?>
				</select>
				<p>
					<label for="aspireupdate-settings-field-<?php echo esc_attr( $id ); ?>_other">
						<?php esc_html_e( 'Enter a custom API host. Ensure that it starts with https://.', 'aspireupdate' ); ?>
					</label><br>
					<input
						type="url"
						data-pattern="(https?)://.*"
						id="aspireupdate-settings-field-<?php echo esc_attr( $id ); ?>_other"
						name="<?php echo esc_attr( $this->option_name ); ?>[<?php echo esc_attr( $id ); ?>_other]"
						value="<?php echo esc_attr( $options[ $id . '_other' ] ?? '' ); ?>"
						class="regular-text"
					/>
				</p>
				<?php
				break;
		}

		echo '</div>';
	}

	/**
	 * Sanitize the Inputs.
	 *
	 * @param array $input The Input values.
	 * @return array The processed Input.
	 */
	public function sanitize_settings( $input ) {
		$sanitized_input = [];

		$sanitized_input['enable']         = (int) ! empty( $input['enable'] );
		$sanitized_input['api_key']        = sanitize_text_field( $input['api_key'] ?? '' );
		$sanitized_input['api_host']       = sanitize_text_field( $input['api_host'] ?? '' );
		$sanitized_input['api_host_other'] = sanitize_text_field( $input['api_host_other'] ?? '' );

		if ( isset( $input['compatibility'] ) && is_array( $input['compatibility'] ) ) {
			$sanitized_input['compatibility'] = array_map(
				function ( $value ) {
					return (int) ! empty( $value );
				},
				$input['compatibility']
			);
		} else {
			$sanitized_input['compatibility'] = [
				'skip_rewriting_on_existing_response' => 0,
			];
		}

		$sanitized_input['enable_debug'] = (int) ! empty( $input['enable_debug'] );
		if ( isset( $input['enable_debug_type'] ) && is_array( $input['enable_debug_type'] ) ) {
			$sanitized_input['enable_debug_type'] = array_map( 'sanitize_text_field', $input['enable_debug_type'] );
		} else {
			$sanitized_input['enable_debug_type'] = [];
		}
		$sanitized_input['disable_ssl_verification'] = (int) ! empty( $input['disable_ssl_verification'] );

		return $sanitized_input;
	}

	/**
	 * Spring cleaning when the options are updated.
	 *
	 * @param array $old_value The old option value
	 * @param array $new_value The new option value
	 *
	 * @return void
	 */
	public function update_option( $old_value, $new_value ) {
		if (
			isset( $old_value['enable_debug'] ) && $old_value['enable_debug'] &&
			isset( $new_value['enable_debug'] ) && ! $new_value['enable_debug']
		) {
			Debug::clear();
		}
	}
}
