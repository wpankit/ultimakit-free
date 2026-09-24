<?php
/**
 * Class UltimaKit_Module_Wc_Hide_Qty_Selector
 *
 * @since 1.0.0
 * @package    UltimaKit
 */

/**
 * Class UltimaKit_Module_Wc_Hide_Qty_Selector
 *
 * Module Version: 1.0.0
 *
 * This class provides methods to control the display of dashboard widgets based on user preferences.
 * It allows users to selectively hide certain dashboard widgets to streamline their WordPress dashboard
 * experience and improve usability.
 *
 * @since 1.0.0
 */
class UltimaKit_Module_Wc_Hide_Qty_Selector extends UltimaKit_Module_Manager {
	/**
	 *
	 * @var string
	 */
	protected $ID = 'ultimakit_module_wc_hide_qty_selector';

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
	protected $read_more_link = 'hide-quantity-selector-product-pages-in-woocommerce';

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
		$this->name        = __( 'Woo - Hide Quantity Selector on Product Pages', 'ultimakit-for-wp' );
		$this->description = __( 'Hiding the quantity selector simplifies the interface for products that can only be purchased one at a time.', 'ultimakit-for-wp' );
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

			if ( 'on' === $this->getModuleSettings( $this->ID, 'hide_qty_selector' ) ) {
				// Hide the quantity selector on WooCommerce product pages
				add_filter( 'woocommerce_is_sold_individually', array( $this, 'hide_quantity_selector_on_product_pages' ), 10, 2 );
			}

			// Add custom field to WooCommerce product data tabs
			add_action( 'woocommerce_product_options_general_product_data', array( $this, 'wc_custom_quantity_selector_option' ) );

			// Save the custom field
			add_action( 'woocommerce_process_product_meta', array( $this, 'wc_save_custom_quantity_selector_option' ) );

			// Hide quantity selector based on the product option
			add_filter( 'woocommerce_is_sold_individually', array( $this, 'wc_hide_quantity_selector_per_product' ), 10, 2 );

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
		$arguments['title'] = __( 'Hide Quantity Selector on Product Pages', 'ultimakit-for-wp' );

		$arguments['fields'] = array(
			'hide_qty_selector' => array(
				'type'  => 'switch',
				'label' => __( 'Hide the quantity selector on all product pages', 'ultimakit-for-wp' ),
				'value' => $this->getModuleSettings( $this->ID, 'hide_qty_selector' ),
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
			array( 'jquery', 'wp-color-picker' ),
			ULTIMAKIT_FOR_WP_VERSION,
			true
		);
	}

	public function hide_quantity_selector_on_product_pages( $sold_individually, $product ) {
		// Set to true to hide the quantity selector globally
		return true;
	}

	public function wc_custom_quantity_selector_option() {
		woocommerce_wp_checkbox(
			array(
				'id'          => '_hide_quantity_selector',
				'label'       => __( 'Hide Quantity Selector', 'ultimakit-for-wp' ),
				'description' => __( 'Check this box to hide the quantity selector on the product page.', 'ultimakit-for-wp' ),
			)
		);
	}

	public function wc_save_custom_quantity_selector_option( $post_id ) {
		$hide_quantity = isset( $_POST['_hide_quantity_selector'] ) ? 'yes' : 'no';
		update_post_meta( $post_id, '_hide_quantity_selector', $hide_quantity );
	}

	public function wc_hide_quantity_selector_per_product( $sold_individually, $product ) {
		$hide_quantity = get_post_meta( $product->get_id(), '_hide_quantity_selector', true );
		if ( $hide_quantity === 'yes' ) {
			return true;
		}
		return $sold_individually;
	}
}
