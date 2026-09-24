<?php
/**
 * Class UltimaKit_Module_Obfuscate_Email_Addresses
 *
 * @since 1.0.0
 * @package    UltimaKit
 */

/**
 * Class UltimaKit_Module_Obfuscate_Email_Addresses
 *
 * This class provides methods to obfuscate email addresses in content to protect them from spam bots.
 * It automatically finds and obfuscates email addresses in post content, making it harder for bots to harvest them.
 *
 * @since 1.0.0
 */
class UltimaKit_Module_Obfuscate_Email_Addresses extends UltimaKit_Module_Manager {
	/**
	 * @var string
	 */
	protected $ID = 'ultimakit_module_obfuscate_email_addresses';

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
	protected $category = 'Security';

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
	protected $read_more_link = 'obfuscate-email-addresses-in-wordpress';

	/**
	 * The settings associated with the module, if any.
	 *
	 * @var array
	 */
	protected $settings;

	/**
	 * Constructs the Obfuscate Email Addresses module instance.
	 *
	 * Initializes the module with default values for properties and prepares
	 * any necessary setup or hooks into WordPress. This may include setting
	 * initial values, registering hooks, or preparing resources needed for
	 * the module to function properly within WordPress.
	 */
	public function __construct() {
		$this->name        = __( 'Obfuscate Email Addresses', 'ultimakit-for-wp' );
		$this->description = __( 'Protect email addresses from spam bots.', 'ultimakit-for-wp' );
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
			add_filter( 'the_content', array( $this, 'obfuscate_emails_in_content' ), 99 );
		}
	}

	/**
	 * Obfuscate email addresses in post content
	 *
	 * @param string $content The post content
	 * @return string Modified content with obfuscated emails
	 */
	public function obfuscate_emails_in_content( $content ) {
		// Check if obfuscation is enabled in settings
		if ( 'on' !== $this->getModuleSettings( $this->ID, 'enable_obfuscation' ) ) {
			return $content;
		}

		// Get obfuscation method from settings
		$method = $this->getModuleSettings( $this->ID, 'obfuscation_method' );
		if ( empty( $method ) ) {
			$method = 'replace_chars';
		}

		// Simple regex to find email addresses
		$pattern = '/([a-zA-Z0-9._%+-]+)@([a-zA-Z0-9.-]+)\\.([a-zA-Z]{2,})/i';
		
		$callback = function( $matches ) use ( $method ) {
			return $this->obfuscate_email( $matches[0], $method );
		};
		
		return preg_replace_callback( $pattern, $callback, $content );
	}

	/**
	 * Obfuscate a single email address using the specified method
	 *
	 * @param string $email The email address to obfuscate
	 * @param string $method The obfuscation method to use
	 * @return string The obfuscated email
	 */
	private function obfuscate_email( $email, $method ) {
		switch ( $method ) {
			case 'replace_chars':
				$obfuscated = str_replace( '@', ' [at] ', $email );
				$obfuscated = str_replace( '.', ' [dot] ', $obfuscated );
				return '<span class="ultimakit-obfuscated-email" title="Email protected">' . esc_html( $obfuscated ) . '</span>';
				
			case 'html_entities':
				$obfuscated = str_replace( '@', '&#64;', $email );
				$obfuscated = str_replace( '.', '&#46;', $obfuscated );
				return '<span class="ultimakit-obfuscated-email" title="Email protected">' . $obfuscated . '</span>';
				
			case 'reverse_text':
				$obfuscated = strrev( $email );
				return '<span class="ultimakit-obfuscated-email" title="Email protected" data-email="' . esc_attr( $email ) . '">' . esc_html( $obfuscated ) . '</span>';
				
			default:
				return $email;
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
		$arguments['title'] = __( 'Obfuscate Email Addresses', 'ultimakit-for-wp' );

		$arguments['fields'] = array(
			'enable_obfuscation' => array(
				'type'  => 'checkbox',
				'label' => __( 'Enable Email Obfuscation', 'ultimakit-for-wp' ),
				'value' => $this->getModuleSettings( $this->ID, 'enable_obfuscation' ),
				'desc'  => __( 'Automatically obfuscate email addresses in post content', 'ultimakit-for-wp' ),
			),
			'obfuscation_method' => array(
				'type'    => 'select',
				'label'   => __( 'Obfuscation Method', 'ultimakit-for-wp' ),
				'options' => array(
					'replace_chars' => __( 'Replace @ and . with [at] and [dot]', 'ultimakit-for-wp' ),
					'html_entities' => __( 'Use HTML entities', 'ultimakit-for-wp' ),
					'reverse_text'  => __( 'Reverse text (requires JavaScript)', 'ultimakit-for-wp' ),
				),
				'value'   => $this->getModuleSettings( $this->ID, 'obfuscation_method' ),
				'desc'    => __( 'Choose how email addresses should be obfuscated', 'ultimakit-for-wp' ),
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