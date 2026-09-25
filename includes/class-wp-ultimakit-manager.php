<?php
class UltimaKit_Module_Manager extends UltimaKit_Helpers {
	public $module_settings;
	protected $ID = '';
	protected $name;
	protected $description;
	protected $plan     = 'free';
	protected $category = '';
	protected $type     = '';
	protected $is_active;
	protected $read_more_link = 'https://www.wpultimakit.com';
	protected $settings       = 'no';
	protected $settings_link  = '#';
	public $modules;
	public $module_settings_obj;
	private $helper;

	private static $instance = null;

	public function __construct() {
		add_action( 'wp_ajax_ultimakit_update_settings', array( $this, 'ultimakit_update_settings' ) );
		add_action( 'wp_ajax_ultimakit_uninstall_settings', array( $this, 'ultimakit_uninstall_settings' ) );
		// Register AJAX actions for logged-in users
		add_action( 'wp_ajax_export_ultimakit_settings', array( $this, 'export_settings_to_json' ) );
		add_action( 'wp_ajax_import_ultimakit_settings', array( $this, 'import_settings_from_json' ) );

		add_action( 'wp_ajax_ultimakit_update_module_width', array( $this, 'ultimakit_update_module_width' ) );
		add_action( 'init', array( $this, 'autoloadModuleSettings' ) );
		$this->helper = new UltimaKit_Helpers();

		// Initialize modules from main and registered add-on paths
		$addon_paths = apply_filters( 'ultimakit_addon_module_paths', array() );
		$this->ultimakit_initializeModules( $addon_paths );
	}

	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Directories scanned for modules: the plugin's own plus any registered by add-ons.
	 *
	 * @param array $additional_paths Extra directories passed in by the caller.
	 * @return array
	 */
	protected function ultimakit_module_paths( $additional_paths = array() ) {
		$base_paths = array(
			ULTIMAKIT_FOR_WP_PATH . 'modules/',
			ULTIMAKIT_FOR_WP_PATH . 'modules/pro-modules/',
		);

		// Allow addon modules to hook into this.
		return array_merge( $base_paths, apply_filters( 'wpuk_module_paths', array() ), $additional_paths );
	}

	public function ultimakit_initializeModules( $additional_paths = array() ) {
		$paths = $this->ultimakit_module_paths( $additional_paths );

		$gravity_forms_active = $this->ultimakit_is_gravity_forms_active();
		$woocommerce_active   = $this->ultimakit_is_woocommerce_active();

		foreach ( $this->ultimakit_get_module_registry( $paths ) as $entry ) {
			$module_folder = $entry['folder'];
			$module_name   = $entry['name'];
			$metadata      = $entry['metadata'];

			if ( isset( $metadata['type'] ) && 'Gravity Forms' === $metadata['type'] && ! $gravity_forms_active ) {
				// Skip Gravity Forms modules if Gravity Forms plugin is not active.
				continue;
			}

			/*
			 * Same rule for WooCommerce. This gate only existed on the sidebar category and
			 * the per-category grids, so WooCommerce modules still appeared under "All
			 * Categories" on sites without WooCommerce — offering toggles that could never
			 * do anything. Gating at discovery keeps the listing, the category counts and
			 * the module loader consistent with each other.
			 */
			if ( isset( $metadata['type'] ) && 'WooCommerce' === $metadata['type'] && ! $woocommerce_active ) {
				continue;
			}

			$is_active       = $this->isModuleActive( $metadata['id'] );
			$module_instance = null;

			if ( $is_active ) {
				$module_file = $module_folder . '/class-wpultimakit-module-' . $module_name . '.php';

				if ( file_exists( $module_file ) ) {
					require_once $module_file;
					$class_name = 'UltimaKit_Module_' . ucfirst( str_replace( '-', '_', $module_name ) );

					if ( class_exists( $class_name ) ) {
						$module_instance = new $class_name();
					}
				}
			}

			// Use the real class for active modules or UltimaKit_Module_Base for inactive ones
			$module_info = $module_instance ?? new UltimaKit_Module_Base(
				array(
					'id'            => $metadata['id'],
					'name'          => $metadata['name'] ?? ucwords( str_replace( '-', ' ', $module_name ) ),
					'description'   => $metadata['description'] ?? 'No description available.',
					'category'      => $metadata['category'] ?? 'General',
					'plan'          => $metadata['plan'] ?? 'free',
					'type'          => $metadata['type'] ?? 'WordPress',
					'link'          => $metadata['link'] ?? '',
					'is_active'     => $is_active,
					'settings'      => $module_instance ? $module_instance->getSettings() : null,
					'settings_link' => $module_instance ? $module_instance->getSettingsLink() : null,
				)
			);

			$this->modules[] = $module_info;
		}

		/*
		 * Since 3.0.0 every former Pro module ships in modules/pro-modules/ and is discovered
		 * above like any other module, so the locked placeholders the free build used to
		 * list from includes/pro-modules.json are gone.
		 */

		// Apply filters and ensure modules are unique by ID
		$filtered_modules = apply_filters( 'ultimakit_modules', $this->modules );

		// Create a temporary array to track unique module IDs
		$unique_modules = array();
		$module_ids     = array();

		// Only keep modules with unique IDs (first occurrence wins)
		foreach ( $filtered_modules as $module ) {
			$module_id = $module->getID();
			// Keyed lookup rather than in_array() so this stays linear over ~380 entries.
			if ( ! isset( $module_ids[ $module_id ] ) ) {
				$module_ids[ $module_id ] = true;
				$unique_modules[]         = $module;
			}
		}

		$this->modules = $unique_modules;
	}

	/**
	 * Discover every module folder and its metadata.
	 *
	 * Scanning ~200 directories and decoding as many metadata.json files costs real time on
	 * every request, and the result only changes when the plugin is updated or an add-on
	 * registers a new path, so it is cached. The cache is bypassed under WP_DEBUG so module
	 * development still picks up changes immediately.
	 *
	 * @param array $paths Directories to scan.
	 * @return array List of [ folder, name, metadata ] entries.
	 */
	protected function ultimakit_get_module_registry( $paths ) {
		$cache_key = 'ultimakit_module_registry_' . md5( ULTIMAKIT_FOR_WP_VERSION . '|' . implode( '|', $paths ) );

		if ( ! ( defined( 'WP_DEBUG' ) && WP_DEBUG ) ) {
			$registry = get_transient( $cache_key );

			if ( is_array( $registry ) ) {
				return $registry;
			}
		}

		$registry = array();

		foreach ( $paths as $modules_directory ) {
			if ( ! is_dir( $modules_directory ) ) {
				continue;
			}

			$module_folders = glob( $modules_directory . '*', GLOB_ONLYDIR );

			if ( empty( $module_folders ) ) {
				continue;
			}

			foreach ( $module_folders as $module_folder ) {
				$metadata_file = $module_folder . '/metadata.json';

				if ( ! file_exists( $metadata_file ) ) {
					continue;
				}

				$metadata = json_decode( file_get_contents( $metadata_file ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Reads a module's metadata.json from the plugin folder.

				if ( ! isset( $metadata['id'] ) ) {
					continue;
				}

				$registry[] = array(
					'folder'   => $module_folder,
					'name'     => basename( $module_folder ),
					'metadata' => $metadata,
				);
			}
		}

		set_transient( $cache_key, $registry, DAY_IN_SECONDS );

		return $registry;
	}

	/**
	 * Whether Gravity Forms is active, without assuming wp-admin/includes/plugin.php is loaded.
	 *
	 * @return bool
	 */
	/**
	 * Whether WooCommerce is active.
	 *
	 * class_exists( 'WooCommerce' ) depends on WooCommerce having loaded already, which is
	 * not guaranteed at plugins_loaded, and is_plugin_active() is not available on the front
	 * end. Check the option directly so discovery gating is reliable in both contexts.
	 *
	 * @return bool
	 */
	protected function ultimakit_is_woocommerce_active() {
		static $is_active = null;

		if ( null !== $is_active ) {
			return $is_active;
		}

		if ( class_exists( 'WooCommerce' ) ) {
			$is_active = true;
			return $is_active;
		}

		$is_active = $this->ultimakit_is_plugin_active( 'woocommerce/woocommerce.php' );

		return $is_active;
	}

	/**
	 * Whether a plugin is active, without assuming wp-admin/includes/plugin.php is loaded.
	 *
	 * @param string $plugin Plugin basename, e.g. "woocommerce/woocommerce.php".
	 * @return bool
	 */
	protected function ultimakit_is_plugin_active( $plugin ) {
		if ( function_exists( 'is_plugin_active' ) ) {
			return is_plugin_active( $plugin );
		}

		if ( in_array( $plugin, (array) get_option( 'active_plugins', array() ), true ) ) {
			return true;
		}

		// Network-activated on multisite.
		if ( is_multisite() ) {
			$network_plugins = (array) get_site_option( 'active_sitewide_plugins', array() );
			return isset( $network_plugins[ $plugin ] );
		}

		return false;
	}

	protected function ultimakit_is_gravity_forms_active() {
		static $is_active = null;

		if ( null !== $is_active ) {
			return $is_active;
		}

		$is_active = $this->ultimakit_is_plugin_active( 'gravityforms/gravityforms.php' );

		return $is_active;
	}

	public function getAllCategories() {
		$all_categories_array = array();

		foreach ( $this->modules as $module ) {
			$type = $module->getType();
			if ( in_array( $type, array( 'WordPress', 'WooCommerce' ), true ) ) {
				$all_categories_array[] = $module->getCategory();
			}
		}

		$all_categories_array = array_unique( $all_categories_array );
		sort( $all_categories_array );

		foreach ( $all_categories_array as $category ) {
			echo '<option value="' . esc_html( $category ) . '">' . esc_html( $category ) . '</option>';
		}
	}

	public function getAllCategoriesList() {
		$all_categories_array = array();

		foreach ( $this->modules as $module ) {
			$type = $module->getType();
			if ( in_array( $type, array( 'WordPress', 'WooCommerce' ), true ) ) {
				$all_categories_array[] = $module->getCategory();
			}
		}

		$all_categories_array = array_unique( $all_categories_array );
		sort( $all_categories_array );

		return $all_categories_array;
	}


	public function getAllModules( $type = '', $plan = '' ) {
		$all_module_info = array();

		foreach ( $this->modules as $module ) {
			if ( ( empty( $type ) || $type === $module->getType() ) && ( empty( $plan ) || $plan === $module->getPlan() ) ) {
				$all_module_info[] = array(
					'id'            => $module->getID(),
					'name'          => $module->getName(),
					'description'   => $module->getDescription(),
					'category'      => $module->getCategory(),
					'plan'          => $module->getPlan(),
					'type'          => $module->getType(),
					'link'          => $module->getLink(),
					'is_active'     => $this->isModuleActive( $module->getID() ),
					'settings'      => $module->getSettings(),
					'settings_link' => $module->getSettingsLink(),
				);
			}
		}

		return $all_module_info;
	}

	public function getAllModulesByCategory( $category = '', $types = array() ) {
		$all_module_info = array();

		foreach ( $this->modules as $module ) {
			$type = $module->getType();
			if ( ! empty( $types ) && ! in_array( $type, $types, true ) ) {
				continue;
			}

			if ( ! empty( $category ) && $category === $module->getCategory() ) {
				$all_module_info[] = array(
					'id'            => $module->getID(),
					'name'          => $module->getName(),
					'description'   => $module->getDescription(),
					'category'      => $module->getCategory(),
					'plan'          => $module->getPlan(),
					'type'          => $module->getType(),
					'link'          => $module->getLink(),
					'is_active'     => $this->isModuleActive( $module->getID() ),
					'settings'      => $module->getSettings(),
					'settings_link' => $module->getSettingsLink(),
				);
			}
		}

		return $all_module_info;
	}

	public function isModuleActive( $module_id ) {
		/*
		 * This previously constructed a throwaway UltimaKit instance on every call purely
		 * to test that it was truthy, which meant a full plugin bootstrap per module per
		 * request. Nothing reads $this->module_settings, so only the lookups below matter.
		 */
		$settings = $this->get_module_settings( $module_id );

		if ( $settings ) {
			$this->module_settings = $settings;
		}

		if ( 'WooCommerce' === $this->getType() ) {
			if ( ! $this->woo_activation_check() ) {
				return false;
			}
		}

		return $this->is_module_enabled( $module_id );
	}

	public function getID() {
		return $this->ID;
	}

	public function getName() {
		return $this->name;
	}

	public function getDescription() {
		return $this->description;
	}

	public function getCategory() {
		return $this->category;
	}

	public function getPlan() {
		return $this->plan;
	}

	public function getType() {
		return $this->type;
	}

	public function getLink() {
		return $this->read_more_link;
	}

	public function getSettings() {
		return ( $this->settings ) ? $this->settings : 'no';
	}

	public function getSettingsLink() {
		return ( $this->settings_link ) ? $this->settings_link : '#';
	}

	public function getModuleSettings( $module_id = '', $key = '', $default_value = false ) {
		$all_settings    = self::ultimakit_get_all_settings();
		$module_settings = isset( $all_settings['values'][ $module_id ] ) ? $all_settings['values'][ $module_id ] : array();

		// If a specific setting key is provided, fetch only that setting
		if ( $key ) {
			$setting = isset( $module_settings['settings'] ) ? $module_settings['settings'] : null;

			// Unserialize the settings array if it exists and return the specific key, or the default
			$unserialized_settings = $setting ? maybe_unserialize( $setting ) : $default_value;
			return isset( $unserialized_settings[ $key ] ) ? $unserialized_settings[ $key ] : $default_value;
		}

		// If no specific key is provided, fetch all settings for the module
		$settings = array();
		foreach ( $module_settings as $setting_key => $setting_value ) {
			$settings[ $setting_key ] = maybe_unserialize( $setting_value );
		}

		return ! empty( $settings ) ? $settings : $default_value;
	}


	public function setModuleStatus( $module_id, $module_status ) {
		return $this->helper->ultimakit_update_module_setting( $module_id, 'enabled', $module_status, true );
	}

	public function setModuleSettings( $module_id, $module_settings_ar ) {

		$module_settings_ar = array_map(
			function ( $value ) {
				return $value;
			},
			$module_settings_ar
		);

		return $this->helper->ultimakit_update_module_setting( $module_id, 'settings', $module_settings_ar, true );
	}

	// First, define an array of allowed keys (whitelist)
	private function get_allowed_settings_keys() {
		return array(
			'noti_bar_text_area',
		);
	}

	// Then, modify your settings processing code
	public function process_module_settings( $raw_settings = null ) {
		if ( ! is_array( $raw_settings ) ) {
			return false;
		}

		$sanitized_settings = array();

		foreach ( $raw_settings as $key => $value ) {
			// Sanitize value based on key type
			$sanitized_value = $this->sanitize_setting_value( $key, $value );

			if ( null !== $sanitized_value ) {
				$sanitized_settings[ $key ] = $sanitized_value;
			}
		}

		return $sanitized_settings;
	}

	// Add a method to handle different types of sanitization based on the key
	private function sanitize_setting_value( $key, $value ) {
		/*
		 * Multi-select fields (type "select2") post an array of values, so recurse rather
		 * than passing the array to trim(), which is a fatal TypeError on PHP 8.
		 */
		if ( is_array( $value ) ) {
			$sanitized = array();
			foreach ( $value as $sub_key => $sub_value ) {
				$sanitized[ $sub_key ] = $this->sanitize_setting_value( $key, $sub_value );
			}
			return $sanitized;
		}

		if ( is_bool( $value ) || is_int( $value ) || is_float( $value ) ) {
			return $value;
		}

		// Remove slashes and trim
		$value = stripslashes( trim( (string) $value ) );

		// Sanitize based on key type
		switch ( $key ) {
			// HTML content
			case 'noti_bar_text_area':
				return wp_kses_post( $value );

			// Default fallback
			default:
				return sanitize_text_field( str_replace( '\\', '', $value ) );
		}
	}

	public function ultimakit_update_settings() {

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'You do not have sufficient permissions', 403 );
		}

		// Verify the nonce
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'ultimakit_nonce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'ultimakit-for-wp' ) ), 401 );
		}

		$helper = new UltimaKit_Helpers();

		// Check if migration has already been performed
		$migration_completed = get_option( 'ultimakit_migration_completed' );
		if ( ! $migration_completed ) {
			$helper->ultimakit_check_and_migrate_settings();
		}

		$module_id     = isset( $_POST['module_id'] ) ? sanitize_text_field( wp_unslash( $_POST['module_id'] ) ) : '';
		$module_status = isset( $_POST['module_status'] ) ? sanitize_text_field( wp_unslash( $_POST['module_status'] ) ) : '';

		// When getting the status from POST
		$module_status = isset( $_POST['module_status'] )
		? $this->validate_and_sanitize_status( wp_unslash( $_POST['module_status'] ) )
		: 'off';

		if ( isset( $_POST['module_settings'] ) && ( $_POST['module_settings'] ) ) {
			$module_settings = $this->process_module_settings( $_POST['module_settings'] );
		} else {
			$module_settings = isset( $_POST['module_settings'] ) ? sanitize_text_field( $_POST['module_settings'] ) : '';
		}

		// Sanitize save mode and initialize response array
		$save_mode = isset( $_POST['save_mode'] ) ? sanitize_text_field( $_POST['save_mode'] ) : '';
		$response  = array();

		if ( 'settings' === $save_mode ) {
			if ( isset( $module_settings['custom_option'] ) && ( 1 === $module_settings['custom_option'] || true === $module_settings['custom_option'] ) ) {
				// For Custom JS and CSS Module
				if ( isset( $module_settings['css_js_snippets'] ) ) {
					$helper->ultimakit_update_module_setting( $module_id, 'settings', $module_settings['css_js_snippets'], true );
				}
			} else {
				$helper->ultimakit_update_module_setting( $module_id, 'settings', $module_settings, true );
			}
			$response = array( 'message' => __( 'Module Settings Saved Successfully', 'ultimakit-for-wp' ) );
		} elseif ( 'on' === $module_status ) {
			$this->setModuleStatus( $module_id, $module_status );
			$response        = array(
				'message' => __( 'Module Enabled Successfully', 'ultimakit-for-wp' ),
				'status'  => 'on',
			);
			$module_instance = $this->getModuleInstance( $module_id );
			if ( $module_instance ) {
				if ( method_exists( $module_instance, 'activate' ) ) {
					$module_instance->activate();
				}
			}
			$helper->ultimakit_update_module_setting( $module_id, 'enabled', 'on', true );
		} elseif ( 'off' === $module_status ) {
			$this->setModuleStatus( $module_id, $module_status );
			$response        = array(
				'message' => __( 'Module Disabled Successfully', 'ultimakit-for-wp' ),
				'status'  => 'off',
			);
			$module_instance = $this->getModuleInstance( $module_id );
			if ( $module_instance ) {
				if ( method_exists( $module_instance, 'deactivate' ) ) {
					$module_instance->deactivate();
				}
			}
			$helper->ultimakit_update_module_setting( $module_id, 'enabled', 'off', true );
		}
		wp_send_json_success( $response );
	}

	/**
	 * Get an instance of a module by its ID.
	 *
	 * @param string $module_id The ID of the module to retrieve.
	 * @return object|null The module instance if found, null otherwise.
	 */
	public function getModuleInstance( $module_id ) {
		// Sanitize the module ID
		$module_id = sanitize_key( $module_id );

		// If the module isn't already loaded, try to load it
		$module_class = $this->getModuleClass( $module_id );
		if ( $module_class && class_exists( $module_class ) ) {
			try {
				$instance                    = new $module_class();
				$this->modules[ $module_id ] = $instance;
				return $instance;
			} catch ( Exception $e ) {
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					error_log( 'UltimaKit: failed to instantiate module ' . $module_id . ': ' . $e->getMessage() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Logged only with WP_DEBUG on.
				}
			}
		}

		return null;
	}

	/**
	 * Get the class name for a module by its ID.
	 *
	 * @param string $module_id The ID of the module.
	 * @return string|null The class name if found, null otherwise.
	 */
	private function getModuleClass( $module_id ) {
		// Map module IDs to their class names
		// This could be enhanced to use a more dynamic approach
		$module_classes = $this->getModuleClassMap();
		return isset( $module_classes[ $module_id ] ) ? $module_classes[ $module_id ] : null;
	}

	/**
	 * Get a mapping of module IDs to their class names.
	 *
	 * @return array Associative array of module IDs to class names.
	 */
	private function getModuleClassMap() {
		// For now, we'll use a static mapping
		return apply_filters(
			'ultimakit_module_class_map',
			array(
				'custom_css_js' => 'WP_Ultimakit_Custom_CSS_JS',
				'admin_bar'     => 'WP_Ultimakit_Admin_Bar',
			)
		);
	}


	// Add this before the switch statements
	private function validate_and_sanitize_status( $status ) {
		// Clean the input
		$status = trim( strtolower( strval( $status ) ) );

		// Validate against allowed values
		if ( ! in_array( $status, array( 'on', 'off' ), true ) ) {
			return 'off'; // default value
		}

		return $status;
	}

	public function ultimakit_uninstall_settings() {

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'You do not have sufficient permissions', 403 );
		}

		// Verify the nonce
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'ultimakit_nonce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'ultimakit-for-wp' ) ), 401 );
		}

		$stat = isset( $_POST['stat'] ) ? sanitize_text_field( wp_unslash( $_POST['stat'] ) ) : 'off';
		$stat = in_array( $stat, array( 'on', 'off' ), true ) ? $stat : 'off';

		update_option( 'ultimakit_uninstall_settings', $stat, 'no' );

		wp_send_json_success( $stat );
	}

	public function export_settings_to_json() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'ultimakit_module_settings';

		// Check for user capability
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'You do not have sufficient permissions', 403 );
		}

		// Verify the nonce
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'ultimakit_nonce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'ultimakit-for-wp' ) ), 401 );
		}

		// Query all rows from the settings table
		$results = $wpdb->get_results( "SELECT * FROM $table_name", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- The table name is $wpdb->prefix plus a fixed string.

		if ( empty( $results ) ) {
			wp_send_json_error( 'No settings found to export.' );
		}

		// Unserialize values for JSON export readability
		foreach ( $results as &$row ) {
			$row['setting_value'] = maybe_unserialize( $row['setting_value'] );
		}
		unset( $row );

		/*
		 * The settings table holds cleartext secrets (SMTP passwords, API keys), so the
		 * export is streamed back through the authenticated AJAX response and turned into
		 * a download client-side. It is never written to wp-content/uploads, which is
		 * world-readable and would expose those secrets to unauthenticated visitors.
		 */
		wp_send_json_success(
			array(
				'filename' => 'ultimakit-settings-' . gmdate( 'Y-m-d_H-i-s' ) . '.json',
				'settings' => $results,
			)
		);
	}

	public function import_settings_from_json() {
		// Check for user capability
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'You do not have sufficient permissions', 403 );
		}

		// Verify the nonce
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'ultimakit_nonce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'ultimakit-for-wp' ) ), 401 );
		}

		// Handle the uploaded JSON file
		if ( isset( $_FILES['json_file'] ) && 0 === $_FILES['json_file']['error'] ) {
			$file = $_FILES['json_file'];

			/*
			 * $_FILES['...']['type'] is supplied by the client and cannot be trusted, so the
			 * upload is validated by extension and by actually parsing it as JSON below.
			 */
			if ( ! isset( $file['tmp_name'] ) || ! is_uploaded_file( $file['tmp_name'] ) ) {
				wp_send_json_error( 'Invalid upload' );
			}

			$filetype = wp_check_filetype( isset( $file['name'] ) ? $file['name'] : '', array( 'json' => 'application/json' ) );
			if ( 'json' !== $filetype['ext'] ) {
				wp_send_json_error( 'Invalid file type' );
			}

			// Read and decode JSON file contents
			$file_contents = file_get_contents( $file['tmp_name'] ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Reads the uploaded file from PHP's temporary folder.
			if ( false === $file_contents ) {
				wp_send_json_error( 'Failed to read file' );
				return;
			}

			$settings = json_decode( $file_contents, true );

			if ( json_last_error() !== JSON_ERROR_NONE ) {
				wp_send_json_error( 'Invalid JSON: ' . json_last_error_msg() );
				return;
			}

			if ( ! is_array( $settings ) ) {
				wp_send_json_error( 'Invalid settings file' );
				return;
			}

			/*
			 * An imported file is untrusted: modules print some settings on public pages
			 * (the maintenance page, for one). Accept only modules this install ships,
			 * including Gravity Forms and WooCommerce modules whose plugin is inactive, only
			 * the two keys the table uses, and sanitize values exactly like a dashboard save.
			 */
			$known_modules = array();
			foreach ( $this->ultimakit_get_module_registry( $this->ultimakit_module_paths() ) as $entry ) {
				$known_modules[ $entry['metadata']['id'] ] = true;
			}

			foreach ( $settings as $row ) {
				if ( ! is_array( $row ) ) {
					continue;
				}
				// Ensure required fields are present in each row
				if ( ! isset( $row['module_name'], $row['setting_key'], $row['setting_value'], $row['autoload'] ) ) {
					continue; // Skip rows that do not have all necessary fields
				}

				$module_name = (string) $row['module_name'];
				$setting_key = (string) $row['setting_key'];

				if ( ! isset( $known_modules[ $module_name ] ) || ! in_array( $setting_key, array( 'enabled', 'settings' ), true ) ) {
					continue;
				}

				if ( 'enabled' === $setting_key ) {
					$setting_value = $this->validate_and_sanitize_status( $row['setting_value'] );
				} elseif ( is_array( $row['setting_value'] ) ) {
					$setting_value = $this->process_module_settings( $row['setting_value'] );
				} else {
					$setting_value = $this->sanitize_setting_value( $setting_key, $row['setting_value'] );
				}

				$this->helper->ultimakit_update_module_setting( $module_name, $setting_key, $setting_value, (int) ! empty( $row['autoload'] ) );
			}

			// Clear cache for consistency
			self::ultimakit_flush_settings_cache();
			wp_send_json_success( 'Settings imported successfully' );
		}

		wp_send_json_error( 'No file uploaded' );
	}




	public function ultimakit_update_module_width() {
		// Check for user capability
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'You do not have sufficient permissions', 403 );
		}

		// Verify the nonce
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'ultimakit_nonce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'ultimakit-for-wp' ) ), 401 );
		}

		$view_modue = isset( $_POST['view'] ) ? sanitize_text_field( wp_unslash( $_POST['view'] ) ) : 'full';
		$view_modue = in_array( $view_modue, array( 'small', 'full' ), true ) ? $view_modue : 'full';

		update_option( 'ultimakit_modules_list_view', $view_modue );
		wp_send_json_success( 'View settings successfully updated.' );
	}

	public function is_module_enabled( $module_name ) {
		$all_settings = self::ultimakit_get_all_settings();

		if ( ! isset( $all_settings['values'][ $module_name ]['enabled'] ) ) {
			return false;
		}

		return maybe_unserialize( $all_settings['values'][ $module_name ]['enabled'] ) === 'on';
	}

	public function get_module_settings( $module_name ) {
		$all_settings = self::ultimakit_get_all_settings();

		// Initialize settings array
		$settings = array();

		if ( isset( $all_settings['values'][ $module_name ]['settings'] ) ) {
			// Preserves the original numerically indexed single-row shape.
			$settings[0] = maybe_unserialize( $all_settings['values'][ $module_name ]['settings'] );
		}

		return $settings;
	}

	public function autoloadModuleSettings() {
		$all_settings = self::ultimakit_get_all_settings();

		// Prepare the settings array with unserialized values
		$autoload_settings = array();

		foreach ( $all_settings['autoload'] as $module_name => $module_settings ) {
			foreach ( $module_settings as $setting_key => $setting_value ) {
				$autoload_settings[ $module_name ][ $setting_key ] = maybe_unserialize( $setting_value );
			}
		}

		return $autoload_settings;
	}
}
