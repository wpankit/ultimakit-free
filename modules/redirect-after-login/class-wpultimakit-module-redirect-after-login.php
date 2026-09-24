<?php
/**
 * Class UltimaKit_Module_Redirect_After_Login
 *
 * @since 1.0.0
 * @package    UltimaKit
 */

/**
 * Class UltimaKit_Module_Redirect_After_Login
 *
 * @since 1.0.0
 */
class UltimaKit_Module_Redirect_After_Login extends UltimaKit_Module_Manager {
	/**
	 * @var string
	 */
	protected $ID = 'ultimakit_module_redirect_after_login';

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
	protected $read_more_link = 'redirect-after-login-in-wordpress';

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
		$this->name        = __( 'Redirect After Login', 'ultimakit-for-wp' );
		$this->description = __( 'Redirect users to specific pages upon logging in, enhancing user experience and engagement', 'ultimakit-for-wp' );
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

			add_filter( 'wp_login', array( $this, 'redirect_after_login' ), 5, 2 );
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
		$arguments['title'] = __( 'Redirect After Login', 'ultimakit-for-wp' );

		$user_roles = $this->get_all_user_roles();

		$arguments['fields'] = array(
			'redirect_after_login' => array(
				'type'  => 'text',
				'desc'  => __( 'Base URL:' ) . get_home_url(),
				'label' => __( 'URL Slug', 'ultimakit-for-wp' ),
				'value' => $this->getModuleSettings( $this->ID, 'redirect_after_login' ),
			),
			'user_roles_list'      => array(
				'type'    => 'select2',
				'label'   => __( 'User Roles', 'ultimakit-for-wp' ),
				'options' => $user_roles,
				'default' => '',
			),
			'user_roles_list_val'  => array(
				'type'  => 'hidden',
				'value' => $this->getModuleSettings( $this->ID, 'user_roles_list_val' ),
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
			'ultimakit_redirect_after_login',
			array(
				'ajax_url'       => admin_url( 'admin-ajax.php' ),
				'ajax_nonce'     => wp_create_nonce( 'ultimakit-smtp-email' ),
				'selected_roles' => $this->getModuleSettings( $this->ID, 'user_roles_list_val' ),
			)
		);
	}

	public function redirect_after_login( $username, $user ) {

		$redirect_slug     = $this->getModuleSettings( $this->ID, 'redirect_after_login' );
		$redirect_slug_raw = isset( $redirect_slug ) ? $redirect_slug : '';

		if ( ! empty( $redirect_slug_raw ) ) {
			$redirect_slug_trimmed = trim( trim( $redirect_slug_raw ), '/' );
			if ( false !== strpos( $redirect_slug_trimmed, '.php' ) ) {
				$slug_suffix = '';
			} else {
				$slug_suffix = '/';
			}
			$relative_url_path = $redirect_slug_trimmed . $slug_suffix;
		} else {
			$relative_url_path = '';
		}

		$user_roles_list = explode( ',', $this->getModuleSettings( $this->ID, 'user_roles_list_val' ) );

		if ( isset( $user_roles_list ) && ( count( $user_roles_list ) > 0 ) ) {

			// Assemble single-dimensional array of roles for which custom URL redirection should happen
			$roles_for_redirect = array();

			foreach ( $user_roles_list as $role_for_redirect ) {
				if ( $role_for_redirect ) {
					$roles_for_redirect[] = $role_for_redirect;
				}
			}

			// Does the user have roles data in array form?
			if ( isset( $user->roles ) && is_array( $user->roles ) ) {
				$current_user_roles = $user->roles;
			}

			// Set custom redirect URL for roles set in the settings. Otherwise, leave redirect URL to the default, i.e. admin dashboard.
			foreach ( $current_user_roles as $role ) {
				if ( in_array( $role, $roles_for_redirect ) ) {
					wp_safe_redirect( home_url( $relative_url_path ) );
					exit();
				}
			}
		}
	}
}
