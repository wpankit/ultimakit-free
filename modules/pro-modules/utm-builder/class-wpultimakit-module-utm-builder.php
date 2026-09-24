<?php
/**
 * Class UltimaKit_Module_UTM_Builder
 *
 * @since 1.0.0
 * @package    UltimaKit
 */

/**
 * Class UltimaKit_Module_UTM_Builder
 *
 * @since 1.0.0
 */
class UltimaKit_Module_UTM_Builder extends UltimaKit_Module_Manager {
	/**
	 * @var string
	 */
	protected $ID = 'ultimakit_module_utm_builder';

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
	protected $category = 'Utilities';

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
	protected $read_more_link = 'utm-builder-in-wordpress';

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
		$this->name        = __( 'UTM Builder', 'ultimakit-for-wp' );
		$this->description = __( 'Generate UTM tracking URLs easily.', 'ultimakit-for-wp' );
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
		$arguments             = array();
		$arguments['ID']       = $this->ID;
		$arguments['title']    = __( 'UTM Builder', 'ultimakit-for-wp' );
		$arguments['save_btn'] = __( 'Generate URL', 'ultimakit-for-wp' );

		$arguments['fields'] = array(
			'website_url'            => array(
				'type'  => 'text',
				'label' => __( 'Website URL', 'ultimakit-for-wp' ),
				'value' => get_home_url(),
			),
			'campaign_id'            => array(
				'type'  => 'text',
				'label' => __( 'Campaign ID', 'ultimakit-for-wp' ),
				'value' => '',
			),
			'campaign_source'        => array(
				'type'  => 'text',
				'label' => __( 'Campaign Source', 'ultimakit-for-wp' ),
				'value' => '',
			),
			'campaign_medium'        => array(
				'type'  => 'text',
				'label' => __( 'Campaign Medium', 'ultimakit-for-wp' ),
				'value' => '',
			),
			'campaign_name'          => array(
				'type'  => 'text',
				'label' => __( 'Campaign Name', 'ultimakit-for-wp' ),
				'value' => '',
			),
			'campaign_term'          => array(
				'type'  => 'text',
				'label' => __( 'Campaign Term', 'ultimakit-for-wp' ),
				'value' => '',
			),
			'campaign_content'       => array(
				'type'  => 'text',
				'label' => __( 'Campaign Content', 'ultimakit-for-wp' ),
				'value' => '',
			),
			'ultimakit_generate_url' => array(
				'type'  => 'textarea',
				'label' => __( 'Generated URL:', 'ultimakit-for-wp' ),
				'value' => '',
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
			'ultimakit_utm_builder',
			array(
				'ajax_url'   => admin_url( 'admin-ajax.php' ),
				'ajax_nonce' => wp_create_nonce( 'ultimakit-utm-builder' ),
				'url_copied' => __( 'URL copied to clipboard!', 'ultimakit-for-wp' ),
				'url_req'    => __( 'Base URL is required.', 'ultimakit-for-wp' ),
			)
		);
	}
}
