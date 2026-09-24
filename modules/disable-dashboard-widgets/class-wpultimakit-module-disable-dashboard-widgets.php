<?php
/**
 * Class UltimaKit_Module_Disable_DashBoard_Widgets
 *
 * The UltimaKit_Module_Disable_DashBoard_Widgets class manages the functionality to disable
 * specific dashboard widgets in WordPress.
 *
 * This class provides methods to control the display of dashboard widgets based on user preferences.
 * It allows users to selectively hide certain dashboard widgets to streamline their WordPress dashboard
 * experience and improve usability.
 *
 * @since 1.0.0
 * @package    UltimaKit
 */

/**
 * Class UltimaKit_Module_Disable_DashBoard_Widgets
 *
 * This class provides methods to control the display of dashboard widgets based on user preferences.
 * It allows users to selectively hide certain dashboard widgets to streamline their WordPress dashboard
 * experience and improve usability.
 *
 * @since 1.0.0
 */
class UltimaKit_Module_Disable_DashBoard_Widgets extends UltimaKit_Module_Manager {
	/**
	 * Unique identifier for the Cleanup Admin Bar module.
	 *
	 * @var string
	 */
	protected $ID = 'ultimakit_disable_dashboard_widgets';

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
	protected $category = 'Disable Components';

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
	protected $read_more_link = 'disable-dashboard-widgets-in-wordpress';

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
		$this->name        = __( 'Disable Dashboard Widgets', 'ultimakit-for-wp' );
		$this->description = __( 'Improve dashboard performance by disabling widgets. Disabled widgets won\'t load assets or appear in Screen Options.', 'ultimakit-for-wp' );
		$this->is_active   = $this->isModuleActive( $this->ID );
		$this->settings    = 'yes';
		$this->initializeModule();
		add_action( 'wp_dashboard_setup', array( $this, 'disable_dashboard_widgets' ) );
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
		if ( true === $this->ultimakit_asset_condition() ) {
			add_action( 'admin_enqueue_scripts', array( $this, 'add_scripts' ) );
			add_action( 'admin_footer', array( $this, 'add_modal' ) );
		}
	}

	/**
	 * This function is used to hide/show dashboard widgets on dashboard.
	 *
	 * @return mixed
	 */
	public function disable_dashboard_widgets() {
		if ( ! $this->is_active ) {
			return false;
		}
		// Dashboard Activity Widget.
		if ( 'on' === $this->getModuleSettings( $this->ID, 'dashboard_activity' ) ) {
			remove_meta_box( 'dashboard_activity', 'dashboard', 'normal' );
		}

		// Dashboard Right now widget.
		if ( 'on' === $this->getModuleSettings( $this->ID, 'dashboard_right_now' ) ) {
			remove_meta_box( 'dashboard_right_now', 'dashboard', 'normal' );
		}

		// Dashboard Open Dashboard.
		if ( 'on' === $this->getModuleSettings( $this->ID, 'iawp' ) ) {
			remove_meta_box( 'iawp', 'dashboard', 'normal' );
		}

		// Dashboard Quick Draft.
		if ( 'on' === $this->getModuleSettings( $this->ID, 'dashboard_quick_press' ) ) {
			remove_meta_box( 'dashboard_quick_press', 'dashboard', 'side' );
		}

		// Dashboard Quick Draft.
		if ( 'on' === $this->getModuleSettings( $this->ID, 'dashboard_site_health' ) ) {
			remove_meta_box( 'dashboard_site_health', 'dashboard', 'normal' );
		}

		// Dashboard Primary.
		if ( 'on' === $this->getModuleSettings( $this->ID, 'dashboard_primary' ) ) {
			remove_meta_box( 'dashboard_primary', 'dashboard', 'side' );
		}

		// Dashboard Primary.
		if ( 'on' === $this->getModuleSettings( $this->ID, 'wc_admin_dashboard_setup' ) ) {
			remove_meta_box( 'wc_admin_dashboard_setup', 'dashboard', 'normal' );
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
		$arguments['title'] = __( 'Disable Dashboard Widgets', 'ultimakit-for-wp' );

		$arguments['fields'] = array(
			'dashboard_activity'    => array(
				'type'  => 'checkbox',
				'label' => __( 'Activity', 'ultimakit-for-wp' ),
				'value' => $this->getModuleSettings( $this->ID, 'dashboard_activity' ),
			),
			'dashboard_right_now'   => array(
				'type'  => 'checkbox',
				'label' => __( 'At a Glance', 'ultimakit-for-wp' ),
				'value' => $this->getModuleSettings( $this->ID, 'dashboard_right_now' ),
			),
			'iawp'                  => array(
				'type'  => 'checkbox',
				'label' => __( 'Open Dashboard', 'ultimakit-for-wp' ),
				'value' => $this->getModuleSettings( $this->ID, 'iawp' ),
			),
			'dashboard_quick_press' => array(
				'type'  => 'checkbox',
				'label' => __( 'Quick Draft', 'ultimakit-for-wp' ),
				'value' => $this->getModuleSettings( $this->ID, 'dashboard_quick_press' ),
			),
			'dashboard_site_health' => array(
				'type'  => 'checkbox',
				'label' => __( 'Site Health Status', 'ultimakit-for-wp' ),
				'value' => $this->getModuleSettings( $this->ID, 'dashboard_site_health' ),
			),
			'dashboard_primary'     => array(
				'type'  => 'checkbox',
				'label' => __( 'WordPress Events and News', 'ultimakit-for-wp' ),
				'value' => $this->getModuleSettings( $this->ID, 'dashboard_primary' ),
			),
		);

		// Include the plugin.php file if it's not already included.
		// This is necessary if you're using this outside of the admin area.
		if ( ! function_exists( 'is_plugin_active' ) ) {
			include_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		// Check if WooCommerce is active.
		if ( is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
			// WooCommerce is active.
			$arguments['fields']['wc_admin_dashboard_setup'] = array(
				'type'  => 'checkbox',
				'label' => __( 'WooCommerce Setup', 'ultimakit-for-wp' ),
				'value' => $this->getModuleSettings( $this->ID, 'wc_admin_dashboard_setup' ),
			);
		}

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
			'wpuk-module-script-' . $this->ID,
			plugins_url( '/module-script.js', __FILE__ ),
			array( 'jquery' ),
			ULTIMAKIT_FOR_WP_VERSION,
			true
		);
	}
}
