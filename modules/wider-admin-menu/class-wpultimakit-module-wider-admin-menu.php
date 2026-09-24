<?php
/**
 * Class UltimaKit_Module_Wider_Admin_Menu
 *
 * @since 1.0.0
 * @package    UltimaKit
 */

/**
 * Class UltimaKit_Module_Wider_Admin_Menu
 *
 * @since 1.0.0
 */
class UltimaKit_Module_Wider_Admin_Menu extends UltimaKit_Module_Manager {
	/**
	 * @var string
	 */
	protected $ID = 'ultimakit_wider_admin_menu';

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
	protected $read_more_link = 'wider-admin-menu-in-wordpress';

	/**
	 * The settings associated with the module, if any.
	 *
	 * @var array
	 */
	protected $settings;

	/**
	 * Constructs the Enhance List Table module instance.
	 *
	 * Initializes the module with default values for properties and prepares
	 * any necessary setup or hooks into WordPress. This may include setting
	 * initial values, registering hooks, or preparing resources needed for
	 * the module to function properly within WordPress.
	 */
	public function __construct() {
		$this->name        = __( 'Wider Admin Menu', 'ultimakit-for-wp' );
		$this->description = __( 'Give the admin menu more room to better accommodate wider items.', 'ultimakit-for-wp' );
		$this->is_active   = $this->isModuleActive( $this->ID );
		$this->settings    = 'yes';
		$this->initializeModule();
		add_action( 'admin_head', array( $this, 'add_css_snippet_to_header' ) );
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
	 * Adds a CSS snippet to the header of the WordPress site.
	 *
	 * This function allows developers to inject custom CSS code directly into the <head> section
	 * of the WordPress site. The provided CSS snippet will be included inline within the HTML
	 * document, allowing for easy customization of styles without modifying theme files directly.
	 *
	 * Note: Inline CSS should be used judiciously to avoid affecting page load performance
	 * and to ensure compatibility with caching mechanisms.
	 *
	 * @since 1.0.0
	 */
	public function add_css_snippet_to_header() {
		if ( ! $this->is_active ) {
			return false;
		}

		if ( $this->getModuleSettings( $this->ID, 'menu_width' ) ) {
			$custom_width = $this->getModuleSettings( $this->ID, 'menu_width' );
			$wp_version   = get_bloginfo( 'version' );
			if ( version_compare( $wp_version, '5', '>' ) ) {
				if ( ! is_rtl() ) {
					$margin_property = 'margin-left';
					$position        = 'left';
				} else {
					$margin_property = 'margin-right';
					$position        = 'right';
				}
				?>
				<style>
					#wpcontent, #wpfooter {
					<?php echo esc_html( esc_html( $margin_property ) ); ?>: <?php echo esc_html( esc_html( $custom_width ) ); ?>px;
					}
					#adminmenuback, #adminmenuwrap, #adminmenu, #adminmenu .wp-submenu {
						width: <?php echo esc_html( $custom_width ); ?>px;
					}
					#adminmenu .wp-submenu {
					<?php echo esc_html( $position ); ?>: <?php echo esc_html( $custom_width ); ?>px;
					}
					#adminmenu .wp-not-current-submenu .wp-submenu, .folded #adminmenu .wp-has-current-submenu .wp-submenu {
						min-width: <?php echo esc_html( $custom_width ); ?>px;
					}
					/* WooCommerce header fix */
					.woocommerce-layout__header {
						width: calc(100% - <?php echo esc_html( $custom_width ); ?>);
					}
				</style>
				<?php
			} elseif ( version_compare( $wp_version, '4', '>=' ) ) {
				if ( ! is_rtl() ) {
					$margin_property = 'margin-left';
					$position        = 'left';
				} else {
					$margin_property = 'margin-right';
					$position        = 'right';
				}
				?>
				<style>
					#wpcontent, #wpfooter {
					<?php echo esc_html( esc_html( $margin_property ) ); ?>: <?php echo esc_html( $custom_width ); ?>px;
					}
					#adminmenuback, #adminmenuwrap, #adminmenu, #adminmenu .wp-submenu {
						width: <?php echo esc_html( $custom_width ); ?>px;
					}
					#adminmenu .wp-submenu {
					<?php echo esc_html( $position ); ?>: <?php echo esc_html( $custom_width ); ?>px;
					}
					#adminmenu .wp-not-current-submenu .wp-submenu, .folded #adminmenu .wp-has-current-submenu .wp-submenu {
						min-width: <?php echo esc_html( $custom_width ); ?>px;
					}
					/* WooCommerce header fix */
					.woocommerce-layout__header {
						width: calc(100% - <?php echo esc_html( $custom_width ); ?>);
					}
				</style>
				<?php
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
		$arguments['title'] = __( 'Wider Admin Menu', 'ultimakit-for-wp' );

		$arguments['fields'] = array(
			'menu_width' => array(
				'type'    => 'select',
				'label'   => __( 'Set width to (default 160px)', 'ultimakit-for-wp' ),
				'options' => array(
					'180' => '180px',
					'200' => '200px',
					'220' => '220px',
					'240' => '240px',
					'260' => '260px',
					'280' => '280px',
					'300' => '300px',
				),
				'default' => $this->getModuleSettings( $this->ID, 'menu_width' ),
			),
		);

		echo esc_html( $this->ultimakit_generate_modal( $arguments ) );
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
			'wpuk-module-script-' . $this->ID,
			plugins_url( '/module-script.js', __FILE__ ),
			array( 'jquery' ),
			ULTIMAKIT_FOR_WP_VERSION,
			true
		);
	}
}



