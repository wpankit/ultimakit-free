<?php
/**
 * Class UltimaKit_Module_Duplicate_Content_Checker
 *
 * @since 1.0.0
 * @package    UltimaKit
 */

/**
 * Class UltimaKit_Module_Duplicate_Content_Checker
 *
 * This class provides methods to control the display of dashboard widgets based on user preferences.
 * It allows users to selectively hide certain dashboard widgets to streamline their WordPress dashboard
 * experience and improve usability.
 *
 * @since 1.0.0
 */
class UltimaKit_Module_Duplicate_Content_Checker extends UltimaKit_Module_Manager {
    /**
     * Module identifier
     * @var string
     */
    protected $ID = 'ultimakit_module_duplicate_content_checker';

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
     * Module plan
     * @var string
     */
    protected $plan = 'pro';

    /**
     * Module category
     * @var string
     */
    protected $category = 'Content Management';

    /**
     * Module type
     * @var string
     */
    protected $type = 'WordPress';

    /**
     * Module active status
     * @var bool
     */
    protected $is_active;

    /**
     * Documentation link
     * @var string
     */
    protected $read_more_link = 'duplicate-content-checker-in-wordpress';

    /**
     * Module settings
     * @var array
     */
    protected $settings;

    /**
     * Constructor
     */
    public function __construct() {
        $this->name = __('Duplicate Content Checker', 'ultimakit-for-wp');
        $this->description = __('Scan the site for duplicate content and suggest fixes.', 'ultimakit-for-wp');
        $this->is_active = $this->isModuleActive($this->ID);
        $this->initializeModule();
    }

    /**
     * Initialize module functionality
     */
    protected function initializeModule() {
        if ($this->is_active) {
            add_action('admin_menu', array($this, 'add_admin_menu'), 20);
            add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
            add_action('wp_ajax_ultimakit_run_duplicate_scan', array($this, 'ajax_run_scan'));
            add_action('wp_ajax_ultimakit_save_scan_settings', array($this, 'ajax_save_settings'));
            add_action('ultimakit_scheduled_duplicate_scan', array($this, 'run_scheduled_scan'));
            add_filter('cron_schedules', array($this, 'add_monthly_cron_schedule'));
        }
    }

    /**
     * Register the 'monthly' schedule offered in the settings (WordPress has no monthly schedule).
     *
     * @param array $schedules Registered cron schedules.
     * @return array
     */
    public function add_monthly_cron_schedule($schedules) {
        if (!isset($schedules['monthly'])) {
            $schedules['monthly'] = array(
                'interval' => 30 * DAY_IN_SECONDS,
                'display'  => __('Once Monthly', 'ultimakit-for-wp'),
            );
        }
        return $schedules;
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_submenu_page(
            'wp-ultimakit-dashboard',
            __('Duplicate Content Checker', 'ultimakit-for-wp'),
            __('Duplicate Content Checker', 'ultimakit-for-wp'),
            'manage_options',
            'wp-ultimakit-duplicate-content-checker',
            array($this, 'render_admin_page')
        );
    }

    /**
     * Enqueue assets
     */
    public function enqueue_assets($hook) {
        if ($hook !== 'ultimakit-for-wp_page_wp-ultimakit-duplicate-content-checker') {
            return;
        }

        wp_enqueue_script(
            $this->ID,
            plugin_dir_url(__FILE__) . 'module-script.js',
            array('jquery'),
            '1.0.0',
            true
        );

        wp_localize_script(
            $this->ID,
            'ultimakitDuplicateChecker',
            array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('ultimakit_duplicate_checker'),
                'scanning_message' => __('Scanning for duplicate content...', 'ultimakit-for-wp'),
                'error_message' => __('Error during scan. Please try again.', 'ultimakit-for-wp')
            )
        );

        wp_enqueue_style(
            $this->ID,
            plugin_dir_url(__FILE__) . 'module-style.css',
            array(),
            '1.0.0'
        );
    }

    /**
     * Render admin page
     */
    public function render_admin_page() {
        $scan_frequency = $this->getModuleSettings($this->ID, 'frequency', 'weekly');
        $post_types = $this->getModuleSettings($this->ID, 'post_types', array('post', 'page'));

        $object = new UltimaKit_Helpers();
        ?>
        <div class="wrap">
            <?php $object->ultimakit_get_header(); ?>
            <div class="container bg-white text-dark p-3 mb-3">
                <ul class="nav nav-tabs" id="wpukTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <a class="nav-link active" id="scanner-tab" data-bs-toggle="tab" href="#duplicate-content-scanner" role="tab" aria-controls="duplicate-content-scanner" aria-selected="true">
                            <?php esc_html_e('Duplicate Content Scanner', 'ultimakit-for-wp'); ?>
                        </a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link" id="settings-tab" data-bs-toggle="tab" href="#scanner-settings" role="tab" aria-controls="scanner-settings" aria-selected="false">
                            <?php esc_html_e('Settings', 'ultimakit-for-wp'); ?>
                        </a>
                    </li>
                </ul>

                <div class="tab-content" id="wpukTabsContent">
                    <!-- Scanner Tab -->
                    <div class="tab-pane fade show active" id="duplicate-content-scanner" role="tabpanel">
                        <div class="row mt-3">
                            <div class="col-12">
                                <div class="card" style="width: 100%; display: contents;">
                                    <div class="card-body">
                                        <h5 class="card-title"><?php esc_html_e('Duplicate Content Scanner', 'ultimakit-for-wp'); ?></h5>
                                        <button id="start-scan" class="btn btn-primary">
                                            <?php esc_html_e('Start Scan', 'ultimakit-for-wp'); ?>
                                        </button>
                                        <div id="scan-progress" class="progress mt-3 d-none">
                                            <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar"></div>
                                        </div>
                                        <div id="scan-results" class="mt-3"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Settings Tab -->
                    <div class="tab-pane fade" id="scanner-settings" role="tabpanel">
                        <div class="row mt-3">
                            <div class="col-12">
                                <div class="card" style="width: 100%; display: contents;">
                                    <div class="card-body">
                                        <h5 class="card-title"><?php esc_html_e('Scanner Settings', 'ultimakit-for-wp'); ?></h5>
                                        <form id="scanner-settings-form">
                                            <div class="mb-3">
                                                <label for="scan-frequency" class="form-label">
                                                    <?php esc_html_e('Scan Frequency', 'ultimakit-for-wp'); ?>
                                                </label>
                                                <select id="scan-frequency" name="frequency" class="form-select">
                                                    <option value="daily" <?php selected($scan_frequency, 'daily'); ?>>
                                                        <?php esc_html_e('Daily', 'ultimakit-for-wp'); ?>
                                                    </option>
                                                    <option value="weekly" <?php selected($scan_frequency, 'weekly'); ?>>
                                                        <?php esc_html_e('Weekly', 'ultimakit-for-wp'); ?>
                                                    </option>
                                                    <option value="monthly" <?php selected($scan_frequency, 'monthly'); ?>>
                                                        <?php esc_html_e('Monthly', 'ultimakit-for-wp'); ?>
                                                    </option>
                                                </select>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label"><?php esc_html_e('Post Types to Scan', 'ultimakit-for-wp'); ?></label>
                                                <?php
                                                $available_post_types = get_post_types(array('public' => true), 'objects');
                                                foreach ($available_post_types as $post_type) {
                                                    $checked = in_array($post_type->name, $post_types) ? 'checked' : '';
                                                    echo '<br />';
                                                    echo '<label class="checkbox-item">';
                                                    echo '<input type="checkbox" name="post_types[]" value="' . esc_attr($post_type->name) . '" ' . $checked . '>';
                                                    echo '<span class="checkbox-label">' . esc_html($post_type->labels->name) . '</span>';
                                                    echo '</label>';
                                                    ?>
                                                    <?php
                                                }
                                                ?>
                                            </div>
                                            <button type="submit" class="btn btn-primary">
                                                <?php esc_html_e('Save Settings', 'ultimakit-for-wp'); ?>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Run content scan
     */
    private function run_scan($post_types = array()) {
        if (empty($post_types)) {
            $settings = $this->getModuleSettings($this->ID, 'settings', array());
            $post_types = isset($settings['post_types']) ? $settings['post_types'] : array('post', 'page');
        }

        $results = array();
        $processed_content = array();

        foreach ($post_types as $post_type) {
            $posts = get_posts(array(
                'post_type' => $post_type,
                'posts_per_page' => -1,
                'post_status' => 'publish'
            ));

            foreach ($posts as $post) {
                $content_hash = md5(strip_tags($post->post_content));
                
                if (isset($processed_content[$content_hash])) {
                    // Duplicate found
                    if (!isset($results[$content_hash])) {
                        $results[$content_hash] = array(
                            'original' => $processed_content[$content_hash],
                            'duplicates' => array()
                        );
                    }
                    
                    $results[$content_hash]['duplicates'][] = array(
                        'id' => $post->ID,
                        'title' => $post->post_title,
                        'url' => get_permalink($post->ID),
                        'type' => $post_type
                    );
                } else {
                    $processed_content[$content_hash] = array(
                        'id' => $post->ID,
                        'title' => $post->post_title,
                        'url' => get_permalink($post->ID),
                        'type' => $post_type
                    );
                }
            }
        }

        return $results;
    }

    /**
     * AJAX handler for running scan
     */
    public function ajax_run_scan() {
        check_ajax_referer('ultimakit_duplicate_checker', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permission denied', 'ultimakit-for-wp'));
        }

        $results = $this->run_scan();
        
        if (empty($results)) {
            wp_send_json_success(array(
                'message' => __('No duplicate content found.', 'ultimakit-for-wp'),
                'results' => array()
            ));
        } else {
            wp_send_json_success(array(
                'message' => __('Scan completed. Duplicate content found.', 'ultimakit-for-wp'),
                'results' => $results
            ));
        }
    }

    /**
     * AJAX handler for saving settings
     */
    public function ajax_save_settings() {
        check_ajax_referer('ultimakit_duplicate_checker', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permission denied', 'ultimakit-for-wp'));
        }

        $settings = array(
            'frequency' => sanitize_text_field($_POST['frequency']),
            // Nothing ticked means no post_types field at all; array_map() on null is a fatal on PHP 8.
            'post_types' => isset($_POST['post_types']) && is_array($_POST['post_types']) ? array_map('sanitize_text_field', $_POST['post_types']) : array()
        );

        $result = $this->ultimakit_update_module_setting($this->ID, 'settings', $settings, false);

        if ($result !== false) {
            // Update cron schedule
            $this->update_scan_schedule($settings['frequency']);
            wp_send_json_success(__('Settings saved successfully', 'ultimakit-for-wp'));
        } else {
            wp_send_json_error(__('Error saving settings', 'ultimakit-for-wp'));
        }
    }

    /**
     * Update scan schedule
     */
    private function update_scan_schedule($frequency) {
        $timestamp = wp_next_scheduled('ultimakit_scheduled_duplicate_scan');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'ultimakit_scheduled_duplicate_scan');
        }

        if ($frequency !== 'never') {
            wp_schedule_event(time(), $frequency, 'ultimakit_scheduled_duplicate_scan');
        }
    }

    /**
     * Run scheduled scan
     */
    public function run_scheduled_scan() {
        $results = $this->run_scan();
        // Store results or send notifications if needed
        $this->store_scan_results($results);
    }

    /**
     * Store scan results
     */
    private function store_scan_results($results) {

        $settings = array(
            'last_scan_results' => $results,
            'last_scan_time' => current_time('mysql')
        );

        $result = $this->ultimakit_update_module_setting($this->ID, 'settings', $settings, false);
    }
    
}
