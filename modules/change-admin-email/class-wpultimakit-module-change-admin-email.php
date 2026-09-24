<?php
/**
 * Class UltimaKit_Module_Change_Admin_Email
 *
 * @since 1.0.0
 * @package    UltimaKit
 */

/**
 * Class UltimaKit_Module_Change_Admin_Email
 *
 * @since 1.0.0
 */
class UltimaKit_Module_Change_Admin_Email extends UltimaKit_Module_Manager {
	/**
	 * @var string
	 */
	protected $ID = 'ultimakit_module_change_admin_email';

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
	protected $read_more_link = 'change-admin-email-in-wordpress-without-email-verification';

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
		$this->name        = __( 'Change Admin Email', 'ultimakit-for-wp' );
		$this->description = __( 'Change Admin Email Without Verification', 'ultimakit-for-wp' );
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
			add_action( 'wp_ajax_ultimakit_update_admin_email', array( $this, 'ultimakit_update_admin_email' ) );
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
		$arguments['title'] = __( 'Admin Email Settings', 'ultimakit-for-wp' );

		$arguments['fields'] = array(
			'admin_email' => array(
				'type'  => 'text',
				'label' => __( 'Admin Email Address', 'ultimakit-for-wp' ),
				'value' => ( ! empty( get_option( 'new_admin_email', '' ) ) ) ? get_option( 'new_admin_email', '' ) : get_option( 'admin_email', '' ),
				'desc'  => __( 'Please enter valid email address.', 'ultimakit-for-wp' ),
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
			'ultimakit_change_admin_email',
			array(
				'ajax_url'   => admin_url( 'admin-ajax.php' ),
				'ajax_nonce' => wp_create_nonce( 'ultimakit-change-email' ),
			)
		);
	}

	public function ultimakit_update_admin_email() {

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'You do not have sufficient permissions', 403 );
		}

		// Verify the nonce
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'ultimakit-change-email' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'ultimakit-for-wp' ) ), 401 );
		}

		$email = isset( $_POST['admin_email'] ) ? sanitize_email( $_POST['admin_email'] ) : '';

		if ( empty( $email ) ) {
			wp_send_json_error( 'Please enter valid email address.' );
		}

		if ( is_email( $email ) ) {
			update_option( 'admin_email', $email );
			delete_option( 'new_admin_email' );
			wp_send_json_success( __( 'Admin email address updated successfully!', 'ultimakit-for-wp' ) );
		} else {
			wp_send_json_error( __( 'Please enter valid email address.', 'ultimakit-for-wp' ) );
		}
	}
}
