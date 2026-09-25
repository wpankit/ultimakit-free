<?php
/**
 * Class UltimaKit_Module_Password_Protection
 *
 * @since 1.0.0
 * @package    UltimaKit
 */

/**
 * Class UltimaKit_Module_Password_Protection
 *
 * This class provides methods to control the display of dashboard widgets based on user preferences.
 * It allows users to selectively hide certain dashboard widgets to streamline their WordPress dashboard
 * experience and improve usability.
 *
 * @since 1.0.0
 */
class UltimaKit_Module_Password_Protection extends UltimaKit_Module_Manager {
	/**
	 *
	 * @var string
	 */
	protected $ID = 'ultimakit_module_password_protection';

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
	protected $read_more_link = 'password-protection-in-wordpress';

	/**
	 * The settings associated with the module, if any.
	 *
	 * @var array
	 */
	protected $settings;

	protected $session_name = 'ultimakit_site_password_verified';

	/**
	 * Constructs the Enhance List Table module instance.
	 *
	 * Initializes the module with default values for properties and prepares
	 * any necessary setup or hooks into WordPress. This may include setting
	 * initial values, registering hooks, or preparing resources needed for
	 * the module to function properly within WordPress.
	 */
	public function __construct() {
		$this->name        = __( 'Password Protection', 'ultimakit-for-wp' );
		$this->description = __( 'Protect your website with a password.', 'ultimakit-for-wp' );
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

			// Initialize the protection
			add_action( 'init', array( $this, 'initialize_protection' ) );

			// Add admin bar information
			add_action( 'admin_bar_menu', array( $this, 'add_admin_bar_info' ), 100 );

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
		$arguments['title'] = __( 'Password Protection Settings', 'ultimakit-for-wp' );

		$arguments['fields'] = array(
			'enable_protection'  => array(
				'type'  => 'checkbox',
				'label' => __( 'Enable password protection for the entire website', 'ultimakit-for-wp' ),
				'value' => $this->getModuleSettings( $this->ID, 'enable_protection' ),
			),
			'password'           => array(
				'type'  => 'text',
				'label' => __( 'Password', 'ultimakit-for-wp' ),
				'value' => $this->getModuleSettings( $this->ID, 'password' ),
			),
			'whitelist_ips'      => array(
				'type'        => 'textarea',
				'label'       => __( 'Whitelist IPs', 'ultimakit-for-wp' ),
				'value'       => $this->getModuleSettings( $this->ID, 'whitelist_ips' ),
				'description' => __( 'Enter IP addresses separated by commas', 'ultimakit-for-wp' ),
			),
			'excluded_urls'      => array(
				'type'        => 'textarea',
				'label'       => __( 'Excluded URLs', 'ultimakit-for-wp' ),
				'value'       => $this->getModuleSettings( $this->ID, 'excluded_urls', 'wp-login.php,/wp-admin/' ),
				'description' => __( 'Enter URLs to exclude from protection, separated by commas', 'ultimakit-for-wp' ),
			),
			'protection_message' => array(
				'type'  => 'textarea',
				'label' => __( 'Protection Message', 'ultimakit-for-wp' ),
				'value' => $this->getModuleSettings( $this->ID, 'protection_message', __( 'This site is password protected. Please enter the password to access the site.', 'ultimakit-for-wp' ) ),
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

		wp_enqueue_style(
			'ultimakit-module-style-' . $this->ID,
			plugins_url( '/module-style.css', __FILE__ ),
			array(),
			filemtime( plugin_dir_path( __FILE__ ) . 'module-style.css' )
		);

		wp_enqueue_script(
			'wpuk-module-script-' . $this->ID,
			plugins_url( '/module-script.js', __FILE__ ),
			array( 'jquery' ),
			ULTIMAKIT_FOR_WP_VERSION,
			true
		);
	}

	/**
	 * Initialize the protection
	 */
	public function initialize_protection() {

		/*
		 * Empty entries must be discarded. explode(',', '') yields array(''), and in PHP 8
		 * strpos($haystack, '') returns 0, so a blank or trailing-comma exclusion list
		 * matched every URL and silently switched protection off for the whole site.
		 */
		$excluded_setting = (string) $this->getModuleSettings( $this->ID, 'excluded_urls', 'wp-login.php,wp-admin/' );
		$excluded_urls    = array_filter( array_map( 'trim', explode( ',', $excluded_setting ) ), 'strlen' );

		if ( empty( $excluded_urls ) ) {
			$excluded_urls = array( 'wp-login.php', 'wp-admin/' );
		}

		$current_url = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '';

		/*
		 * Compare paths, not raw strings: stripping every slash and prefix-matching what
		 * was left let any exclusion (including the default "wp-admin/") match unrelated
		 * URLs that merely started with the same characters, e.g. "wp-admin/" also excluded
		 * "/wp-admin-fake-page", and a site-configured exclusion like "/members" also
		 * excluded "/members-only-secret". A trailing slash marks a directory exclusion
		 * (its whole subtree); without one, only that exact path is excluded.
		 */
		$request_path = '/' . ltrim( (string) wp_parse_url( $current_url, PHP_URL_PATH ), '/' );

		// Exclusions like "wp-login.php" are relative to the site, so match them the same way on subdirectory installs.
		$site_path = '/' . trim( (string) wp_parse_url( home_url(), PHP_URL_PATH ), '/' );
		if ( '/' !== $site_path && 0 === strpos( $request_path, $site_path . '/' ) ) {
			$request_path = substr( $request_path, strlen( $site_path ) );
		}

		foreach ( $excluded_urls as $url ) {
			$excluded_path = '/' . ltrim( $url, '/' );

			if ( $request_path === $excluded_path ) {
				return;
			}

			if ( '/' === substr( $excluded_path, -1 ) && 0 === strpos( $request_path, $excluded_path ) ) {
				return;
			}
		}

		// Skip protection for whitelisted IPs
		$whitelist_setting = (string) $this->getModuleSettings( $this->ID, 'whitelist_ips' );
		$whitelist_ips     = array_filter( array_map( 'trim', explode( ',', $whitelist_setting ) ), 'strlen' );
		$current_ip        = isset( $_SERVER['REMOTE_ADDR'] ) ? wp_unslash( $_SERVER['REMOTE_ADDR'] ) : '';

		if ( $whitelist_ips && in_array( $current_ip, $whitelist_ips, true ) ) {
			return;
		}

		// Check if user is already authenticated
		if ( ! $this->is_authenticated() && ! is_user_logged_in() ) {
			$this->show_password_form();
		}
	}

	/**
	 * Check if user is authenticated
	 */
	private function is_authenticated() {
		if ( isset( $_COOKIE[ $this->session_name ] ) ) {
			$cookie_value = $_COOKIE[ $this->session_name ];
			$hash         = hash( 'sha256', $this->getModuleSettings( $this->ID, 'password' ) . AUTH_SALT );
			return hash_equals( $hash, $cookie_value );
		}
		return false;
	}

	/**
	 * Set authentication cookie
	 */
	private function set_auth_cookie() {
		$hash = hash( 'sha256', $this->getModuleSettings( $this->ID, 'password' ) . AUTH_SALT );
		setcookie(
			$this->session_name,
			$hash,
			array(
				'expires'  => time() + ( 30 * DAY_IN_SECONDS ),
				'path'     => '/',
				'domain'   => $_SERVER['HTTP_HOST'],
				'secure'   => is_ssl(),
				'httponly' => true,
				'samesite' => 'Strict',
			)
		);
	}

	/**
	 * Add admin bar information
	 */
	public function add_admin_bar_info( $wp_admin_bar ) {
		$status = $this->getModuleSettings( $this->ID, 'enable_protection' ) ? 'Active' : 'Inactive';
		$color  = $this->getModuleSettings( $this->ID, 'enable_protection' ) ? '#4caf50' : '#dc3545';

		$wp_admin_bar->add_node(
			array(
				'id'    => 'site-protection-status',
				'title' => '<span class="ab-icon"></span>' .
						'<span style="color: ' . $color . '">' . __( 'Protection', 'ultimakit-for-wp' ) . ': ' . $status . '</span>',
				'href'  => '#',
				'meta'  => array(
					'title' => __( 'Site Protection Settings', 'ultimakit-for-wp' ),
				),
			)
		);
	}

	/**
	 * Show password protection form
	 */
	private function show_password_form() {
		$error = false;

		if ( isset( $_SERVER['REQUEST_METHOD'] ) && 'POST' === $_SERVER['REQUEST_METHOD'] && isset( $_POST['site_password'] ) ) {
			$submitted = (string) wp_unslash( $_POST['site_password'] );
			$expected  = (string) $this->getModuleSettings( $this->ID, 'password' );

			// hash_equals avoids leaking the password through comparison timing.
			if ( '' !== $expected && hash_equals( $expected, $submitted ) ) {
				$this->set_auth_cookie();
				// wp_safe_redirect: REQUEST_URI is attacker-controlled and was an open redirect.
				wp_safe_redirect( esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) );
				exit;
			} else {
				$error = true;
			}
		}

		// Display the password form
		?>
		<!DOCTYPE html>
		<html lang="en">
		<head>
			<meta charset="UTF-8">
			<meta name="viewport" content="width=device-width, initial-scale=1.0">
			<title><?php echo get_bloginfo( 'name' ); ?> <?php _e( 'Protected', 'ultimakit-for-wp' ); ?></title>
			<style>
				body {
					font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
					background: #f0f2f5;
					margin: 0;
					padding: 0;
					display: flex;
					justify-content: center;
					align-items: center;
					min-height: 100vh;
				}
				.protection-form {
					background: #fff;
					padding: 30px;
					border-radius: 8px;
					box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
					width: 100%;
					max-width: 400px;
					text-align: center;
				}
				.protection-form h2 {
					color: #1d2327;
					margin-bottom: 20px;
				}
				.protection-form input[type="password"] {
					width: 100%;
					padding: 10px;
					margin: 10px 0;
					border: 1px solid #dcdcde;
					border-radius: 4px;
					box-sizing: border-box;
				}
				.protection-form input[type="submit"] {
					background: #2271b1;
					border: none;
					color: #fff;
					padding: 10px 20px;
					border-radius: 4px;
					cursor: pointer;
					font-size: 14px;
				}
				.protection-form input[type="submit"]:hover {
					background: #135e96;
				}
				.message {
					margin-bottom: 20px;
					color: #50575e;
				}
				.error-message {
					color: #dc3232;
					margin-bottom: 15px;
				}
			</style>
		</head>
		<body>
			<div class="protection-form">
				<h2><?php echo get_bloginfo( 'name' ); ?></h2>
				<div class="message"><?php echo esc_html( $this->getModuleSettings( $this->ID, 'protection_message' ) ); ?></div>
				<?php if ( $error ) : ?>
					<div class="error-message"><?php _e( 'Invalid password. Please try again.', 'ultimakit-for-wp' ); ?></div>
				<?php endif; ?>
				<form method="post">
					<input type="password" name="site_password" placeholder="<?php _e( 'Enter password', 'ultimakit-for-wp' ); ?>" required>
					<input type="submit" value="<?php _e( 'Access Site', 'ultimakit-for-wp' ); ?>">
				</form>
			</div>
		</body>
		</html>
		<?php
		exit;
	}
}
