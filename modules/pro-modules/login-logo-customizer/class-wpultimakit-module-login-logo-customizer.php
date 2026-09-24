<?php
/**
 * Class UltimaKit_Module_Login_Logo_Customizer
 *
 * @since 1.0.0
 * @package    UltimaKit
 */

/**
 * Class UltimaKit_Module_Login_Logo_Customizer
 *
 * @since 1.0.0
 */
class UltimaKit_Module_Login_Logo_Customizer extends UltimaKit_Module_Manager {
	/**
	 * @var string
	 */
	protected $ID = 'ultimakit_module_login_logo_customizer';

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
	protected $plan = 'pro';

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
	protected $read_more_link = 'change-admin-login-logo-customizer-wordpress';

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
		$this->name        = __( 'Login Logo Customizer', 'ultimakit-for-wp' );
		$this->description = __( 'Allows users to replace the default WordPress login page logo with their custom logo, with options to adjust its size and positioning.', 'ultimakit-for-wp' );
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
			add_action( 'login_enqueue_scripts', array( $this, 'wpuk_custom_login_logo' ) );
			add_filter( 'login_headerurl', array( $this, 'wpuk_login_logo_url' ) );

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
		$arguments['title'] = __( 'Custom Admin Footer', 'ultimakit-for-wp' );

		$arguments['fields'] = array(
			'html_img'         => array(
				'type'  => 'html',
				'value' => '<img id="wpuk_img_placeholder" src="' . $this->getModuleSettings( $this->ID, 'login_logo_url' ) . '" width="100px">',
			),
			'html'             => array(
				'type'  => 'html',
				'value' => '<button class="btn btn-primary" id="wpuk_file_upload_llc">' . __( 'Upload Image', 'ultimakit-for-wp' ) . '</button>',
			),
			'login_logo_url'   => array(
				'type'        => 'text',
				'label'       => '', // __( 'Logo URL', 'ultimakit-for-wp' ),
				'placeholder' => __( 'Logo URL', 'ultimakit-for-wp' ),
				'value'       => $this->getModuleSettings( $this->ID, 'login_logo_url' ),
			),
			'login_logo_width' => array(
				'type'        => 'number',
				'label'       => __( 'Logo Width', 'ultimakit-for-wp' ),
				'placeholder' => __( 'Logo Width', 'ultimakit-for-wp' ),
				'value'       => $this->getModuleSettings( $this->ID, 'login_logo_width' ),
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
		wp_enqueue_media();

		wp_enqueue_script(
			'ultimakit-module-script-' . $this->ID,
			plugins_url( '/module-script.js', __FILE__ ),
			array( 'jquery' ),
			ULTIMAKIT_FOR_WP_VERSION,
			true
		);
	}


	// Customize the login logo CSS
	public function wpuk_custom_login_logo() {
		$logo_url   = esc_url( $this->getModuleSettings( $this->ID, 'login_logo_url' ) );
		$logo_width = intval( $this->getModuleSettings( $this->ID, 'login_logo_width' ) );

		if ( $logo_url ) {
			?>
			<style type="text/css">
				body.login div#login h1 a {
					background-image: url('<?php echo $logo_url; ?>');
					background-size: contain;
					width: <?php echo $logo_width; ?>px;
				}
			</style>
			<?php
		}
	}

	// Set custom URL for login logo link
	public function wpuk_login_logo_url() {
		return home_url();
	}
}
