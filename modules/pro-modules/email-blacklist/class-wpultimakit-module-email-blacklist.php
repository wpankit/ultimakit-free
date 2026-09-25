<?php
/**
 * Class UltimaKit_Module_Email_Blacklist
 *
 * @since 1.0.0
 * @package    UltimaKit
 */

/**
 * Class UltimaKit_Module_Email_Blacklist
 *
 * @since 1.0.0
 */
class UltimaKit_Module_Email_Blacklist extends UltimaKit_Module_Manager {
	/**
	 * @var string
	 */
	protected $ID = 'ultimakit_module_gf_email_blacklist';

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
	protected $category = 'Form';

	/**
	 * The type of module, indicating its platform or use case.
	 *
	 * @var string
	 */
	protected $type = 'Gravity Forms';

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
	protected $read_more_link = 'add-email-address-to-email-blacklist-gravity-forms';

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
		$this->name        = __( 'Email Blacklist', 'ultimakit-for-wp' );
		$this->description = __( 'The Email Blacklist Add-on for Gravity Forms was built to help block submissions from users with generic or competitors email addresses.', 'ultimakit-for-wp' );
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

			add_action( 'gform_validation', array( $this, 'gf_email_blacklist_check' ) );
			add_filter( 'gform_form_settings_fields', array( $this, 'gf_email_blacklist_settings_fields' ), 10, 2 );
			add_action( 'gform_pre_form_settings_save', array( $this, 'gf_email_blacklist_save_settings' ) );

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
		$arguments['title'] = __( 'Email Blacklist (Gravity Forms)', 'ultimakit-for-wp' );

		$arguments['fields'] = array(
			'enable_global_settings' => array(
				'type'  => 'switch',
				'label' => __( 'Enable Global Settings', 'ultimakit-for-wp' ),
				'value' => $this->getModuleSettings( $this->ID, 'enable_global_settings' ),
			),
			'emails'                 => array(
				'type'  => 'textarea',
				'label' => __( 'Blacklisted Emails (one per line)', 'ultimakit-for-wp' ),
				'desc'  => __( 'Enter blacklisted email addresses, one per line.', 'ultimakit-for-wp' ),
				'value' => $this->getModuleSettings( $this->ID, 'emails' ),
			),
			'domains'                => array(
				'type'  => 'textarea',
				'label' => __( 'Blacklisted Domains (one per line)', 'ultimakit-for-wp' ),
				'desc'  => __( 'Enter blacklisted domains (e.g., gmail.com), one per line.', 'ultimakit-for-wp' ),
				'value' => $this->getModuleSettings( $this->ID, 'domains' ),
			),
			'patterns'               => array(
				'type'  => 'textarea',
				'label' => __( 'Blacklist Regex Patterns (one per line)', 'ultimakit-for-wp' ),
				'desc'  => __( 'Enter blacklisted regex patterns (e.g., /.ru$/), one per line.', 'ultimakit-for-wp' ),
				'value' => $this->getModuleSettings( $this->ID, 'patterns' ),
			),
			'message'                => array(
				'type'  => 'text',
				'label' => __( 'Error Message', 'ultimakit-for-wp' ),
				'desc'  => __( 'Custom error message to display.', 'ultimakit-for-wp' ),
				'value' => $this->getModuleSettings( $this->ID, 'message', __( 'This email address is not allowed.', 'ultimakit-for-wp' ) ),
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

	/**
	 * The configured rejection message, falling back when it was saved empty.
	 *
	 * getModuleSettings() returns the stored value whenever the key is merely set, and the
	 * settings modal posts every field, so once saved the key is always set — possibly to
	 * an empty string. Without this guard a blocked submission showed a blank error and the
	 * user saw a form that simply refused to submit with no explanation.
	 *
	 * @return string
	 */
	protected function wpuk_get_error_message() {
		$message = (string) $this->getModuleSettings( $this->ID, 'message' );

		if ( '' === trim( $message ) ) {
			$message = __( 'This email address is not allowed.', 'ultimakit-for-wp' );
		}

		return $message;
	}

	public function gf_email_blacklist_check( $validation_result ) {
		$form = $validation_result['form'];

		// Use global settings if enabled
		if ( 'on' === $this->getModuleSettings( $this->ID, 'enable_global_settings' ) ) {
			/*
			 * $form_settings does not exist in this branch — it is only assigned in the else
			 * below — so wp_parse_args() received an undefined variable, emitting a PHP 8
			 * warning plus a deprecation on every submission for sites using the global
			 * blacklist. There is nothing to merge here; the global settings ARE the values.
			 */
			$settings = array(
				'emails'        => array_filter( array_map( 'trim', explode( "\n", (string) $this->getModuleSettings( $this->ID, 'emails' ) ) ) ),
				'domains'       => array_filter( array_map( 'trim', explode( "\n", (string) $this->getModuleSettings( $this->ID, 'domains' ) ) ) ),
				'patterns'      => array_filter( array_map( 'trim', explode( "\n", (string) $this->getModuleSettings( $this->ID, 'patterns' ) ) ) ),
				'error_message' => $this->wpuk_get_error_message(),
			);
		} else {
			// Retrieve blacklist settings from gf_email_blacklist JSON field
			$form_settings_json = rgar( $form, 'gf_email_blacklist_emails' );
			$form_settings      = ! empty( $form_settings_json ) ? json_decode( $form_settings_json, true ) : array();

			// Set default settings if no blacklist exists
			$settings = wp_parse_args(
				$form_settings,
				array(
					'emails'        => array_filter( array_map( 'trim', explode( "\n", rgar( $form, 'gf_email_blacklist_emails', '' ) ) ) ),
					'domains'       => array_filter( array_map( 'trim', explode( "\n", rgar( $form, 'gf_email_blacklist_domains', '' ) ) ) ),
					'patterns'      => array_filter( array_map( 'trim', explode( "\n", rgar( $form, 'gf_email_blacklist_patterns', '' ) ) ) ),
					'error_message' => rgar( $form, 'gf_email_blacklist_error_message', 'This email address is not allowed.' ),
				)
			);
		}

		// Iterate over fields to find email fields
		foreach ( $form['fields'] as &$field ) {
			if ( $field->type == 'email' ) {
				$email_field_id = $field->id;
				$email          = rgpost( 'input_' . $email_field_id );

				/*
				 * Gravity Forms trims the value before validating and saving it (and validates
				 * only the first value of an array), so match against that same value. Comparing
				 * the raw POST let " bad@competitor.com" through with a leading space.
				 */
				$email = is_array( $email ) ? rgar( $email, 0 ) : $email;
				$email = is_string( $email ) ? trim( $email ) : '';

				if ( empty( $email ) ) {
					continue; // Skip validation if the field is empty
				}

				// Check blacklist emails (address and entries trimmed and lower-cased)
				$blocked_emails = array_map( 'strtolower', array_map( 'trim', (array) $settings['emails'] ) );
				if ( in_array( strtolower( $email ), $blocked_emails, true ) ) {
					$validation_result['is_valid'] = false;
					$field['failed_validation']    = true;
					$field['validation_message']   = $settings['error_message'];
					continue;
				}

				// Check blacklist domains; a blocked domain also blocks its subdomains
				$domain            = strtolower( ltrim( strstr( $email, '@' ), '@' ) );
				$is_blocked_domain = false;
				foreach ( (array) $settings['domains'] as $blocked_domain ) {
					$blocked_domain = ltrim( strtolower( trim( $blocked_domain ) ), '@.' );
					if ( '' !== $blocked_domain && ( $domain === $blocked_domain || substr( $domain, -strlen( '.' . $blocked_domain ) ) === '.' . $blocked_domain ) ) {
						$is_blocked_domain = true;
						break;
					}
				}
				if ( $is_blocked_domain ) {
					$validation_result['is_valid'] = false;
					$field['failed_validation']    = true;
					$field['validation_message']   = $settings['error_message'];
					continue;
				}

				// Check blacklist patterns (regex)
				foreach ( $settings['patterns'] as $pattern ) {
					// Ensure the pattern is wrapped with delimiters if missing
					$wrapped_pattern = $pattern;
					if ( @preg_match( $pattern, '' ) === false ) {
						$wrapped_pattern = '/' . trim( $pattern, '/' ) . '/';
					}

					// Check if the email matches the regex
					if ( @preg_match( $wrapped_pattern, $email ) ) {
						$validation_result['is_valid'] = false;
						$field['failed_validation']    = true;
						$field['validation_message']   = $settings['error_message'];
						continue;
					}
				}
			}
		}

		$validation_result['form'] = $form;
		return $validation_result;
	}

	public function gf_email_blacklist_settings_fields( $fields, $form ) {
		// Retrieve settings from gf_email_blacklist JSON field
		$settings = json_decode( rgar( $form, 'gf_email_blacklist' ), true );
		if ( empty( $settings ) ) {
			// Fall back to individual fields if JSON is empty
			$settings = array(
				'emails'        => array_filter( array_map( 'trim', explode( "\n", rgar( $form, 'gf_email_blacklist_emails', '' ) ) ) ),
				'domains'       => array_filter( array_map( 'trim', explode( "\n", rgar( $form, 'gf_email_blacklist_domains', '' ) ) ) ),
				'patterns'      => array_filter( array_map( 'trim', explode( "\n", rgar( $form, 'gf_email_blacklist_patterns', '' ) ) ) ),
				'error_message' => rgar( $form, 'gf_email_blacklist_error_message', 'This email address is not allowed.' ),
			);
		}

		// Convert arrays back to newline-separated strings for display in the editor
		$emails_text   = implode( "\n", $settings['emails'] );
		$domains_text  = implode( "\n", $settings['domains'] );
		$patterns_text = implode( "\n", $settings['patterns'] );
		$error_message = $settings['error_message'];

		// Add settings fields to the form editor
		$fields['gf_email_blacklist_settings'] = array(
			'title'  => esc_html__( 'Email Blacklist', 'ultimakit-for-wp' ),
			'fields' => array(
				array(
					'id'          => 'gf_email_blacklist_emails',
					'name'        => 'gf_email_blacklist_emails',
					'type'        => 'textarea',
					'label'       => esc_html__( 'Blacklisted Emails (one per line)', 'ultimakit-for-wp' ),
					'description' => esc_html__( 'Enter blacklisted email addresses, one per line.', 'ultimakit-for-wp' ),
					'class'       => 'medium merge-right',
					'value'       => $emails_text,
				),
				array(
					'id'          => 'gf_email_blacklist_domains',
					'name'        => 'gf_email_blacklist_domains',
					'type'        => 'textarea',
					'label'       => esc_html__( 'Blacklisted Domains (one per line)', 'ultimakit-for-wp' ),
					'description' => esc_html__( 'Enter blacklisted domains (e.g., gmail.com), one per line.', 'ultimakit-for-wp' ),
					'class'       => 'medium merge-right',
					'value'       => $domains_text,
				),
				array(
					'id'          => 'gf_email_blacklist_patterns',
					'name'        => 'gf_email_blacklist_patterns',
					'type'        => 'textarea',
					'label'       => esc_html__( 'Blacklist Regex Patterns (one per line)', 'ultimakit-for-wp' ),
					'description' => esc_html__( 'Enter blacklisted regex patterns (e.g., /.ru$/), one per line.', 'ultimakit-for-wp' ),
					'class'       => 'medium merge-right',
					'value'       => $patterns_text,
				),
				array(
					'id'          => 'gf_email_blacklist_error_message',
					'name'        => 'gf_email_blacklist_error_message',
					'type'        => 'text',
					'label'       => esc_html__( 'Error Message', 'ultimakit-for-wp' ),
					'description' => esc_html__( 'Custom error message to display.', 'ultimakit-for-wp' ),
					'class'       => 'medium merge-right',
					'value'       => $error_message,
				),
			),
		);

		return $fields;
	}

	public function gf_email_blacklist_save_settings( $form ) {
		// Retrieve individual settings from the form submission
		$emails        = array_filter( array_map( 'trim', explode( "\n", rgpost( 'gf_email_blacklist_emails' ) ) ) );
		$domains       = array_filter( array_map( 'trim', explode( "\n", rgpost( 'gf_email_blacklist_domains' ) ) ) );
		$patterns      = array_filter( array_map( 'trim', explode( "\n", rgpost( 'gf_email_blacklist_patterns' ) ) ) );
		$error_message = sanitize_text_field( rgpost( 'gf_email_blacklist_error_message' ) );

		// Combine settings into a single JSON field
		$settings = array(
			'emails'        => $emails,
			'domains'       => $domains,
			'patterns'      => $patterns,
			'error_message' => $error_message,
		);

		// Save the serialized settings into the gf_email_blacklist field
		$form['gf_email_blacklist'] = json_encode( $settings );

		return $form;
	}
}
