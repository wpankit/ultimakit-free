<?php
/**
 * Class UltimaKit_Module_Disable_Site_Health
 *
 * @since 1.0.0
 * @package    UltimaKit
 */

/**
 * Class UltimaKit_Module_Disable_Site_Health
 *
 * This class provides methods to control the display of dashboard widgets based on user preferences.
 * It allows users to selectively hide certain dashboard widgets to streamline their WordPress dashboard
 * experience and improve usability.
 *
 * @since 1.0.0
 */
class UltimaKit_Module_Disable_Site_Health extends UltimaKit_Module_Manager {
	/**
	 *
	 * @var string
	 */
	protected $ID = 'ultimakit_module_disable_site_health';

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
	protected $read_more_link = 'disable-site-health-in-wordpress';

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
		$this->name        = __( 'Disable Site Health', 'ultimakit-for-wp' );
		$this->description = __( 'Completely hide the Site Health menu item and dashboard widget.', 'ultimakit-for-wp' );
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

			// Remove Tools Submenu Item for Site Health.
			add_action(
				'admin_menu',
				function () {
					remove_submenu_page( 'tools.php', 'site-health.php' );
				}
			);

			// Prevent direct access to the Site Health page.
			add_action(
				'current_screen',
				function () {
					$screen = get_current_screen();
					if ( 'site-health' === $screen->id ) {
						wp_safe_redirect( admin_url() );
						exit;
					}
				}
			);

			// Disable the Site Health Dashboard Widget.
			add_action(
				'wp_dashboard_setup',
				function () {
					global $wp_meta_boxes;
					if ( isset( $wp_meta_boxes['dashboard']['normal']['core']['dashboard_site_health'] ) ) {
						unset( $wp_meta_boxes['dashboard']['normal']['core']['dashboard_site_health'] );
					}
				}
			);

		}
	}
}
