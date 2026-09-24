<?php
/**
 * Class UltimaKit_Module_List_Active_Plugins_First
 *
 * @since 1.0.0
 * @package    UltimaKit
 */

/**
 * Class UltimaKit_Module_List_Active_Plugins_First
 *
 * @since 1.0.0
 */
class UltimaKit_Module_List_Active_Plugins_First extends UltimaKit_Module_Manager {
	/**
	 * Unique identifier for the Hide Admin Bar module.
	 *
	 * @var string
	 */
	protected $ID = 'ultimakit_module_list_active_plugins_first';

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
	protected $read_more_link = 'list-active-plugins-first-in-wordpress';

	/**
	 * The settings associated with the module, if any.
	 *
	 * @var array
	 */
	protected $settings;

	/**
	 * Initializes the module with default values for properties and prepares
	 * any necessary setup or hooks into WordPress. This may include setting
	 * initial values, registering hooks, or preparing resources needed for
	 * the module to function properly within WordPress.
	 */
	public function __construct() {
		$this->name        = __( 'Show Active Plugins First', 'ultimakit-for-wp' );
		$this->description = __( 'Show active plugins first in the plugin list. This is especially helpful if you have many inactive plugins.', 'ultimakit-for-wp' );
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
			add_action( 'admin_head-plugins.php', array( $this, 'show_active_plugins_first' ) );
		}
	}

	public function show_active_plugins_first() {
		global $wp_list_table, $status;

		if ( ! in_array( $status, array( 'active', 'inactive', 'recently_activated', 'mustuse' ), true ) ) {
			uksort( $wp_list_table->items, array( $this, 'change_plugins_order' ) );
		}
	}

	// Custom callback to order plugins
	public function change_plugins_order( $a, $b ) {
		$a_active = is_plugin_active( $a );
		$b_active = is_plugin_active( $b );

		if ( $a_active && ! $b_active ) {
			return -1;
		} elseif ( ! $a_active && $b_active ) {
			return 1;
		} else {
			// If both plugins have the same activation status, order by plugin name
			return strcasecmp( $a, $b );
		}
	}
}
