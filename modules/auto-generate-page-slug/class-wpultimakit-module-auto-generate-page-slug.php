<?php
/**
 * Class UltimaKit_Module_Auto_Generate_Page_Slug
 *
 * @since 1.0.0
 * @package    UltimaKit
 */

/**
 * Class UltimaKit_Module_Auto_Generate_Page_Slug
 *
 * This class provides methods to control the display of dashboard widgets based on user preferences.
 * It allows users to selectively hide certain dashboard widgets to streamline their WordPress dashboard
 * experience and improve usability.
 *
 * @since 1.0.0
 */
class UltimaKit_Module_Auto_Generate_Page_Slug extends UltimaKit_Module_Manager {
	/**
	 *
	 * @var string
	 */
	protected $ID = 'ultimakit_auto_generate_page_slug';

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
	protected $category = 'Utilities';

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
	protected $read_more_link = 'auto-generate-page-slug-in-wordpress';

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
		$this->name        = __( 'Auto-Generate Page Slugs', 'ultimakit-for-wp' );
		$this->description = __( 'Simplify the process of creating SEO-friendly page slugs by automatically generating them from page titles.', 'ultimakit-for-wp' );
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
			add_filter( 'wp_insert_post_data', array( $this, 'ultimakit_auto_generate_page_slug' ), 10, 2 );
		}
	}

	// Function to auto-generate slugs for pages before saving.
	public function ultimakit_auto_generate_page_slug( $data, $postarr ) {
		// Check if it's a page and not a revision.
		if ( $data['post_type'] === 'page' && $data['post_status'] !== 'auto-draft' ) {
			// Generate a slug if one is not set or if it matches the default slug.
			if ( empty( $data['post_name'] ) || $data['post_name'] === sanitize_title( $data['post_title'] ) ) {
				$data['post_name'] = sanitize_title( $data['post_title'] );
			}
		}
		return $data;
	}
}
