<?php
/**
 * Class UltimaKit_Module_Featured_Image_Checker
 *
 * @since 1.0.0
 * @package    UltimaKit
 */

/**
 * Class UltimaKit_Module_Featured_Image_Checker
 *
 * This class provides methods to control the display of dashboard widgets based on user preferences.
 * It allows users to selectively hide certain dashboard widgets to streamline their WordPress dashboard
 * experience and improve usability.
 *
 * @since 1.0.0
 */
class UltimaKit_Module_Featured_Image_Checker extends UltimaKit_Module_Manager {
	/**
	 *
	 * @var string
	 */
	protected $ID = 'ultimakit_module_featured_image_checker';

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
	protected $read_more_link = 'featured-image-checker-in-wordpress';

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
		$this->name        = __( 'Featured Image Checker', 'ultimakit-for-wp' );
		$this->description = __( 'Scan your content for missing featured images.', 'ultimakit-for-wp' );
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
            add_action('wp_ajax_scan_missing_featured_images', [$this, 'scan_missing_featured_images']);
            add_action('wp_ajax_get_missing_featured_images', [$this, 'get_missing_featured_images']);
		}

	}

    /**
     * Enqueue admin assets.
     *
     * @param string $hook Current admin page.
     */
    public function enqueue_admin_assets($hook) {
        if ($hook !== 'ultimakit-for-wp_page_wp-ultimakit-featured-images') {
            return;
        }

        wp_enqueue_script(
            $this->ID,
            plugin_dir_url(__FILE__) . 'module-script.js',
            ['jquery'],
            ULTIMAKIT_FOR_WP_VERSION,
            true
        );

        wp_localize_script($this->ID, 'ultimakitFeaturedImage', array(
            'nonce' => wp_create_nonce('ultimakit_featured_image_nonce'),
            'ajaxurl' => admin_url('admin-ajax.php'),
            'scanning' => __('Scanning...', 'ultimakit-for-wp'),
            'scanComplete' => __('Scan complete!', 'ultimakit-for-wp'),
            'error' => __('An error occurred.', 'ultimakit-for-wp')
        ));
        

    }

    public function add_submenu_page() {
        add_submenu_page(
            'wp-ultimakit-dashboard',
            __('Featured Image Checker', 'ultimakit-for-wp'),
            __('Featured Image Checker', 'ultimakit-for-wp'),
            'manage_options',
            'wp-ultimakit-featured-images',
            array($this, 'render_page')
        );
    }


    public function render_page() {
        $object = new UltimaKit_Helpers();
        ?>

        <div class="wrap">
			<?php $object->ultimakit_get_header(); ?>
			<div class="container bg-white text-dark p-3 mb-3">
				<!-- Nav tabs -->
				<ul class="nav nav-tabs" id="wpukTabs" role="tablist">
					<li class="nav-item" role="presentation">
						<a class="nav-link active" id="settings-tab" data-bs-toggle="tab" href="#featured-image-checker-settings" role="tab" aria-controls="featured-image-checker-settings" aria-selected="true"><?php echo esc_html_e( 'Featured Image Checker', 'ultimakit-for-wp' ); ?></a>
					</li>
					
				</ul>

				<!-- Tab panes -->
				<div class="tab-content" id="wpukTabsContent">
					<div class="tab-pane fade show active" id="robots-txt-editor-settings" role="tabpanel" aria-labelledby="settings-tab">
						<!-- Your modules content here -->
						<div class="row">
                            <p class="mb-4"><?php _e('Scan your content for missing featured images.', 'ultimakit-for-wp'); ?></p>
                            
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <button id="scan-images" class="btn btn-primary">
                                    <?php _e('Scan for Missing Images', 'ultimakit-for-wp'); ?>
                                </button>
                                <div id="scan-progress" class="d-none">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden"><?php _e('Scanning...', 'ultimakit-for-wp'); ?></span>
                                    </div>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th><?php _e('Title', 'ultimakit-for-wp'); ?></th>
                                            <th><?php _e('Type', 'ultimakit-for-wp'); ?></th>
                                            <th><?php _e('Author', 'ultimakit-for-wp'); ?></th>
                                            <th><?php _e('Date', 'ultimakit-for-wp'); ?></th>
                                            <th><?php _e('Actions', 'ultimakit-for-wp'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody id="results-body">
                                        <tr>
                                            <td colspan="5" class="text-center">
                                                <?php _e('Click "Scan for Missing Images" to begin.', 'ultimakit-for-wp'); ?>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                                <div id="pagination" class="d-none">
                                    <nav aria-label="Page navigation">
                                        <ul class="pagination justify-content-center">
                                        </ul>
                                    </nav>
                                </div>
                            </div>

						</div>
					</div>

				</div>
			</div>
		</div>

       
        <?php
    }

    public function scan_missing_featured_images() {
        check_ajax_referer('ultimakit_featured_image_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permission denied.', 'ultimakit-for-wp'));
        }

        $post_types = ['post', 'page'];
        $posts_without_images = [];

        foreach ($post_types as $post_type) {
            $args = array(
                'post_type' => $post_type,
                'posts_per_page' => -1,
                'post_status' => 'publish'
            );

            $query = new WP_Query($args);

            while ($query->have_posts()) {
                $query->the_post();
                if (!has_post_thumbnail()) {
                    $posts_without_images[] = array(
                        'ID' => get_the_ID(),
                        'post_type' => $post_type
                    );
                }
            }
        }

        wp_reset_postdata();

        update_option('ultimakit_missing_featured_images', $posts_without_images);
        wp_send_json_success(array(
            'count' => count($posts_without_images)
        ));
    }

    public function get_missing_featured_images() {
        check_ajax_referer('ultimakit_featured_image_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permission denied.', 'ultimakit-for-wp'));
        }

        $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $per_page = 20;
        
        $posts_without_images = get_option('ultimakit_missing_featured_images', array());
        $total_items = count($posts_without_images);
        $total_pages = ceil($total_items / $per_page);
        
        $offset = ($page - 1) * $per_page;
        $items = array_slice($posts_without_images, $offset, $per_page);
        
        $results = array();
        foreach ($items as $item) {
            $post = get_post($item['ID']);
            $results[] = array(
                'ID' => $post->ID,
                'title' => $post->post_title,
                'type' => $item['post_type'],
                'author' => get_the_author_meta('display_name', $post->post_author),
                'date' => get_the_date('Y-m-d', $post),
                'edit_url' => get_edit_post_link($post->ID, 'raw')
            );
        }

        wp_send_json_success(array(
            'items' => $results,
            'total_pages' => $total_pages,
            'current_page' => $page
        ));
    }


}
