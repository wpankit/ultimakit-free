<?php
/**
 * Class UltimaKit_Module_Term_Order
 *
 * @since 1.0.0
 * @package    UltimaKit
 */

/**
 * Class UltimaKit_Module_Term_Order
 *
 * @since 1.0.0
 */
class UltimaKit_Module_Term_Order extends UltimaKit_Module_Manager {
	/**
	 * @var string
	 */
	protected $ID = 'ultimakit_module_term_order';

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
	protected $category = 'Content Management';

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
	protected $read_more_link = 'set-term-order-in-wordpress';

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
		$this->name        = __( 'Term Order', 'ultimakit-for-wp' );
		$this->description = __( 'Enable drag-and-drop term reordering in WordPress.', 'ultimakit-for-wp' );
		$this->is_active   = $this->isModuleActive( $this->ID );
		$this->settings    = 'yes';
		$this->initializeModule();

		// add_action('ultimakit_module_action_fired', array($this,'module_updated'), 1, 2 );
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

			add_action( 'wp_ajax_ultimakit_save_term_order', array( $this, 'save_term_order' ) );
			add_filter( 'get_terms_args', array( $this, 'include_term_order' ), 10, 2 );

			// Add filter conditionally for the admin
			if ( is_admin() ) {
				add_filter( 'get_terms', array( $this, 'order_terms_by_term_meta' ), 10, 3 );
			}

			if ( 'on' === $this->getModuleSettings( $this->ID, 'show_on_frontend' ) ) {
				add_filter( 'get_terms', array( $this, 'order_terms_by_term_meta' ), 10, 3 );
			}
		}
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
	public function add_scripts( $hook ) {

		if ( $hook == 'edit-tags.php' || 'toplevel_page_wp-ultimakit-dashboard' === $hook ) {
			wp_enqueue_script( 'jquery-ui-sortable' );

			wp_enqueue_script(
				'ultimakit-module-script-' . $this->ID,
				plugins_url( '/module-script.js', __FILE__ ),
				array( 'jquery', 'jquery-ui-sortable' ),
				ULTIMAKIT_FOR_WP_VERSION,
				true
			);

			wp_localize_script(
				'ultimakit-module-script-' . $this->ID,
				'ultimakit_term_order',
				array(
					'ajax_url'   => admin_url( 'admin-ajax.php' ),
					'ajax_nonce' => wp_create_nonce( 'ultimakit-term-order' ),
					'taxonomy'   => isset( $_GET['taxonomy'] ) ? $_GET['taxonomy'] : '',
				)
			);
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
		$arguments['title'] = __( 'Term Order', 'ultimakit-for-wp' );

		$arguments['fields'] = array(
			'show_on_frontend' => array(
				'type'  => 'switch',
				'label' => __( 'Display the custom order of terms in frontend queries.', 'ultimakit-for-wp' ),
				'value' => $this->getModuleSettings( $this->ID, 'show_on_frontend' ),
			),
		);
		$this->ultimakit_generate_modal( $arguments );
	}

	public function save_term_order() {
		// Security check (adjust capability as needed)
		if ( ! current_user_can( 'manage_categories' ) || ! wp_verify_nonce( $_POST['nonce'], 'ultimakit-term-order' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'ultimakit-for-wp' ) ), 401 );
		}

		// Check if it's an AJAX request
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			$order = $_POST['order'];
			// parse_str($order, $term_order);
			global $wpdb;

			foreach ( $order as $term_id ) {
				update_term_meta( $term_id['term_id'], 'term_order', $term_id['position'] );
			}

			// Respond with success
			wp_send_json_success( __( 'Terms order saved successfully!', 'ultimakit-for-wp' ) );
		} else {
			// Not an AJAX request, handle it accordingly (e.g., error message)
			wp_send_json_error( __( 'Something went wrong, Please try again.', 'ultimakit-for-wp' ) );
		}
	}

	public function include_term_order( $args, $taxonomies ) {
		if ( isset( $_GET['taxonomy'] ) && in_array( $_GET['taxonomy'], $taxonomies ) ) {
			$args['orderby'] = 'term_order';
			$args['order']   = 'ASC';
		}
		return $args;
	}

	public function order_terms_by_term_meta( $terms, $taxonomies, $args ) {

		if ( isset( $args['orderby'] ) && $args['orderby'] === 'term_order' ) {
			usort(
				$terms,
				function ( $a, $b ) {

					$order_a = get_term_meta( $a->term_id, 'term_order', true );
					$order_b = get_term_meta( $b->term_id, 'term_order', true );

					$order_a = is_numeric( $order_a ) ? (int) $order_a : 0;
					$order_b = is_numeric( $order_b ) ? (int) $order_b : 0;

					return $order_a - $order_b; // Sort in ascending order
				}
			);
		}

		return $terms;
	}
}
