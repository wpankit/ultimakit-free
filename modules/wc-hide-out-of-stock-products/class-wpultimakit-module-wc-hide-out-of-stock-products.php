<?php
/**
 * Class UltimaKit_Module_Wc_Hide_Out_Of_Stock_Products
 *
 * @since 1.0.0
 * @package    UltimaKit
 */

/**
 * Class UltimaKit_Module_Wc_Hide_Out_Of_Stock_Products
 *
 * Module Version: 1.0.0
 *
 * This class provides methods to control the display of dashboard widgets based on user preferences.
 * It allows users to selectively hide certain dashboard widgets to streamline their WordPress dashboard
 * experience and improve usability.
 *
 * @since 1.0.0
 */
class UltimaKit_Module_Wc_Hide_Out_Of_Stock_Products extends UltimaKit_Module_Manager {
	/**
	 *
	 * @var string
	 */
	protected $ID = 'ultimakit_module_wc_hide_out_of_stock_products';

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
	protected $read_more_link = 'hide-out-of-stock-products-in-woocommerce';

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
		$this->name        = __( 'Woo - Hide Out Of Stock Products', 'ultimakit-for-wp' );
		$this->description = __( "Allows store owners to automatically hide products from the store's catalog if they are out of stock", 'ultimakit-for-wp' );
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
			// Automatically hide out-of-stock products from the WooCommerce shop
			add_filter( 'woocommerce_product_query', array( $this, 'hide_out_of_stock_products_from_shop' ) );

			// Ensure out-of-stock products are hidden from WooCommerce search results
			add_filter( 'woocommerce_product_is_visible', array( $this, 'hide_out_of_stock_products_from_search' ), 10, 2 );
		}
	}

	public function hide_out_of_stock_products_from_shop( $query ) {
		if ( ! is_admin() && $query->is_main_query() && ( is_shop() || is_product_category() || is_product_tag() ) ) {
			$query->set(
				'meta_query',
				array(
					array(
						'key'     => '_stock_status',
						'value'   => 'outofstock',
						'compare' => '!=',
					),
				)
			);
		}
	}


	public function hide_out_of_stock_products_from_search( $visible, $product_id ) {
		$product = wc_get_product( $product_id );
		if ( $product->is_in_stock() ) {
			return $visible;
		}
		return false;
	}
}
