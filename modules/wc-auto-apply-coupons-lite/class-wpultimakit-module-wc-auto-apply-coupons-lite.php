<?php
/**
 * Class UltimaKit_Module_Wc_Auto_Apply_Coupons_Lite
 *
 * @since 1.0.0
 * @package    UltimaKit
 */

/**
 * Class UltimaKit_Module_Wc_Auto_Apply_Coupons_Lite
 *
 * Module Version: 1.0.0
 *
 * This class provides methods to control the display of dashboard widgets based on user preferences.
 * It allows users to selectively hide certain dashboard widgets to streamline their WordPress dashboard
 * experience and improve usability.
 *
 * @since 1.0.0
 */
class UltimaKit_Module_Wc_Auto_Apply_Coupons_Lite extends UltimaKit_Module_Manager {
	/**
	 *
	 * @var string
	 */
	protected $ID = 'ultimakit_module_wc_auto_apply_coupons_lite';

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
	protected $read_more_link = 'auto-apply-coupons-lite-in-woocommerce';

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
		$this->name        = __( 'Woo - Auto Apply Coupons', 'ultimakit-for-wp' );
		$this->description = __( 'Automatically apply coupons based on the total cart value.', 'ultimakit-for-wp' );
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

			// Automatically apply a coupon based on cart total or directly if min_cart_total is empty or zero
			add_action( 'woocommerce_cart_updated', array( $this, 'wc_auto_apply_coupon_based_on_min_cart_total' ) );
			add_action( 'woocommerce_before_cart', array( $this, 'wc_auto_apply_coupon_based_on_min_cart_total' ) );
			add_action( 'woocommerce_before_checkout_form', array( $this, 'wc_auto_apply_coupon_based_on_min_cart_total' ) );

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
		$arguments['title'] = __( 'Auto Apply coupons', 'ultimakit-for-wp' );

		$arguments['fields'] = array(
			'coupon_code' => array(
				'type'  => 'text',
				'label' => __( 'Coupon Code', 'ultimakit-for-wp' ),
				'value' => $this->getModuleSettings( $this->ID, 'coupon_code' ),
			),
			'cart_value'  => array(
				'type'  => 'number',
				'label' => __( 'Minimum Cart Value', 'ultimakit-for-wp' ),
				'value' => $this->getModuleSettings( $this->ID, 'cart_value' ),
				'desc'  => __( 'Set a minimum cart value to apply the coupon. Set to 0 or leave empty to auto-apply on all orders.' ),
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

	public function wc_auto_apply_coupon_based_on_min_cart_total() {
		// Define your coupon code and the minimum cart total
		$coupon_code    = $this->getModuleSettings( $this->ID, 'coupon_code' );
		$min_cart_total = $this->getModuleSettings( $this->ID, 'cart_value' ); // Set to 0 or empty to apply coupon directly, or specify a value for minimum cart total

		if ( empty( $coupon_code ) ) {
			return;
		}
		// Ensure WooCommerce cart object exists
		if ( WC()->cart ) {
			// Get the current cart total (before discounts and taxes)
			$cart_total = WC()->cart->get_cart_contents_total();

			// If min_cart_total is zero or empty, apply coupon directly
			if ( empty( $min_cart_total ) || $min_cart_total == 0 ) {
				if ( ! WC()->cart->has_discount( $coupon_code ) ) {
					WC()->cart->apply_coupon( $coupon_code );
				}
			} else {
				// If the cart total is greater than or equal to min_cart_total, apply the coupon
				if ( $cart_total >= $min_cart_total ) {
					if ( ! WC()->cart->has_discount( $coupon_code ) ) {
						WC()->cart->apply_coupon( $coupon_code );
					}
				}
			}
		}
	}
}
