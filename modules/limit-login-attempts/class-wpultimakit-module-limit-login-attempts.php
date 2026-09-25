<?php
/**
 * Class UltimaKit_Module_Limit_Login_Attempts
 *
 * @since 1.0.0
 * @package    UltimaKit
 */

/**
 * Class UltimaKit_Module_Limit_Login_Attempts
 *
 * This class provides methods to control the display of dashboard widgets based on user preferences.
 * It allows users to selectively hide certain dashboard widgets to streamline their WordPress dashboard
 * experience and improve usability.
 *
 * @since 1.0.0
 */
class UltimaKit_Module_Limit_Login_Attempts extends UltimaKit_Module_Manager {
	/**
	 * @var string
	 */
	protected $ID = 'ultimakit_module_limit_login_attempts';

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
	protected $category = 'Log In/Out | Register';

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
	protected $read_more_link = 'limit-login-attempts-in-wordpress';

	/**
	 * The settings associated with the module, if any.
	 *
	 * @var array
	 */
	protected $settings;

	/**
	 * URL to the settings page for the module.
	 *
	 * @var string
	 */
	protected $settings_link;

	protected $login_failed;
	protected $login_attempt;
	private $db_version = '1.0.0';
	private $db_version_key;

	/**
	 *
	 * Initializes the module with default values for properties and prepares
	 * any necessary setup or hooks into WordPress. This may include setting
	 * initial values, registering hooks, or preparing resources needed for
	 * the module to function properly within WordPress.
	 */
	public function __construct() {
		$this->name        = __( 'Login Attempts', 'ultimakit-for-wp' );
		$this->description = __( 'Add secured layer of security to your website by limiting login attempts.', 'ultimakit-for-wp' );
		$this->is_active   = $this->isModuleActive( $this->ID );
		$this->settings    = 'yes';
		$this->initializeModule();

		global $wpdb;
		$this->login_failed  = $wpdb->prefix . 'ultimakit_login_failed';
		$this->login_attempt = $wpdb->prefix . 'ultimakit_login_attempt';

		$this->db_version_key = 'ultimakit_login_attempts_db_version';

		// Check and setup database when module is loaded
		add_action( 'init', array( $this, 'check_db_setup' ), 1 );
	}

	/**
	 * Check and setup database
	 */
	public function check_db_setup() {
		if ( ! $this->is_active ) {
			return;
		}

		$installed_version = get_option( $this->db_version_key, '0' );
		if ( ! $this->is_table_exists( $this->login_failed ) || version_compare( $installed_version, $this->db_version, '<' ) ) {
			$this->create_login_attempts_failed_table();
		}

		if ( ! $this->is_table_exists( $this->login_attempt ) || version_compare( $installed_version, $this->db_version, '<' ) ) {
			$this->create_login_attempts_attempt_table();
		}

		// Update version in database
		update_option( $this->db_version_key, $this->db_version );
	}

	public function create_login_attempts_failed_table() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		/*Create login failed table*/
		$login_failed_sql = " CREATE TABLE IF NOT EXISTS $this->login_failed (
			`id` int(11) NOT NULL AUTO_INCREMENT,
			`username` varchar(100) NOT NULL,
			`ip` varchar(15) NOT NULL,
			`country` varchar(60)  NULL,
			`redirect_to` varchar(255)  NULL,
			`status` int(11) NOT NULL DEFAULT 0,
			`locktime` varchar(255)  NULL,
			`locklimit` varchar(255)  NULL,
			`date` datetime NOT NULL DEFAULT current_timestamp(),
			UNIQUE KEY id (id)
		)$charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$wpdb->query( $login_failed_sql );

		// Run version-specific migrations
		$this->run_migrations( get_option( $this->db_version_key, '0' ) );
	}

	public function create_login_attempts_attempt_table() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();
		/*Create login count table*/
		$login_attempt_sql = "CREATE TABLE IF NOT EXISTS $this->login_attempt (
				`id` int(11) NOT NULL AUTO_INCREMENT,
				`attempt` varchar(100) NOT NULL,
				`ip` varchar(15) NOT NULL,
				`status` int(11) NOT NULL DEFAULT 0,
				`date` datetime NOT NULL DEFAULT current_timestamp(),
				UNIQUE KEY id (id)
			)$charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$wpdb->query( $login_attempt_sql );

		// Run version-specific migrations
		$this->run_migrations( get_option( $this->db_version_key, '0' ) );
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
			add_action( 'admin_enqueue_scripts', array( $this, 'add_scripts' ) );
			add_action( 'admin_footer', array( $this, 'add_modal' ) );

			// add_action( 'admin_init', array( $this, 'ultimakit_create_login_attempts_table' ) );

			// Login Attempts
			add_filter( 'authenticate', array( $this, 'ultimakit_check_attempted_login' ), 30, 3 );
			add_action( 'wp_login_failed', array( $this, 'ultimakit_login_failed' ), 10, 1 );
			add_action( 'admin_menu', array( $this, 'ultimakit_user_login_attempt' ) );
			add_action( 'wp_ajax_ultimakit_unblock_user', array( $this, 'ultimakit_unblock_user' ) );
			add_action( 'init', array( $this, 'ultimakit_clear_db' ) );
			add_action( 'admin_init', array( $this, 'ultimakit_clear_db' ) );
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
		$arguments['title'] = __( 'Login Limit Attempts Settings', 'ultimakit-for-wp' );

		$arguments['fields'] = array(
			'login_attempts'  => array(
				'type'  => 'text',
				'label' => __( 'Login Attempts', 'ultimakit-for-wp' ),
				'value' => $this->getModuleSettings( $this->ID, 'login_attempts' ),
			),
			'lockout_minutes' => array(
				'type'  => 'text',
				'label' => __( 'Lockout Time (Minutes)', 'ultimakit-for-wp' ),
				'value' => $this->getModuleSettings( $this->ID, 'lockout_minutes' ),
			),
		);

		$this->ultimakit_generate_modal( $arguments );
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
			'ultimakit_limit_login',
			array(
				'ajax_url'   => admin_url( 'admin-ajax.php' ),
				'ajax_nonce' => wp_create_nonce( 'ultimakit-limit-login-attempts' ),
			)
		);
	}


	public function ultimakit_user_login_attempt() {

		add_submenu_page(
			'wp-ultimakit-dashboard', // Parent slug
			__( 'Login Attempts', 'ultimakit-for-wp' ), // Page title
			__( 'Login Attempts', 'ultimakit-for-wp' ), // Menu title
			'manage_options', // Capability
			'wp-ultimakit-limit-login-attemps', // Menu slug
			array( $this, 'settings_admin_login_attempt' ) // Function to display the page content
		);
	}


	/**
	 * Creates a table for logging email activities.
	 *
	 * Ensures that the table only gets created if it does not already exist.
	 * This function utilizes the WordPress $wpdb object to interact with the database
	 * and the dbDelta function for safe table creation.
	 */
	public function ultimakit_create_login_attempts_table() {
	}

	/**
	 * Check attempted login and handle authentication limits.
	 *
	 * @param WP_User|WP_Error|null $user     WP_User object if authentication successful, WP_Error object on failure, or null if no authentication check was performed.
	 * @param string                $username User's username.
	 * @param string                $password User's password.
	 *
	 * @return WP_User|WP_Error|null WP_User object if authentication successful, WP_Error object on failure, or null if no authentication check was performed.
	 */
	public function ultimakit_check_attempted_login( $user, $username, $password ) {
		global $wpdb;

		// Get user login attempt configuration
		$wp_config = $this->getModuleSettings( $this->ID, 'login_attempts' );

		// Get user's IP address
		$ultimakit_ip = $this->ultimakit_get_the_user_ip();

		// Get login attempts limit from module settings
		$login_failed_option = $this->getModuleSettings( $this->ID, 'login_attempts' );

		// Get the last failed login block time for the user's IP
		$log_block_locktime = $wpdb->get_var( $wpdb->prepare( "SELECT date FROM {$this->login_failed} WHERE ip = %s AND status = '1' ORDER BY id DESC", $ultimakit_ip ) );

		// Current date and time
		$date_time_wp = date_i18n( 'Y-m-d H:i:s' );

		// Get login status for the IP
		$login_status = $wpdb->get_var( $wpdb->prepare( "SELECT attempt FROM {$this->login_attempt} WHERE ip = %s", $ultimakit_ip ) );

		// Check if there is no previous login block
		if ( empty( $log_block_locktime ) ) {
			// Check if the login attempts exceed the limit
			if ( ( $login_status + 1 ) >= $login_failed_option ) {
				// Store the failed user attempt
				$this->ultimakit_storefailed_user();

				// Add action to display custom login message
				add_action( 'login_footer', array( $this, 'ultimakit_login_footer' ), 100 );

				// Return error message for too many login attempts
				return new WP_Error( 'too_many_tried', sprintf( __( 'Sorry: You have reached the authentication limit, please try again after %1$s.', 'ultimakit-for-wp' ), 'some time' ) );
			}
		} else {
			// Clear database entries for failed login attempts
			$this->ultimakit_clear_db();
		}

		// Return the user object
		return $user;
	}

	/**
	 * Clear database entries related to failed login attempts.
	 */
	public function ultimakit_clear_db() {
		global $wpdb;

		// Get the user's IP address
		$ultimakit_ip = $this->ultimakit_get_the_user_ip();

		// Get the last failed login block time for the user's IP
		$log_block_locktime = $wpdb->get_var( $wpdb->prepare( "SELECT date FROM {$this->login_failed} WHERE ip = %s AND status = 1", $ultimakit_ip ) );

		// Current date and time
		$date_time_wp = date_i18n( 'Y-m-d H:i:s' );
		$date_formate = get_option( 'date_format' );
		$time_formate = get_option( 'time_format' );
		$wpdate       = date( $date_formate . ' ' . $time_formate, strtotime( $date_time_wp ) );
		$newdate      = date_i18n( 'Y-m-d H:i:s', $wpdate );

		// Get the count of records before the current date
		$get_record = $wpdb->get_results( $wpdb->prepare( "SELECT count(date) FROM {$this->login_attempt} WHERE `date` <= %s", $newdate ) );

		// Check if the user is still blocked
		if ( $log_block_locktime >= $newdate ) {
			// Add action to display custom login message
			add_action( 'login_footer', array( $this, 'ultimakit_login_footer' ), 100 );

			// Return error message for too many login attempts
			return new WP_Error( 'too_many_tried', sprintf( __( 'Sorry: You have reached the authentication limit, please try again after %1$s.', 'ultimakit-for-wp' ), 'some time' ) );
		} else {
			// If not blocked, clear failed login records
			$i = 0;
			if ( $ultimakit_ip && ! empty( $log_block_locktime ) ) {
				foreach ( $get_record as $dt ) {
					// Update status of failed login attempts
					$rows_affected = $wpdb->query( $wpdb->prepare( "UPDATE {$this->login_failed} SET status = %s", 0 ) );

					// Delete old login attempt records
					$locktime = $wpdb->query( $wpdb->prepare( "DELETE FROM {$this->login_attempt} WHERE `date` <= %s", $newdate ) );

					++$i;
				}
			}
		}

		// If user is logged in, delete their login attempts
		if ( is_user_logged_in() ) {
			$locktime = $wpdb->query( $wpdb->prepare( "DELETE FROM {$this->login_attempt} WHERE `ip` = %s", $ultimakit_ip ) );
		}
	}


	/**
	 * Store failed user login attempt.
	 */
	public function ultimakit_storefailed_user() {
		global $wpdb;

		// Get login attempts and lockout time options
		$login_failed_option = $this->getModuleSettings( $this->ID, 'login_attempts' );
		$login_locktime      = $this->getModuleSettings( $this->ID, 'lockout_minutes' );

		// Get user's IP address
		$ultimakit_ip = $this->ultimakit_get_the_user_ip();

		// Get current date and time
		$date         = date_i18n( 'Y-m-d H:i:s' );
		$date_formate = get_option( 'date_format' );
		$time_formate = get_option( 'time_format' );
		$wpdate       = date( $date_formate . ' ' . $time_formate, strtotime( $date ) );
		$newdate      = date_i18n( 'Y-m-d H:i:s', $wpdate );

		// Calculate future lockout time
		$currentDate = strtotime( $newdate );
		$futureDate  = $currentDate + ( 60 * $login_locktime );
		$formatDate  = date( 'Y-m-d H:i:s', $futureDate );

		// Check if username and password are provided
		if ( ! empty( $_REQUEST['log'] ) && ! empty( $_REQUEST['pwd'] ) ) {
			// Check if the IP is already logged with a failed attempt
			$check_loged_ip = $wpdb->get_var( $wpdb->prepare( "SELECT count(*) FROM {$this->login_failed} WHERE ip = %s AND status = '1'", $ultimakit_ip ) );

			// If IP is not already logged with a failed attempt, insert new record
			if ( empty( $check_loged_ip ) ) {
				$insert = $wpdb->query(
					$wpdb->prepare(
						" INSERT INTO $this->login_failed
	                    ( username, ip, country,redirect_to,status,locktime,locklimit,date )
	                    VALUES ( %s, %s, %s,%s, %s, %s, %s, %s) ",
						array(
							$_REQUEST['log'], // Username
							$this->ultimakit_get_the_user_ip(), // IP address
							' ', // Country (empty for now)
							$_REQUEST['redirect_to'], // Redirect URL
							1, // Status
							$login_locktime, // Lock time
							$login_failed_option, // Lock limit
							$formatDate, // Future lockout time
						)
					)
				);
			}
		}
	}

	public function ultimakit_login_footer() {
		?>
		<script>
			jQuery(document).ready(function($){
				$("#user_login").attr("disabled", true);
				$("#user_pass").attr("disabled", true);
				$("#rememberme").attr("disabled", true);
				$("#wp-submit").attr("disabled", true);
			});
		</script>
		<?php
	}

	public function ultimakit_login_failed( $username ) {
		global $wpdb;
		$this->ultimakit_check_user_login_attempt();
		add_action( 'login_message', array( $this, 'ultimakit_login_message' ), 100 );
	}

	public function ultimakit_login_message() {
		global $wpdb;
		$wp_config    = $this->getModuleSettings( $this->ID, 'login_attempts' );
		$table_name   = $this->login_attempt;
		$ultimakit_ip = $this->ultimakit_get_the_user_ip();

		$login_failed_option = $this->getModuleSettings( $this->ID, 'login_attempts' );
		$login_status        = $wpdb->get_var( $wpdb->prepare( ' SELECT attempt FROM ' . $table_name . ' WHERE ip = %s', $ultimakit_ip ) );
		if ( $login_status <= $login_failed_option && $login_failed_option - $login_status != 0 ) {
			?>
			<div id="login_error" style="margin-bottom: 10px; padding: 10px; background: #ff000040; color: red;">
				<strong><?php echo esc_html_e( 'Login limits error :' ); ?></strong> <?php echo esc_html_e( 'remaning attempt', 'ultimakit-for-wp' ); ?>
				<?php
				if ( ! empty( $login_failed_option ) ) {
					echo esc_html( $login_failed_option - $login_status ); }
				?>
				of 
				<?php
				if ( ! empty( $login_failed_option ) ) {
					echo esc_html( $login_failed_option ); }
				?>
			</div>
			<?php
		}
	}

	public function ultimakit_check_user_login_attempt() {
		global $wpdb;
		$login_failed_option = $this->getModuleSettings( $this->ID, 'login_attempts' );
		$ultimakit_ip        = $this->ultimakit_get_the_user_ip();
		if ( ! empty( $_REQUEST['log'] ) && ! empty( $_REQUEST['pwd'] ) ) {
			$table_name   = $this->login_attempt;
			$login_status = $wpdb->get_var( $wpdb->prepare( ' SELECT count(*) FROM ' . $table_name . ' WHERE ip = %s', $ultimakit_ip ) );
			if ( empty( $login_status ) ) {
				$insert = $wpdb->query(
					$wpdb->prepare(
						"INSERT INTO $table_name
	                ( attempt, ip, status, date )
	                VALUES ( %s, %s, %s, %s) ",
						array(
							$attempt = 1,
							$ip = $ultimakit_ip,
							$status = 1,
							date_i18n( 'Y-m-d H:i:s' ),
						)
					)
				);
				$last_insert_id = $wpdb->insert_id;
			} else {
				$attempt_count = $wpdb->get_var( $wpdb->prepare( ' SELECT attempt FROM ' . $table_name . ' WHERE ip = %s ORDER BY id DESC', $ultimakit_ip ) );
				$attempt       = $attempt_count + 1;
				$rows_affected = $wpdb->query( $wpdb->prepare( "UPDATE {$table_name} SET attempt = %s, ip = %s", $attempt, $ultimakit_ip ) );
			}
		}
	}

	public function ultimakit_unblock_user() {
		global $wpdb;
		$table_name   = $this->login_attempt;
		$table_failed = $this->login_failed;

		/*
		 * Without these checks any logged-in user could clear their own brute-force lockout
		 * on demand, which defeats the module entirely.
		 */
		check_ajax_referer( 'ultimakit-limit-login-attempts', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Forbidden', 403 );
		}

		$get_request = isset( $_REQUEST['ip'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['ip'] ) ) : '';

		if ( $get_request ) {
			$rows_affected = $wpdb->query(
				$wpdb->prepare(
					"DELETE {$table_name}, {$table_failed} FROM {$table_name} INNER JOIN {$table_failed} 
		  WHERE {$table_name}.ip = {$table_failed}.ip AND {$table_name}.ip = %s",
					$get_request
				)
			);
		}
		wp_die();
	}

	public function settings_admin_login_attempt() {
		$object = new UltimaKit_Helpers();
		global $wpdb;
		$failed_record  = $this->login_failed;
		$total_items    = $wpdb->get_var( "SELECT COUNT(*) FROM {$failed_record} WHERE status = '1' ORDER BY id DESC" );
		$log_block_time = $wpdb->get_results( "SELECT * FROM {$failed_record} WHERE status = '1' ORDER BY id DESC" );
		?>
		<div class="wrap">
			<?php $object->ultimakit_get_header(); ?>
			<div class="container bg-white text-dark p-3 gap-3 mb-3 log-section">
				<?php
				if ( $total_items > 0 ) {
					$items_per_page = 15;
					$page           = isset( $_GET['pages'] ) ? intval( $_GET['pages'] ) : 1;
					$offset         = ( $page - 1 ) * $items_per_page;
					$query          = $wpdb->prepare( "SELECT * FROM {$failed_record} LIMIT %d, %d", $offset, $items_per_page );
					$results        = $wpdb->get_results( $query );
					$date_format    = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
					?>
					<table id="ultimakit_log_list" class="table table-striped table-bordered ultimakit_smtp_log table log_section" style="width:100%">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Username', 'ultimakit-for-wp' ); ?></th>
								<th><?php esc_html_e( 'IP', 'ultimakit-for-wp' ); ?></th>
								<th><?php esc_html_e( 'Date', 'ultimakit-for-wp' ); ?></th>
								<th><?php esc_html_e( 'Lockout Time', 'ultimakit-for-wp' ); ?></th>
								<th></th>
							</tr>
						</thead>
						<tbody>
						<?php foreach ( $results as $result ) { ?>
							<tr class="ultimakit_export_posts block_list item-list">
								<td><?php echo esc_html( $result->username ); ?></td>
								<td><?php echo esc_html( $result->ip ); ?></td>
								<td>
								<?php
										$date_formate = get_option( 'date_format' );
										$time_formate = get_option( 'time_format' );
										echo esc_html( date( $date_formate . ' ' . $time_formate, strtotime( $result->date ) ) );
								?>
										</td>
								<td><?php echo esc_html( $result->locktime ); ?></td>
								<td class="">
									<a href="javascript:void(0);" id="<?php echo esc_attr( $result->id ); ?>" class="ultimakit_user_block_list" data-ip="<?php echo esc_attr( $result->ip ); ?>">
									<span class="icon mr-3 has-text-primary unlock-icon">
										<button class="btn btn-primary" type="button"><?php echo esc_html_e( 'unblock', 'ultimakit-for-wp' ); ?> <svg xmlns="http://www.w3.org/2000/svg" id="Outline" viewBox="0 0 24 24" width="15" height="15" fill="currentColor"><path d="M17,8H7V7a5,5,0,0,1,9.375-2.422,1,1,0,0,0,1.749-.971A7,7,0,0,0,5,7V8.424A5,5,0,0,0,2,13v6a5.006,5.006,0,0,0,5,5H17a5.006,5.006,0,0,0,5-5V13A5.006,5.006,0,0,0,17,8Zm3,11a3,3,0,0,1-3,3H7a3,3,0,0,1-3-3V13a3,3,0,0,1,3-3H17a3,3,0,0,1,3,3Z"/><path d="M12,14a1,1,0,0,0-1,1v2a1,1,0,0,0,2,0V15A1,1,0,0,0,12,14Z"/></svg></button>
									</span> </a>
								</td>
							</tr>
						<?php } ?>
						</tbody>
					</table>
					<?php echo self::pagination_links( esc_html( $page ), esc_html( $total_items ), esc_html( $items_per_page ) ); ?>
					<?php
				} else {
					esc_html_e( 'No results found.', 'ultimakit-for-wp' );
				}
				?>
			</div>
		</div>
		<?php
	}

	/**
	 * Generate pagination links for the log list.
	 */
	private static function pagination_links( $current_page, $total_items, $items_per_page ) {

		$total_pages = ceil( esc_html( $total_items ) / esc_html( $items_per_page ) );
		$output      = '<nav aria-label="navigation ultimakit_log_pagination"><ul class="pagination">';

		for ( $i = 1; $i <= $total_pages; $i++ ) {
			$class   = $i === $current_page ? 'page-link current' : 'page-link';
			$url     = esc_url(
				add_query_arg(
					array(
						'page'  => 'wp-ultimakit-smtp-email-logs',
						'pages' => $i,
					)
				)
			);
			$output .= sprintf( '<li class="page-item"><a class="%s" href="%s">%d</a></li>', esc_html( $class ), esc_url( $url ), esc_html( $i ) );
		}

		$output .= '</ul></nav>';
		return $output;
	}
}
