<?php
/**
 * Class UltimaKit_Module_Gdpr_compliance_tool
 *
 * @since 1.0.0
 * @package    UltimaKit
 */

/**
 * Class UltimaKit_Module_Gdpr_compliance_tool
 *
 * @since 1.0.0
 */
class UltimaKit_Module_Gdpr_compliance_tool extends UltimaKit_Module_Manager {
	/**
	 * @var string
	 */
	protected $ID = 'ultimakit_module_gdpr_compliance_tool';

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
	protected $read_more_link = 'set-gdpr-compliance-message-in-wordpress';

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
		$this->name        = __( 'Simple GDPR Compliance Tool', 'ultimakit-for-wp' );
		$this->description = __( 'Adds a customizable cookie consent banner to notify users about cookies and an easy way to link to a privacy policy page.', 'ultimakit-for-wp' );
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
			add_action( 'wp_enqueue_scripts', array( $this, 'wp_add_scripts' ) );
			add_action( 'wp_head', array( $this, 'wp_add_styles' ) );
			add_action( 'wp_footer', array( $this, 'wpuk_display_gdpr_banner' ) );

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
		$arguments['title'] = __( 'Simple GDPR Compliance Tool', 'ultimakit-for-wp' );

		$arguments['fields'] = array(
			'wpuk_gdpr_message'       => array(
				'type'  => 'textarea',
				'label' => __( 'Text', 'ultimakit-for-wp' ),
				'value' => $this->getModuleSettings( $this->ID, 'wpuk_gdpr_message', __( 'We use cookies to ensure you get the best experience on our website.', 'ultimakit-for-wp' ) ),
			),
			'wpuk_gdpr_pp_heading'    => array(
				'type'  => 'text',
				'label' => __( 'Privacy Policy Anchor Text', 'ultimakit-for-wp' ),
				'value' => $this->getModuleSettings( $this->ID, 'wpuk_gdpr_pp_heading', __( 'Learn more', 'ultimakit-for-wp' ) ),
			),
			'wpuk_gdpr_pp_url'        => array(
				'type'  => 'text',
				'label' => __( 'Privacy Policy URL', 'ultimakit-for-wp' ),
				'value' => $this->getModuleSettings( $this->ID, 'wpuk_gdpr_pp_url', '#' ),
			),
			'wpuk_gdpr_accept_label'  => array(
				'type'  => 'text',
				'label' => __( 'Accept Button Label', 'ultimakit-for-wp' ),
				'value' => $this->getModuleSettings( $this->ID, 'wpuk_gdpr_accept_label', __( 'Accept', 'ultimakit-for-wp' ) ),
			),
			'wpuk_gdpr_bg_color'      => array(
				'type'  => 'color',
				'label' => __( 'Background Color', 'ultimakit-for-wp' ),
				'value' => $this->getModuleSettings( $this->ID, 'wpuk_gdpr_bg_color', '#000' ),
			),
			'wpuk_gdpr_txt_color'     => array(
				'type'  => 'color',
				'label' => __( 'Text/Anchor Color', 'ultimakit-for-wp' ),
				'value' => $this->getModuleSettings( $this->ID, 'wpuk_gdpr_txt_color', '#fff' ),
			),
			'wpuk_gdpr_btn_bg_color'  => array(
				'type'  => 'color',
				'label' => __( 'Button Background Color', 'ultimakit-for-wp' ),
				'value' => $this->getModuleSettings( $this->ID, 'wpuk_gdpr_btn_bg_color', '#007cba' ),
			),
			'wpuk_gdpr_btn_hvr_color' => array(
				'type'  => 'color',
				'label' => __( 'Button Hover Color', 'ultimakit-for-wp' ),
				'value' => $this->getModuleSettings( $this->ID, 'wpuk_gdpr_btn_hvr_color', '#005a9c' ),
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

		wp_enqueue_style( 'wp-color-picker' );

		wp_enqueue_script(
			'ultimakit-module-script-' . $this->ID,
			plugins_url( '/module-script.js', __FILE__ ),
			array( 'jquery', 'wp-color-picker' ),
			ULTIMAKIT_FOR_WP_VERSION,
			true
		);
	}

	public function wp_add_scripts() {
		wp_enqueue_script(
			'ultimakit-module-script-front-' . $this->ID,
			plugins_url( '/wpuk-gdpr.js', __FILE__ ),
			array( 'jquery' ),
			ULTIMAKIT_FOR_WP_VERSION,
			true
		);
	}

	public function wp_add_styles() {
		echo '<style type="text/css">
		#wpuk-gdpr-banner {
			position: fixed;
			bottom: 0;
			left: 0;
			right: 0;
			background-color: ' . $this->getModuleSettings( $this->ID, 'wpuk_gdpr_bg_color', '#000' ) . ';
			color: ' . $this->getModuleSettings( $this->ID, 'wpuk_gdpr_txt_color', '#fff' ) . ';
			padding: 10px;
			text-align: center;
			z-index: 9999;
		}
		#wpuk-gdpr-banner p {
			display: inline-block;
			margin: 0;
			padding-right: 10px;
			color: ' . $this->getModuleSettings( $this->ID, 'wpuk_gdpr_txt_color', '#fff' ) . ';
		}
		#wpuk-gdpr-banner a {
			color: ' . $this->getModuleSettings( $this->ID, 'wpuk_gdpr_txt_color', '#fff' ) . ';
			text-decoration: underline;
		}
		#wpuk-gdpr-banner button {
			background-color: ' . $this->getModuleSettings( $this->ID, 'wpuk_gdpr_btn_bg_color', '#007cba' ) . ';
			color: ' . $this->getModuleSettings( $this->ID, 'wpuk_gdpr_txt_color', '#fff' ) . ';
			border: none;
			padding: 5px 10px;
			cursor: pointer;
			border-radius: 3px;
		}
		#wpuk-gdpr-banner button:hover {
			background-color: ' . $this->getModuleSettings( $this->ID, 'wpuk_gdpr_btn_hvr_color', '#005a9c' ) . ';
		}
		</style>';
	}

	// Step 3: Display the GDPR consent banner if enabled
	public function wpuk_display_gdpr_banner() {
		$message    = esc_html( $this->getModuleSettings( $this->ID, 'wpuk_gdpr_message', __( 'We use cookies to ensure you get the best experience on our website.', 'ultimakit-for-wp' ) ) );
		$policy_url = esc_url( $this->getModuleSettings( $this->ID, 'wpuk_gdpr_pp_url', '#' ) );
		?>
		<div id="wpuk-gdpr-banner" style="display:none;">
			<p><?php echo $message; ?>
				<?php if ( $policy_url ) : ?>
					<a href="<?php echo $policy_url; ?>" target="_blank"><?php echo esc_html( $this->getModuleSettings( $this->ID, 'wpuk_gdpr_pp_heading', __( 'Learn more', 'ultimakit-for-wp' ) ) ); ?></a>
				<?php endif; ?>
			</p>
			<button id="wpuk-gdpr-accept"><?php echo esc_html( $this->getModuleSettings( $this->ID, 'wpuk_gdpr_accept_label', __( 'Accept', 'ultimakit-for-wp' ) ) ); ?></button>
		</div>
		<?php
	}
}
