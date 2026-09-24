<?php
/**
 * Class UltimaKit_Module_Quick_Draft_Notes
 *
 * @since 1.0.0
 * @package    UltimaKit
 */

/**
 * Class UltimaKit_Module_Quick_Draft_Notes
 *
 * This class provides methods to control the display of dashboard widgets based on user preferences.
 * It allows users to selectively hide certain dashboard widgets to streamline their WordPress dashboard
 * experience and improve usability.
 *
 * @since 1.0.0
 */
class UltimaKit_Module_Quick_Draft_Notes extends UltimaKit_Module_Manager {
	/**
	 *
	 * @var string
	 */
	protected $ID = 'ultimakit_module_quick_draft_notes';

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
	protected $read_more_link = 'quick-draft-notes-in-wordpress';

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
		$this->name        = __( 'Quick Draft Notes', 'ultimakit-for-wp' );
		$this->description = __( 'This module lets you jot down ideas and reminders for your posts directly in the editor.', 'ultimakit-for-wp' );
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

           	// Add meta box for quick draft notes
			add_action('add_meta_boxes', array($this, 'add_notes_meta_box'));
			
			// Save notes
			add_action('save_post', array($this, 'save_notes'));
			
			// Enqueue scripts and styles
			add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));

		}

	}

	public function add_notes_meta_box() {
        add_meta_box(
            'quick-draft-notes',
            __('Quick Draft Notes', 'ultimakit-for-wp'),
            array($this, 'render_meta_box'),
            array('post', 'page'),
            'normal',
            'high'
        );
    }

    public function render_meta_box($post) {
        // Get the saved notes
        $notes = get_post_meta($post->ID, '_quick_draft_notes', true);
        ?>
        <div id="quick-draft-notes-container">
            <textarea 
                id="quick-draft-notes" 
                name="quick_draft_notes" 
                rows="5" 
                style="width: 100%;"
                placeholder="<?php esc_attr_e('Jot down your ideas or reminders here...', 'ultimakit-for-wp'); ?>"
            ><?php echo esc_textarea($notes); ?></textarea>
        </div>
        <?php
    }

    public function save_notes($post_id) {
        // Skip autosaves
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Check permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Save notes if set
        if (isset($_POST['quick_draft_notes'])) {
            $notes = sanitize_textarea_field($_POST['quick_draft_notes']);
            update_post_meta($post_id, '_quick_draft_notes', $notes);
        }
    }

    public function enqueue_assets($hook) {
        if (!in_array($hook, array('post.php', 'post-new.php'))) {
            return;
        }

        wp_enqueue_style(
            $this->ID,
            plugin_dir_url(__FILE__) . 'module-style.css',
            array(),
            '1.0.0'
        );
    }


}
