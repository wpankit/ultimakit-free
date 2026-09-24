<?php
/**
 * Class UltimaKit_Module_Hide_Admin_Notices_Advanced
 *
 * @since 1.0.0
 * @package    UltimaKit
 */

/**
 * Class UltimaKit_Module_Hide_Admin_Notices_Advanced
 *
 * This class provides methods to control the display of dashboard widgets based on user preferences.
 * It allows users to selectively hide certain dashboard widgets to streamline their WordPress dashboard
 * experience and improve usability.
 *
 * @since 1.0.0
 */
class UltimaKit_Module_Hide_Admin_Notices_Advanced extends UltimaKit_Module_Manager {
	/**
	 * @var string
	 */
	protected $ID = 'ultimakit_module_hide_admin_notices_advanced';

	/**
	 * The name of the module.
	 *
	 * @var string
	 */
	protected $name;

	/**
	 * A brief description of what the module does.
	 *
	 * @var string
	 */
	protected $description;

	/**
	 * The pricing plan associated with the module.
	 *
	 * @var string
	 */
	protected $plan = 'free';

	/**
	 * The category of functionality the module falls under.
	 *
	 * @var string
	 */
	protected $category = 'Admin Interface';

	/**
	 * The type of module, indicating its platform or use case.
	 *
	 * @var string
	 */
	protected $type = 'WordPress';

	/**
	 * Flag indicating whether the module is active.
	 *
	 * @var bool
	 */
	protected $is_active;

	/**
	 * URL providing more detailed information about the module.
	 *
	 * @var string
	 */
	protected $read_more_link = 'hide-admin-notices-advanced-in-wordpress';

	/**
	 * The settings associated with the module, if any.
	 *
	 * @var array
	 */
	protected $settings;

	/**
	 * Constructs the Hide Admin Bar module instance.
	 *
	 * Initializes the module with default values for properties and prepares
	 * any necessary setup or hooks into WordPress. This may include setting
	 * initial values, registering hooks, or preparing resources needed for
	 * the module to function properly within WordPress.
	 */
	public function __construct() {
		$this->name        = __( 'Hide Admin Notices - Advanced', 'ultimakit-for-wp' );
		$this->description = __( 'Stop annoying admin notices.', 'ultimakit-for-wp' );
		$this->is_active   = $this->isModuleActive( $this->ID );
		$this->settings    = 'yes';
		$this->initializeModule();
		$this->settings_link = '#';
	}

	/**
	 * Initializes the specific module within the application.
	 *
	 * This function is responsible for performing the initial setup required to get the module
	 * up and running. This includes registering hooks and filters, enqueing styles and scripts,
	 * and any other preliminary setup tasks that need to be performed before the module can
	 * start functioning as expected.
	 *
	 * It's typically called during the plugin or theme's initialization phase, ensuring that
	 * all module dependencies are loaded and ready for use.
	 *
	 * @return void
	 */
	protected function initializeModule() {
		if ( $this->is_active ) {
			add_action( 'admin_enqueue_scripts', array( $this, 'add_scripts' ) );
			add_action( 'admin_footer', array( $this, 'add_modal' ) );

			add_action( 'network_admin_notices', array( $this, 'notices_start' ), 0 );
			add_action( 'user_admin_notices', array( $this, 'notices_start' ), 0 );
			add_action( 'admin_notices', array( $this, 'notices_start' ), 0 );
			// add_action( 'admin_notices',     array( $this, 'notices_end'), 100000 );
			add_action( 'admin_init', array( $this, 'remove_user_notices' ) );
			add_action( 'plugin_check', array( $this, 'plugin_check' ) );
			add_action( 'theme_check', array( $this, 'theme_check' ) );
			add_action( 'core_check', array( $this, 'core_check' ) );
			add_action( 'all_check', array( $this, 'all_check' ) );
		}
	}

	/**
	 * Adds a modal dialog to the page.
	 *
	 * This function is responsible for initiating and rendering a modal dialog within the
	 * application or website interface. It typically involves setting up the necessary HTML
	 * and JavaScript for the modal to function and display correctly. The modal can be used
	 * for various purposes, such as displaying information, confirming actions, or collecting
	 * user input.
	 *
	 * @return void
	 */
	public function add_modal() {
		$arguments          = array();
		$arguments['ID']    = $this->ID;
		$arguments['title'] = __( 'Hide Admin Notices - Settings', 'ultimakit-for-wp' );

		$existing_options = stripslashes( $this->getModuleSettings( $this->ID, 'ultimakit_hide_notices_for' ) );

		if ( ! empty( $existing_options ) ) {
			$options = json_decode( $existing_options, true );
		}
		$roles      = wp_roles()->roles;
		$roles_html = '';

		foreach ( $roles as $index => $role ) {
			$slug      = $index; // Assuming the index serves as the slug
			$role_name = $role['name'];

			// Initialize variables
			$checked_plugin = '-';
			$checked_theme  = '-';
			$checked_core   = '-';
			$checked_all    = '-';

			if ( isset( $options[ $index ] ) ) {
				foreach ( $options[ $index ] as $value_to_check ) {
					if ( $value_to_check === 'plugin_check' ) {
						$checked_plugin = 'checked';
					} elseif ( $value_to_check === 'theme_check' ) {
						$checked_theme = 'checked';
					} elseif ( $value_to_check === 'core_check' ) {
						$checked_core = 'checked';
					} elseif ( $value_to_check === 'all_check' ) {
						$checked_all = 'checked';
					}
				}
			}

			$roles_html .= '<div class="role" data-role="' . $slug . '"><div>' . $role_name . '</div>
		        <div class="form-check form-switch module-switch"><input type="checkbox" id="' . $slug . '_plugin_check" class="form-check-input plugin_check" ' . $checked_plugin . ' ><label class="form-check-label switch-label" for="' . $slug . '_plugin_check">toggle me</label></div>
		        <div class="form-check form-switch module-switch"><input type="checkbox" id="' . $slug . '_theme_check" class="form-check-input theme_check" ' . $checked_theme . '><label class="form-check-label switch-label" for="' . $slug . '_theme_check">toggle me</label></div>
		        <div class="form-check form-switch module-switch"><input type="checkbox" id="' . $slug . '_core_check" class="form-check-input core_check" ' . $checked_core . '><label class="form-check-label switch-label" for="' . $slug . '_core_check">toggle me</label></div>
		        <div class="form-check form-switch module-switch"><input type="checkbox" id="' . $slug . '_all_check" class="form-check-input all_check" ' . $checked_all . '><label class="form-check-label switch-label" for="' . $slug . '_all_check">toggle me</label></div>
		    </div>';
		}

		$arguments['fields'] = array(
			'hide_notices'               => array(
				'type'  => 'html',
				'label' => __( 'From Name', 'ultimakit-for-wp' ),
				'value' => '<div class="ultimakit-role-manager">
								<div><h6>' . __( 'Turn on the toggle to hide admin notices based on user roles.', 'ultimakit-for-wp' ) . '</h6></div>
							  <div class="header">
							    <div>' . __( 'Role', 'ultimakit-for-wp' ) . '</div>
							    <div>' . __( 'Plugin', 'ultimakit-for-wp' ) . '</div>
							    <div>' . __( 'Theme', 'ultimakit-for-wp' ) . '</div>
							    <div>' . __( 'Core', 'ultimakit-for-wp' ) . '</div>
							    <div>' . __( 'All', 'ultimakit-for-wp' ) . '</div>
							  </div>
							  <div class="roles">
							      ' . $roles_html . '
							    <!-- Repeat for Author, Contributor, Subscriber -->
							  </div>
							</div>',
			),
			'ultimakit_hide_notices_for' => array(
				'type'  => 'hidden',
				'value' => stripslashes( $this->getModuleSettings( $this->ID, 'ultimakit_hide_notices_for' ) ),
			),
		);

		$this->ultimakit_generate_modal( $arguments, 'modal-lg' );
	}

	/**
	 * Enqueues scripts for the theme or plugin.
	 *
	 * This function handles the registration and enqueuing of JavaScript files required
	 * by the theme or plugin. It ensures that scripts are loaded in the correct order and
	 * that dependencies are managed properly. Scripts can include both local and external
	 * resources, and may be conditionally loaded based on the context or user actions.
	 *
	 * Use this function to enqueue all JavaScript necessary for the functionality of your
	 * theme or plugin, adhering to WordPress best practices for script registration and
	 * enqueuing.
	 *
	 * @return void
	 */
	public function add_scripts() {

		wp_enqueue_style(
			'ultimakit-module-style-' . $this->ID,
			plugins_url( '/module-style.css', __FILE__ ),
			array(),
			filemtime( plugin_dir_path( __FILE__ ) . 'module-style.css' )
		);

		wp_enqueue_script(
			'ultimakit-module-script-' . $this->ID,
			plugins_url( '/module-script.js', __FILE__ ),
			array( 'jquery' ),
			ULTIMAKIT_FOR_WP_VERSION,
			true
		);

		wp_localize_script(
			'ultimakit-module-script-' . $this->ID,
			'ultimakit_smtp_email',
			array(
				'ajax_url'   => admin_url( 'admin-ajax.php' ),
				'ajax_nonce' => wp_create_nonce( 'ultimakit-smtp-email' ),
			)
		);
	}


	public function notices_start() {
		ob_start();
	}

	public function notices_end() {
		$notices = ob_get_clean(); // Get the output buffer contents and clean it
		wp_add_inline_script( 'ultimakit-notices', 'const ultimakit_notices_html = ' . json_encode( $notices ), 'before' ); // Add notices HTML as inline script
		echo '<a id="ultimakit-notices-placeholder"></a>'; // Output a placeholder anchor element
	}

	public function remove_user_notices() {
		$existing_options = $this->getModuleSettings( $this->ID, 'ultimakit_hide_notices_for' );
		if ( ! empty( $existing_options ) ) {
			$options           = json_decode( $existing_options, true );
			$user              = wp_get_current_user();
			$role              = $user->roles[0];
			$all_enable_notice = $options[ $role ] ?? array();

			foreach ( $all_enable_notice as $enable_notice ) {
				if ( ! empty( $enable_notice ) ) {
					do_action( $enable_notice );
				}
			}
		}
	}


	public function remove_core_check() {
		global $wp_version;
		return (object) array(
			'last_checked'    => time(),
			'version_checked' => $wp_version,
		);
	}

	public function plugin_check() {
		remove_action( 'load-update-core.php', 'wp_update_plugins' );
		add_filter( 'pre_site_transient_update_plugins', '__return_null' );
		add_filter( 'pre_site_transient_update_plugins', array( $this, 'remove_core_check' ) );
	}

	public function theme_check() {

		remove_action( 'load-update-core.php', 'wp_update_themes' );
		add_filter( 'pre_site_transient_update_themes', '__return_null' );
		add_filter( 'pre_site_transient_update_themes', array( $this, 'remove_core_check' ) );
	}

	public function core_check() {
		if ( ! current_user_can( 'update_core' ) ) {
			return;
		}
		add_filter( 'pre_site_transient_update_core', array( $this, 'remove_core_check' ) );
		add_filter( 'pre_option_update_core', '__return_null' );
	}

	public function all_check() {
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_notices_menu_inline_css' ) );
	}

	public function admin_notices_menu_inline_css() {
		?>
		<style type="text/css">
			#wpbody-content .notice:not(.system-notice,.update-message),
			#wpbody-content .notice-error,
			#wpbody-content .error,
			#wpbody-content .notice-info,
			#wpbody-content .notice-information,
			#wpbody-content #message,
			#wpbody-content .notice-warning:not(.update-message),
			#wpbody-content .notice-success:not(.update-message),
			#wpbody-content .notice-updated,
			#wpbody-content .updated:not(.active, .inactive, .plugin-update-tr),
			#wpbody-content .update-nag,
			#wpbody-content > .wrap > .notice:not(.system-notice,.hidden),
			#wpbody-content > .wrap > .notice-error,
			#wpbody-content > .wrap > .error:not(.hidden),
			#wpbody-content > .wrap > .notice-info,
			#wpbody-content > .wrap > .notice-information,
			#wpbody-content > .wrap > #message,
			#wpbody-content > .wrap > .notice-warning:not(.hidden),
			#wpbody-content > .wrap > .notice-success,
			#wpbody-content > .wrap > .notice-updated,
			#wpbody-content > .wrap > .updated,
			#wpbody-content > .wrap > .update-nag,
			#wpbody-content > .wrap > div > .notice:not(.system-notice,.hidden),
			#wpbody-content > .wrap > div > .notice-error,
			#wpbody-content > .wrap > div > .error:not(.hidden),
			#wpbody-content > .wrap > div > .notice-info,
			#wpbody-content > .wrap > div > .notice-information,
			#wpbody-content > .wrap > div > #message,
			#wpbody-content > .wrap > div > .notice-warning:not(.hidden),
			#wpbody-content > .wrap > div > .notice-success,
			#wpbody-content > .wrap > div > .notice-updated,
			#wpbody-content > .wrap > div > .updated,
			#wpbody-content > .wrap > div > .update-nag,
			#wpbody-content > div > .wrap > .notice:not(.system-notice,.hidden),
			#wpbody-content > div > .wrap > .notice-error,
			#wpbody-content > div > .wrap > .error:not(.hidden),
			#wpbody-content > div > .wrap > .notice-info,
			#wpbody-content > div > .wrap > .notice-information,
			#wpbody-content > div > .wrap > #message,
			#wpbody-content > div > .wrap > .notice-warning:not(.hidden),
			#wpbody-content > div > .wrap > .notice-success,
			#wpbody-content > div > .wrap > .notice-updated,
			#wpbody-content > div > .wrap > .updated,
			#wpbody-content > div > .wrap > .update-nag,
			#wpbody-content > .notice,
			#wpbody-content > .error,
			#wpbody-content > .updated,
			#wpbody-content > .update-nag,
			#wpbody-content > .jp-connection-banner,
			#wpbody-content > .jitm-banner,
			#wpbody-content > .jetpack-jitm-message,
			#wpbody-content > .ngg_admin_notice,
			#wpbody-content > .imagify-welcome,
			#wpbody-content #wordfenceAutoUpdateChoice,
			#wpbody-content #easy-updates-manager-dashnotice {
				display: none !important;
			}
		</style>
		<?php
	}
}
