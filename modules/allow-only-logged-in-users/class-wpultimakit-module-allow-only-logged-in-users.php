<?php
/**
 * Class UltimaKit_Module_Allow_Only_Logged_In_Users
 *
 * @since 1.0.0
 * @package    UltimaKit
 */

/**
 * Class UltimaKit_Module_Allow_Only_Logged_In_Users
 *
 * This class provides methods to control the display of dashboard widgets based on user preferences.
 * It allows users to selectively hide certain dashboard widgets to streamline their WordPress dashboard
 * experience and improve usability.
 *
 * @since 1.0.0
 */
class UltimaKit_Module_Allow_Only_Logged_In_Users extends UltimaKit_Module_Manager {
	/**
	 *
	 * @var string
	 */
	protected $ID = 'ultimakit_allow_only_logged_in_users';

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
	protected $read_more_link = 'allow-only-logged-in-users-in-wordpress';

	/**
	 * The settings associated with the module, if any.
	 *
	 * @var array
	 */
	protected $settings;

	/**
	 *
	 * Initializes the module with default values for properties and prepares
	 * any necessary setup or hooks into WordPress. This may include setting
	 * initial values, registering hooks, or preparing resources needed for
	 * the module to function properly within WordPress.
	 */
	public function __construct() {
		$this->name        = __( 'Allow Only Logged-In Users', 'ultimakit-for-wp' );
		$this->description = __( 'Make your site private just for logged-in users.', 'ultimakit-for-wp' );
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

			add_action(
				'wp',
				static function () {
					if ( is_user_logged_in() ) {
						return;
					}

					// Handle API requests separately.
					if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
						return;
					}

					// Allow access to the login screens.
					$allowed = array(
						'wp-login.php'     => true,
						'wp-signup.php'    => true,
						'wp-activate.php'  => true,
						'wp-trackback.php' => true,
						'wp-cron.php'      => true,
					);
					if ( isset( $allowed[ basename( $_SERVER['PHP_SELF'] ) ] ) ) {
						return;
					}

					nocache_headers();
					wp_safe_redirect(
						wp_login_url(
							set_url_scheme( 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] )
						)
					);
					exit;
				}
			);

			// Custom Login error.
			add_action(
				'init',
				static function () {
					global $error;

					if ( 'wp-login.php' !== basename( $_SERVER['PHP_SELF'] ) || ! empty( $_POST ) || ( ! empty( $_GET ) && empty( $_GET['redirect_to'] ) ) ) {
						return;
					}

					$redirect = isset( $_GET['redirect_to'] ) ? $_GET['redirect_to'] : '';
					// strpos rather than str_starts_with: WordPress only polyfills that from 5.9 and this plugin supports 5.6.
					if ( ! $redirect || 0 === strpos( $redirect, admin_url() ) ) {
						return;
					}

					$error = __( 'You need to login to access this website.' );
				}
			);

			// Force logged-in only traffic for the API.
			add_filter(
				'rest_authentication_errors',
				static function ( $result ) {
					if ( is_wp_error( $result ) ) {
						return $result;
					}

					if ( ! is_user_logged_in() ) {
						return new WP_Error(
							'rest_not_logged_in',
							__( 'This content is restricted to logged-in users.' ),
							array( 'status' => 401 )
						);
					}

					return $result;
				}
			);

			// Don't allow indexing.
			add_action( 'pre_option_blog_public', '__return_zero' );

		}
	}
}
