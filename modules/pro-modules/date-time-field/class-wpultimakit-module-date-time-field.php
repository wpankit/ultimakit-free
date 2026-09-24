<?php
/**
 * Class UltimaKit_Module_Date_Time_Field
 *
 * @since 1.0.0
 * @package    UltimaKit
 */

/**
 * Class UltimaKit_Module_Date_Time_Field
 *
 * @since 1.0.0
 */
class UltimaKit_Module_Date_Time_Field extends UltimaKit_Module_Manager {
	/**
	 * @var string
	 */
	protected $ID = 'ultimakit_module_gf_date_time_field';

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
	protected $read_more_link = 'add-date-time-field-in-gravity-forms';

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
		$this->name        = __( 'Date-Time Field', 'ultimakit-for-wp' );
		$this->description = __( 'The Date-Time Field Add-On enhances Gravity Forms with advanced date and time functionalities.', 'ultimakit-for-wp' );
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
			add_action('admin_enqueue_scripts', array( $this, 'enqueue_wpuk_gf_datetime_admin_styles' ) );
			add_filter('gform_add_field_buttons', array( $this, 'add_wpuk_gf_datetime_field_button' ) );
			/*
			 * gform_field_type_classes is NOT a Gravity Forms filter — it appears nowhere in
			 * the GF source — so GF_Field_wpuk_gf_DateTime was never registered and
			 * GF_Fields::create() fell through to a plain GF_Field. The class's validate(),
			 * get_field_input() and get_form_editor_field_title() were all dead code, and the
			 * editor showed the raw type string as the field title. GF_Fields::register() is
			 * the documented API, and is what every other custom field here uses.
			 */
			if ( class_exists( 'GF_Fields' ) && class_exists( 'GF_Field_wpuk_gf_DateTime' ) && ! GF_Fields::exists( 'wpuk_gf_datetime' ) ) {
				GF_Fields::register( new GF_Field_wpuk_gf_DateTime() );
			}
			add_filter('gform_field_input', array( $this, 'render_wpuk_gf_datetime_field_input'), 10, 5);
			add_filter('gform_field_validation', array( $this, 'validate_wpuk_gf_datetime_field_input'), 10, 4);
			add_action('gform_admin_pre_render', array( $this, 'wpuk_gf_datetime_editor_script'));
			add_action('gform_field_advanced_settings', array( $this, 'wpuk_gf_datetime_advanced_settings'), 10, 2);
			add_filter('gform_entry_field_value', array( $this, 'wpuk_gf_datetime_entry_display'), 10, 4);

		}
	}

	public function enqueue_wpuk_gf_datetime_admin_styles() {
		if (GFForms::get_page() === 'form_editor') {
			wp_enqueue_style('wpuk-gf-datetime-icon', plugin_dir_url(__FILE__) . 'admin-styles.css');
		}
	}
	
	public function add_wpuk_gf_datetime_field_button($field_groups) {
		foreach ($field_groups as &$group) {
			if ($group['name'] === 'advanced_fields') {
				// Ensure the field is added only once
				$already_added = false;
				foreach ($group['fields'] as $field) {
					if (isset($field['onclick']) && $field['onclick'] === "StartAddField('wpuk_gf_datetime');") {
						$already_added = true;
						break;
					}
				}
	
				if (!$already_added) {
					$group['fields'][] = array(
						'class'   => 'button wpuk-gf-datetime-field-icon', // Add custom icon class
						'value'   => __('Date-Time', 'ultimakit-for-wp'),
						'onclick' => "StartAddField('wpuk_gf_datetime');"
					);
				}
				break;
			}
		}
		return $field_groups;
	}
	

	public function render_wpuk_gf_datetime_field_input($input, $field, $value, $lead_id, $form_id) {
		if ($field->type === 'wpuk_gf_datetime') {
			$min_date = esc_attr($field->minDate);
			$max_date = esc_attr($field->maxDate);
	
			return sprintf(
				'<div class="ginput_container ginput_container_wpuk_gf_datetime">
					<input type="datetime-local" name="input_%d" id="input_%d_%d" value="%s" %s %s />
				</div>',
				$field->id,
				$form_id,
				$field->id,
				esc_attr($value),
				$min_date ? 'min="' . $min_date . '"' : '',
				$max_date ? 'max="' . $max_date . '"' : ''
			);
		}
		return $input;
	}	

	public function validate_wpuk_gf_datetime_field_input($result, $value, $form, $field) {
		if ($field->type === 'wpuk_gf_datetime') {
			if ($field->isRequired && empty($value)) {
				$result['is_valid'] = false;
				$result['message'] = __('This field is required.', 'ultimakit-for-wp');
			} elseif (!empty($value)) {
				$timestamp = strtotime($value);
				if (!empty($field->minDate) && $timestamp < strtotime($field->minDate)) {
					$result['is_valid'] = false;
					$result['message'] = sprintf(__('Date must be on or after %s.', 'ultimakit-for-wp'), $field->minDate);
				}
				if (!empty($field->maxDate) && $timestamp > strtotime($field->maxDate)) {
					$result['is_valid'] = false;
					$result['message'] = sprintf(__('Date must be on or before %s.', 'ultimakit-for-wp'), $field->maxDate);
				}
			}
		}
		return $result;
	}

	public function wpuk_gf_datetime_editor_script($form) {
		if (GFForms::get_page() !== 'form_editor') {
			return $form;
		}
		?>
		<script type="text/javascript">
			jQuery(document).ready(function() {
				// Register custom field settings for the wpuk_gf_datetime field
				fieldSettings.wpuk_gf_datetime = ".label_setting, .admin_label_setting, .rules_setting, .required_setting, .visibility_setting, .description_setting, .css_class_setting, .wpuk_gf_datetime_constraint_setting, .wpuk_gf_datetime_format_setting";
	
				// Bind custom settings to the form editor
				jQuery(document).on("gform_load_field_settings", function(event, field) {
					if (field.type === "wpuk_gf_datetime") {
						jQuery("#wpuk_gf_datetime_min_date").val(field.minDate || "");
						jQuery("#wpuk_gf_datetime_max_date").val(field.maxDate || "");
					}
				});
	
				// Save settings when they are changed
				jQuery("#wpuk_gf_datetime_min_date").on("change", function() {
					SetFieldProperty("minDate", this.value);
				});
				jQuery("#wpuk_gf_datetime_max_date").on("change", function() {
					SetFieldProperty("maxDate", this.value);
				});
			});
		</script>
		<?php
		return $form;
	}

	public function wpuk_gf_datetime_advanced_settings($position, $form_id) {
		// Check the field position
		if ($position === 50 && !did_action('wpuk_gf_datetime_advanced_settings_rendered')) { 
			// Ensure the settings are rendered only once
			do_action('wpuk_gf_datetime_advanced_settings_rendered'); 
			?>
			<li class="wpuk_gf_datetime_constraint_setting field_setting">
				<label for="wpuk_gf_datetime_min_date">
					<?php _e('Minimum Date', 'ultimakit-for-wp'); ?>
				</label>
				<input type="datetime-local" id="wpuk_gf_datetime_min_date" onchange="SetFieldProperty('minDate', this.value);" />
			</li>
	
			<li class="wpuk_gf_datetime_constraint_setting field_setting">
				<label for="wpuk_gf_datetime_max_date">
					<?php _e('Maximum Date', 'ultimakit-for-wp'); ?>
				</label>
				<input type="datetime-local" id="wpuk_gf_datetime_max_date" onchange="SetFieldProperty('maxDate', this.value);" />
			</li>
			<?php
		}
	}

	public function wpuk_gf_datetime_entry_display($value, $field, $entry, $form) {
		if ($field->type === 'wpuk_gf_datetime') {
			$date_format = !empty($field->dateFormat) ? $field->dateFormat : 'Y-m-d H:i';
			$value = !empty($value) ? date($date_format, strtotime($value)) : '';
		}
		return $value;
	}

}


// Same guard as the other custom field modules: without it a missing Gravity Forms
// folder turned every request into a fatal "Class GF_Field not found".
if (class_exists('GF_Field')) {
class GF_Field_wpuk_gf_DateTime extends GF_Field {
    public $type = 'wpuk_gf_datetime';

	public function __construct($data = array()) {
        parent::__construct($data);
        $this->label       = rgar($data, 'label', __('Date-Time', 'ultimakit-for-wp'));
        $this->adminLabel  = rgar($data, 'adminLabel', '');
        $this->description = rgar($data, 'description', '');
        $this->cssClass    = rgar($data, 'cssClass', '');
        $this->isRequired  = rgar($data, 'isRequired', false);
        $this->visibility  = rgar($data, 'visibility', 'visible');
        $this->minDate     = rgar($data, 'minDate', '');
        $this->maxDate     = rgar($data, 'maxDate', '');
        $this->dateFormat  = rgar($data, 'dateFormat', 'Y-m-d H:i');
    }

    public function get_form_editor_field_title() {
        return __('Date-Time', 'ultimakit-for-wp');
    }

    public function get_form_editor_field_settings() {
		return array(
			'label_setting',
			'admin_label_setting',
			'rules_setting',
			'required_setting',
			'visibility_setting',
			'description_setting',
			'css_class_setting',
			'wpuk_gf_datetime_constraint_setting', // Min/Max Date
		);
	}

    public function validate($value, $form) {
        if ($this->isRequired && empty($value)) {
            $this->failed_validation = true;
            $this->validation_message = __('This field is required.', 'ultimakit-for-wp');
        }
        if (!empty($value)) {
            $timestamp = strtotime($value);
            if ($timestamp === false) {
                $this->failed_validation = true;
                $this->validation_message = __('Invalid date format.', 'ultimakit-for-wp');
                // Without returning, false coerces to 0 below and the message was replaced
                // with a misleading "Date must be on or after ..." error.
                return;
            }
            if (!empty($this->minDate) && $timestamp < strtotime($this->minDate)) {
                $this->failed_validation = true;
                $this->validation_message = sprintf(__('Date must be on or after %s.', 'ultimakit-for-wp'), $this->minDate);
            }
            if (!empty($this->maxDate) && $timestamp > strtotime($this->maxDate)) {
                $this->failed_validation = true;
                $this->validation_message = sprintf(__('Date must be on or before %s.', 'ultimakit-for-wp'), $this->maxDate);
            }
        }
    }

    public function get_field_input($form, $value = '', $entry = null) {
        $min_date = esc_attr($this->minDate);
        $max_date = esc_attr($this->maxDate);
        $css_class = esc_attr($this->cssClass);

        return sprintf(
            '<div class="ginput_container ginput_container_wpuk_gf_datetime">
                <input type="datetime-local" name="input_%d" id="input_%d_%d" value="%s" class="large %s" %s %s />
            </div>',
            $this->id,
            $form['id'],
            $this->id,
            esc_attr($value),
            $css_class,
            $min_date ? 'min="' . $min_date . '"' : '',
            $max_date ? 'max="' . $max_date . '"' : ''
        );
    }
}
}
