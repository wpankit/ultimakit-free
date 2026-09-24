<?php
/**
 * Class UltimaKit_Module_Admin_UI
 *
 * @since 1.0.0
 * @package    UltimaKit
 */

/**
 * Class UltimaKit_Module_Admin_UI
 *
 * This class provides methods to control the display of dashboard widgets based on user preferences.
 * It allows users to selectively hide certain dashboard widgets to streamline their WordPress dashboard
 * experience and improve usability.
 *
 * @since 1.0.0
 */
class UltimaKit_Module_Admin_UI extends UltimaKit_Module_Manager {
	/**
	 * Unique identifier for the Admin UI module.
	 *
	 * @var string
	 */
	protected $ID = 'ultimakit_module_admin_ui';

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
	protected $category = 'Admin Interface';

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
	public $read_more_link = 'admin-ui-in-wordpress';

	/**
	 * The settings associated with the module, if any.
	 *
	 * @var array
	 */
	protected $settings;

	/**
	 * Constructs the Admin UI module instance.
	 *
	 * Initializes the module with default values for properties and prepares
	 * any necessary setup or hooks into WordPress. This may include setting
	 * initial values, registering hooks, or preparing resources needed for
	 * the module to function properly within WordPress.
	 */
	public function __construct() {
		$this->name        = __( 'Admin UI', 'ultimakit-for-wp' );
		$this->description = __( 'Easily customize the WordPress admin interface.', 'ultimakit-for-wp' );
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
			add_action( 'admin_enqueue_scripts', array( $this, 'add_scripts' ) );
			add_action( 'admin_menu', array( $this, 'ultimakit_admin_ui' ) );

			add_action( 'admin_head', array( $this, 'ultimakit_add_custom_admin_styles' ) );
			add_filter( 'admin_bar_menu', array( $this, 'ultimakit_remove_admin_bar_item' ), 999 );
		}
	}

	public function ultimakit_admin_ui() {
		add_submenu_page(
			'wp-ultimakit-dashboard', // Parent slug
			__( 'Admin UI', 'ultimakit-for-wp' ), // Page title
			__( 'Admin UI', 'ultimakit-for-wp' ), // Menu title
			'manage_options', // Capability
			'wp-ultimakit-customise-admin-ui', // Menu slug
			array( $this, 'settings_admin_ui' ) // Function to display the page content
		);
	}

	public function settings_admin_ui() {
		$object = new UltimaKit_Helpers();
		?>
		<div class="wrap">
			<?php $object->ultimakit_get_header(); ?>
			<div class="container bg-white text-dark p-3 mb-3">
				<!-- Nav tabs -->
				<ul class="nav nav-tabs" id="wpukTabs" role="tablist">
					<li class="nav-item" role="presentation">
						<a class="nav-link active" id="settings-tab" data-bs-toggle="tab" href="#admin-ui-settings" role="tab" aria-controls="admin-ui-settings" aria-selected="true"><?php echo esc_html_e( 'Admin UI Settings', 'ultimakit-for-wp' ); ?></a>
					</li>
					
				</ul>

				<!-- Tab panes -->
				<div class="tab-content" id="wpukTabsContent">
					<div class="tab-pane fade show active" id="admin-ui-settings" role="tabpanel" aria-labelledby="settings-tab">
						<!-- Your modules content here -->
						<div class="row">
							<?php
								$arguments          = array();
								$arguments['ID']    = $this->ID;
								$arguments['title'] = __( 'Admin UI Settings', 'ultimakit-for-wp' );

								$arguments['fields'] = array(
									'body_color'           => array(
										'type'  => 'color',
										'label' => __( 'Body Color', 'ultimakit-for-wp' ),
										'value' => $this->getModuleSettings( $this->ID, 'body_color' ),
									),
									'sidebar_color'        => array(
										'type'  => 'color',
										'label' => __( 'Sidebar Color', 'ultimakit-for-wp' ),
										'value' => $this->getModuleSettings( $this->ID, 'sidebar_color' ),
									),
									'adminbar_color'       => array(
										'type'  => 'color',
										'label' => __( 'Admin Bar Color', 'ultimakit-for-wp' ),
										'value' => $this->getModuleSettings( $this->ID, 'adminbar_color' ),
									),
									'sidebar_txt_color'    => array(
										'type'  => 'color',
										'label' => __( 'Sidebar Text Color', 'ultimakit-for-wp' ),
										'value' => $this->getModuleSettings( $this->ID, 'sidebar_txt_color' ),
									),
									'adminbar_txt_color'   => array(
										'type'  => 'color',
										'label' => __( 'Admin Bar Text Color', 'ultimakit-for-wp' ),
										'value' => $this->getModuleSettings( $this->ID, 'adminbar_txt_color' ),
									),
									'sel_menu_item_color'  => array(
										'type'  => 'color',
										'label' => __( 'Selected Menu Item Color', 'ultimakit-for-wp' ),
										'value' => $this->getModuleSettings( $this->ID, 'sel_menu_item_color' ),
									),
									'adminbar_icons_color' => array(
										'type'  => 'color',
										'label' => __( 'Admin Bar Icons Color', 'ultimakit-for-wp' ),
										'value' => $this->getModuleSettings( $this->ID, 'adminbar_icons_color' ),
									),
									'sel_menu_txt_color'   => array(
										'type'  => 'color',
										'label' => __( 'Selected Menu Text Color', 'ultimakit-for-wp' ),
										'value' => $this->getModuleSettings( $this->ID, 'sel_menu_txt_color' ),
									),
									'hover_menu_item_color' => array(
										'type'  => 'color',
										'label' => __( 'Hovered Menu Item Color', 'ultimakit-for-wp' ),
										'value' => $this->getModuleSettings( $this->ID, 'hover_menu_item_color' ),
									),
									'hovered_menu_txt_color' => array(
										'type'  => 'color',
										'label' => __( 'Hovered Menu Text Color', 'ultimakit-for-wp' ),
										'value' => $this->getModuleSettings( $this->ID, 'hovered_menu_txt_color' ),
									),
									'sidebar_icon_color'   => array(
										'type'  => 'color',
										'label' => __( 'Sidebar Icons Color', 'ultimakit-for-wp' ),
										'value' => $this->getModuleSettings( $this->ID, 'sidebar_icon_color' ),
									),
									'sidebar_width'        => array(
										'type'  => 'text',
										'label' => __( 'Sidebar Width', 'ultimakit-for-wp' ),
										'value' => $this->getModuleSettings( $this->ID, 'sidebar_width' ),
										'desc'  => __( 'Default width is 160px', 'ultimakit-for-wp' ),
									),
									'hide_adminbar_icons'  => array(
										'type'  => 'switch',
										'label' => __( 'Hide Admin Bar Icons', 'ultimakit-for-wp' ),
										'value' => $this->getModuleSettings( $this->ID, 'hide_adminbar_icons' ),
									),
									'hide_comments_item'   => array(
										'type'  => 'switch',
										'label' => __( 'Hide Comments Item', 'ultimakit-for-wp' ),
										'value' => $this->getModuleSettings( $this->ID, 'hide_comments_item' ),
									),
									'hide_new_item'        => array(
										'type'  => 'switch',
										'label' => __( 'Hide New Item', 'ultimakit-for-wp' ),
										'value' => $this->getModuleSettings( $this->ID, 'hide_new_item' ),
									),
									'hide_sidebar_icons'   => array(
										'type'  => 'switch',
										'label' => __( 'Hide Sidebar Icons', 'ultimakit-for-wp' ),
										'value' => $this->getModuleSettings( $this->ID, 'hide_sidebar_icons' ),
									),
									'hide_customize_item'  => array(
										'type'  => 'switch',
										'label' => __( 'Hide Customize Item', 'ultimakit-for-wp' ),
										'value' => $this->getModuleSettings( $this->ID, 'hide_customize_item' ),
									),
									'hide_updates_item'    => array(
										'type'  => 'switch',
										'label' => __( 'Hide Updates Item', 'ultimakit-for-wp' ),
										'value' => $this->getModuleSettings( $this->ID, 'hide_updates_item' ),
									),
								);

								$this->ultimakit_generate_form( $arguments );
								?>
						</div>
					</div>

				</div>
			</div>
		</div>
		<?php
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

		wp_enqueue_style( 'wp-color-picker' );

		wp_enqueue_script(
			'ultimakit-module-script-' . $this->ID,
			plugins_url( '/module-script.js', __FILE__ ),
			array( 'jquery', 'wp-color-picker' ),
			ULTIMAKIT_FOR_WP_VERSION,
			true
		);
	}

	public function ultimakit_add_custom_admin_styles() {

		$sidebar_width = absint( $this->getModuleSettings( $this->ID, 'sidebar_width' ) ) ?: 160;

		$bodyColor = $this->getModuleSettings( $this->ID, 'body_color' ) ?: '';

		$color = $this->getModuleSettings( $this->ID, 'sidebar_color' ) ?: '';

		$fontColor = esc_html( $this->getModuleSettings( $this->ID, 'sidebar_txt_color' ) ) ?: '';

		$adminBarColor = $this->getModuleSettings( $this->ID, 'adminbar_color' ) ?: '';

		$adminBarIconsColor = $this->getModuleSettings( $this->ID, 'adminbar_icons_color' ) ?: '';

		$adminBarTextColor = $this->getModuleSettings( $this->ID, 'adminbar_txt_color' ) ?: '';

		$adminSideBarIconColor = esc_html( $this->getModuleSettings( $this->ID, 'sidebar_icon_color' ) ) ?: '';

		$selectedMenuColor = $this->getModuleSettings( $this->ID, 'sel_menu_item_color' ) ?: '';

		$selectedMenuTextColor = $this->getModuleSettings( $this->ID, 'sel_menu_txt_color' ) ?: '';

		$hoverdMenuColor = $this->getModuleSettings( $this->ID, 'hover_menu_item_color' ) ?: '';

		$hoverdMenuTextColor = $this->getModuleSettings( $this->ID, 'hovered_menu_txt_color' ) ?: '';

		$hideIcons = 'on' === $this->getModuleSettings( $this->ID, 'hide_adminbar_icons' ) ? 'none' : 'block';

		$hideAdminBarIcons = 'on' === $this->getModuleSettings( $this->ID, 'hide_sidebar_icons' ) ? 'none' : 'block';

		?>
		<style id="ultimakit-admin-ui-css">
			body.ultimakit-base.folded #adminmenu .wp-menu-image {
				display: block !important;
			}
			body {
				background: 
				<?php
				if ( ! empty( $bodyColor ) ) {
					echo esc_html( $bodyColor );
				}
				?>
				!important;
			}
			#adminmenu,
			#adminmenu .wp-submenu,
			#adminmenuback,
			#adminmenuwrap {
				background: 
				<?php
				if ( ! empty( $color ) ) {
					echo esc_html( $color );
				}
				?>
				!important;
			}
			#adminmenu a,
			#adminmenu .wp-submenu a {
				color: 
				<?php
				if ( ! empty( $fontColor ) ) {
					echo esc_html( $fontColor );
				}
				?>
				!important;
			}
			#adminmenu a:hover,
			#adminmenu .wp-submenu a:hover,
			#adminmenu li.current a.menu-top:hover {
				color: <?php echo esc_html( $hoverdMenuTextColor ); ?> !important;
			}
			#wpadminbar {
				background-color: 
				<?php
				if ( ! empty( $adminBarColor ) ) {
					echo esc_html( $adminBarColor );
				}
				?>
				!important;
			}
			#wpadminbar .ab-item,
			#wpadminbar a.ab-item,
			#wpadminbar>#wp-toolbar span.ab-label,
			#wpadminbar>#wp-toolbar span.noticon {
				color: <?php echo esc_html( $adminBarTextColor ); ?> !important;
			}
			#adminmenu li.current a.menu-top,
			#adminmenu .wp-has-current-submenu a.wp-has-current-submenu,
			.wp-menu-arrow,
			#adminmenu a.wp-menu-arrow:focus,
			#adminmenu a.wp-menu-arrow:hover,
			#adminmenu a:focus .wp-menu-arrow,
			.folded #adminmenu .wp-has-current-submenu .wp-menu-arrow,
			.folded #adminmenu a.wp-has-current-submenu .wp-menu-arrow {
				background: 
				<?php
				if ( ! empty( $selectedMenuColor ) ) {
					echo esc_html( $selectedMenuColor );
				}
				?>
				!important;
			}
			#adminmenu li.current a.menu-top,
			#adminmenu .wp-has-current-submenu a.wp-has-current-submenu,
			.wp-menu-arrow,
			#adminmenu a.wp-menu-arrow:focus,
			#adminmenu a.wp-menu-arrow:hover,
			#adminmenu a:focus .wp-menu-arrow,
			.folded #adminmenu .wp-has-current-submenu .wp-menu-arrow,
			.folded #adminmenu a.wp-has-current-submenu .wp-menu-arrow {
				color: <?php echo esc_html( $selectedMenuTextColor ); ?> !important;
			}
			#adminmenu li.current a.menu-top:hover,
			#adminmenu li.menu-top:hover,
			#adminmenu li.opensub>a.menu-top,
			#adminmenu li>a.menu-top:focus {
				background: <?php echo esc_html( $hoverdMenuColor ); ?> !important;
			}
			#adminmenu .wp-menu-image {
				display: <?php echo esc_html( $hideAdminBarIcons ); ?>;
			}
			#adminmenu a.menu-top {
				padding-left: <?php echo $hideIcons == 'none' ? '0' : ''; ?> !important;
			}
			#adminmenu div.wp-menu-name {
				padding: <?php echo $hideAdminBarIcons == 'none' ? '8px' : ''; ?> !important;
			}
			#adminmenu li.menu-top {
				margin-bottom: <?php echo $hideIcons == 'none' ? '0' : ''; ?> !important;
			}
			#wpadminbar .ab-top-menu .ab-icon,
			#wpadminbar .ab-top-menu .ab-item::before {
				display: <?php echo esc_html( $hideIcons ); ?>;
			}
			#wpadminbar .ab-icon,
			#wpadminbar .ab-item::before {
				display: <?php echo esc_html( $hideAdminBarIcons ); ?>;
			}
			.ultimakit-base #adminmenuwrap {
				margin-top: auto !important;
			}
			#wpadminbar .ab-icon:before,
			#wp-admin-bar-site-name>.ab-item:before {
				color: <?php echo esc_html( $adminBarIconsColor ); ?> !important;
			}
			#adminmenu div.wp-menu-image:before {
				color: <?php echo esc_html( $adminSideBarIconColor ); ?> !important;
			}
			@media screen and (min-width: 800px) {
				body:not(.folded) #adminmenuback,
				body:not(.folded) #adminmenuwrap,
				body:not(.folded) #adminmenu,
				body:not(.folded) #adminmenu .wp-submenu {
					width: <?php echo $sidebar_width; ?>px !important;
				}
				body:not(.folded) #wpcontent,
				body:not(.folded) #wpfooter {
					margin-left: <?php echo $sidebar_width; ?>px !important;
				}
				body:not(.folded) #adminmenu .wp-not-current-submenu .wp-submenu,
				body:not(.folded) .interface-interface-skeleton {
					left: <?php echo $sidebar_width; ?>px !important;
				}
				body:not(.folded) #adminmenu .wp-not-current-submenu .wp-submenu,
				body:not(.folded) #adminmenu .wp-has-current-submenu .wp-submenu {
					min-width: <?php echo $sidebar_width; ?>px!important;
				}
				/* WooCommerce header fix */
				body:not(.folded) .woocommerce-layout__header,
				body:not(.folded) #e-admin-top-bar-root {
					width: calc(100% - <?php echo $sidebar_width; ?>px);
				}
			}

		</style>
		<style>
			#ultimakit_sidebar_width {
				-webkit-appearance: none;
				appearance: none; 
				width: 100%;
				cursor: pointer;
				outline: none;
				overflow: hidden;
				border-radius: 16px;
			}

			#ultimakit_sidebar_width::-webkit-slider-runnable-track {
				height: 12px;
				background: #ccc;
				border-radius: 16px;
			}

			#ultimakit_sidebar_width::-moz-range-track {
				height: 12px;
				background: #ccc;
				border-radius: 16px;
			}

			#ultimakit_sidebar_width::-webkit-slider-thumb {
				-webkit-appearance: none;
				appearance: none; 
				height: 12px;
				width: 12px;
				background-color: #fff;
				border-radius: 50%;
				border: 2px solid #2271b1;
				box-shadow: -407px 0 0 400px #2271b1;
			}

			#ultimakit_sidebar_width::-moz-range-thumb {
				height: 12px;
				width: 12px;
				background-color: #fff;
				border-radius: 50%;
				border: 1px solid #2271b1;
				box-shadow: -407px 0 0 400px #2271b1;
			}
		</style>
		<?php
	}

	public function ultimakit_remove_admin_bar_item( $wp_admin_bar ) {

		if ( 'on' === $this->getModuleSettings( $this->ID, 'hide_customize_item' ) ) {
			$wp_admin_bar->remove_menu( 'customize' );
		}

		if ( 'on' === $this->getModuleSettings( $this->ID, 'hide_updates_item' ) ) {
			$wp_admin_bar->remove_menu( 'updates' );
		}

		if ( 'on' === $this->getModuleSettings( $this->ID, 'hide_comments_item' ) ) {
			$wp_admin_bar->remove_menu( 'comments' );
		}

		if ( 'on' === $this->getModuleSettings( $this->ID, 'hide_new_item' ) ) {
			$wp_admin_bar->remove_menu( 'new-content' );
		}
	}
}
