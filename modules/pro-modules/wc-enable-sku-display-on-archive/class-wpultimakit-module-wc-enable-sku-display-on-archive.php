<?php
/**
 * Class UltimaKit_Module_Wc_Enable_Sku_Display_On_Archive
 *
 * @since 1.0.0
 * @package    UltimaKit
 */

/**
 * Class UltimaKit_Module_Wc_Enable_Sku_Display_On_Archive
 *
 * Module Version: 1.0.0
 *
 * This class provides methods to control the display of dashboard widgets based on user preferences.
 * It allows users to selectively hide certain dashboard widgets to streamline their WordPress dashboard
 * experience and improve usability.
 *
 * @since 1.0.0
 */
class UltimaKit_Module_Wc_Enable_Sku_Display_On_Archive extends UltimaKit_Module_Manager {
	/**
	 *
	 * @var string
	 */
	protected $ID = 'ultimakit_module_wc_enable_sku_display_on_archive';

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
	protected $category = 'WooCommerce';

	/**
	 * The type of module, indicating its platform or use case.
	 *
	 * @var string
	 */
	protected $type = 'WooCommerce';

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
	protected $read_more_link = 'enable-sku-display-on-archive-pages-in-woocommerce';

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
		$this->name        = __( 'Woo - Enable SKU Display on Product Archive Pages', 'ultimakit-for-wp' );
		$this->description = __( 'Allows store owners to display the SKU (Stock Keeping Unit) of products on the archive pages.', 'ultimakit-for-wp' );
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

			if ( 'on' === $this->getModuleSettings( $this->ID, 'enable_sku' ) ) {
				// Display SKU on WooCommerce product archive pages (shop, category, etc.)
				add_action( 'woocommerce_after_shop_loop_item_title', array( $this, 'wc_show_sku_on_archive_pages' ), 15 );
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
		$arguments['title'] = __( 'Enable SKU Display on Product Archive Pages', 'ultimakit-for-wp' );

		$arguments['fields'] = array(
			'enable_sku'         => array(
				'type'  => 'switch',
				'label' => __( 'Enable', 'ultimakit-for-wp' ),
				'value' => $this->getModuleSettings( $this->ID, 'enable_sku' ),
			),
			'text_label_for_sku' => array(
				'type'        => 'text',
				'placeholder' => __( 'SKU:', 'ultimakit-for-wp' ),
				'label'       => __( 'Label', 'ultimakit-for-wp' ),
				'value'       => $this->getModuleSettings( $this->ID, 'text_label_for_sku' ),
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

	public function wc_show_sku_on_archive_pages() {
		global $product;

		// Get the SKU
		$sku = $product->get_sku();

		// Get the SKU Label
		$label = $this->getModuleSettings( $this->ID, 'text_label_for_sku' ) ?: __( 'SKU', 'ultimakit-for-wp' );

		// Check if SKU exists and display it
		if ( $sku ) {
			echo '<p class="product-sku" style="text-align:center;">' . esc_html( $label ) . ' ' . esc_html( $sku ) . '</p>';
		}
	}
}
