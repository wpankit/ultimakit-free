<?php
/**
 * Class UltimaKit_Module_Hide_Form_Field_Labels
 *
 * @since 1.0.0
 * @package    UltimaKit
 */

/**
 * Class UltimaKit_Module_Hide_Form_Field_Labels
 *
 * This class provides methods to control the display of dashboard widgets based on user preferences.
 * It allows users to selectively hide certain dashboard widgets to streamline their WordPress dashboard
 * experience and improve usability.
 *
 * @since 1.0.0
 */
class UltimaKit_Module_Hide_Form_Field_Labels extends UltimaKit_Module_Manager {
	/**
	 * Unique identifier for the Hide Admin Bar module.
	 *
	 * @var string
	 */
	protected $ID = 'ultimakit_module_gf_hide_form_field_labels';

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
	protected $category = 'Disable';

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
	protected $read_more_link = 'hide-form-field-labels-in-gravity-forms';

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
		$this->name        = __( 'Hide Form Field Labels', 'ultimakit-for-wp' );
		$this->description = __( 'This module allows you to hide the labels of form fields in Gravity Forms, giving you more control over the form\'s appearance. It is useful for forms where labels are not required or when placeholders are used instead.', 'ultimakit-for-wp' );
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

			add_filter( 'gform_form_settings_fields', array( $this, 'wpuk_add_hide_label_settings_dropdown' ), 10, 2 );

			add_filter( 'gform_pre_form_settings_save', array( $this, 'wpuk_save_hide_label_settings_dropdown' ) );

			add_filter( 'gform_field_content', array( $this, 'wpuk_apply_hide_label_logic_dropdown' ), 10, 5 );

			add_filter( 'gform_tooltips', array( $this, 'wpuk_add_hide_label_tooltip' ) );

		}
	}


	public function wpuk_add_hide_label_settings_dropdown( $fields, $form ) {
		$hide_label_enabled = rgar( $form, 'hideLabelEnabled', 'no' ); // Default to 'no'

		$fields['hide_label_settings'] = array(
			'title'  => __( 'Hide Label Settings', 'ultimakit-for-wp' ),
			'fields' => array(
				array(
					'type'          => 'select',
					'label'         => __( 'Enable Hide Labels Globally', 'ultimakit-for-wp' ),
					'name'          => 'hide_label_enabled',
					'tooltip'       => __( 'Enable this option to hide all field labels in this form globally.', 'ultimakit-for-wp' ),
					'class'         => 'medium',
					'choices'       => array(
						array(
							'label' => __( 'No', 'ultimakit-for-wp' ),
							'value' => 'no',
						),
						array(
							'label' => __( 'Yes', 'ultimakit-for-wp' ),
							'value' => 'yes',
						),
					),
					'default_value' => $hide_label_enabled,
				),
			),
		);

		return $fields;
	}

	public function wpuk_save_hide_label_settings_dropdown( $form ) {
		/*
		 * Gravity Forms posts settings under _gform_setting_*, so the un-prefixed lookup
		 * never matched and this unconditionally wrote 'no' on every save — while the read
		 * path below uses a different key again ('hide_label_enabled'). Read the prefixed
		 * key and write the same key that is read.
		 */
		if ( isset( $_POST['_gform_setting_hide_label_enabled'] ) ) {
			$form['hide_label_enabled'] = sanitize_text_field( wp_unslash( $_POST['_gform_setting_hide_label_enabled'] ) );
		}

		return $form;
	}

	public function wpuk_apply_hide_label_logic_dropdown( $content, $field, $value, $lead_id, $form_id ) {
		// Cached per request: this fires once per field render.
		static $forms = array();

		if ( ! isset( $forms[ $form_id ] ) ) {
			$forms[ $form_id ] = GFAPI::get_form( $form_id );
		}

		if ( rgar( $forms[ $form_id ], 'hide_label_enabled' ) === 'yes' ) {
			/*
			 * gform_field_content passes the WHOLE field markup, so stripping every <label>
			 * also removed per-choice labels on radio/checkbox fields and the First/Last
			 * sub-labels on Name and Address fields — a radio group rendered as bare,
			 * unlabelled buttons. Only remove the field's own label element.
			 */
			$content = preg_replace(
				'/<label[^>]*class="[^"]*\bgfield_label\b[^"]*"[^>]*>.*?<\/label>/is',
				'',
				$content
			);
		}

		return $content;
	}

	public function wpuk_add_hide_label_tooltip( $tooltips ) {
		$tooltips['hide_label_enabled'] = '<h6>' . esc_html__( 'Hide Labels Globally', 'ultimakit-for-wp' ) . '</h6>' . esc_html__( 'Enable this option to hide all form field labels globally for this form.', 'ultimakit-for-wp' );
		return $tooltips;
	}
}
