<?php
/**
 * Class UltimaKit_Module_Scroll_To_Top
 *
 * @since 1.0.0
 * @package    UltimaKit
 */

/**
 * Class UltimaKit_Module_Scroll_To_Top
 *
 * @since 1.0.0
 */
class UltimaKit_Module_Scroll_To_Top extends UltimaKit_Module_Manager {
	/**
	 * @var string
	 */
	protected $ID = 'ultimakit_module_scroll_to_top';

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
	protected $plan = 'pro';

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
	protected $read_more_link = 'add-scroll-to-top-button-in-wordpress';

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
		$this->name        = __( 'Scroll To Top', 'ultimakit-for-wp' );
		$this->description = __( 'Adds a customizable button that appears when users scroll down a page, allowing them to return to the top with a smooth scrolling effect.', 'ultimakit-for-wp' );
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
			add_action( 'wp_enqueue_scripts', array( $this, 'wpuk_enqueue_scroll_to_top_scripts' ) );
			add_action( 'wp_head', array( $this, 'wpuk_add_css_to_head' ) );
			add_action( 'wp_footer', array( $this, 'wpuk_add_scroll_to_top_button' ) );

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
		$arguments['title'] = __( 'Custom Admin Footer', 'ultimakit-for-wp' );

		$arguments['fields'] = array(
			'html_img'        => array(
				'type'  => 'html',
				'value' => '<img width="45px" id="wpuk_stt_img_placeholder" src="' . $this->getModuleSettings( $this->ID, 'stt_icon' ) . '" width="100px">',
			),
			'html'            => array(
				'type'  => 'html',
				'value' => '<button class="btn btn-primary" id="wpuk_file_icon_llc">' . __( 'Upload Image', 'ultimakit-for-wp' ) . '</button>',
			),
			'stt_icon'        => array(
				'type'        => 'text',
				'label'       => '',
				'placeholder' => __( 'Icon URL (SVG, PNG)', 'ultimakit-for-wp' ),
				'value'       => $this->getModuleSettings( $this->ID, 'stt_icon' ),
			),
			'stt_icon_width'  => array(
				'type'        => 'number',
				'label'       => __( 'Logo Width', 'ultimakit-for-wp' ),
				'placeholder' => __( 'Logo Width', 'ultimakit-for-wp' ),
				'value'       => $this->getModuleSettings( $this->ID, 'stt_icon_width', 35 ),
			),
			'stt_icon_height' => array(
				'type'        => 'number',
				'label'       => __( 'Logo Height', 'ultimakit-for-wp' ),
				'placeholder' => __( 'Logo Height', 'ultimakit-for-wp' ),
				'value'       => $this->getModuleSettings( $this->ID, 'stt_icon_height', 35 ),
			),
			'stt_bg_color'    => array(
				'type'        => 'color',
				'label'       => __( 'Background Color', 'ultimakit-for-wp' ),
				'placeholder' => __( 'Background Color', 'ultimakit-for-wp' ),
				'value'       => $this->getModuleSettings( $this->ID, 'stt_bg_color', '#555' ),
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
		wp_enqueue_media();
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script(
			'ultimakit-module-script-' . $this->ID,
			plugins_url( '/module-script.js', __FILE__ ),
			array( 'jquery', 'wp-color-picker' ),
			ULTIMAKIT_FOR_WP_VERSION,
			true
		);
	}

	public function wpuk_enqueue_scroll_to_top_scripts() {
		wp_enqueue_script(
			'ultimakit-module-script-front-' . $this->ID,
			plugins_url( '/wpuk-scroll-to-top.js', __FILE__ ),
			array( 'jquery' ),
			ULTIMAKIT_FOR_WP_VERSION,
			true
		);
	}

	public function wpuk_add_css_to_head() {
		$bg_color = sanitize_hex_color( (string) $this->getModuleSettings( $this->ID, 'stt_bg_color', '#555' ) );
		$bg_color = ! empty( $bg_color ) ? $bg_color : '#555';

		echo '<style type="text/css">
		#wpuk-scroll-to-top {
			position: fixed;
			bottom: ' . absint( $this->get_stt_size( 'stt_icon_width' ) ) . 'px;
			right: ' . absint( $this->get_stt_size( 'stt_icon_height' ) ) . 'px;
			background-color: ' . esc_attr( $bg_color ) . ';
			color: #fff;
			border-radius: 50%;
			cursor: pointer;
			z-index: 9999;
			transition: background-color 0.3s;
			width: ' . absint( $this->get_stt_size( 'stt_icon_width' ) ) . 'px;
			height: ' . absint( $this->get_stt_size( 'stt_icon_height' ) ) . 'px;
		}
		#wpuk-scroll-to-top:hover {
			background-color: ' . esc_attr( $bg_color ) . ';
		}
		</style>';
	}

	/**
	 * Icon size setting as a positive integer, falling back to the 35px default.
	 *
	 * @param string $key Setting key.
	 * @return int
	 */
	private function get_stt_size( $key ) {
		$size = absint( $this->getModuleSettings( $this->ID, $key, 35 ) );
		return $size ? $size : 35;
	}

	// Step 3: Add the button HTML if enabled
	public function wpuk_add_scroll_to_top_button() {
		if ( ! empty( $this->getModuleSettings( $this->ID, 'stt_icon' ) ) ) {
			echo '<div id="wpuk-scroll-to-top" style="display:none;"><img width="' . esc_attr( $this->get_stt_size( 'stt_icon_width' ) ) . 'px" height="' . esc_attr( $this->get_stt_size( 'stt_icon_width' ) ) . 'px" src="' . esc_url( $this->getModuleSettings( $this->ID, 'stt_icon' ) ) . '"  alt="scroll-to-top-icon"></div>';
		} else {
			echo '<div id="wpuk-scroll-to-top" style="display:none;">&#8679;</div>';
		}
	}
}
