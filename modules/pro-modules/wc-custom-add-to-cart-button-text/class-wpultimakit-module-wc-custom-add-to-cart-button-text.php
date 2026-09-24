<?php
/**
 * Class UltimaKit_Module_Wc_Custom_Add_To_Cart_Button_Text
 *
 * @since 1.0.0
 * @package    UltimaKit
 */

/**
 * Class UltimaKit_Module_Wc_Custom_Add_To_Cart_Button_Text
 *
 * Module Version: 1.0.0
 *
 * This class provides methods to control the display of dashboard widgets based on user preferences.
 * It allows users to selectively hide certain dashboard widgets to streamline their WordPress dashboard
 * experience and improve usability.
 *
 * @since 1.0.0
 */
class UltimaKit_Module_Wc_Custom_Add_To_Cart_Button_Text extends UltimaKit_Module_Manager {
	/**
	 *
	 * @var string
	 */
	protected $ID = 'ultimakit_module_wc_custom_add_to_cart_button_text';

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
	protected $read_more_link = 'set-custom-text-to-add-to-cart-button-in-woocommerce';

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
		$this->name        = __( 'Woo - Custom "Add to Cart" Button Text', 'ultimakit-for-wp' );
		$this->description = __( 'Set custom text to Add to Cart Button.', 'ultimakit-for-wp' );
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
			// Change "Add to Cart" button text for simple products
			add_filter( 'woocommerce_product_single_add_to_cart_text', array( $this, 'custom_add_to_cart_text_single' ) );
			add_filter( 'woocommerce_product_add_to_cart_text', array( $this, 'custom_add_to_cart_text_archive' ) );

			/*
			 * Block themes need nothing extra: WooCommerce's product button block reads the same
			 * add_to_cart_text() filters. The old block-editor script was printed before the
			 * editor's doctype and relabelled every core Button block, so it is gone.
			 */
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
		$arguments['title'] = __( 'Custom "Add to Cart" Button Text', 'ultimakit-for-wp' );

		$arguments['fields'] = array(
			'custom_add_to_cart_btn_txt' => array(
				'type'  => 'text',
				'label' => __( 'Set Button Text', 'ultimakit-for-wp' ),
				'value' => $this->getModuleSettings( $this->ID, 'custom_add_to_cart_btn_txt' ),
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

	public function custom_add_to_cart_text_single( $text ) {
		$custom_text = $this->getModuleSettings( $this->ID, 'custom_add_to_cart_btn_txt' );

		// Keep WooCommerce's own label while no custom text is set, rather than a blank button.
		return ! empty( $custom_text ) ? $custom_text : $text;
	}

	public function custom_add_to_cart_text_archive( $text ) {
		$custom_text = $this->getModuleSettings( $this->ID, 'custom_add_to_cart_btn_txt' );

		// Keep WooCommerce's own label while no custom text is set, rather than a blank button.
		return ! empty( $custom_text ) ? $custom_text : $text;
	}
}
