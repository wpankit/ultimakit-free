<?php
/**
 * Class UltimaKit_Module_Duplicate_Post
 *
 * @since 1.0.0
 * @package    UltimaKit
 */

/**
 * Class UltimaKit_Module_Duplicate_Post
 *
 * This class provides methods to control the display of dashboard widgets based on user preferences.
 * It allows users to selectively hide certain dashboard widgets to streamline their WordPress dashboard
 * experience and improve usability.
 *
 * @since 1.0.0
 */
class UltimaKit_Module_Duplicate_Post extends UltimaKit_Module_Manager {
	/**
	 *
	 * @var string
	 */
	protected $ID = 'ultimakit_module_duplicate_post';

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
	protected $read_more_link = 'duplicate-post-in-wordpress';

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
		$this->name        = __( 'Duplicate Post (Post Meta, Terms and Taxonomies)', 'ultimakit-for-wp' );
		$this->description = __( 'This tool helps you duplicate your posts and pages with post meta, terms and taxonomies.', 'ultimakit-for-wp' );
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

            add_action('wp_ajax_duplicate_post', array($this, 'duplicate_post'));
            add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));

            // Existing constructor code...
            add_filter('post_row_actions', array($this, 'add_clone_link'), 10, 2);

            // Add duplicate link to page row actions
            add_filter('page_row_actions', array($this, 'add_clone_link'), 10, 2);

		}

	}

    public function enqueue_scripts() {
		wp_enqueue_script($this->ID, plugin_dir_url(__FILE__) . 'module-script.js', array('jquery'), ULTIMAKIT_FOR_WP_VERSION, true);
		wp_localize_script($this->ID, 'ultimakitDuplicate', array(
			'ajaxurl' => admin_url('admin-ajax.php'),
			'nonce' => wp_create_nonce('ultimakit-duplicate-post'),
			'error' => __('An error occurred while duplicating the post.', 'ultimakit-for-wp'),
			'success' => __('Post duplicated successfully!', 'ultimakit-for-wp'),
		));
	}

	public function add_clone_link($actions, $post) {
		// Check capabilities based on post type
		$can_duplicate = false;
		
		if ($post->post_type === 'page') {
			$can_duplicate = current_user_can('edit_pages', $post->ID);
		} else {
			$can_duplicate = current_user_can('edit_posts', $post->ID);
		}
	
		if ($can_duplicate) {
			$actions['duplicate'] = '<a href="#" class="clone-post" data-post-id="' . esc_attr($post->ID) . '">' . __('Duplicate', 'ultimakit-for-wp') . '</a>';
		}
		
		return $actions;
	}

	public function duplicate_post() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ultimakit-duplicate-post')) {
            wp_send_json_error(__('Invalid security token.', 'ultimakit-for-wp'));
            return;
        }

		if (!current_user_can('manage_options')) {
			wp_send_json_error(__('Permission denied.', 'ultimakit-for-wp'));
		}

        // Get the post ID
        $post_id = intval($_POST['post_id']);
        if (!$post_id) {
            wp_send_json_error(__('Invalid post ID.', 'ultimakit-for-wp'));
        }

        // Get the original post
        $post = get_post($post_id);
        if (!$post) {
            wp_send_json_error(__('Post not found.', 'ultimakit-for-wp'));
        }

        // Create a new post object
        $new_post = array(
            'post_title' => $post->post_title . ' (Copy)',
            'post_content' => $post->post_content,
            'post_status' => 'draft', // Set to draft or publish as needed
            'post_type' => $post->post_type,
            'post_excerpt' => $post->post_excerpt,
            'post_author' => $post->post_author,
            'post_date' => current_time('mysql'),
            'post_date_gmt' => current_time('mysql', 1),
        );

        // Insert the new post
        $new_post_id = wp_insert_post($new_post);
        if (is_wp_error($new_post_id)) {
            wp_send_json_error(__('Error duplicating post: ' . $new_post_id->get_error_message(), 'ultimakit-for-wp'));
        }

        // Copy post meta
        $meta = get_post_meta($post_id);
        foreach ($meta as $key => $value) {
            update_post_meta($new_post_id, $key, $value[0]);
        }

		// Copy terms and taxonomies only if it's not a page
		if ($post->post_type !== 'page') {
			$taxonomies = get_object_taxonomies($post->post_type);
			foreach ($taxonomies as $taxonomy) {
				$terms = wp_get_post_terms($post_id, $taxonomy, array('fields' => 'ids'));
				if (!empty($terms)) {
					wp_set_object_terms($new_post_id, $terms, $taxonomy);
				}
			}
		}
	
		wp_send_json_success(array(
			'message' => __('Content duplicated successfully!', 'ultimakit-for-wp'),
			'new_post_id' => $new_post_id
		));
    }


}
