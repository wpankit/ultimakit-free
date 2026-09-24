<?php
/**
 * Class UltimaKit_Module_Disable_Emojis
 *
 * @since 1.0.0
 * @package    UltimaKit
 */

/**
 * Class UltimaKit_Module_Disable_Emojis
 *
 * This class provides methods to control the display of dashboard widgets based on user preferences.
 * It allows users to selectively hide certain dashboard widgets to streamline their WordPress dashboard
 * experience and improve usability.
 *
 * @since 1.0.0
 */
class UltimaKit_Module_Disable_Emojis extends UltimaKit_Module_Manager {
	/**
	 *
	 * @var string
	 */
	protected $ID = 'ultimakit_module_disable_emojis';

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
	protected $read_more_link = 'disable-emojis-in-wordpress';

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
		$this->name        = __( 'Disable Emojis', 'ultimakit-for-wp' );
		$this->description = __( 'Disable Emoji\'s in WordPress to improve your site\'s performance', 'ultimakit-for-wp' );
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
			 * Disable the emojis in WordPress.
			 */
			add_action(
				'init',
				function () {
					remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
					remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
					remove_action( 'wp_print_styles', 'print_emoji_styles' );
					remove_action( 'admin_print_styles', 'print_emoji_styles' );
					remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
					remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
					remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );

					// Remove from TinyMCE.
					add_filter(
						'tiny_mce_plugins',
						function ( $plugins ) {
							if ( is_array( $plugins ) ) {
								return array_diff( $plugins, array( 'wpemoji' ) );
							} else {
								return array();
							}
						}
					);

					// Remove from dns-prefetch.
					add_filter(
						'wp_resource_hints',
						function ( $urls, $relation_type ) {
							if ( 'dns-prefetch' === $relation_type ) {
								$emoji_svg_url = apply_filters( 'emoji_svg_url', 'https://s.w.org/images/core/emoji/2/svg/' );
								$urls          = array_diff( $urls, array( $emoji_svg_url ) );
							}

							return $urls;
						},
						10,
						2
					);
				}
			);

		}
	}
}
