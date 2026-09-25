<?php
/**
 * Class UltimaKit_Module_Redirect_Manager
 *
 * @since 1.0.0
 * @package    UltimaKit
 */

/**
 * Class UltimaKit_Module_Redirect_Manager
 *
 * This class provides methods to control the display of dashboard widgets based on user preferences.
 * It allows users to selectively hide certain dashboard widgets to streamline their WordPress dashboard
 * experience and improve usability.
 *
 * @since 1.0.0
 */
class UltimaKit_Module_Redirect_Manager extends UltimaKit_Module_Manager {
	/**
	 *
	 * @var string
	 */
	protected $ID = 'ultimakit_module_redirect_manager';

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


	private $table_name;
	private $db_version = '1.0.0';
	private $db_version_key;

	/**
	 * URL providing more detailed information about the module.
	 *
	 * @var string
	 */
	protected $read_more_link = 'redirect-manager-in-wordpress';

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
		$this->name        = __( 'Redirect Manager', 'ultimakit-for-wp' );
		$this->description = __( 'Create and manage 301 redirects for deleted or moved pages.', 'ultimakit-for-wp' );
		$this->is_active   = $this->isModuleActive( $this->ID );

		$this->initializeModule();

		global $wpdb;
		$this->db_version_key = 'ultimakit_redirect_manager_db_version';
		$this->table_name     = $wpdb->prefix . 'ultimakit_redirect_manager';
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

			// Check and setup database when module is loaded
			add_action( 'init', array( $this, 'check_db_setup' ), 1 );

			add_action( 'init', array( $this, 'handle_redirects' ), 1 );
			add_action( 'admin_menu', array( $this, 'add_submenu_page' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
			add_action( 'wp_ajax_wpuk_save_redirect', array( $this, 'ajax_save_redirect' ) );
			add_action( 'wp_ajax_wpuk_delete_redirect', array( $this, 'ajax_delete_redirect' ) );

			// Hook into post deletion
			add_action( 'wp_trash_post', array( $this, 'suggest_redirect_on_delete' ) );
			add_action( 'delete_post', array( $this, 'suggest_redirect_on_delete' ) );
			add_action( 'admin_notices', array( $this, 'show_redirect_notice' ) );

		}
	}

	/**
	 * Add submenu page
	 */
	public function add_submenu_page() {
		// Check if parent menu exists
		global $submenu;
		if ( ! isset( $submenu['wp-ultimakit-dashboard'] ) ) {
			return;
		}

		add_submenu_page(
			'wp-ultimakit-dashboard',
			__( 'Redirect Manager', 'ultimakit-for-wp' ),
			__( 'Redirect Manager', 'ultimakit-for-wp' ),
			'manage_options',
			'wp-ultimakit-redirect-manager',
			array( $this, 'x_render_page' )
		);
	}

	/**
	 * Enqueue admin assets.
	 *
	 * @param string $hook Current admin page.
	 */
	public function enqueue_admin_assets( $hook ) {

		wp_enqueue_script(
			'wpuk-redirect-manager',
			plugin_dir_url( __FILE__ ) . 'module-script.js',
			array( 'jquery' ),
			'1.0.0',
			true
		);

		wp_localize_script(
			'wpuk-redirect-manager',
			'wpukRedirect',
			array(
				'nonce'    => wp_create_nonce( 'wpuk_redirect_nonce' ),
				'ajaxurl'  => admin_url( 'admin-ajax.php' ),
				'messages' => array(
					'confirmDelete' => __( 'Are you sure you want to delete this redirect?', 'ultimakit-for-wp' ),
					'success'       => __( 'Redirect saved successfully', 'ultimakit-for-wp' ),
					'error'         => __( 'Error saving redirect', 'ultimakit-for-wp' ),
					'deleteSuccess' => __( 'Redirect deleted successfully', 'ultimakit-for-wp' ),
					'deleteError'   => __( 'Error deleting redirect', 'ultimakit-for-wp' ),
				),
			)
		);
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
			$this->create_redirect_table();
		}
	}

	public function create_redirect_table() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS {$this->table_name} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            source_url varchar(255) NOT NULL,
            target_url varchar(255) NOT NULL,
            redirect_type smallint(4) NOT NULL DEFAULT 301,
            hits bigint(20) NOT NULL DEFAULT 0,
            status varchar(20) NOT NULL DEFAULT 'active',
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            last_accessed datetime DEFAULT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY source_url (source_url)
        ) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

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

	public function handle_redirects() {
		if ( is_admin() ) {
			return;
		}

		$current_url = rtrim( $_SERVER['REQUEST_URI'], '/' );

		global $wpdb;
		$redirect = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$this->table_name} 
            WHERE source_url = %s 
            AND status = 'active'",
				$current_url
			)
		);

		if ( $redirect ) {
			// Update hit count and last accessed
			$wpdb->update(
				$this->table_name,
				array(
					'hits'          => $redirect->hits + 1,
					'last_accessed' => current_time( 'mysql' ),
				),
				array( 'id' => $redirect->id )
			);

			wp_redirect( $redirect->target_url, $redirect->redirect_type );
			exit;
		}
	}

	public function x_render_page() {
		$object = new UltimaKit_Helpers();
		global $wpdb;
		$redirects = $wpdb->get_results( "SELECT * FROM {$this->table_name} ORDER BY created_at DESC" );
		?>
		<div class="wrap">
			<?php $object->ultimakit_get_header(); ?>
			<div class="container bg-white text-dark p-3 mb-3">
				<!-- Nav tabs -->
				<ul class="nav nav-tabs" id="wpukTabs" role="tablist">
					<li class="nav-item" role="presentation">
						<a class="nav-link active" id="settings-tab" data-bs-toggle="tab" href="#redirect-manager-settings" role="tab" aria-controls="redirect-manager-settings" aria-selected="true"><?php echo esc_html_e( 'Redirect Manager (301)', 'ultimakit-for-wp' ); ?></a>
					</li>
					
				</ul>

				<!-- Tab panes -->
				<div class="tab-content" id="wpukTabsContent">
					<div class="tab-pane fade show active" id="redirect-manager-settings" role="tabpanel" aria-labelledby="settings-tab">
						<!-- Your modules content here -->
						<div class="row">
							<form id="wpuk-redirect-form">
								<table class="form-table">
									<tr>
										<th scope="row">
											<label for="source_url"><?php _e( 'Source URL', 'ultimakit-for-wp' ); ?></label>
										</th>
										<td>
											<input type="text" id="source_url" name="source_url" class="regular-text" required>
											<p class="description"><?php _e( 'Enter the old URL (e.g., /old-page)', 'ultimakit-for-wp' ); ?></p>
										</td>
									</tr>
									<tr>
										<th scope="row">
											<label for="target_url"><?php _e( 'Target URL', 'ultimakit-for-wp' ); ?></label>
										</th>
										<td>
											<input type="text" id="target_url" name="target_url" class="regular-text" required>
											<p class="description"><?php _e( 'Enter the new URL (e.g., /new-page)', 'ultimakit-for-wp' ); ?></p>
										</td>
									</tr>
								</table>
								<p class="submit">
									<button type="submit" class="btn btn-success bg-primary"><?php _e( 'Add Redirect', 'ultimakit-for-wp' ); ?></button>
								</p>
							</form>
						</div>

						<div class="row">
							<div class="wpuk-redirects-list">
								<h5><?php _e( 'Existing Redirects', 'ultimakit-for-wp' ); ?></h2>
								<table class="table table-striped table-bordered">
									<thead>
										<tr>
											<th><?php _e( 'Source URL', 'ultimakit-for-wp' ); ?></th>
											<th><?php _e( 'Target URL', 'ultimakit-for-wp' ); ?></th>
											<th><?php _e( 'Hits', 'ultimakit-for-wp' ); ?></th>
											<th><?php _e( 'Last Accessed', 'ultimakit-for-wp' ); ?></th>
											<th><?php _e( 'Actions', 'ultimakit-for-wp' ); ?></th>
										</tr>
									</thead>
									<tbody>
										<?php if ( empty( $redirects ) ) : ?>
											<tr>
												<td colspan="5"><?php _e( 'No redirects found.', 'ultimakit-for-wp' ); ?></td>
											</tr>
										<?php else : ?>
											<?php foreach ( $redirects as $redirect ) : ?>
												<tr data-id="<?php echo esc_attr( $redirect->id ); ?>">
													<td><?php echo esc_html( $redirect->source_url ); ?></td>
													<td><?php echo esc_html( $redirect->target_url ); ?></td>
													<td><?php echo esc_html( $redirect->hits ); ?></td>
													<td><?php echo $redirect->last_accessed ? esc_html( date_i18n( get_option( 'date_format' ), strtotime( $redirect->last_accessed ) ) ) : '-'; ?></td>
													<td>
														<button class="btn btn-danger button-small wpuk-delete-redirect"><?php _e( 'Delete', 'ultimakit-for-wp' ); ?></button>
													</td>
												</tr>
											<?php endforeach; ?>
										<?php endif; ?>
									</tbody>
								</table>
							</div>
						</div>
					</div>

				</div>
			</div>
		</div>

		
		<?php
	}

	public function ajax_save_redirect() {
		global $wpdb;

		// Verify nonce and permissions
		check_ajax_referer( 'wpuk_redirect_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied', 'ultimakit-for-wp' ) ) );
			return;
		}

		// Get and sanitize input data
		$source_url = isset( $_POST['source_url'] ) ? sanitize_text_field( wp_unslash( $_POST['source_url'] ) ) : '';
		$target_url = isset( $_POST['target_url'] ) ? sanitize_text_field( wp_unslash( $_POST['target_url'] ) ) : '';

		// Validate required fields
		if ( empty( $source_url ) || empty( $target_url ) ) {
			wp_send_json_error( array( 'message' => __( 'Source URL and Target URL are required', 'ultimakit-for-wp' ) ) );
			return;
		}

		// Ensure URLs start with /
		$source_url = '/' . ltrim( $source_url, '/' );
		$target_url = '/' . ltrim( $target_url, '/' );

		// Check for existing source URL
		$exists = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$this->table_name} WHERE source_url = %s",
				$source_url
			)
		);

		if ( $exists ) {
			wp_send_json_error( array( 'message' => __( 'A redirect with this source URL already exists', 'ultimakit-for-wp' ) ) );
			return;
		}

		// Insert new redirect
		$current_time = current_time( 'mysql' );
		$result       = $wpdb->insert(
			$this->table_name,
			array(
				'source_url'    => $source_url,
				'target_url'    => $target_url,
				'hits'          => 0,
				'created_at'    => $current_time,
				'last_accessed' => $current_time,
			),
			array( '%s', '%s', '%d', '%s', '%s' )
		);

		if ( ! $result ) {
			wp_send_json_error( array( 'message' => __( 'Error saving redirect', 'ultimakit-for-wp' ) ) );
			return;
		}

		$redirect_id = $wpdb->insert_id;

		// Get the new redirect data
		$redirect = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$this->table_name} WHERE id = %d",
				$redirect_id
			)
		);

		if ( ! $redirect ) {
			wp_send_json_error( array( 'message' => __( 'Error retrieving redirect data', 'ultimakit-for-wp' ) ) );
			return;
		}

		// Format the response data with proper null checks
		$response_data = array(
			'message'  => __( 'Redirect saved successfully', 'ultimakit-for-wp' ),
			'redirect' => array(
				'id'            => $redirect->id,
				'source_url'    => $redirect->source_url,
				'target_url'    => $redirect->target_url,
				'hits'          => (int) $redirect->hits,
				'last_accessed' => ! empty( $redirect->last_accessed ) ? date_i18n( get_option( 'date_format' ), strtotime( $redirect->last_accessed ) ) : '-',
				'created_at'    => ! empty( $redirect->created_at ) ? date_i18n( get_option( 'date_format' ), strtotime( $redirect->created_at ) ) : '-',
				'last_accessed' => ! empty( $redirect->last_accessed ) ? date_i18n( get_option( 'date_format' ), strtotime( $redirect->last_accessed ) ) : '-',
			),
		);

		wp_send_json_success( $response_data );
	}




	public function ajax_delete_redirect() {
		check_ajax_referer( 'wpuk_redirect_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Permission denied' );
		}

		$redirect_id = isset( $_POST['id'] ) ? intval( $_POST['id'] ) : 0;

		if ( ! $redirect_id ) {
			wp_send_json_error( 'Invalid redirect ID' );
		}

		global $wpdb;
		$result = $wpdb->delete(
			$this->table_name,
			array( 'id' => $redirect_id ),
			array( '%d' )
		);

		if ( $result ) {
			wp_send_json_success( __( 'Redirect deleted successfully', 'ultimakit-for-wp' ) );
		} else {
			wp_send_json_error( 'Error deleting redirect' );
		}
	}

	public function suggest_redirect_on_delete( $post_id ) {
		// Verify if user has permission
		if ( ! current_user_can( 'delete_posts' ) ) {
			return;
		}

		$post = get_post( $post_id );

		// Only proceed for public post types
		$post_type = get_post_type( $post );
		if ( ! is_post_type_viewable( $post_type ) ) {
			return;
		}

		// Only suggest for previously published content
		if ( $post && $post->post_status === 'publish' ) {
			// Get the relative URL
			$post_url = wp_make_link_relative( get_permalink( $post_id ) );

			// Store the URL and post title for the notice
			set_transient(
				'wpuk_deleted_post_' . get_current_user_id(),
				array(
					'url'   => $post_url,
					'title' => $post->post_title,
					'type'  => $post_type,
				),
				60 // Increase timeout to 60 seconds
			);
		}
	}

	public function show_redirect_notice() {
		$deleted_post = get_transient( 'wpuk_deleted_post_' . get_current_user_id() );

		if ( ! $deleted_post ) {
			return;
		}

		// Clear the transient
		delete_transient( 'wpuk_deleted_post_' . get_current_user_id() );

		?>
		<div class="notice notice-warning is-dismissible">
			<p>
				<?php
				printf(
					__( 'The %1$s "%2$s" was moved to trash. Would you like to <a href="%3$s">set up a redirect</a> for it?', 'ultimakit-for-wp' ),
					esc_html( get_post_type_object( $deleted_post['type'] )->labels->singular_name ),
					esc_html( $deleted_post['title'] ),
					esc_url( admin_url( 'admin.php?page=wp-ultimakit-redirect-manager&source=' . urlencode( $deleted_post['url'] ) ) )
				);
				?>
			</p>
		</div>
		<?php
	}
}
