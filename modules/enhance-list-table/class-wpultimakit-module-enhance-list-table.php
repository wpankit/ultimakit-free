<?php
/**
 * Class UltimaKit_Module_Enhance_List_Table
 *
 * @since 1.0.0
 * @package    UltimaKit
 */

/**
 * Class UltimaKit_Module_Enhance_List_Table
 *
 * This class provides methods to control the display of dashboard widgets based on user preferences.
 * It allows users to selectively hide certain dashboard widgets to streamline their WordPress dashboard
 * experience and improve usability.
 *
 * @since 1.0.0
 */
class UltimaKit_Module_Enhance_List_Table extends UltimaKit_Module_Manager {
	/**
	 *
	 * @var string
	 */
	protected $ID = 'ultimakit_enhance_list_table';

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
	protected $read_more_link = 'enhance-list-table-in-wordpress';

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
		$this->name        = __( 'Enhance List Tables', 'ultimakit-for-wp' );
		$this->description = __( 'Boost functionality of listing pages by adding or removing columns and elements for posts, media, and users.', 'ultimakit-for-wp' );
		$this->is_active   = $this->isModuleActive( $this->ID );
		$this->settings    = 'yes';
		$this->initializeModule();
		$this->ultimakit_enhance_list_table();
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
	 * This function initialise the features of enhance list table.
	 *
	 * @return mixed
	 */
	public function ultimakit_enhance_list_table() {
		if ( ! $this->is_active ) {
			return false;
		}

		$helper = new UltimaKit_Helpers();
		// Hide featured Image.
		if ( 'on' === $this->getModuleSettings( $this->ID, 'show_featured_image_column' ) ) {
			add_action( 'admin_init', array( $helper, 'show_featured_image_column' ) );
		}

		// Show Excerpt Column.
		if ( 'on' === $this->getModuleSettings( $this->ID, 'show_excerpt_column' ) ) {
			add_action( 'admin_init', array( $helper, 'show_excerpt_column' ) );
		}

		// Show ID Column.
		if ( 'on' === $this->getModuleSettings( $this->ID, 'show_id_column' ) ) {
			add_action( 'admin_init', array( $helper, 'show_id_column' ) );
		}

		// Show ID in Action Row.
		if ( 'on' === $this->getModuleSettings( $this->ID, 'show_id_in_action_row' ) ) {
			add_action( 'admin_init', array( $helper, 'show_id_in_action_row' ) );
		}

		// Show Custom Taxonomy Filters.
		if ( 'on' === $this->getModuleSettings( $this->ID, 'show_custom_taxonomy_filters' ) ) {
			add_action( 'restrict_manage_posts', array( $helper, 'show_custom_taxonomy_filters' ) );
		}

		// Hide Comments column.
		if ( 'on' === $this->getModuleSettings( $this->ID, 'hide_comments_column' ) ) {
			add_action( 'admin_init', array( $helper, 'hide_comments_column' ) );
		}

		// Hide Tags column.
		if ( 'on' === $this->getModuleSettings( $this->ID, 'hide_post_tags_column' ) ) {
			add_action( 'admin_init', array( $helper, 'hide_post_tags_column' ) );
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
		$arguments['title'] = __( 'Enhance List Tables Settings', 'ultimakit-for-wp' );

		$arguments['fields'] = array(
			'show_featured_image_column'   => array(
				'type'  => 'checkbox',
				'label' => __( 'Show featured image column', 'ultimakit-for-wp' ),
				'value' => $this->getModuleSettings( $this->ID, 'show_featured_image_column' ),
			),
			'show_excerpt_column'          => array(
				'type'  => 'checkbox',
				'label' => __( 'Show excerpt column', 'ultimakit-for-wp' ),
				'value' => $this->getModuleSettings( $this->ID, 'show_excerpt_column' ),
			),
			'show_id_column'               => array(
				'type'  => 'checkbox',
				'label' => __( 'Show ID column', 'ultimakit-for-wp' ),
				'value' => $this->getModuleSettings( $this->ID, 'show_id_column' ),
			),
			'show_id_in_action_row'        => array(
				'type'  => 'checkbox',
				'label' => __( 'Show ID in action rows along with links for Edit, View, etc.', 'ultimakit-for-wp' ),
				'value' => $this->getModuleSettings( $this->ID, 'show_id_in_action_row' ),
			),
			'show_custom_taxonomy_filters' => array(
				'type'  => 'checkbox',
				'label' => __( 'Show additional filter(s) for hierarchical, custom taxonomies', 'ultimakit-for-wp' ),
				'value' => $this->getModuleSettings( $this->ID, 'show_custom_taxonomy_filters' ),
			),
			'hide_comments_column'         => array(
				'type'  => 'checkbox',
				'label' => __( 'Remove comments column', 'ultimakit-for-wp' ),
				'value' => $this->getModuleSettings( $this->ID, 'hide_comments_column' ),
			),
			'hide_post_tags_column'        => array(
				'type'  => 'checkbox',
				'label' => __( 'Remove tags column (for posts)', 'ultimakit-for-wp' ),
				'value' => $this->getModuleSettings( $this->ID, 'hide_post_tags_column' ),
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
			'wpuk-module-script-' . $this->ID,
			plugins_url( '/module-script.js', __FILE__ ),
			array( 'jquery' ),
			ULTIMAKIT_FOR_WP_VERSION,
			true
		);
	}
}
