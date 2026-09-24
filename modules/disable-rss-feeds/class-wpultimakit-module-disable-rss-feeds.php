<?php
/**
 * Class UltimaKit_Module_Disable_RSS_Feeds
 *
 * @since 1.0.0
 * @package    UltimaKit
 */

/**
 * Class UltimaKit_Module_Disable_RSS_Feeds
 *
 * This class provides methods to control the display of dashboard widgets based on user preferences.
 * It allows users to selectively hide certain dashboard widgets to streamline their WordPress dashboard
 * experience and improve usability.
 *
 * @since 1.0.0
 */
class UltimaKit_Module_Disable_RSS_Feeds extends UltimaKit_Module_Manager {
	/**
	 * Unique identifier for the Hide Admin Bar module.
	 *
	 * @var string
	 */
	protected $ID = 'ultimakit_module_disable_rss_feeds';

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
	protected $read_more_link = 'disable-rss-feed-in-wordpress';

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
		$this->name        = __( 'Disable RSS Feeds', 'ultimakit-for-wp' );
		$this->description = __( 'Disable all RSS feeds', 'ultimakit-for-wp' );
		$this->is_active   = $this->isModuleActive( $this->ID );
		$this->settings    = 'no';
		$this->initializeModule();
		$this->settings_link = '#';
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
			remove_action( 'wp_head', 'feed_links', 2 );
			remove_action( 'wp_head', 'feed_links_extra', 3 );
			remove_action( 'do_feed_rdf', 'do_feed_rdf', 10, 0 );
			remove_action( 'do_feed_rss', 'do_feed_rss', 10, 0 );
			remove_action( 'do_feed_rss2', 'do_feed_rss2', 10, 1 );
			remove_action( 'do_feed_atom', 'do_feed_atom', 10, 1 );
			add_action( 'template_redirect', array( $this, 'ultimakit_redirect_feed_to_page' ), 10, 1 );
		}
	}


	public function ultimakit_redirect_feed_to_page() {
		if ( is_feed() ) {
			status_header( 403 );
			die( '403 Forbidden' );
		}
	}
}
