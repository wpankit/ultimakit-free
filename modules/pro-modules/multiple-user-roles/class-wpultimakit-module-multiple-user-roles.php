<?php
/**
 * Class UltimaKit_Module_Multiple_User_Roles
 *
 * @since 1.0.0
 * @package    UltimaKit
 */

/**
 * Class UltimaKit_Module_Multiple_User_Roles
 *
 * This class provides methods to assign multiple roles to a single user,
 * allowing for more granular permission control and role management.
 *
 * @since 1.0.0
 */
class UltimaKit_Module_Multiple_User_Roles extends UltimaKit_Module_Manager {
	/**
	 * @var string
	 */
	protected $ID = 'ultimakit_multiple_user_roles';

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
	protected $category = 'User Management';

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
	protected $read_more_link = 'assign-multiple-user-roles-in-wordpress';

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
		$this->name        = __( 'Multiple User Roles', 'ultimakit-for-wp' );
		$this->description = __( 'Allow assigning multiple roles to a single user.', 'ultimakit-for-wp' );
		$this->is_active   = $this->isModuleActive( $this->ID );
		$this->settings    = 'yes';
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
			add_action( 'admin_enqueue_scripts', array( $this, 'add_scripts' ) );
			add_action( 'admin_footer', array( $this, 'add_modal' ) );
			add_action( 'show_user_profile', array( $this, 'add_multiple_roles_field' ) );
			add_action( 'edit_user_profile', array( $this, 'add_multiple_roles_field' ) );
			add_action( 'personal_options_update', array( $this, 'save_multiple_roles' ) );
			add_action( 'edit_user_profile_update', array( $this, 'save_multiple_roles' ) );
			add_filter( 'user_has_cap', array( $this, 'check_multiple_role_capabilities' ), 10, 4 );
			add_filter( 'manage_users_columns', array( $this, 'modify_users_columns' ) );
			add_filter( 'manage_users_custom_column', array( $this, 'display_user_roles_column' ), 10, 3 );
			add_action( 'wp_ajax_ultimakit_get_user_roles', array( $this, 'get_user_roles_ajax' ) );
			add_action( 'wp_ajax_ultimakit_save_user_roles', array( $this, 'save_user_roles_ajax' ) );
		}
	}

	/**
	 * Add multiple roles field to user profile
	 *
	 * @param WP_User $user User object
	 */
	public function add_multiple_roles_field( $user ) {
		// Granting roles is a promotion, so it needs promote_users on this specific user.
		if ( ! $this->ultimakit_can_assign_roles( $user->ID ) ) {
			return;
		}

		$enabled = $this->getModuleSettings( $this->ID, 'enable_multiple_roles' );
		if ( 'on' !== $enabled ) {
			return;
		}

		$user_roles      = $this->get_user_multiple_roles( $user->ID );
		$available_roles = $this->ultimakit_get_assignable_roles();

		wp_nonce_field( 'ultimakit_multiple_roles_' . $user->ID, 'ultimakit_multiple_roles_nonce' );

		?>
		<h3><?php esc_html_e( 'Multiple User Roles', 'ultimakit-for-wp' ); ?></h3>
		<table class="form-table">
			<tr>
				<th scope="row"><?php esc_html_e( 'Additional Roles', 'ultimakit-for-wp' ); ?></th>
				<td>
					<fieldset>
						<?php foreach ( $available_roles as $role_key => $role_name ) : ?>
							<?php if ( $role_key !== $user->roles[0] ) : // Don't show primary role ?>
								<label>
									<input type="checkbox" 
											name="ultimakit_additional_roles[]" 
											value="<?php echo esc_attr( $role_key ); ?>"
											<?php checked( in_array( $role_key, $user_roles ) ); ?> />
									<?php echo esc_html( $role_name ); ?>
								</label><br>
							<?php endif; ?>
						<?php endforeach; ?>
					</fieldset>
					<p class="description">
						<?php esc_html_e( 'Select additional roles for this user. The primary role is managed separately.', 'ultimakit-for-wp' ); ?>
					</p>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Save multiple roles for a user
	 *
	 * @param int $user_id User ID
	 */
	public function save_multiple_roles( $user_id ) {
		// Check permissions
		if ( ! $this->ultimakit_can_assign_roles( $user_id ) ) {
			return;
		}

		if ( ! isset( $_POST['ultimakit_multiple_roles_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ultimakit_multiple_roles_nonce'] ) ), 'ultimakit_multiple_roles_' . $user_id ) ) {
			return;
		}

		$enabled = $this->getModuleSettings( $this->ID, 'enable_multiple_roles' );
		if ( 'on' !== $enabled ) {
			return;
		}

		// Get selected additional roles
		$additional_roles = isset( $_POST['ultimakit_additional_roles'] ) ? array_map( 'sanitize_text_field', (array) wp_unslash( $_POST['ultimakit_additional_roles'] ) ) : array();

		/*
		 * Validate against the roles the current user is actually allowed to hand out, not
		 * every registered role. get_editable_roles() honours the editable_roles filter that
		 * WooCommerce and others use to keep, say, a Shop Manager from granting Administrator.
		 */
		$available_roles = array_keys( $this->ultimakit_get_assignable_roles() );
		$valid_roles     = array_values( array_intersect( $additional_roles, $available_roles ) );

		// Save to user meta
		update_user_meta( $user_id, '_ultimakit_additional_roles', $valid_roles );
	}

	/**
	 * Roles the current user may grant to others.
	 *
	 * @return array Role key => role name.
	 */
	protected function ultimakit_get_assignable_roles() {
		if ( ! function_exists( 'get_editable_roles' ) ) {
			require_once ABSPATH . 'wp-admin/includes/user.php';
		}

		$roles = array();
		foreach ( get_editable_roles() as $role_key => $role ) {
			$roles[ $role_key ] = isset( $role['name'] ) ? $role['name'] : $role_key;
		}

		return $roles;
	}

	/**
	 * Whether the current user may change the additional roles of a given user.
	 *
	 * Self-assignment is refused: WordPress core does not let anyone change their own role,
	 * and allowing it here turned any edit_users holder into an Administrator.
	 *
	 * @param int $user_id Target user ID.
	 * @return bool
	 */
	protected function ultimakit_can_assign_roles( $user_id ) {
		$user_id = (int) $user_id;

		if ( ! $user_id || $user_id === get_current_user_id() ) {
			return false;
		}

		return current_user_can( 'promote_users' ) && current_user_can( 'edit_user', $user_id );
	}

	/**
	 * Get multiple roles for a user
	 *
	 * @param int $user_id User ID
	 * @return array Array of role keys
	 */
	public function get_user_multiple_roles( $user_id ) {
		$roles = get_user_meta( $user_id, '_ultimakit_additional_roles', true );
		return is_array( $roles ) ? $roles : array();
	}

	/**
	 * Check capabilities based on multiple roles
	 *
	 * @param array $allcaps All capabilities for the user
	 * @param array $caps Required capabilities
	 * @param array $args Arguments
	 * @param WP_User $user User object
	 * @return array Modified capabilities
	 */
	public function check_multiple_role_capabilities( $allcaps, $caps, $args, $user ) {
		$enabled = $this->getModuleSettings( $this->ID, 'enable_multiple_roles' );
		if ( 'on' !== $enabled ) {
			return $allcaps;
		}

		$additional_roles = $this->get_user_multiple_roles( $user->ID );

		foreach ( $additional_roles as $role_key ) {
			$role = get_role( $role_key );
			if ( $role ) {
				$allcaps = array_merge( $allcaps, $role->capabilities );
			}
		}

		return $allcaps;
	}

	/**
	 * Modify users list columns to show multiple roles
	 *
	 * @param array $columns Existing columns
	 * @return array Modified columns
	 */
	public function modify_users_columns( $columns ) {
		$enabled = $this->getModuleSettings( $this->ID, 'enable_multiple_roles' );
		if ( 'on' !== $enabled ) {
			return $columns;
		}

		// Replace the default role column with our custom one
		if ( isset( $columns['role'] ) ) {
			$columns['role'] = __( 'User Roles', 'ultimakit-for-wp' );
		}

		return $columns;
	}

	/**
	 * Display user roles in the users list column
	 *
	 * @param string $value Column value
	 * @param string $column_name Column name
	 * @param int $user_id User ID
	 * @return string Column content
	 */
	public function display_user_roles_column( $value, $column_name, $user_id ) {
		// Only process if this is the role column
		if ( $column_name !== 'role' ) {
			return $value;
		}

		$enabled = $this->getModuleSettings( $this->ID, 'enable_multiple_roles' );
		if ( 'on' !== $enabled ) {
			return $value;
		}

		$user = get_user_by( 'ID', $user_id );
		if ( ! $user ) {
			return $value;
		}

		$all_roles = array();

		// Add primary role
		if ( ! empty( $user->roles ) ) {
			$primary_role = $user->roles[0];
			$role_names   = wp_roles()->get_names();
			$all_roles[]  = '<strong>' . esc_html( $role_names[ $primary_role ] ?? $primary_role ) . '</strong>';
		}

		// Add additional roles
		$additional_roles = $this->get_user_multiple_roles( $user_id );
		if ( ! empty( $additional_roles ) ) {
			$role_names = wp_roles()->get_names();
			foreach ( $additional_roles as $role_key ) {
				$role_name   = $role_names[ $role_key ] ?? $role_key;
				$all_roles[] = '<span class="ultimakit-additional-role">' . esc_html( $role_name ) . '</span>';
			}
		}

		if ( empty( $all_roles ) ) {
			return __( 'No roles assigned', 'ultimakit-for-wp' );
		}

		return implode( '<br>', $all_roles );
	}

	/**
	 * AJAX handler to get user roles
	 */
	public function get_user_roles_ajax() {
		// Verify nonce
		if ( ! wp_verify_nonce( $_POST['nonce'], 'ultimakit_multiple_user_roles' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'ultimakit-for-wp' ) ) );
		}

		// Check permissions
		if ( ! current_user_can( 'edit_users' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'ultimakit-for-wp' ) ) );
		}

		$user_id = intval( $_POST['user_id'] );

		if ( ! $this->ultimakit_can_assign_roles( $user_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'ultimakit-for-wp' ) ) );
		}

		$user = get_user_by( 'ID', $user_id );

		if ( ! $user ) {
			wp_send_json_error( array( 'message' => __( 'User not found.', 'ultimakit-for-wp' ) ) );
		}

		$additional_roles = $this->get_user_multiple_roles( $user_id );
		$available_roles  = $this->ultimakit_get_assignable_roles();

		wp_send_json_success(
			array(
				'user_roles'      => $additional_roles,
				'available_roles' => $available_roles,
				'primary_role'    => $user->roles[0] ?? '',
			)
		);
	}

	/**
	 * AJAX handler to save user roles
	 */
	public function save_user_roles_ajax() {
		// Verify nonce
		if ( ! wp_verify_nonce( $_POST['nonce'], 'ultimakit_multiple_user_roles' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'ultimakit-for-wp' ) ) );
		}

		// Check permissions
		if ( ! current_user_can( 'edit_users' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'ultimakit-for-wp' ) ) );
		}

		$user_id = intval( $_POST['user_id'] );

		if ( ! $this->ultimakit_can_assign_roles( $user_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'ultimakit-for-wp' ) ) );
		}

		$roles = isset( $_POST['roles'] ) ? array_map( 'sanitize_text_field', (array) wp_unslash( $_POST['roles'] ) ) : array();

		// Only roles the current user is allowed to grant.
		$available_roles = array_keys( $this->ultimakit_get_assignable_roles() );
		$valid_roles     = array_values( array_intersect( $roles, $available_roles ) );

		update_user_meta( $user_id, '_ultimakit_additional_roles', $valid_roles );

		wp_send_json_success( array( 'message' => __( 'User roles updated successfully.', 'ultimakit-for-wp' ) ) );
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
		$arguments['title'] = __( 'UltimaKit - Multiple User Roles', 'ultimakit-for-wp' );

		$arguments['fields'] = array(
			'enable_multiple_roles' => array(
				'type'  => 'checkbox',
				'label' => __( 'Enable Multiple User Roles', 'ultimakit-for-wp' ),
				'value' => $this->getModuleSettings( $this->ID, 'enable_multiple_roles' ),
				'desc'  => __( 'Allow users to have multiple roles with combined capabilities', 'ultimakit-for-wp' ),
			),
			'role_management'       => array(
				'type'  => 'custom',
				'label' => __( 'Role Management', 'ultimakit-for-wp' ),
				'value' => $this->getModuleSettings( $this->ID, 'role_management' ),
				'desc'  => __( 'Configure how multiple roles are managed and applied', 'ultimakit-for-wp' ),
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
		wp_enqueue_style(
			'ultimakit-module-style-' . $this->ID,
			plugins_url( '/module-style.css', __FILE__ ),
			array(),
			filemtime( plugin_dir_path( __FILE__ ) . 'module-style.css' )
		);

		wp_enqueue_script(
			'ultimakit-module-script-' . $this->ID,
			plugins_url( '/module-script.js', __FILE__ ),
			array( 'jquery' ),
			ULTIMAKIT_FOR_WP_VERSION,
			true
		);

		wp_localize_script(
			'ultimakit-module-script-' . $this->ID,
			'ultimakit_multiple_user_roles',
			array(
				'ajax_url'   => admin_url( 'admin-ajax.php' ),
				'ajax_nonce' => wp_create_nonce( 'ultimakit_multiple_user_roles' ),
			)
		);
	}
}
