<?php
/**
 * Class UltimaKit_Module_Clean_User_Profiles
 *
 * @since 1.0.0
 * @package    UltimaKit
 */

/**
 * Class UltimaKit_Module_Clean_User_Profiles
 *
 * @since 1.0.0
 */
class UltimaKit_Module_Clean_User_Profiles extends UltimaKit_Module_Manager {
	/**
	 * Unique identifier for the Hide Admin Bar module.
	 *
	 * @var string
	 */
	protected $ID = 'ultimakit_clean_user_profiles';

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
	protected $read_more_link = 'clean-user-profiles-in-wordpress';

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
		$this->name        = __( 'Clean User Profiles', 'ultimakit-for-wp' );
		$this->description = __( 'Clean up user profiles by removing unused sections.', 'ultimakit-for-wp' );
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
		$arguments['title'] = __( 'Clean User Profiles (Hide sections)', 'ultimakit-for-wp' );

		$arguments['fields'] = array(
			'user-admin-color-wrap'         => array(
				'type'  => 'checkbox',
				'label' => __( 'Admin Color Scheme', 'ultimakit-for-wp' ),
				'value' => $this->getModuleSettings( $this->ID, 'user-admin-color-wrap' ),
			),
			'user-admin-bar-front-wrap'     => array(
				'type'  => 'checkbox',
				'label' => __( 'Toolbar', 'ultimakit-for-wp' ),
				'value' => $this->getModuleSettings( $this->ID, 'user-admin-bar-front-wrap' ),
			),
			'user-description-wrap'         => array(
				'type'  => 'checkbox',
				'label' => __( 'Biographical Info', 'ultimakit-for-wp' ),
				'value' => $this->getModuleSettings( $this->ID, 'user-description-wrap' ),
			),
			'user-role-wrap'                => array(
				'type'  => 'checkbox',
				'label' => __( 'Role', 'ultimakit-for-wp' ),
				'value' => $this->getModuleSettings( $this->ID, 'user-role-wrap' ),
			),
			'user-email-wrap'               => array(
				'type'  => 'checkbox',
				'label' => __( 'Email', 'ultimakit-for-wp' ),
				'value' => $this->getModuleSettings( $this->ID, 'user-email-wrap' ),
			),
			'user-pass1-wrap'               => array(
				'type'  => 'checkbox',
				'label' => __( 'New Password', 'ultimakit-for-wp' ),
				'value' => $this->getModuleSettings( $this->ID, 'user-pass1-wrap' ),
			),
			'user-generate-reset-link-wrap' => array(
				'type'  => 'checkbox',
				'label' => __( 'Reset Password', 'ultimakit-for-wp' ),
				'value' => $this->getModuleSettings( $this->ID, 'user-generate-reset-link-wrap' ),
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

		$hide_sections = array();

		if ( 'on' === $this->getModuleSettings( $this->ID, 'user-admin-color-wrap' ) ) {
			array_push( $hide_sections, 'user-admin-color-wrap' );
		}

		if ( 'on' === $this->getModuleSettings( $this->ID, 'user-admin-bar-front-wrap' ) ) {
			array_push( $hide_sections, 'user-admin-bar-front-wrap' );
		}

		if ( 'on' === $this->getModuleSettings( $this->ID, 'user-description-wrap' ) ) {
			array_push( $hide_sections, 'user-description-wrap' );
		}

		if ( 'on' === $this->getModuleSettings( $this->ID, 'user-role-wrap' ) ) {
			array_push( $hide_sections, 'user-role-wrap' );
		}

		if ( 'on' === $this->getModuleSettings( $this->ID, 'user-email-wrap' ) ) {
			array_push( $hide_sections, 'user-email-wrap' );
		}

		if ( 'on' === $this->getModuleSettings( $this->ID, 'user-pass1-wrap' ) ) {
			array_push( $hide_sections, 'user-pass1-wrap' );
		}

		if ( 'on' === $this->getModuleSettings( $this->ID, 'user-generate-reset-link-wrap' ) ) {
			array_push( $hide_sections, 'user-generate-reset-link-wrap' );
		}

		wp_localize_script(
			'ultimakit-module-script-' . $this->ID,
			'ultimakit_clean_user_profiles',
			array( 'sections' => json_encode( $hide_sections ) )
		);
	}
}
