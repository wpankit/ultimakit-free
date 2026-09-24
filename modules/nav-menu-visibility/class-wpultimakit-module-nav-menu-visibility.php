<?php
/**
 * Class UltimaKit_Module_Nav_Menu_Visibility
 *
 * @since 1.0.0
 * @package    UltimaKit
 */

/**
 * Class UltimaKit_Module_Nav_Menu_Visibility
 *
 * @since 1.0.0
 */
class UltimaKit_Module_Nav_Menu_Visibility extends UltimaKit_Module_Manager {
	/**
	 * @var string
	 */
	protected $ID = 'ultimakit_module_nav_menu_visibility';

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
	protected $category = 'Optimizations';

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
	protected $read_more_link = 'set-navigation-menu-visibility-in-wordpress';

	/**
	 * The settings associated with the module, if any.
	 *
	 * @var array
	 */
	protected $settings;

	/**
	 * Constructs the Hide Admin Bar module instance.
	 *
	 * Initializes the module with default values for properties and prepares
	 * any necessary setup or hooks into WordPress. This may include setting
	 * initial values, registering hooks, or preparing resources needed for
	 * the module to function properly within WordPress.
	 */
	public function __construct() {
		$this->name        = __( 'Navigation Menu Visibility', 'ultimakit-for-wp' );
		$this->description = __( 'This module offers greater control over your navigation menu by enabling visibility controls to be applied to the menu.', 'ultimakit-for-wp' );
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
			add_action( 'wp_nav_menu_item_custom_fields', array( $this, 'ultimakit_custom_menu_item_fields' ), 10, 4 );
			add_action( 'wp_update_nav_menu_item', array( $this, 'ultimakit_save_custom_menu_item_fields' ), 10, 3 );
			add_filter( 'wp_get_nav_menu_items', array( $this, 'ultimakit_filter_menu_items_by_login' ), 10, 3 );
		}
	}

	public function ultimakit_custom_menu_item_fields( $item_id, $item, $depth, $args ) {
		// Get the saved checkbox value
		$is_visible = get_post_meta( $item_id, '_ultimakit_menu_item_visible', true );

		// Output the radio button field
		?>
		<fieldset class="field_ultimakit_menu_role nav_menu_logged_in_out_field description-wide" style="margin: 5px 0;">
			<span class="menu-item-title"><?php esc_html_e( 'Menu Item Visibility For', 'ultimakit-for-wp' ); ?></span><br/>
			<label>
					<input type="radio" class="widefat" name="ultimakit_menu_item_visible[<?php echo esc_attr( $item_id ); ?>]" value="1" <?php checked( '1', $is_visible ); ?> />
					<?php esc_html_e( 'Logged In', 'ultimakit-for-wp' ); ?>
				</label>
				<label>
					<input type="radio" class="widefat" name="ultimakit_menu_item_visible[<?php echo esc_attr( $item_id ); ?>]" value="2" <?php checked( '2', $is_visible ); ?> />
					<?php esc_html_e( 'Logged Out', 'ultimakit-for-wp' ); ?>
				</label>
				<label>
					<input type="radio" class="widefat" name="ultimakit_menu_item_visible[<?php echo esc_attr( $item_id ); ?>]" value="" <?php checked( '', $is_visible ); ?> />
					<?php esc_html_e( 'Everyone', 'ultimakit-for-wp' ); ?>
				</label>
			</fieldset>
		<?php
	}

	public function ultimakit_save_custom_menu_item_fields( $menu_id, $menu_item_db_id, $menu_item_args ) {
		if ( isset( $_POST['ultimakit_menu_item_visible'][ $menu_item_db_id ] ) && in_array( $_POST['ultimakit_menu_item_visible'][ $menu_item_db_id ], array( '1', '2', '' ) ) ) {
			$visibility_option = sanitize_key( $_POST['ultimakit_menu_item_visible'][ $menu_item_db_id ] );
			update_post_meta( $menu_item_db_id, '_ultimakit_menu_item_visible', $visibility_option );
		} else {
			delete_post_meta( $menu_item_db_id, '_ultimakit_menu_item_visible' );
		}
	}

	// Filter menu items based on user login status
	public function ultimakit_filter_menu_items_by_login( $items, $menu, $args ) {
		if ( is_admin() ) {
			return $items; // Return all items in the admin area
		}

		foreach ( $items as $key => $item ) {
			$is_visible = get_post_meta( $item->ID, '_ultimakit_menu_item_visible', true );

			// Remove the item if it's marked as visible only for logged-in users, but the user is not logged in
			if ( $is_visible == '1' && ! is_user_logged_in() ) {
				unset( $items[ $key ] );
			}

			// Remove the item if it's marked as visible only for logged-out users, but the user is logged in
			if ( $is_visible == '2' && is_user_logged_in() ) {
				unset( $items[ $key ] );
			}
		}

		return $items;
	}
}
