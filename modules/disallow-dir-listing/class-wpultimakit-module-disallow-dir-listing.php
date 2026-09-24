<?php
/**
 * Class UltimaKit_Module_Disallow_Dir_Listing
 *
 * @since 1.0.0
 * @package    UltimaKit
 */

/**
 * Class UltimaKit_Module_Disallow_Dir_Listing
 *
 * This class provides methods to disable directory listing for better security.
 * It prevents users from browsing directory contents by adding appropriate
 * security headers and .htaccess rules.
 *
 * @since 1.0.0
 */
class UltimaKit_Module_Disallow_Dir_Listing extends UltimaKit_Module_Manager {
	/**
	 * Unique identifier for the Disallow Dir Listing module.
	 *
	 * @var string
	 */
	protected $ID = 'ultimakit_disallow_dir_listing';

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
	protected $read_more_link = 'disallow-directory-listing-in-wordpress';

	/**
	 * The settings associated with the module, if any.
	 *
	 * @var array
	 */
	protected $settings;

	/**
	 * Constructs the Disallow Dir Listing module instance.
	 *
	 * Initializes the module with default values for properties and prepares
	 * any necessary setup or hooks into WordPress. This may include setting
	 * initial values, registering hooks, or preparing resources needed for
	 * the module to function properly within WordPress.
	 */
	public function __construct() {
		$this->name        = __( 'Disallow Dir Listing', 'ultimakit-for-wp' );
		$this->description = __( 'Disable directory listing for better security.', 'ultimakit-for-wp' );
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
		if ( $this->is_active ) {
			// Add security headers to prevent directory listing
			add_action( 'send_headers', array( $this, 'add_security_headers' ) );
			
			// Add .htaccess rules for Apache servers
			add_action( 'init', array( $this, 'maybe_add_htaccess_rules' ) );
			
			// Add index.php files to directories that don't have them
			add_action( 'init', array( $this, 'maybe_add_index_files' ) );
			
			// Block direct access to sensitive directories
			add_action( 'init', array( $this, 'block_sensitive_directories' ) );
		}
	}

	/**
	 * Adds security headers to prevent directory listing.
	 *
	 * @return void
	 */
	public function add_security_headers() {
		// Prevent directory listing via HTTP headers
		header( 'X-Content-Type-Options: nosniff' );
		header( 'X-Frame-Options: SAMEORIGIN' );
		header( 'X-XSS-Protection: 1; mode=block' );
		
		// Add additional security headers
		if ( ! headers_sent() ) {
			header( 'Referrer-Policy: strict-origin-when-cross-origin' );
		}
	}

	/**
	 * Adds .htaccess rules to prevent directory listing on Apache servers.
	 *
	 * @return void
	 */
	public function maybe_add_htaccess_rules() {
		// Only run this once per day to avoid performance issues
		$last_check = get_option( 'ultimakit_dir_listing_htaccess_check', 0 );
		if ( time() - $last_check < DAY_IN_SECONDS ) {
			return;
		}

		// Check if we're on an Apache server
		if ( ! $this->is_apache_server() ) {
			update_option( 'ultimakit_dir_listing_htaccess_check', time() );
			return;
		}

		$htaccess_file = ABSPATH . '.htaccess';
		$htaccess_content = '';

		// Read existing .htaccess file
		if ( file_exists( $htaccess_file ) ) {
			$htaccess_content = file_get_contents( $htaccess_file );
		}

		// Check if our rules are already present
		if ( strpos( $htaccess_content, '# BEGIN UltimaKit Directory Listing Protection' ) !== false ) {
			update_option( 'ultimakit_dir_listing_htaccess_check', time() );
			return;
		}

		// Prepare the rules to add
		$rules = "\n# BEGIN UltimaKit Directory Listing Protection\n";
		$rules .= "<IfModule mod_autoindex.c>\n";
		$rules .= "    Options -Indexes\n";
		$rules .= "</IfModule>\n";
		$rules .= "<IfModule mod_rewrite.c>\n";
		$rules .= "    RewriteEngine On\n";
		$rules .= "    # Block access to sensitive files\n";
		$rules .= "    RewriteRule ^(wp-config\.php|readme\.html|license\.txt|wp-admin/install\.php) - [F,L]\n";
		$rules .= "    # Block access to .htaccess and .htpasswd\n";
		$rules .= "    RewriteRule ^\.htaccess$ - [F,L]\n";
		$rules .= "    RewriteRule ^\.htpasswd$ - [F,L]\n";
		$rules .= "    # Block access to backup files\n";
		$rules .= "    RewriteRule \.(bak|backup|old|orig|save|swp|sql)$ - [F,L]\n";
		$rules .= "    # Block access to log files\n";
		$rules .= "    RewriteRule \.(log|txt)$ - [F,L]\n";
		$rules .= "</IfModule>\n";
		$rules .= "# END UltimaKit Directory Listing Protection\n";

		// Add rules to .htaccess file
		if ( is_writable( $htaccess_file ) || ( ! file_exists( $htaccess_file ) && is_writable( ABSPATH ) ) ) {
			$new_content = $htaccess_content . $rules;
			file_put_contents( $htaccess_file, $new_content );
		}

		update_option( 'ultimakit_dir_listing_htaccess_check', time() );
	}

	/**
	 * Adds index.php files to directories that don't have them.
	 *
	 * @return void
	 */
	public function maybe_add_index_files() {
		// Only run this once per day
		$last_check = get_option( 'ultimakit_dir_listing_index_check', 0 );
		if ( time() - $last_check < DAY_IN_SECONDS ) {
			return;
		}

		$directories_to_protect = array(
			ABSPATH . 'wp-content/uploads/',
			ABSPATH . 'wp-content/plugins/',
			ABSPATH . 'wp-content/themes/',
			ABSPATH . 'wp-includes/',
		);

		foreach ( $directories_to_protect as $directory ) {
			if ( is_dir( $directory ) && ! file_exists( $directory . 'index.php' ) ) {
				$this->create_index_file( $directory );
			}
		}

		update_option( 'ultimakit_dir_listing_index_check', time() );
	}

	/**
	 * Creates an index.php file in the specified directory.
	 *
	 * @param string $directory The directory path.
	 * @return bool True if successful, false otherwise.
	 */
	private function create_index_file( $directory ) {
		$index_content = "<?php\n// Silence is golden.\n";
		$index_file = $directory . 'index.php';

		// Check if directory is writable
		if ( ! is_writable( $directory ) ) {
			return false;
		}

		// Create the index.php file
		return file_put_contents( $index_file, $index_content ) !== false;
	}

	/**
	 * Blocks direct access to sensitive directories via PHP.
	 *
	 * @return void
	 */
	public function block_sensitive_directories() {
		// Get the current request URI
		$request_uri = $_SERVER['REQUEST_URI'] ?? '';
		
		// Define sensitive patterns to block
		$sensitive_patterns = array(
			'/wp-config.php',
			'/wp-config-sample.php',
			'/readme.html',
			'/license.txt',
			'/wp-admin/install.php',
			'/.htaccess',
			'/.htpasswd',
			'/wp-content/uploads/',
			'/wp-content/plugins/',
			'/wp-content/themes/',
			'/wp-includes/',
		);

		// Check if current request matches any sensitive pattern
		foreach ( $sensitive_patterns as $pattern ) {
			if ( strpos( $request_uri, $pattern ) !== false ) {
				// Check if it's a directory listing request (no file extension)
				if ( $this->is_directory_listing_request( $request_uri ) ) {
					$this->block_access();
				}
			}
		}
	}

	/**
	 * Checks if the current request is for directory listing.
	 *
	 * @param string $request_uri The request URI.
	 * @return bool True if it's a directory listing request.
	 */
	private function is_directory_listing_request( $request_uri ) {
		// Remove query string
		$path = parse_url( $request_uri, PHP_URL_PATH );
		
		// Check if the path ends with a slash (directory request)
		if ( substr( $path, -1 ) === '/' ) {
			return true;
		}
		
		// Check if no file extension is present
		$extension = pathinfo( $path, PATHINFO_EXTENSION );
		if ( empty( $extension ) ) {
			return true;
		}
		
		return false;
	}

	/**
	 * Blocks access to the current request.
	 *
	 * @return void
	 */
	private function block_access() {
		// Send 403 Forbidden response
		http_response_code( 403 );
		
		// Display a simple error message
		echo '<!DOCTYPE html>';
		echo '<html><head><title>403 Forbidden</title></head>';
		echo '<body><h1>403 Forbidden</h1>';
		echo '<p>Access to this resource is forbidden.</p>';
		echo '</body></html>';
		
		exit;
	}

	/**
	 * Checks if the server is running Apache.
	 *
	 * @return bool True if Apache server.
	 */
	private function is_apache_server() {
		$server_software = $_SERVER['SERVER_SOFTWARE'] ?? '';
		return stripos( $server_software, 'apache' ) !== false;
	}
} 