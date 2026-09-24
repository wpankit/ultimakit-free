<?php
/**
 * Class UltimaKit_Module_Auto_Link_Keywords    
 *
 * @since 1.0.0
 * @package    UltimaKit
 */

/**
 * Class UltimaKit_Module_Auto_Link_Keywords
 *
 * This class provides methods to control the display of dashboard widgets based on user preferences.
 * It allows users to selectively hide certain dashboard widgets to streamline their WordPress dashboard
 * experience and improve usability.
 *
 * @since 1.0.0
 */
class UltimaKit_Module_Auto_Link_Keywords extends UltimaKit_Module_Manager {
	/**
	 *
	 * @var string
	 */
	protected $ID = 'ultimakit_module_auto_link_keywords';

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
	protected $category = 'Content Management';

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
	protected $read_more_link = 'get-readability-score-in-wordpress';

	/**
	 * The settings associated with the module, if any.
	 *
	 * @var array
	 */
	protected $settings;

    /**
     * The option name for storing keywords data.
     *
     * @var string
     */
    private $option_name = 'ultimakit_auto_link_keywords';

	/**
	 *
	 * Initializes the module with default values for properties and prepares
	 * any necessary setup or hooks into WordPress. This may include setting
	 * initial values, registering hooks, or preparing resources needed for
	 * the module to function properly within WordPress.
	 */
	public function __construct() {
		$this->name        = __( 'Auto Link Keywords', 'ultimakit-for-wp' );
		$this->description = __( 'This tool helps you auto link keywords in your content.', 'ultimakit-for-wp' );
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

            add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
            add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
            add_action('wp_ajax_save_keyword', array($this, 'ajax_save_keyword'));
            add_action('wp_ajax_delete_keyword', array($this, 'ajax_delete_keyword'));
            // Priority 12: after do_shortcode (11), so shortcode text is never rewritten.
            add_filter('the_content', array($this, 'process_content'), 12);

		}

	}


    /**
     * Add menu item.
     */
    public function add_admin_menu() {
        add_submenu_page(
            'wp-ultimakit-dashboard',
            __('Auto-Link Keywords', 'ultimakit-for-wp'),
            __('Auto-Link Keywords', 'ultimakit-for-wp'),
            'manage_options',
            'wp-ultimakit-auto-link-keywords',
            array($this, 'render_admin_page')
        );
    }

    /**
     * Enqueue admin assets.
     */
    public function enqueue_admin_assets($hook) {
        
        if ('ultimakit-for-wp_page_wp-ultimakit-auto-link-keywords' !== $hook) {
            return;
        }

        wp_enqueue_style(
            'ultimakit-auto-link-keywords',
            plugin_dir_url(__FILE__) . 'module-style.css',
            array(),
            '1.0.0'
        );

        wp_enqueue_script(
            'ultimakit-auto-link-keywords',
            plugin_dir_url(__FILE__) . 'module-script.js',
            array('jquery'),
            '1.0.0',
            true
        );

        wp_localize_script('ultimakit-auto-link-keywords', 'ultimakitAutoLink', array(
			'ajaxurl' => admin_url('admin-ajax.php'),
			'nonce' => wp_create_nonce('ultimakit-auto-link-keywords'),
			'messages' => array(
				'success' => __('Keyword saved successfully.', 'ultimakit-for-wp'),
				'deleted' => __('Keyword deleted successfully.', 'ultimakit-for-wp'),
				'error' => __('An error occurred. Please try again.', 'ultimakit-for-wp'),
				'delete_keyword_confirm_message' => __('Are you sure you want to delete this keyword?', 'ultimakit-for-wp'),
				'delete_keyword_error_message' => __('Error deleting keyword', 'ultimakit-for-wp')
			)
		));
    }

    /**
     * Render admin page.
     */
    public function render_admin_page() {
        $keywords = $this->get_keywords();
        $object = new UltimaKit_Helpers();
        ?>
        <div class="wrap">
			<?php $object->ultimakit_get_header(); ?>
			<div class="container bg-white text-dark p-3 mb-3">
				<!-- Nav tabs -->
				<ul class="nav nav-tabs" id="wpukTabs" role="tablist">
					<li class="nav-item" role="presentation">
						<a class="nav-link active" id="settings-tab" data-bs-toggle="tab" href="#auto-link-keywords-settings" role="tab" aria-controls="auto-link-keywords-settings" aria-selected="true"><?php echo esc_html_e( 'Auto Link Keywords', 'ultimakit-for-wp' ); ?></a>
					</li>
					
				</ul>

				<!-- Tab panes -->
				<div class="tab-content" id="wpukTabsContent">
					<div class="tab-pane fade show active" id="auto-link-keywords-settings" role="tabpanel" aria-labelledby="settings-tab">
						<!-- Your modules content here -->
						<div class="row">
                            <div class="keyword-form">
                                <form id="add-keyword-form">
                                    <table class="form-table">
                                        <tr>
                                            <th><label for="keyword"><?php _e('Keyword', 'ultimakit-for-wp'); ?></label></th>
                                            <td><input type="text" id="keyword" name="keyword" required></td>
                                        </tr>
                                        <tr>
                                            <th><label for="url"><?php _e('URL', 'ultimakit-for-wp'); ?></label></th>
                                            <td><input type="url" id="url" name="url" required></td>
                                        </tr>
                                        <tr>
                                            <th><label for="limit"><?php _e('Link Limit', 'ultimakit-for-wp'); ?></label></th>
                                            <td>
                                                <input type="number" id="limit" name="limit" min="0" value="0">
                                                <p class="description"><?php _e('0 = unlimited', 'ultimakit-for-wp'); ?></p>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th><label for="case_sensitive"><?php _e('Case Sensitive', 'ultimakit-for-wp'); ?></label></th>
                                            <td><input type="checkbox" id="case_sensitive" name="case_sensitive"></td>
                                        </tr>
                                    </table>
                                    <button type="submit" class="btn btn-success bg-primary"><?php _e('Add Keyword', 'ultimakit-for-wp'); ?></button>
                                </form>
                            </div>

                            <div class="keyword-list mt-3">
                                <h4><?php _e('Existing Keywords', 'ultimakit-for-wp'); ?></h4>
                                <table class="table table-striped table-bordered">
                                    <thead>
                                        <tr>
                                            <th><?php _e('Keyword', 'ultimakit-for-wp'); ?></th>
                                            <th><?php _e('URL', 'ultimakit-for-wp'); ?></th>
                                            <th><?php _e('Link Limit', 'ultimakit-for-wp'); ?></th>
                                            <th><?php _e('Case Sensitive', 'ultimakit-for-wp'); ?></th>
                                            <th><?php _e('Actions', 'ultimakit-for-wp'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($keywords as $id => $data) : ?>
                                        <tr>
                                            <td><?php echo esc_html($data['keyword']); ?></td>
                                            <td><?php echo esc_url($data['url']); ?></td>
                                            <td><?php echo intval($data['limit']); ?></td>
                                            <td><?php echo $data['case_sensitive'] ? 'Yes' : 'No'; ?></td>
                                            <td>
                                                <button class="btn btn-warning delete-keyword" data-id="<?php echo esc_attr($id); ?>"><?php _e('Delete', 'ultimakit-for-wp'); ?></button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
						</div>
					</div>
				</div>
			</div>
		</div>

        <?php
    }

    /**
     * Get all keywords.
     *
     * @return array
     */
    private function get_keywords() {
        return get_option($this->option_name, array());
    }

    /**
     * Save keyword via AJAX.
     */
    public function ajax_save_keyword() {
		// Verify nonce first
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ultimakit-auto-link-keywords')) {
            wp_send_json_error('Invalid security token.');
            return;
        }
	
		if (!current_user_can('manage_options')) {
			wp_send_json_error('Permission denied');
		}
	
		$keyword_id = isset($_POST['keyword_id']) ? sanitize_text_field($_POST['keyword_id']) : '';
		$keyword = sanitize_text_field($_POST['keyword']);
		$url = esc_url_raw($_POST['url']);
		$limit = intval($_POST['limit']);
		$case_sensitive = isset($_POST['case_sensitive']) && $_POST['case_sensitive'] === 'true';
	
		if (empty($keyword) || empty($url)) {
			wp_send_json_error('Invalid input');
		}
	
		$keywords = $this->get_keywords();
		
		if (empty($keyword_id)) {
			$keyword_id = uniqid();
		}
		
		$keywords[$keyword_id] = array(
			'id' => $keyword_id,
			'keyword' => $keyword,
			'url' => $url,
			'limit' => $limit,
			'case_sensitive' => $case_sensitive
		);
	
		update_option($this->option_name, $keywords);
		
		wp_send_json_success(array(
			'keyword' => $keywords[$keyword_id]
		));
	}

    /**
     * Delete keyword via AJAX.
     */
    public function ajax_delete_keyword() {
        // Verify nonce first
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ultimakit-auto-link-keywords')) {
            wp_send_json_error(array(
                'success' => false,
                'message' => 'Invalid security token.'
            ));
            return;
        }
	
		if (!current_user_can('manage_options')) {
			wp_send_json_error(array(
                'success' => false,
                'message' => 'Permission denied'
            ));
			return;
		}

        $id = sanitize_text_field($_POST['id']);
        $keywords = $this->get_keywords();

        if (isset($keywords[$id])) {
            unset($keywords[$id]);
            update_option($this->option_name, $keywords);
            wp_send_json_success();
        }

        wp_send_json_error(array(
            'success' => false,
            'message' => 'Keyword not found'
        ));
    }

    /**
     * Process content and add links.
     *
     * @param string $content The post content
     * @return string
     */
    public function process_content($content) {
        if (!is_singular() || empty($content)) {
            return $content;
        }

        $keywords = $this->get_keywords();

        // Existing links, comments, script/style/textarea bodies and tags (with their attributes)
        // are left untouched: keywords are only linked in the text between them.
        $protected_pattern = '#(<!--.*?-->|<a\b[^>]*>.*?</a\s*>|<script\b[^>]*>.*?</script\s*>|<style\b[^>]*>.*?</style\s*>|<textarea\b[^>]*>.*?</textarea\s*>|<[^>]+>)#is';

        foreach ($keywords as $data) {
            $keyword = $data['keyword'];
            $url = $data['url'];
            $limit = $data['limit'];
            $case_sensitive = $data['case_sensitive'];
            
            // Skip empty keywords or URLs
            if (empty($keyword) || empty($url)) {
                continue;
            }

            // Prepare the regex pattern
            $pattern = $case_sensitive 
                ? '/\b(' . preg_quote($keyword, '/') . ')\b(?![^<]*>)/'
                : '/\b(' . preg_quote($keyword, '/') . ')\b(?![^<]*>)/i';

            // Create replacement link
            $replacement = '<a href="' . esc_url($url) . '" title="' . esc_attr($keyword) . '">$1</a>';

            // Split again for every keyword so links added for earlier keywords are protected too.
            $parts = preg_split($protected_pattern, $content, -1, PREG_SPLIT_DELIM_CAPTURE);
            if (false === $parts) {
                continue;
            }

            // Apply replacement with limit (0 = unlimited) to the text segments only (even indexes).
            $remaining = $limit > 0 ? $limit : -1;
            foreach ($parts as $index => $part) {
                if (1 === $index % 2 || '' === $part) {
                    continue;
                }

                $replaced = preg_replace($pattern, $replacement, $part, $remaining, $count);
                if (null !== $replaced) {
                    $parts[$index] = $replaced;
                }

                if ($remaining > 0) {
                    $remaining -= $count;
                    if ($remaining <= 0) {
                        break;
                    }
                }
            }

            $content = implode('', $parts);
        }

        return $content;
    }


}
