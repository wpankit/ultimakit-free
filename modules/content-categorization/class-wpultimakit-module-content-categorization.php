<?php
/**
 * Class UltimaKit_Module_Content_Categorization
 *
 * @since 1.0.0
 * @package    UltimaKit
 */

/**
 * Class UltimaKit_Module_Content_Categorization
 *
 * This class provides methods to control the display of dashboard widgets based on user preferences.
 * It allows users to selectively hide certain dashboard widgets to streamline their WordPress dashboard
 * experience and improve usability.
 *
 * @since 1.0.0
 */
class UltimaKit_Module_Content_Categorization extends UltimaKit_Module_Manager {
    /**
     * Unique identifier for this module
     * @var string
     */
    protected $ID = 'ultimakit_module_content_categorization';

    /**
     * Module name
     * @var string
     */
    protected $name;

    /**
     * Module description
     * @var string
     */
    protected $description;

    /**
     * Required plan level to access this module
     * @var string
     */
    protected $plan = 'free';

    /**
     * Module category for organization
     * @var string
     */
    protected $category = 'Content Management';

    /**
     * Module type for filtering
     * @var string
     */
    protected $type = 'WordPress';

    /**
     * Whether module is currently active
     * @var bool
     */
    protected $is_active;

    /**
     * Current module version
     * @var string
     */
    protected $version = '1.0.0';

    public function __construct() {
        $this->name = __('Content Categorization', 'ultimakit-for-wp');
        $this->description = __('Suggest categories for posts based on their content.', 'ultimakit-for-wp');
        $this->is_active = $this->isModuleActive($this->ID);
        
        $this->initializeModule();
    }

    protected function initializeModule() {
        if ($this->is_active) {
            add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
            add_action('wp_ajax_get_category_suggestions', array($this, 'ajax_get_category_suggestions'));
            add_action('add_meta_boxes', array($this, 'add_category_suggestion_box'));
        }
    }

    public function add_category_suggestion_box() {
        $post_types = array('post', 'page');
        foreach ($post_types as $post_type) {
            add_meta_box(
                'ultimakit_category_suggestions',
                __('Category Suggestions', 'ultimakit-for-wp'),
                array($this, 'render_category_suggestion_box'),
                $post_type,
                'side',
                'default'
            );
        }
    }

    public function render_category_suggestion_box($post) {
        ?>
        <div id="ultimakit-category-suggestions">
            <div class="suggestion-input">
                <textarea id="content-input" class="widefat" 
                          placeholder="<?php esc_attr_e('Type or paste your content here...', 'ultimakit-for-wp'); ?>"></textarea>
            </div>
            <button id="suggest-categories" class="button button-primary">
                <?php esc_html_e('Suggest Categories', 'ultimakit-for-wp'); ?>
            </button>
            <div id="suggestion-results" class="suggestion-results"></div>
            <div class="suggestion-loading" style="display:none;">
                <?php esc_html_e('Loading suggestions...', 'ultimakit-for-wp'); ?>
            </div>
        </div>
        <?php
    }

    public function enqueue_admin_assets($hook) {
        if (!in_array($hook, array('post.php', 'post-new.php'))) {
            return;
        }

        wp_enqueue_style(
            $this->ID . '-admin',
            plugin_dir_url(__FILE__) . 'module-style.css',
            array(),
            $this->version
        );

        wp_enqueue_script(
            $this->ID . '-admin',
            plugin_dir_url(__FILE__) . 'module-script.js',
            array('jquery'),
            $this->version,
            true
        );

        wp_localize_script(
            $this->ID . '-admin',
            'ultimakitCategorySuggestion',
            array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('ultimakit_category_suggestion_nonce'),
                'loading' => __('Loading suggestions...', 'ultimakit-for-wp'),
                'no_results' => __('No suggestions found', 'ultimakit-for-wp'),
                'error' => __('Error fetching suggestions', 'ultimakit-for-wp')
            )
        );
    }

    public function ajax_get_category_suggestions() {
        check_ajax_referer('ultimakit_category_suggestion_nonce', 'nonce');

        $content = isset($_POST['content']) ? sanitize_textarea_field($_POST['content']) : '';
        if (empty($content)) {
            wp_send_json_error('No content provided');
            return;
        }

        // Here you can implement your logic to analyze content and suggest categories
        $suggestions = $this->get_category_suggestions($content);

        if (!empty($suggestions)) {
            wp_send_json_success($suggestions);
        } else {
            wp_send_json_error('No suggestions found');
        }
    }

    private function get_category_suggestions($content) {
        // Example logic for category suggestions based on keywords in content
        $keywords = array_map('trim', explode(' ', $content));
        $categories = get_categories(array('hide_empty' => false));
        $suggestions = array();

        foreach ($categories as $category) {
            foreach ($keywords as $keyword) {
                if (stripos($category->name, $keyword) !== false) {
                    $suggestions[$category->term_id] = $category->name;
                }
            }
        }

        return array_values(array_unique($suggestions));
    }
}