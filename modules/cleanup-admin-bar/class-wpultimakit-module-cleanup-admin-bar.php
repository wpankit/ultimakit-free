<?php
/**
 *
 * This module streamlines the WordPress admin bar by offering features to hide, show, and rearrange items for a cleaner interface.
 *
 * @package    UltimaKit
 * @link       https://xwpankit.com
 * @since      1.0.0
 */
class UltimaKit_Module_CleanUp_Admin_Bar extends UltimaKit_Module_Manager {
	/**
	 * Unique identifier for the Cleanup Admin Bar module.
	 *
	 * @var string
	 */
	protected $ID = 'ultimakit_cleanup_admin_bar';

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
	protected $read_more_link = 'cleanup-admin-bar-in-wordpress';

	/**
	 * The settings associated with the module, if any.
	 *
	 * @var array
	 */
	protected $settings;

	/**
	 * Constructs the Cleanup Admin Bar module instance.
	 *
	 * Initializes the module with default values for properties and prepares
	 * any necessary setup or hooks into WordPress. This may include setting
	 * initial values, registering hooks, or preparing resources needed for
	 * the module to function properly within WordPress.
	 */
	public function __construct() {
		$this->name        = __( 'Cleanup Admin Bar', 'ultimakit-for-wp' );
		$this->description = __( 'Remove various elements from the admin bar.', 'ultimakit-for-wp' );
		$this->is_active   = $this->isModuleActive( $this->ID );
		$this->settings    = 'yes';
		$this->initializeModule();
		add_action( 'admin_bar_menu', array( $this, 'wpuk_admin_bar_options' ), 11 );
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
		if ( true === $this->ultimakit_asset_condition() ) {
			add_action( 'admin_enqueue_scripts', array( $this, 'add_scripts' ) );
			add_action( 'admin_footer', array( $this, 'add_modal' ) );
		}
	}

	/**
	 *
	 * This function provides the logic to retrieve, display, or update the settings
	 * that control the appearance and behavior of the WordPress admin bar. It can
	 * involve fetching current settings, rendering an options page in the admin dashboard,
	 * and processing form submissions to update those settings. The function ensures that
	 * any modifications are applied effectively, enhancing the admin bar's usability or
	 * appearance according to the user's preferences.
	 *
	 * @param WP_Admin_Bar $wp_admin_bar Global instance of the WP_Admin_Bar class, used to manipulate the admin bar. This parameter allows the function to modify the admin bar directly, offering a means to customize its contents.
	 *
	 * @return mixed
	 */
	public function wpuk_admin_bar_options( $wp_admin_bar ) {
		if ( ! $this->is_active ) {
			return false;
		}

		// Admin Bar Logo.
		if ( 'on' === $this->getModuleSettings( $this->ID, 'remove_wp_logo_menu' ) ) {
			remove_action( 'admin_bar_menu', 'wp_admin_bar_wp_menu', 10 );
			$wp_admin_bar->remove_menu( 'wp-logo' );
		}

		// Remove Customize Menu.
		if ( 'on' === $this->getModuleSettings( $this->ID, 'remove_customize_menu' ) ) {
			remove_action( 'admin_bar_menu', 'wp_admin_bar_customize_menu', 40 );
			$wp_admin_bar->remove_menu( 'customize' );
		}

		// Remove Updates Menu.
		if ( 'on' === $this->getModuleSettings( $this->ID, 'remove_updates_counter_link' ) ) {
			remove_action( 'admin_bar_menu', 'wp_admin_bar_updates_menu', 50 );
			$wp_admin_bar->remove_menu( 'updates' );
		}

		// Remove Comments Counter Link Menu.
		if ( 'on' === $this->getModuleSettings( $this->ID, 'remove_comments_counter_link' ) ) {
			remove_action( 'admin_bar_menu', 'wp_admin_bar_comments_menu', 60 );
			$wp_admin_bar->remove_menu( 'comments' );
		}

		// Remove New Content Menu.
		if ( 'on' === $this->getModuleSettings( $this->ID, 'remove_new_content_menu' ) ) {
			remove_action( 'admin_bar_menu', 'wp_admin_bar_new_content_menu', 70 );
			$wp_admin_bar->remove_menu( 'new-content' );
		}

		// Remove Howdy.
		if ( 'on' === $this->getModuleSettings( $this->ID, 'remove_howdy' ) ) {
			remove_action( 'admin_bar_menu', 'wp_admin_bar_my_account_item', 7 );
			$current_user = wp_get_current_user();
			$user_id      = get_current_user_id();
			$profile_url  = get_edit_profile_url( $user_id );
			$avatar       = get_avatar( $user_id, 26 );
			// size 26x26 pixels.
			$display_name = $current_user->display_name;
			$class        = 'with-avatar';
			$wp_admin_bar->add_menu(
				array(
					'id'     => 'my-account',
					'parent' => 'top-secondary',
					'title'  => $display_name . $avatar,
					'href'   => $profile_url,
					'meta'   => array(
						'class' => $class,
					),
				)
			);
		}

		// Remove Help Tab.
		if ( 'on' === $this->getModuleSettings( $this->ID, 'remove_help_tab' ) ) {
			if ( is_admin() ) {
				$screen = get_current_screen();
				$screen->remove_help_tabs();
			}
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
		$arguments['title'] = __( 'Cleanup Admin Bar Settings', 'ultimakit-for-wp' );

		$arguments['fields'] = array(
			'remove_wp_logo_menu'          => array(
				'type'  => 'checkbox',
				'label' => __( 'Remove WordPress logo/menu', 'ultimakit-for-wp' ),
				'value' => $this->getModuleSettings( $this->ID, 'remove_wp_logo_menu' ),
			),
			'remove_customize_menu'        => array(
				'type'  => 'checkbox',
				'label' => __( 'Remove customize menu', 'ultimakit-for-wp' ),
				'value' => $this->getModuleSettings( $this->ID, 'remove_customize_menu' ),
			),
			'remove_updates_counter_link'  => array(
				'type'  => 'checkbox',
				'label' => __( 'Remove updates counter/link', 'ultimakit-for-wp' ),
				'value' => $this->getModuleSettings( $this->ID, 'remove_updates_counter_link' ),
			),
			'remove_comments_counter_link' => array(
				'type'  => 'checkbox',
				'label' => __( 'Remove comments counter/link', 'ultimakit-for-wp' ),
				'value' => $this->getModuleSettings( $this->ID, 'remove_comments_counter_link' ),
			),
			'remove_new_content_menu'      => array(
				'type'  => 'checkbox',
				'label' => __( 'Remove new content menu', 'ultimakit-for-wp' ),
				'value' => $this->getModuleSettings( $this->ID, 'remove_new_content_menu' ),
			),
			'remove_howdy'                 => array(
				'type'  => 'checkbox',
				'label' => __( "Remove 'Howdy'", 'ultimakit-for-wp' ),
				'value' => $this->getModuleSettings( $this->ID, 'remove_howdy' ),
			),
			'remove_help_tab'              => array(
				'type'  => 'checkbox',
				'label' => __( 'Remove the Help tab and drawer', 'ultimakit-for-wp' ),
				'value' => $this->getModuleSettings( $this->ID, 'remove_help_tab' ),
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
	}
}
