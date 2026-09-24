<?php
/**
 * Class UltimaKit_Module_Maintenance_Mode
 *
 * @since 1.0.0
 * @package    UltimaKit
 */

/**
 * Class UltimaKit_Module_Maintenance_Mode
 *
 * This class provides methods to control the display of dashboard widgets based on user preferences.
 * It allows users to selectively hide certain dashboard widgets to streamline their WordPress dashboard
 * experience and improve usability.
 *
 * @since 1.0.0
 */
class UltimaKit_Module_Maintenance_Mode extends UltimaKit_Module_Manager {
	/**
	 * Unique identifier for the Hide Admin Bar module.
	 *
	 * @var string
	 */
	protected $ID = 'ultimakit_module_maintenance_mode';

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
	protected $read_more_link = 'maintenance-mode-in-wordpress';

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
		$this->name        = __( 'Maintenance Mode', 'ultimakit-for-wp' );
		$this->description = __( 'Clear your site cache after enabling or disabling maintenance mode.', 'ultimakit-for-wp' );
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

			add_action( 'admin_bar_menu', array( $this, 'ultimakit_wp_maintenance_admin_bar_notice' ), 100 );
			add_action( 'template_redirect', array( $this, 'ultimakit_wp_maintenance_mode' ) );
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
		$arguments['title'] = __( 'Maintenance Mode Settings', 'ultimakit-for-wp' );

		$arguments['fields'] = array(
			'maintenance_mode_status'  => array(
				'type'  => 'switch',
				'label' => __( 'Activate maintenance mode', 'ultimakit-for-wp' ),
				'value' => $this->getModuleSettings( $this->ID, 'maintenance_mode_status' ),
			),
			'title'                    => array(
				'type'  => 'text',
				'label' => __( 'Title', 'ultimakit-for-wp' ),
				'value' => $this->getModuleSettings( $this->ID, 'title' ),
			),
			'message'                  => array(
				'type'  => 'textarea',
				'label' => __( 'Message', 'ultimakit-for-wp' ),
				'value' => $this->getModuleSettings( $this->ID, 'message' ),
			),
			'title_font_size'          => array(
				'type'  => 'text',
				'label' => __( 'Title Font Size', 'ultimakit-for-wp' ),
				'value' => $this->getModuleSettings( $this->ID, 'title_font_size' ),
				'value' => 48,
			),
			'message_font_size'        => array(
				'type'  => 'text',
				'label' => __( 'Message Font Size', 'ultimakit-for-wp' ),
				'value' => $this->getModuleSettings( $this->ID, 'message_font_size' ),
				'value' => 16,
			),
			'font_color'               => array(
				'type'  => 'color',
				'label' => __( 'Font Color', 'ultimakit-for-wp' ),
				'value' => $this->getModuleSettings( $this->ID, 'font_color' ),
			),
			'overlay_background_color' => array(
				'type'  => 'color',
				'label' => __( 'Overlay Background Color', 'ultimakit-for-wp' ),
				'value' => $this->getModuleSettings( $this->ID, 'overlay_background_color' ),
			),
			'background_image'         => array(
				'type'  => 'html',
				'label' => __( 'Background Image', 'ultimakit-for-wp' ),
				'value' => '<input style="width:300px" type="text" class="media-url" id="background_image" name="background_image" value="' . esc_url( (string) $this->getModuleSettings( $this->ID, 'background_image' ) ) . '" /> <input type="button" style="padding: 4px; margin-top: -4px;" class="btn btn-primary ultimakit-media-upload-button" value="' . __( 'Upload Media', 'ultimakit-for-wp' ) . '"></input>',
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

		wp_enqueue_media();

		wp_enqueue_script(
			'ultimakit-module-script-' . $this->ID,
			plugins_url( '/module-script.js', __FILE__ ),
			array( 'jquery', 'wp-color-picker' ),
			ULTIMAKIT_FOR_WP_VERSION,
			true
		);
	}

	public function ultimakit_wp_maintenance_admin_bar_notice() {
		global $wp_admin_bar;

		$options = $this->getModuleSettings( $this->ID, 'maintenance_mode_status' );

		if ( 'on' === $options ) {
			$notice = '<span style="color: #FFD700; padding: 8px; background-color:#6610F2">' . __( 'Maintenance Activated', 'ultimakit-for-wp' ) . '</span>';
			$args   = array(
				'id'    => 'ultimakit_wp_maintenance_admin_notice',
				'title' => $notice,
				'href'  => admin_url( 'admin.php?page=wp-ultimakit-dashboard' ),
			);
			$wp_admin_bar->add_node( $args );
		}
	}

	public function ultimakit_wp_maintenance_mode() {
		if ( is_admin() ) {
			return;
		}

		$options = $this->getModuleSettings( $this->ID, 'maintenance_mode_status' );

		if ( empty( $options ) || 'off' === $options ) {
			return;
		}

		// edit_themes is denied everywhere when DISALLOW_FILE_EDIT is set, which would lock admins out.
		if ( current_user_can( 'manage_options' ) && is_user_logged_in() ) {
			return;
		}

		$background_image_url = $this->getModuleSettings( $this->ID, 'background_image' );
		$custom_message       = $this->getModuleSettings( $this->ID, 'message' );
		$custom_title         = $this->getModuleSettings( $this->ID, 'title' );
		$title                = ! empty( $custom_title ) ? $custom_title : __( 'Maintenance Mode', 'ultimakit-for-wp' );

		$overlay_background_color = sanitize_hex_color( (string) $this->getModuleSettings( $this->ID, 'overlay_background_color' ) );
		$overlay_background_color = ! empty( $overlay_background_color ) ? $overlay_background_color : 'rgba(128, 128, 128, 0.5)';

		$title_color  = sanitize_hex_color( (string) $this->getModuleSettings( $this->ID, 'font_color' ) );
		$title_color  = ! empty( $title_color ) ? $title_color : 'inherit';
		$title_size   = absint( $this->getModuleSettings( $this->ID, 'title_font_size' ) );
		$title_size   = $title_size ? $title_size : 48;
		$message_size = absint( $this->getModuleSettings( $this->ID, 'message_font_size' ) );
		$message_size = $message_size ? $message_size : 16;

		$output = '<!DOCTYPE html>
		<html lang="de">
		<head>
		    <meta charset="utf-8">
		    <title>' . esc_html( $title ) . '</title>
		    <style>
		        body, #error-page {
		            margin: 0;
		            border: none;
		            padding: 0;
		            max-width: none;
		            box-shadow: none;
		        }
		        body {
		            background-image: url("' . esc_url( $background_image_url ) . '");
		            background-size: cover;
		            background-repeat: no-repeat;
		            background-position: center center;
		            background-color: #333;
		            height: 100vh;
		            width: 100vw;
		            text-align: center;
		            display: flex;
		            align-items: center;
		            justify-content: center;
		        }
		        html {
		            height: 100%;
		            width: 100%;
		        }
		        h1 {
		           
		            margin-bottom: 1rem;
		        }
		        #error-page {
		            position: absolute;
		            top: 0;
		            left: 0;
		            width: 100vw;
		            height: 100vh;
		        }
		        #content {
		            background-color: ' . esc_attr( $overlay_background_color ) . ';
		            padding: 1rem 2rem;
		            border-radius: 5px;
		        }
		        #content h1 { color: ' . esc_attr( $title_color ) . '; font-size: ' . absint( $title_size ) . 'px; }
		        #content p { color: ' . esc_attr( $title_color ) . '; font-size: ' . absint( $message_size ) . 'px; }
		    </style>
		</head>
		<body>
		    <div id="content">
		        <h1>' . esc_html( $title ) . '</h1>
		        <p>' . wp_kses_post( $custom_message ) . '</p>
		    </div>
		</body>
		</html>';

			wp_die(
				$output,
				esc_html( $title ),
				array(
					'response'  => 503,
					'back_link' => false,
				)
			);
	}
}
