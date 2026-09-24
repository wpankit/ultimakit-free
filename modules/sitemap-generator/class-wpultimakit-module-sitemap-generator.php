<?php
/**
 * Class UltimaKit_Module_Sitemap_Generator
 *
 * @since 1.0.0
 * @package    UltimaKit
 */

/**
 * Class UltimaKit_Module_Sitemap_Generator
 *
 * This class provides methods to control the display of dashboard widgets based on user preferences.
 * It allows users to selectively hide certain dashboard widgets to streamline their WordPress dashboard
 * experience and improve usability.
 *
 * @since 1.0.0
 */
class UltimaKit_Module_Sitemap_Generator extends UltimaKit_Module_Manager {
	/**
	 *
	 * @var string
	 */
	protected $ID = 'ultimakit_module_sitemap_generator';

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
	protected $read_more_link = 'sitemap-generator-in-wordpress';

	/**
	 * The settings associated with the module, if any.
	 *
	 * @var array
	 */
	protected $settings;

    private $excluded_items = array();

	/**
	 *
	 * Initializes the module with default values for properties and prepares
	 * any necessary setup or hooks into WordPress. This may include setting
	 * initial values, registering hooks, or preparing resources needed for
	 * the module to function properly within WordPress.
	 */
	public function __construct() {
		$this->name        = __( 'Sitemap Generator', 'ultimakit-for-wp' );
		$this->description = __( 'This tool helps you generate a sitemap.xml file.', 'ultimakit-for-wp' );
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

            // Add admin menu
            add_action('admin_menu', array($this, 'add_admin_menu'),20);
            
            // Enqueue scripts and styles
            add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
            
            // Add custom schedule interval
            add_filter('cron_schedules', array($this, 'add_cron_interval'));
            
            // AJAX handlers
            add_action('wp_ajax_ultimakit_save_sitemap_settings', array($this, 'ajax_save_settings'));
            add_action('wp_ajax_ultimakit_generate_sitemap', array($this, 'ajax_generate_sitemap'));
            add_action('wp_ajax_ultimakit_delete_sitemap', array($this, 'ajax_delete_sitemap'));

            // Check and setup the schedule
            $this->setup_schedule();

            // Add custom cron schedules
            add_filter('cron_schedules', array($this, 'add_cron_schedules'));

            // Add the custom schedule hook
            add_action('ultimakit_generate_sitemap', array($this, 'generate_sitemap'));
		}

	}

    /**
     * Add submenu page
     */
    public function add_admin_menu() {
        // Check if parent menu exists
        global $submenu;
        if (!isset($submenu['wp-ultimakit-dashboard'])) {
            return;
        }
       
        add_submenu_page(
            'wp-ultimakit-dashboard',
            __('Sitemap Generator', 'ultimakit-for-wp'),
            __('Sitemap Generator', 'ultimakit-for-wp'),
            'manage_options',
            'wp-ultimakit-sitemap-generator',
            array($this, 'render_admin_page')
        );
    }

   
    public function enqueue_assets() {
        // Only load on our settings page
        $screen = get_current_screen();
        
        if ($screen && $screen->id === 'ultimakit-for-wp_page_wp-ultimakit-sitemap-generator') {
        
            wp_enqueue_script(
                $this->ID,
                plugin_dir_url(__FILE__) . 'module-script.js',
                array('jquery'),
                '1.0.0',
                true
            );
    
            // Add ajax url for our JavaScript
            wp_localize_script(
                $this->ID,
                'ultimakitSitemap',
                array(
                    'ajaxurl' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('ultimakit_generate_sitemap'),
                    'error_saving_settings' => __('Error saving settings. Please try again.', 'ultimakit-for-wp'),
                    'error_generating_sitemap' => __('Error generating sitemap. Please try again.', 'ultimakit-for-wp'),
                    'error_deleting_sitemap' => __('Error deleting sitemap. Please try again.', 'ultimakit-for-wp'),
                )
            );
    
            // Enqueue styles
            wp_enqueue_style(
                $this->ID,
                plugin_dir_url(__FILE__) . 'module-style.css',
                array(),
                '1.0.0'
            );
        }
    }

    public function render_admin_page() {
        $excluded_items = $this->getModuleSettings( $this->ID, 'excluded_items', array() );
        $current_frequency = $this->getModuleSettings( $this->ID, 'frequency', 'daily' );

        $object = new UltimaKit_Helpers();
        ?>
        <div class="wrap">
			<?php $object->ultimakit_get_header(); ?>
			<div class="container bg-white text-dark p-3 mb-3">
				<!-- Nav tabs -->
				<ul class="nav nav-tabs" id="wpukTabs" role="tablist">
					<li class="nav-item" role="presentation">
						<a class="nav-link active" id="settings-tab" data-bs-toggle="tab" href="#sitemap-generator-settings" role="tab" aria-controls="sitemap-generator-settings" aria-selected="true"><?php echo esc_html_e( 'Sitemap Generator', 'ultimakit-for-wp' ); ?></a>
					</li>
					
				</ul>

				<!-- Tab panes -->
				<div class="tab-content" id="wpukTabsContent">
					<div class="tab-pane fade show active" id="sitemap-generator-settings" role="tabpanel" aria-labelledby="settings-tab">
						<!-- Your modules content here -->
						<div class="row">
                            
                            <div id="ultimakit-sitemap-settings">

                                <div class="sitemap-frequency">
                                    <h6><?php _e('Sitemap Update Frequency', 'ultimakit-for-wp'); ?></h6>
                                    <select id="sitemap-frequency" name="sitemap_frequency">
                                        <option value="hourly" <?php echo ($current_frequency === 'hourly') ? 'selected' : ''; ?>>
                                            <?php _e('Hourly', 'ultimakit-for-wp'); ?>
                                        </option>
                                        <option value="daily" <?php echo ($current_frequency === 'daily') ? 'selected' : ''; ?>>
                                            <?php _e('Daily', 'ultimakit-for-wp'); ?>
                                        </option>
                                        <option value="weekly" <?php echo ($current_frequency === 'weekly') ? 'selected' : ''; ?>>
                                            <?php _e('Weekly', 'ultimakit-for-wp'); ?>
                                        </option>
                                        <option value="monthly" <?php echo ($current_frequency === 'monthly') ? 'selected' : ''; ?>>
                                            <?php _e('Monthly', 'ultimakit-for-wp'); ?>
                                        </option>
                                    </select>
                                </div>
                                
                                <form id="ultimakit-sitemap-form">
                                    <div id="sitemap-items">
                                        <div class="sitemap-section">
                                            <h5><?php _e('Posts', 'ultimakit-for-wp'); ?></h5>
                                            <div class="checkbox-grid">
                                                <?php
                                                $posts = get_posts(array('posts_per_page' => -1));
                                                foreach ($posts as $post) {
                                                    $checked = in_array('post_' . $post->ID, $excluded_items) ? 'checked' : '';
                                                    echo '<label class="checkbox-item">';
                                                    echo '<input type="checkbox" name="excluded_items[]" value="post_' . $post->ID . '" ' . $checked . '>';
                                                    echo '<span class="checkbox-label">' . esc_html($post->post_title) . '</span>';
                                                    echo '</label>';
                                                }
                                                ?>
                                            </div>
                                        </div>

                                        <div class="sitemap-section">
                                            <h5><?php _e('Pages', 'ultimakit-for-wp'); ?></h5>
                                            <div class="checkbox-grid">
                                                <?php
                                                $pages = get_pages();
                                                foreach ($pages as $page) {
                                                    $checked = in_array('page_' . $page->ID, $excluded_items) ? 'checked' : '';
                                                    echo '<label class="checkbox-item">';
                                                    echo '<input type="checkbox" name="excluded_items[]" value="page_' . $page->ID . '" ' . $checked . '>';
                                                    echo '<span class="checkbox-label">' . esc_html($page->post_title) . '</span>';
                                                    echo '</label>';
                                                }
                                                ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="submit-button">
                                        <button type="submit" id="save-sitemap-settings" class="btn btn-primary"><?php _e('Save Settings', 'ultimakit-for-wp'); ?></button>
                                        <button type="button" id="generate-sitemap" class="btn btn-success"><?php _e('Generate Sitemap', 'ultimakit-for-wp'); ?></button>
                                        <button type="button" id="delete-sitemap" class="btn btn-danger" <?php echo !file_exists(ABSPATH . 'sitemap.xml') ? 'disabled' : ''; ?>><?php _e('Delete Sitemap', 'ultimakit-for-wp'); ?></button>
                                    </div>
                                </form>
                            </div>

						</div>
					</div>

				</div>
			</div>
		</div>
        <?php
    }

    public function add_cron_interval($schedules) {
        $schedules['weekly'] = array(
            'interval' => 604800,
            'display' => __('Once Weekly', 'ultimakit-for-wp')
        );
        return $schedules;
    }

    public function ajax_generate_sitemap() {
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array(
                'message' => __('Permission denied', 'ultimakit-for-wp')
            ), 403);
            wp_die();
        }

        // Verify nonce
        if (!check_ajax_referer('ultimakit_generate_sitemap', 'nonce', false)) {
            wp_send_json_error(array(
                'message' => __('Invalid security token', 'ultimakit-for-wp')
            ), 401);
            wp_die();
        }
    
        // Generate sitemap
        $this->generate_sitemap();
    
        wp_send_json_success(__('Sitemap generated successfully', 'ultimakit-for-wp'));
    }

    public function generate_sitemap() {

        $upload_dir = wp_upload_dir();
        $sitemap_path = $upload_dir['basedir'] . '/sitemap.xml';

        // Try to delete existing sitemap
        if (!$this->delete_existing_sitemap($sitemap_path)) {
            // Handle deletion failure
            return array(
                'success' => false,
                'message' => __('Could not delete existing sitemap file. Please check file permissions.', 'ultimakit-for-wp')
            );
        }

        
        $excluded_items = $this->getModuleSettings( $this->ID, 'excluded_items', array() );
        
        $xml = new DOMDocument('1.0', 'UTF-8');
        $xml->formatOutput = true;
    
        $urlset = $xml->createElement('urlset');
        $urlset->setAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');
        $xml->appendChild($urlset);
    
        // Add homepage
        $this->add_url($xml, $urlset, home_url('/'));
    
        // Add posts
        $this->add_posts_to_sitemap($xml, $urlset, $excluded_items);
    
        // Add pages
        $this->add_pages_to_sitemap($xml, $urlset, $excluded_items);
    
        // Add categories
        $this->add_categories_to_sitemap($xml, $urlset, $excluded_items);
    
        // Add tags
        $this->add_tags_to_sitemap($xml, $urlset, $excluded_items);
    
        // Add custom post types
        $this->add_custom_post_types_to_sitemap($xml, $urlset, $excluded_items);
    
        // Save sitemap
        $xml->save(ABSPATH . 'sitemap.xml');
    }
    
    private function add_posts_to_sitemap($xml, $urlset, $excluded_items) {
        $posts = get_posts(array('posts_per_page' => -1));
        foreach ($posts as $post) {
            if (!in_array('post_' . $post->ID, $excluded_items)) {
                $this->add_url($xml, $urlset, get_permalink($post));
            }
        }
    }
    
    private function add_pages_to_sitemap($xml, $urlset, $excluded_items) {
        $pages = get_pages();
        foreach ($pages as $page) {
            if (!in_array('page_' . $page->ID, $excluded_items)) {
                $this->add_url($xml, $urlset, get_permalink($page));
            }
        }
    }
    
    private function add_categories_to_sitemap($xml, $urlset, $excluded_items) {
        $categories = get_categories(array('hide_empty' => false));
        foreach ($categories as $category) {
            if (!in_array('category_' . $category->term_id, $excluded_items)) {
                $this->add_url($xml, $urlset, get_category_link($category));
            }
        }
    }
    
    private function add_tags_to_sitemap($xml, $urlset, $excluded_items) {
        $tags = get_tags(array('hide_empty' => false));
        foreach ($tags as $tag) {
            if (!in_array('tag_' . $tag->term_id, $excluded_items)) {
                $this->add_url($xml, $urlset, get_tag_link($tag));
            }
        }
    }
    
    private function add_custom_post_types_to_sitemap($xml, $urlset, $excluded_items) {
        $post_types = get_post_types(array('public' => true), 'objects');
        foreach ($post_types as $post_type) {
            if ($post_type->name !== 'post' && $post_type->name !== 'page') {
                $posts = get_posts(array('post_type' => $post_type->name, 'posts_per_page' => -1));
                foreach ($posts as $post) {
                    if (!in_array($post_type->name . '_' . $post->ID, $excluded_items)) {
                        $this->add_url($xml, $urlset, get_permalink($post));
                    }
                }
            }
        }
    }

    private function add_url($xml, $urlset, $url) {
        $url_element = $xml->createElement('url');
        
        $loc = $xml->createElement('loc', esc_url($url));
        $url_element->appendChild($loc);
        
        $lastmod = $xml->createElement('lastmod', date('Y-m-d'));
        $url_element->appendChild($lastmod);
        
        // Set change frequency and priority based on your logic
        $changefreq = $xml->createElement('changefreq', 'weekly'); // Example: set to weekly
        $url_element->appendChild($changefreq);
        
        $priority = $xml->createElement('priority', '0.5'); // Example: set to 0.5
        $url_element->appendChild($priority);
        
        $urlset->appendChild($url_element);
    }


    public function ajax_save_settings() {

        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array(
                'message' => __('Permission denied', 'ultimakit-for-wp')
            ), 403);
            wp_die();
        }

        // Verify nonce
        if (!check_ajax_referer('ultimakit_generate_sitemap', 'nonce', false)) {
            wp_send_json_error(array(
                'message' => __('Invalid security token', 'ultimakit-for-wp')
            ), 401);
            wp_die();
        }

        $settings = array(
            'excluded_items' => isset($_POST['excluded_items']) ? array_map('sanitize_text_field', $_POST['excluded_items']) : array(),
            'frequency' => isset($_POST['frequency']) ? sanitize_text_field($_POST['frequency']) : 'daily'
        );

		$result = $this->ultimakit_update_module_setting( $this->ID, 'settings', $settings, false );

        if ($result !== false) {
            wp_send_json_success(__('Settings saved successfully', 'ultimakit-for-wp'));
        } else {
            wp_send_json_error(__('Error saving settings', 'ultimakit-for-wp')  );
        }
    }

    /**
     * AJAX handler for deleting the sitemap file
     *
     * @return void
     */
    public function ajax_delete_sitemap() {
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array(
                'message' => __('Permission denied', 'ultimakit-for-wp')
            ), 403);
            wp_die();
        }

        // Verify nonce
        if (!check_ajax_referer('ultimakit_generate_sitemap', 'nonce', false)) {
            wp_send_json_error(array(
                'message' => __('Invalid security token', 'ultimakit-for-wp')
            ), 401);
            wp_die();
        }

        $sitemap_path = ABSPATH . 'sitemap.xml';

        // Check if sitemap exists
        if (!file_exists($sitemap_path)) {
            wp_send_json_error(__('Sitemap file does not exist.', 'ultimakit-for-wp'));
        }
        
        // Try to delete the file
        if ($this->delete_existing_sitemap($sitemap_path)) {
            wp_send_json_success(__('Sitemap deleted successfully.', 'ultimakit-for-wp'));
        } else {
            wp_send_json_error(__('Could not delete sitemap file. Please check file permissions.', 'ultimakit-for-wp'));
        }
    }

    /**
     * Delete existing sitemap file
     * 
     * @param string $sitemap_path Full path to the sitemap file
     * @return bool True if deletion successful or file doesn't exist, false if deletion fails
     */
    private function delete_existing_sitemap($sitemap_path) {
        // Check if file exists
        if (!file_exists($sitemap_path)) {
            return true; // File doesn't exist, so we can consider this successful
        }

        // Check if file is writable
        if (!is_writable($sitemap_path)) {
            // Try to make the file writable
            if (!chmod($sitemap_path, 0644)) {
                error_log('UltimaKit Sitemap: Unable to modify file permissions for ' . $sitemap_path);
                return false;
            }
        }

        try {
            // Attempt to delete the file
            if (@unlink($sitemap_path)) {
                return true;
            } else {
                // Log the error if deletion fails
                $error = error_get_last();
                error_log('UltimaKit Sitemap: Failed to delete sitemap file. Error: ' . ($error ? $error['message'] : 'Unknown error'));
                return false;
            }
        } catch (Exception $e) {
            error_log('UltimaKit Sitemap: Exception while deleting sitemap - ' . $e->getMessage());
            return false;
        }
    }

    // Method to handle schedule setup
    public function setup_schedule() {
        // Get the current frequency setting
        $frequency = $this->getModuleSettings($this->ID, 'frequency', 'daily');
        
        // Get the timestamp of the next scheduled event
        $next_scheduled = wp_next_scheduled('ultimakit_generate_sitemap');
        
        // Clear existing schedule if it exists
        if ($next_scheduled) {
            wp_unschedule_event($next_scheduled, 'ultimakit_generate_sitemap');
        }
        
        // Schedule new event
        if (!wp_next_scheduled('ultimakit_generate_sitemap')) {
            wp_schedule_event(time(), $frequency, 'ultimakit_generate_sitemap');
        }
    }

    // Add this method to handle schedule changes when settings are saved
    public function update_schedule($new_frequency) {
        // Clear existing schedule
        $next_scheduled = wp_next_scheduled('ultimakit_generate_sitemap');
        if ($next_scheduled) {
            wp_unschedule_event($next_scheduled, 'ultimakit_generate_sitemap');
        }
        
        // Set up new schedule
        wp_schedule_event(time(), $new_frequency, 'ultimakit_generate_sitemap');
    }

    // Add custom cron schedules if needed
    public function add_cron_schedules($schedules) {
        if (!isset($schedules['weekly'])) {
            $schedules['weekly'] = array(
                'interval' => 7 * 24 * 60 * 60,
                'display' => __('Once Weekly', 'ultimakit-for-wp')
            );
        }
        
        if (!isset($schedules['monthly'])) {
            $schedules['monthly'] = array(
                'interval' => 30 * 24 * 60 * 60,
                'display' => __('Once Monthly', 'ultimakit-for-wp')
            );
        }
        
        return $schedules;
    }

}
