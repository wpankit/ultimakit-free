<?php
/**
 * Class UltimaKit_Module_Force_SSL
 *
 * @since 1.8.5
 * @package    UltimaKit
 */

/**
 * Class UltimaKit_Module_Force_SSL
 *
 * This class provides methods to enforce HTTPS/SSL across the WordPress site.
 * It redirects all HTTP traffic to HTTPS, updates internal links, and ensures
 * secure connections for better security and SEO.
 *
 * @since 1.8.5
 */
class UltimaKit_Module_Force_SSL extends UltimaKit_Module_Manager {
	/**
	 * @var string
	 */
	protected $ID = 'ultimakit_module_force_ssl';

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
	protected $read_more_link = 'force-ssl-https-wordpress';

	/**
	 * The settings associated with the module, if any.
	 *
	 * @var array
	 */
	protected $settings;

	/**
	 * Constructs the Force SSL module instance.
	 *
	 * Initializes the module with default values for properties and prepares
	 * any necessary setup or hooks into WordPress. This may include setting
	 * initial values, registering hooks, or preparing resources needed for
	 * the module to function properly within WordPress.
	 */
	public function __construct() {
		$this->name        = __( 'Force SSL', 'ultimakit-for-wp' );
		$this->description = __( 'Enforce HTTPS across the site.', 'ultimakit-for-wp' );
		$this->is_active   = $this->isModuleActive( $this->ID );
		$this->settings    = 'no';
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
		if ( $this->is_active && ! $this->is_localhost() ) {
			// Force HTTPS redirect
			add_action( 'template_redirect', array( $this, 'force_ssl_redirect' ) );
			
			// Update WordPress site URL to HTTPS
			add_filter( 'home_url', array( $this, 'force_ssl_url' ), 10, 2 );
			add_filter( 'site_url', array( $this, 'force_ssl_url' ), 10, 2 );
			
			// Update content URLs to HTTPS
			add_filter( 'content_url', array( $this, 'force_ssl_url' ), 10, 2 );
			add_filter( 'plugins_url', array( $this, 'force_ssl_url' ), 10, 2 );
			add_filter( 'upload_dir', array( $this, 'force_ssl_upload_dir' ) );
			
			// Force HTTPS for admin area
			add_action( 'admin_init', array( $this, 'force_ssl_admin' ) );
			
			// Update canonical URLs
			add_filter( 'wp_get_canonical_url', array( $this, 'force_ssl_canonical_url' ), 10, 2 );
			
			// Force HTTPS for login and registration
			add_action( 'login_init', array( $this, 'force_ssl_login' ) );
			
			// Update REST API URLs
			add_filter( 'rest_url', array( $this, 'force_ssl_rest_url' ) );
			
			// Force HTTPS for XML-RPC
			add_filter( 'xmlrpc_url', array( $this, 'force_ssl_url' ), 10, 2 );
			
			// Update theme and stylesheet URLs
			add_filter( 'theme_root_uri', array( $this, 'force_ssl_url' ), 10, 2 );
			add_filter( 'stylesheet_uri', array( $this, 'force_ssl_url' ), 10, 2 );
			add_filter( 'template_directory_uri', array( $this, 'force_ssl_url' ), 10, 2 );
			
			// Force HTTPS for AJAX requests
			add_filter( 'admin_url', array( $this, 'force_ssl_admin_url' ), 10, 2 );
			
			// Update pingback URLs
			add_filter( 'pingback_url', array( $this, 'force_ssl_url' ), 10, 2 );
			
			// Force HTTPS for comment form action
			add_filter( 'comment_form_defaults', array( $this, 'force_ssl_comment_form' ) );
			
			// Update feed URLs
			add_filter( 'feed_link', array( $this, 'force_ssl_url' ), 10, 2 );
			add_filter( 'get_feed_link', array( $this, 'force_ssl_url' ), 10, 2 );
			
			// Force HTTPS for wp-login.php
			add_action( 'init', array( $this, 'force_ssl_login_redirect' ) );
		}
	}

	/**
	 * Forces SSL redirect for all HTTP requests.
	 *
	 * @return void
	 */
	public function force_ssl_redirect() {
		// Skip redirect for CLI requests
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			return;
		}

		// Skip redirect for AJAX requests to prevent issues
		if ( wp_doing_ajax() ) {
			return;
		}

		// Skip redirect for REST API requests to prevent issues
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return;
		}

		// Check if we're not already on HTTPS
		if ( ! is_ssl() ) {
			// Get the current URL
			$current_url = ( isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http' ) . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
			
			// Convert to HTTPS
			$https_url = str_replace( 'http://', 'https://', $current_url );
			
			// Perform 301 redirect to HTTPS
			wp_redirect( $https_url, 301 );
			exit;
		}
	}

	/**
	 * Forces SSL for WordPress URLs.
	 *
	 * @param string $url The URL to modify.
	 * @param string $path The path to append to the URL.
	 * @return string The modified URL.
	 */
	public function force_ssl_url( $url, $path = '' ) {
		// Skip if already HTTPS
		if ( strpos( $url, 'https://' ) === 0 ) {
			return $url;
		}

		// Convert HTTP to HTTPS
		return str_replace( 'http://', 'https://', $url );
	}

	/**
	 * Forces SSL for upload directory URLs.
	 *
	 * @param array $uploads The upload directory array.
	 * @return array The modified upload directory array.
	 */
	public function force_ssl_upload_dir( $uploads ) {
		if ( isset( $uploads['url'] ) ) {
			$uploads['url'] = str_replace( 'http://', 'https://', $uploads['url'] );
		}
		if ( isset( $uploads['baseurl'] ) ) {
			$uploads['baseurl'] = str_replace( 'http://', 'https://', $uploads['baseurl'] );
		}
		return $uploads;
	}

	/**
	 * Forces SSL for admin area.
	 *
	 * @return void
	 */
	public function force_ssl_admin() {
		if ( ! is_ssl() && is_admin() ) {
			wp_redirect( 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], 301 );
			exit;
		}
	}

	/**
	 * Forces SSL for canonical URLs.
	 *
	 * @param string $canonical_url The canonical URL.
	 * @param WP_Post $post The post object.
	 * @return string The modified canonical URL.
	 */
	public function force_ssl_canonical_url( $canonical_url, $post ) {
		return str_replace( 'http://', 'https://', $canonical_url );
	}

	/**
	 * Forces SSL for login pages.
	 *
	 * @return void
	 */
	public function force_ssl_login() {
		if ( ! is_ssl() ) {
			wp_redirect( 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], 301 );
			exit;
		}
	}

	/**
	 * Forces SSL for REST API URLs.
	 *
	 * @param string $url The REST API URL.
	 * @return string The modified REST API URL.
	 */
	public function force_ssl_rest_url( $url ) {
		return str_replace( 'http://', 'https://', $url );
	}

	/**
	 * Forces SSL for admin URLs.
	 *
	 * @param string $url The admin URL.
	 * @param string $path The path to append.
	 * @return string The modified admin URL.
	 */
	public function force_ssl_admin_url( $url, $path = '' ) {
		return str_replace( 'http://', 'https://', $url );
	}

	/**
	 * Forces SSL for comment form action.
	 *
	 * @param array $defaults The comment form defaults.
	 * @return array The modified comment form defaults.
	 */
	public function force_ssl_comment_form( $defaults ) {
		if ( isset( $defaults['action'] ) ) {
			$defaults['action'] = str_replace( 'http://', 'https://', $defaults['action'] );
		}
		return $defaults;
	}

	/**
	 * Forces SSL redirect for wp-login.php.
	 *
	 * @return void
	 */
	public function force_ssl_login_redirect() {
		// Check if we're on the login page and not using HTTPS
		if ( strpos( $_SERVER['REQUEST_URI'], 'wp-login.php' ) !== false && ! is_ssl() ) {
			wp_redirect( 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], 301 );
			exit;
		}
	}

	/**
	 * Checks if the current environment is localhost.
	 *
	 * @return bool True if running on localhost, false otherwise.
	 */
	private function is_localhost() {
		// Check for WordPress localhost constant
		if ( defined( 'WP_LOCAL_DEV' ) && WP_LOCAL_DEV ) {
			return true;
		}

		// Check for common localhost hostnames
		$localhost_hosts = array(
			'localhost',
			'127.0.0.1',
			'::1',
			'local',
			'dev',
			'development',
			'test',
			'staging'
		);

		$current_host = isset( $_SERVER['HTTP_HOST'] ) ? $_SERVER['HTTP_HOST'] : '';
		$current_host = strtolower( $current_host );

		// Remove port number if present
		$current_host = preg_replace( '/:\d+$/', '', $current_host );

		// Check if current host matches localhost patterns
		foreach ( $localhost_hosts as $localhost_host ) {
			if ( $current_host === $localhost_host || strpos( $current_host, $localhost_host ) !== false ) {
				return true;
			}
		}

		// Check for local IP addresses
		if ( preg_match( '/^192\.168\./', $current_host ) || 
			 preg_match( '/^10\./', $current_host ) || 
			 preg_match( '/^172\.(1[6-9]|2[0-9]|3[0-1])\./', $current_host ) ) {
			return true;
		}

		// Check for .local, .test, .dev TLDs
		if ( preg_match( '/\.(local|test|dev)$/', $current_host ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Checks if the site is accessible via HTTPS.
	 *
	 * @return bool True if HTTPS is accessible, false otherwise.
	 */
	private function is_https_accessible() {
		$site_url = get_site_url();
		$https_url = str_replace( 'http://', 'https://', $site_url );
		
		// Test HTTPS accessibility
		$response = wp_remote_get( $https_url, array(
			'timeout' => 5,
			'sslverify' => false
		) );
		
		return ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) === 200;
	}

	/**
	 * Adds security headers for HTTPS.
	 *
	 * @return void
	 */
	public function add_security_headers() {
		if ( is_ssl() ) {
			// HSTS header (HTTP Strict Transport Security)
			header( 'Strict-Transport-Security: max-age=31536000; includeSubDomains; preload' );
			
			// Content Security Policy header
			header( 'Content-Security-Policy: upgrade-insecure-requests' );
			
			// X-Content-Type-Options header
			header( 'X-Content-Type-Options: nosniff' );
			
			// X-Frame-Options header
			header( 'X-Frame-Options: SAMEORIGIN' );
			
			// X-XSS-Protection header
			header( 'X-XSS-Protection: 1; mode=block' );
		}
	}
} 