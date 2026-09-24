<?php

/**
 * Lock Admin Email Module
 *
 * @link       https://xwpankit.com
 * @since      1.0.0
 *
 * @package    UltimaKit
 * @subpackage UltimaKit/modules
 */

/**
 * Lock Admin Email Module Class
 *
 * This class handles the functionality to prevent changes to the admin email address.
 *
 * @since      1.0.0
 * @package    UltimaKit
 * @subpackage UltimaKit/modules
 * @author     Ankit Panchal <developer@wpultimakit.com>
 */
class UltimaKit_Module_Lock_admin_email extends UltimaKit_Module_Manager {

	/**
	 * The unique identifier of the module.
	 *
	 * @var string
	 */
	protected $ID = 'ultimakit_module_lock_admin_email';

	/**
	 * The name of the module.
	 *
	 * @var string
	 */
	protected $name;

	/**
	 * The description of the module.
	 *
	 * @var string
	 */
	protected $description;

	/**
	 * The plan type of the module (free/pro).
	 *
	 * @var string
	 */
	protected $plan = 'free';

	/**
	 * The category of the module.
	 *
	 * @var string
	 */
	protected $category = 'Security';

	/**
	 * The type of the module.
	 *
	 * @var string
	 */
	protected $type = 'WordPress';

	/**
	 * Whether the module is active.
	 *
	 * @var bool
	 */
	protected $is_active;

	/**
	 * The read more link for the module.
	 *
	 * @var string
	 */
	protected $read_more_link = 'lock-admin-email-in-wordpress';

	/**
	 * The settings associated with the module, if any.
	 *
	 * @var array
	 */
	protected $settings;

	/**
	 * Initializes the module with default values for properties and prepares
	 * any necessary setup or hooks into WordPress. This may include setting
	 * initial values, registering hooks, or preparing resources needed for
	 * the module to function properly within WordPress.
	 */
	public function __construct() {
		$this->name        = __( 'Lock Admin Email', 'ultimakit-for-wp' );
		$this->description = __( 'Prevent changes to the admin email address.', 'ultimakit-for-wp' );
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
			// Block admin email changes at multiple levels
			add_filter( 'pre_update_option_admin_email', array( $this, 'ultimakit_block_admin_email_update' ), 1, 2 );
			add_filter( 'pre_update_option_new_admin_email', array( $this, 'ultimakit_block_admin_email_update' ), 1, 2 );
			
			// Block through form submissions
			add_action( 'admin_init', array( $this, 'ultimakit_block_options_form' ), 1 );
			add_action( 'admin_init', array( $this, 'ultimakit_block_direct_updates' ), 1 );
			
			// Add admin notice when someone tries to change the email
			add_action( 'admin_notices', array( $this, 'ultimakit_show_admin_notice' ) );
			
			// Remove the admin email field from the General Settings page
			add_action( 'admin_head', array( $this, 'ultimakit_hide_admin_email_field' ) );
		}
	}



	/**
	 * Block admin email updates through the update_option hook
	 *
	 * @param mixed $new_value The new value being set.
	 * @param mixed $old_value The old value.
	 * @return mixed The value to actually set.
	 */
	public function ultimakit_block_admin_email_update( $new_value, $old_value ) {
		// Set a flag to show notice
		set_transient( 'ultimakit_admin_email_blocked', true, 60 );
		
		// Always return the old value to prevent changes
		return $old_value;
	}

	/**
	 * Block admin email changes through the options.php form submission
	 */
	public function ultimakit_block_options_form() {
		if ( isset( $_POST['option_page'] ) && $_POST['option_page'] === 'general' ) {
			if ( isset( $_POST['admin_email'] ) ) {
				// Remove the admin_email from POST data to prevent it from being saved
				unset( $_POST['admin_email'] );
				
				// Set a flag to show notice
				set_transient( 'ultimakit_admin_email_blocked', true, 60 );
			}
		}
	}

	/**
	 * Block direct admin email updates through various methods
	 */
	public function ultimakit_block_direct_updates() {
		// Block direct POST requests to update admin email
		if ( isset( $_POST['admin_email'] ) || isset( $_POST['new_admin_email'] ) ) {
			// Set a flag to show notice
			set_transient( 'ultimakit_admin_email_blocked', true, 60 );
			
			// Remove the email fields from POST data
			unset( $_POST['admin_email'] );
			unset( $_POST['new_admin_email'] );
		}
		
		// Block AJAX requests that might update admin email
		if ( wp_doing_ajax() && ( isset( $_POST['admin_email'] ) || isset( $_POST['new_admin_email'] ) ) ) {
			wp_send_json_error( 'Admin email changes are blocked by the Lock Admin Email module.' );
		}
	}

	/**
	 * Show admin notice when someone tries to change the email
	 */
	public function ultimakit_show_admin_notice() {
		// Show notice if email change was blocked
		if ( isset( $_GET['ultimakit_email_blocked'] ) && $_GET['ultimakit_email_blocked'] === '1' ) {
			?>
			<div class="notice notice-warning is-dismissible">
				<p>
					<strong><?php _e( 'Admin Email Change Blocked:', 'ultimakit-for-wp' ); ?></strong>
					<?php _e( 'The admin email address cannot be changed while the Lock Admin Email module is active.', 'ultimakit-for-wp' ); ?>
				</p>
			</div>
			<?php
		}
		
		// Show notice if transient is set
		if ( get_transient( 'ultimakit_admin_email_blocked' ) ) {
			delete_transient( 'ultimakit_admin_email_blocked' );
			?>
			<div class="notice notice-warning is-dismissible">
				<p>
					<strong><?php _e( 'Admin Email Change Blocked:', 'ultimakit-for-wp' ); ?></strong>
					<?php _e( 'The admin email address cannot be changed while the Lock Admin Email module is active.', 'ultimakit-for-wp' ); ?>
				</p>
			</div>
			<?php
		}
	}

	/**
	 * Hide the admin email field from the General Settings page
	 */
	public function ultimakit_hide_admin_email_field() {
		// Only hide on the General Settings page
		$screen = get_current_screen();
		if ( $screen && $screen->id === 'options-general' ) {
			?>
			<script type="text/javascript">
				jQuery(document).ready(function($) {
					// Hide the admin email field using multiple selectors for compatibility
					$('label[for="admin_email"], input[name="admin_email"]').closest('tr').hide();
					$('th').each(function() {
						var text = $(this).text().toLowerCase();
						if (text.indexOf('e-mail address') !== -1 || text.indexOf('email address') !== -1) {
							$(this).closest('tr').hide();
						}
					});
				});
			</script>
			<?php
		}
	}
} 