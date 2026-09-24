<?php
/**
 * Class UltimaKit_Module_Disable_Author_Archives
 *
 * @since 1.0.0
 * @package    UltimaKit
 */

/**
 * Class UltimaKit_Module_Disable_Author_Archives
 *
 * This class provides methods to control the display of dashboard widgets based on user preferences.
 * It allows users to selectively hide certain dashboard widgets to streamline their WordPress dashboard
 * experience and improve usability.
 *
 * @since 1.0.0
 */
class UltimaKit_Module_Disable_Author_Archives extends UltimaKit_Module_Manager {
	/**
	 *
	 * @var string
	 */
	protected $ID = 'ultimakit_module_disable_author_archives';

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
	protected $read_more_link = 'disable-author-archives-in-wordpress';

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
		$this->name        = __( 'Disable Author Archives', 'ultimakit-for-wp' );
		$this->description = __( 'Completely hide author archives and prevent direct access by returning a 404 page.', 'ultimakit-for-wp' );
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
			// Return a 404 page for author pages if accessed directly.
			add_action(
				'template_redirect',
				function () {
					if ( is_author() ) {
						global $wp_query;
						$wp_query->set_404();
						status_header( 404 );
						nocache_headers();
					}
				}
			);

			// Remove the author links.
			add_filter( 'author_link', '__return_empty_string', 1000 );
			add_filter( 'the_author_posts_link', 'get_the_author', 1000, 0 );

			// Remove the author pages from the WP 5.5+ sitemap.
			add_filter(
				'wp_sitemaps_add_provider',
				function ( $provider, $name ) {
					if ( 'users' === $name ) {
						return false;
					}

					return $provider;
				},
				10,
				2
			);

			// Remove admin links in the list of users.
			add_filter(
				'user_row_actions',
				function ( $actions, $user ) {
					unset( $actions['view'] );
					unset( $actions['posts'] );

					return $actions;
				},
				10,
				2
			);
		}
	}
}
