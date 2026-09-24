<?php
/**
 * Class UltimaKit_Module_Pre_Filled_Form_Fields
 *
 * @since 1.0.0
 * @package    UltimaKit
 */

/**
 * Class UltimaKit_Module_Pre_Filled_Form_Fields
 *
 * This class provides methods to control the display of dashboard widgets based on user preferences.
 * It allows users to selectively hide certain dashboard widgets to streamline their WordPress dashboard
 * experience and improve usability.
 *
 * @since 1.0.0
 */
class UltimaKit_Module_Pre_Filled_Form_Fields extends UltimaKit_Module_Manager {
	/**
	 * @var string
	 */
	protected $ID = 'ultimakit_module_gf_pre_filled_form_fields';

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
	protected $read_more_link = 'pre-filled-form-fields-via-url-parameters-in-gravity-forms';

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
		$this->name        = __( 'Pre-Filled Form Fields via URL Parameters', 'ultimakit-for-wp' );
		$this->description = __( 'This module allows you to pre-fill Gravity Forms fields dynamically using URL parameters. It supports a wide range of field types, including text, checkboxes, multiple select, and name fields.', 'ultimakit-for-wp' );
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
			
			add_filter('gform_pre_render', array($this, 'prefill_fields'), 10, 2);
			// Not hooked to gform_admin_pre_render: a crafted form-editor link could otherwise
			// plant URL values as defaults that are then saved with the form.
			add_filter('gform_pre_submission_filter', array($this, 'prefill_fields'), 10, 2);
			
			// Add settings
			add_filter('gform_form_settings_menu', array($this, 'add_prefill_settings_tab'));
			add_action('gform_form_settings_page_prefill', array($this, 'render_prefill_settings_page'));
		}	
	}

	public function add_prefill_settings_tab($tabs) {
        $tabs[] = array(
            'name'         => 'prefill',
            'label'        => esc_html__('Pre-Fill Settings', 'ultimakit-for-wp'),
            'icon'         => 'gform-icon--settings',
            'capabilities' => array('gravityforms_edit_forms'),
        );
        return $tabs;
    }

    public function prefill_fields($form, $ajax = false) {
        if (empty($form['fields'])) {
            return $form;
        }

        $enabled_fields = $this->get_enabled_fields($form['id']);
        if (empty($enabled_fields)) {
            return $form;
        }

        foreach ($form['fields'] as &$field) {
            if (!in_array($field->id, $enabled_fields)) {
                continue;
            }

            // Handle name fields
            if ($field->type === 'name') {
                $this->handle_name_field($field, $form['id']);
                continue;
            }

            // Handle checkbox fields
            if ($field->type === 'checkbox') {
                $this->handle_checkbox_field($field, $form['id']);
                continue;
            }

            // Handle multiple select fields
            if ($field->type === 'multiselect') {
                $this->handle_multiselect_field($field, $form['id']);
                continue;
            }

            $parameter_name = $this->get_parameter_name($field, $form['id']);
            $value = $this->get_parameter_value($parameter_name);

            if ($value !== null) {
                $field->defaultValue = $this->sanitize_value($value, $field);
            }
        }

        return $form;
    }

    private function handle_name_field(&$field, $form_id) {
        $prefix = $this->get_parameter_prefix($form_id);
        
        $name_inputs = array(
            'prefix' => 'namePrefix',
            'first' => 'firstName',
            'middle' => 'middleName',
            'last' => 'lastName',
            'suffix' => 'nameSuffix'
        );

        foreach ($name_inputs as $input_key => $property) {
            $param_name = $prefix . $field->id . '_' . $input_key;
            $value = $this->get_parameter_value($param_name);
            
            if ($value !== null) {
                foreach ($field->inputs as &$input) {
                    // Exact match: strpos() let field id "13" collide with the ".3" probe.
                    if ( (string) $input['id'] === $field->id . '.' . $this->get_name_input_id($input_key) ) {
                        $input['defaultValue'] = sanitize_text_field($value);
                        break;
                    }
                }
            }
        }
    }

    private function handle_checkbox_field(&$field, $form_id) {
        $prefix = $this->get_parameter_prefix($form_id);

        if (!empty($field->inputs)) {
            foreach ($field->inputs as &$input) {
                // Ensure all required properties exist
                if (!isset($input['isSelected'])) {
                    $input['isSelected'] = false;
                }
                if (!isset($input['defaultValue'])) {
                    $input['defaultValue'] = '0';
                }

                $input_id = str_replace('.', '_', $input['id']);
                $param_name = $prefix . $input_id;
                $value = $this->get_parameter_value($param_name);

                if ($value !== null) {
                    $is_checked = filter_var($value, FILTER_VALIDATE_BOOLEAN);
                    
                    // Update both properties
                    $input['isSelected'] = $is_checked;
                    $input['defaultValue'] = $is_checked ? '1' : '0';

                    // Also set the field's value
                    if (!isset($field->choices)) {
                        $field->choices = array();
                    }

                    // Find and update the corresponding choice
                    foreach ($field->choices as &$choice) {
                        if (isset($choice['text']) && $choice['text'] === $input['label']) {
                            $choice['isSelected'] = $is_checked;
                            break;
                        }
                    }
                }
            }
        } else {
            $param_name = $prefix . $field->id;
            $value = $this->get_parameter_value($param_name);

            if ($value !== null) {
                $is_checked = filter_var($value, FILTER_VALIDATE_BOOLEAN);
                $field->defaultValue = $is_checked ? '1' : '0';
            }
        }
    }

    private function handle_multiselect_field(&$field, $form_id) {
        $prefix = $this->get_parameter_prefix($form_id);
        $param_name = $prefix . $field->id;
        $value = $this->get_parameter_value($param_name);

        if ($value !== null) {
            // Handle both array and comma-separated string values
            $selected_values = is_array($value) ? $value : explode(',', $value);
            $selected_values = array_map('trim', $selected_values);
            

            // Validate and sanitize values against available choices
            $valid_values = array();
            foreach ($field->choices as &$choice) {
                if (in_array($choice['value'], $selected_values)) {
                    $choice['isSelected'] = true;
                    $valid_values[] = $choice['value'];
                } else {
                    $choice['isSelected'] = false;
                }
            }

            // Set the default value as a comma-separated string
            $field->defaultValue = implode(',', $valid_values);
        }
    }

    private function get_name_input_id($input_key) {
        /*
         * Gravity Forms' Name field uses {id}.2 for Prefix, not {id}.1 — see
         * GF_Field_Name (input ids .2 prefix, .3 first, .4 middle, .6 last, .8 suffix).
         * With '1' the prefix probe matched no input, so ?field_5_prefix=Dr was silently
         * ignored while every other name part worked.
         */
        $input_ids = array(
            'prefix' => '2',
            'first' => '3',
            'middle' => '4',
            'last' => '6',
            'suffix' => '8'
        );
        return isset($input_ids[$input_key]) ? $input_ids[$input_key] : '';
    }

    private function get_parameter_prefix($form_id) {
        $prefix = get_option("gf_prefill_parameter_prefix_{$form_id}");
        return $prefix ? $prefix : 'field_';
    }

    private function get_parameter_name($field, $form_id) {
        $prefix = $this->get_parameter_prefix($form_id);
        return $prefix . $field->id;
    }

    private function get_enabled_fields($form_id) {
        return get_option("gf_prefill_enabled_fields_{$form_id}", array());
    }

    private function get_parameter_value($parameter) {
        if (!isset($_GET[$parameter])) {
            return null;
        }

        /*
         * Strip { and }: URL values become field default values, which Gravity Forms runs
         * through merge-tag replacement, so ?field_1={embed_post:post_password} or
         * {custom_field:_any_key} would otherwise show post data to any visitor.
         */

        // Handle array values (for multiple select)
        if (is_array($_GET[$parameter])) {
            return str_replace(array('{', '}'), '', array_map('sanitize_text_field', $_GET[$parameter]));
        }

        return str_replace(array('{', '}'), '', sanitize_text_field($_GET[$parameter]));
    }

    private function sanitize_value($value, $field) {
        switch ($field->type) {
            case 'multiselect':
                if (is_array($value)) {
                    return array_map('sanitize_text_field', $value);
                }
                return sanitize_text_field($value);
            case 'date':
                return $this->sanitize_date($value);
            case 'checkbox':
                return $this->sanitize_checkbox_value($value);
            case 'select':
            case 'radio':
                return $this->sanitize_choice_value($value, $field);
            case 'name':
                return $this->sanitize_name_value($value);
            default:
                return sanitize_text_field($value);
        }
    }

    private function sanitize_date($value) {
        return sanitize_text_field($value);
    }

    private function sanitize_checkbox_value($value) {
        if (is_array($value)) {
            return array_map('sanitize_text_field', $value);
        }
        return sanitize_text_field($value);
    }

    private function sanitize_choice_value($value, $field) {
        $choices = $field->choices;
        if (empty($choices)) {
            return sanitize_text_field($value);
        }

        $valid_values = array_column($choices, 'value');
        return in_array($value, $valid_values) ? $value : '';
    }

    private function sanitize_name_value($value) {
        return sanitize_text_field($value);
    }

    public function render_prefill_settings_page() {
        $form_id = rgget('id');
        $form = GFAPI::get_form($form_id);
        
        if ($this->is_form_settings_submission()) {
            $this->save_prefill_settings($form_id);
            ?>
            <div class="updated notice">
                <p><?php esc_html_e('Settings updated successfully.', 'ultimakit-for-wp'); ?></p>
            </div>
            <?php
        }

        $enabled_fields = $this->get_enabled_fields($form_id);
        $prefix = $this->get_parameter_prefix($form_id);

        GFFormSettings::page_header();
        ?>
        <div class="gform-settings-panel">
            <header class="gform-settings-panel__header">
                <h4 class="gform-settings-panel__title"><?php esc_html_e('Pre-Fill Settings', 'ultimakit-for-wp'); ?></h4>
            </header>
            <div class="gform-settings-panel__content">
                <form method="post">
                    <?php wp_nonce_field('gform_prefill_settings', 'gform_prefill_settings_nonce'); ?>

                    <div class="gform-settings-field">
                        <label class="gform-settings-label" for="prefill_parameter_prefix">
                            <?php esc_html_e('Parameter Prefix', 'ultimakit-for-wp'); ?>
                        </label>
                        <span class="gform-settings-input__container">
                            <input type="text" 
                                   id="prefill_parameter_prefix" 
                                   name="prefill_parameter_prefix" 
                                   value="<?php echo esc_attr($prefix); ?>" 
                                   class="medium">
                            <span class="gform-settings-description">
                                <?php esc_html_e('Enter the prefix for URL parameters (default: field_)', 'ultimakit-for-wp'); ?>
                            </span>
                        </span>
                    </div>

                    <div class="gform-settings-field">
                        <label class="gform-settings-label">
                            <?php esc_html_e('Enable Pre-fill for Fields', 'ultimakit-for-wp'); ?>
                        </label>
                        <span class="gform-settings-input__container">
                            <div class="prefill-fields-container">
                                <?php foreach ($form['fields'] as $field): ?>
                                    <div class="prefill-field-item">
                                        <label>
                                            <input type="checkbox" 
                                                   name="prefill_enabled_fields[]" 
                                                   value="<?php echo esc_attr($field->id); ?>"
                                                   <?php checked(in_array($field->id, $enabled_fields)); ?>>
                                            <?php echo esc_html($field->label); ?> 
                                            <span class="field-id">(ID: <?php echo esc_html($field->id); ?>)</span>
                                        </label>
                                        <div class="prefill-field-param">
                                            <?php if ($field->type === 'checkbox' && !empty($field->inputs)): ?>
                                                <?php esc_html_e('URL Parameters:', 'ultimakit-for-wp'); ?><br>
                                                <?php foreach ($field->inputs as $input): ?>
                                                    <div class="checkbox-param">
                                                        <?php 
                                                        $input_id = str_replace('.', '_', $input['id']);
                                                        $param_code = $prefix . $input_id;
                                                        ?>
                                                        <span class="checkbox-label"><?php echo esc_html($input['label']); ?>:</span>
                                                        <code><?php echo esc_html($param_code); ?>=1</code>
                                                    </div>
                                                <?php endforeach; ?>
                                            <?php elseif ($field->type === 'multiselect'): ?>
                                                <?php esc_html_e('URL Parameter Examples:', 'ultimakit-for-wp'); ?><br>
                                                <div class="multiselect-param">
                                                    <span><?php esc_html_e('Comma-separated:', 'ultimakit-for-wp'); ?></span>
                                                    <code><?php echo esc_html($prefix . $field->id); ?>=value1,value2,value3</code>
                                                </div>
                                                <div class="multiselect-param">
                                                    <span><?php esc_html_e('Array notation:', 'ultimakit-for-wp'); ?></span>
                                                    <code><?php echo esc_html($prefix . $field->id); ?>[]=value1&<?php echo esc_html($prefix . $field->id); ?>[]=value2</code>
                                                </div>
                                            <?php elseif ($field->type === 'name'): ?>
                                                <?php
                                                $name_parts = array(
                                                    'prefix' => 'Prefix',
                                                    'first' => 'First',
                                                    'middle' => 'Middle',
                                                    'last' => 'Last',
                                                    'suffix' => 'Suffix'
                                                );
                                                foreach ($name_parts as $key => $label):
                                                    if ($key === 'first' || $key === 'last' || 
                                                        ($key === 'middle' && $field->nameFormat === 'extended') || 
                                                        ($key === 'prefix' && $field->nameFormat === 'extended') || 
                                                        ($key === 'suffix' && $field->nameFormat === 'extended')):
                                                    ?>
                                                        <div>
                                                            <?php echo esc_html($label); ?>: 
                                                            <code><?php echo esc_html($prefix . $field->id . '_' . $key); ?></code>
                                                        </div>
                                                    <?php
                                                    endif;
                                                endforeach;
                                                ?>
                                            <?php else: ?>
                                                <?php esc_html_e('URL Parameter:', 'ultimakit-for-wp'); ?> 
                                                <code><?php echo esc_html($prefix . $field->id); ?></code>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </span>
                    </div>

                    <div class="gform-settings-save-container">
                        <button type="submit" 
                                name="gform_save_settings" 
                                value="save" 
                                class="primary button large">
                            <?php esc_html_e('Save Settings', 'ultimakit-for-wp'); ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <style>
            .prefill-fields-container {
                max-height: 400px;
                overflow-y: auto;
                border: 1px solid #ddd;
                padding: 15px;
                background: #fff;
                border-radius: 3px;
                margin-top: 10px;
            }
            .prefill-field-item {
                margin-bottom: 12px;
                padding-bottom: 12px;
                border-bottom: 1px solid #eee;
            }
            .prefill-field-item:last-child {
                border-bottom: none;
                margin-bottom: 0;
                padding-bottom: 0;
            }
            .prefill-field-param {
                margin-left: 25px;
                margin-top: 5px;
                color: #666;
                font-size: 12px;
            }
            .prefill-field-param code {
                background: #f5f5f5;
                padding: 2px 5px;
                border-radius: 3px;
            }
            .field-id {
                color: #666;
                font-size: 12px;
            }
            .checkbox-param {
                margin: 5px 0 5px 20px;
            }
            .checkbox-label {
                display: inline-block;
                min-width: 150px;
                color: #666;
            }
            .multiselect-param {
                margin: 5px 0 5px 20px;
            }
            .multiselect-param span {
                display: inline-block;
                min-width: 120px;
                color: #666;
            }
        </style>
        <?php
        GFFormSettings::page_footer();
    }

    private function is_form_settings_submission() {
        return rgpost('gform_save_settings') && check_admin_referer('gform_prefill_settings', 'gform_prefill_settings_nonce');
    }

    private function save_prefill_settings($form_id) {
        $enabled_fields = rgpost('prefill_enabled_fields') ? rgpost('prefill_enabled_fields') : array();
        $enabled_fields = array_map('intval', $enabled_fields);
        
        $parameter_prefix = rgpost('prefill_parameter_prefix');
        $parameter_prefix = sanitize_text_field($parameter_prefix);

        if (empty($parameter_prefix)) {
            $parameter_prefix = 'field_';
        }

        update_option("gf_prefill_enabled_fields_{$form_id}", $enabled_fields);
        update_option("gf_prefill_parameter_prefix_{$form_id}", $parameter_prefix);
    }

}
