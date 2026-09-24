<?php
/**
 * Class UltimaKit_Module_Disallow_Plugin_Upload
 *
 * @since 1.0.0
 * @package    UltimaKit
 */

/**
 * Class UltimaKit_Module_Disallow_Plugin_Upload
 *
 * This class provides methods to control the display of dashboard widgets based on user preferences.
 * It allows users to selectively hide certain dashboard widgets to streamline their WordPress dashboard
 * experience and improve usability.
 *
 * @since 1.0.0
 */
class UltimaKit_Module_Disallow_Plugin_Upload extends UltimaKit_Module_Manager {
	/**
	 *
	 * @var string
	 */
	protected $ID = 'ultimakit_module_disallow_plugin_upload';

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
	protected $read_more_link = 'disallow-plugin-upload-in-wordpress';

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
		$this->name        = __( 'Disallow Plugin Upload', 'ultimakit-for-wp' );
		$this->description = __( 'Disable zip file uploads for plugins, which are used to install plugins on your website.', 'ultimakit-for-wp' );
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
			add_action('admin_menu', array($this, 'remove_plugin_install_menu'));
			add_action('admin_init', array($this, 'block_plugin_install_page'));
			add_action('admin_notices', array($this, 'plugin_upload_disabled_notice'));
		}
	}
	
	public function remove_plugin_install_menu() {
		remove_submenu_page('plugins.php', 'plugin-install.php');
	}
	
	public function block_plugin_install_page() {
		global $pagenow;
		
		if ($pagenow === 'plugin-install.php') {
			wp_die(
				__('Plugin installation has been disabled for security reasons.', 'ultimakit-for-wp'),
				__('Plugin Installation Disabled', 'ultimakit-for-wp'),
				array('response' => 403)
			);
		}
	}
	
	public function plugin_upload_disabled_notice() {
		$screen = get_current_screen();
		if ($screen && $screen->id === 'plugins') {
			?>
			<div class="notice notice-warning is-dismissible">
				<p><?php esc_html_e('Plugin uploads have been disabled for security reasons. Please contact your administrator if you need to install a new plugin.', 'ultimakit-for-wp'); ?></p>
			</div>
			<?php
		}
	}


}
