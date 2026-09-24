<?php
/**
 * Class UltimaKit_Module_Disallow_Wp_File_Edit
 *
 * @since 1.0.0
 * @package    UltimaKit
 */

/**
 * Class UltimaKit_Module_Disallow_Wp_File_Edit
 *
 * This class provides methods to control the display of dashboard widgets based on user preferences.
 * It allows users to selectively hide certain dashboard widgets to streamline their WordPress dashboard
 * experience and improve usability.
 *
 * @since 1.0.0
 */
class UltimaKit_Module_Disallow_Wp_File_Edit extends UltimaKit_Module_Manager {
	/**
	 *
	 * @var string
	 */
	protected $ID = 'ultimakit_module_disallow_wp_file_edit';

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
	protected $read_more_link = 'disallow-wp-file-edit-in-wordpress';

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
		$this->name        = __( 'Disallow WP File Edit', 'ultimakit-for-wp' );
		$this->description = __( 'Prevent the modification of your website\'s core files through the WordPress admin panel.', 'ultimakit-for-wp' );
		$this->is_active   = $this->isModuleActive( $this->ID );
		$this->settings    = 'no';
		$this->initializeModule();

        add_filter('ultimakit_module_class_map', function($map) {
			$map['ultimakit_module_disallow_wp_file_edit'] = 'UltimaKit_Module_Disallow_Wp_File_Edit';
			return $map;
		});
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
            if (!defined('DISALLOW_FILE_EDIT')) {
				UltimaKit_WP_Config::replace_or_add_constant('DISALLOW_FILE_EDIT', true);
			} else {
				// If DISALLOW_FILE_EDIT is already defined but with a different value, update it
				if (DISALLOW_FILE_EDIT !== true) {
					UltimaKit_WP_Config::replace_or_add_constant('DISALLOW_FILE_EDIT', true);
				}
			}

            add_action( 'admin_init', array( $this, 'disable_wp_file_edit' ) );
		}
	}

    /**
     * deactivate
     *
     * @return void
     */
    public static function deactivate(){
        UltimaKit_WP_Config::remove_constant('DISALLOW_FILE_EDIT');
    }

    /**
     * Disable the wp file edit
     *
     * @return void
     */
    public function disable_wp_file_edit() {
        if( !defined('DISALLOW_FILE_EDIT') ) define( 'DISALLOW_FILE_EDIT', true );
    }

}
