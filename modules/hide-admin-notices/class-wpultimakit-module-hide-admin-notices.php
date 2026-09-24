<?php
/**
 * Class UltimaKit_Module_Hide_Admin_Notices
 *
 * @since 1.0.0
 * @package    UltimaKit
 */

/**
 * Class UltimaKit_Module_Hide_Admin_Notices
 *
 * This class provides methods to control the display of dashboard widgets based on user preferences.
 * It allows users to selectively hide certain dashboard widgets to streamline their WordPress dashboard
 * experience and improve usability.
 *
 * @since 1.0.0
 */
class UltimaKit_Module_Hide_Admin_Notices extends UltimaKit_Module_Manager {
	/**
	 * Unique identifier for the Cleanup Admin Bar module.
	 *
	 * @var string
	 */
	protected $ID = 'ultimakit_hide_admin_notices';

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
	protected $read_more_link = 'hide-admin-notice-in-wordpress';

	/**
	 * The settings associated with the module, if any.
	 *
	 * @var array
	 */
	protected $settings;

	/**
	 * Constructs the Enhance List Table module instance.
	 *
	 * Initializes the module with default values for properties and prepares
	 * any necessary setup or hooks into WordPress. This may include setting
	 * initial values, registering hooks, or preparing resources needed for
	 * the module to function properly within WordPress.
	 */
	public function __construct() {
		$this->name        = __( 'Hide Admin Notices', 'ultimakit-for-wp' );
		$this->description = __( 'Clean up admin pages by moving notices into a separate panel easily accessible via the admin bar.', 'ultimakit-for-wp' );
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
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_notices_menu_inline_css' ) );
		}
	}

	/**
	 * Outputs inline CSS for styling admin notices in the WordPress admin menu.
	 *
	 * This function generates inline CSS to style admin notices displayed within the WordPress admin menu.
	 * The generated CSS includes styles for various notice types such as success, error, warning, and info.
	 * Developers can customize these styles as needed to match the design of their WordPress admin area.
	 *
	 * @since 1.0.0
	 */
	public function admin_notices_menu_inline_css() {
		?>
		<style type="text/css">
			#wpbody-content .notice:not(.system-notice,.update-message),
			#wpbody-content .notice-error,
			#wpbody-content .error,
			#wpbody-content .notice-info,
			#wpbody-content .notice-information,
			#wpbody-content #message,
			#wpbody-content .notice-warning:not(.update-message),
			#wpbody-content .notice-success:not(.update-message),
			#wpbody-content .notice-updated,
			#wpbody-content .updated:not(.active, .inactive, .plugin-update-tr),
			#wpbody-content .update-nag,
			#wpbody-content > .wrap > .notice:not(.system-notice,.hidden),
			#wpbody-content > .wrap > .notice-error,
			#wpbody-content > .wrap > .error:not(.hidden),
			#wpbody-content > .wrap > .notice-info,
			#wpbody-content > .wrap > .notice-information,
			#wpbody-content > .wrap > #message,
			#wpbody-content > .wrap > .notice-warning:not(.hidden),
			#wpbody-content > .wrap > .notice-success,
			#wpbody-content > .wrap > .notice-updated,
			#wpbody-content > .wrap > .updated,
			#wpbody-content > .wrap > .update-nag,
			#wpbody-content > .wrap > div > .notice:not(.system-notice,.hidden),
			#wpbody-content > .wrap > div > .notice-error,
			#wpbody-content > .wrap > div > .error:not(.hidden),
			#wpbody-content > .wrap > div > .notice-info,
			#wpbody-content > .wrap > div > .notice-information,
			#wpbody-content > .wrap > div > #message,
			#wpbody-content > .wrap > div > .notice-warning:not(.hidden),
			#wpbody-content > .wrap > div > .notice-success,
			#wpbody-content > .wrap > div > .notice-updated,
			#wpbody-content > .wrap > div > .updated,
			#wpbody-content > .wrap > div > .update-nag,
			#wpbody-content > div > .wrap > .notice:not(.system-notice,.hidden),
			#wpbody-content > div > .wrap > .notice-error,
			#wpbody-content > div > .wrap > .error:not(.hidden),
			#wpbody-content > div > .wrap > .notice-info,
			#wpbody-content > div > .wrap > .notice-information,
			#wpbody-content > div > .wrap > #message,
			#wpbody-content > div > .wrap > .notice-warning:not(.hidden),
			#wpbody-content > div > .wrap > .notice-success,
			#wpbody-content > div > .wrap > .notice-updated,
			#wpbody-content > div > .wrap > .updated,
			#wpbody-content > div > .wrap > .update-nag,
			#wpbody-content > .notice,
			#wpbody-content > .error,
			#wpbody-content > .updated,
			#wpbody-content > .update-nag,
			#wpbody-content > .jp-connection-banner,
			#wpbody-content > .jitm-banner,
			#wpbody-content > .jetpack-jitm-message,
			#wpbody-content > .ngg_admin_notice,
			#wpbody-content > .imagify-welcome,
			#wpbody-content #wordfenceAutoUpdateChoice,
			#wpbody-content #easy-updates-manager-dashnotice {
				display: none !important;
			}
		</style>
		<?php
	}
}



