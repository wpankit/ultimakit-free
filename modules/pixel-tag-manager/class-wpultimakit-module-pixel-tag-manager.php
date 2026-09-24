<?php
/**
 *
 * This module streamlines the WordPress admin bar by offering features to hide, show, and rearrange items for a cleaner interface.
 *
 * @package    UltimaKit
 * @link       https://xwpankit.com
 * @since      1.0.0
 */
class UltimaKit_Module_Pixel_Tag_Manager extends UltimaKit_Module_Manager {
	/**
	 * @var string
	 */
	protected $ID = 'ultimakit_pixel_tag_manager';

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
	protected $read_more_link = 'pixel-tag-manager-in-wordpress';

	/**
	 * The settings associated with the module, if any.
	 *
	 * @var array
	 */
	protected $settings;

	/**
	 * Initializes the module with default values for properties and prepares
	 * any necessary setup or hooks into WordPress. This may include setting
	 * initial values, registering hooks, or preparing resources needed for
	 * the module to function properly within WordPress.
	 */
	public function __construct() {
		$this->name        = __( 'Pixel Tag Manager', 'ultimakit-for-wp' );
		$this->description = __( 'Pixel Tag Manager integrates your website with Google, Facebook, and Pinterest IDs for seamless tracking and analytics.', 'ultimakit-for-wp' );
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
		if ( true === $this->ultimakit_asset_condition() ) {
			add_action( 'admin_enqueue_scripts', array( $this, 'add_scripts' ) );
			add_action( 'admin_footer', array( $this, 'add_modal' ) );
		}
		add_action( 'wp_head', array( $this, 'ultimakit_pixel_tag_manager_wp_header' ) );
	}

	/**
	 *
	 * This function provides the logic to retrieve, display, or update the settings
	 * that control the appearance and behavior of the WordPress admin bar. It can
	 * involve fetching current settings, rendering an options page in the admin dashboard,
	 * and processing form submissions to update those settings. The function ensures that
	 * any modifications are applied effectively, enhancing the admin bar's usability or
	 * appearance according to the user's preferences.
	 *
	 * @param WP_Admin_Bar $wp_admin_bar Global instance of the WP_Admin_Bar class, used to manipulate the admin bar. This parameter allows the function to modify the admin bar directly, offering a means to customize its contents.
	 *
	 * @return mixed
	 */
	public function ultimakit_pixel_tag_manager_wp_header() {
		if ( ! $this->is_active ) {
			return false;
		}

		if ( ! empty( $this->getModuleSettings( $this->ID, 'google_analytics' ) ) ) {
			/*Google analitic pixel code*/
			?>
			<script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo esc_attr( $this->getModuleSettings( $this->ID, 'google_analytics' ) ); ?>"></script>
			<script>
				window.dataLayer = window.dataLayer || [];
				function gtag(){dataLayer.push(arguments);}
				gtag("js", new Date());
				gtag("config", "<?php echo esc_js( $this->getModuleSettings( $this->ID, 'google_analytics' ) ); ?>");
			</script> 
			<?php
			/*Google analitic pixel code end here*/
		}

		if ( ! empty( $this->getModuleSettings( $this->ID, 'facebook_pixel' ) ) ) {
			/*Facebook pixel code*/
			?>
			<script>
				!function(f,b,e,v,n,t,s)
				{if(f.fbq)return;n=f.fbq=function(){n.callMethod?
				n.callMethod.apply(n,arguments):n.queue.push(arguments)};
				if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
				n.queue=[];t=b.createElement(e);t.async=!0;
				t.src=v;s=b.getElementsByTagName(e)[0];
				s.parentNode.insertBefore(t,s)}(window, document,'script',
				'https://connect.facebook.net/en_US/fbevents.js');
				fbq('init', '<?php echo esc_js( $this->getModuleSettings( $this->ID, 'facebook_pixel' ) ); ?>');
				fbq('track', 'PageView');
			</script>
			<noscript>
			<img height="1" width="1" style="display:none" src="https://www.facebook.com/tr?id=<?php echo esc_attr( $this->getModuleSettings( $this->ID, 'facebook_pixel' ) ); ?>&ev=PageView&noscript=1"/>
			</noscript> 
			<?php
			/* End Facebook Pixel Code */
		}

		if ( ! empty( $this->getModuleSettings( $this->ID, 'pinterest_pixel' ) ) ) {
			/*Pinterest pixel code*/
			?>
			<script type="text/javascript"> 
				!function(e){if(!window.pintrk){window.pintrk=function(){window.pintrk.queue.push(Array.prototype.slice.call(arguments))};
				var n=window.pintrk;
					n.queue=[],n.version="3.0";
				var t=document.createElement("script");
					t.async=!0,t.src=e;
				var r=document.getElementsByTagName("script")[0];
					r.parentNode.insertBefore(t,r)}}("https://s.pinimg.com/ct/core.js");
				pintrk('load', '<?php echo esc_js( $this->getModuleSettings( $this->ID, 'pinterest_pixel' ) ); ?>'); 
				pintrk('page'); 
			</script>
			<noscript>
				<img height="1" width="1" style="display:none;" alt="" src="https://ct.pinterest.com/v3/?tid=<?php echo esc_attr( $this->getModuleSettings( $this->ID, 'pinterest_pixel' ) ); ?>&noscript=1" />
			</noscript>
			<?php
			/* End Pinterest Pixel Code */
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
		$arguments['title'] = __( 'Pixel Tag Manager Settings', 'ultimakit-for-wp' );

		$arguments['fields'] = array(
			'google_analytics' => array(
				'type'     => 'text',
				'label'    => __( 'Google Analytics/Tags', 'ultimakit-for-wp' ),
				'value'    => $this->getModuleSettings( $this->ID, 'google_analytics' ),
				'desc'     => __( 'How to find the tracking ID', 'ultimakit-for-wp' ),
				'desc_url' => esc_url( 'https://support.google.com/analytics/answer/9539598?hl=en' ),
			),
			'facebook_pixel'   => array(
				'type'     => 'text',
				'label'    => __( 'Facebook Pixel ID', 'ultimakit-for-wp' ),
				'value'    => $this->getModuleSettings( $this->ID, 'facebook_pixel' ),
				'desc'     => __( 'How to find the Facebook pixel ID', 'ultimakit-for-wp' ),
				'desc_url' => esc_url( 'https://www.facebook.com/business/help/952192354843755?id=1205376682832142' ),
			),
			'pinterest_pixel'  => array(
				'type'     => 'text',
				'label'    => __( 'Pinterest Pixel ID', 'ultimakit-for-wp' ),
				'value'    => $this->getModuleSettings( $this->ID, 'pinterest_pixel' ),
				'desc'     => __( 'How to find the Pinterest pixel ID', 'ultimakit-for-wp' ),
				'desc_url' => esc_url( 'https://help.pinterest.com/en/business/article/install-the-pinterest-tag' ),
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
