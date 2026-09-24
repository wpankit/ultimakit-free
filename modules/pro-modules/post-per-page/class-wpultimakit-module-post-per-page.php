<?php
/**
 * Class UltimaKit_Module_Post_Per_Page
 *
 * @since 1.0.0
 * @package    UltimaKit
 */

/**
 * Class UltimaKit_Module_Post_Per_Page
 *
 * This module allows users to set custom post counts per page for different post types,
 * providing better control over content display and user experience.
 *
 * @since 1.0.0
 */
class UltimaKit_Module_Post_Per_Page extends UltimaKit_Module_Manager {
	/**
	 * Unique identifier for the Post Per Page module.
	 *
	 * @var string
	 */
	protected $ID = 'ultimakit_post_per_page';

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
	protected $read_more_link = 'post-per-page-in-wordpress';

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
		$this->name        = __( 'Post Per Page', 'ultimakit-for-wp' );
		$this->description = __( 'Set the number of posts displayed per page for each post type.', 'ultimakit-for-wp' );
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

			// Hook into WordPress to modify posts per page
			add_action( 'pre_get_posts', array( $this, 'modify_posts_per_page' ) );
		}
	}

	/**
	 * Modifies the posts per page for different post types.
	 *
	 * @param WP_Query $query The WordPress query object.
	 */
	public function modify_posts_per_page( $query ) {
		// Only modify main queries and frontend queries
		if ( is_admin() || ! $query->is_main_query() ) {
			return;
		}

		// Don't modify admin queries
		if ( is_admin() ) {
			return;
		}

		// Get the post type
		$post_type = $query->get( 'post_type' );
		
		// Default to 'post' if no post type is set
		if ( empty( $post_type ) ) {
			$post_type = 'post';
		}

		// Handle array of post types
		if ( is_array( $post_type ) ) {
			$post_type = $post_type[0]; // Use the first post type
		}

		// Get custom posts per page setting for this post type
		$posts_per_page = $this->get_posts_per_page_for_type( $post_type );
		
		if ( $posts_per_page && is_numeric( $posts_per_page ) && $posts_per_page > 0 ) {
			$query->set( 'posts_per_page', intval( $posts_per_page ) );
		}
	}

	/**
	 * Gets the posts per page setting for a specific post type.
	 *
	 * @param string $post_type The post type.
	 * @return int|false The number of posts per page or false if not set.
	 */
	private function get_posts_per_page_for_type( $post_type ) {
		// Check if there's a specific setting for this post type
		$post_type_setting = $this->getModuleSettings( $this->ID, 'post_type_' . $post_type );
		if ( ! empty( $post_type_setting ) ) {
			return intval( $post_type_setting );
		}

		// Check if there's a default setting
		$default_setting = $this->getModuleSettings( $this->ID, 'default' );
		if ( ! empty( $default_setting ) ) {
			return intval( $default_setting );
		}

		return false;
	}

	/**
	 * Gets all available post types for the settings form.
	 *
	 * @return array Array of post types with their labels.
	 */
	private function get_available_post_types() {
		$post_types = get_post_types( array( 'public' => true ), 'objects' );
		$available_types = array();

		foreach ( $post_types as $post_type => $post_type_object ) {
			$available_types[ $post_type ] = $post_type_object->labels->name;
		}

		return $available_types;
	}



	/**
	 * Sanitizes the posts per page value.
	 *
	 * @param mixed $value The value to sanitize.
	 * @return int The sanitized value.
	 */
	public function sanitize_posts_per_page( $value ) {
		$value = intval( $value );
		
		if ( $value < 1 ) {
			$value = 1;
		} elseif ( $value > 100 ) {
			$value = 100;
		}
		
		return $value;
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
		$arguments['title'] = __( 'Post Per Page Settings', 'ultimakit-for-wp' );

		// Get available post types
		$post_types = $this->get_available_post_types();

		$fields = array();

		// Add default setting
		$fields['default'] = array(
			'type'  => 'number',
			'label' => __( 'Default Posts Per Page', 'ultimakit-for-wp' ),
			'value' => $this->getModuleSettings( $this->ID, 'default' ),
			'desc'  => __( 'Default number of posts per page for all post types (if not specified below).', 'ultimakit-for-wp' ),
			'min'   => 1,
			'max'   => 100,
		);

		// Add settings for each post type
		foreach ( $post_types as $post_type => $label ) {
			$fields[ 'post_type_' . $post_type ] = array(
				'type'  => 'number',
				'label' => sprintf( __( '%s per page', 'ultimakit-for-wp' ), $label ),
				'value' => $this->getModuleSettings( $this->ID, 'post_type_' . $post_type ),
				'desc'  => sprintf( __( 'Number of %s to display per page. Leave empty to use default.', 'ultimakit-for-wp' ), strtolower( $label ) ),
				'min'   => 1,
				'max'   => 100,
			);
		}

		$arguments['fields'] = $fields;

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

		// Localize script with AJAX data
		wp_localize_script(
			'ultimakit-module-script-' . $this->ID,
			'ultimakit_post_per_page',
			array(
				'ajax_url'   => admin_url( 'admin-ajax.php' ),
				'ajax_nonce' => wp_create_nonce( 'ultimakit_nonce' ),
			)
		);
	}


} 