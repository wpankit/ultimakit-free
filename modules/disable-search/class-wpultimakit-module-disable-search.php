<?php
/**
 * Class UltimaKit_Module_Disable_Search
 *
 * @since 1.0.0
 * @package    UltimaKit
 */

/**
 * Class UltimaKit_Module_Disable_Search
 *
 * This class provides methods to control the display of dashboard widgets based on user preferences.
 * It allows users to selectively hide certain dashboard widgets to streamline their WordPress dashboard
 * experience and improve usability.
 *
 * @since 1.0.0
 */
class UltimaKit_Module_Disable_Search extends UltimaKit_Module_Manager {
	/**
	 *
	 * @var string
	 */
	protected $ID = 'ultimakit_module_disable_search';

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
	protected $category = 'Disable Components';

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
	protected $read_more_link = 'disable-search-in-wordpress';

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
		$this->name        = __( 'Disable Search', 'ultimakit-for-wp' );
		$this->description = __( 'Completely disable search on your WordPress website.', 'ultimakit-for-wp' );
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

			// Prevent search queries.
			add_action(
				'parse_query',
				function ( $query, $error = true ) {
					if ( is_search() && ! is_admin() ) {
						$query->is_search       = false;
						$query->query_vars['s'] = false;
						$query->query['s']      = false;
						if ( true === $error ) {
							$query->is_404 = true;
						}
					}
				},
				15,
				2
			);

			// Remove the Search Widget.
			add_action(
				'widgets_init',
				function () {
					unregister_widget( 'WP_Widget_Search' );
				}
			);

			// Remove the search form.
			add_filter( 'get_search_form', '__return_empty_string', 999 );

			// Remove the core search block.
			add_action(
				'init',
				function () {
					if ( ! function_exists( 'unregister_block_type' ) || ! class_exists( 'WP_Block_Type_Registry' ) ) {
						return;
					}
					$block = 'core/search';
					if ( WP_Block_Type_Registry::get_instance()->is_registered( $block ) ) {
						unregister_block_type( $block );
					}
				}
			);

			// Remove admin bar menu search box.
			add_action(
				'admin_bar_menu',
				function ( $wp_admin_bar ) {
					$wp_admin_bar->remove_menu( 'search' );
				},
				11
			);

		}
	}
}
