<?php
/**
 * Class UltimaKit_Module_Word_Count_Tracker
 *
 * @since 1.0.0
 * @package    UltimaKit
 */

/**
 * Class UltimaKit_Module_Word_Count_Tracker
 *
 * This class provides methods to control the display of dashboard widgets based on user preferences.
 * It allows users to selectively hide certain dashboard widgets to streamline their WordPress dashboard
 * experience and improve usability.
 *
 * @since 1.0.0
 */
class UltimaKit_Module_Word_Count_Tracker extends UltimaKit_Module_Manager {
	/**
	 *
	 * @var string
	 */
	protected $ID = 'ultimakit_module_word_count_tracker';

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
		$this->name        = __( 'Word Count Tracker', 'ultimakit-for-wp' );
		$this->description = __( 'This tool helps you track the word count of your posts and pages.', 'ultimakit-for-wp' );
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

            // Add meta box for target word count
			add_action('add_meta_boxes', array($this, 'add_word_count_meta_box'));
			
			// Save target word count
			add_action('save_post', array($this, 'save_target_word_count'));
			
			// Enqueue scripts
			add_action('enqueue_block_editor_assets', array($this, 'enqueue_block_editor_assets'));
			add_action('admin_enqueue_scripts', array($this, 'enqueue_classic_editor_assets'));

		}

	}


	public function add_word_count_meta_box() {
        add_meta_box(
            'word-count-tracker',
            __('Word Count Tracker', 'ultimakit-for-wp'),
            array($this, 'render_meta_box'),
            array('post', 'page'),
            'side',
            'high'
        );
    }

    public function render_meta_box($post) {
        // Get the target word count
        $target_word_count = get_post_meta($post->ID, '_target_word_count', true);
        ?>
        <div id="word-count-tracker-container">
            <p>
                <strong><?php _e('Current Word Count:', 'ultimakit-for-wp'); ?></strong>
                <span id="current-word-count">0</span>
            </p>
            <p>
                <label for="target-word-count"><?php _e('Target Word Count:', 'ultimakit-for-wp'); ?></label>
                <input 
                    type="number" 
                    id="target-word-count" 
                    name="target_word_count" 
                    value="<?php echo esc_attr($target_word_count); ?>" 
                    min="0"
                    style="width: 100%;"
                />
            </p>
        </div>
        <?php
    }

    public function save_target_word_count($post_id) {
        // Skip autosaves
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Check permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Save target word count if set
        if (isset($_POST['target_word_count'])) {
            $target_word_count = intval($_POST['target_word_count']);
            update_post_meta($post_id, '_target_word_count', $target_word_count);
        }
    }

    public function enqueue_block_editor_assets() {
        wp_enqueue_script(
           $this->ID,
            plugin_dir_url(__FILE__) . 'module-script.js',
            array('wp-blocks', 'wp-element', 'wp-editor'),
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

    public function enqueue_classic_editor_assets($hook) {
        if (!in_array($hook, array('post.php', 'post-new.php'))) {
            return;
        }

        if (!use_block_editor_for_post_type(get_post_type())) {
            wp_enqueue_script(
                $this->ID,
                plugin_dir_url(__FILE__) . 'module-script-editor.js',
                array('jquery', 'editor'), // Add 'editor' dependency
                '1.0.0',
                true
            );
        }

        wp_enqueue_style(
            $this->ID,
            plugin_dir_url(__FILE__) . 'module-style.css',
            array(),
            '1.0.0'
        );
    }


}
