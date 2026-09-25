<?php
/**
 * Class UltimaKit_Module_Change_Login_Url
 *
 * @since 1.0.0
 * @package    UltimaKit
 */

/**
 * Class UltimaKit_Module_Change_Login_Url
 *
 * This class provides methods to change the default WordPress login URL
 * to a custom one for enhanced security.
 *
 * @since 1.0.0
 */
class UltimaKit_Module_Change_Login_Url extends UltimaKit_Module_Manager {
	/**
	 * @var string
	 */
	protected $ID = 'ultimakit_change_login_url';

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
	protected $category = 'Security';

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
	protected $read_more_link = 'change-wordpress-login-url';

	/**
	 * The settings associated with the module, if any.
	 *
	 * @var array
	 */
	protected $settings;

	/**
	 * Initializes the module with default values for properties and prepares
	 * any necessary setup or hooks into WordPress. This may include setting
	 * initial values, registering hooks, or preparing resources needed for
	 * the module to function properly within WordPress.
	 */
	public function __construct() {
		$this->name        = __( 'Change Login URL', 'ultimakit-for-wp' );
		$this->description = __( 'Change the default login URL to a custom one.', 'ultimakit-for-wp' );
		$this->is_active   = $this->isModuleActive( $this->ID );
		$this->settings    = 'yes';
		$this->initializeModule();
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
			add_action( 'template_redirect', array( $this, 'handle_custom_login_url' ) );
			add_filter( 'login_url', array( $this, 'modify_login_url' ), 10, 3 );
			add_filter( 'site_url', array( $this, 'modify_site_url' ), 10, 4 );
			add_filter( 'wp_redirect', array( $this, 'modify_redirect_url' ), 10, 2 );
		}

		add_action( 'init', array( $this, 'block_default_login_url' ), 1 );
		add_action( 'wp', array( $this, 'block_default_login_url' ), 1 );
	}

	/**
	 * Activate the module
	 */
	public function activate() {
		// No need to flush rewrite rules with this approach
	}

	/**
	 * Deactivate the module
	 */
	public function deactivate() {
		// No need to flush rewrite rules with this approach
	}

	/**
	 * Block access to default login URL if custom URL is enabled
	 */
	public function block_default_login_url() {
		$enabled = $this->getModuleSettings( $this->ID, 'enable_custom_login_url' );
		if ( 'on' !== $enabled ) {
			return;
		}

		$custom_slug = $this->getModuleSettings( $this->ID, 'custom_login_slug' );
		if ( empty( $custom_slug ) ) {
			return;
		}

		// Check if we're accessing wp-login.php directly
		$current_file = basename( $_SERVER['SCRIPT_NAME'] );
		$request_uri  = $_SERVER['REQUEST_URI'];
		$php_self     = $_SERVER['PHP_SELF'];

		// Also check if we're on the login page through WordPress's internal detection
		$is_login_page = false;

		// Check various ways WordPress might indicate we're on the login page
		if ( $current_file === 'wp-login.php' ||
			strpos( $request_uri, '/wp-login.php' ) !== false ||
			strpos( $php_self, '/wp-login.php' ) !== false ||
			( function_exists( 'is_admin' ) && ! is_admin() && strpos( $request_uri, 'wp-login' ) !== false ) ) {
			$is_login_page = true;
		}

		// Block access to default login URL if custom URL is set
		if ( $is_login_page ) {

			// Don't redirect if this is an AJAX request
			if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
				return;
			}

			// Don't redirect if this is a cron job
			if ( defined( 'DOING_CRON' ) && DOING_CRON ) {
				return;
			}

			// Don't redirect if this is an XML-RPC request
			if ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) {
				return;
			}

			// Don't redirect if this is a REST API request
			if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
				return;
			}

			// Redirect to homepage
			wp_redirect( home_url( '/' ) );
			exit;
		}
	}

	/**
	 * Handle custom login URL requests
	 */
	public function handle_custom_login_url() {
		$enabled = $this->getModuleSettings( $this->ID, 'enable_custom_login_url' );
		if ( 'on' !== $enabled ) {
			return;
		}

		$custom_slug = $this->getModuleSettings( $this->ID, 'custom_login_slug' );
		if ( empty( $custom_slug ) ) {
			return;
		}

		/*
		 * Match the path only. WordPress adds a query string to almost every login-screen
		 * link (redirect_to after an /wp-admin/ visit, action=logout, action=lostpassword,
		 * action=rp from reset emails, action=postpass for protected posts), and comparing
		 * the whole REQUEST_URI sent all of those to a 404. The home path keeps it working
		 * when WordPress lives in a subdirectory.
		 */
		$request_path      = (string) wp_parse_url( isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '', PHP_URL_PATH );
		$home_path         = (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH );
		$custom_login_path = trailingslashit( $home_path ) . trim( $custom_slug, '/' );

		// Check if the request is for our custom login URL
		if ( untrailingslashit( $request_path ) === untrailingslashit( $custom_login_path ) ) {
			// WordPress has already flagged this unknown path as a 404; the login screen is not one.
			status_header( 200 );

			// Set up the login page properly
			global $error, $user_login, $redirect_to;

			// Initialize variables that wp-login.php expects
			$error       = '';
			$user_login  = '';
			$redirect_to = '';

			// Get any query parameters
			if ( isset( $_GET['redirect_to'] ) ) {
				$redirect_to = $_GET['redirect_to'];
			}
			if ( isset( $_GET['user_login'] ) ) {
				$user_login = $_GET['user_login'];
			}

			// Load the WordPress login page
			if ( ! defined( 'WP_USE_THEMES' ) ) {
				define( 'WP_USE_THEMES', false );
			}
			require_once ABSPATH . 'wp-login.php';
			exit;
		}
	}

	/**
	 * Modify login URL to use custom URL
	 *
	 * @param string $login_url Login URL.
	 * @param string $redirect Redirect URL.
	 * @param bool $force_reauth Force re-authentication.
	 * @return string Modified login URL
	 */
	public function modify_login_url( $login_url, $redirect, $force_reauth ) {
		$enabled = $this->getModuleSettings( $this->ID, 'enable_custom_login_url' );
		if ( 'on' !== $enabled ) {
			return $login_url;
		}

		$custom_slug = $this->getModuleSettings( $this->ID, 'custom_login_slug' );
		if ( empty( $custom_slug ) ) {
			return $login_url;
		}

		// Build the custom login URL
		$custom_login_url = home_url( '/' . $custom_slug . '/' );

		// Add redirect parameter if provided
		if ( ! empty( $redirect ) ) {
			$custom_login_url = add_query_arg( 'redirect_to', urlencode( $redirect ), $custom_login_url );
		}

		// Add force re-authentication parameter if needed
		if ( $force_reauth ) {
			$custom_login_url = add_query_arg( 'reauth', '1', $custom_login_url );
		}

		return $custom_login_url;
	}

	/**
	 * Modify site_url calls that reference wp-login.php to use the custom URL.
	 * This fixes the login form's action attribute which uses site_url(), not wp_login_url().
	 *
	 * @param string $url The complete site URL.
	 * @param string $path Path relative to the site URL.
	 * @param string $scheme URL scheme.
	 * @param int    $blog_id Blog ID.
	 * @return string Modified URL
	 */
	public function modify_site_url( $url, $path, $scheme, $blog_id ) {
		if ( strpos( $url, 'wp-login.php' ) === false ) {
			return $url;
		}

		$enabled = $this->getModuleSettings( $this->ID, 'enable_custom_login_url' );
		if ( 'on' !== $enabled ) {
			return $url;
		}

		$custom_slug = $this->getModuleSettings( $this->ID, 'custom_login_slug' );
		if ( empty( $custom_slug ) ) {
			return $url;
		}

		return str_replace( 'wp-login.php', trailingslashit( $custom_slug ), $url );
	}

	/**
	 * Modify redirect URLs to use custom login URL
	 *
	 * @param string $location The path to redirect to.
	 * @param int $status Status code to use.
	 * @return string Modified redirect URL
	 */
	public function modify_redirect_url( $location, $status ) {
		$enabled = $this->getModuleSettings( $this->ID, 'enable_custom_login_url' );
		if ( 'on' !== $enabled ) {
			return $location;
		}

		$custom_slug = $this->getModuleSettings( $this->ID, 'custom_login_slug' );
		if ( empty( $custom_slug ) ) {
			return $location;
		}

		// Replace default login URL with custom URL in redirects
		$default_login_url = wp_login_url();
		$custom_login_url  = home_url( '/' . $custom_slug . '/' );

		return str_replace( $default_login_url, $custom_login_url, $location );
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
		$arguments['title'] = __( 'Change Login URL Settings', 'ultimakit-for-wp' );

		$arguments['fields'] = array(
			'enable_custom_login_url' => array(
				'type'  => 'switch',
				'label' => __( 'Enable custom login URL', 'ultimakit-for-wp' ),
				'value' => $this->getModuleSettings( $this->ID, 'enable_custom_login_url' ),
				'desc'  => __( 'Enable this to change your default login URL.', 'ultimakit-for-wp' ),
			),
			'custom_login_slug'       => array(
				'type'        => 'text',
				'label'       => __( 'Custom Login URL Slug', 'ultimakit-for-wp' ),
				'value'       => $this->getModuleSettings( $this->ID, 'custom_login_slug' ),
				'placeholder' => 'my-login',
				'desc'        => __( 'Enter a custom slug for your login URL. This will change your login URL from /wp-login.php to /your-slug/', 'ultimakit-for-wp' ),
			),
		);

		$this->ultimakit_generate_modal( $arguments );
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
		wp_enqueue_script(
			'ultimakit-module-script-' . $this->ID,
			plugins_url( '/module-script.js', __FILE__ ),
			array( 'jquery' ),
			ULTIMAKIT_FOR_WP_VERSION,
			true
		);

		wp_localize_script(
			'ultimakit-module-script-' . $this->ID,
			'ultimakit_change_login_url',
			array(
				'ajax_url'   => admin_url( 'admin-ajax.php' ),
				'ajax_nonce' => wp_create_nonce( 'ultimakit_nonce' ),
			)
		);
	}
}
