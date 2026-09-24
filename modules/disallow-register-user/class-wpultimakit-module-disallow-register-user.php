<?php
/**
 * Class UltimaKit_Module_Disallow_Register_User
 *
 * @since 1.0.0
 * @package    UltimaKit
 */

/**
 * Class UltimaKit_Module_Disallow_Register_User
 *
 * This class provides methods to control the display of dashboard widgets based on user preferences.
 * It allows users to selectively hide certain dashboard widgets to streamline their WordPress dashboard
 * experience and improve usability.
 *
 * @since 1.0.0
 */
class UltimaKit_Module_Disallow_Register_User extends UltimaKit_Module_Manager {
	/**
	 *
	 * @var string
	 */
	protected $ID = 'ultimakit_module_disallow_register_user';

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
	protected $category = 'Log In/Out | Register';

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
	protected $read_more_link = 'disallow-register-user-in-wordpress';

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
		$this->name        = __( 'Disallow register user', 'ultimakit-for-wp' );
		$this->description = __( 'Prevent the creation of new user accounts on your website with the native WordPress registration form.', 'ultimakit-for-wp' );
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
			add_action('init', array($this, 'disable_user_registration'));
			add_action('admin_init', array($this, 'remove_user_registration_options'));
			add_action('admin_notices', array($this, 'registration_disabled_notice'));
			add_filter('wp_login_errors', array($this, 'modify_registration_error_message'), 10, 2);
		}
	}
	
	public function disable_user_registration() {
		// Disable the registration functionality
		add_filter('option_users_can_register', '__return_zero');
		
		// Redirect registration page to login
		if (isset($_GET['action']) && $_GET['action'] == 'register') {
			wp_redirect(wp_login_url());
			exit();
		}
	}
	
	public function remove_user_registration_options() {
		// Remove the "Anyone can register" checkbox from the Settings > General page
		add_filter('pre_option_users_can_register', '__return_zero');
		
		// Hide the checkbox using CSS
		add_action('admin_head', function() {
			echo '<style>
				.options-general-php label[for="users_can_register"] {
					display: none;
				}
			</style>';
		});
	}
	
	public function registration_disabled_notice() {
		$screen = get_current_screen();
		if ($screen && $screen->id === 'options-general') {
			?>
			<div class="notice notice-warning is-dismissible">
				<p><?php esc_html_e('User registration has been disabled for security reasons. New user accounts can only be created by administrators.', 'ultimakit-for-wp'); ?></p>
			</div>
			<?php
		}
	}
	
	public function modify_registration_error_message($errors, $redirect_to) {
		// If someone tries to access the registration form directly
		if (isset($_GET['action']) && $_GET['action'] == 'register') {
			$errors->add(
				'registerdisabled',
				__('<strong>ERROR</strong>: User registration is disabled on this site.', 'ultimakit-for-wp'),
				'message'
			);
		}
		
		return $errors;
	}


}
