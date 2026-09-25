<?php
/**
 * Class UltimaKit_Module_Custom_Post_Type_Taxonomy
 *
 * @since 1.0.0
 * @package    UltimaKit
 */

/**
 * Class UltimaKit_Module_Custom_Post_Type_Taxonomy
 *
 * @since 1.0.0
 */
class UltimaKit_Module_Custom_Post_Type_Taxonomy extends UltimaKit_Module_Manager {
	/**
	 * @var string
	 */
	protected $ID = 'ultimakit_module_custom_Post_Type_Taxonomy';

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
	protected $category = 'Custom Code';

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
	protected $read_more_link = 'custom-post-type-and-custom-taxonomy-in-wordpress';

	/**
	 * The settings associated with the module, if any.
	 *
	 * @var array
	 */
	protected $settings;

	protected $cpt_table;

	protected $ctx_table;
	private $db_version = '1.0.0';
	private $db_version_key; // Increase this whenever you change the schema

	/**
	 * Initializes the module with default values for properties and prepares
	 * any necessary setup or hooks into WordPress. This may include setting
	 * initial values, registering hooks, or preparing resources needed for
	 * the module to function properly within WordPress.
	 */
	public function __construct() {
		$this->name        = __( 'Custom Post Type / Taxonomy', 'ultimakit-for-wp' );
		$this->description = __( 'Register Custom Post Types, Custom Taxonomy', 'ultimakit-for-wp' );
		$this->is_active   = $this->isModuleActive( $this->ID );

		global $wpdb;
		$this->cpt_table = $wpdb->prefix . 'ultimakit_cpt';
		$this->ctx_table = $wpdb->prefix . 'ultimakit_ctx';

		$this->initializeModule();

		$this->db_version_key = 'ultimakit_cpt_ctx_db_version';

		// Check and setup database when module is loaded
		add_action( 'init', array( $this, 'check_db_setup' ), 1 );
	}

	/**
	 * Check and setup database
	 */
	public function check_db_setup() {
		if ( ! $this->is_active ) {
			return;
		}

		$installed_version = get_option( $this->db_version_key, '0' );
		if ( ! $this->is_table_exists( $this->cpt_table ) || version_compare( $installed_version, $this->db_version, '<' ) ) {
			$this->create_cpt_table();
		}

		if ( ! $this->is_table_exists( $this->ctx_table ) || version_compare( $installed_version, $this->db_version, '<' ) ) {
			$this->create_ctx_table();
		}

		// Run version-specific migrations
		$this->run_migrations( get_option( $this->db_version_key, '0' ) );

		// Update version in database
		update_option( $this->db_version_key, $this->db_version );
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
			add_action( 'admin_menu', array( $this, 'ultimakit_custom_content_page' ) );

			add_action( 'wp_ajax_save_custom_post_type', array( $this, 'save_custom_post_type' ) );
			add_action( 'wp_ajax_save_custom_taxonomy', array( $this, 'save_custom_taxonomy' ) );

			if ( $this->check_active_cpt() ) {
				add_action( 'init', array( $this, 'register_custom_post_type' ), 0 );
				add_action( 'init', array( $this, 'register_custom_taxonomy' ), 0 );
			}

			add_action( 'wp_ajax_ultimakit_delete_cpt', array( $this, 'delete_cpt' ) );
			add_action( 'wp_ajax_ultimakit_change_cpt_status', array( $this, 'change_cpt_status' ) );
			add_action( 'wp_ajax_ultimakit_get_cpt_ctx_data', array( $this, 'get_cpt_ctx_data' ) );

			add_action( 'wp_ajax_ultimakit_delete_ctx', array( $this, 'delete_ctx' ) );
			add_action( 'wp_ajax_ultimakit_change_ctx_status', array( $this, 'change_ctx_status' ) );

		}
	}

	public function run_migrations( $from_version ) {
		global $wpdb;

		if ( version_compare( $from_version, '1.0.1', '<' ) ) {
			// Add new columns or modify existing ones
			// $wpdb->query("ALTER TABLE {$this->table_name} ADD COLUMN new_column varchar(100)");
		}
	}

	public function create_cpt_table() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		$cpt_sql = "CREATE TABLE IF NOT EXISTS $this->cpt_table (
			`id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			`post_type_slug` VARCHAR(200) NOT NULL UNIQUE,
			`post_type_label` VARCHAR(200) NOT NULL,
			`description` TEXT NULL,
			`settings` TEXT NULL, 
			`active` TINYINT(1) DEFAULT 1, -- Boolean field (1 for active, 0 for inactive)
			`created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
			`updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			UNIQUE KEY id (id)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$wpdb->query( $cpt_sql );
	}

	public function create_ctx_table() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		$cpt_sql = "CREATE TABLE IF NOT EXISTS $this->ctx_table (
			`id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			`taxonomy_slug` VARCHAR(200) NOT NULL UNIQUE,
			`taxonomy_label` VARCHAR(200) NOT NULL,
			`settings` TEXT NULL, 
			`active` TINYINT(1) DEFAULT 1, -- Boolean field (1 for active, 0 for inactive)
			`created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
			`updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			UNIQUE KEY id (id)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$wpdb->query( $cpt_sql );
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

		$allowed_pages = array( 'wp-ultimakit-custom-post-type', 'wp-ultimakit-custom-taxonomies', 'wp-ultimakit-custom-post-type-list' );
		if ( ! isset( $_GET['page'] ) || ! in_array( $_GET['page'], $allowed_pages, true ) ) {
			return;
		}

		wp_enqueue_style(
			'ultimakit-module-style-' . $this->ID,
			plugins_url( '/module-style.css', __FILE__ ),
			array(),
			filemtime( plugin_dir_path( __FILE__ ) . 'module-style.css' )
		);

		wp_enqueue_script(
			'ultimakit-module-script-' . $this->ID,
			plugins_url( '/module-script.js', __FILE__ ),
			array( 'jquery' ),
			ULTIMAKIT_FOR_WP_VERSION,
			true
		);

		wp_localize_script(
			'ultimakit-module-script-' . $this->ID,
			'ultimakit_custom_post_type',
			array(
				'ajax_url'   => admin_url( 'admin-ajax.php' ),
				'admin_url'  => admin_url( 'admin.php?page=wp-ultimakit-custom-post-type-list' ),
				'ajax_nonce' => wp_create_nonce( 'ultimakit-custom-post-type' ),
			)
		);
	}

	public function ultimakit_custom_content_page() {

		add_submenu_page(
			'wp-ultimakit-dashboard', // Parent slug
			__( 'Create Post Type', 'ultimakit-for-wp' ), // Page title
			__( 'Create Post Type', 'ultimakit-for-wp' ), // Menu title
			'manage_options', // Capability
			'wp-ultimakit-custom-post-type', // Menu slug
			array( $this, 'generate_custom_post_type' ) // Function to display the page content
		);

		add_submenu_page(
			'wp-ultimakit-dashboard', // Parent slug
			__( 'Create Taxonomy', 'ultimakit-for-wp' ), // Page title
			__( 'Create Taxonomy', 'ultimakit-for-wp' ), // Menu title
			'manage_options', // Capability
			'wp-ultimakit-custom-taxonomies', // Menu slug
			array( $this, 'generate_custom_taxonomy' ) // Function to display the page content
		);

		add_submenu_page(
			'wp-ultimakit-dashboard', // Parent slug
			__( 'Post Types & Taxonomies', 'ultimakit-for-wp' ), // Page title
			__( 'Post Types & Taxonomies', 'ultimakit-for-wp' ), // Menu title
			'manage_options', // Capability
			'wp-ultimakit-custom-post-type-list', // Menu slug
			array( $this, 'generate_custom_post_type_list' ) // Function to display the page content
		);
	}

	public function check_active_cpt() {
		global $wpdb;

		// This runs at plugins_loaded, but the tables are only created on init: skip until they exist.
		if ( ! $this->is_table_exists( $this->cpt_table ) ) {
			return false;
		}

		$result = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM $this->cpt_table WHERE active = %d LIMIT 1",
				1
			)
		);

		// Return true if there's an active post type, false otherwise
		return ! empty( $result );
	}

	/**
	 * Sanitize the key and the human-readable strings of a submitted post type / taxonomy.
	 *
	 * @param array  $fields_array Submitted fields.
	 * @param string $slug         Key already passed through sanitize_key().
	 * @return array
	 */
	private function sanitize_cpt_ctx_fields( $fields_array, $slug ) {
		$fields_array['rew_slug'] = $slug;

		foreach ( $fields_array as $key => $value ) {
			if ( 'singular_name' === $key || 'name' === $key || 0 === strpos( (string) $key, 'lab_' ) ) {
				$fields_array[ $key ] = sanitize_text_field( $value );
			}
		}

		if ( isset( $fields_array['description'] ) ) {
			$fields_array['description'] = sanitize_textarea_field( $fields_array['description'] );
		}

		return $fields_array;
	}

	/**
	 * Keys that clash with WordPress core post types, taxonomies or query vars.
	 *
	 * @param string $type 'cpt' for post types, 'ctx' for taxonomies.
	 * @return array
	 */
	private function get_reserved_keys( $type = 'cpt' ) {
		$reserved = array( 'post', 'page', 'attachment', 'revision', 'nav_menu_item', 'custom_css', 'customize_changeset', 'oembed_cache', 'user_request', 'wp_block', 'wp_template', 'wp_template_part', 'wp_global_styles', 'wp_navigation', 'wp_font_family', 'wp_font_face', 'action', 'author', 'order', 'theme' );

		if ( 'ctx' === $type ) {
			$reserved = array_merge( $reserved, array( 'category', 'post_tag', 'nav_menu', 'link_category', 'post_format', 'wp_theme', 'wp_template_part_area', 'wp_pattern_category' ) );
		}

		return $reserved;
	}

	/**
	 * Drop register_post_type() / register_taxonomy() args that take callbacks, class names or
	 * internal flags, so stored settings can never inject them.
	 *
	 * @param array $args Registration args.
	 * @return array
	 */
	private function remove_unsafe_register_args( $args ) {
		$unsafe_args = array( 'register_meta_box_cb', 'meta_box_cb', 'update_count_callback', 'meta_box_sanitize_cb', 'late_route_registration', '_builtin', '_edit_link' );

		foreach ( $unsafe_args as $unsafe_arg ) {
			unset( $args[ $unsafe_arg ] );
		}

		// The builder's "Rest Controller Class" field stays usable, but only for real REST controllers.
		foreach ( array( 'rest_controller_class', 'autosave_rest_controller_class', 'revisions_rest_controller_class' ) as $class_arg ) {
			if ( isset( $args[ $class_arg ] ) && ! ( is_string( $args[ $class_arg ] ) && class_exists( $args[ $class_arg ] ) && is_subclass_of( $args[ $class_arg ], 'WP_REST_Controller' ) ) ) {
				unset( $args[ $class_arg ] );
			}
		}

		return $args;
	}

	public function generate_custom_post_type() {
		$object = new UltimaKit_Helpers();
		?>
		<div class="wrap">
			<?php $object->ultimakit_get_header(); ?>
			<div class="container bg-white text-dark p-3 mb-3">
				<!-- Nav tabs -->
				<ul class="nav nav-tabs" id="wpukTabs" role="tablist">
					<li class="nav-item" role="presentation">
						<a class="nav-link active" id="posttype-tab" data-bs-toggle="tab" href="#custom-post-type-posttype" role="tab" aria-controls="custom-post-type-posttype" aria-selected="true"><?php echo esc_html_e( 'Post Type', 'ultimakit-for-wp' ); ?></a>
					</li>
					<li class="nav-item" role="presentation">
						<a class="nav-link" id="labels-tab" data-bs-toggle="tab" href="#custom-post-type-labels" role="tab" aria-controls="custom-post-type-labels" aria-selected="true"><?php echo esc_html_e( 'Labels', 'ultimakit-for-wp' ); ?></a>
					</li>
					<li class="nav-item" role="presentation">
						<a class="nav-link" id="options-tab" data-bs-toggle="tab" href="#custom-post-type-options" role="tab" aria-controls="custom-post-type-options" aria-selected="true"><?php echo esc_html_e( 'Options', 'ultimakit-for-wp' ); ?></a>
					</li>
					<li class="nav-item" role="presentation">
						<a class="nav-link " id="visibility-tab" data-bs-toggle="tab" href="#custom-post-type-visibility" role="tab" aria-controls="custom-post-type-visibility" aria-selected="true"><?php echo esc_html_e( 'Visibility', 'ultimakit-for-wp' ); ?></a>
					</li>
					<li class="nav-item" role="presentation">
						<a class="nav-link" id="query-tab" data-bs-toggle="tab" href="#custom-post-type-query" role="tab" aria-controls="custom-post-type-query" aria-selected="true"><?php echo esc_html_e( 'Query', 'ultimakit-for-wp' ); ?></a>
					</li>
					<li class="nav-item" role="presentation">
						<a class="nav-link" id="permalinks-tab" data-bs-toggle="tab" href="#custom-post-type-permalinks" role="tab" aria-controls="custom-post-type-permalinks" aria-selected="true"><?php echo esc_html_e( 'Permalinks', 'ultimakit-for-wp' ); ?></a>
					</li>
					<li class="nav-item" role="presentation">
						<a class="nav-link" id="capabilities-tab" data-bs-toggle="tab" href="#custom-post-type-capabilities" role="tab" aria-controls="custom-post-type-capabilities" aria-selected="true"><?php echo esc_html_e( 'Capabilities', 'ultimakit-for-wp' ); ?></a>
					</li>
					<li class="nav-item" role="presentation">
						<a class="nav-link" id="restapi-tab" data-bs-toggle="tab" href="#custom-post-type-restapi" role="tab" aria-controls="custom-post-type-restapi" aria-selected="true"><?php echo esc_html_e( 'Rest API', 'ultimakit-for-wp' ); ?></a>
					</li>
				</ul>

				<!-- Tab panes -->
				<div class="tab-content" id="wpukTabsContent">
					<div class="tab-pane fade show active" id="custom-post-type-posttype" role="tabpanel" aria-labelledby="posttype-tab">
						<!-- Your modules content here -->
						<div class="row">
							<?php
								$arguments          = array();
								$arguments['ID']    = $this->ID;
								$arguments['title'] = __( 'Post Type', 'ultimakit-for-wp' );

								$arguments['fields'] = array(
									'singular_name' => array(
										'type'     => 'text',
										'label'    => __( 'Name (Singular)', 'ultimakit-for-wp' ),
										'value'    => 'Post Type',
										'desc'     => __( 'Post type singular name. e.g. Product, Event or Movie.', 'ultimakit-for-wp' ),
										'required' => 'required',
									),
									'name'          => array(
										'type'     => 'text',
										'label'    => __( 'Name (Plural)', 'ultimakit-for-wp' ),
										'value'    => 'Post Types',
										'desc'     => __( 'Post type plural name. e.g. Products, Events or Movies.', 'ultimakit-for-wp' ),
										'required' => 'required',
									),
									'rew_slug'      => array(
										'type'     => 'text',
										'label'    => __( 'Key', 'ultimakit-for-wp' ),
										'value'    => 'post_type',
										'desc'     => __( 'Key used in the code. Up to 20 characters, lowercase, no spaces.', 'ultimakit-for-wp' ),
										'required' => 'required',
									),
									'description'   => array(
										'type'  => 'textarea',
										'label' => __( 'Description', 'ultimakit-for-wp' ),
										'desc'  => __( 'A short descriptive summary of the post type.', 'ultimakit-for-wp' ),
										'value' => $this->getModuleSettings( $this->ID, 'description' ),
									),
									'taxonomies'    => array(
										'type'     => 'text',
										'label'    => __( 'Link To Taxonomies', 'ultimakit-for-wp' ),
										'value'    => 'category,post_tag',
										'desc'     => __( 'Comma separated list of <a target="_blank" href="https://wordpress.org/documentation/article/taxonomies/">Taxonomies</a>.', 'ultimakit-for-wp' ),
										'required' => '',
									),
									'hierarchical'  => array(
										'type'     => 'select',
										'label'    => __( 'Hierarchical', 'ultimakit-for-wp' ),
										'options'  => array(
											false => 'No (like posts)',
											true  => 'Yes (like pages)',
										),
										'desc'     => __( 'Hierarchical post types allows descendants.', 'ultimakit-for-wp' ),
										'required' => '',
									),
								);

								$this->ultimakit_generate_form( $arguments );

								?>
						</div>
					</div>

					<div class="tab-pane fade show" id="custom-post-type-labels" role="tabpanel" aria-labelledby="labels-tab">
						<!-- Your modules content here -->
						<div class="row">
							<?php
								$arguments          = array();
								$arguments['ID']    = $this->ID;
								$arguments['title'] = __( 'Labels', 'ultimakit-for-wp' );

								$arguments['fields'] = array(
									'lab_menu_name'        => array(
										'type'     => 'text',
										'label'    => __( 'Menu Name', 'ultimakit-for-wp' ),
										'value'    => __( 'Post Types', 'ultimakit-for-wp' ),
										'required' => false,
									),
									'lab_name_admin_bar'   => array(
										'type'     => 'text',
										'label'    => __( 'Admin Bar Name', 'ultimakit-for-wp' ),
										'value'    => __( 'Post Type', 'ultimakit-for-wp' ),
										'required' => false,
									),
									'lab_archives'         => array(
										'type'     => 'text',
										'label'    => __( 'Archives', 'ultimakit-for-wp' ),
										'value'    => __( 'Item Archives', 'ultimakit-for-wp' ),
										'required' => false,
									),
									'lab_attributes'       => array(
										'type'     => 'text',
										'label'    => __( 'Attributes', 'ultimakit-for-wp' ),
										'value'    => __( 'Item Attributes', 'ultimakit-for-wp' ),
										'required' => false,
									),
									'lab_parent_item_colon' => array(
										'type'     => 'text',
										'label'    => __( 'Parent Item', 'ultimakit-for-wp' ),
										'value'    => __( 'Parent Item:', 'ultimakit-for-wp' ),
										'required' => false,
									),
									'lab_all_items'        => array(
										'type'     => 'text',
										'label'    => __( 'All Items', 'ultimakit-for-wp' ),
										'value'    => __( 'All Items', 'ultimakit-for-wp' ),
										'required' => false,
									),
									'lab_add_new_item'     => array(
										'type'     => 'text',
										'label'    => __( 'Add New Item', 'ultimakit-for-wp' ),
										'value'    => __( 'Add New Item', 'ultimakit-for-wp' ),
										'required' => false,
									),
									'lab_add_new'          => array(
										'type'     => 'text',
										'label'    => __( 'Add New', 'ultimakit-for-wp' ),
										'value'    => __( 'Add New', 'ultimakit-for-wp' ),
										'required' => false,
									),
									'lab_new_item'         => array(
										'type'     => 'text',
										'label'    => __( 'New Item', 'ultimakit-for-wp' ),
										'value'    => __( 'New Item', 'ultimakit-for-wp' ),
										'required' => false,
									),
									'lab_edit_item'        => array(
										'type'     => 'text',
										'label'    => __( 'Edit Item', 'ultimakit-for-wp' ),
										'value'    => __( 'Edit Item', 'ultimakit-for-wp' ),
										'required' => false,
									),
									'lab_update_item'      => array(
										'type'     => 'text',
										'label'    => __( 'Update Item', 'ultimakit-for-wp' ),
										'value'    => __( 'Update Item', 'ultimakit-for-wp' ),
										'required' => false,
									),
									'lab_view_item'        => array(
										'type'     => 'text',
										'label'    => __( 'View Item', 'ultimakit-for-wp' ),
										'value'    => __( 'View Item', 'ultimakit-for-wp' ),
										'required' => false,
									),
									'lab_view_items'       => array(
										'type'     => 'text',
										'label'    => __( 'View Items', 'ultimakit-for-wp' ),
										'value'    => __( 'View Items', 'ultimakit-for-wp' ),
										'required' => false,
									),
									'lab_search_items'     => array(
										'type'     => 'text',
										'label'    => __( 'Search Item', 'ultimakit-for-wp' ),
										'value'    => __( 'Search Item', 'ultimakit-for-wp' ),
										'required' => false,
									),
									'lab_not_found'        => array(
										'type'     => 'text',
										'label'    => __( 'Not Found', 'ultimakit-for-wp' ),
										'value'    => __( 'Not found', 'ultimakit-for-wp' ),
										'required' => false,
									),
									'lab_not_found_in_trash' => array(
										'type'     => 'text',
										'label'    => __( 'Not Found in Trash', 'ultimakit-for-wp' ),
										'value'    => __( 'Not found in Trash', 'ultimakit-for-wp' ),
										'required' => false,
									),
									'lab_featured_image'   => array(
										'type'     => 'text',
										'label'    => __( 'Featured Image', 'ultimakit-for-wp' ),
										'value'    => __( 'Featured Image', 'ultimakit-for-wp' ),
										'required' => false,
									),
									'lab_set_featured_image' => array(
										'type'     => 'text',
										'label'    => __( 'Set Featured Image', 'ultimakit-for-wp' ),
										'value'    => __( 'Set Featured Image', 'ultimakit-for-wp' ),
										'required' => false,
									),
									'lab_remove_featured_image' => array(
										'type'     => 'text',
										'label'    => __( 'Remove Featured Image', 'ultimakit-for-wp' ),
										'value'    => __( 'Remove Featured Image', 'ultimakit-for-wp' ),
										'required' => false,
									),
									'lab_use_featured_image' => array(
										'type'     => 'text',
										'label'    => __( 'Use as featured image', 'ultimakit-for-wp' ),
										'value'    => __( 'Use as featured image', 'ultimakit-for-wp' ),
										'required' => false,
									),
									'lab_insert_into_item' => array(
										'type'     => 'text',
										'label'    => __( 'Insert into item', 'ultimakit-for-wp' ),
										'value'    => __( 'Insert into item', 'ultimakit-for-wp' ),
										'required' => false,
									),
									'lab_uploaded_to_this_item' => array(
										'type'     => 'text',
										'label'    => __( 'Uploaded to this item', 'ultimakit-for-wp' ),
										'value'    => __( 'Uploaded to this item', 'ultimakit-for-wp' ),
										'required' => false,
									),
									'lab_items_list'       => array(
										'type'     => 'text',
										'label'    => __( 'Items list', 'ultimakit-for-wp' ),
										'value'    => __( 'Items list', 'ultimakit-for-wp' ),
										'required' => false,
									),
									'lab_items_list_navigation' => array(
										'type'     => 'text',
										'label'    => __( 'Items list navigation', 'ultimakit-for-wp' ),
										'value'    => __( 'Items list navigation', 'ultimakit-for-wp' ),
										'required' => false,
									),
									'lab_filter_items_list' => array(
										'type'     => 'text',
										'label'    => __( 'Filter items list', 'ultimakit-for-wp' ),
										'value'    => __( 'Filter items list', 'ultimakit-for-wp' ),
										'required' => false,
									),
								);
								$this->ultimakit_generate_form( $arguments );
								?>
						</div>
					</div>

					<div class="tab-pane fade show" id="custom-post-type-options" role="tabpanel" aria-labelledby="custom-post-type-options">
						<!-- Your modules content here -->
						<div class="row">
							<?php
								$arguments          = array();
								$arguments['ID']    = $this->ID;
								$arguments['title'] = __( 'Options', 'ultimakit-for-wp' );

								$checkboxes  = '<label>' . __( 'Support', 'ultimakit-for-wp' ) . '</label><br />';
								$checkboxes .= '<input type="checkbox" value="title" checked name="wp_supports[]">' . __( 'Title', 'ultimakit-for-wp' ) . '<br />';
								$checkboxes .= '<input type="checkbox" value="editor" name="wp_supports[]">' . __( 'Content (editor)', 'ultimakit-for-wp' ) . '<br />';
								$checkboxes .= '<input type="checkbox" value="excerpt" name="wp_supports[]">' . __( 'Excerpt', 'ultimakit-for-wp' ) . '<br />';
								$checkboxes .= '<input type="checkbox" value="author" name="wp_supports[]">' . __( 'Author', 'ultimakit-for-wp' ) . '<br />';
								$checkboxes .= '<input type="checkbox" value="featured_image" name="wp_supports[]">' . __( 'Featured Image', 'ultimakit-for-wp' ) . '<br />';
								$checkboxes .= '<input type="checkbox" value="comments" name="wp_supports[]">' . __( 'Comments', 'ultimakit-for-wp' ) . '<br />';
								$checkboxes .= '<input type="checkbox" value="trackbacks" name="wp_supports[]">' . __( 'Trackbacks', 'ultimakit-for-wp' ) . '<br />';
								$checkboxes .= '<input type="checkbox" value="revisions" name="wp_supports[]">' . __( 'Revisions', 'ultimakit-for-wp' ) . '<br />';
								$checkboxes .= '<input type="checkbox" value="custom_fields" name="wp_supports[]">' . __( 'Custom Fields', 'ultimakit-for-wp' ) . '<br />';
								$checkboxes .= '<input type="checkbox" value="page_attributes" name="wp_supports[]">' . __( 'Page Attributes', 'ultimakit-for-wp' ) . '<br />';
								$checkboxes .= '<input type="checkbox" value="post_formats" name="wp_supports[]">' . __( 'Post Formats', 'ultimakit-for-wp' );

								$arguments['fields'] = array(
									'supports'            => array(
										'type'     => 'html',
										'label'    => __( 'Supports', 'ultimakit-for-wp' ),
										'value'    => $checkboxes,
										'required' => false,
									),
									'exclude_from_search' => array(
										'type'    => 'select',
										'label'   => __( 'Exclude From Search', 'ultimakit-for-wp' ),
										'options' => array(
											'no'  => 'No',
											'yes' => 'Yes',
										),
										'desc'    => __( 'Posts of this type should be excluded from search results.', 'ultimakit-for-wp' ),
										'default' => 'no',
									),
									'can_export'          => array(
										'type'     => 'select',
										'label'    => __( 'Enable Export', 'ultimakit-for-wp' ),
										'options'  => array(
											'yes' => 'Yes',
											'no'  => 'No',
										),
										'desc'     => __( 'Enables post type export.', 'ultimakit-for-wp' ),
										'default'  => 'yes',
										'required' => false,
									),
									'enable_archives'     => array(
										'type'     => 'select',
										'label'    => __( 'Enable Archives', 'ultimakit-for-wp' ),
										'options'  => array(
											'no'          => __( 'No (prevent archive pages)', 'ultimakit-for-wp' ),
											'yes_default' => __( 'Yes (use default slug)', 'ultimakit-for-wp' ),
											'custom'      => __( 'Yes (set custom archive slug)', 'ultimakit-for-wp' ),
										),
										'desc'     => __( 'Enables post type archives. Post type key is used as defauly archive slug.', 'ultimakit-for-wp' ),
										'default'  => 'yes_default',
										'required' => false,
									),
									'has_archive'         => array(
										'type'     => 'text',
										'label'    => __( 'Custom Archive Slug', 'ultimakit-for-wp' ),
										'value'    => '',
										'required' => false,
									),
								);
								$this->ultimakit_generate_form( $arguments );
								?>
						</div>
					</div>

					<?php
						// Check if 'cpt_id' exists in the URL and sanitize it
						$cpt_id = isset( $_GET['cpt_id'] ) ? esc_attr( $_GET['cpt_id'] ) : '';
					?>
					<input type="hidden" value="<?php echo $cpt_id; ?>" id="cpt_id">

					<div class="tab-pane fade show " id="custom-post-type-visibility" role="tabpanel" aria-labelledby="custom-post-type-visibility">
						<!-- Your modules content here -->
						<div class="row">
							<?php
								$arguments          = array();
								$arguments['ID']    = $this->ID;
								$arguments['title'] = __( 'Visibility', 'ultimakit-for-wp' );

								$arguments['fields'] = array(
									'public'            => array(
										'type'     => 'select',
										'label'    => __( 'Public', 'ultimakit-for-wp' ),
										'options'  => array(
											'yes' => __( 'Yes', 'ultimakit-for-wp' ),
											'no'  => __( 'No', 'ultimakit-for-wp' ),
										),
										'default'  => 'yes',
										'desc'     => __( 'Whether a post type is intended for use publicly either via the admin interface or by front-end users.', 'ultimakit-for-wp' ),
										'required' => false,
									),
									'show_ui'           => array(
										'type'     => 'select',
										'label'    => __( 'Show UI', 'ultimakit-for-wp' ),
										'options'  => array(
											'yes' => __( 'Yes', 'ultimakit-for-wp' ),
											'no'  => __( 'No', 'ultimakit-for-wp' ),
										),
										'default'  => 'yes',
										'desc'     => __( 'Show post type UI in the admin.', 'ultimakit-for-wp' ),
										'required' => false,
									),
									'show_in_admin_bar' => array(
										'type'     => 'select',
										'label'    => __( 'Show in Admin Sidebar', 'ultimakit-for-wp' ),
										'options'  => array(
											'yes' => __( 'Yes', 'ultimakit-for-wp' ),
											'no'  => __( 'No', 'ultimakit-for-wp' ),
										),
										'default'  => 'yes',
										'desc'     => __( 'Show post type in admin sidebar.', 'ultimakit-for-wp' ),
										'required' => false,
									),
									'menu_position'     => array(
										'type'     => 'select',
										'label'    => __( 'Position', 'ultimakit-for-wp' ),
										'options'  => array(
											'5'   => __( '5 - below Posts', 'ultimakit-for-wp' ),
											'10'  => __( '10 - below Posts', 'ultimakit-for-wp' ),
											'15'  => __( '15 - below Links', 'ultimakit-for-wp' ),
											'20'  => __( '20 - below Pages', 'ultimakit-for-wp' ),
											'25'  => __( '25 - below Comments', 'ultimakit-for-wp' ),
											'60'  => __( '60 - below first separator', 'ultimakit-for-wp' ),
											'65'  => __( '65 - below Plugins', 'ultimakit-for-wp' ),
											'70'  => __( '70 - below Users', 'ultimakit-for-wp' ),
											'75'  => __( '75 - below Tools', 'ultimakit-for-wp' ),
											'80'  => __( '80 - below Settings', 'ultimakit-for-wp' ),
											'100' => __( '100 - below second separator', 'ultimakit-for-wp' ),
										),
										'default'  => '5',
										'desc'     => __( 'Position to show Post Type in the Admin Sidebar', 'ultimakit-for-wp' ),
										'required' => false,
									),
									'menu_icon'         => array(
										'type'        => 'text',
										'label'       => __( 'Admin Sidebar Icon', 'ultimakit-for-wp' ),
										'value'       => 'dashicons-admin-post',
										'placeholder' => __( 'dashicons-admin-post', 'ultimakit-for-wp' ),
										'desc'        => __( 'Post type icon. Use <a href="https://developer.wordpress.org/resource/dashicons/" target="_blank">dashicon</a> name or full icon URL (http://.../icon.png).', 'ultimakit-for-wp' ),
										'required'    => false,
									),
									'show_in_nav_menus' => array(
										'type'     => 'select',
										'label'    => __( 'Show in Navigation Menus', 'ultimakit-for-wp' ),
										'options'  => array(
											'yes' => __( 'Yes', 'ultimakit-for-wp' ),
											'no'  => __( 'No', 'ultimakit-for-wp' ),
										),
										'default'  => 'yes',
										'desc'     => __( 'Show post type in <a href="https://codex.wordpress.org/Navigation_Menus" target="_blank">Navigation Menus</a>', 'ultimakit-for-wp' ),
										'required' => false,
									),
								);
								$this->ultimakit_generate_form( $arguments );
								?>
						</div>
					</div>

					<div class="tab-pane fade show " id="custom-post-type-query" role="tabpanel" aria-labelledby="custom-post-type-query">
						<!-- Your modules content here -->
						<div class="row">
							<?php
								$arguments          = array();
								$arguments['ID']    = $this->ID;
								$arguments['title'] = __( 'Visibility', 'ultimakit-for-wp' );

								$arguments['fields'] = array(
									'xx_query'           => array(
										'type'     => 'select',
										'label'    => __( 'Public', 'ultimakit-for-wp' ),
										'options'  => array(
											'false' => __( 'Default (post type key)', 'ultimakit-for-wp' ),
											'true'  => __( 'Custom query variable', 'ultimakit-for-wp' ),
										),
										'default'  => 'false',
										'desc'     => __( "Direct query variable used in <a href='https://developer.wordpress.org/reference/classes/wp_query/#post-type-parameters' target='_blank'>WP_Query</a>. <strong>e.g. WP_Query( array( 'post_type' => 'product', 'term' => 'disk' ) )</strong>", 'ultimakit-for-wp' ),
										'required' => false,
									),
									'publicly_queryable' => array(
										'type'     => 'select',
										'label'    => __( 'Publicly Queryable', 'ultimakit-for-wp' ),
										'options'  => array(
											'yes' => __( 'Yes', 'ultimakit-for-wp' ),
											'no'  => __( 'No', 'ultimakit-for-wp' ),
										),
										'default'  => 'yes',
										'desc'     => __( 'Enable front end queries as part of parse_request().', 'ultimakit-for-wp' ),
										'required' => false,
									),
									'query_var'          => array(
										'type'        => 'text',
										'label'       => __( 'Publicly Queryable', 'ultimakit-for-wp' ),
										'placeholder' => __( 'post_type', 'ultimakit-for-wp' ),
										'desc'        => __( 'Custom query variable.', 'ultimakit-for-wp' ),
										'required'    => false,
									),
								);
								$this->ultimakit_generate_form( $arguments );
								?>
						</div>
					</div>

					<div class="tab-pane fade show " id="custom-post-type-permalinks" role="tabpanel" aria-labelledby="custom-post-type-permalinks">
						<!-- Your modules content here -->
						<div class="row">
							<?php
								$arguments          = array();
								$arguments['ID']    = $this->ID;
								$arguments['title'] = __( 'Visibility', 'ultimakit-for-wp' );

								$arguments['fields'] = array(
									'xx_permalink_query' => array(
										'type'     => 'select',
										'label'    => __( 'Permalink Rewrite', 'ultimakit-for-wp' ),
										'options'  => array(
											'no'      => __( 'No permalink (prevent URL rewriting)', 'ultimakit-for-wp' ),
											'default' => __( 'Default permalink (post type key)', 'ultimakit-for-wp' ),
											'custom'  => __( 'Custom permalink', 'ultimakit-for-wp' ),
										),
										'default'  => 'false',
										'desc'     => __( 'Use Default <a href="https://developer.wordpress.org/reference/classes/wp_query/#post-type-parameters" target="_blank">Permalinks</a> (using post type key), prevent automatic URL rewriting (no pretty permalinks), or set custom permalinks.', 'ultimakit-for-wp' ),
										'required' => false,
									),
									'url_slug'           => array(
										'type'        => 'text',
										'label'       => __( 'URL Slug', 'ultimakit-for-wp' ),
										'placeholder' => __( 'post_type', 'ultimakit-for-wp' ),
										'desc'        => __( 'Pretty permalink base text. <br />i.e. www.example.com/product/', 'ultimakit-for-wp' ),
										'required'    => false,
									),
									'rew_with_front'     => array(
										'type'     => 'select',
										'label'    => __( 'Use URL Slug', 'ultimakit-for-wp' ),
										'options'  => array(
											'yes' => __( 'Yes', 'ultimakit-for-wp' ),
											'no'  => __( 'No', 'ultimakit-for-wp' ),
										),
										'default'  => 'yes',
										'desc'     => __( 'Use Post Type slug as URL base. <br />Default: Yes ', 'ultimakit-for-wp' ),
										'required' => false,
									),
									'rew_pages'          => array(
										'type'     => 'select',
										'label'    => __( 'Pagination', 'ultimakit-for-wp' ),
										'options'  => array(
											'yes' => __( 'Yes', 'ultimakit-for-wp' ),
											'no'  => __( 'No', 'ultimakit-for-wp' ),
										),
										'default'  => 'yes',
										'desc'     => __( 'Allow post-type pagination. <br />Default: Yes ', 'ultimakit-for-wp' ),
										'required' => false,
									),
									'rew_feeds'          => array(
										'type'     => 'select',
										'label'    => __( 'Feeds', 'ultimakit-for-wp' ),
										'options'  => array(
											'yes' => __( 'Yes', 'ultimakit-for-wp' ),
											'no'  => __( 'No', 'ultimakit-for-wp' ),
										),
										'default'  => 'yes',
										'desc'     => __( 'Build feed permastruct. <br />Default: Yes ', 'ultimakit-for-wp' ),
										'required' => false,
									),
								);
								$this->ultimakit_generate_form( $arguments );
								?>
						</div>
					</div>

					<div class="tab-pane fade show" id="custom-post-type-capabilities" role="tabpanel" aria-labelledby="custom-post-type-capabilities">
						<!-- Your modules content here -->
						<div class="row">
							<?php
								$arguments          = array();
								$arguments['ID']    = $this->ID;
								$arguments['title'] = __( 'Capabilities', 'ultimakit-for-wp' );

								$arguments['fields'] = array(
									'xx_capabilities'    => array(
										'type'     => 'select',
										'label'    => __( 'Capabilities', 'ultimakit-for-wp' ),
										'options'  => array(
											'base'   => __( 'Base capabilities', 'ultimakit-for-wp' ),
											'custom' => __( 'Custom capabilities', 'ultimakit-for-wp' ),
										),
										'default'  => 'base',
										'desc'     => __( 'Set <a href="https://codex.wordpress.org/Roles_and_Capabilities" target="_blank">user capabilities</a> to manage post type.', 'ultimakit-for-wp' ),
										'required' => false,
									),
									'capabilities'       => array(
										'type'     => 'select',
										'label'    => __( 'Base Capability Type', 'ultimakit-for-wp' ),
										'options'  => array(
											'posts' => __( 'Posts', 'ultimakit-for-wp' ),
											'pages' => __( 'Pages', 'ultimakit-for-wp' ),
										),
										'default'  => 'pages',
										'desc'     => __( 'Used as a base to construct capabilities.', 'ultimakit-for-wp' ),
										'required' => false,
									),
									'caps_read_post'     => array(
										'type'     => 'text',
										'label'    => __( 'Read Post', 'ultimakit-for-wp' ),
										'value'    => __( 'read_post', 'ultimakit-for-wp' ),
										'required' => false,
									),
									'caps_read_private_posts' => array(
										'type'     => 'text',
										'label'    => __( 'Read Private Posts', 'ultimakit-for-wp' ),
										'value'    => __( 'read_private_posts', 'ultimakit-for-wp' ),
										'required' => false,
									),
									'caps_publish_posts' => array(
										'type'     => 'text',
										'label'    => __( 'Publish Posts', 'ultimakit-for-wp' ),
										'value'    => __( 'publish_posts', 'ultimakit-for-wp' ),
										'required' => false,
									),
									'caps_delete_posts'  => array(
										'type'     => 'text',
										'label'    => __( 'Delete Post', 'ultimakit-for-wp' ),
										'value'    => __( 'delete_posts', 'ultimakit-for-wp' ),
										'required' => false,
									),
									'caps_edit_post'     => array(
										'type'     => 'text',
										'label'    => __( 'Edit Post', 'ultimakit-for-wp' ),
										'value'    => __( 'edit_post', 'ultimakit-for-wp' ),
										'required' => false,
									),
									'caps_edit_posts'    => array(
										'type'     => 'text',
										'label'    => __( 'Edit Posts', 'ultimakit-for-wp' ),
										'value'    => __( 'edit_posts', 'ultimakit-for-wp' ),
										'required' => false,
									),
									'caps_edit_other_posts' => array(
										'type'     => 'text',
										'label'    => __( 'Edit Others Posts', 'ultimakit-for-wp' ),
										'value'    => __( 'edit_other_posts', 'ultimakit-for-wp' ),
										'required' => false,
									),

								);
								$this->ultimakit_generate_form( $arguments );
								?>
						</div>
					</div>

					<div class="tab-pane fade show " id="custom-post-type-restapi" role="tabpanel" aria-labelledby="custom-post-type-restapi">
						<!-- Your modules content here -->
						<div class="row">
							<?php
								$arguments          = array();
								$arguments['ID']    = $this->ID;
								$arguments['title'] = __( 'Visibility', 'ultimakit-for-wp' );

								$arguments['fields'] = array(
									'show_in_rest' => array(
										'type'     => 'select',
										'label'    => __( 'Show in Rest', 'ultimakit-for-wp' ),
										'options'  => array(
											''    => __( 'Choose', 'ultimakit-for-wp' ),
											true  => __( 'Yes', 'ultimakit-for-wp' ),
											false => __( 'No', 'ultimakit-for-wp' ),
										),
										'default'  => '',
										'desc'     => __( 'Whether to add the post type route in the <br />REST API "wp/v2" namespace.', 'ultimakit-for-wp' ),
										'required' => false,
									),
									'rest_base'    => array(
										'type'     => 'text',
										'label'    => __( 'Rest Base', 'ultimakit-for-wp' ),
										'desc'     => __( 'To change the base url of REST API route. <br />Default is the post type key.', 'ultimakit-for-wp' ),
										'required' => false,
									),
									'rest_controller_class' => array(
										'type'     => 'text',
										'label'    => __( 'Rest Controller Class', 'ultimakit-for-wp' ),
										'desc'     => __( 'REST API Controller class name. <br />Default is "WP_REST_Posts_Controller".', 'ultimakit-for-wp' ),
										'required' => false,
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

	public function save_custom_post_type() {

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'You do not have sufficient permissions', 403 );
		}

		// Verify the nonce
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'ultimakit-custom-post-type' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'ultimakit-for-wp' ) ), 401 );
		}

		// Check if 'formData' is set in POST request
		if ( isset( $_POST['formData'] ) ) {
			// Decode the JSON string into a PHP array
			$formDataArray = json_decode( stripslashes( $_POST['formData'] ), true );

			$fields_array = array();

			if ( is_array( $formDataArray ) ) {
				foreach ( $formDataArray as $index => $formDataObject ) {
					foreach ( $formDataObject as $key => $value ) {
						if ( substr( $key, 0, 3 ) !== 'xx_' ) {
							$fields_array[ $key ] = $value;
						}
					}
				}

				if ( count( $fields_array ) > 0 ) {
					global $wpdb;

					// Sanitize the key and the label strings before anything is stored (add and edit).
					$slug         = isset( $fields_array['rew_slug'] ) ? sanitize_key( $fields_array['rew_slug'] ) : '';
					$fields_array = $this->sanitize_cpt_ctx_fields( $fields_array, $slug );

					if ( '' === $slug || in_array( $slug, $this->get_reserved_keys( 'cpt' ), true ) ) {
						wp_send_json_error(
							array(
								'success' => false,
								'message' => __( 'This key is empty or reserved by WordPress. Please choose a different key.', 'ultimakit-for-wp' ),
							),
							400
						);
					}

					if ( 'new' === $_POST['mode'] ) {

						// Check for duplicate slug before inserting
						$existing_slug = $wpdb->get_var(
							$wpdb->prepare(
								"SELECT id FROM $this->cpt_table WHERE post_type_slug = %s LIMIT 1",
								$slug
							)
						);
						if ( $existing_slug ) {
							wp_send_json_error(
								array(
									'success' => false,
									'message' => __( 'A Custom Post Type with this slug already exists.', 'ultimakit-for-wp' ),
								)
							);
						}

						// Insert into the database
						$insert_result = $wpdb->insert(
							$this->cpt_table, // Your custom post types table name
							array(
								'post_type_slug'  => $slug, // The slug for the custom post type
								'post_type_label' => $fields_array['singular_name'],  // Singular label for the custom post type
								'description'     => isset( $fields_array['description'] ) ? $fields_array['description'] : '', // A brief description
								'settings'        => wp_json_encode( $fields_array ), // Store settings as JSON
								'active'          => 1,
								'created_at'      => current_time( 'mysql' ), // Current timestamp
								'updated_at'      => current_time( 'mysql' ), // Current timestamp
							)
						);

						if ( false === $insert_result ) {
							wp_send_json_error(
								array(
									'success' => false,
									'message' => __( 'Error: Failed to create the Custom Post Type.', 'ultimakit-for-wp' ),
								)
							);
						}

						flush_rewrite_rules();
						wp_send_json_success(
							array(
								'success' => true,
								'message' => __( 'The Custom Post Type created successfully.', 'ultimakit-for-wp' ),
							)
						);

					} elseif ( 'edit' === $_POST['mode'] ) {
							// Check if post_type_slug already exists in the database
							$existing_id = $wpdb->get_var(
								$wpdb->prepare(
									"SELECT id FROM $this->cpt_table WHERE id = %d",
									$_POST['id']
								)
							);

							// Update the existing row
							$update_result = $wpdb->update(
								$this->cpt_table, // Your custom post types table name
								array(
									'post_type_slug'  => $slug,  // Update the label for the custom post type
									'post_type_label' => $fields_array['singular_name'],  // Update the label for the custom post type
									'description'     => $fields_array['description'],    // Update the description
									'settings'        => json_encode( $fields_array ),      // Update the settings as JSON
									'updated_at'      => current_time( 'mysql' ),            // Update the timestamp
								),
								array( 'id' => $existing_id )       // The where clause (which row to update)
							);

						if ( $update_result !== false ) {
							wp_send_json_success(
								array(
									'success' => true,
									'message' => __( 'The Custom Post Type updated successfully.', 'ultimakit-for-wp' ),
								)
							);
						} else {
							wp_send_json_error(
								array(
									'success' => false,
									'message' => __( 'Error: Failed to update the Custom Post Type.', 'ultimakit-for-wp' ),
								)
							);
						}
					} else {
						// Handle the case where the slug already exists (e.g., display an error message or log it)
						wp_send_json_success(
							array(
								'success' => true,
								'message' => __( 'The custom post type with this slug already exists.', 'ultimakit-for-wp' ),
							)
						);
					}
				}
			} else {
				wp_send_json_error(
					array(
						'success' => false,
						'message' => __( 'Error: Data decoding failed.', 'ultimakit-for-wp' ),
					)
				);
			}
		} else {
			wp_send_json_error(
				array(
					'success' => false,
					'message' => __( 'No form data received.', 'ultimakit-for-wp' ),
				)
			);
		}

		wp_die();
	}

	public function register_custom_post_type() {
		global $wpdb;

		$result = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM $this->cpt_table WHERE active = %d",
				1
			)
		);

		$reg_post_types = array();
		$post_slug      = '';
		if ( ! empty( $result ) ) {
			foreach ( $result as $post_type ) {
				$post_slug         = $post_type->post_type_slug;
				$reg_post_types    = array(); // Array to hold arguments for register_post_type
				$capabilities      = array(); // Array to hold capabilities
				$skip_capabilities = false; // Flag to skip capabilities array

				// Check if post type settings are available
				if ( ! empty( $post_type->settings ) ) {
					// Decode the JSON settings from the database
					$settings = json_decode( $post_type->settings, true );

					// Labels
					$labels = array();

					foreach ( $settings as $key => $value ) {
						// Skip elements with 'xx_' prefix
						if ( strpos( $key, 'xx_' ) === 0 ) {
							continue; // Skip this element
						}

						// Convert 'yes' values to true, else false
						if ( $value === 'yes' ) {
							$value = true;
						}

						// Process label fields
						if ( strpos( $key, 'lab_' ) === 0 ) {
							$labels[ str_replace( 'lab_', '', $key ) ] = $value;
						}
						// Process rewrite rules
						elseif ( strpos( $key, 'rew_' ) === 0 ) {
							$reg_post_types['rewrite'][ str_replace( 'rew_', '', $key ) ] = $value;
						}
						// Process capabilities fields
						elseif ( strpos( $key, 'caps_' ) === 0 ) {
							$capabilities[ str_replace( 'caps_', '', $key ) ] = $value;
						}
						// Check if 'xx_capabilities' is set to 'base' to skip capabilities
						elseif ( $key === 'xx_capabilities' && $value === 'base' ) {
							$skip_capabilities = true;
						}
						// Handle other key-value pairs (supports, taxonomies, etc.)
						elseif ( $key === 'supports' || $key === 'taxonomies' ) {
							$reg_post_types[ $key ] = explode( ',', $value ); // Convert comma-separated values to array
						}
						// Handle special cases, like archives
						elseif ( $key === 'enable_archives' ) {
							if ( $value === 'yes_default' ) {
								$reg_post_types['has_archive'] = true; // Default to true
							} else {
								$reg_post_types['has_archive'] = $value; // Custom value
							}
						}
						// Handle other settings directly
						else {
							$reg_post_types[ $key ] = $value;
						}
					}

					// Move name/singular_name from top-level into labels (not valid as top-level CPT args)
					if ( isset( $reg_post_types['singular_name'] ) ) {
						$labels['singular_name'] = $reg_post_types['singular_name'];
						unset( $reg_post_types['singular_name'] );
					}
					if ( isset( $reg_post_types['name'] ) ) {
						$labels['name'] = $reg_post_types['name'];
						unset( $reg_post_types['name'] );
					}

					// Set labels
					$reg_post_types['labels'] = $labels;

					// If 'xx_capabilities' is not 'base' and capabilities array is not empty, assign it
					if ( ! $skip_capabilities && ! empty( $capabilities ) ) {
						$reg_post_types['capabilities'] = $capabilities;
						$reg_post_types['map_meta_cap'] = true;

						// Remove default capability_type if capabilities are set
						if ( isset( $reg_post_types['capability_type'] ) ) {
							unset( $reg_post_types['capability_type'] );
						}
					}

					// Stored settings must never supply callbacks, classes or internal flags.
					$reg_post_types = $this->remove_unsafe_register_args( $reg_post_types );

					// Register the post type dynamically
					register_post_type( $post_slug, $reg_post_types );
				}
			}
		}
	}

	public function generate_custom_post_type_list() {
		$object = new UltimaKit_Helpers();
		global $wpdb;
		?>
		<div class="wrap">
			<?php $object->ultimakit_get_header(); ?>
			<div class="container bg-white text-dark p-3 mb-3">
				<!-- Nav tabs -->
				<ul class="nav nav-tabs" id="wpukTabs" role="tablist">
					<li class="nav-item" role="presentation">
						<a class="nav-link active" id="settings-tab" data-bs-toggle="tab" href="#custom-post-types" role="tab" aria-controls="custom-js-css-settings" aria-selected="true"><?php echo esc_html_e( 'Post Types', 'ultimakit-for-wp' ); ?></a>
					</li>
					<li class="nav-item" role="presentation">
						<a class="nav-link" id="taxonomies-tab" data-bs-toggle="tab" href="#custom-taxonomies" role="tab" aria-controls="custom-js-css-settings" aria-selected="true"><?php echo esc_html_e( 'Taxonomies', 'ultimakit-for-wp' ); ?></a>
					</li>
				</ul>


				<!-- Tab panes -->
				<div class="tab-content" id="wpukTabsContent">
					<p style="color:red">*<?php echo __( 'Refresh the permalinks after updating or adding a custom post type or custom taxonomy.', 'ultimakit-for-wp' ); ?></p>
					
					<div class="tab-pane fade show active" id="custom-post-types" role="tabpanel" aria-labelledby="settings-tab">
						<!-- Your modules content here -->
						<div class="row">
							<div id="snippets-container">
								<?php
									$all_cpts = $wpdb->get_results( "SELECT * FROM $this->cpt_table" );
								if ( ! empty( $all_cpts ) ) {
									?>
												<table class="table table-striped table-bordered text-left snippet-list">
													<thead>
														<tr>
															<th class="text-end"><?php echo esc_html_e( 'Post Type', 'ultimakit-for-wp' ); ?></th>
															<th class="text-end"><?php echo esc_html_e( 'Slug', 'ultimakit-for-wp' ); ?></th>
															<th class="text-end"><?php echo esc_html_e( 'Status', 'ultimakit-for-wp' ); ?></th>
															<th class="text-end"><?php echo esc_html_e( 'Action', 'ultimakit-for-wp' ); ?></th>
														</tr>
													</thead>
													<tbody>
											<?php
											foreach ( $all_cpts as $cpt ) {
												?>
														<tr>
															<td class="text-end"><?php echo esc_html( $cpt->post_type_label ); ?></td>
															<td class="text-end"><?php echo esc_html( $cpt->post_type_slug ); ?></td>
															<td class="text-end">
															<form class="snippets-button">
																<div class="form-check form-switch module-switch"><input 
																<?php
																if ( $cpt->active ) {
																	echo 'checked'; }
																?>
																class="form-check-input cpt_status" type="checkbox" data-id="<?php echo esc_attr( $cpt->id ); ?>" id="cpt_status_<?php echo esc_attr( $cpt->id ); ?>" name="cpt_status_<?php echo esc_attr( $cpt->id ); ?>" > <label class="form-check-label switch-label" for="cpt_status_<?php echo esc_attr( $cpt->id ); ?>">toggle me</label></div>
															</form>
															</td>
															<td class="text-end">
																<a href="<?php echo admin_url( 'admin.php?page=wp-ultimakit-custom-post-type' ); ?>&mode=edit&cpt_id=<?php echo esc_attr( $cpt->id ); ?>" class="ultimakit_cpt_action" data-id="<?php echo esc_attr( $cpt->id ); ?>">
																	<span class="dashicons dashicons-edit"></span>
																</a>
																<a href="javascript:void(0);" class="ultimakit_cpt_action" data-id="<?php echo esc_attr( $cpt->id ); ?>" data-mode="delete">
																	<span class="dashicons dashicons-trash"></span>
																</a>
															</td>
														</tr>
											<?php } ?>
													</tbody>
												</table>
											<?php
								} else {
									echo esc_html_e( 'No post types found.', 'ultimakit-for-wp' );
								}
								?>
							</div>
							<a href="<?php echo admin_url( 'admin.php?page=wp-ultimakit-custom-post-type' ); ?>" id="create-custom-post-type" class="btn btn-primary"><?php echo esc_html_e( 'Create Custom Post Type', 'ultimakit-for-wp' ); ?></a>
							<a href="<?php echo admin_url( 'admin.php?page=wp-ultimakit-custom-taxonomies' ); ?>" id="create-custom-taxonomies" class="btn btn-primary"><?php echo esc_html_e( 'Create Custom Taxonomy', 'ultimakit-for-wp' ); ?></a>
						</div>
					</div>

					<div class="tab-pane fade show" id="custom-taxonomies" role="tabpanel" aria-labelledby="taxonomies-tab">
						<!-- Your modules content here -->
						<div class="row">
							<div id="snippets-container">
								<?php
									$all_ctx = $wpdb->get_results( "SELECT * FROM $this->ctx_table" );
								if ( ! empty( $all_ctx ) ) {
									?>
												<table class="table table-striped table-bordered text-left snippet-list">
													<thead>
														<tr>
															<th class="text-end"><?php echo esc_html_e( 'Taxonomy', 'ultimakit-for-wp' ); ?></th>
															<th class="text-end"><?php echo esc_html_e( 'Slug', 'ultimakit-for-wp' ); ?></th>
															<th class="text-end"><?php echo esc_html_e( 'Status', 'ultimakit-for-wp' ); ?></th>
															<th class="text-end"><?php echo esc_html_e( 'Action', 'ultimakit-for-wp' ); ?></th>
														</tr>
													</thead>
													<tbody>
											<?php
											foreach ( $all_ctx as $cpt ) {
												?>
														<tr>
															<td class="text-end"><?php echo esc_html( $cpt->taxonomy_label ); ?></td>
															<td class="text-end"><?php echo esc_html( $cpt->taxonomy_slug ); ?></td>
															<td class="text-end">
															<form class="snippets-button">
																<div class="form-check form-switch module-switch"><input 
																<?php
																if ( $cpt->active ) {
																	echo 'checked'; }
																?>
																class="form-check-input ctx_status" type="checkbox" data-id="<?php echo esc_attr( $cpt->id ); ?>" id="ctx_status_<?php echo esc_attr( $cpt->id ); ?>" name="ctx_status_<?php echo esc_attr( $cpt->id ); ?>" > <label class="form-check-label switch-label" for="ctx_status_<?php echo esc_attr( $cpt->id ); ?>">toggle me</label></div>
															</form>
															</td>
															<td class="text-end">
																<a href="<?php echo admin_url( 'admin.php?page=wp-ultimakit-custom-taxonomies' ); ?>&mode=edit&ctx_id=<?php echo esc_attr( $cpt->id ); ?>" class="ultimakit_ctx_action" data-id="<?php echo esc_attr( $cpt->id ); ?>">
																	<span class="dashicons dashicons-edit"></span>
																</a>
																<a href="javascript:void(0);" class="ultimakit_ctx_action" data-id="<?php echo esc_attr( $cpt->id ); ?>" data-mode="delete">
																	<span class="dashicons dashicons-trash"></span>
																</a>
															</td>
														</tr>
											<?php } ?>
													</tbody>
												</table>
											<?php
								} else {
									echo esc_html_e( 'No taxonomies found.', 'ultimakit-for-wp' );
								}
								?>
							</div>
							<a href="<?php echo admin_url( 'admin.php?page=wp-ultimakit-custom-post-type' ); ?>" id="create-custom-post-type" class="btn btn-primary"><?php echo esc_html_e( 'Create Custom Post Type', 'ultimakit-for-wp' ); ?></a>
							<a href="<?php echo admin_url( 'admin.php?page=wp-ultimakit-custom-taxonomies' ); ?>" id="create-custom-taxonomies" class="btn btn-primary"><?php echo esc_html_e( 'Create Custom Taxonomy', 'ultimakit-for-wp' ); ?></a>
						</div>
					</div>

				</div>
			</div>
		</div>
		<?php
	}

	public function delete_cpt() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'You do not have sufficient permissions', 403 );
		}

		// Verify the nonce
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'ultimakit-custom-post-type' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'ultimakit-for-wp' ) ), 401 );
		}

		$id = isset( $_POST['id'] ) ? intval( $_POST['id'] ) : 0;

		global $wpdb;
		$table_name = $this->cpt_table;  // The table from which the record will be deleted

		// Prepare the SQL query to prevent SQL injection
		$sql = $wpdb->prepare(
			"DELETE FROM `{$table_name}` WHERE `id` = %d",
			$id  // Ensure $id is obtained securely and is validated as an integer
		);

		// Execute the query
		$success = $wpdb->query( $sql );

		// Check for success/failure
		if ( $success !== false ) {
			wp_send_json_success( __( 'Post Type deleted successfully.', 'ultimakit-for-wp' ) );
		} else {
			wp_send_json_error( __( 'Error in deleting record:', 'ultimakit-for-wp' ) . ' ' . $wpdb->last_error );
		}
	}

	public function change_cpt_status() {

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'You do not have sufficient permissions', 403 );
		}

		// Verify the nonce
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'ultimakit-custom-post-type' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'ultimakit-for-wp' ) ), 401 );
		}

		$id = isset( $_POST['id'] ) ? intval( $_POST['id'] ) : 0;

		// Check if 'status' is set in $_POST and if it is exactly 'true'
		$status = ( isset( $_POST['status'] ) && $_POST['status'] === '1' ) ? 1 : 0;

		global $wpdb;
		$table_name = $this->cpt_table;

		$updated = $wpdb->update(
			$table_name,
			array( 'active' => $status ), // Set the status to 1 or 0
			array( 'id' => $id ),         // Where ID matches
			array( '%d' ),                // Status is an integer
			array( '%d' )                 // ID is an integer
		);

		// Check for success/failure
		if ( $updated !== false ) {
			wp_send_json_success( __( 'Custom Post Type status changed successfully.', 'ultimakit-for-wp' ) );
		} else {
			wp_send_json_error( __( 'Error in updating status:', 'ultimakit-for-wp' ) . ' ' . $wpdb->last_error );
		}
	}

	public function get_cpt_ctx_data() {
		global $wpdb;

		// Verify user permissions
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'You do not have sufficient permissions.', 403 );
			wp_die();
		}

		// Verify nonce for security
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'ultimakit-custom-post-type' ) ) {
			wp_send_json_error( 'Unauthorized request.', 401 );
			wp_die();
		}

		// Get and sanitize the CPT ID
		$cpt_id = isset( $_POST['cpt_id'] ) ? intval( $_POST['cpt_id'] ) : 0;
		$ctx_id = isset( $_POST['ctx_id'] ) ? intval( $_POST['ctx_id'] ) : 0;
		$type   = isset( $_POST['type'] ) ? $_POST['type'] : 'cpt';
		$data   = '';

		if ( $cpt_id <= 0 ) {
			wp_send_json_error( 'Invalid CPT ID.', 400 );
			wp_die();
		}

		if ( 'ctx' == $type ) {
			// Define the custom table name
			$table_name = $wpdb->prefix . 'ultimakit_ctx';  // Assuming 'ultimakit_ctx' is your custom table

			// Fetch the CPT data from the custom table
			$data = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT * FROM $table_name WHERE id = %d",
					$ctx_id
				)
			);
		} else {
			// Define the custom table name
			$table_name = $wpdb->prefix . 'ultimakit_cpt';  // Assuming 'ultimakit_cpt' is your custom table

			// Fetch the CPT data from the custom table
			$data = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT * FROM $table_name WHERE id = %d",
					$cpt_id
				)
			);
		}

		// Check if the data was found
		if ( $data ) {
			// Return the CPT data as JSON
			wp_send_json_success( $data );
		} else {
			wp_send_json_error( 'Data not found.' );
		}

		wp_die(); // Required to terminate AJAX execution properly
	}

	public function generate_custom_taxonomy() {
		$object = new UltimaKit_Helpers();
		?>
		<div class="wrap">
			<?php $object->ultimakit_get_header(); ?>
			<div class="container bg-white text-dark p-3 mb-3">
				<!-- Nav tabs -->
				<ul class="nav nav-tabs" id="wpukTabs" role="tablist">
					<li class="nav-item" role="presentation">
						<a class="nav-link active" id="general-tab" data-bs-toggle="tab" href="#custom-taxonomy-general" role="tab" aria-controls="custom-taxonomy-general" aria-selected="true"><?php echo esc_html_e( 'General', 'ultimakit-for-wp' ); ?></a>
					</li>
					<li class="nav-item" role="presentation">
						<a class="nav-link" id="labels-tab" data-bs-toggle="tab" href="#custom-taxonomy-labels" role="tab" aria-controls="custom-taxonomy-labels" aria-selected="true"><?php echo esc_html_e( 'Labels', 'ultimakit-for-wp' ); ?></a>
					</li>
					<li class="nav-item" role="presentation">
						<a class="nav-link " id="visibility-tab" data-bs-toggle="tab" href="#custom-taxonomy-visibility" role="tab" aria-controls="custom-taxonomy-visibility" aria-selected="true"><?php echo esc_html_e( 'Visibility', 'ultimakit-for-wp' ); ?></a>
					</li>
					<li class="nav-item" role="presentation">
						<a class="nav-link" id="query-tab" data-bs-toggle="tab" href="#custom-taxonomy-query" role="tab" aria-controls="custom-taxonomy-query" aria-selected="true"><?php echo esc_html_e( 'Query', 'ultimakit-for-wp' ); ?></a>
					</li>
					<li class="nav-item" role="presentation">
						<a class="nav-link" id="permalinks-tab" data-bs-toggle="tab" href="#custom-taxonomy-permalinks" role="tab" aria-controls="custom-taxonomy-permalinks" aria-selected="true"><?php echo esc_html_e( 'Permalinks', 'ultimakit-for-wp' ); ?></a>
					</li>
					<li class="nav-item" role="presentation">
						<a class="nav-link" id="capabilities-tab" data-bs-toggle="tab" href="#custom-taxonomy-capabilities" role="tab" aria-controls="custom-taxonomy-capabilities" aria-selected="true"><?php echo esc_html_e( 'Capabilities', 'ultimakit-for-wp' ); ?></a>
					</li>
					<li class="nav-item" role="presentation">
						<a class="nav-link" id="restapi-tab" data-bs-toggle="tab" href="#custom-taxonomy-restapi" role="tab" aria-controls="custom-taxonomy-restapi" aria-selected="true"><?php echo esc_html_e( 'Rest API', 'ultimakit-for-wp' ); ?></a>
					</li>
				</ul>

				<!-- Tab panes -->
				<div class="tab-content" id="wpukTabsContent">
					<div class="tab-pane fade show active" id="custom-taxonomy-general" role="tabpanel" aria-labelledby="general-tab">
						<!-- Your modules content here -->
						<div class="row">
							<?php
								$arguments          = array();
								$arguments['ID']    = $this->ID;
								$arguments['title'] = __( 'Post Type', 'ultimakit-for-wp' );

								$arguments['fields'] = array(
									'singular_name' => array(
										'type'     => 'text',
										'label'    => __( 'Name (Singular)', 'ultimakit-for-wp' ),
										'value'    => 'Taxonomy',
										'desc'     => __( 'Taxonomy singular name.', 'ultimakit-for-wp' ),
										'required' => 'required',
									),
									'name'          => array(
										'type'     => 'text',
										'label'    => __( 'Name (Plural)', 'ultimakit-for-wp' ),
										'value'    => 'Taxonomies',
										'desc'     => __( 'Taxonomy plural name.', 'ultimakit-for-wp' ),
										'required' => 'required',
									),
									'rew_slug'      => array(
										'type'     => 'text',
										'label'    => __( 'Key', 'ultimakit-for-wp' ),
										'value'    => 'taxonomy',
										'desc'     => __( 'Key used in the code. Up to 32 characters, lowercase.', 'ultimakit-for-wp' ),
										'required' => 'required',
									),
									'link_to_post'  => array(
										'type'     => 'text',
										'label'    => __( 'Link To Post Type(s)', 'ultimakit-for-wp' ),
										'value'    => 'post',
										'desc'     => __( 'Comma separated list of <a href="https://codex.wordpress.org/Post_Types" target="_blank">Post Types.</a>', 'ultimakit-for-wp' ),
										'required' => '',
									),
									'hierarchical'  => array(
										'type'     => 'select',
										'label'    => __( 'Hierarchical', 'ultimakit-for-wp' ),
										'options'  => array(
											false => 'No (like tags)',
											true  => 'Yes (like categories)',
										),
										'desc'     => __( 'Hierarchical taxonomy allows descendants.', 'ultimakit-for-wp' ),
										'required' => '',
									),
								);

								$this->ultimakit_generate_form( $arguments );

								?>
						</div>
					</div>

					<div class="tab-pane fade show" id="custom-taxonomy-labels" role="tabpanel" aria-labelledby="labels-tab">
						<!-- Your modules content here -->
						<div class="row">
							<?php
								$arguments          = array();
								$arguments['ID']    = $this->ID;
								$arguments['title'] = __( 'Labels', 'ultimakit-for-wp' );

								$arguments['fields'] = array(
									'lab_menu_name'     => array(
										'type'     => 'text',
										'label'    => __( 'Menu Name', 'ultimakit-for-wp' ),
										'value'    => __( 'Taxonomy', 'ultimakit-for-wp' ),
										'required' => false,
									),
									'lab_new_item_name' => array(
										'type'     => 'text',
										'label'    => __( 'New Item Name', 'ultimakit-for-wp' ),
										'value'    => __( 'New Item Name', 'ultimakit-for-wp' ),
										'required' => false,
									),
									'lab_separate_items_with_commas' => array(
										'type'     => 'text',
										'label'    => __( 'Separate Items with commas', 'ultimakit-for-wp' ),
										'value'    => __( 'Separate Items with commas', 'ultimakit-for-wp' ),
										'required' => false,
									),
									'lab_search_items'  => array(
										'type'     => 'text',
										'label'    => __( 'Search Items', 'ultimakit-for-wp' ),
										'value'    => __( 'Search Items', 'ultimakit-for-wp' ),
										'required' => false,
									),
									'lab_all_items'     => array(
										'type'     => 'text',
										'label'    => __( 'All Items', 'ultimakit-for-wp' ),
										'value'    => __( 'All Items', 'ultimakit-for-wp' ),
										'required' => false,
									),
									'lab_add_new_item'  => array(
										'type'     => 'text',
										'label'    => __( 'Add New Item', 'ultimakit-for-wp' ),
										'value'    => __( 'Add New Item', 'ultimakit-for-wp' ),
										'required' => false,
									),
									'lab_add_or_remove_items' => array(
										'type'     => 'text',
										'label'    => __( 'Add or Remove Items', 'ultimakit-for-wp' ),
										'value'    => __( 'Add or Remove Items', 'ultimakit-for-wp' ),
										'required' => false,
									),
									'lab_not_found'     => array(
										'type'     => 'text',
										'label'    => __( 'Not Found', 'ultimakit-for-wp' ),
										'value'    => __( 'Not Found', 'ultimakit-for-wp' ),
										'required' => false,
									),
									'lab_parent_item'   => array(
										'type'     => 'text',
										'label'    => __( 'Parent Item', 'ultimakit-for-wp' ),
										'value'    => __( 'Parent Item', 'ultimakit-for-wp' ),
										'required' => false,
									),
									'lab_edit_item'     => array(
										'type'     => 'text',
										'label'    => __( 'Edit Item', 'ultimakit-for-wp' ),
										'value'    => __( 'Edit Item', 'ultimakit-for-wp' ),
										'required' => false,
									),
									'lab_choose_from_most_used' => array(
										'type'     => 'text',
										'label'    => __( 'Choose From Most Used', 'ultimakit-for-wp' ),
										'value'    => __( 'Choose From Most Used', 'ultimakit-for-wp' ),
										'required' => false,
									),
									'lab_no_terms'      => array(
										'type'     => 'text',
										'label'    => __( 'No items', 'ultimakit-for-wp' ),
										'value'    => __( 'No items', 'ultimakit-for-wp' ),
										'required' => false,
									),
									'lab_parent_item_colon' => array(
										'type'     => 'text',
										'label'    => __( 'Parent Item (colon)', 'ultimakit-for-wp' ),
										'value'    => __( 'Parent Item:', 'ultimakit-for-wp' ),
										'required' => false,
									),
									'lab_update_item'   => array(
										'type'     => 'text',
										'label'    => __( 'Update Item', 'ultimakit-for-wp' ),
										'value'    => __( 'Update Item', 'ultimakit-for-wp' ),
										'required' => false,
									),
									'lab_view_item'     => array(
										'type'     => 'text',
										'label'    => __( 'View Item', 'ultimakit-for-wp' ),
										'value'    => __( 'View Item', 'ultimakit-for-wp' ),
										'required' => false,
									),
									'lab_popular_items' => array(
										'type'     => 'text',
										'label'    => __( 'Popular Items', 'ultimakit-for-wp' ),
										'value'    => __( 'Popular Items', 'ultimakit-for-wp' ),
										'required' => false,
									),
									'lab_items_list'    => array(
										'type'     => 'text',
										'label'    => __( 'Items list', 'ultimakit-for-wp' ),
										'value'    => __( 'Items list', 'ultimakit-for-wp' ),
										'required' => false,
									),
									'lab_item_list_navigation' => array(
										'type'     => 'text',
										'label'    => __( 'Items list navigation', 'ultimakit-for-wp' ),
										'value'    => __( 'Items list navigation', 'ultimakit-for-wp' ),
										'required' => false,
									),
								);
								$this->ultimakit_generate_form( $arguments );
								?>
						</div>
					</div>

					<?php
						// Check if 'cpt_id' exists in the URL and sanitize it
						$cpt_id = isset( $_GET['cpt_id'] ) ? esc_attr( $_GET['cpt_id'] ) : '';
					?>
					<input type="hidden" value="<?php echo $cpt_id; ?>" id="cpt_id">

					<div class="tab-pane fade show " id="custom-taxonomy-visibility" role="tabpanel" aria-labelledby="custom-taxonomy-visibility">
						<!-- Your modules content here -->
						<div class="row">
							<?php
								$arguments          = array();
								$arguments['ID']    = $this->ID;
								$arguments['title'] = __( 'Visibility', 'ultimakit-for-wp' );

								$arguments['fields'] = array(
									'public'            => array(
										'type'     => 'select',
										'label'    => __( 'Public', 'ultimakit-for-wp' ),
										'options'  => array(
											'yes' => __( 'Yes', 'ultimakit-for-wp' ),
											'no'  => __( 'No', 'ultimakit-for-wp' ),
										),
										'default'  => 'yes',
										'desc'     => __( 'Show this taxonomy in the admin UI.										', 'ultimakit-for-wp' ),
										'required' => false,
									),
									'show_ui'           => array(
										'type'     => 'select',
										'label'    => __( 'Show UI', 'ultimakit-for-wp' ),
										'options'  => array(
											'yes' => __( 'Yes', 'ultimakit-for-wp' ),
											'no'  => __( 'No', 'ultimakit-for-wp' ),
										),
										'default'  => 'yes',
										'desc'     => __( 'Show taxonomy managing UI in the admin.', 'ultimakit-for-wp' ),
										'required' => false,
									),
									'show_tagcloud'     => array(
										'type'     => 'select',
										'label'    => __( 'Show Tag Cloud', 'ultimakit-for-wp' ),
										'options'  => array(
											'yes' => __( 'Yes', 'ultimakit-for-wp' ),
											'no'  => __( 'No', 'ultimakit-for-wp' ),
										),
										'default'  => 'yes',
										'desc'     => __( 'Show in tag cloud widget.', 'ultimakit-for-wp' ),
										'required' => false,
									),
									'show_admin_column' => array(
										'type'     => 'select',
										'label'    => __( 'Show Admin Column', 'ultimakit-for-wp' ),
										'options'  => array(
											'yes' => __( 'Yes', 'ultimakit-for-wp' ),
											'no'  => __( 'No', 'ultimakit-for-wp' ),
										),
										'default'  => 'yes',
										'desc'     => __( 'Show taxonomy columns on associated post-types.', 'ultimakit-for-wp' ),
										'required' => false,
									),
									'show_in_nav_menus' => array(
										'type'     => 'select',
										'label'    => __( 'Show in Navigation Menus', 'ultimakit-for-wp' ),
										'options'  => array(
											'yes' => __( 'Yes', 'ultimakit-for-wp' ),
											'no'  => __( 'No', 'ultimakit-for-wp' ),
										),
										'default'  => 'yes',
										'desc'     => __( 'Taxonomy available for selection in <a href="https://codex.wordpress.org/Navigation_Menus" target="_blank">Navigation Menus</a>', 'ultimakit-for-wp' ),
										'required' => false,
									),
								);
								$this->ultimakit_generate_form( $arguments );
								?>
						</div>
					</div>

					<div class="tab-pane fade show " id="custom-taxonomy-query" role="tabpanel" aria-labelledby="custom-taxonomy-query">
						<!-- Your modules content here -->
						<div class="row">
							<?php
								$arguments          = array();
								$arguments['ID']    = $this->ID;
								$arguments['title'] = __( 'Query', 'ultimakit-for-wp' );

								$arguments['fields'] = array(
									'xx_query'  => array(
										'type'     => 'select',
										'label'    => __( 'Public', 'ultimakit-for-wp' ),
										'options'  => array(
											'false' => __( 'Default (taxonomy key)', 'ultimakit-for-wp' ),
											'true'  => __( 'Custom query variable', 'ultimakit-for-wp' ),
										),
										'default'  => 'false',
										'desc'     => __( "Direct query variable used in WP_Query. e.g. <a href='https://codex.wordpress.org/Class_Reference/WP_Query#Taxonomy_Parameters' target='_blank'>WP_Query</a>( array( <strong>'taxonomy' => 'genre'</strong>, 'term' => 'comedy' ) )", 'ultimakit-for-wp' ),
										'required' => false,
									),
									'query_var' => array(
										'type'     => 'text',
										'label'    => __( 'Custom Query', 'ultimakit-for-wp' ),
										'desc'     => __( 'Custom query variable.', 'ultimakit-for-wp' ),
										'required' => false,
									),
								);
								$this->ultimakit_generate_form( $arguments );
								?>
						</div>
					</div>

					<div class="tab-pane fade show " id="custom-taxonomy-permalinks" role="tabpanel" aria-labelledby="custom-taxonomy-permalinks">
						<!-- Your modules content here -->
						<div class="row">
							<?php
								$arguments          = array();
								$arguments['ID']    = $this->ID;
								$arguments['title'] = __( 'Permalinks', 'ultimakit-for-wp' );

								$arguments['fields'] = array(
									'xx_permalink_query' => array(
										'type'     => 'select',
										'label'    => __( 'Permalink Rewrite', 'ultimakit-for-wp' ),
										'options'  => array(
											'no'      => __( 'No permalink (prevent URL rewriting)', 'ultimakit-for-wp' ),
											'default' => __( 'Default permalink (taxonomy key)', 'ultimakit-for-wp' ),
											'custom'  => __( 'Custom permalink', 'ultimakit-for-wp' ),
										),
										'default'  => 'false',
										'desc'     => __( 'Use Default <a href="https://codex.wordpress.org/Using_Permalinks" target="_blank">Permalinks</a> (using taxonomy key), prevent automatic URL rewriting (no pretty permalinks), or set custom permalinks.', 'ultimakit-for-wp' ),
										'required' => false,
									),
									'url_slug'           => array(
										'type'        => 'text',
										'label'       => __( 'URL Slug', 'ultimakit-for-wp' ),
										'placeholder' => __( 'taxonomy', 'ultimakit-for-wp' ),
										'desc'        => __( 'Pretty permalink base rewrite text. i.e. www.example.com/ganer/', 'ultimakit-for-wp' ),
										'required'    => false,
									),
									'rew_with_front'     => array(
										'type'     => 'select',
										'label'    => __( 'Use URL Slug', 'ultimakit-for-wp' ),
										'options'  => array(
											'yes' => __( 'Yes', 'ultimakit-for-wp' ),
											'no'  => __( 'No', 'ultimakit-for-wp' ),
										),
										'default'  => 'yes',
										'desc'     => __( 'Use taxonomy slug as URL base. <br />Default: Yes ', 'ultimakit-for-wp' ),
										'required' => false,
									),
									'rew_hierarchical'   => array(
										'type'     => 'select',
										'label'    => __( 'Hierarchical URL Slug', 'ultimakit-for-wp' ),
										'options'  => array(
											'yes' => __( 'Yes', 'ultimakit-for-wp' ),
											'no'  => __( 'No', 'ultimakit-for-wp' ),
										),
										'default'  => 'yes',
										'desc'     => __( 'Allow hierarchical URLs. <br />Default: Yes ', 'ultimakit-for-wp' ),
										'required' => false,
									),
								);
								$this->ultimakit_generate_form( $arguments );
								?>
						</div>
					</div>

					<div class="tab-pane fade show" id="custom-taxonomy-capabilities" role="tabpanel" aria-labelledby="custom-taxonomy-capabilities">
						<!-- Your modules content here -->
						<div class="row">
							<?php
								$arguments          = array();
								$arguments['ID']    = $this->ID;
								$arguments['title'] = __( 'Capabilities', 'ultimakit-for-wp' );

								$arguments['fields'] = array(
									'xx_capabilities'   => array(
										'type'     => 'select',
										'label'    => __( 'Capabilities', 'ultimakit-for-wp' ),
										'options'  => array(
											'default' => __( 'Default', 'ultimakit-for-wp' ),
											'custom'  => __( 'Custom capabilities', 'ultimakit-for-wp' ),
										),
										'default'  => 'default',
										'desc'     => __( 'Set custom <a href="https://wordpress.org/documentation/article/roles-and-capabilities/" target="_blank">user capabilities</a> to manage taxonomy. Default: category capabilities', 'ultimakit-for-wp' ),
										'required' => false,
									),
									'caps_edit_terms'   => array(
										'type'        => 'text',
										'label'       => __( 'Edit Terms', 'ultimakit-for-wp' ),
										'placeholder' => __( 'manage_categories', 'ultimakit-for-wp' ),
										'value'       => '',
										'required'    => false,
									),
									'caps_manage_terms' => array(
										'type'        => 'text',
										'label'       => __( 'Manage Terms', 'ultimakit-for-wp' ),
										'placeholder' => __( 'manage_categories', 'ultimakit-for-wp' ),
										'value'       => '',
										'required'    => false,
									),
									'caps_delete_terms' => array(
										'type'        => 'text',
										'label'       => __( 'Delete Terms', 'ultimakit-for-wp' ),
										'placeholder' => __( 'manage_categories', 'ultimakit-for-wp' ),
										'value'       => '',
										'required'    => false,
									),
									'caps_assign_terms' => array(
										'type'        => 'text',
										'label'       => __( 'Assign Terms', 'ultimakit-for-wp' ),
										'placeholder' => __( 'assign_terms', 'ultimakit-for-wp' ),
										'value'       => '',
										'required'    => false,
									),

								);
								$this->ultimakit_generate_form( $arguments );
								?>
						</div>
					</div>

					<div class="tab-pane fade show " id="custom-taxonomy-restapi" role="tabpanel" aria-labelledby="custom-taxonomy-restapi">
						<!-- Your modules content here -->
						<div class="row">
							<?php
								$arguments          = array();
								$arguments['ID']    = $this->ID;
								$arguments['title'] = __( 'Visibility', 'ultimakit-for-wp' );

								$arguments['fields'] = array(
									'show_in_rest' => array(
										'type'     => 'select',
										'label'    => __( 'Show in Rest', 'ultimakit-for-wp' ),
										'options'  => array(
											''    => __( 'Choose', 'ultimakit-for-wp' ),
											'yes' => __( 'Yes', 'ultimakit-for-wp' ),
											'no'  => __( 'No', 'ultimakit-for-wp' ),
										),
										'default'  => 'yes',
										'desc'     => __( 'Whether to include the taxonomy in the REST API.', 'ultimakit-for-wp' ),
										'required' => false,
									),
									'rest_base'    => array(
										'type'     => 'text',
										'label'    => __( 'Rest Base', 'ultimakit-for-wp' ),
										'desc'     => __( 'To change the base url of REST API route. Default is the taxonomy key.', 'ultimakit-for-wp' ),
										'required' => false,
									),
									'rest_controller_class' => array(
										'type'     => 'text',
										'label'    => __( 'Rest Controller Class', 'ultimakit-for-wp' ),
										'desc'     => __( 'REST API Controller class name. Default is "WP_REST_Terms_Controller"', 'ultimakit-for-wp' ),
										'required' => false,
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

	public function save_custom_taxonomy() {

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'You do not have sufficient permissions', 403 );
		}

		// Verify the nonce
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'ultimakit-custom-post-type' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'ultimakit-for-wp' ) ), 401 );
		}

		// Check if 'formData' is set in POST request
		if ( isset( $_POST['formData'] ) ) {
			// Decode the JSON string into a PHP array
			$formDataArray = json_decode( stripslashes( $_POST['formData'] ), true );

			$fields_array = array();

			if ( is_array( $formDataArray ) ) {
				foreach ( $formDataArray as $index => $formDataObject ) {
					foreach ( $formDataObject as $key => $value ) {
						if ( substr( $key, 0, 3 ) !== 'xx_' ) {
							$fields_array[ $key ] = $value;
						}
					}
				}

				if ( count( $fields_array ) > 0 ) {
					global $wpdb;

					// Sanitize the key and the label strings before anything is stored (add and edit).
					$slug         = isset( $fields_array['rew_slug'] ) ? sanitize_key( $fields_array['rew_slug'] ) : '';
					$fields_array = $this->sanitize_cpt_ctx_fields( $fields_array, $slug );

					if ( '' === $slug || in_array( $slug, $this->get_reserved_keys( 'ctx' ), true ) ) {
						wp_send_json_error(
							array(
								'success' => false,
								'message' => __( 'This key is empty or reserved by WordPress. Please choose a different key.', 'ultimakit-for-wp' ),
							),
							400
						);
					}

					if ( 'new' === $_POST['mode'] ) {

						// Check for duplicate slug before inserting
						$existing_slug = $wpdb->get_var(
							$wpdb->prepare(
								"SELECT id FROM $this->ctx_table WHERE taxonomy_slug = %s LIMIT 1",
								$slug
							)
						);
						if ( $existing_slug ) {
							wp_send_json_error(
								array(
									'success' => false,
									'message' => __( 'A Custom Taxonomy with this slug already exists.', 'ultimakit-for-wp' ),
								)
							);
						}

						// Insert into the database
						$insert_result = $wpdb->insert(
							$this->ctx_table, // Your Custom Taxonomy table name
							array(
								'taxonomy_slug'  => $slug, // The slug for the Custom Taxonomy
								'taxonomy_label' => $fields_array['singular_name'],  // Singular label for the Custom Taxonomy
								'settings'       => wp_json_encode( $fields_array ), // Store settings as JSON
								'active'         => 1,
								'created_at'     => current_time( 'mysql' ), // Current timestamp
								'updated_at'     => current_time( 'mysql' ), // Current timestamp
							)
						);

						if ( false === $insert_result ) {
							wp_send_json_error(
								array(
									'success' => false,
									'message' => __( 'Error: Failed to create the Custom Taxonomy.', 'ultimakit-for-wp' ),
								)
							);
						}

						flush_rewrite_rules();
						wp_send_json_success(
							array(
								'success' => true,
								'message' => __( 'The Custom Taxonomy created successfully.', 'ultimakit-for-wp' ),
							)
						);

					} elseif ( 'edit' === $_POST['mode'] ) {
							// Check if post_type_slug already exists in the database
							$existing_id = $wpdb->get_var(
								$wpdb->prepare(
									"SELECT id FROM $this->ctx_table WHERE id = %d",
									$_POST['id']
								)
							);

							// Update the existing row
							$update_result = $wpdb->update(
								$this->ctx_table, // Your Custom Taxonomy table name
								array(
									'taxonomy_slug'  => $slug,  // Update the label for the Custom Taxonomy
									'taxonomy_label' => $fields_array['singular_name'],  // Update the label for the Custom Taxonomy
									'settings'       => json_encode( $fields_array ),      // Update the settings as JSON
									'updated_at'     => current_time( 'mysql' ),            // Update the timestamp
								),
								array( 'id' => $existing_id )       // The where clause (which row to update)
							);

						if ( $update_result !== false ) {
							wp_send_json_success(
								array(
									'success' => true,
									'message' => __( 'The Custom Taxonomy updated successfully.', 'ultimakit-for-wp' ),
								)
							);
						} else {
							wp_send_json_error(
								array(
									'success' => false,
									'message' => __( 'Error: Failed to update the Custom Taxonomy.', 'ultimakit-for-wp' ),
								)
							);
						}
					} else {
						// Handle the case where the slug already exists (e.g., display an error message or log it)
						wp_send_json_success(
							array(
								'success' => true,
								'message' => __( 'The custom taxonomy with this slug already exists.', 'ultimakit-for-wp' ),
							)
						);
					}
				}
			} else {
				wp_send_json_error(
					array(
						'success' => false,
						'message' => __( 'Error: Data decoding failed.', 'ultimakit-for-wp' ),
					)
				);
			}
		} else {
			wp_send_json_error(
				array(
					'success' => false,
					'message' => __( 'No form data received.', 'ultimakit-for-wp' ),
				)
			);
		}

		wp_die();
	}

	public function register_custom_taxonomy() {
		global $wpdb;

		$result = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM $this->ctx_table WHERE active = %d",
				1
			)
		);

		$tax_slug = '';

		if ( ! empty( $result ) ) {
			foreach ( $result as $taxonomie ) {
				$tax_slug          = $taxonomie->taxonomy_slug;
				$reg_taxonomies    = array(); // Array to hold arguments for register_taxonomy
				$capabilities      = array(); // Array to hold capabilities
				$skip_capabilities = false; // Flag to skip capabilities array

				// Check if post type settings are available
				if ( ! empty( $taxonomie->settings ) ) {
					// Decode the JSON settings from the database
					$settings = json_decode( $taxonomie->settings, true );

					// Labels
					$labels = array();

					// Resolve linked post types before iterating settings
					$link_to_post = isset( $settings['link_to_post'] ) ? $settings['link_to_post'] : 'post';
					if ( strpos( $link_to_post, ',' ) !== false ) {
						$link_to_post_array = explode( ',', $link_to_post );
					} else {
						$link_to_post_array = array( $link_to_post );
					}

					foreach ( $settings as $key => $value ) {
						// Skip elements with 'xx_' prefix
						if ( strpos( $key, 'xx_' ) === 0 ) {
							continue; // Skip this element
						}

						// Convert 'yes' values to true, else false
						if ( $value === 'yes' ) {
							$value = true;
						} elseif ( $value === 'no' ) {
							$value = false;
						}

						// Process label fields
						if ( strpos( $key, 'lab_' ) === 0 ) {
							$labels[ str_replace( 'lab_', '', $key ) ] = $value;
						}
						// Process rewrite rules
						elseif ( strpos( $key, 'rew_' ) === 0 ) {
							$reg_taxonomies['rewrite'][ str_replace( 'rew_', '', $key ) ] = $value;
						}
						// Process capabilities fields
						elseif ( strpos( $key, 'caps_' ) === 0 ) {
							if ( ! empty( $value ) ) {
								$capabilities[ str_replace( 'caps_', '', $key ) ] = $value;
							}
						}
						// Check if 'xx_capabilities' is set to 'default' to skip custom capabilities
						elseif ( $key === 'xx_capabilities' && $value === 'default' ) {
							$skip_capabilities = true;
						}
						// Handle other settings directly
						elseif ( ! empty( $value ) ) {
							$reg_taxonomies[ $key ] = $value;
						}
					}

					// Set URL slug from settings if available
					if ( isset( $settings['rew_slug'] ) ) {
						$reg_taxonomies['rewrite']['slug'] = $settings['rew_slug'];
					}

					if ( isset( $settings['show_in_rest'] ) && 'yes' === $settings['show_in_rest'] ) {
						$reg_taxonomies['show_in_rest'] = true;
					}

					// Move name/singular_name from top-level into labels (not valid as top-level taxonomy args)
					if ( isset( $reg_taxonomies['singular_name'] ) ) {
						$labels['singular_name'] = $reg_taxonomies['singular_name'];
						unset( $reg_taxonomies['singular_name'] );
					}
					if ( isset( $reg_taxonomies['name'] ) ) {
						$labels['name'] = $reg_taxonomies['name'];
						unset( $reg_taxonomies['name'] );
					}
					// link_to_post is resolved via \$link_to_post_array, not a direct taxonomy arg
					unset( $reg_taxonomies['link_to_post'] );

					// Set labels
					$reg_taxonomies['labels'] = $labels;

					// If 'xx_capabilities' is not 'base' and capabilities array is not empty, assign it
					if ( ! $skip_capabilities && ! empty( $capabilities ) ) {
						$reg_taxonomies['capabilities'] = $capabilities;

						// Remove default capability_type if capabilities are set
						if ( isset( $reg_taxonomies['capability_type'] ) ) {
							unset( $reg_taxonomies['capability_type'] );
						}
					}
					unset( $reg_taxonomies['url_slug'] );
				}

				if ( ! empty( $tax_slug ) ) {
					// Stored settings must never supply callbacks, classes or internal flags.
					$reg_taxonomies = $this->remove_unsafe_register_args( $reg_taxonomies );

					// Register the taxonomy dynamically
					register_taxonomy( $tax_slug, $link_to_post_array, $reg_taxonomies );
				}
			}
		}
	}

	public function delete_ctx() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'You do not have sufficient permissions', 403 );
		}

		// Verify the nonce
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'ultimakit-custom-post-type' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'ultimakit-for-wp' ) ), 401 );
		}

		$id = isset( $_POST['id'] ) ? intval( $_POST['id'] ) : 0;

		global $wpdb;
		$table_name = $this->ctx_table;  // The table from which the record will be deleted

		// Prepare the SQL query to prevent SQL injection
		$sql = $wpdb->prepare(
			"DELETE FROM `{$table_name}` WHERE `id` = %d",
			$id  // Ensure $id is obtained securely and is validated as an integer
		);

		// Execute the query
		$success = $wpdb->query( $sql );

		// Check for success/failure
		if ( $success !== false ) {
			wp_send_json_success( __( 'Taxonomy deleted successfully.', 'ultimakit-for-wp' ) );
		} else {
			wp_send_json_error( __( 'Error in deleting record:', 'ultimakit-for-wp' ) . ' ' . $wpdb->last_error );
		}
	}

	public function change_ctx_status() {

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'You do not have sufficient permissions', 403 );
		}

		// Verify the nonce
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'ultimakit-custom-post-type' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'ultimakit-for-wp' ) ), 401 );
		}

		$id = isset( $_POST['id'] ) ? intval( $_POST['id'] ) : 0;

		// Check if 'status' is set in $_POST and if it is exactly 'true'
		$status = ( isset( $_POST['status'] ) && $_POST['status'] === '1' ) ? 1 : 0;

		global $wpdb;
		$table_name = $this->ctx_table;

		$updated = $wpdb->update(
			$table_name,
			array( 'active' => $status ), // Set the status to 1 or 0
			array( 'id' => $id ),         // Where ID matches
			array( '%d' ),                // Status is an integer
			array( '%d' )                 // ID is an integer
		);

		// Check for success/failure
		if ( $updated !== false ) {
			wp_send_json_success( __( 'Custom Taxonomy status changed successfully.', 'ultimakit-for-wp' ) );
		} else {
			wp_send_json_error( __( 'Error in updating status:', 'ultimakit-for-wp' ) . ' ' . $wpdb->last_error );
		}
	}
}
