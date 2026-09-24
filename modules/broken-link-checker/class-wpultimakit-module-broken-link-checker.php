<?php
/**
 * Class UltimaKit_Module_Broken_Link_Checker
 *
 * @since 1.0.0
 * @package    UltimaKit
 */

/**
 * Class UltimaKit_Module_Broken_Link_Checker
 *
 * This class provides functionality to scan and identify broken links within
 * the WordPress site content, including posts, pages, and custom post types.
 * It allows users to find and fix broken internal and external links to
 * maintain site health and SEO.
 *
 * @since 1.0.0
 */
class UltimaKit_Module_Broken_Link_Checker extends UltimaKit_Module_Manager {
    /**
     * Module identifier
     *
     * @var string
     */
    protected $ID = 'ultimakit_module_broken_link_checker';

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
    protected $plan = 'free';

    /**
     * Module category
     *
     * @var string
     */
    protected $category = 'Optimizations';

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
    protected $read_more_link = 'broken-link-checker-in-wordpress';

    /**
     * Module settings
     *
     * @var array
     */
    protected $settings;

    /**
     * Items per page for pagination
     *
     * @var int
     */
    private $items_per_page = 20;

    /**
     * Constructor
     */
    public function __construct() {
        $this->name = __('Broken Link Checker', 'ultimakit-for-wp');
        $this->description = __('Scan your site for broken internal or external links and fix them easily.', 'ultimakit-for-wp');
        $this->is_active = $this->isModuleActive($this->ID);

        $this->initializeModule();
    }

    /**
     * Initialize module
     */
    protected function initializeModule() {
        if ($this->is_active) {
            add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
            add_action('admin_menu', array($this, 'add_submenu_page'), 20);
            add_action('wp_ajax_scan_broken_links', array($this, 'scan_broken_links'));
            add_action('wp_ajax_update_link', array($this, 'update_link'));
        }
    }

    /**
     * Add submenu page
     */
    public function add_submenu_page() {
        global $submenu;
        if (!isset($submenu['wp-ultimakit-dashboard'])) {
            return;
        }

        add_submenu_page(
            'wp-ultimakit-dashboard',
            __('Broken Link Checker', 'ultimakit-for-wp'),
            __('Broken Link Checker', 'ultimakit-for-wp'),
            'manage_options',
            'wp-ultimakit-link-checker',
            array($this, 'render_page')
        );
    }

    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {

        wp_enqueue_script('jquery');

        wp_enqueue_style(
            'ultimakit-link-checker',
            plugin_dir_url(__FILE__) . 'module-style.css',
            [],
            '1.0.0'
        );

        wp_enqueue_script(
            'ultimakit-link-checker',
            plugin_dir_url(__FILE__) . 'module-script.js',
            ['jquery'],
            '1.0.0',
            true
        );

        wp_localize_script(
            'ultimakit-link-checker',
            'ultimakit_link_checker',
            array(
                'url' => admin_url('admin-ajax.php'),
                'scan_nonce' => wp_create_nonce('scan_broken_links'),
                'update_nonce' => wp_create_nonce('update_link'),
                'scanning' => __('Scanning...', 'ultimakit-for-wp'),
                'scan_error' => __('An error occurred while scanning.', 'ultimakit-for-wp'),
                'saving' => __('Updating...', 'ultimakit-for-wp'),
                'save' => __('Update Link', 'ultimakit-for-wp'),
                'updated' => __('Updated!', 'ultimakit-for-wp'),
                'error' => __('Error!', 'ultimakit-for-wp'),
                'select_type' => __('Please select at least one type of link to scan.', 'ultimakit-for-wp'),
                'scan_button' => __('Scan for Broken Links', 'ultimakit-for-wp'),
                'scan_again_button' => __('Scan Again for Broken Links', 'ultimakit-for-wp'),
            )
        );
    }

    /**
     * Render admin page
     */
    public function render_page() {
        $object = new UltimaKit_Helpers();
        ?>
        <div class="wrap">
            <?php $object->ultimakit_get_header(); ?>
            <div class="container bg-white text-dark p-3 mb-3">
                <!-- Nav tabs -->
                <ul class="nav nav-tabs" id="wpukTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <a class="nav-link active" id="settings-tab" data-bs-toggle="tab" href="#broken-link-checker-settings" role="tab" aria-controls="broken-link-checker-settings" aria-selected="true"><?php echo esc_html_e( 'Broken Link Checker', 'ultimakit-for-wp' ); ?></a>
                    </li>
                    
                </ul>

                <!-- Tab panes -->
                <div class="tab-content" id="wpukTabsContent">
                    <div class="tab-pane fade show active" id="broken-link-checker-settings" role="tabpanel" aria-labelledby="settings-tab">
                        <!-- Your modules content here -->
                        <div class="row">
                            <div class="notice notice-info">
                            <p><?php _e('Scan your website for broken internal and external links. Fix them to maintain good SEO and user experience.', 'ultimakit-for-wp'); ?></p>
                            </div>
                            
                            <div class="card mb-4">
                                <div class="card-body">
                                    <h5 class="card-title"><?php _e('Link Checker Settings', 'ultimakit-for-wp'); ?></h5>
                                    <!-- External Links Checkbox -->
                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="checkbox" id="check_external" checked>
                                        <label class="form-check-label" for="check_external">
                                            <?php _e('Check External Links', 'ultimakit-for-wp'); ?>
                                        </label>
                                    </div>

                                    <!-- Internal Links Checkbox -->
                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="checkbox" id="check_internal" checked>
                                        <label class="form-check-label" for="check_internal">
                                            <?php _e('Check Internal Links', 'ultimakit-for-wp'); ?>
                                        </label>
                                    </div>

                                    <button id="scan-links" class="btn btn-primary">
                                        <?php _e('Scan for Broken Links', 'ultimakit-for-wp'); ?>
                                    </button>
                                </div>
                            </div>

                            <div id="scan-progress" class="progress mb-4 d-none">
                                <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%"></div>
                            </div>

                            <div id="results-container"></div>
                        
                        </div>
                    </div>

                </div>
            </div>
        </div>
    


        <?php
    }

    /**
     * Scan for broken links
     */
    public function scan_broken_links() {
        check_ajax_referer('scan_broken_links', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array(
                'message' => __('Insufficient permissions', 'ultimakit-for-wp')
            ));
        }

        $check_external = isset($_POST['check_external']) ? filter_var($_POST['check_external'], FILTER_VALIDATE_BOOLEAN) : true;
        $check_internal = isset($_POST['check_internal']) ? filter_var($_POST['check_internal'], FILTER_VALIDATE_BOOLEAN) : true;
        $current_page = isset($_POST['page']) ? absint($_POST['page']) : 1;
        $offset = ($current_page - 1) * $this->items_per_page;

        // Get all posts, pages, and custom post types
        $args = array(
            'post_type' => 'any',
            'post_status' => 'publish',
            'posts_per_page' => $this->items_per_page,
            'offset' => $offset
        );

        $posts = get_posts($args);
        $broken_links = array();

        foreach ($posts as $post) {
            $content = $post->post_content;
            preg_match_all('/<a\s+.*?href=[\'"](.*?)[\'"].*?>/i', $content, $matches);
            
            if (!empty($matches[1])) {
                foreach ($matches[1] as $url) {
                    $is_external = strpos($url, home_url()) === false;
                    
                    if (($is_external && $check_external) || (!$is_external && $check_internal)) {
                        $response = wp_remote_head($url, array('timeout' => 5));
                        $response_code = wp_remote_retrieve_response_code($response);

                        if (is_wp_error($response) || $response_code >= 400) {
                            $broken_links[] = array(
                                'url' => $url,
                                'post_id' => $post->ID,
                                'post_title' => $post->post_title,
                                'is_external' => $is_external,
                                'response_code' => $response_code
                            );
                        }
                    }
                }
            }
        }

        $total_posts = wp_count_posts()->publish;
        $total_pages = ceil($total_posts / $this->items_per_page);

        if (empty($broken_links)) {
            echo '<div class="alert alert-success">' . __('No broken links found!', 'ultimakit-for-wp') . '</div>';
            wp_die();
        }

        $this->render_results_table($broken_links, $total_pages, $current_page);
        wp_die();
    }

    /**
     * Render results table
     */
    private function render_results_table($broken_links, $total_pages, $current_page) {
        ?>
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead class="table-light">
                    <tr>
                        <th><?php _e('URL', 'ultimakit-for-wp'); ?></th>
                        <th><?php _e('Location', 'ultimakit-for-wp'); ?></th>
                        <th><?php _e('Type', 'ultimakit-for-wp'); ?></th>
                        <th><?php _e('Status', 'ultimakit-for-wp'); ?></th>
                        <th><?php _e('Actions', 'ultimakit-for-wp'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($broken_links as $link): ?>
                        <tr>
                            <td>
                                <input type="text" class="form-control" id="url-<?php echo esc_attr($link['post_id']); ?>" 
                                       value="<?php echo esc_attr($link['url']); ?>">
                            </td>
                            <td>
                                <a href="<?php echo get_edit_post_link($link['post_id']); ?>" target="_blank">
                                    <?php echo esc_html($link['post_title']); ?>
                                </a>
                            </td>
                            <td>
                                <span class="badge <?php echo $link['is_external'] ? 'bg-warning' : 'bg-info'; ?>">
                                    <?php echo $link['is_external'] ? __('External', 'ultimakit-for-wp') : __('Internal', 'ultimakit-for-wp'); ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-danger">
                                    <?php echo $link['response_code']; ?>
                                </span>
                            </td>
                            <td>
                                <button class="update-link btn btn-primary btn-sm" 
                                        data-post-id="<?php echo esc_attr($link['post_id']); ?>"
                                        data-old-url="<?php echo esc_attr($link['url']); ?>">
                                    <?php _e('Update', 'ultimakit-for-wp'); ?>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($total_pages > 1): ?>
            <nav aria-label="Page navigation" class="mt-4">
                <ul class="pagination justify-content-center">
                    <?php if ($current_page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="#" data-page="<?php echo $current_page - 1; ?>">
                                <?php _e('Previous', 'ultimakit-for-wp'); ?>
                            </a>
                        </li>
                    <?php endif; ?>

                    <li class="page-item disabled">
                        <span class="page-link">
                            <?php printf(__('Page %s of %s', 'ultimakit-for-wp'), $current_page, $total_pages); ?>
                        </span>
                    </li>

                    <?php if ($current_page < $total_pages): ?>
                        <li class="page-item">
                            <a class="page-link" href="#" data-page="<?php echo $current_page + 1; ?>">
                                <?php _e('Next', 'ultimakit-for-wp'); ?>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        <?php endif;
    }

    /**
     * Update link
     */
    public function update_link() {
        check_ajax_referer('update_link', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', 'ultimakit-for-wp'));
        }

        $post_id = intval($_POST['post_id']);
        $old_url = sanitize_text_field($_POST['old_url']);
        $new_url = sanitize_text_field($_POST['new_url']);

        $post = get_post($post_id);
        if (!$post) {
            wp_send_json_error(__('Post not found', 'ultimakit-for-wp'));
        }

        $content = $post->post_content;
        $content = str_replace($old_url, $new_url, $content);

        $updated = wp_update_post(array(
            'ID' => $post_id,
            'post_content' => $content
        ));

        if ($updated) {
            wp_send_json_success(array(
                'message' => __('Link updated successfully', 'ultimakit-for-wp')
            ));
        } else {
            wp_send_json_error(__('Failed to update link', 'ultimakit-for-wp'));
        }
    }
}