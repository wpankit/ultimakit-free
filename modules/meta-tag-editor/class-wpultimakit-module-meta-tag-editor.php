<?php
/**
 * Class UltimaKit_Module_Meta_Tag_Editor
 *
 * @since 1.0.0
 * @package    UltimaKit
 */

/**
 * Class UltimaKit_Module_Meta_Tag_Editor
 *
 * This class provides methods to control the display of dashboard widgets based on user preferences.
 * It allows users to selectively hide certain dashboard widgets to streamline their WordPress dashboard
 * experience and improve usability.
 *
 * @since 1.0.0
 */
class UltimaKit_Module_Meta_Tag_Editor extends UltimaKit_Module_Manager {
	/**
	 *
	 * @var string
	 */
	protected $ID = 'ultimakit_module_meta_tag_editor';

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
	protected $read_more_link = 'meta-tag-editor-in-wordpress';

	/**
	 * The settings associated with the module, if any.
	 *
	 * @var array
	 */
	protected $settings;

	/**
     * Active SEO plugin (if any).
     *
     * @var string|false
     */
    private $active_seo_plugin = false;

	/**
     * Known SEO plugins list.
     *
     * @var array
     */
    private $seo_plugins;


	/**
	 *
	 * Initializes the module with default values for properties and prepares
	 * any necessary setup or hooks into WordPress. This may include setting
	 * initial values, registering hooks, or preparing resources needed for
	 * the module to function properly within WordPress.
	 */
	public function __construct() {
		$this->name        = __( 'Meta Tag Editor', 'ultimakit-for-wp' );
		$this->description = __( 'Edit the meta tags of your WordPress site.', 'ultimakit-for-wp' );
		$this->is_active   = $this->isModuleActive( $this->ID );
		$this->settings    = 'yes';

		$this->seo_plugins = array(
            'wordpress-seo/wp-seo.php' => __( 'Yoast SEO', 'ultimakit-for-wp' ),
            'rank-math/rank-math.php' => __( 'Rank Math', 'ultimakit-for-wp' ),
            'all-in-one-seo-pack/all_in_one_seo_pack.php' => __( 'All in One SEO Pack', 'ultimakit-for-wp' ),
            'wp-seopress/seopress.php' => __( 'SEOPress', 'ultimakit-for-wp' ),
            'autodescription/autodescription.php' => __( 'The SEO Framework', 'ultimakit-for-wp' ),
            'slim-seo/slim-seo.php' => __( 'Slim SEO', 'ultimakit-for-wp' )
        );

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
			// Include required files
			if (!function_exists('is_plugin_active')) {
				include_once(ABSPATH . 'wp-admin/includes/plugin.php');
			}
            
			// // Check for active SEO plugins
			$this->active_seo_plugin = $this->detect_active_seo_plugins();
	
			// Add hooks
			add_action('admin_init', [$this, 'setup_module']);
			add_action('wp_head', [$this, 'output_meta_tags'], 1);
			add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
			add_action( 'admin_footer', array( $this, 'add_modal' ) );

		}

	}

	/**
	 * Adds a modal dialog to the page.
	 *
	 * This function is responsible for initiating and rendering a modal dialog within the
	 * application or website interface. It typically involves setting up the necessary HTML
	 * and JavaScript for the modal to function and display correctly. The modal can be used
	 * for various purposes, such as displaying information, confirming actions, or collecting
	 * user input.
	 *
	 * @return void
	 */
	public function add_modal() {
		$arguments          = array();
		$arguments['ID']    = $this->ID;
		$arguments['title'] = __( 'Meta Tag Editor', 'ultimakit-for-wp' );

		$arguments['fields'] = array(
			'override_seo_plugins' => array(
                'type' => 'switch',
                'label' => __('Override SEO Plugins', 'ultimakit-for-wp'),
                'value' => $this->getModuleSettings($this->ID, 'override_seo_plugins', 'off'),
            )
		);

		$this->ultimakit_generate_modal( $arguments );
	}

	/**
     * Detect active SEO plugins.
     *
     * @return string|false
     */
    private function detect_active_seo_plugins() {
        foreach ($this->seo_plugins as $plugin_file => $plugin_name) {
            if (is_plugin_active($plugin_file)) {
                return $plugin_name;
            }
        }
        return false;
    }
	

	/**
     * Setup module based on conditions.
     */
    public function setup_module() {
        if ($this->should_disable_module()) {
            $this->disable_module();
            add_action('admin_notices', [$this, 'display_seo_plugin_notice']);
        } else {
            add_action('add_meta_boxes', [$this, 'add_meta_box']);
            add_action('save_post', [$this, 'save_meta_data']);
        }
    }

    /**
     * Check if module should be disabled.
     *
     * @return boolean
     */
    private function should_disable_module() {
        return $this->active_seo_plugin && 'off' === $this->getModuleSettings($this->ID, 'override_seo_plugins', 'off');
    }

    /**
     * Disable module functionality.
     */
    private function disable_module() {
        remove_action('add_meta_boxes', [$this, 'add_meta_box']);
        remove_action('save_post', [$this, 'save_meta_data']);
    }

    /**
     * Display admin notice for active SEO plugin.
     */
    public function display_seo_plugin_notice() {
        ?>
        <div class="notice notice-error is-dismissible">
            <p>
                <strong><?php echo esc_html__( 'Meta Tag Editor Disabled:', 'ultimakit-for-wp' ); ?></strong> 
                <?php echo esc_html($this->active_seo_plugin); ?> <?php echo esc_html__( 'is active and managing meta tags.', 'ultimakit-for-wp' ); ?> 
                <?php echo esc_html__( 'To use the Meta Tag Editor disable the other SEO plugin.', 'ultimakit-for-wp' ); ?>
            </p>
        </div>
        <?php
    }

    /**
     * Add meta box to post editor.
     */
    public function add_meta_box() {
        add_meta_box(
            'ultimakit_meta_tag_editor',
            __( 'Meta Tag Editor', 'ultimakit-for-wp' ),
            [$this, 'render_meta_box'],
            ['post', 'page'],
            'normal',
            'high'
        );
    }

    /**
     * Render meta box content.
     *
     * @param WP_Post $post Post object.
     */
    public function render_meta_box($post) {
        wp_nonce_field('ultimakit_meta_tag_editor', 'ultimakit_meta_tag_nonce');

        $meta_title = get_post_meta($post->ID, '_meta_title', true);
        $meta_description = get_post_meta($post->ID, '_meta_description', true);

		// Get the site URL and post slug
		$site_url = get_site_url();
		$post_slug = $post->post_name;
		if (empty($post_slug)) {
			// If post is not published yet, generate a preview slug
			$post_slug = sanitize_title($post->post_title);
		}
		
		// Create the full preview URL
		$preview_url = trailingslashit($site_url);
		if ($post->post_type === 'post') {
			$preview_url .= trailingslashit(get_option('permalink_structure') ? '' : '?p=' . $post->ID);
		}
		$preview_url .= $post_slug;

		// Format the URL for display
		$displayed_url = preg_replace(
			[
				'#^https?:#', // Remove protocol
				'#/{2,}#',    // Remove multiple slashes
				'#/$#'        // Remove trailing slash
			],
			[
				'',
				'/',
				''
			],
			$preview_url
		);
        ?>
        <div class="ultimakit-meta-tag-editor">
            <div class="meta-field">
                <label for="wpuk_meta_title"><?php echo esc_html__( 'Meta Title:', 'ultimakit-for-wp' ); ?></label>
                <input type="text" id="wpuk_meta_title" name="wpuk_meta_title" 
                    value="<?php echo esc_attr($meta_title); ?>" 
                    maxlength="60" style="width: 100%;">
                <p class="description">
                    <?php echo esc_html__( 'Recommended: 50-60 characters. ', 'ultimakit-for-wp' ); ?>
                    <span id="wpuk_title_count">0</span> <?php echo esc_html__( 'characters used.', 'ultimakit-for-wp' ); ?>
                </p>
            </div>

            <div class="meta-field">
                <label for="wpuk_meta_description"><?php echo esc_html__( 'Meta Description:', 'ultimakit-for-wp' ); ?></label>
                <textarea id="wpuk_meta_description" name="wpuk_meta_description" 
                    maxlength="160" style="width: 100%;"><?php echo esc_textarea($meta_description); ?></textarea>
                <p class="description">
                    <?php echo esc_html__( 'Recommended: 150-160 characters. ', 'ultimakit-for-wp' ); ?>
                    <span id="wpuk_description_count">0</span> <?php echo esc_html__( 'characters used.', 'ultimakit-for-wp' ); ?>
                </p>
            </div>

            <div class="meta-preview">
                <h4><?php echo esc_html__( 'Search Engine Preview:', 'ultimakit-for-wp' ); ?></h4>
                <div id="wpuk_seo_preview" class="preview-box">
                    <div id="wpuk_preview_title" class="preview-title"></div>
                    <div class="preview-url"><?php echo esc_html($displayed_url); ?></div>
                    <div id="wpuk_preview_description" class="preview-description"></div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Enqueue admin assets.
     *
     * @param string $hook Current admin page.
     */
    public function enqueue_admin_assets($hook) {

		wp_enqueue_style(
            'ultimakit-meta-editor',
            plugin_dir_url(__FILE__) . 'module-css.css',
            [],
            '1.0.0'
        );

        wp_enqueue_script(
            'ultimakit-meta-editor',
            plugin_dir_url(__FILE__) . 'module-script-general.js',
            ['jquery'],
            '1.0.0',
            true
        );
    }

    /**
     * Save meta data.
     *
     * @param int $post_id Post ID.
     */
    public function save_meta_data($post_id) {
        if (!isset($_POST['ultimakit_meta_tag_nonce']) || 
            !wp_verify_nonce($_POST['ultimakit_meta_tag_nonce'], 'ultimakit_meta_tag_editor')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        if (isset($_POST['wpuk_meta_title'])) {
            update_post_meta(
                $post_id,
                '_meta_title',
                sanitize_text_field($_POST['wpuk_meta_title'])
            );
        }

        if (isset($_POST['wpuk_meta_description'])) {
            update_post_meta(
                $post_id,
                '_meta_description',
                sanitize_textarea_field($_POST['wpuk_meta_description'])
            );
        }
    }

    /**
     * Output meta tags in front-end.
     */
    public function output_meta_tags() {
        if ($this->should_disable_module()) {
            return;
        }

        if (is_singular()) {
            $post_id = get_the_ID();
            $meta_title = get_post_meta($post_id, '_meta_title', true);
            $meta_description = get_post_meta($post_id, '_meta_description', true);

            if ($meta_title) {
                echo '<meta name="title" content="' . esc_attr($meta_title) . '">' . PHP_EOL;
            }
            if ($meta_description) {
                echo '<meta name="description" content="' . esc_attr($meta_description) . '">' . PHP_EOL;
            }
        }
    }

}
