<?php
/**
 * Class UltimaKit_Module_User_Last_Login_Timestamp
 *
 * @since 1.0.0
 * @package    UltimaKit
 */

/**
 * Class UltimaKit_Module_User_Last_Login_Timestamp
 *
 * @since 1.0.0
 */
class UltimaKit_Module_User_Last_Login_Timestamp extends UltimaKit_Module_Manager {
	/**
	 * @var string
	 */
	protected $ID = 'ultimakit_user_last_login_timestamp';

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
	protected $read_more_link = 'user-last-login-timestamp-in-wordpress';

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
		$this->name        = __( 'User Last Login Timestamp', 'ultimakit-for-wp' );
		$this->description = __( 'Displays the exact date and time a user last logged into your website, helping you track user activity.', 'ultimakit-for-wp' );
		$this->is_active   = $this->isModuleActive( $this->ID );
		$this->settings    = 'no';
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
			add_action( 'wp_login', array( $this, 'ultimakit_login_datetime' ) );
			add_filter( 'manage_users_columns', array( $this, 'ultimakit_add_last_login_status_column' ) );
			add_filter( 'manage_users_custom_column', array( $this, 'ultimakit_last_login_info' ), 10, 4 );
		}
	}


	/**
	 * Log date time when a user last logged in successfully
	 */
	public function ultimakit_login_datetime( $user_login ) {

		$user = get_user_by( 'login', $user_login ); // by username
		update_user_meta( $user->ID, 'ultimakit_user_last_login_status', time() );
	}

	/**
	 * Add Last Login column to users list table
	 */
	public function ultimakit_add_last_login_status_column( $columns ) {

		$columns['ultimakit_last_login'] = __( 'Last Login', 'ultimakit-for-wp' );
		return $columns;
	}

	/**
	 * Display user last login info in the last login column
	 */
	public function ultimakit_last_login_info( $output, $column_name, $user_id ) {

		if ( 'ultimakit_last_login' === $column_name ) {

			if ( ! empty( get_user_meta( $user_id, 'ultimakit_user_last_login_status', true ) ) ) {

				$ultimakit_last_login = (int) get_user_meta( $user_id, 'ultimakit_user_last_login_status', true );

				if ( function_exists( 'wp_date' ) ) {
					$date_formate = get_option( 'date_format' );
					$time_formate = get_option( 'time_format' );
					$output       = date( $date_formate . ' ' . $time_formate, $ultimakit_last_login );
				} else {
					$output = date_i18n( 'M j, Y H:i A', $ultimakit_last_login );
				}
			} else {

							$output = __( 'No data yet', 'ultimakit-for-wp' );

			}
		}

		return $output;
	}
}
