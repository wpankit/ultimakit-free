<?php
/**
 * Class UltimaKit_Module_Activity_Logs
 *
 * @since 1.0.0
 * @package    UltimaKit
 */

/**
 * Class UltimaKit_Module_Activity_Logs
 *
 * This class provides methods to control the display of dashboard widgets based on user preferences.
 * It allows users to selectively hide certain dashboard widgets to streamline their WordPress dashboard
 * experience and improve usability.
 *
 * @since 1.0.0
 */
class UltimaKit_Module_Activity_Logs extends UltimaKit_Module_Manager {
	/**
	 *
	 * @var string
	 */
	protected $ID = 'ultimakit_module_activity_logs';

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
	protected $category = 'Utilities';

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
	protected $read_more_link = 'admin-activity-logger';

	/**
	 * The settings associated with the module, if any.
	 *
	 * @var array
	 */
	protected $settings;

	private $table_name;
	private $db_version = '1.0.0';
	private $db_version_key; // Increase this whenever you change the schema

	private $current_user_id;
	private $pending_login_user_id;

	/**
	 * Initializes the module with default values for properties and prepares
	 * any necessary setup or hooks into WordPress.
	 */
	public function __construct() {
		global $wpdb;

		$this->name        = __( 'Admin Activity Logger', 'ultimakit-for-wp' );
		$this->description = __( 'Tracks admin actions (e.g., post updates, plugin activations, user logins) and displays them in a simple log.', 'ultimakit-for-wp' );
		$this->is_active   = $this->isModuleActive( $this->ID );
		$this->settings    = 'yes';
		$this->initializeModule();

		$this->db_version_key = 'ultimakit_activity_logs_db_version';
		$this->table_name     = $wpdb->prefix . 'ultimakit_activity_logs';

		// Check and setup database when module is loaded
		add_action( 'init', array( $this, 'check_db_setup' ), 1 );
	}

	/**
	 * Create the activity logs table
	 *
	 * @return bool
	 */
	public function create_log_table() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS {$this->table_name} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            action varchar(255) NOT NULL,
            object_type varchar(50) NOT NULL,
            object_id bigint(20),
            details text,
            ip_address varchar(45),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY object_type (object_type),
            KEY created_at (created_at)
        ) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		// Run version-specific migrations
		$this->run_migrations( get_option( $this->db_version_key, '0' ) );

		// Update version in database
		update_option( $this->db_version_key, $this->db_version );
	}

	/**
	 * Run database migrations
	 */
	private function run_migrations( $from_version ) {
		global $wpdb;

		if ( version_compare( $from_version, '1.0.1', '<' ) ) {
			// Add new columns or modify existing ones
			// $wpdb->query("ALTER TABLE {$this->table_name} ADD COLUMN new_column varchar(100)");
		}
	}

	/**
	 * Check and setup database
	 */
	public function check_db_setup() {
		if ( ! $this->is_active ) {
			return;
		}

		$installed_version = get_option( $this->db_version_key, '0' );
		if ( ! $this->is_table_exists( $this->table_name ) || version_compare( $installed_version, $this->db_version, '<' ) ) {
			$this->create_log_table();
		}
	}

	/**
	 * Initializes the specific module within the application.
	 */
	protected function initializeModule() {
		if ( $this->is_active ) {
			global $wpdb;
			$this->table_name = $wpdb->prefix . 'ultimakit_activity_logs';

			add_action( 'admin_enqueue_scripts', array( $this, 'add_scripts' ) );
			add_action( 'admin_footer', array( $this, 'add_modal' ) );

			// Initialize hooks
			add_action( 'admin_menu', array( $this, 'add_menu_page' ) );
			add_action( 'admin_init', array( $this, 'handle_csv_export' ) );
			add_action( 'admin_logger_cleanup_cron', array( $this, 'delete_old_logs' ) );
			add_action( 'wp', array( $this, 'schedule_retention_cleanup' ) );
			add_action( 'wp_ajax_ultimakit_delete_all_logs', array( $this, 'ajax_delete_all_logs' ) );

			if ( 'on' == $this->getModuleSettings( $this->ID, 'post_logs', 'off' ) ) {
				add_action( 'post_updated', array( $this, 'log_post_update' ), 10, 3 );
				add_action( 'delete_post', array( $this, 'log_post_deletion' ) );
				add_action( 'transition_post_status', array( $this, 'log_post_status_change' ), 10, 3 );
			}

			if ( 'on' == $this->getModuleSettings( $this->ID, 'category_logs', 'off' ) ) {
				add_action( 'created_term', array( $this, 'log_category_creation' ) );
				add_action( 'delete_term', array( $this, 'log_category_deletion' ), 10, 4 );
				add_action( 'edited_term', array( $this, 'log_category_update' ) );
			}

			if ( 'on' == $this->getModuleSettings( $this->ID, 'comments_logs', 'off' ) ) {
				add_action( 'wp_insert_comment', array( $this, 'log_comment_creation' ) );
				add_action( 'delete_comment', array( $this, 'log_comment_deletion' ) );
				add_action( 'edit_comment', array( $this, 'log_comment_update' ) );
				add_action( 'transition_comment_status', array( $this, 'log_comment_status_change' ), 10, 3 );
			}

			if ( 'on' == $this->getModuleSettings( $this->ID, 'users_logs', 'off' ) ) {
				add_action( 'user_register', array( $this, 'log_user_creation' ) );
				add_action( 'delete_user', array( $this, 'log_user_deletion' ) );
				add_action( 'profile_update', array( $this, 'log_profile_update' ) );
				add_action( 'wp_login', array( $this, 'log_user_login' ), 10, 2 );
				add_action( 'wp_login_failed', array( $this, 'log_failed_login' ), 10, 2 );
				// add_action('init', array($this, 'store_user_id'));
				add_action( 'clear_auth_cookie', array( $this, 'log_user_logout' ) );
				add_action( 'set_user_role', array( $this, 'log_role_change' ), 10, 3 );
				add_action( 'add_user_role', array( $this, 'log_role_addition' ), 10, 2 );
				add_action( 'remove_user_role', array( $this, 'log_role_removal' ), 10, 2 );
			}

			if ( 'on' == $this->getModuleSettings( $this->ID, 'plugins_logs', 'off' ) ) {
				add_action( 'activated_plugin', array( $this, 'log_plugin_activation' ) );
				add_action( 'deactivated_plugin', array( $this, 'log_plugin_deactivation' ) );
				add_action( 'switch_theme', array( $this, 'log_theme_switch' ), 10, 3 );
			}

			if ( 'on' == $this->getModuleSettings( $this->ID, 'media_logs', 'off' ) ) {
				add_action( 'add_attachment', array( $this, 'log_media_upload' ) );
				add_action( 'delete_attachment', array( $this, 'log_media_deletion' ) );
				add_action( 'edit_attachment', array( $this, 'log_media_update' ) );
			}

			if ( 'on' == $this->getModuleSettings( $this->ID, 'options_logs', 'off' ) ) {
				add_action( 'updated_option', array( $this, 'log_option_update' ), 10, 3 );
			}

			if ( 'on' == $this->getModuleSettings( $this->ID, 'menu_logs', 'off' ) ) {
				add_action( 'wp_update_nav_menu', array( $this, 'log_menu_update' ) );
				add_action( 'wp_create_nav_menu', array( $this, 'log_menu_creation' ) );
				add_action( 'wp_delete_nav_menu', array( $this, 'log_menu_deletion' ) );
			}

			if ( 'on' == $this->getModuleSettings( $this->ID, 'widget_logs', 'off' ) ) {
				add_action( 'update_option_sidebars_widgets', array( $this, 'log_widget_update' ), 10, 2 );
			}
		}
	}

	/**
	 * Enqueues scripts for the theme or plugin.
	 *
	 * This function handles the registration and enqueuing of JavaScript files required
	 * by the theme or plugin. It ensures that scripts are loaded in the correct order and
	 * that dependencies are managed properly. Scripts can include both local and external
	 * resources, and may be conditionally loaded based on the context or user actions.
	 *
	 * Use this function to enqueue all JavaScript necessary for the functionality of your
	 * theme or plugin, adhering to WordPress best practices for script registration and
	 * enqueuing.
	 *
	 * @return void
	 */
	public function add_scripts() {
		wp_enqueue_script(
			'ultimakit-module-script-' . $this->ID,
			plugins_url( '/module-script.js', __FILE__ ),
			array( 'jquery' ),
			ULTIMAKIT_FOR_WP_VERSION,
			true
		);

		wp_localize_script(
			'ultimakit-module-script-' . $this->ID,
			'ultimakitL10n',
			array(
				'confirmDeleteAll' => __( 'Are you sure you want to delete all logs? This action cannot be undone.', 'ultimakit-for-wp' ),
				'deleteLogsNonce'  => wp_create_nonce( 'ultimakit_delete_logs_nonce' ),
				'ajaxError'        => __( 'An error occurred while processing your request.', 'ultimakit-for-wp' ),
			)
		);
	}

	public function add_menu_page() {
		add_submenu_page(
			'wp-ultimakit-dashboard',
			__( 'Activity Logs', 'ultimakit-for-wp' ),
			__( 'Activity Logs', 'ultimakit-for-wp' ),
			'manage_options',
			'wp-ultimakit-activity-log-list',
			array( $this, 'display_logs_page' )
		);
	}

	public function add_modal() {
		$arguments          = array();
		$arguments['ID']    = $this->ID;
		$arguments['title'] = __( 'Admin Activity Logger', 'ultimakit-for-wp' );

		$arguments['fields'] = array(
			'log_retention' => array(
				'type'  => 'number',
				'label' => __( 'Log Retention Period (days)', 'ultimakit-for-wp' ),
				'value' => $this->getModuleSettings( $this->ID, 'log_retention', 30 ),
				'desc'  => __( 'Logs older than this number of days will be automatically deleted.', 'ultimakit-for-wp' ),
			),
			'post_logs'     => array(
				'type'  => 'switch',
				'label' => __( 'Track activity for Posts and Pages', 'ultimakit-for-wp' ),
				'value' => $this->getModuleSettings( $this->ID, 'post_logs', 'off' ),
			),
			'category_logs' => array(
				'type'  => 'switch',
				'label' => __( 'Track activity for Categories', 'ultimakit-for-wp' ),
				'value' => $this->getModuleSettings( $this->ID, 'category_logs', 'off' ),
			),
			'comments_logs' => array(
				'type'  => 'switch',
				'label' => __( 'Track activity for Comments', 'ultimakit-for-wp' ),
				'value' => $this->getModuleSettings( $this->ID, 'comments_logs', 'off' ),
			),
			'users_logs'    => array(
				'type'  => 'switch',
				'label' => __( 'Track activity for Users', 'ultimakit-for-wp' ),
				'value' => $this->getModuleSettings( $this->ID, 'users_logs', 'off' ),
			),
			'plugins_logs'  => array(
				'type'  => 'switch',
				'label' => __( 'Track activity for Plugins', 'ultimakit-for-wp' ),
				'value' => $this->getModuleSettings( $this->ID, 'plugins_logs', 'off' ),
			),
			'media_logs'    => array(
				'type'  => 'switch',
				'label' => __( 'Track activity for Media', 'ultimakit-for-wp' ),
				'value' => $this->getModuleSettings( $this->ID, 'media_logs', 'off' ),
			),
			'options_logs'  => array(
				'type'  => 'switch',
				'label' => __( 'Track activity for Options', 'ultimakit-for-wp' ),
				'value' => $this->getModuleSettings( $this->ID, 'options_logs', 'off' ),
			),
			'menu_logs'     => array(
				'type'  => 'switch',
				'label' => __( 'Track activity for Nav Menu', 'ultimakit-for-wp' ),
				'value' => $this->getModuleSettings( $this->ID, 'menu_logs', 'off' ),
			),
			'widget_logs'   => array(
				'type'  => 'switch',
				'label' => __( 'Track activity for Widgets', 'ultimakit-for-wp' ),
				'value' => $this->getModuleSettings( $this->ID, 'widget_logs', 'off' ),
			),
		);

		$this->ultimakit_generate_modal( $arguments );
	}

	/**
	 * Delete all logs from the database
	 *
	 * @return array Response array with status and message
	 */
	public function delete_all_logs() {
		global $wpdb;

		try {
			// Get the table name
			$table_name = $wpdb->prefix . 'ultimakit_activity_logs';

			// Delete all records from the table
			$result = $wpdb->query( "TRUNCATE TABLE {$table_name}" );

			if ( $result !== false ) {
				return array(
					'success' => true,
					'message' => __( 'All logs have been successfully deleted.', 'ultimakit-for-wp' ),
				);
			} else {
				return array(
					'success' => false,
					'message' => __( 'Failed to delete logs. Database error occurred.', 'ultimakit-for-wp' ),
				);
			}
		} catch ( Exception $e ) {
			return array(
				'success' => false,
				'message' => sprintf(
					__( 'Error deleting logs: %s', 'ultimakit-for-wp' ),
					$e->getMessage()
				),
			);
		}
	}

	/**
	 * Ajax callback to handle delete all logs request
	 */
	public function ajax_delete_all_logs() {
		// Verify nonce
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'ultimakit_delete_logs_nonce' ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Security check failed.', 'ultimakit-for-wp' ),
				)
			);
		}

		// Check user capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'You do not have permission to perform this action.', 'ultimakit-for-wp' ),
				)
			);
		}

		// Delete all logs
		$result = $this->delete_all_logs();

		if ( $result['success'] ) {
			wp_send_json_success(
				array(
					'message' => $result['message'],
				)
			);
		} else {
			wp_send_json_error(
				array(
					'message' => $result['message'],
				)
			);
		}
	}

	public function display_logs_page() {
		global $wpdb;

		// Pagination settings
		$items_per_page = 20;
		$current_page   = isset( $_GET['paged'] ) ? max( 1, intval( $_GET['paged'] ) ) : 1;
		$offset         = ( $current_page - 1 ) * $items_per_page;

		// Get total items for pagination
		$total_items = $wpdb->get_var( "SELECT COUNT(*) FROM {$this->table_name}" );
		$total_pages = ceil( $total_items / $items_per_page );

		// Get logs with pagination
		$logs = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT l.*, u.display_name 
                FROM {$this->table_name} l 
                LEFT JOIN {$wpdb->users} u ON l.user_id = u.ID 
                ORDER BY l.created_at DESC 
                LIMIT %d OFFSET %d",
				$items_per_page,
				$offset
			)
		);
		?>

		<div class="wrap">
			<?php $this->ultimakit_get_header(); ?>
			<div class="container bg-white text-dark p-3 mb-3">
				<!-- Nav tabs -->
				<ul class="nav nav-tabs" id="wpukTabs" role="tablist">
					<li class="nav-item" role="presentation">
						<a class="nav-link active" id="settings-tab" data-bs-toggle="tab" href="#activity-logs-list" role="tab" aria-controls="activity-logs-list" aria-selected="true">
							<?php esc_html_e( 'Activity Logs', 'ultimakit-for-wp' ); ?>
						</a>
					</li>
				</ul>

				<!-- Tab panes -->
				<div class="tab-content" id="wpukTabsContent">
					<div class="tab-pane fade show active" id="activity-logs-list" role="tabpanel" aria-labelledby="settings-tab">
						<div class="row">
							<!-- Export Button -->
							<form method="post" class="mb-4 mt-4">
								<input type="hidden" name="export_logs" value="1">
								<?php wp_nonce_field( 'ultimakit_export_activity_logs' ); ?>
								<button type="submit" class="btn btn-success bg-primary">
									<i class="dashicons dashicons-download"></i> 
									<?php esc_html_e( 'Export to CSV', 'ultimakit-for-wp' ); ?>
								</button>

								<button id="delete-logs-button" type="button" class="btn btn-success bg-primary">
									<i class="dashicons dashicons-trash"></i>
									<?php _e( 'Delete All Logs', 'ultimakit-for-wp' ); ?>
								</button>

							</form>
							<!-- Logs Table -->
							<div class="table-responsive">
								<table class="table table-striped table-bordered">
									<thead>
										<tr>
											<th class="text-end"><?php esc_html_e( 'Date/Time', 'ultimakit-for-wp' ); ?></th>
											<th class="text-end"><?php esc_html_e( 'User', 'ultimakit-for-wp' ); ?></th>
											<th class="text-end"><?php esc_html_e( 'Action', 'ultimakit-for-wp' ); ?></th>
											<th class="text-end"><?php esc_html_e( 'Details', 'ultimakit-for-wp' ); ?></th>
											<th class="text-end"><?php esc_html_e( 'IP Address', 'ultimakit-for-wp' ); ?></th>
										</tr>
									</thead>
									<tbody>
										<?php foreach ( $logs as $log ) : ?>
											<tr>
												<td>
												<?php
													$timestamp    = strtotime( $log->created_at );
													$current_time = current_time( 'timestamp' );
													$time_diff    = $current_time - $timestamp;

												if ( $time_diff < DAY_IN_SECONDS ) {
													echo esc_html(
														sprintf(
															__( '%s ago', 'ultimakit-for-wp' ),
															human_time_diff( $timestamp, $current_time )
														)
													);
												} else {
													// Custom date format for older entries
													echo esc_html( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $timestamp ) );
												}
												?>
												</td>
												<td>
													<?php
													if ( $log->display_name ) {
														echo esc_html( $log->display_name );
													} else {
														esc_html_e( 'Deleted User', 'ultimakit-for-wp' );
													}
													?>
												</td>
												<td>
													<span class="badge <?php echo esc_attr( $this->get_action_badge_class( $log->action ) ); ?>">
														<?php echo esc_html( $this->format_action_name( $log->action ) ); ?>
													</span>
												</td>
												<td style="width: 33%;"><?php echo esc_html( $log->details ); ?></td>
												<td><?php echo esc_html( $log->ip_address ); ?></td>
											</tr>
										<?php endforeach; ?>
									</tbody>
								</table>
							</div>
					
							<!-- Pagination -->
							<?php if ( $total_pages > 1 ) : ?>
							<nav aria-label="Activity log pagination" class="mt-4">
								<ul class="pagination justify-content-center">
									<?php
									// Previous button
									if ( $current_page > 1 ) :
										?>
										<li class="page-item">
											<a class="page-link" href="<?php echo esc_url( add_query_arg( 'paged', $current_page - 1 ) ); ?>">
												<?php esc_html_e( 'Previous', 'ultimakit-for-wp' ); ?>
											</a>
										</li>
										<?php
									endif;

									// Calculate range of visible pages
									$range     = 2; // Number of pages to show on each side of current page
									$showitems = ( $range * 2 ) + 1;

									// First page
									if ( $current_page > $range + 1 ) :
										?>
										<li class="page-item">
											<a class="page-link" href="<?php echo esc_url( add_query_arg( 'paged', 1 ) ); ?>">1</a>
										</li>
										<?php if ( $current_page > $range + 2 ) : ?>
											<li class="page-item disabled"><span class="page-link">...</span></li>
											<?php
										endif;
									endif;

									// Main pagination
									for ( $i = 1; $i <= $total_pages; $i++ ) :
										if ( $i >= $current_page - $range && $i <= $current_page + $range ) :
											?>
											<li class="page-item <?php echo $current_page == $i ? 'active' : ''; ?>">
												<a class="page-link" href="<?php echo esc_url( add_query_arg( 'paged', $i ) ); ?>">
													<?php echo esc_html( $i ); ?>
												</a>
											</li>
											<?php
										endif;
									endfor;

									// Last page
									if ( $current_page < $total_pages - $range ) :
										if ( $current_page < $total_pages - $range - 1 ) :
											?>
											<li class="page-item disabled"><span class="page-link">...</span></li>
										<?php endif; ?>
										<li class="page-item">
											<a class="page-link" href="<?php echo esc_url( add_query_arg( 'paged', $total_pages ) ); ?>">
												<?php echo esc_html( $total_pages ); ?>
											</a>
										</li>
										<?php
									endif;

									// Next button
									if ( $current_page < $total_pages ) :
										?>
										<li class="page-item">
											<a class="page-link" href="<?php echo esc_url( add_query_arg( 'paged', $current_page + 1 ) ); ?>">
												<?php esc_html_e( 'Next', 'ultimakit-for-wp' ); ?>
											</a>
										</li>
									<?php endif; ?>
								</ul>
							</nav>
						<?php endif; ?>
						</div>
					</div>
				</div>
			</div>
		</div>

		<style>
			.wrap { max-width: 98%; }
			.page-item .page-link{
				color: #6610F2;
			}
			.active>.page-link, .page-link.active{
				background-color: #6610F2 !important;
				border:none;
				color: #fff;
			}
		</style>
		<?php
	}

	private function log_activity( $action, $object_type, $object_id = 0, $details = '' ) {
		global $wpdb;

		$user_id    = ( $action === 'user_login' ) ? $object_id : get_current_user_id();
		$ip_address = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';

		$wpdb->insert(
			$this->table_name,
			array(
				'user_id'     => $user_id,
				'action'      => $action,
				'object_type' => $object_type,
				'object_id'   => $object_id,
				'details'     => $details,
				'ip_address'  => $ip_address,
				'created_at'  => current_time( 'mysql' ),
			),
			array( '%d', '%s', '%s', '%d', '%s', '%s', '%s' )
		);
	}

	/**
	 * Log post update with detailed change tracking
	 *
	 * @param int      $post_id     The post ID
	 * @param WP_Post  $post_after  The post object after the update
	 * @param WP_Post  $post_before The post object before the update
	 * @return void
	 */
	public function log_post_update( $post_id, $post_after, $post_before ) {
		try {
			// Verify post exists
			if ( ! $post_id || ! is_numeric( $post_id ) ) {
				throw new Exception( 'Invalid post ID provided' );
			}

			// Get post object
			$post = get_post( $post_id );
			if ( ! $post ) {
				return;
			}

			// Skip logging for nav menu items
			if ( $post->post_type === 'nav_menu_item' ) {
				return;
			}

			// Get current user
			$current_user = wp_get_current_user();

			// Track specific changes
			$changes = $this->detect_post_changes( $post_before, $post_after );

			// Get additional post meta changes
			$meta_changes = $this->detect_post_meta_changes( $post_id );

			// Get taxonomies changes
			$taxonomy_changes = $this->detect_taxonomy_changes( $post_id, $post_before, $post_after );

			// Prepare detailed metadata
			$meta_data = array(
				'post_details'     => array(
					'post_type'   => $post_after->post_type,
					'post_status' => array(
						'before' => $post_before->post_status,
						'after'  => $post_after->post_status,
					),
					'permalink'   => get_permalink( $post_id ),
					'edit_link'   => get_edit_post_link( $post_id ),
				),
				'changes'          => $changes,
				'meta_changes'     => $meta_changes,
				'taxonomy_changes' => $taxonomy_changes,
				'revision_id'      => wp_get_post_revisions( $post_id, array( 'posts_per_page' => 1 ) )[0]->ID ?? null,
				'editor_details'   => array(
					'user_id'    => $current_user->ID,
					'user_login' => $current_user->user_login,
					'user_email' => $current_user->user_email,
					'user_roles' => $current_user->roles,
				),
				'context'          => array(
					'timestamp'   => current_time( 'mysql' ),
					'user_agent'  => isset( $_SERVER['HTTP_USER_AGENT'] )
						? sanitize_text_field( $_SERVER['HTTP_USER_AGENT'] )
						: '',
					'referer'     => wp_get_referer(),
					'request_url' => isset( $_SERVER['REQUEST_URI'] )
						? esc_url_raw( $_SERVER['REQUEST_URI'] )
						: '',
				),
			);

			// Create detailed log message
			$log_message = $this->create_post_update_message(
				$post_after,
				$post_before,
				$changes,
				$meta_changes,
				$taxonomy_changes
			);

			// Log the activity
			$log_result = $this->log_activity(
				'post_update',
				'post',
				$post_id,
				$log_message,
				$meta_data
			);

			// Debug logging
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log(
					sprintf(
						'Post update logged - ID: %d, Title: %s, Status: %s',
						$post_id,
						$post_after->post_title,
						$log_result ? 'Success' : 'Failed'
					)
				);
			}
		} catch ( Exception $e ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'Error logging post update: ' . $e->getMessage() );
			}
		}
	}

	/**
	 * Detect changes between post versions
	 *
	 * @param WP_Post $post_before
	 * @param WP_Post $post_after
	 * @return array
	 */
	private function detect_post_changes( $post_before, $post_after ) {
		$changes         = array();
		$fields_to_check = array(
			'post_title'     => __( 'Title', 'ultimakit-for-wp' ),
			'post_content'   => __( 'Content', 'ultimakit-for-wp' ),
			'post_excerpt'   => __( 'Excerpt', 'ultimakit-for-wp' ),
			'post_status'    => __( 'Status', 'ultimakit-for-wp' ),
			'post_name'      => __( 'Slug', 'ultimakit-for-wp' ),
			'post_parent'    => __( 'Parent', 'ultimakit-for-wp' ),
			'menu_order'     => __( 'Menu Order', 'ultimakit-for-wp' ),
			'post_password'  => __( 'Password', 'ultimakit-for-wp' ),
			'comment_status' => __( 'Comment Status', 'ultimakit-for-wp' ),
			'ping_status'    => __( 'Ping Status', 'ultimakit-for-wp' ),
		);

		foreach ( $fields_to_check as $field => $label ) {
			if ( $post_before->$field !== $post_after->$field ) {
				$changes[ $field ] = array(
					'label'  => $label,
					'before' => $post_before->$field,
					'after'  => $post_after->$field,
				);
			}
		}

		return $changes;
	}

	/**
	 * Detect post meta changes
	 *
	 * @param int $post_id
	 * @return array
	 */
	private function detect_post_meta_changes( $post_id ) {
		$meta_changes = array();

		// Get the current revision
		$latest_revision = array_values( wp_get_post_revisions( $post_id ) )[0] ?? null;

		if ( $latest_revision ) {
			$old_meta = get_post_meta( $latest_revision->ID );
			$new_meta = get_post_meta( $post_id );

			// Track added, modified, and removed meta
			foreach ( $new_meta as $key => $value ) {
				if ( ! isset( $old_meta[ $key ] ) ) {
					$meta_changes['added'][ $key ] = $value[0];
				} elseif ( $old_meta[ $key ] !== $value ) {
					$meta_changes['modified'][ $key ] = array(
						'before' => $old_meta[ $key ][0],
						'after'  => $value[0],
					);
				}
			}

			foreach ( $old_meta as $key => $value ) {
				if ( ! isset( $new_meta[ $key ] ) ) {
					$meta_changes['removed'][ $key ] = $value[0];
				}
			}
		}

		return $meta_changes;
	}

	/**
	 * Detect taxonomy term changes
	 *
	 * @param int     $post_id
	 * @param WP_Post $post_before
	 * @param WP_Post $post_after
	 * @return array
	 */
	private function detect_taxonomy_changes( $post_id, $post_before, $post_after ) {
		$taxonomy_changes = array();
		$taxonomies       = get_object_taxonomies( $post_after->post_type );

		foreach ( $taxonomies as $taxonomy ) {
			$old_terms = wp_get_object_terms( $post_before->ID, $taxonomy, array( 'fields' => 'names' ) );
			$new_terms = wp_get_object_terms( $post_after->ID, $taxonomy, array( 'fields' => 'names' ) );

			if ( $old_terms != $new_terms ) {
				$taxonomy_changes[ $taxonomy ] = array(
					'before'  => $old_terms,
					'after'   => $new_terms,
					'added'   => array_diff( $new_terms, $old_terms ),
					'removed' => array_diff( $old_terms, $new_terms ),
				);
			}
		}

		return $taxonomy_changes;
	}

	/**
	 * Create detailed post update message
	 *
	 * @param WP_Post $post_after
	 * @param WP_Post $post_before
	 * @param array   $changes
	 * @param array   $meta_changes
	 * @param array   $taxonomy_changes
	 * @return string
	 */
	private function create_post_update_message( $post_after, $post_before, $changes, $meta_changes, $taxonomy_changes ) {
		$messages = array();

		// Base message
		$messages[] = sprintf(
			/* translators: %s: post title */
			__( 'Updated post "%s"', 'ultimakit-for-wp' ),
			$post_after->post_title
		);

		// Add specific changes
		if ( ! empty( $changes ) ) {
			foreach ( $changes as $field => $change ) {
				$messages[] = sprintf(
					/* translators: 1: field label, 2: old value, 3: new value */
					__( 'Changed %1$s from "%2$s" to "%3$s"', 'ultimakit-for-wp' ),
					$change['label'],
					$this->truncate_string( $change['before'] ),
					$this->truncate_string( $change['after'] )
				);
			}
		}

		// Add taxonomy changes
		if ( ! empty( $taxonomy_changes ) ) {
			foreach ( $taxonomy_changes as $taxonomy => $change ) {
				if ( ! empty( $change['added'] ) ) {
					$messages[] = sprintf(
						/* translators: 1: taxonomy name, 2: added terms */
						__( 'Added %1$s terms: %2$s', 'ultimakit-for-wp' ),
						$taxonomy,
						implode( ', ', $change['added'] )
					);
				}
				if ( ! empty( $change['removed'] ) ) {
					$messages[] = sprintf(
						/* translators: 1: taxonomy name, 2: removed terms */
						__( 'Removed %1$s terms: %2$s', 'ultimakit-for-wp' ),
						$taxonomy,
						implode( ', ', $change['removed'] )
					);
				}
			}
		}

		return implode( '. ', $messages );
	}

	/**
	 * Truncate string for log messages
	 *
	 * @param string $string
	 * @param int    $length
	 * @return string
	 */
	private function truncate_string( $string, $length = 50 ) {
		if ( strlen( $string ) > $length ) {
			return substr( $string, 0, $length ) . '...';
		}
		return $string;
	}

	/**
	 * Log user creation with detailed information
	 *
	 * @param int $user_id The ID of the newly created user
	 * @return void
	 */
	public function log_user_creation( $user_id ) {
		try {
			// Get the new user object
			$new_user = get_userdata( $user_id );
			if ( ! $new_user ) {
				throw new Exception( 'Invalid user ID or user not found' );
			}

			// Get the user who performed the action
			$current_user = wp_get_current_user();

			// Get user registration method
			$registration_method = $this->determine_registration_method();

			// Prepare metadata
			$meta_data = array(
				'user_details' => array(
					'email'               => $new_user->user_email,
					'username'            => $new_user->user_login,
					'roles'               => $new_user->roles,
					'registration_date'   => $new_user->user_registered,
					'registration_method' => $registration_method,
				),
				'created_by'   => array(
					'id'         => $current_user->ID ?: 'system',
					'username'   => $current_user->user_login ?: 'system',
					'ip_address' => $this->get_client_ip(),
				),
			);

			// Create descriptive log message
			$log_message = sprintf(
				/* translators: 1: username, 2: email, 3: roles, 4: created by */
				__( 'New user created - Username: %1$s, Email: %2$s, Roles: %3$s, Created by: %4$s', 'ultimakit-for-wp' ),
				$new_user->user_login,
				$new_user->user_email,
				implode( ', ', $new_user->roles ),
				$current_user->ID ? $current_user->user_login : __( 'system', 'ultimakit-for-wp' )
			);

			// Log the activity
			$this->log_activity(
				'user_create',
				'user',
				$user_id,
				$log_message,
				$meta_data
			);

		} catch ( Exception $e ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'Error logging user creation: ' . $e->getMessage() );
			}
		}
	}

	/**
	 * Determine the method used for user registration
	 *
	 * @return string
	 */
	private function determine_registration_method() {
		if ( isset( $_POST['action'] ) && $_POST['action'] === 'createuser' ) {
			return 'admin_created';
		}
		if ( isset( $_POST['action'] ) && $_POST['action'] === 'register' ) {
			return 'user_registration';
		}
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			return 'wp_cli';
		}
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return 'rest_api';
		}
		if ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) {
			return 'xml_rpc';
		}
		return 'other';
	}

	/**
	 * Get client IP address
	 *
	 * @return string
	 */
	private function get_client_ip() {
		$ip_headers = array( 'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR' );
		foreach ( $ip_headers as $header ) {
			if ( isset( $_SERVER[ $header ] ) ) {
				return sanitize_text_field( $_SERVER[ $header ] );
			}
		}
		return '';
	}

	/**
	 * Log user deletion with detailed information
	 *
	 * @param int $user_id The ID of the user being deleted
	 * @return void
	 */
	public function log_user_deletion( $user_id ) {
		try {
			// Get user data before deletion
			$deleted_user = get_userdata( $user_id );
			if ( ! $deleted_user ) {
				throw new Exception( 'Invalid user ID or user not found' );
			}

			// Get the user performing the deletion
			$current_user = wp_get_current_user();

			// Get user's posts handling preference
			$reassign_posts = isset( $_POST['delete_option'] ) && $_POST['delete_option'] === 'reassign';
			$reassign_to    = isset( $_POST['reassign_user'] ) ? absint( $_POST['reassign_user'] ) : null;

			// Get user's roles and capabilities before deletion
			$user_roles = ! empty( $deleted_user->roles ) ? $deleted_user->roles : array( 'none' );

			// Get user's last login if you have that data
			$last_login = get_user_meta( $user_id, 'last_login', true );

			// Prepare metadata
			$meta_data = array(
				'deleted_user'     => array(
					'username'        => $deleted_user->user_login,
					'email'           => $deleted_user->user_email,
					'display_name'    => $deleted_user->display_name,
					'roles'           => $user_roles,
					'registered_date' => $deleted_user->user_registered,
					'last_login'      => $last_login ?: 'unknown',
					'post_count'      => count_user_posts( $user_id ),
				),
				'deletion_details' => array(
					'deleted_by'     => $current_user->ID ? $current_user->user_login : 'system',
					'posts_handling' => $reassign_posts ?
						sprintf( 'reassigned to user ID: %d', $reassign_to ) :
						'deleted',
					'timestamp'      => current_time( 'mysql' ),
				),
			);

			// Create descriptive log message
			$log_message = sprintf(
				/* translators: 1: username, 2: email, 3: roles, 4: deleted by, 5: posts handling */
				__( 'Deleted user - Username: %1$s, Email: %2$s, Roles: %3$s, Deleted by: %4$s, Posts: %5$s', 'ultimakit-for-wp' ),
				$deleted_user->user_login,
				$deleted_user->user_email,
				implode( ', ', $user_roles ),
				$current_user->ID ? $current_user->user_login : __( 'system', 'ultimakit-for-wp' ),
				$reassign_posts ?
					sprintf( __( 'reassigned to user ID: %d', 'ultimakit-for-wp' ), $reassign_to ) :
					__( 'deleted', 'ultimakit-for-wp' )
			);

			// Log the activity using existing method
			$this->log_activity(
				'user_delete',
				'user',
				$user_id,
				$log_message,
				$meta_data
			);

			// Debug logging if enabled
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log(
					sprintf(
						'User deletion logged - ID: %d, Username: %s',
						$user_id,
						$deleted_user->user_login
					)
				);
			}
		} catch ( Exception $e ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'Error logging user deletion: ' . $e->getMessage() );
			}
		}
	}

	/**
	 * Log user profile updates with change tracking
	 *
	 * @param int $user_id The ID of the user being updated
	 * @return void
	 */
	public function log_profile_update( $user_id ) {
		try {
			$user = get_userdata( $user_id );
			if ( ! $user ) {
				throw new Exception( 'Invalid user ID or user not found' );
			}

			// Track changed fields
			$changed_fields = $this->detect_profile_changes( $user_id );

			// Get current user (who made the changes)
			$current_user = wp_get_current_user();

			$meta_data = array(
				'user_details' => array(
					'email'        => $user->user_email,
					'roles'        => $user->roles,
					'display_name' => $user->display_name,
				),
				'changes'      => $changed_fields,
				'modified_by'  => array(
					'user_id'  => $current_user->ID,
					'username' => $current_user->user_login,
				),
				'timestamp'    => current_time( 'mysql' ),
			);

			$log_message = sprintf(
				/* translators: 1: username, 2: changed fields */
				__( 'Profile updated for %1$s. Changed fields: %2$s', 'ultimakit-for-wp' ),
				$user->user_login,
				implode( ', ', array_keys( $changed_fields ) )
			);

			$this->log_activity( 'profile_update', 'user', $user_id, $log_message, $meta_data );
		} catch ( Exception $e ) {
			$this->handle_log_error( 'profile_update', $e->getMessage() );
		}
	}

	/**
	 * Log plugin activation with system details
	 *
	 * @param string $plugin Plugin path
	 * @return void
	 */
	public function log_plugin_activation( $plugin ) {
		try {
			$plugin_data  = get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin );
			$current_user = wp_get_current_user();

			$meta_data = array(
				'plugin_details'     => array(
					'name'    => $plugin_data['Name'],
					'version' => $plugin_data['Version'],
					'author'  => $plugin_data['Author'],
					'path'    => $plugin,
				),
				'activation_details' => array(
					'activated_by' => $current_user->user_login,
					'timestamp'    => current_time( 'mysql' ),
					'wp_version'   => get_bloginfo( 'version' ),
					'php_version'  => phpversion(),
				),
			);

			$log_message = sprintf(
				/* translators: 1: plugin name, 2: version, 3: activated by */
				__( 'Activated plugin: %1$s (v%2$s) by %3$s', 'ultimakit-for-wp' ),
				$plugin_data['Name'],
				$plugin_data['Version'],
				$current_user->user_login
			);

			$this->log_activity( 'plugin_activation', 'plugin', 0, $log_message, $meta_data );
		} catch ( Exception $e ) {
			$this->handle_log_error( 'plugin_activation', $e->getMessage() );
		}
	}

	/**
	 * Log plugin deactivation with system details
	 *
	 * @param string $plugin Plugin path
	 * @return void
	 */
	public function log_plugin_deactivation( $plugin ) {
		try {
			$plugin_data  = get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin );
			$current_user = wp_get_current_user();

			$meta_data = array(
				'plugin_details'       => array(
					'name'    => $plugin_data['Name'],
					'version' => $plugin_data['Version'],
					'author'  => $plugin_data['Author'],
					'path'    => $plugin,
				),
				'deactivation_details' => array(
					'deactivated_by'  => $current_user->user_login,
					'timestamp'       => current_time( 'mysql' ),
					'active_duration' => $this->get_plugin_active_duration( $plugin ),
				),
			);

			$log_message = sprintf(
				/* translators: 1: plugin name, 2: version, 3: deactivated by */
				__( 'Deactivated plugin: %1$s (v%2$s) by %3$s', 'ultimakit-for-wp' ),
				$plugin_data['Name'],
				$plugin_data['Version'],
				$current_user->user_login
			);

			$this->log_activity( 'plugin_deactivation', 'plugin', 0, $log_message, $meta_data );
		} catch ( Exception $e ) {
			$this->handle_log_error( 'plugin_deactivation', $e->getMessage() );
		}
	}

	/**
	 * Log successful user login attempts
	 *
	 * @param string  $user_login Username
	 * @param WP_User $user       User object
	 * @return void
	 */
	public function log_user_login( $user_login, $user ) {
		if ( ! ( $user instanceof WP_User ) ) {
			return;
		}

		try {
			$meta_data = array(
				'user_details'    => array(
					'email'        => $user->user_email,
					'roles'        => $user->roles,
					'display_name' => $user->display_name,
				),
				'session_details' => array(
					'timestamp'   => current_time( 'mysql' ),
					'user_agent'  => sanitize_text_field( $_SERVER['HTTP_USER_AGENT'] ?? '' ),
					'login_count' => intval( get_user_meta( $user->ID, 'login_count', true ) ) + 1,
				),
			);

			update_user_meta( $user->ID, 'last_login', current_time( 'mysql' ) );
			update_user_meta( $user->ID, 'login_count', $meta_data['session_details']['login_count'] );

			$log_message = sprintf(
				/* translators: 1: display name, 2: username, 3: roles */
				__( 'User logged in: %1$s (%2$s) with roles: %3$s', 'ultimakit-for-wp' ),
				$user->display_name,
				$user_login,
				implode( ', ', $user->roles )
			);

			$this->log_activity( 'user_login', 'user', $user->ID, $log_message, $meta_data );
		} catch ( Exception $e ) {
			$this->handle_log_error( 'user_login', $e->getMessage() );
		}
	}

	/**
	 * Log failed login attempts
	 *
	 * @param string $username Attempted username
	 * @return void
	 */
	public function log_failed_login( $username ) {
		try {
			$meta_data = array(
				'attempt_details' => array(
					'timestamp'  => current_time( 'mysql' ),
					'user_agent' => sanitize_text_field( $_SERVER['HTTP_USER_AGENT'] ?? '' ),
					'username'   => sanitize_user( $username ),
				),
			);

			$log_message = sprintf(
				/* translators: %s: username */
				__( 'Failed login attempt for username: %s', 'ultimakit-for-wp' ),
				sanitize_user( $username )
			);

			$this->log_activity( 'failed_login', 'user', 0, $log_message, $meta_data );
		} catch ( Exception $e ) {
			$this->handle_log_error( 'failed_login', $e->getMessage() );
		}
	}

	/**
	 * Log user logout events
	 *
	 * @return void
	 */
	public function log_user_logout() {
		try {
			$user = wp_get_current_user();

			if ( ! $user || ! $user->ID ) {
				return;
			}

			$session_duration = $this->calculate_session_duration( $user->ID );

			$meta_data = array(
				'user_details'    => array(
					'email'        => $user->user_email,
					'roles'        => $user->roles,
					'display_name' => $user->display_name,
				),
				'session_details' => array(
					'timestamp'        => current_time( 'mysql' ),
					'session_duration' => $session_duration,
					'last_activity'    => get_user_meta( $user->ID, 'last_activity', true ),
				),
			);

			$log_message = sprintf(
				/* translators: 1: username, 2: session duration */
				__( 'User logged out: %1$s (Session duration: %2$s)', 'ultimakit-for-wp' ),
				$user->user_login,
				$this->format_duration( $session_duration )
			);

			$this->log_activity( 'user_logout', 'user', $user->ID, $log_message, $meta_data );
		} catch ( Exception $e ) {
			$this->handle_log_error( 'user_logout', $e->getMessage() );
		}
	}

	/**
	 * Get all trackable user fields
	 *
	 * @return array
	 */
	private function detect_profile_changes( $user_id ) {
		$changed_fields = array();
		$old_user_data  = get_userdata( $user_id );

		$fields_to_check = array(
			// Basic Information
			'user_login'              => __( 'Username', 'ultimakit-for-wp' ),
			'user_email'              => __( 'Email', 'ultimakit-for-wp' ),
			'user_url'                => __( 'Website', 'ultimakit-for-wp' ),
			'display_name'            => __( 'Display Name', 'ultimakit-for-wp' ),
			'nickname'                => __( 'Nickname', 'ultimakit-for-wp' ),
			'first_name'              => __( 'First Name', 'ultimakit-for-wp' ),
			'last_name'               => __( 'Last Name', 'ultimakit-for-wp' ),

			// Profile Information
			'description'             => __( 'Biographical Info', 'ultimakit-for-wp' ),
			'rich_editing'            => __( 'Visual Editor', 'ultimakit-for-wp' ),
			'syntax_highlighting'     => __( 'Syntax Highlighting', 'ultimakit-for-wp' ),
			'comment_shortcuts'       => __( 'Keyboard Shortcuts', 'ultimakit-for-wp' ),
			'admin_color'             => __( 'Admin Color Scheme', 'ultimakit-for-wp' ),
			'use_ssl'                 => __( 'SSL', 'ultimakit-for-wp' ),
			'show_admin_bar_front'    => __( 'Toolbar', 'ultimakit-for-wp' ),
			'locale'                  => __( 'Language', 'ultimakit-for-wp' ),

			// Security Related
			'user_pass'               => __( 'Password', 'ultimakit-for-wp' ),
			'user_activation_key'     => __( 'Activation Key', 'ultimakit-for-wp' ),
			'user_status'             => __( 'User Status', 'ultimakit-for-wp' ),
			'spam'                    => __( 'Spam Status', 'ultimakit-for-wp' ),
			'deleted'                 => __( 'Deletion Status', 'ultimakit-for-wp' ),

			// Time and Date
			'user_registered'         => __( 'Registration Date', 'ultimakit-for-wp' ),

			// Capabilities and Roles
			'capabilities'            => __( 'Capabilities', 'ultimakit-for-wp' ),
			'role'                    => __( 'Role', 'ultimakit-for-wp' ),

			// Social Media (if using custom fields)
			'facebook'                => __( 'Facebook', 'ultimakit-for-wp' ),
			'twitter'                 => __( 'Twitter', 'ultimakit-for-wp' ),
			'linkedin'                => __( 'LinkedIn', 'ultimakit-for-wp' ),
			'instagram'               => __( 'Instagram', 'ultimakit-for-wp' ),

			// Contact Information
			'phone'                   => __( 'Phone Number', 'ultimakit-for-wp' ),
			'address'                 => __( 'Address', 'ultimakit-for-wp' ),
			'city'                    => __( 'City', 'ultimakit-for-wp' ),
			'state'                   => __( 'State', 'ultimakit-for-wp' ),
			'country'                 => __( 'Country', 'ultimakit-for-wp' ),
			'zip'                     => __( 'ZIP Code', 'ultimakit-for-wp' ),

			// Professional Information
			'company'                 => __( 'Company', 'ultimakit-for-wp' ),
			'position'                => __( 'Position', 'ultimakit-for-wp' ),
			'expertise'               => __( 'Expertise', 'ultimakit-for-wp' ),

			// Communication Preferences
			'email_notifications'     => __( 'Email Notifications', 'ultimakit-for-wp' ),
			'newsletter_subscription' => __( 'Newsletter Subscription', 'ultimakit-for-wp' ),

			// Avatar and Media
			'avatar'                  => __( 'Profile Picture', 'ultimakit-for-wp' ),
			'cover_image'             => __( 'Cover Image', 'ultimakit-for-wp' ),
		);

		foreach ( $fields_to_check as $field => $label ) {
			// Handle regular user fields
			if ( isset( $_POST[ $field ] ) ) {
				$old_value = $old_user_data->$field ?? '';
				$new_value = sanitize_text_field( $_POST[ $field ] );

				if ( $new_value !== $old_value ) {
					// Special handling for sensitive data
					if ( $field === 'user_pass' ) {
						$changed_fields[ $label ] = array(
							'old' => '********',
							'new' => '********',
						);
					} else {
						$changed_fields[ $label ] = array(
							'old' => $old_value,
							'new' => $new_value,
						);
					}
				}
			}

			// Handle user meta fields
			$meta_fields = array(
				'phone',
				'address',
				'city',
				'state',
				'country',
				'zip',
				'company',
				'position',
				'expertise',
				'facebook',
				'twitter',
				'linkedin',
				'instagram',
				'email_notifications',
				'newsletter_subscription',
			);

			if ( in_array( $field, $meta_fields ) ) {
				$old_meta_value = get_user_meta( $user_id, $field, true );
				$new_meta_value = isset( $_POST[ $field ] ) ? sanitize_text_field( $_POST[ $field ] ) : '';

				if ( $new_meta_value !== $old_meta_value ) {
					$changed_fields[ $label ] = array(
						'old' => $old_meta_value,
						'new' => $new_meta_value,
					);
				}
			}

			// Handle special fields like capabilities and roles
			if ( $field === 'capabilities' || $field === 'role' ) {
				$old_roles = $old_user_data->roles;
				$new_roles = isset( $_POST['role'] ) ? array( $_POST['role'] ) : $old_roles;

				if ( $old_roles != $new_roles ) {
					$changed_fields[ $label ] = array(
						'old' => implode( ', ', $old_roles ),
						'new' => implode( ', ', $new_roles ),
					);
				}
			}
		}

		// Handle file uploads (avatar and cover image)
		if ( isset( $_FILES['avatar'] ) && $_FILES['avatar']['size'] > 0 ) {
			$changed_fields[ __( 'Profile Picture', 'ultimakit-for-wp' ) ] = array(
				'old' => __( 'Previous image', 'ultimakit-for-wp' ),
				'new' => $_FILES['avatar']['name'],
			);
		}

		if ( isset( $_FILES['cover_image'] ) && $_FILES['cover_image']['size'] > 0 ) {
			$changed_fields[ __( 'Cover Image', 'ultimakit-for-wp' ) ] = array(
				'old' => __( 'Previous image', 'ultimakit-for-wp' ),
				'new' => $_FILES['cover_image']['name'],
			);
		}

		return $changed_fields;
	}

	/**
	 * Calculate session duration
	 *
	 * @param int $user_id
	 * @return int Duration in seconds
	 */
	private function calculate_session_duration( $user_id ) {
		$login_time = strtotime( get_user_meta( $user_id, 'last_login', true ) );
		return $login_time ? ( time() - $login_time ) : 0;
	}

	/**
	 * Format duration into human-readable string
	 *
	 * @param int $duration_in_seconds
	 * @return string
	 */
	private function format_duration( $duration_in_seconds ) {
		if ( $duration_in_seconds < 60 ) {
			return sprintf( _n( '%d second', '%d seconds', $duration_in_seconds, 'ultimakit-for-wp' ), $duration_in_seconds );
		}

		$minutes = floor( $duration_in_seconds / 60 );
		if ( $minutes < 60 ) {
			return sprintf( _n( '%d minute', '%d minutes', $minutes, 'ultimakit-for-wp' ), $minutes );
		}

		$hours = floor( $minutes / 60 );
		return sprintf( _n( '%d hour', '%d hours', $hours, 'ultimakit-for-wp' ), $hours );
	}

	/**
	 * Get plugin active duration
	 *
	 * @param string $plugin
	 * @return string
	 */
	private function get_plugin_active_duration( $plugin ) {
		$activation_time = get_option( 'plugin_activation_' . sanitize_key( $plugin ) );
		if ( ! $activation_time ) {
			return 'unknown';
		}

		$duration = time() - strtotime( $activation_time );
		return $this->format_duration( $duration );
	}

	/**
	 * Handle logging errors
	 *
	 * @param string $context
	 * @param string $message
	 * @return void
	 */
	private function handle_log_error( $context, $message ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( sprintf( 'Logging error in %s: %s', $context, $message ) );
		}
	}

	/**
	 * Log user role changes
	 *
	 * @param int    $user_id   The user ID
	 * @param string $new_role  The new role
	 * @param array  $old_roles Previous roles the user had
	 * @return void
	 */
	public function log_role_change( $user_id, $new_role, $old_roles ) {
		// Get the user who made the change
		$current_user = wp_get_current_user();
		$changed_user = get_user_by( 'id', $user_id );

		if ( ! $changed_user ) {
			return;
		}

		// Format old roles for display
		$old_roles_string = ! empty( $old_roles ) ? implode( ', ', $old_roles ) : __( 'none', 'ultimakit-for-wp' );

		$this->log_activity(
			'role_change',
			'user',
			$user_id,
			sprintf(
				/* translators: 1: username, 2: old role(s), 3: new role, 4: person making the change */
				__( 'Role changed for user %1$s from %2$s to %3$s by %4$s', 'ultimakit-for-wp' ),
				$changed_user->user_login,
				$old_roles_string,
				$new_role,
				$current_user->ID ? $current_user->user_login : __( 'system', 'ultimakit-for-wp' )
			),
			array(
				'user_email'       => $changed_user->user_email,
				'old_roles'        => $old_roles_string,
				'new_role'         => $new_role,
				'changed_by'       => $current_user->ID,
				'changed_by_email' => $current_user->user_email,
			)
		);
	}

	/**
	 * Log when a role is added to a user (Enhanced version)
	 *
	 * @param int    $user_id The user ID
	 * @param string $role    The added role
	 * @return void
	 */
	public function log_role_addition( $user_id, $role ) {
		try {
			// Input validation
			if ( ! $user_id || ! is_numeric( $user_id ) ) {
				throw new Exception( 'Invalid user ID provided' );
			}

			if ( empty( $role ) ) {
				throw new Exception( 'No role specified for addition' );
			}

			// Get user objects
			$changed_user = get_user_by( 'id', $user_id );
			$current_user = wp_get_current_user();

			if ( ! $changed_user ) {
				throw new Exception( 'User not found for role addition logging' );
			}

			// Get context information
			$timestamp  = current_time( 'mysql' );
			$user_agent = isset( $_SERVER['HTTP_USER_AGENT'] )
				? sanitize_text_field( $_SERVER['HTTP_USER_AGENT'] )
				: '';

			// Get all current roles after addition
			$all_roles = array_values( $changed_user->roles );

			// Check if this is a duplicate role
			$is_duplicate = in_array( $role, $all_roles );

			// Prepare metadata
			$meta_data = array(
				'user_details'   => array(
					'email'           => $changed_user->user_email,
					'display_name'    => $changed_user->display_name,
					'user_registered' => $changed_user->user_registered,
				),
				'role_details'   => array(
					'added_role'   => $role,
					'all_roles'    => $all_roles,
					'is_duplicate' => $is_duplicate,
				),
				'change_details' => array(
					'changed_by'   => array(
						'id'    => $current_user->ID,
						'login' => $current_user->user_login,
						'email' => $current_user->user_email,
					),
					'timestamp'    => $timestamp,
					'user_agent'   => $user_agent,
					'request_url'  => isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( $_SERVER['REQUEST_URI'] ) : '',
					'http_referer' => wp_get_referer(),
				),
			);

			// Create log message
			$log_message = $this->create_role_addition_message(
				$changed_user,
				$role,
				$current_user,
				$all_roles,
				$is_duplicate
			);

			// Log the activity
			$log_result = $this->log_activity(
				'role_addition',
				'user',
				$user_id,
				$log_message,
				$meta_data
			);

			// Debug logging
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log(
					sprintf(
						'Role addition logged for user %s (ID: %d) - Role: %s - Status: %s',
						$changed_user->user_login,
						$user_id,
						$role,
						$log_result ? 'Success' : 'Failed'
					)
				);
			}
		} catch ( Exception $e ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'Error logging role addition: ' . $e->getMessage() );
			}
		}
	}

	/**
	 * Log when a role is removed from a user (Enhanced version)
	 *
	 * @param int    $user_id The user ID
	 * @param string $role    The removed role
	 * @return void
	 */
	public function log_role_removal( $user_id, $role ) {
		try {
			// Input validation
			if ( ! $user_id || ! is_numeric( $user_id ) ) {
				throw new Exception( 'Invalid user ID provided' );
			}

			if ( empty( $role ) ) {
				throw new Exception( 'No role specified for removal' );
			}

			// Get user objects
			$changed_user = get_user_by( 'id', $user_id );
			$current_user = wp_get_current_user();

			if ( ! $changed_user ) {
				throw new Exception( 'User not found for role removal logging' );
			}

			// Get context information
			$timestamp  = current_time( 'mysql' );
			$user_agent = isset( $_SERVER['HTTP_USER_AGENT'] )
				? sanitize_text_field( $_SERVER['HTTP_USER_AGENT'] )
				: '';

			// Get remaining roles after removal
			$remaining_roles = array_values( $changed_user->roles );

			// Check if this was the user's last role
			$is_last_role = empty( $remaining_roles );

			// Prepare metadata
			$meta_data = array(
				'user_details'   => array(
					'email'           => $changed_user->user_email,
					'display_name'    => $changed_user->display_name,
					'user_registered' => $changed_user->user_registered,
				),
				'role_details'   => array(
					'removed_role'    => $role,
					'remaining_roles' => $remaining_roles,
					'is_last_role'    => $is_last_role,
				),
				'change_details' => array(
					'changed_by'   => array(
						'id'    => $current_user->ID,
						'login' => $current_user->user_login,
						'email' => $current_user->user_email,
					),
					'timestamp'    => $timestamp,
					'user_agent'   => $user_agent,
					'request_url'  => isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( $_SERVER['REQUEST_URI'] ) : '',
					'http_referer' => wp_get_referer(),
				),
			);

			// Create log message
			$log_message = $this->create_role_removal_message(
				$changed_user,
				$role,
				$current_user,
				$remaining_roles,
				$is_last_role
			);

			// Log the activity
			$log_result = $this->log_activity(
				'role_removal',
				'user',
				$user_id,
				$log_message,
				$meta_data
			);

			// Additional action if this was the user's last role
			if ( $is_last_role ) {
				do_action( 'ultimakit_user_lost_all_roles', $user_id );
			}

			// Debug logging
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log(
					sprintf(
						'Role removal logged for user %s (ID: %d) - Role: %s - Status: %s',
						$changed_user->user_login,
						$user_id,
						$role,
						$log_result ? 'Success' : 'Failed'
					)
				);
			}
		} catch ( Exception $e ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'Error logging role removal: ' . $e->getMessage() );
			}
		}
	}

	/**
	 * Create message for role addition
	 *
	 * @param WP_User $changed_user User being changed
	 * @param string  $role         Added role
	 * @param WP_User $current_user User making the change
	 * @param array   $all_roles    All current roles
	 * @param bool    $is_duplicate Whether this is a duplicate role
	 * @return string
	 */
	private function create_role_addition_message( $changed_user, $role, $current_user, $all_roles, $is_duplicate ) {
		if ( $is_duplicate ) {
			return sprintf(
				/* translators: 1: username, 2: role, 3: person making the change */
				__( 'Attempted to add existing role "%2$s" to user %1$s by %3$s', 'ultimakit-for-wp' ),
				$changed_user->user_login,
				$role,
				$current_user->ID ? $current_user->user_login : __( 'system', 'ultimakit-for-wp' )
			);
		}

		return sprintf(
			/* translators: 1: username, 2: role, 3: person making the change, 4: all roles */
			__( 'Role "%2$s" added to user %1$s by %3$s. All roles: %4$s', 'ultimakit-for-wp' ),
			$changed_user->user_login,
			$role,
			$current_user->ID ? $current_user->user_login : __( 'system', 'ultimakit-for-wp' ),
			implode( ', ', $all_roles )
		);
	}

	/**
	 * Create message for role removal
	 *
	 * @param WP_User $changed_user  User being changed
	 * @param string  $role         Removed role
	 * @param WP_User $current_user User making the change
	 * @param array   $remaining_roles Remaining roles
	 * @param bool    $is_last_role Whether this was the last role
	 * @return string
	 */
	private function create_role_removal_message( $changed_user, $role, $current_user, $remaining_roles, $is_last_role ) {
		if ( $is_last_role ) {
			return sprintf(
				/* translators: 1: username, 2: role, 3: person making the change */
				__( 'Last role "%2$s" removed from user %1$s by %3$s', 'ultimakit-for-wp' ),
				$changed_user->user_login,
				$role,
				$current_user->ID ? $current_user->user_login : __( 'system', 'ultimakit-for-wp' )
			);
		}

		return sprintf(
			/* translators: 1: username, 2: role, 3: person making the change, 4: remaining roles */
			__( 'Role "%2$s" removed from user %1$s by %3$s. Remaining roles: %4$s', 'ultimakit-for-wp' ),
			$changed_user->user_login,
			$role,
			$current_user->ID ? $current_user->user_login : __( 'system', 'ultimakit-for-wp' ),
			implode( ', ', $remaining_roles )
		);
	}

	public function store_user_id() {
		$user_id = get_current_user_id();
		$this->log_activity(
			'store_user_id',
			'user',
			$user_id,
			sprintf(
				/* translators: %s: username */
				__( 'User ID Stored: %d', 'ultimakit-for-wp' ),
				$user_id
			)
		);
	}

	/**
	 * Log post status changes with comprehensive details
	 *
	 * @param string   $new_status New post status
	 * @param string   $old_status Old post status
	 * @param WP_Post  $post       Post object
	 * @return void
	 */
	public function log_post_status_change( $new_status, $old_status, $post ) {
		if ( $new_status === $old_status ) {
			return;
		}

		try {
			$current_user  = wp_get_current_user();
			$post_type_obj = get_post_type_object( $post->post_type );

			// Get post author details
			$author = get_userdata( $post->post_author );

			// Get post categories and tags
			$categories = wp_get_post_categories( $post->ID, array( 'fields' => 'names' ) );
			$tags       = wp_get_post_tags( $post->ID, array( 'fields' => 'names' ) );

			// Get revision information
			$revisions      = wp_get_post_revisions( $post->ID );
			$revision_count = count( $revisions );

			// Check if this is a scheduled change
			$is_scheduled = ! empty( $post->post_date_gmt ) &&
						strtotime( $post->post_date_gmt ) > time();

			// Format status labels
			$old_status_label = $this->get_status_label( $old_status );
			$new_status_label = $this->get_status_label( $new_status );

			$meta_data = array(
				'post_details'    => array(
					'title'         => $post->post_title,
					'post_type'     => $post_type_obj->labels->singular_name,
					'post_id'       => $post->ID,
					'permalink'     => get_permalink( $post->ID ),
					'author'        => array(
						'id'    => $author->ID,
						'name'  => $author->display_name,
						'email' => $author->user_email,
					),
					'categories'    => $categories,
					'tags'          => $tags,
					'created_date'  => $post->post_date,
					'modified_date' => $post->post_modified,
				),
				'status_change'   => array(
					'old_status'    => $old_status_label,
					'new_status'    => $new_status_label,
					'changed_by'    => array(
						'id'   => $current_user->ID,
						'name' => $current_user->display_name,
					),
					'timestamp'     => current_time( 'mysql' ),
					'is_scheduled'  => $is_scheduled,
					'schedule_date' => $is_scheduled ? $post->post_date_gmt : null,
				),
				'revision_info'   => array(
					'revision_count' => $revision_count,
					'last_revised'   => $revision_count > 0 ? get_the_modified_time( 'Y-m-d H:i:s', $post->ID ) : null,
				),
				'additional_info' => array(
					'word_count'     => str_word_count( strip_tags( $post->post_content ) ),
					'has_password'   => ! empty( $post->post_password ),
					'comment_status' => $post->comment_status,
					'ping_status'    => $post->ping_status,
					'menu_order'     => $post->menu_order,
				),
			);

			// Add featured image info if exists
			if ( has_post_thumbnail( $post->ID ) ) {
				$meta_data['post_details']['featured_image'] = get_the_post_thumbnail_url( $post->ID );
			}

			// Add custom fields if any
			$custom_fields = get_post_custom( $post->ID );
			if ( ! empty( $custom_fields ) ) {
				$meta_data['custom_fields'] = array_keys( $custom_fields );
			}

			$log_message = sprintf(
				/* translators: 1: post title, 2: post type, 3: old status, 4: new status, 5: changed by */
				__( '%2$s "%1$s" status changed from %3$s to %4$s by %5$s', 'ultimakit-for-wp' ),
				$post->post_title,
				$post_type_obj->labels->singular_name,
				$old_status_label,
				$new_status_label,
				$current_user->display_name
			);

			// Add scheduling information to log message if applicable
			if ( $is_scheduled ) {
				$log_message .= sprintf(
					/* translators: %s: scheduled date */
					__( ' (scheduled for %s)', 'ultimakit-for-wp' ),
					get_date_from_gmt( $post->post_date_gmt, get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) )
				);
			}

			$this->log_activity( 'post_status_change', 'post', $post->ID, $log_message, $meta_data );

			// Log additional context for specific status changes
			$this->log_special_status_changes( $new_status, $old_status, $post );

		} catch ( Exception $e ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( sprintf( 'Error logging post status change: %s', $e->getMessage() ) );
			}
		}
	}

	/**
	 * Get human-readable status label
	 *
	 * @param string $status Post status
	 * @return string
	 */
	private function get_status_label( $status ) {
		$status_labels = array(
			'publish'    => __( 'Published', 'ultimakit-for-wp' ),
			'draft'      => __( 'Draft', 'ultimakit-for-wp' ),
			'pending'    => __( 'Pending Review', 'ultimakit-for-wp' ),
			'private'    => __( 'Private', 'ultimakit-for-wp' ),
			'future'     => __( 'Scheduled', 'ultimakit-for-wp' ),
			'trash'      => __( 'Trashed', 'ultimakit-for-wp' ),
			'auto-draft' => __( 'Auto Draft', 'ultimakit-for-wp' ),
			'inherit'    => __( 'Revision', 'ultimakit-for-wp' ),
		);

		return isset( $status_labels[ $status ] ) ? $status_labels[ $status ] : ucfirst( $status );
	}

	/**
	 * Log additional context for specific status changes
	 *
	 * @param string   $new_status New post status
	 * @param string   $old_status Old post status
	 * @param WP_Post  $post       Post object
	 * @return void
	 */
	private function log_special_status_changes( $new_status, $old_status, $post ) {
		// Log when post is published
		if ( $new_status === 'publish' && $old_status !== 'publish' ) {
			$this->log_post_publication( $post );
		}

		// Log when post is trashed
		if ( $new_status === 'trash' ) {
			$this->log_post_trash( $post );
		}

		// Log when post is restored from trash
		if ( $old_status === 'trash' && $new_status !== 'trash' ) {
			$this->log_post_restore( $post );
		}
	}

	/**
	 * Log when a post is moved to trash
	 *
	 * @param WP_Post $post Post object
	 */
	public function log_post_trash( $post ) {
		// Get additional post information
		$post_type       = get_post_type_object( $post->post_type );
		$post_type_label = $post_type ? $post_type->labels->singular_name : $post->post_type;

		// Get the user who trashed the post
		$current_user = wp_get_current_user();

		// Get post categories and tags
		$categories = wp_get_post_categories( $post->ID, array( 'fields' => 'names' ) );
		$tags       = wp_get_post_tags( $post->ID, array( 'fields' => 'names' ) );

		// Create detailed meta information
		$meta = array(
			'post_id'       => $post->ID,
			'post_title'    => $post->post_title,
			'post_type'     => $post_type_label,
			'post_date'     => $post->post_date,
			'post_modified' => $post->post_modified,
			'trashed_by'    => array(
				'user_id'    => $current_user->ID,
				'user_login' => $current_user->user_login,
				'user_email' => $current_user->user_email,
				'user_role'  => implode( ', ', $current_user->roles ),
			),
			'categories'    => ! empty( $categories ) ? implode( ', ', $categories ) : '',
			'tags'          => ! empty( $tags ) ? implode( ', ', $tags ) : '',
			'trashed_date'  => current_time( 'mysql' ),
		);

		// Create a detailed message
		$message = sprintf(
			/* translators: 1: post type, 2: post title, 3: user login */
			__( 'Moved %1$s "%2$s" to trash by %3$s', 'ultimakit-for-wp' ),
			$post_type_label,
			$post->post_title,
			$current_user->user_login
		);

		try {
			$this->log_activity(
				'post_trash',
				'post',
				$post->ID,
				$message,
				$meta
			);
		} catch ( Exception $e ) {
			error_log(
				sprintf(
					'Failed to log post trash: %s (Post ID: %d)',
					$e->getMessage(),
					$post->ID
				)
			);
		}
	}

	/**
	 * Log when a post is restored from trash
	 *
	 * @param WP_Post $post Post object
	 */
	public function log_post_restore( $post ) {
		// Get additional post information
		$post_type       = get_post_type_object( $post->post_type );
		$post_type_label = $post_type ? $post_type->labels->singular_name : $post->post_type;

		// Get the user who restored the post
		$current_user = wp_get_current_user();

		// Get post categories and tags
		$categories = wp_get_post_categories( $post->ID, array( 'fields' => 'names' ) );
		$tags       = wp_get_post_tags( $post->ID, array( 'fields' => 'names' ) );

		// Create detailed meta information
		$meta = array(
			'post_id'       => $post->ID,
			'post_title'    => $post->post_title,
			'post_type'     => $post_type_label,
			'post_date'     => $post->post_date,
			'post_modified' => $post->post_modified,
			'restored_by'   => array(
				'user_id'    => $current_user->ID,
				'user_login' => $current_user->user_login,
				'user_email' => $current_user->user_email,
				'user_role'  => implode( ', ', $current_user->roles ),
			),
			'categories'    => ! empty( $categories ) ? implode( ', ', $categories ) : '',
			'tags'          => ! empty( $tags ) ? implode( ', ', $tags ) : '',
			'restored_date' => current_time( 'mysql' ),
			'new_status'    => get_post_status( $post->ID ),
		);

		// Create a detailed message
		$message = sprintf(
			/* translators: 1: post type, 2: post title, 3: user login, 4: new status */
			__( 'Restored %1$s "%2$s" from trash by %3$s (New status: %4$s)', 'ultimakit-for-wp' ),
			$post_type_label,
			$post->post_title,
			$current_user->user_login,
			get_post_status_object( get_post_status( $post->ID ) )->label
		);

		try {
			$this->log_activity(
				'post_restore',
				'post',
				$post->ID,
				$message,
				$meta
			);
		} catch ( Exception $e ) {
			error_log(
				sprintf(
					'Failed to log post restore: %s (Post ID: %d)',
					$e->getMessage(),
					$post->ID
				)
			);
		}
	}

	/**
	 * Log post publication details
	 *
	 * @param WP_Post $post Post object
	 * @return void
	 */
	private function log_post_publication( $post ) {
		$meta_data = array(
			'publication_details' => array(
				'publish_date'         => current_time( 'mysql' ),
				'is_first_publication' => $this->is_first_publication( $post->ID ),
			),
		);

		$this->log_activity(
			'post_published',
			'post',
			$post->ID,
			sprintf(
				/* translators: %s: post title */
				__( 'Post "%s" has been published', 'ultimakit-for-wp' ),
				$post->post_title
			),
			$meta_data
		);
	}

	/**
	 * Check if this is the first publication of the post
	 *
	 * @param int $post_id Post ID
	 * @return bool
	 */
	private function is_first_publication( $post_id ) {
		$revisions = wp_get_post_revisions( $post_id );
		return empty( $revisions );
	}

	public function log_post_deletion( $post_id ) {
		// Get the post object before it's deleted
		$post = get_post( $post_id );

		// If post doesn't exist, return early
		if ( ! $post ) {
			return;
		}

		// Get additional post information
		$post_type         = get_post_type_object( $post->post_type );
		$post_type_label   = $post_type ? $post_type->labels->singular_name : $post->post_type;
		$post_status       = get_post_status_object( $post->post_status );
		$post_status_label = $post_status ? $post_status->label : $post->post_status;

		// Get the user who deleted the post
		$current_user = wp_get_current_user();

		// Get post categories and tags
		$categories = wp_get_post_categories( $post_id, array( 'fields' => 'names' ) );
		$tags       = wp_get_post_tags( $post_id, array( 'fields' => 'names' ) );

		// Get post meta data (optional - remove if not needed)
		$meta_data = get_post_meta( $post_id );

		// Create detailed meta information
		$meta = array(
			'post_id'       => $post_id,
			'post_title'    => $post->post_title,
			'post_type'     => $post_type_label,
			'post_status'   => $post_status_label,
			'post_date'     => $post->post_date,
			'post_modified' => $post->post_modified,
			'deleted_by'    => array(
				'user_id'    => $current_user->ID,
				'user_login' => $current_user->user_login,
				'user_email' => $current_user->user_email,
				'user_role'  => implode( ', ', $current_user->roles ),
			),
			'categories'    => ! empty( $categories ) ? implode( ', ', $categories ) : '',
			'tags'          => ! empty( $tags ) ? implode( ', ', $tags ) : '',
			'deletion_date' => current_time( 'mysql' ),
			// 'meta_data' => $meta_data, // Uncomment if you want to log post meta
		);

		// Create a detailed message
		$message = sprintf(
			/* translators: 1: post type, 2: post title, 3: post status, 4: user login */
			__( 'Deleted %1$s "%2$s" (Status: %3$s) by user %4$s', 'ultimakit-for-wp' ),
			$post_type_label,
			$post->post_title,
			$post_status_label,
			$current_user->user_login
		);

		try {
			$this->log_activity(
				'post_deletion',
				'post',
				$post_id,
				$message,
				$meta
			);
		} catch ( Exception $e ) {
			// Log the error if logging fails
			error_log(
				sprintf(
					'Failed to log post deletion: %s (Post ID: %d)',
					$e->getMessage(),
					$post_id
				)
			);
		}
	}

	/**
	 * Log category creation with detailed information
	 *
	 * @param int $term_id The ID of the created category
	 */
	public function log_category_creation( $term_id ) {
		// Get the term object
		$term = get_term( $term_id );

		// Return if term doesn't exist or there's an error
		if ( ! $term || is_wp_error( $term ) ) {
			return;
		}

		// Get the current user
		$current_user = wp_get_current_user();

		// Get parent category information if exists
		$parent_term = null;
		if ( $term->parent ) {
			$parent_term = get_term( $term->parent );
		}

		// Get taxonomy object for labels
		$taxonomy       = get_taxonomy( $term->taxonomy );
		$taxonomy_label = $taxonomy ? $taxonomy->labels->singular_name : $term->taxonomy;

		// Create detailed meta information
		$meta = array(
			'term_id'     => $term_id,
			'name'        => $term->name,
			'slug'        => $term->slug,
			'taxonomy'    => $taxonomy_label,
			'description' => $term->description,
			'parent'      => array(
				'id'   => $term->parent,
				'name' => ( $parent_term && ! is_wp_error( $parent_term ) ) ? $parent_term->name : '',
			),
			'count'       => $term->count,
			'created_by'  => array(
				'user_id'    => $current_user->ID,
				'user_login' => $current_user->user_login,
				'user_email' => $current_user->user_email,
				'user_role'  => implode( ', ', $current_user->roles ),
			),
			'created_at'  => current_time( 'mysql' ),
			'term_meta'   => get_term_meta( $term_id ), // Optional: Remove if you don't need term meta
			'term_link'   => get_term_link( $term_id ), // The URL of the category
		);

		// Create a detailed message
		$message = sprintf(
			/* translators: 1: taxonomy label, 2: term name, 3: user login, 4: parent category */
			__( 'Created %1$s "%2$s" by %3$s%4$s', 'ultimakit-for-wp' ),
			$taxonomy_label,
			$term->name,
			$current_user->user_login,
			( $parent_term && ! is_wp_error( $parent_term ) )
				? sprintf(/* translators: %s: parent category name */
					__( ' under parent category "%s"', 'ultimakit-for-wp' ),
					$parent_term->name
				)
				: ''
		);

		try {
			// Log the activity
			$this->log_activity(
				'category_creation',
				'taxonomy',
				$term_id,
				$message,
				$meta
			);

			// Optional: Log to WordPress debug log
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log(
					sprintf(
						'Category created: %s (ID: %d) by user %s',
						$term->name,
						$term_id,
						$current_user->user_login
					)
				);
			}
		} catch ( Exception $e ) {
			// Log any errors that occur during logging
			error_log(
				sprintf(
					'Failed to log category creation: %s (Term ID: %d)',
					$e->getMessage(),
					$term_id
				)
			);
		}
	}

	/**
	 * Log category update with detailed information
	 *
	 * @param int $term_id The ID of the updated category
	 */
	public function log_category_update( $term_id ) {
		// Get the term object
		$term = get_term( $term_id );

		// Return if term doesn't exist or there's an error
		if ( ! $term || is_wp_error( $term ) ) {
			return;
		}

		// Get the current user
		$current_user = wp_get_current_user();

		// Get parent category information if exists
		$parent_term = null;
		if ( $term->parent ) {
			$parent_term = get_term( $term->parent );
		}

		// Get taxonomy object for labels
		$taxonomy       = get_taxonomy( $term->taxonomy );
		$taxonomy_label = $taxonomy ? $taxonomy->labels->singular_name : $term->taxonomy;

		// Get the changes by comparing with the previous version
		$old_term_data = wp_cache_get( 'last_changed_term_' . $term_id, 'ultimakit-terms' );
		$changes       = array();

		if ( $old_term_data ) {
			if ( $old_term_data['name'] !== $term->name ) {
				$changes['name'] = array(
					'from' => $old_term_data['name'],
					'to'   => $term->name,
				);
			}
			if ( $old_term_data['slug'] !== $term->slug ) {
				$changes['slug'] = array(
					'from' => $old_term_data['slug'],
					'to'   => $term->slug,
				);
			}
			if ( $old_term_data['parent'] !== $term->parent ) {
				$changes['parent'] = array(
					'from' => $old_term_data['parent'],
					'to'   => $term->parent,
				);
			}
			if ( $old_term_data['description'] !== $term->description ) {
				$changes['description'] = array(
					'from' => $old_term_data['description'],
					'to'   => $term->description,
				);
			}
		}

		// Create detailed meta information
		$meta = array(
			'term_id'     => $term_id,
			'name'        => $term->name,
			'slug'        => $term->slug,
			'taxonomy'    => $taxonomy_label,
			'description' => $term->description,
			'parent'      => array(
				'id'   => $term->parent,
				'name' => ( $parent_term && ! is_wp_error( $parent_term ) ) ? $parent_term->name : '',
			),
			'count'       => $term->count,
			'updated_by'  => array(
				'user_id'    => $current_user->ID,
				'user_login' => $current_user->user_login,
				'user_email' => $current_user->user_email,
				'user_role'  => implode( ', ', $current_user->roles ),
			),
			'updated_at'  => current_time( 'mysql' ),
			'changes'     => $changes,
			'term_meta'   => get_term_meta( $term_id ),
			'term_link'   => get_term_link( $term_id ),
		);

		// Create a detailed message
		$message = sprintf(
			/* translators: 1: taxonomy label, 2: term name, 3: user login */
			__( 'Updated %1$s "%2$s" by %3$s', 'ultimakit-for-wp' ),
			$taxonomy_label,
			$term->name,
			$current_user->user_login
		);

		if ( ! empty( $changes ) ) {
			$message .= ' (' . implode( ', ', array_keys( $changes ) ) . ' modified)';
		}

		try {
			$this->log_activity(
				'category_update',
				'taxonomy',
				$term_id,
				$message,
				$meta
			);
		} catch ( Exception $e ) {
			error_log(
				sprintf(
					'Failed to log category update: %s (Term ID: %d)',
					$e->getMessage(),
					$term_id
				)
			);
		}
	}

	/**
	 * Log category deletion with detailed information
	 *
	 * @param int    $term_id  Term ID.
	 * @param int    $tt_id    Term taxonomy ID.
	 * @param string $taxonomy Taxonomy slug.
	 * @param mixed  $deleted_term Object containing the term data that was deleted.
	 */
	public function log_category_deletion( $term_id, $tt_id, $taxonomy, $deleted_term = null ) {
		// Only proceed if it's a category
		if ( $taxonomy !== 'category' ) {
			return;
		}

		// Get the current user
		$current_user = wp_get_current_user();

		// Get taxonomy object for labels
		$taxonomy_obj   = get_taxonomy( $taxonomy );
		$taxonomy_label = $taxonomy_obj ? $taxonomy_obj->labels->singular_name : $taxonomy;

		// Initialize meta array with basic information
		$meta = array(
			'term_id'          => $term_id,
			'term_taxonomy_id' => $tt_id,
			'taxonomy'         => $taxonomy_label,
			'deleted_by'       => array(
				'user_id'    => $current_user->ID,
				'user_login' => $current_user->user_login,
				'user_email' => $current_user->user_email,
				'user_role'  => implode( ', ', $current_user->roles ),
			),
			'deleted_at'       => current_time( 'mysql' ),
		);

		// If we have the deleted term object (passed by WordPress)
		if ( $deleted_term && is_object( $deleted_term ) ) {
			// Get parent category information if exists
			$parent_term = null;
			if ( ! empty( $deleted_term->parent ) ) {
				$parent_term = get_term( $deleted_term->parent );
			}

			// Add detailed term information to meta
			$meta = array_merge(
				$meta,
				array(
					'name'        => $deleted_term->name,
					'slug'        => $deleted_term->slug,
					'description' => $deleted_term->description,
					'parent'      => array(
						'id'   => $deleted_term->parent,
						'name' => ( $parent_term && ! is_wp_error( $parent_term ) ) ? $parent_term->name : '',
					),
					'count'       => $deleted_term->count,
					'term_group'  => $deleted_term->term_group,
					'filter'      => $deleted_term->filter,
				)
			);

			$message = sprintf(
				/* translators: 1: taxonomy label, 2: term name, 3: user login */
				__( 'Deleted %1$s "%2$s" by %3$s', 'ultimakit-for-wp' ),
				$taxonomy_label,
				$deleted_term->name,
				$current_user->user_login
			);

			// Add parent category information to message if exists
			if ( $parent_term && ! is_wp_error( $parent_term ) ) {
				$message .= sprintf(
					/* translators: %s: parent category name */
					__( ' (was under parent category "%s")', 'ultimakit-for-wp' ),
					$parent_term->name
				);
			}
		} else {
			// Fallback message if we don't have the deleted term object
			$message = sprintf(
				/* translators: 1: taxonomy label, 2: term ID, 3: user login */
				__( 'Deleted %1$s with ID %2$d by %3$s', 'ultimakit-for-wp' ),
				$taxonomy_label,
				$term_id,
				$current_user->user_login
			);
		}

		// Add information about associated posts if available
		if ( $deleted_term && isset( $deleted_term->count ) && $deleted_term->count > 0 ) {
			$meta['posts_affected'] = $deleted_term->count;
			$message               .= sprintf(
				/* translators: %d: number of posts */
				__( ' (%d posts were affected)', 'ultimakit-for-wp' ),
				$deleted_term->count
			);
		}

		try {
			// Log the activity
			$this->log_activity(
				'category_deletion',
				'taxonomy',
				$term_id,
				$message,
				$meta
			);

			// Optional: Log to WordPress debug log
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log(
					sprintf(
						'Category deleted: ID %d by user %s',
						$term_id,
						$current_user->user_login
					)
				);
			}
		} catch ( Exception $e ) {
			// Log any errors that occur during logging
			error_log(
				sprintf(
					'Failed to log category deletion: %s (Term ID: %d)',
					$e->getMessage(),
					$term_id
				)
			);
		}
	}

	/**
	 * Log comment creation with detailed information
	 *
	 * @param int $comment_id The ID of the newly created comment
	 */
	public function log_comment_creation( $comment_id ) {
		$comment = get_comment( $comment_id );
		if ( ! $comment ) {
			return;
		}

		// Get post information
		$post       = get_post( $comment->comment_post_ID );
		$post_title = $post ? $post->post_title : __( 'Unknown Post', 'ultimakit-for-wp' );

		// Get comment author information
		$author_name = $comment->comment_author;
		if ( $comment->user_id ) {
			$user        = get_user_by( 'id', $comment->user_id );
			$author_name = $user ? $user->display_name : $comment->comment_author;
		}

		// Create meta information
		$meta = array(
			'comment_data' => array(
				'author'       => $author_name,
				'author_email' => $comment->comment_author_email,
				'author_ip'    => $comment->comment_author_IP,
				'author_url'   => $comment->comment_author_url,
				'content'      => wp_strip_all_tags( $comment->comment_content ),
				'user_id'      => $comment->user_id,
				'user_agent'   => $comment->comment_agent,
				'type'         => $comment->comment_type,
				'parent'       => $comment->comment_parent,
			),
			'post_data'    => array(
				'post_id'    => $comment->comment_post_ID,
				'post_title' => $post_title,
				'post_type'  => $post ? $post->post_type : 'unknown',
			),
			'timestamp'    => current_time( 'mysql' ),
			'status'       => $comment->comment_approved,
		);

		try {
			$message = sprintf(
				/* translators: 1: author name, 2: post title */
				__( 'New comment by %1$s on post "%2$s"', 'ultimakit-for-wp' ),
				$author_name,
				$post_title
			);

			if ( $comment->comment_parent ) {
				$parent_comment = get_comment( $comment->comment_parent );
				if ( $parent_comment ) {
					$message .= sprintf(
						/* translators: %s: parent comment author */
						__( ' in reply to %s', 'ultimakit-for-wp' ),
						$parent_comment->comment_author
					);
				}
			}

			$this->log_activity(
				'comment_creation',
				'comment',
				$comment_id,
				$message,
				$meta
			);

		} catch ( Exception $e ) {
			error_log( sprintf( 'Failed to log comment creation: %s', $e->getMessage() ) );
		}
	}

	/**
	 * Log comment updates with change tracking
	 *
	 * @param int $comment_id The ID of the updated comment
	 */
	public function log_comment_update( $comment_id ) {
		$comment = get_comment( $comment_id );
		if ( ! $comment ) {
			return;
		}

		// Get the current user
		$current_user = wp_get_current_user();

		// Create meta information
		$meta = array(
			'comment_data' => array(
				'content'     => wp_strip_all_tags( $comment->comment_content ),
				'modified_by' => array(
					'user_id'    => $current_user->ID,
					'user_login' => $current_user->user_login,
				),
			),
			'timestamp'    => current_time( 'mysql' ),
		);

		try {
			$message = sprintf(
				/* translators: 1: comment ID, 2: user login */
				__( 'Comment ID %1$d updated by %2$s', 'ultimakit-for-wp' ),
				$comment_id,
				$current_user->user_login
			);

			$this->log_activity(
				'comment_update',
				'comment',
				$comment_id,
				$message,
				$meta
			);

		} catch ( Exception $e ) {
			error_log( sprintf( 'Failed to log comment update: %s', $e->getMessage() ) );
		}
	}

	/**
	 * Log comment deletion with context
	 *
	 * @param int $comment_id The ID of the deleted comment
	 */
	public function log_comment_deletion( $comment_id ) {
		$comment = get_comment( $comment_id );
		if ( ! $comment ) {
			return;
		}

		// Get the current user
		$current_user = wp_get_current_user();

		// Create meta information
		$meta = array(
			'deleted_comment_data' => array(
				'author'          => $comment->comment_author,
				'author_email'    => $comment->comment_author_email,
				'post_id'         => $comment->comment_post_ID,
				'content_preview' => wp_trim_words( wp_strip_all_tags( $comment->comment_content ), 20 ),
			),
			'deleted_by'           => array(
				'user_id'    => $current_user->ID,
				'user_login' => $current_user->user_login,
			),
			'timestamp'            => current_time( 'mysql' ),
		);

		try {
			$message = sprintf(
				/* translators: 1: comment author, 2: post ID, 3: user login */
				__( 'Comment by %1$s on post ID %2$d deleted by %3$s', 'ultimakit-for-wp' ),
				$comment->comment_author,
				$comment->comment_post_ID,
				$current_user->user_login
			);

			$this->log_activity(
				'comment_deletion',
				'comment',
				$comment_id,
				$message,
				$meta
			);

		} catch ( Exception $e ) {
			error_log( sprintf( 'Failed to log comment deletion: %s', $e->getMessage() ) );
		}
	}

	/**
	 * Log comment status changes with context
	 *
	 * @param string $new_status New comment status
	 * @param string $old_status Old comment status
	 * @param object $comment    Comment object
	 */
	public function log_comment_status_change( $new_status, $old_status, $comment ) {
		if ( ! $comment ) {
			return;
		}

		// Get the current user
		$current_user = wp_get_current_user();

		// Get post information
		$post       = get_post( $comment->comment_post_ID );
		$post_title = $post ? $post->post_title : __( 'Unknown Post', 'ultimakit-for-wp' );

		// Create meta information
		$meta = array(
			'status_change' => array(
				'old_status' => $old_status,
				'new_status' => $new_status,
			),
			'comment_data'  => array(
				'author'          => $comment->comment_author,
				'post_id'         => $comment->comment_post_ID,
				'post_title'      => $post_title,
				'content_preview' => wp_trim_words( wp_strip_all_tags( $comment->comment_content ), 20 ),
			),
			'changed_by'    => array(
				'user_id'    => $current_user->ID,
				'user_login' => $current_user->user_login,
			),
			'timestamp'     => current_time( 'mysql' ),
		);

		try {
			$message = sprintf(
				/* translators: 1: comment author, 2: old status, 3: new status, 4: user login */
				__( 'Comment by %1$s status changed from "%2$s" to "%3$s" by %4$s', 'ultimakit-for-wp' ),
				$comment->comment_author,
				$old_status,
				$new_status,
				$current_user->user_login
			);

			$this->log_activity(
				'comment_status_change',
				'comment',
				$comment->comment_ID,
				$message,
				$meta
			);

		} catch ( Exception $e ) {
			error_log( sprintf( 'Failed to log comment status change: %s', $e->getMessage() ) );
		}
	}

	/**
	 * Log media upload with detailed information
	 *
	 * @param int $attachment_id The ID of the uploaded media
	 */
	public function log_media_upload( $attachment_id ) {
		$attachment = get_post( $attachment_id );
		if ( ! $attachment ) {
			return;
		}

		// Get the current user
		$current_user = wp_get_current_user();

		// Get attachment metadata
		$metadata  = wp_get_attachment_metadata( $attachment_id );
		$file_path = get_attached_file( $attachment_id );
		$file_size = file_exists( $file_path ) ? size_format( filesize( $file_path ) ) : 'unknown';
		$mime_type = get_post_mime_type( $attachment_id );

		// Get image dimensions if it's an image
		$dimensions = '';
		if ( wp_attachment_is_image( $attachment_id ) ) {
			$image_data = wp_get_attachment_image_src( $attachment_id, 'full' );
			if ( $image_data ) {
				$dimensions = $image_data[1] . 'x' . $image_data[2];
			}
		}

		// Create meta information
		$meta = array(
			'file_info'       => array(
				'filename'   => basename( $file_path ),
				'file_size'  => $file_size,
				'mime_type'  => $mime_type,
				'dimensions' => $dimensions,
				'file_path'  => wp_basename( dirname( $file_path ) ),
			),
			'attachment_data' => array(
				'title'       => $attachment->post_title,
				'caption'     => $attachment->post_excerpt,
				'description' => $attachment->post_content,
				'alt_text'    => get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ),
			),
			'uploaded_by'     => array(
				'user_id'    => $current_user->ID,
				'user_login' => $current_user->user_login,
				'user_email' => $current_user->user_email,
				'user_role'  => implode( ', ', $current_user->roles ),
			),
			'timestamp'       => current_time( 'mysql' ),
			'metadata'        => $metadata,
		);

		try {
			$message = sprintf(
				/* translators: 1: file name, 2: file type, 3: file size, 4: user login */
				__( 'Media "%1$s" (%2$s, %3$s) uploaded by %4$s', 'ultimakit-for-wp' ),
				$attachment->post_title,
				$mime_type,
				$file_size,
				$current_user->user_login
			);

			if ( $dimensions ) {
				$message .= sprintf(
					/* translators: %s: dimensions */
					__( ' - Dimensions: %s', 'ultimakit-for-wp' ),
					$dimensions
				);
			}

			$this->log_activity(
				'media_upload',
				'media',
				$attachment_id,
				$message,
				$meta
			);

		} catch ( Exception $e ) {
			error_log( sprintf( 'Failed to log media upload: %s', $e->getMessage() ) );
		}
	}

	/**
	 * Log media updates with change tracking
	 *
	 * @param int $attachment_id The ID of the updated media
	 */
	public function log_media_update( $attachment_id ) {
		$attachment = get_post( $attachment_id );
		if ( ! $attachment ) {
			return;
		}

		// Get the current user
		$current_user = wp_get_current_user();

		// Get updated metadata
		$metadata  = wp_get_attachment_metadata( $attachment_id );
		$file_path = get_attached_file( $attachment_id );
		$mime_type = get_post_mime_type( $attachment_id );

		// Create meta information
		$meta = array(
			'updated_data' => array(
				'title'       => $attachment->post_title,
				'caption'     => $attachment->post_excerpt,
				'description' => $attachment->post_content,
				'alt_text'    => get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ),
				'mime_type'   => $mime_type,
			),
			'modified_by'  => array(
				'user_id'    => $current_user->ID,
				'user_login' => $current_user->user_login,
				'user_email' => $current_user->user_email,
				'user_role'  => implode( ', ', $current_user->roles ),
			),
			'timestamp'    => current_time( 'mysql' ),
			'metadata'     => $metadata,
		);

		try {
			$message = sprintf(
				/* translators: 1: media title, 2: user login */
				__( 'Media "%1$s" updated by %2$s', 'ultimakit-for-wp' ),
				$attachment->post_title,
				$current_user->user_login
			);

			$this->log_activity(
				'media_update',
				'media',
				$attachment_id,
				$message,
				$meta
			);

		} catch ( Exception $e ) {
			error_log( sprintf( 'Failed to log media update: %s', $e->getMessage() ) );
		}
	}

	/**
	 * Log media deletion with context
	 *
	 * @param int $attachment_id The ID of the deleted media
	 */
	public function log_media_deletion( $attachment_id ) {
		$attachment = get_post( $attachment_id );
		if ( ! $attachment ) {
			return;
		}

		// Get the current user
		$current_user = wp_get_current_user();

		// Get file information before deletion
		$file_path = get_attached_file( $attachment_id );
		$mime_type = get_post_mime_type( $attachment_id );
		$file_size = file_exists( $file_path ) ? size_format( filesize( $file_path ) ) : 'unknown';

		// Create meta information
		$meta = array(
			'deleted_file_info' => array(
				'filename'    => basename( $file_path ),
				'file_path'   => wp_basename( dirname( $file_path ) ),
				'file_size'   => $file_size,
				'mime_type'   => $mime_type,
				'title'       => $attachment->post_title,
				'caption'     => $attachment->post_excerpt,
				'description' => $attachment->post_content,
				'alt_text'    => get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ),
			),
			'deleted_by'        => array(
				'user_id'    => $current_user->ID,
				'user_login' => $current_user->user_login,
				'user_email' => $current_user->user_email,
				'user_role'  => implode( ', ', $current_user->roles ),
			),
			'timestamp'         => current_time( 'mysql' ),
		);

		try {
			$message = sprintf(
				/* translators: 1: file name, 2: file type, 3: user login */
				__( 'Media "%1$s" (%2$s) deleted by %3$s', 'ultimakit-for-wp' ),
				$attachment->post_title,
				$mime_type,
				$current_user->user_login
			);

			$this->log_activity(
				'media_deletion',
				'media',
				$attachment_id,
				$message,
				$meta
			);

		} catch ( Exception $e ) {
			error_log( sprintf( 'Failed to log media deletion: %s', $e->getMessage() ) );
		}
	}

	/**
	 * Log option updates with detailed change tracking
	 *
	 * @param string $option_name The name of the updated option
	 * @param mixed  $old_value   The old option value
	 * @param mixed  $new_value   The new option value
	 */
	public function log_option_update( $option_name, $old_value, $new_value ) {
		// Get the current user
		$current_user = wp_get_current_user();

		// Skip logging for certain sensitive or transient options
		$excluded_options = array(
			'cron',
			'active_plugins',
			'recently_activated',
			'_site_transient_',
			'_transient_',
			'auth_key',
			'auth_salt',
			'logged_in_key',
			'logged_in_salt',
			'nonce_key',
			'nonce_salt',
			'session_tokens',
		);

		foreach ( $excluded_options as $excluded ) {
			if ( strpos( $option_name, $excluded ) !== false ) {
				return;
			}
		}

		// Determine if the value is serialized
		$is_serialized = is_serialized( $new_value );

		// Prepare values for logging
		$old_value_for_log = $this->prepare_option_value_for_log( $old_value );
		$new_value_for_log = $this->prepare_option_value_for_log( $new_value );

		// Never log the values of options that look like credentials (e.g. mailserver_pass, API keys).
		// Empty values are kept so the created/deleted detection still works.
		if ( preg_match( '/pass|secret|token|key|salt|auth|nonce|license/i', (string) $option_name ) ) {
			$old_value_for_log = ( null === $old_value_for_log || '' === $old_value_for_log ) ? $old_value_for_log : '[redacted]';
			$new_value_for_log = ( null === $new_value_for_log || '' === $new_value_for_log ) ? $new_value_for_log : '[redacted]';
		}

		// Create meta information
		$meta = array(
			'option_info' => array(
				'name'          => $option_name,
				'is_serialized' => $is_serialized,
				'old_value'     => $old_value_for_log,
				'new_value'     => $new_value_for_log,
			),
			'changed_by'  => array(
				'user_id'    => $current_user->ID,
				'user_login' => $current_user->user_login,
				'user_email' => $current_user->user_email,
				'user_role'  => implode( ', ', $current_user->roles ),
			),
			'timestamp'   => current_time( 'mysql' ),
			'change_type' => $this->determine_change_type( $old_value, $new_value ),
		);

		try {
			// Create a descriptive message based on the type of change
			$message = $this->create_option_update_message(
				$option_name,
				$old_value_for_log,
				$new_value_for_log,
				$current_user->user_login
			);

			$this->log_activity(
				'option_update',
				'option',
				0,
				$message,
				$meta
			);

		} catch ( Exception $e ) {
			error_log( sprintf( 'Failed to log option update: %s', $e->getMessage() ) );
		}
	}

	/**
	 * Prepare option value for logging
	 *
	 * @param mixed $value The option value to prepare
	 * @return mixed
	 */
	private function prepare_option_value_for_log( $value ) {
		// Handle arrays and objects
		if ( is_array( $value ) || is_object( $value ) ) {
			// Convert to array if object
			$value = (array) $value;

			// Remove sensitive information
			$value = $this->remove_sensitive_data( $value );

			// Limit array depth and length for logging
			return $this->truncate_array( $value, 3, 5 );
		}

		// Handle strings
		if ( is_string( $value ) ) {
			// Truncate long strings
			if ( strlen( $value ) > 100 ) {
				return substr( $value, 0, 97 ) . '...';
			}
			return $value;
		}

		// Handle boolean values
		if ( is_bool( $value ) ) {
			return $value ? 'true' : 'false';
		}

		return $value;
	}

	/**
	 * Remove sensitive data from array
	 *
	 * @param array $array The array to clean
	 * @return array
	 */
	private function remove_sensitive_data( $array ) {
		$sensitive_keys = array(
			'password',
			'pass',
			'pwd',
			'auth',
			'key',
			'secret',
			'token',
			'api_key',
			'private',
		);

		foreach ( $array as $key => $value ) {
			foreach ( $sensitive_keys as $sensitive ) {
				if ( stripos( $key, $sensitive ) !== false ) {
					$array[ $key ] = '[REDACTED]';
					break;
				}
			}

			if ( is_array( $value ) ) {
				$array[ $key ] = $this->remove_sensitive_data( $value );
			}
		}

		return $array;
	}

	/**
	 * Truncate array for logging
	 *
	 * @param array $array The array to truncate
	 * @param int   $depth Maximum depth
	 * @param int   $length Maximum length at each level
	 * @return array
	 */
	private function truncate_array( $array, $depth = 3, $length = 5 ) {
		if ( $depth === 0 ) {
			return '[MAX_DEPTH]';
		}

		$result = array();
		$count  = 0;

		foreach ( $array as $key => $value ) {
			if ( $count >= $length ) {
				$result['[TRUNCATED]'] = 'Array was truncated...';
				break;
			}

			if ( is_array( $value ) ) {
				$result[ $key ] = $this->truncate_array( $value, $depth - 1, $length );
			} else {
				$result[ $key ] = $value;
			}

			++$count;
		}

		return $result;
	}

	/**
	 * Determine the type of change made to the option
	 *
	 * @param mixed $old_value Old option value
	 * @param mixed $new_value New option value
	 * @return string
	 */
	private function determine_change_type( $old_value, $new_value ) {
		if ( $old_value === null || $old_value === '' ) {
			return 'created';
		}
		if ( $new_value === null || $new_value === '' ) {
			return 'deleted';
		}
		return 'modified';
	}

	/**
	 * Create a descriptive message for the option update
	 *
	 * @param string $option_name Option name
	 * @param mixed  $old_value   Old value
	 * @param mixed  $new_value   New value
	 * @param string $user_login  Username
	 * @return string
	 */
	private function create_option_update_message( $option_name, $old_value, $new_value, $user_login ) {
		$change_type = $this->determine_change_type( $old_value, $new_value );

		switch ( $change_type ) {
			case 'created':
				return sprintf(
					/* translators: 1: option name, 2: user login */
					__( 'Option "%1$s" created by %2$s', 'ultimakit-for-wp' ),
					$option_name,
					$user_login
				);

			case 'deleted':
				return sprintf(
					/* translators: 1: option name, 2: user login */
					__( 'Option "%1$s" deleted by %2$s', 'ultimakit-for-wp' ),
					$option_name,
					$user_login
				);

			default:
				if ( is_scalar( $old_value ) && is_scalar( $new_value ) && strlen( $old_value ) < 50 && strlen( $new_value ) < 50 ) {
					return sprintf(
						/* translators: 1: option name, 2: old value, 3: new value, 4: user login */
						__( 'Option "%1$s" changed from "%2$s" to "%3$s" by %4$s', 'ultimakit-for-wp' ),
						$option_name,
						$old_value,
						$new_value,
						$user_login
					);
				}

				return sprintf(
					/* translators: 1: option name, 2: user login */
					__( 'Option "%1$s" modified by %2$s', 'ultimakit-for-wp' ),
					$option_name,
					$user_login
				);
		}
	}

	/**
	 * Log menu updates with detailed change tracking
	 *
	 * @param int $menu_id The ID of the updated menu
	 */
	public function log_menu_update( $menu_id ) {
		$menu = wp_get_nav_menu_object( $menu_id );
		if ( ! $menu ) {
			return;
		}

		// Get the current user
		$current_user = wp_get_current_user();

		// Get menu items
		$menu_items      = wp_get_nav_menu_items( $menu_id );
		$menu_item_count = is_array( $menu_items ) ? count( $menu_items ) : 0;

		// Get menu locations
		$menu_locations      = get_nav_menu_locations();
		$menu_location_names = array();
		foreach ( $menu_locations as $location => $id ) {
			if ( $id === $menu_id ) {
				$menu_location_names[] = $location;
			}
		}

		// Create meta information
		$meta = array(
			'menu_data'   => array(
				'name'        => $menu->name,
				'slug'        => $menu->slug,
				'description' => $menu->description,
				'item_count'  => $menu_item_count,
				'locations'   => $menu_location_names,
			),
			'menu_items'  => $this->get_menu_items_summary( $menu_items ),
			'modified_by' => array(
				'user_id'    => $current_user->ID,
				'user_login' => $current_user->user_login,
				'user_email' => $current_user->user_email,
				'user_role'  => implode( ', ', $current_user->roles ),
			),
			'timestamp'   => current_time( 'mysql' ),
		);

		try {
			$message = sprintf(
				/* translators: 1: menu name, 2: item count, 3: user login */
				__( 'Menu "%1$s" updated by %3$s (contains %2$d items)', 'ultimakit-for-wp' ),
				$menu->name,
				$menu_item_count,
				$current_user->user_login
			);

			if ( ! empty( $menu_location_names ) ) {
				$message .= sprintf(
					/* translators: %s: menu locations */
					__( ' - Locations: %s', 'ultimakit-for-wp' ),
					implode( ', ', $menu_location_names )
				);
			}

			$this->log_activity(
				'menu_update',
				'menu',
				$menu_id,
				$message,
				$meta
			);

		} catch ( Exception $e ) {
			error_log( sprintf( 'Failed to log menu update: %s', $e->getMessage() ) );
		}
	}

	/**
	 * Log menu creation with detailed information
	 *
	 * @param int $menu_id The ID of the created menu
	 */
	public function log_menu_creation( $menu_id ) {
		$menu = wp_get_nav_menu_object( $menu_id );
		if ( ! $menu ) {
			return;
		}

		// Get the current user
		$current_user = wp_get_current_user();

		// Create meta information
		$meta = array(
			'menu_data'  => array(
				'name'        => $menu->name,
				'slug'        => $menu->slug,
				'description' => $menu->description,
			),
			'created_by' => array(
				'user_id'    => $current_user->ID,
				'user_login' => $current_user->user_login,
				'user_email' => $current_user->user_email,
				'user_role'  => implode( ', ', $current_user->roles ),
			),
			'timestamp'  => current_time( 'mysql' ),
		);

		try {
			$message = sprintf(
				/* translators: 1: menu name, 2: user login */
				__( 'Menu "%1$s" created by %2$s', 'ultimakit-for-wp' ),
				$menu->name,
				$current_user->user_login
			);

			$this->log_activity(
				'menu_creation',
				'menu',
				$menu_id,
				$message,
				$meta
			);

		} catch ( Exception $e ) {
			error_log( sprintf( 'Failed to log menu creation: %s', $e->getMessage() ) );
		}
	}

	/**
	 * Log menu deletion with context
	 *
	 * @param int $menu_id The ID of the deleted menu
	 */
	public function log_menu_deletion( $menu_id ) {
		$menu = wp_get_nav_menu_object( $menu_id );
		if ( ! $menu ) {
			return;
		}

		// Get the current user
		$current_user = wp_get_current_user();

		// Get menu items before deletion
		$menu_items      = wp_get_nav_menu_items( $menu_id );
		$menu_item_count = is_array( $menu_items ) ? count( $menu_items ) : 0;

		// Create meta information
		$meta = array(
			'deleted_menu_data' => array(
				'name'          => $menu->name,
				'slug'          => $menu->slug,
				'description'   => $menu->description,
				'item_count'    => $menu_item_count,
				'items_summary' => $this->get_menu_items_summary( $menu_items ),
			),
			'deleted_by'        => array(
				'user_id'    => $current_user->ID,
				'user_login' => $current_user->user_login,
				'user_email' => $current_user->user_email,
				'user_role'  => implode( ', ', $current_user->roles ),
			),
			'timestamp'         => current_time( 'mysql' ),
		);

		try {
			$message = sprintf(
				/* translators: 1: menu name, 2: item count, 3: user login */
				__( 'Menu "%1$s" deleted by %3$s (contained %2$d items)', 'ultimakit-for-wp' ),
				$menu->name,
				$menu_item_count,
				$current_user->user_login
			);

			$this->log_activity(
				'menu_deletion',
				'menu',
				$menu_id,
				$message,
				$meta
			);

		} catch ( Exception $e ) {
			error_log( sprintf( 'Failed to log menu deletion: %s', $e->getMessage() ) );
		}
	}

	/**
	 * Get a summary of menu items
	 *
	 * @param array $menu_items Array of menu items
	 * @return array
	 */
	private function get_menu_items_summary( $menu_items ) {
		if ( ! is_array( $menu_items ) ) {
			return array();
		}

		$summary = array();
		foreach ( $menu_items as $item ) {
			$summary[] = array(
				'id'         => $item->ID,
				'title'      => $item->title,
				'type'       => $item->type,
				'url'        => $item->url,
				'menu_order' => $item->menu_order,
				'parent'     => $item->menu_item_parent,
			);
		}

		return $summary;
	}

	public function log_widget_update( $old_value, $new_value ) {
		$this->log_activity(
			'widget_update',
			'widget',
			0,
			__( 'Updated widget settings', 'ultimakit-for-wp' )
		);
	}

	public function log_theme_switch( $new_theme_name, $new_theme, $old_theme ) {
		$this->log_activity(
			'theme_switch',
			'theme',
			0,
			sprintf(
				/* translators: 1: old theme name, 2: new theme name */
				__( 'Switched theme from "%1$s" to "%2$s"', 'ultimakit-for-wp' ),
				$old_theme->get( 'Name' ),
				$new_theme_name
			)
		);
	}

	public function handle_csv_export() {
		if ( ! isset( $_POST['export_logs'] ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have sufficient permissions to export logs.', 'ultimakit-for-wp' ) );
		}

		// Without a nonce an attacker could make a logged-in admin's browser export the log.
		check_admin_referer( 'ultimakit_export_activity_logs' );

		global $wpdb;
		$logs = $wpdb->get_results(
			"SELECT l.*, u.display_name 
            FROM {$this->table_name} l 
            LEFT JOIN {$wpdb->users} u ON l.user_id = u.ID 
            ORDER BY l.created_at DESC"
		);

		header( 'Content-Type: text/csv' );
		header( 'Content-Disposition: attachment; filename="activity-logs-' . date( 'Y-m-d' ) . '.csv"' );

		$output = fopen( 'php://output', 'w' );

		// Add CSV headers
		fputcsv(
			$output,
			array_map(
				array( $this, 'escape_csv_cell' ),
				array(
					__( 'Date/Time', 'ultimakit-for-wp' ),
					__( 'User', 'ultimakit-for-wp' ),
					__( 'Action', 'ultimakit-for-wp' ),
					__( 'Details', 'ultimakit-for-wp' ),
					__( 'IP Address', 'ultimakit-for-wp' ),
				)
			)
		);

		// Add log entries
		foreach ( $logs as $log ) {
			$user_name = $log->display_name ? $log->display_name : __( 'Deleted User', 'ultimakit-for-wp' );
			fputcsv(
				$output,
				array_map(
					array( $this, 'escape_csv_cell' ),
					array(
						wp_date( 'Y-m-d H:i:s', strtotime( $log->created_at ) ),
						$user_name,
						$this->format_action_name( $log->action ),
						$log->details,
						$log->ip_address,
					)
				)
			);
		}

		fclose( $output );
		exit;
	}

	/**
	 * Stop spreadsheet apps from treating a CSV cell as a formula (CSV injection).
	 *
	 * @param mixed $value Cell value.
	 * @return mixed
	 */
	private function escape_csv_cell( $value ) {
		if ( is_string( $value ) && '' !== $value && in_array( $value[0], array( '=', '+', '-', '@', "\t", "\r" ), true ) ) {
			return "'" . $value;
		}
		return $value;
	}

	private function get_action_badge_class( $action ) {
		$classes = array(
			'post_'     => 'bg-primary',
			'user_'     => 'bg-success',
			'plugin_'   => 'bg-info',
			'comment_'  => 'bg-warning',
			'media_'    => 'bg-secondary',
			'category_' => 'bg-dark',
			'menu_'     => 'bg-danger',
			'widget_'   => 'bg-info',
			'option_'   => 'bg-secondary',
			'theme_'    => 'bg-primary',
			'failed_'   => 'bg-danger',
		);

		foreach ( $classes as $prefix => $class ) {
			if ( strpos( $action, $prefix ) === 0 ) {
				return $class;
			}
		}

		return 'bg-secondary';
	}

	private function format_action_name( $action ) {
		return ucwords( str_replace( '_', ' ', $action ) );
	}

	public function delete_old_logs() {
		global $wpdb;
		$retention_days = intval( $this->getModuleSettings( $this->ID, 'log_retention', 30 ) );

		if ( $retention_days > 0 ) {
			$wpdb->query(
				$wpdb->prepare(
					"DELETE FROM {$this->table_name} WHERE created_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
					$retention_days
				)
			);
		}
	}

	public function schedule_retention_cleanup() {
		if ( ! wp_next_scheduled( 'admin_logger_cleanup_cron' ) ) {
			wp_schedule_event( time(), 'daily', 'admin_logger_cleanup_cron' );
		}
	}
}