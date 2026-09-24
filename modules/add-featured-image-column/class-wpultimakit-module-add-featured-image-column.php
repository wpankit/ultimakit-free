<?php
/**
 * Class UltimaKit_Module_Add_Featured_Image_Column
 *
 * @since 1.0.0
 * @package    UltimaKit
 */

/**
 * Class UltimaKit_Module_Add_Featured_Image_Column
 *
 * @since 1.0.0
 */
class UltimaKit_Module_Add_Featured_Image_Column extends UltimaKit_Module_Manager {
	/**
	 * @var string
	 */
	protected $ID = 'ultimakit_module_add_featured_image_column';

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
	protected $read_more_link = 'add-featured-image-column-in-wordpress';

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
		$this->name        = __( 'Add Featured Image Column', 'ultimakit-for-wp' );
		$this->description = __( 'Display the featured image in the list of posts in the admin.', 'ultimakit-for-wp' );
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

			add_filter(
				'manage_posts_columns',
				function ( $columns ) {
					// You can change this to any other position by changing 'title' to the name of the column you want to put it after.
					$move_after     = 'title';
					$move_after_key = array_search( $move_after, array_keys( $columns ), true );

					$first_columns = array_slice( $columns, 0, $move_after_key + 1 );
					$last_columns  = array_slice( $columns, $move_after_key + 1 );

					return array_merge(
						$first_columns,
						array(
							'featured_image' => $this->getModuleSettings( $this->ID, 'column_title', __( 'Featured Image', 'ultimakit-for-wp' ) ),
						),
						$last_columns
					);
				}
			);

			add_action(
				'manage_posts_custom_column',
				function ( $column ) {
					if ( 'featured_image' === $column ) {
						the_post_thumbnail( array( 300, 80 ) );
					}
				}
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
		$arguments['title'] = __( 'Add Featured Image Column', 'ultimakit-for-wp' );

		$arguments['fields'] = array(
			'column_title' => array(
				'type'  => 'text',
				'label' => __( 'Column Title', 'ultimakit-for-wp' ),
				'value' => $this->getModuleSettings( $this->ID, 'column_title', __( 'Featured Image', 'ultimakit-for-wp' ) ),
				'desc'  => __( 'Default length is <strong>Featured Image</strong>', 'ultimakit-for-wp' ),
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
}
