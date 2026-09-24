<?php
/**
 * Class UltimaKit_Module_Disable_Embeds
 *
 * @since 1.0.0
 * @package    UltimaKit
 */

/**
 * Class UltimaKit_Module_Disable_Embeds
 *
 * This class provides methods to control the display of dashboard widgets based on user preferences.
 * It allows users to selectively hide certain dashboard widgets to streamline their WordPress dashboard
 * experience and improve usability.
 *
 * @since 1.0.0
 */
class UltimaKit_Module_Disable_Embeds extends UltimaKit_Module_Manager {
	/**
	 *
	 * @var string
	 */
	protected $ID = 'ultimakit_module_disable_embeds';

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
	protected $read_more_link = 'disable-embeds-in-wordpress';

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
		$this->name        = __( 'Disable Embeds', 'ultimakit-for-wp' );
		$this->description = __( 'Remove an extra request and prevent others from adding embeds in your site.', 'ultimakit-for-wp' );
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
			/**
			 * Disable all embeds in WordPress.
			 */
			add_action(
				'init',
				function () {

					// Remove the REST API endpoint.
					remove_action( 'rest_api_init', 'wp_oembed_register_route' );

					// Turn off oEmbed auto discovery.
					add_filter( 'embed_oembed_discover', '__return_false' );

					// Don't filter oEmbed results.
					remove_filter( 'oembed_dataparse', 'wp_filter_oembed_result', 10 );

					// Remove oEmbed discovery links.
					remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );

					// Remove oEmbed-specific JavaScript from the front-end and back-end.
					remove_action( 'wp_head', 'wp_oembed_add_host_js' );
					add_filter(
						'tiny_mce_plugins',
						function ( $plugins ) {
							return array_diff( $plugins, array( 'wpembed' ) );
						}
					);

					// Remove all embeds rewrite rules.
					add_filter(
						'rewrite_rules_array',
						function ( $rules ) {
							foreach ( $rules as $rule => $rewrite ) {
								if ( false !== strpos( $rewrite, 'embed=true' ) ) {
									unset( $rules[ $rule ] );
								}
							}

							return $rules;
						}
					);

					// Remove filter of the oEmbed result before any HTTP requests are made.
					remove_filter( 'pre_oembed_result', 'wp_filter_pre_oembed_result', 10 );
				},
				9999
			);
		}
	}
}
