<?php
/**
 * Class UltimaKit_Module_Post_Publish_Checklist
 *
 * @since 1.0.0
 * @package    UltimaKit
 */

/**
 * Class UltimaKit_Module_Post_Publish_Checklist
 *
 * This class provides methods to control the display of dashboard widgets based on user preferences.
 * It allows users to selectively hide certain dashboard widgets to streamline their WordPress dashboard
 * experience and improve usability.
 *
 * @since 1.0.0
 */
class UltimaKit_Module_Post_Publish_Checklist extends UltimaKit_Module_Manager {
	/**
	 *
	 * @var string
	 */
	protected $ID = 'ultimakit_module_post_publish_checklist';

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
	protected $type = 'Content & SEO';

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
	protected $read_more_link = 'post-publish-checklist-in-wordpress';

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
		$this->name        = __( 'Post Publish Checklist', 'ultimakit-for-wp' );
		$this->description = __( 'This module lets you create a post publish checklist to ensure your posts are ready to publish.', 'ultimakit-for-wp' );
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

			// Add meta box for the checklist
			add_action('add_meta_boxes', array($this, 'add_checklist_meta_box'));
        
			// Save checklist items
			add_action('save_post', array($this, 'save_checklist'));
			
			// Enqueue scripts and styles
			add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));

		}

	}


	public function add_checklist_meta_box() {
        add_meta_box(
            'post-publish-checklist',
            __('Post-Publish Checklist', 'ultimakit-for-wp'),
            array($this, 'render_meta_box'),
            array('post', 'page'),
            'side',
            'high'
        );
    }

    public function render_meta_box($post) {
        // Get the saved checklist items
        $checklist_items = get_post_meta($post->ID, '_post_publish_checklist', true);
        $checklist_items = $checklist_items ? json_decode($checklist_items, true) : [];
        ?>
        <div id="post-publish-checklist-container">
            <ul id="checklist-items">
                <?php
                $default_items = [
                    __('Add meta tags', 'ultimakit-for-wp'),
                    __('Set featured image', 'ultimakit-for-wp'),
                    __('Proofread content', 'ultimakit-for-wp'),
                    __('Check links', 'ultimakit-for-wp'),
                    __('Preview post', 'ultimakit-for-wp'),
                ];
                foreach ($default_items as $item) {
                    $checked = in_array($item, $checklist_items) ? 'checked' : '';
                    echo '<li><label><input type="checkbox" name="checklist_items[]" value="' . esc_attr($item) . '" ' . $checked . '> ' . esc_html($item) . '</label></li>';
                }
                ?>
            </ul>
            <input type="text" id="new-checklist-item" placeholder="<?php esc_attr_e('Add new item...', 'ultimakit-for-wp'); ?>" style="width: 100%;">
            <button type="button" id="add-checklist-item" class="button" style="margin-top: 5px;"><?php _e('Add', 'ultimakit-for-wp'); ?></button>
        </div>
        <?php
    }

    public function save_checklist($post_id) {
        // Skip autosaves
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Check permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Save checklist items if set
        if (isset($_POST['checklist_items'])) {
            $checklist_items = array_map('sanitize_text_field', $_POST['checklist_items']);
            update_post_meta($post_id, '_post_publish_checklist', json_encode($checklist_items));
        } else {
            delete_post_meta($post_id, '_post_publish_checklist');
        }
    }

    public function enqueue_assets($hook) {
        if (!in_array($hook, array('post.php', 'post-new.php'))) {
            return;
        }

        wp_enqueue_script(
            $this->ID,
            plugin_dir_url(__FILE__) . 'module-script.js',
            array('jquery'),
            '1.0.0',
            true
        );

        wp_enqueue_style(
            $this->ID,
            plugin_dir_url(__FILE__) . 'module-style.css',
            array(),
            '1.0.0'
        );
    }

}
