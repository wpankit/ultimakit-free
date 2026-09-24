<?php
/**
 * Class UltimaKit_Module_Content_Expiry
 *
 * @since 1.0.0
 * @package    UltimaKit
 */

/**
 * Class UltimaKit_Module_Content_Expiry
 *
 * This class provides methods to control the display of dashboard widgets based on user preferences.
 * It allows users to selectively hide certain dashboard widgets to streamline their WordPress dashboard
 * experience and improve usability.
 *
 * @since 1.0.0
 */
class UltimaKit_Module_Content_Expiry extends UltimaKit_Module_Manager {
	/**
	 *
	 * @var string
	 */
	protected $ID = 'ultimakit_module_content_expiry';

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
	protected $read_more_link = 'content-expiry-reminder-in-wordpress';

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
		$this->name        = __( 'Content Expiry Reminder', 'ultimakit-for-wp' );
		$this->description = __( 'Notify users when specific posts or pages need to be updated (e.g., after 6 months or 1 year).', 'ultimakit-for-wp' );
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
			add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
			add_action('add_meta_boxes', array($this, 'add_expiry_meta_box'));
			add_action('save_post', array($this, 'save_expiry_date'));
			add_action('wp_dashboard_setup', array($this, 'add_dashboard_widget'));
			add_action('admin_notices', array($this, 'display_expiry_notices'));
			add_action('wp_ajax_dismiss_expiry_notice', array($this, 'dismiss_expiry_notice'));
			add_action('wp_ajax_save_expiry_settings', array($this, 'save_expiry_settings'));
			
			// Add hook for email notifications
			add_action('ultimakit_content_expiry_check', array($this, 'send_email_notifications'));

			// Schedule email notifications when email setting is enabled
			if ($this->getModuleSettings($this->ID, 'enable_email')) {
				$this->schedule_email_notifications();
			}
		
			// Handle email settings changes
			add_action('update_option_ultimakit_content_expiry_settings', array($this, 'handle_email_settings_change'), 10, 2);

			// Add hooks for custom columns
			add_filter('manage_posts_columns', array($this, 'add_expiry_column'));
			add_filter('manage_pages_columns', array($this, 'add_expiry_column'));
			add_action('manage_posts_custom_column', array($this, 'display_expiry_column'), 10, 2);
			add_action('manage_pages_custom_column', array($this, 'display_expiry_column'), 10, 2);
			
			// Make the column sortable
			add_filter('manage_edit-post_sortable_columns', array($this, 'make_expiry_column_sortable'));
			add_filter('manage_edit-page_sortable_columns', array($this, 'make_expiry_column_sortable'));
			
			// Handle custom column sorting
			add_action('pre_get_posts', array($this, 'handle_expiry_column_sorting'));
		}

	}

	/**
     * Enqueue admin assets.
     *
     * @param string $hook Current admin page.
     */
    public function enqueue_scripts($hook) {
        
        wp_enqueue_script(
            'ultimakit-content-expiry',
            plugin_dir_url(__FILE__) . 'module-script.js',
            ['jquery'],
            '1.0.0',
            true
        );

        wp_localize_script('ultimakit-content-expiry', 'ultimakitContentExpiry', array(
			'nonce' => wp_create_nonce('ultimakit_content_expiry_nonce'),
            'ajaxurl' => admin_url('admin-ajax.php'),
			'error' => __('An error occurred while saving settings.', 'ultimakit-for-wp'),
			'saveButton' => __('Save Settings', 'ultimakit-for-wp')
        ));

    }

    /**
     * Add submenu page
     */
    public function add_submenu_page() {
        // Check if parent menu exists
        global $submenu;
        if (!isset($submenu['wp-ultimakit-dashboard'])) {
            return;
        }

        add_submenu_page(
            'wp-ultimakit-dashboard',
            __('Content Expiry', 'ultimakit-for-wp'),
            __('Content Expiry', 'ultimakit-for-wp'),
            'manage_options',
            'wp-ultimakit-content-expiry',
            array($this, 'render_page')
        );
        
    }

	/**
	 * Schedule daily email check
	 */
   private function schedule_email_notifications() {
	   if (!wp_next_scheduled('ultimakit_content_expiry_check')) {
		   wp_schedule_event(time(), 'daily', 'ultimakit_content_expiry_check');
	   }
   }

   /**
	* Send email notifications for content that needs review
	*/
   public function send_email_notifications() {
	//    default_expiry
	   $enable_email = $this->getModuleSettings($this->ID, 'enable_email');
	   
	   // Check if email notifications are enabled
	   if (!$enable_email) {
		   return;
	   }

	   // Get content that needs review
	   $expiring_content = $this->get_expiring_content();
	   
	   if (empty($expiring_content)) {
		   return;
	   }

	   // Get admin email and site info
	   $admin_email = get_option('admin_email');
	   $site_name = get_bloginfo('name');
	   $site_url = get_bloginfo('url');

	   // Build email content
	   $content_list = '';
	   foreach ($expiring_content as $content) {
		   $edit_link = get_edit_post_link($content->ID, 'raw');
		   $expiry_date = get_post_meta($content->ID, '_content_expiry_date', true);
		   
		   $content_list .= sprintf(
			   "- %s\n  Type: %s\n  Review Date: %s\n  Edit Link: %s\n\n",
			   esc_html($content->post_title),
			   esc_html($content->post_type),
			   esc_html($expiry_date),
			   esc_url($edit_link)
		   );
	   }

	   // Email subject
	   $subject = sprintf(
		   '[%s] Content Review Reminder - %d items need attention',
		   $site_name,
		   count($expiring_content)
	   );

	   // Email body
	   $message = sprintf(
		   "Hello,\n\n" .
		   "This is an automated reminder from your website %s.\n\n" .
		   "The following content items need review:\n\n" .
		   "%s\n" .
		   "Please review and update these items as needed.\n\n" .
		   "You can manage all content reviews here: %s\n\n" .
		   "Best regards,\n" .
		   "UltimaKit Content Expiry Reminder",
		   $site_name,
		   $content_list,
		   admin_url('admin.php?page=wp-ultimakit-content-expiry')
	   );

	   // Email headers
	   $headers = array(
		   'Content-Type: text/plain; charset=UTF-8',
		   sprintf('From: %s <wordpress@%s>', $site_name, parse_url($site_url, PHP_URL_HOST))
	   );

	   // Send email
	   $sent = wp_mail($admin_email, $subject, $message, $headers);

	   // Log email status
	   update_option('ultimakit_content_expiry_last_notification', array(
		   'timestamp' => current_time('mysql'),
		   'status' => $sent ? 'success' : 'failed',
		   'items_count' => count($expiring_content)
	   ));
   }

   /**
	* Handle changes to email notification settings
	*/
   public function handle_email_settings_change($old_value, $new_value) {
	   // If email notifications were enabled
	   if (!empty($new_value['enable_email']) && (empty($old_value['enable_email']))) {
		   $this->schedule_email_notifications();
	   }
	   
	   // If email notifications were disabled
	   if (empty($new_value['enable_email']) && !empty($old_value['enable_email'])) {
		   wp_clear_scheduled_hook('ultimakit_content_expiry_check');
	   }
   }

	public function render_page() {
        $object = new UltimaKit_Helpers();
        $expiring_content = $this->get_expiring_content();
        ?>
		<style>	
            #content-expiry-settings .form-check-label{
                text-indent: unset !important;
            }
        </style>
        <div class="wrap">
			<?php $object->ultimakit_get_header(); ?>
			<div class="container bg-white text-dark p-3 mb-3">
				<!-- Nav tabs -->
				<ul class="nav nav-tabs" id="wpukTabs" role="tablist">
					<li class="nav-item" role="presentation">
						<a class="nav-link active" id="settings-tab" data-bs-toggle="tab" href="#content-expiry-settings" role="tab" aria-controls="content-expiry-settings" aria-selected="true"><?php echo esc_html_e( 'Content Expiry Reminder Settings', 'ultimakit-for-wp' ); ?></a>
					</li>
					
				</ul>

				<!-- Tab panes -->
				<div class="tab-content" id="wpukTabsContent">
					<div class="tab-pane fade show active" id="content-expiry-settings" role="tabpanel" aria-labelledby="settings-tab">
						<!-- Your modules content here -->
						<div class="row">
                            
							<div class="notice notice-info">
								<p><?php _e('Set and manage content expiry reminders for your posts and pages.', 'ultimakit-for-wp'); ?></p>
							</div>

							<?php
							// Get saved settings
							$settings['default_expiry'] = $this->getModuleSettings($this->ID, 'default_expiry', 6);
							$settings['enable_email'] = $this->getModuleSettings($this->ID, 'enable_email');
							?>
							<div class="card w-100">
								<div class="card-body">
									<div class="form-group mb-3">
										<label for="default_expiry"><?php _e('Default Expiry Period', 'ultimakit-for-wp'); ?></label>
										<select id="default_expiry" class="form-control" name="default_expiry">
											<option value="3" <?php selected($settings['default_expiry'], 3); ?>><?php _e('3 months', 'ultimakit-for-wp'); ?></option>
											<option value="6" <?php selected($settings['default_expiry'], 6); ?>><?php _e('6 months', 'ultimakit-for-wp'); ?></option>
											<option value="12" <?php selected($settings['default_expiry'], 12); ?>><?php _e('1 year', 'ultimakit-for-wp'); ?></option>
										</select>
									</div>

									<div class="form-check mb-3">
										<input class="form-check-input" type="checkbox" id="enable_email" name="enable_email" 
											<?php checked($settings['enable_email'], true); ?>>
										<label class="form-check-label" for="enable_email">
											<?php _e('Enable email notifications', 'ultimakit-for-wp'); ?>
										</label>
									</div>

									<button id="save-settings" class="btn btn-primary">
										<?php _e('Save Settings', 'ultimakit-for-wp'); ?>
									</button>
								</div>
							</div>

							<?php if (!empty($expiring_content)) : ?>
								<div class="card w-100 mt-4">
									<div class="card-body">
										<h5 class="card-title"><?php _e('Content Needing Review', 'ultimakit-for-wp'); ?></h5>
										<div class="table-responsive">
											<table class="table">
												<thead>
													<tr>
														<th><?php _e('Title', 'ultimakit-for-wp'); ?></th>
														<th><?php _e('Type', 'ultimakit-for-wp'); ?></th>
														<th><?php _e('Expiry Date', 'ultimakit-for-wp'); ?></th>
														<th><?php _e('Actions', 'ultimakit-for-wp'); ?></th>
													</tr>
												</thead>
												<tbody>
													<?php foreach ($expiring_content as $content) : ?>
														<tr>
															<td><?php echo esc_html($content->post_title); ?></td>
															<td><?php echo esc_html($content->post_type); ?></td>
															<td><?php echo esc_html(get_post_meta($content->ID, '_content_expiry_date', true)); ?></td>
															<td>
																<a target="_blank" href="<?php echo get_edit_post_link($content->ID); ?>" class="btn btn-sm btn-primary">
																	<?php _e('Edit', 'ultimakit-for-wp'); ?>
																</a>
															</td>
														</tr>
													<?php endforeach; ?>
												</tbody>
											</table>
										</div>
									</div>
								</div>
							<?php endif; ?>
						</div>
					</div>

				</div>
			</div>
		</div>

        
        <?php
    }

	public function add_expiry_meta_box() {
        $post_types = array('post', 'page');
        foreach ($post_types as $post_type) {
            add_meta_box(
                'content_expiry_meta_box',
                __('Content Expiry', 'ultimakit-for-wp'),
                array($this, 'render_meta_box'),
                $post_type,
                'side',
                'high'
            );
        }
    }

    public function render_meta_box($post) {
        wp_nonce_field('content_expiry_meta_box', 'content_expiry_nonce');
        $expiry_date = get_post_meta($post->ID, '_content_expiry_date', true);
        ?>
        <p>
            <label for="content_expiry_date"><?php _e('Review Date:', 'ultimakit-for-wp'); ?></label>
            <input type="date" id="content_expiry_date" name="content_expiry_date" 
                   value="<?php echo esc_attr($expiry_date); ?>" class="widefat">
        </p>
        <?php
    }

    public function save_expiry_date($post_id) {
        if (!isset($_POST['content_expiry_nonce']) || 
            !wp_verify_nonce($_POST['content_expiry_nonce'], 'content_expiry_meta_box')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        if (isset($_POST['content_expiry_date'])) {
            update_post_meta($post_id, '_content_expiry_date', sanitize_text_field($_POST['content_expiry_date']));
        }
    }

    private function get_expiring_content() {
        $args = array(
            'post_type' => array('post', 'page'),
            'posts_per_page' => -1,
            'meta_query' => array(
                array(
                    'key' => '_content_expiry_date',
                    'value' => date('Y-m-d'),
                    'compare' => '<=',
                    'type' => 'DATE'
                )
            )
        );

        return get_posts($args);
    }

    public function add_dashboard_widget() {
        wp_add_dashboard_widget(
            'ultimakit_content_expiry_widget',
            __('Content Needing Review [UltimaKit For WP]', 'ultimakit-for-wp'),
            array($this, 'render_dashboard_widget')
        );
    }

    public function render_dashboard_widget() {
        $expiring_content = $this->get_expiring_content();
        if (empty($expiring_content)) {
            echo '<p>' . __('No content currently needs review.', 'ultimakit-for-wp') . '</p>';
            return;
        }

        echo '<ul>';
        foreach ($expiring_content as $content) {
            printf(
                '<li><a href="%s">%s</a> - %s</li>',
                get_edit_post_link($content->ID),
                esc_html($content->post_title),
                esc_html(get_post_meta($content->ID, '_content_expiry_date', true))
            );
        }
        echo '</ul>';
    }

    public function display_expiry_notices() {
        $screen = get_current_screen();
        if ($screen->id === 'dashboard' || $screen->id === 'edit-post' || $screen->id === 'edit-page') {
            $expiring_content = $this->get_expiring_content();
            if (!empty($expiring_content)) {
                ?>
                <div class="notice notice-warning is-dismissible">
                    <p>
                        <?php 
                        printf(
                            __('You have %d pieces of content that need review. <a href="%s">View all</a>', 'ultimakit-for-wp'),
                            count($expiring_content),
                            admin_url('admin.php?page=wp-ultimakit-content-expiry')
                        );
                        ?>
                    </p>
                </div>
                <?php
            }
        }
    }

    public function dismiss_expiry_notice() {
        check_ajax_referer('ultimakit_content_expiry_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permission denied.', 'ultimakit-for-wp'));
        }

        // Store dismissed state in user meta
        update_user_meta(get_current_user_id(), '_ultimakit_expiry_notice_dismissed', time());
        wp_send_json_success();
    }

	/**
     * Save module settings via AJAX
     */
    public function save_expiry_settings() {
        check_ajax_referer('ultimakit_content_expiry_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('You do not have permission to perform this action.', 'ultimakit-for-wp'));
        }

        $default_expiry = isset($_POST['default_expiry']) ? absint($_POST['default_expiry']) : 6;
        $enable_email = isset($_POST['enable_email']) ? filter_var($_POST['enable_email'], FILTER_VALIDATE_BOOLEAN) : false;

        // Validate default expiry period
        if (!in_array($default_expiry, array(3, 6, 12))) {
            $default_expiry = 6; // Set default if invalid value
        }

        // Save settings
        $settings = array(
            'default_expiry' => $default_expiry,
            'enable_email' => $enable_email,
            'last_updated' => current_time('mysql')
        );

		$updated = $this->ultimakit_update_module_setting( $this->ID, 'settings', $settings, false );

        if ($updated) {
            // If email notifications are enabled, schedule the cron job
            if ($enable_email) {
                if (!wp_next_scheduled('ultimakit_content_expiry_check')) {
                    wp_schedule_event(time(), 'daily', 'ultimakit_content_expiry_check');
                }
            } else {
                // Remove the scheduled event if email notifications are disabled
                wp_clear_scheduled_hook('ultimakit_content_expiry_check');
            }

            wp_send_json_success(__('Settings saved successfully!', 'ultimakit-for-wp'));
        } else {
            wp_send_json_error(__('No changes were made or settings could not be saved.', 'ultimakit-for-wp'));
        }
    }

	/**
	 * Add expiry date column to posts/pages list
	 *
	 * @param array $columns Existing columns
	 * @return array Modified columns
	 */
	public function add_expiry_column($columns) {
		$new_columns = array();
		
		foreach ($columns as $key => $value) {
			$new_columns[$key] = $value;
			// Add our column before the date column
			if ($key === 'date') {
				$new_columns['content_expiry'] = __('Content Expiry', 'ultimakit-for-wp');
			}
		}
		
		return $new_columns;
	}

	/**
	 * Display content expiry date in the custom column
	 *
	 * @param string $column Column name
	 * @param int $post_id Post ID
	 */
	public function display_expiry_column($column, $post_id) {
		if ($column === 'content_expiry') {
			$expiry_date = get_post_meta($post_id, '_content_expiry_date', true);
			
			if (!empty($expiry_date)) {
				$date_object = new DateTime($expiry_date);
				$today = new DateTime();
				$date_format = get_option('date_format');
				
				// Check if content is expired
				if ($date_object <= $today) {
					echo '<span class="content-expired">';
					echo esc_html(date_i18n($date_format, strtotime($expiry_date)));
					echo ' <span class="dashicons dashicons-warning" style="color: #dc3545;"></span>';
					echo '</span>';
				} else {
					echo '<span class="content-not-expired">';
					echo esc_html(date_i18n($date_format, strtotime($expiry_date)));
					echo '</span>';
				}
			} else {
				echo '<span class="no-expiry">—</span>';
			}
		}
	}

	/**
	 * Make the expiry column sortable
	 *
	 * @param array $columns Sortable columns
	 * @return array Modified sortable columns
	 */
	public function make_expiry_column_sortable($columns) {
		$columns['content_expiry'] = 'content_expiry';
		return $columns;
	}

	/**
	 * Handle the sorting of the expiry column
	 *
	 * @param WP_Query $query The WordPress query object
	 */
	public function handle_expiry_column_sorting($query) {
		if (!is_admin()) {
			return;
		}

		$orderby = $query->get('orderby');

		if ('content_expiry' === $orderby) {
			$query->set('meta_key', '_content_expiry_date');
			$query->set('orderby', 'meta_value');
		}
	}

}
