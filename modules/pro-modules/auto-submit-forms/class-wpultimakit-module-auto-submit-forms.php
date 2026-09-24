<?php
/**
 * Class UltimaKit_Module_Auto_Submit_Forms
 *
 * @since 1.0.0
 * @package    UltimaKit
 */

/**
 * Class UltimaKit_Module_Auto_Submit_Forms
 *
 * This class provides methods to control the display of dashboard widgets based on user preferences.
 * It allows users to selectively hide certain dashboard widgets to streamline their WordPress dashboard
 * experience and improve usability.
 *
 * @since 1.0.0
 */
class UltimaKit_Module_Auto_Submit_Forms extends UltimaKit_Module_Manager {
	/**
	 * @var string
	 */
	protected $ID = 'ultimakit_module_gf_auto_submit_forms';

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
	protected $category = 'Forms';

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
	protected $read_more_link = 'auto-submit-forms-on-last-field-in-gravity-forms';

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
		$this->name        = __( 'Auto-Submit on Last Field', 'ultimakit-for-wp' );
		$this->description = __( 'Automatically submits the form when the last field is completed, streamlining user experience and reducing submission steps. Includes settings to enable or disable the feature on a per-form basis.', 'ultimakit-for-wp' );
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
			
			// Add form settings field
			add_filter('gform_form_settings_fields', array( $this, 'wpuk_add_auto_submit_settings' ), 10, 2);
			
			// Save form settings
			add_filter('gform_pre_form_settings_save', array( $this, 'wpuk_save_auto_submit_setting' ) );
			
			// Add inline script to enable auto-submit functionality
			add_action('gform_enqueue_scripts', array( $this, 'wpuk_enqueue_auto_submit_script' ) );

		}	
	}

	public function wpuk_add_auto_submit_settings($fields, $form) {
		$form_setting = isset($form['wpuk_auto_submit_enabled']) ? $form['wpuk_auto_submit_enabled'] : '0';

		$fields['auto_submit_settings'] = [
			'title'  => __('Auto-Submit Settings', 'ultimakit-for-wp'),
			'fields' => [
				[
					'type'          => 'select',
					'label'         => __('Enable Auto-Submit', 'ultimakit-for-wp'),
					'name'          => 'wpuk_auto_submit_enabled', // Unique key
					'tooltip'       => __('Automatically submits the form when the last field is filled.', 'ultimakit-for-wp'),
					'choices'       => [
						[
							'label' => __('No', 'ultimakit-for-wp'),
							'value' => '0',
						],
						[
							'label' => __('Yes', 'ultimakit-for-wp'),
							'value' => '1',
						],
					],
					'default_value' => $form_setting,
				],
			],
		];

		return $fields;
	}

	public function wpuk_save_auto_submit_setting($form) {
		/*
		 * The Gravity Forms settings framework posts fields under _gform_setting_*, so the
		 * un-prefixed key never matched and this branch never ran. (The setting still
		 * persisted only because GF core copies posted values onto $form itself.)
		 */
		if ( isset( $_POST['_gform_setting_wpuk_auto_submit_enabled'] ) ) {
			$form['wpuk_auto_submit_enabled'] = sanitize_text_field( wp_unslash( $_POST['_gform_setting_wpuk_auto_submit_enabled'] ) );
		}

		return $form;
	}

	public function wpuk_enqueue_auto_submit_script($form) {
		if (!empty($form['wpuk_auto_submit_enabled']) && $form['wpuk_auto_submit_enabled'] === '1') {
			$form_id = absint( $form['id'] );

			/*
			 * Three problems previously:
			 *
			 * 1. Gravity Forms appends its honeypot as the LAST field, so with Honeypot
			 *    enabled the trigger was an input the user never touches and auto-submit
			 *    simply never fired. Hidden/submit inputs and the validation container are
			 *    now excluded when picking the last field.
			 * 2. form.submit() is the NATIVE method and does not dispatch a submit event, so
			 *    Gravity Forms' own handler never ran — no AJAX path, no multi-page handling,
			 *    no spinner, no HTML5 validation. Trigger a real submit instead.
			 * 3. DOMContentLoaded has already fired for AJAX-rendered forms and re-renders
			 *    after a validation failure, so the behaviour was lost there.
			 */
			wp_add_inline_script('jquery', "
				( function ( \$ ) {
					var wpukFormId = " . $form_id . ";

					function wpukBindAutoSubmit( formId ) {
						if ( formId && parseInt( formId, 10 ) !== wpukFormId ) {
							return;
						}

						var form = document.getElementById( 'gform_' + wpukFormId );
						if ( ! form ) { return; }

						var candidates = Array.prototype.filter.call(
							form.querySelectorAll( '.gfield input, .gfield textarea, .gfield select' ),
							function ( el ) {
								if ( el.type === 'hidden' || el.type === 'submit' || el.type === 'button' ) { return false; }
								// Skip the honeypot / validation container.
								if ( el.closest( '.gform_validation_container' ) ) { return false; }
								return el.offsetParent !== null;
							}
						);

						var lastField = candidates[ candidates.length - 1 ];
						if ( ! lastField || lastField.dataset.wpukAutoSubmitBound ) { return; }
						lastField.dataset.wpukAutoSubmitBound = '1';

						lastField.addEventListener( 'change', function () {
							setTimeout( function () {
								// Go through jQuery so Gravity Forms' own submit handler runs.
								\$( form ).trigger( 'submit' );
							}, 500 );
						} );
					}

					\$( document ).on( 'gform_post_render', function ( event, formId ) {
						wpukBindAutoSubmit( formId );
					} );

					\$( function () {
						wpukBindAutoSubmit( wpukFormId );
					} );
				} )( jQuery );
			");
		}
	}


}
