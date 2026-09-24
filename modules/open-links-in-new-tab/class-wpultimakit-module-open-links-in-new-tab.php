<?php
/**
 * Class UltimaKit_Module_Open_Links_In_New_Tab
 *
 * @since 1.0.0
 * @package    UltimaKit
 */

/**
 * Class UltimaKit_Module_Open_Links_In_New_Tab
 *
 * @since 1.0.0
 */
class UltimaKit_Module_Open_Links_In_New_Tab extends UltimaKit_Module_Manager {
	/**
	 * Unique identifier for the Hide Admin Bar module.
	 *
	 * @var string
	 */
	protected $ID = 'ultimakit_module_open_links_in_new_tab';

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
	protected $read_more_link = 'open-all-links-on-your-page-post-in-a-new-tab-in-wordpress';

	/**
	 * The settings associated with the module, if any.
	 *
	 * @var array
	 */
	protected $settings;

	/**
	 * Constructs the Hide Admin Bar module instance.
	 *
	 * Initializes the module with default values for properties and prepares
	 * any necessary setup or hooks into WordPress. This may include setting
	 * initial values, registering hooks, or preparing resources needed for
	 * the module to function properly within WordPress.
	 */
	public function __construct() {
		$this->name        = __( 'Open All External Links in the New Tab', 'ultimakit-for-wp' );
		$this->description = __( 'This feature lets you link to external URLs from your posts or pages, directing visitors to another website.', 'ultimakit-for-wp' );
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
			add_filter( 'the_content', array( $this, 'ultimakit_open_links_in_new_tab' ) );
		}
	}

	public function ultimakit_open_links_in_new_tab( $content ) {
		if ( empty( $content ) ) {
			return $content;
		}

		// regex pattern for "a href"
		$regexp = '/<a\s[^>]*href=("??)([^" >]*?)\1[^>]*>/siU';

		// Find all anchor tags in the content
		preg_match_all( $regexp, $content, $matches, PREG_SET_ORDER );

		// Iterate through the matches
		foreach ( $matches as $match ) {
			$original_tag = $match[0];
			$url          = $match[2];

			// Skip internal links
			if ( false !== strpos( $url, get_site_url() ) ) {
				continue;
			}

			// Skip non-HTTP/HTTPS links
			if ( false === strpos( $url, 'http' ) ) {
				continue;
			}

			// Add target="_blank" if not present
			if ( false === strpos( $original_tag, 'target="_blank"' ) ) {
				$original_tag = str_replace( '>', ' target="_blank">', $original_tag );
			}

			// Add rel="noopener noreferrer nofollow" if not present
			if ( false === strpos( $original_tag, 'rel=' ) ) {
				$original_tag = str_replace( '>', ' rel="noopener noreferrer nofollow">', $original_tag );
			} else {
				// Replace rel="noopener" with rel="noopener noreferrer nofollow"
				$original_tag = str_replace( 'noopener', 'noopener noreferrer nofollow', $original_tag );
			}

			// Replace the original tag with the modified tag
			$content = str_replace( $match[0], $original_tag, $content );
		}

		return $content;
	}
}
