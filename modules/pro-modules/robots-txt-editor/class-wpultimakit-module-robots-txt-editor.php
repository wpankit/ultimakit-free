<?php
/**
 * Class UltimaKit_Module_Robots_Txt_Editor
 *
 * @since 1.0.0
 * @package    UltimaKit
 */

/**
 * Class UltimaKit_Module_Robots_Txt_Editor
 *
 * This class provides methods to control the display of dashboard widgets based on user preferences.
 * It allows users to selectively hide certain dashboard widgets to streamline their WordPress dashboard
 * experience and improve usability.
 *
 * @since 1.0.0
 */
class UltimaKit_Module_Robots_Txt_Editor extends UltimaKit_Module_Manager {
	/**
	 *
	 * @var string
	 */
	protected $ID = 'ultimakit_module_robots_txt_editor';

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
	protected $category = 'Optimizations';

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
	protected $read_more_link = 'robots-txt-editor-in-wordpress';

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
		$this->name        = __( 'Robots.txt Editor', 'ultimakit-for-wp' );
		$this->description = __( 'This tool helps you edit your robots.txt file.', 'ultimakit-for-wp' );
		$this->is_active   = $this->isModuleActive( $this->ID );

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

            add_action('admin_menu', array($this, 'add_submenu_page'));
			add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
            add_action('wp_ajax_save_robots_txt', array($this, 'save_robots_txt'));
		}

	}

    /**
     * Add submenu page
     */
    public function add_submenu_page() {
        // Check if parent menu exists
        global $submenu;
        if (!isset($submenu['wp-ultimakit-dashboard'])) {
            return;
        }

        add_submenu_page(
            'wp-ultimakit-dashboard',
            __('Robots.txt Editor', 'ultimakit-for-wp'),
            __('Robots.txt Editor', 'ultimakit-for-wp'),
            'manage_options',
            'wp-ultimakit-robots-editor',
            array($this, 'render_page')
        );
        
    }

    /**
     * Enqueue admin assets.
     *
     * @param string $hook Current admin page.
     */
    public function enqueue_admin_assets($hook) {
        
        wp_enqueue_script(
            'ultimakit-robots-txt-editor',
            plugin_dir_url(__FILE__) . 'module-script.js',
            ['jquery'],
            '1.0.0',
            true
        );

        wp_localize_script('ultimakit-robots-txt-editor', 'ultimakitRobotsTxtEditor', array(
            'nonce' => wp_create_nonce('ultimakit_robots_txt_editor_nonce'),
            'ajaxurl' => admin_url('admin-ajax.php'),
            'saveSuccess' => __('Robots.txt file updated successfully!', 'ultimakit-for-wp'),
            'saveError' => __('Error updating robots.txt file.', 'ultimakit-for-wp'),
            'saveButton' => __('Save Changes', 'ultimakit-for-wp'),
            'resetConfirm' => __('Are you sure you want to reset the robots.txt file to the default settings?', 'ultimakit-for-wp')
        ));

    }

    public function render_page() {
        $object = new UltimaKit_Helpers();
        $robots_content = $this->get_robots_content();
        ?>
        <style>
            #robots-txt-editor-settings .form-check-label{
                text-indent: unset !important;
            }
        </style>
        <div class="wrap">
			<?php $object->ultimakit_get_header(); ?>
			<div class="container bg-white text-dark p-3 mb-3">
				<!-- Nav tabs -->
				<ul class="nav nav-tabs" id="wpukTabs" role="tablist">
					<li class="nav-item" role="presentation">
						<a class="nav-link active" id="settings-tab" data-bs-toggle="tab" href="#robots-txt-editor-settings" role="tab" aria-controls="robots-txt-editor-settings" aria-selected="true"><?php echo esc_html_e( 'Robots.txt Editor', 'ultimakit-for-wp' ); ?></a>
					</li>
					
				</ul>

				<!-- Tab panes -->
				<div class="tab-content" id="wpukTabsContent">
					<div class="tab-pane fade show active" id="robots-txt-editor-settings" role="tabpanel" aria-labelledby="settings-tab">
						<!-- Your modules content here -->
						<div class="row">
                            <div class="form-group mb-3">
                                <textarea id="robots-content" class="form-control" rows="15"><?php echo esc_textarea($robots_content); ?></textarea>
                            </div>

                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="backup_robots" checked>
                                <label class="form-check-label" for="backup_robots">
                                    <?php _e('Create backup before saving', 'ultimakit-for-wp'); ?>
                                </label>
                            </div>

                            <div class="d-flex justify-content-between align-items-center">
                                <button id="save-robots" class="btn btn-primary">
                                    <?php _e('Save Changes', 'ultimakit-for-wp'); ?>
                                </button>
                                <button id="reset-robots" class="btn btn-outline-secondary">
                                    <?php _e('Reset to Default', 'ultimakit-for-wp'); ?>
                                </button>
                            </div>

                            <div id="save-message" class="alert mt-3 d-none"></div>
						</div>
					</div>

				</div>
			</div>
		</div>

        
        <?php
    }

    private function get_robots_content() {
        $robots_path = ABSPATH . 'robots.txt';
        
        if (file_exists($robots_path)) {
            return file_get_contents($robots_path);
        }

        return $this->get_default_robots_content();
    }

    private function get_default_robots_content() {
        $site_url = get_site_url();
        return "User-agent: *\nDisallow: /wp-admin/\nDisallow: /wp-includes/\n\nSitemap: {$site_url}/sitemap.xml";
    }

    public function save_robots_txt() {
        check_ajax_referer('ultimakit_robots_txt_editor_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('You do not have permission to perform this action.', 'ultimakit-for-wp'));
        }

        // A multisite network shares one physical robots.txt, so only a super admin may replace it.
        if (is_multisite() && !is_super_admin()) {
            wp_send_json_error(__('On a multisite network only a super admin can edit the robots.txt file.', 'ultimakit-for-wp'));
        }

        $content = isset($_POST['content']) ? stripslashes($_POST['content']) : '';
        // The checkbox value arrives as the string "true"/"false"; (bool) "false" would be true.
        $create_backup = isset($_POST['backup']) ? filter_var(wp_unslash($_POST['backup']), FILTER_VALIDATE_BOOLEAN) : false;
        $robots_path = ABSPATH . 'robots.txt';

        // Create backup if requested
        if ($create_backup && file_exists($robots_path)) {
            $backup_path = ABSPATH . 'robots.txt.backup-' . date('Y-m-d-H-i-s');
            copy($robots_path, $backup_path);
        }

        if (file_put_contents($robots_path, $content) !== false) {
            wp_send_json_success(__('Robots.txt file updated successfully!', 'ultimakit-for-wp'));
        } else {
            wp_send_json_error(__('Error updating robots.txt file. Please check file permissions.', 'ultimakit-for-wp'));
        }
    }

}
