<?php
/**
 * Class UltimaKit_Module_Lock_Site_Url
 *
 * @since 1.0.0
 * @package    UltimaKit
 */

/**
 * Class UltimaKit_Module_Lock_Site_Url
 *
 * This class provides methods to control the display of dashboard widgets based on user preferences.
 * It allows users to selectively hide certain dashboard widgets to streamline their WordPress dashboard
 * experience and improve usability.
 *
 * @since 1.0.0
 */
class UltimaKit_Module_Lock_Site_Url extends UltimaKit_Module_Manager {
	/**
	 *
	 * @var string
	 */
	protected $ID = 'ultimakit_module_lock_site_url';

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
	protected $read_more_link = 'lock-site-url-in-wordpress';

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
		$this->name        = __( 'Lock Site URL', 'ultimakit-for-wp' );
		$this->description = __( 'Prevent the modification of the site URL on your website.', 'ultimakit-for-wp' );
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
			// Disable site URL fields in General Settings
			add_filter('pre_update_option_siteurl', [$this, 'prevent_url_change'], 10, 2);
			add_filter('pre_update_option_home', [$this, 'prevent_url_change'], 10, 2);
			
			// Add notice to General Settings page
			add_action('admin_notices', [$this, 'display_lock_notice']);
			
			// Disable the fields in the General Settings
			add_action('admin_head', [$this, 'disable_url_fields']);
		}
	}

	/**
     * Prevent URL from being changed
     */
    public function prevent_url_change($new_value, $old_value) {
        return $old_value;
    }

    /**
     * Display notice about locked URLs
     */
    public function display_lock_notice() {
        $screen = get_current_screen();
        if ($screen->id === 'options-general') {
            echo '<div class="notice notice-warning">
                    <p><strong>Notice:</strong> Site URL and WordPress Address (URL) are locked and cannot be modified for security reasons.</p>
                  </div>';
        }
    }

    /**
     * Disable URL fields and add the badge
     */
    public function disable_url_fields() {
        $screen = get_current_screen();
        if ($screen->id === 'options-general') {
            ?>
            <style>
                /* Style for the locked badge */
                .security-badge {
                    display: inline-flex;
                    align-items: center;
                    padding: 4px 8px;
                    background-color: #f0ffe6; /* Light green background */
                    border: 1px solid #4caf50; /* Green border */
                    border-radius: 12px; /* Rounded corners */
                    color: #4caf50; /* Green text */
                    font-size: 12px;
                    font-weight: bold;
                    font-family: Arial, sans-serif;
                    margin-left: 10px; /* Space between field and badge */
                }

                .security-badge .icon {
                    margin-right: 4px; /* Space between icon and text */
                }

                .security-badge .icon svg {
                    width: 14px;
                    height: 14px;
                    fill: #4caf50; /* Green icon color */
                }

                /* Disable the input fields */
                #siteurl, #home {
                    background-color: #f0f0f1;
                    pointer-events: none;
                    opacity: 0.7;
                }
            </style>
            <script type="text/javascript">
                jQuery(document).ready(function($) {
                    // Add the locked badge next to the fields
                    $('#siteurl, #home').each(function() {
                        $(this).after(`
                            <span class="security-badge">
                                <span class="icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                                        <path d="M12 2a4 4 0 0 0-4 4v4H6a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8a2 2 0 0 0-2-2h-2V6a4 4 0 0 0-4-4zm0 2a2 2 0 0 1 2 2v4h-4V6a2 2 0 0 1 2-2zm-6 8h12v8H6v-8z"/>
                                    </svg>
                                </span>
                                Locked for better security
                            </span>
                        `);
                    });
                });
            </script>
            <?php
        }
    }


}
