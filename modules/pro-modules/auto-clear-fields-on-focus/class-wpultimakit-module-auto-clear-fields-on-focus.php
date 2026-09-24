<?php
/**
 * Class UltimaKit_Module_Auto_Clear_Fields_On_Focus
 *
 * @since 1.0.0
 * @package    UltimaKit
 */

/**
 * Class UltimaKit_Module_Auto_Clear_Fields_On_Focus
 *
 * @since 1.0.0
 */
class UltimaKit_Module_Auto_Clear_Fields_On_Focus extends UltimaKit_Module_Manager {
	/**
	 * @var string
	 */
	protected $ID = 'ultimakit_module_gf_auto_clear_fields_on_focus';

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
	protected $read_more_link = 'auto-clear-form-fields-on-focus-in-gravity-forms';

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
		$this->name        = __( 'Auto-Clear Form Field on Focus', 'ultimakit-for-wp' );
		$this->description = __( 'This module automatically clears the input values of form fields when they gain focus, providing users with a clean slate to enter new information.', 'ultimakit-for-wp' );
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

			// Add a form-specific setting to Gravity Forms.
			add_filter('gform_form_settings_fields', array( $this, 'wpuk_auto_clear_settings_fields' ), 10, 2);

			// Save the form-specific setting.
			add_action('gform_pre_form_settings_save', array( $this, 'wpuk_auto_clear_save_settings' ) );

			add_action('gform_enqueue_scripts', array( $this, 'wpuk_enqueue_auto_clear_scripts' ) );

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
		$arguments['title'] = __( 'Auto-Clear Form Field on Focus', 'ultimakit-for-wp' );

		$arguments['fields'] = array(
			'wpuk_auto_clear_global'         => array(
				'type'  => 'switch',
				'label' => __( 'Enable Auto-Clear Globally', 'ultimakit-for-wp' ),
				'value' => $this->getModuleSettings( $this->ID, 'wpuk_auto_clear_global' ),
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

	public function wpuk_auto_clear_settings_fields($fields, $form) {
		// Retrieve settings from the JSON field or fallback to individual values
		$settings = json_decode(rgar($form, 'wpuk_auto_clear_settings'), true);
		if (empty($settings)) {
			// Default fallback values
			$settings = array(
				'enabled' => 'inherit', // Options: inherit, enabled, disabled
			);
		}
	
		// Extract settings
		$enabled = $settings['enabled'];
	
		// Add settings fields to the form editor
		$fields['wpuk_auto_clear_settings'] = array(
			'title' => esc_html__('Auto-Clear on Focus', 'ultimakit-for-wp'),
			'fields' => array(
				array(
					'id' => 'wpuk_auto_clear_enabled',
					'name' => 'wpuk_auto_clear_enabled',
					'type' => 'select',
					'label' => esc_html__('Enable Auto-Clear on Focus', 'ultimakit-for-wp'),
					'description' => esc_html__('Enable or disable the auto-clear functionality when a field gains focus.', 'ultimakit-for-wp'),
					'class' => 'medium merge-right',
					'choices' => array(
						array('label' => esc_html__('Inherit Global Setting', 'ultimakit-for-wp'), 'value' => 'inherit'),
						array('label' => esc_html__('Enabled', 'ultimakit-for-wp'), 'value' => 'enabled'),
						array('label' => esc_html__('Disabled', 'ultimakit-for-wp'), 'value' => 'disabled'),
					),
					'value' => $enabled,
				),
			),
		);
	
		return $fields;
	}
	
	public function wpuk_auto_clear_save_settings($form) {
		/*
		 * Gravity Forms posts settings under _gform_setting_*, so the un-prefixed read
		 * always returned '' and this stored {"enabled":""} on every save. The value that
		 * is actually read at render time is $form['wpuk_auto_clear_enabled'], so write
		 * that key and drop the junk JSON blob.
		 */
		if ( isset( $_POST['_gform_setting_wpuk_auto_clear_enabled'] ) ) {
			$form['wpuk_auto_clear_enabled'] = sanitize_text_field( wp_unslash( $_POST['_gform_setting_wpuk_auto_clear_enabled'] ) );
		}

		return $form;
	}

	public function wpuk_enqueue_auto_clear_scripts($form) {
		$global_setting = ( 'on' === $this->getModuleSettings( $this->ID, 'wpuk_auto_clear_global' ) ) ? 'enabled' : 'disabled';
		$form_setting   = $form['wpuk_auto_clear_enabled'] ?? 'inherit';

		// Determine if the feature should be enabled for this form.
		$enabled = ($form_setting === 'enabled') || ($form_setting === 'inherit' && $global_setting === 'enabled');

		if ($enabled) {
			$form_id = absint( rgar( $form, 'id' ) );

			/*
			 * Two bugs here previously:
			 *
			 * 1. The selector was not scoped to this form, so enabling the feature on one
			 *    form silently applied it to every Gravity Form on the page.
			 * 2. It matched ALL inputs. For a checkbox or radio, .value is the choice value
			 *    rather than user-entered text, so merely tabbing onto one cleared it and the
			 *    browser posted an empty string — keyboard users silently lost selections.
			 *    Same for file inputs (cleared the chosen file) and ranges.
			 *
			 * Binding on gform_post_render rather than DOMContentLoaded also means it still
			 * works for AJAX forms and after a validation re-render, where DOMContentLoaded
			 * has already fired.
			 */
			wp_add_inline_script('jquery', "
				( function ( \$ ) {
					var wpukFormId = " . $form_id . ";
					var wpukSelector = 'input[type=\"text\"], input[type=\"email\"], input[type=\"tel\"], input[type=\"url\"], input[type=\"number\"], input[type=\"password\"], input[type=\"search\"], textarea';

					function wpukBindAutoClear( formId ) {
						if ( formId && parseInt( formId, 10 ) !== wpukFormId ) {
							return;
						}

						var form = document.getElementById( 'gform_' + wpukFormId );
						if ( ! form ) {
							return;
						}

						form.querySelectorAll( '.gfield ' + wpukSelector.split( ', ' ).join( ', .gfield ' ) ).forEach( function ( field ) {
							if ( field.dataset.wpukAutoClearBound ) {
								return;
							}
							field.dataset.wpukAutoClearBound = '1';
							field.addEventListener( 'focus', function () {
								if ( field.value ) {
									field.value = '';
								}
							} );
						} );
					}

					\$( document ).on( 'gform_post_render', function ( event, formId ) {
						wpukBindAutoClear( formId );
					} );

					\$( function () {
						wpukBindAutoClear( wpukFormId );
					} );
				} )( jQuery );
			");
		}
	}
	

}