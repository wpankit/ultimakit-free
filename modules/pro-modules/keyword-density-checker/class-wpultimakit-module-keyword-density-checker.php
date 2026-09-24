<?php
/**
 * Class UltimaKit_Module_Keyword_Density_Checker
 *
 * @since 1.0.0
 * @package    UltimaKit
 */

/**
 * Class UltimaKit_Module_Keyword_Density_Checker
 *
 * This class provides functionality to scan and identify broken links within
 * the WordPress site content, including posts, pages, and custom post types.
 * It allows users to find and fix broken internal and external links to
 * maintain site health and SEO.
 *
 * @since 1.0.0
 */
class UltimaKit_Module_Keyword_Density_Checker extends UltimaKit_Module_Manager {
    /**
     * Module identifier
     *
     * @var string
     */
    protected $ID = 'ultimakit_module_keyword_density_checker';

    /**
     * The name of the module
     *
     * @var string
     */
    protected $name;

    /**
     * Module description
     *
     * @var string
     */
    protected $description;

    /**
     * Module plan (free/pro)
     *
     * @var string
     */
    protected $plan = 'pro';

    /**
     * Module category
     *
     * @var string
     */
    protected $category = 'Content Management';

    /**
     * Module type
     *
     * @var string
     */
    protected $type = 'WordPress';

    /**
     * Module status
     *
     * @var bool
     */
    protected $is_active;

    /**
     * Documentation link
     *
     * @var string
     */
    protected $read_more_link = 'keyword-density-checker-in-wordpress';

    /**
     * Module settings
     *
     * @var array
     */
    protected $settings;

    /**
     * Constructor
     */
    public function __construct() {
        $this->name = __('Keyword Density Checker', 'ultimakit-for-wp');
        $this->description = __('Analyze the frequency of a target keyword in a post or page and provide a simple score.', 'ultimakit-for-wp');
        $this->is_active = $this->isModuleActive($this->ID);

        $this->initializeModule();
    }

    /**
     * Initialize module
     */
    protected function initializeModule() {
        if ($this->is_active) {
            add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
            add_action('add_meta_boxes', array($this, 'add_meta_box'));
            add_action('wp_ajax_analyze_keyword_density', array($this, 'ajax_analyze_keyword_density'));
        }
    }

    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {

        wp_enqueue_style(
            $this->ID,
            plugin_dir_url(__FILE__) . '/module-style.css',
            array(),
            ULTIMAKIT_FOR_WP_VERSION
        );

        wp_enqueue_script(
            $this->ID,
            plugin_dir_url(__FILE__) . '/module-script.js',
            array('jquery'),
            ULTIMAKIT_FOR_WP_VERSION,
            true
        );

        wp_localize_script($this->ID, 'wpukKeywordDensity', array(
            'nonce' => wp_create_nonce('wpuk_keyword_density_nonce'),
            'analyzing' => __('Analyzing...', 'ultimakit-for-wp'),
            'error' => __('Error analyzing content', 'ultimakit-for-wp'),
            'no_content' => __('No content found to analyze', 'ultimakit-for-wp'),
            'no_keyword' => __('Please enter a keyword to analyze', 'ultimakit-for-wp')
        ));

    }

    public function add_meta_box() {
        // Get all public post types
        $post_types = get_post_types(array(
            'public' => true
        ));
    
        // Remove attachment post type as it doesn't need keyword analysis
        if (isset($post_types['attachment'])) {
            unset($post_types['attachment']);
        }
    
        // Add meta box to all public post types
        add_meta_box(
            'wpuk-keyword-density',
            __('Keyword Density Checker [UltimaKit For WP]', 'ultimakit-for-wp'),
            array($this, 'render_meta_box'),
            array_values($post_types),
            'normal',
            'default'
        );
    }

    public function render_meta_box($post) {
        ?>
        <div class="wpuk-keyword-density-checker">
            <div class="wpuk-input-group">
                <input type="text" 
                       id="wpuk-keyword-input" 
                       placeholder="<?php esc_attr_e('Enter target keyword', 'ultimakit-for-wp'); ?>"
                       style="width: 50%;">
                <button type="button" 
                        id="wpuk-analyze-keyword" 
                        class="button button-primary">
                    <?php esc_html_e('Analyze', 'ultimakit-for-wp'); ?>
                </button>
            </div>
            
            <div id="wpuk-density-results" class="wpuk-density-results" style="display: none; margin-top: 10px;">
                <div class="wpuk-density-score">
                    <span class="wpuk-label"><strong><?php esc_html_e('Density Score:', 'ultimakit-for-wp'); ?></strong></span>
                    <span id="wpuk-density-percentage">0%</span>
                </div>
                <div class="wpuk-occurrence-count">
                    <span class="wpuk-label"><strong><?php esc_html_e('Occurrences:', 'ultimakit-for-wp'); ?></strong></span>
                    <span id="wpuk-keyword-count">0</span>
                </div>
                <div class="wpuk-density-status">
                    <span class="wpuk-label"><strong><?php esc_html_e('Status:', 'ultimakit-for-wp'); ?></strong></span>
                    <span id="wpuk-density-status-text"></span>
                </div>
            </div>
            
            <div id="wpuk-density-error" class="wpuk-density-error" style="display: none;"></div>
        </div>
        <?php
    }

    public function ajax_analyze_keyword_density() {
        check_ajax_referer('wpuk_keyword_density_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Permission denied');
        }

        $keyword = sanitize_text_field($_POST['keyword']);
        $content = wp_strip_all_tags($_POST['content']);

        if (empty($keyword) || empty($content)) {
            wp_send_json_error('Invalid input');
        }

        $result = $this->analyze_content($content, $keyword);
        wp_send_json_success($result);
    }

    private function analyze_content($content, $keyword) {
        $word_count = str_word_count(strtolower($content));
        $keyword_count = substr_count(strtolower($content), strtolower($keyword));
        
        if ($word_count === 0) {
            return array(
                'density' => 0,
                'count' => 0,
                'status' => 'error',
                'message' => __('No content to analyze', 'ultimakit-for-wp')
            );
        }

        $density = ($keyword_count / $word_count) * 100;
        $density = round($density, 2);

        $status = $this->get_density_status($density);

        return array(
            'density' => $density,
            'count' => $keyword_count,
            'status' => $status['status'],
            'message' => $status['message']
        );
    }

    private function get_density_status($density) {
        if ($density === 0) {
            return array(
                'status' => 'poor',
                'message' => __('Keyword not found in content', 'ultimakit-for-wp')
            );
        } elseif ($density < 0.5) {
            return array(
                'status' => 'low',
                'message' => __('Keyword density is too low', 'ultimakit-for-wp')
            );
        } elseif ($density <= 2.5) {
            return array(
                'status' => 'good',
                'message' => __('Keyword density is optimal', 'ultimakit-for-wp')
            );
        } else {
            return array(
                'status' => 'high',
                'message' => __('Keyword density is too high (potential keyword stuffing)', 'ultimakit-for-wp')
            );
        }
    }

}