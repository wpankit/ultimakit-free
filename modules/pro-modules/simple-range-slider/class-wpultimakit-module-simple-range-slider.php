<?php
/**
 * Class UltimaKit_Module_Simple_Range_Slider
 *
 * @since 1.0.0
 * @package    UltimaKit
 */

/**
 * Class UltimaKit_Module_Simple_Range_Slider
 *
 * This class provides methods to control the display of dashboard widgets based on user preferences.
 * It allows users to selectively hide certain dashboard widgets to streamline their WordPress dashboard
 * experience and improve usability.
 *
 * @since 1.0.0
 */
class UltimaKit_Module_Simple_Range_Slider extends UltimaKit_Module_Manager {
	/**
	 * Unique identifier for the Hide Admin Bar module.
	 *
	 * @var string
	 */
	protected $ID = 'ultimakit_module_gf_simple_range_slider';

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
	protected $read_more_link = 'add-simple-range-slider-field-in-gravity-forms';

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
		$this->name        = __( 'Simple Range Slider', 'ultimakit-for-wp' );
		$this->description = __( 'This module introduces a customizable Range Slider field for Gravity Forms.', 'ultimakit-for-wp' );
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
			/**
			 * Add custom settings for Min, Max, and Default in form editor.
			*/
			add_action('gform_field_standard_settings', array( $this, 'wpuk_range_slider_standard_settings' ), 10, 2);

			/**
			 * Load saved settings into editor.
			 */
			add_action('gform_editor_js', array( $this, 'wpuk_range_slider_editor_js'));

			/**
			 * Set default values when the field is added.
			 */
			add_action('gform_editor_js_set_default_values', array( $this, 'wpuk_range_slider_set_defaults'));
		}	
	}

	public function wpuk_range_slider_standard_settings($position, $form_id) {
		if ($position == 25) { ?>
			<li class="wpuk_min_setting field_setting">
				<label for="wpuk_min_setting"><?php _e('Min Value', 'ultimakit-for-wp'); ?></label>
				<input type="number" id="wpuk_min_setting" class="fieldwidth-3" onchange="SetFieldProperty('wpukMin', this.value);" />
			</li>
			<li class="wpuk_max_setting field_setting">
				<label for="wpuk_max_setting"><?php _e('Max Value', 'ultimakit-for-wp'); ?></label>
				<input type="number" id="wpuk_max_setting" class="fieldwidth-3" onchange="SetFieldProperty('wpukMax', this.value);" />
			</li>
			<li class="wpuk_default_setting field_setting">
				<label for="wpuk_default_setting"><?php _e('Default Value', 'ultimakit-for-wp'); ?></label>
				<input type="number" id="wpuk_default_setting" class="fieldwidth-3" onchange="SetFieldProperty('wpukDefault', this.value);" />
			</li>
		<?php }
	}

	public function wpuk_range_slider_editor_js() { ?>
		<script type="text/javascript">
			jQuery(document).on('gform_load_field_settings', function(event, field) {
				jQuery('#wpuk_min_setting').val(field.wpukMin || 0);
				jQuery('#wpuk_max_setting').val(field.wpukMax || 100);
				jQuery('#wpuk_default_setting').val(field.wpukDefault || 50);
			});
		</script>
	<?php }

	public function wpuk_range_slider_set_defaults() { ?>
		case "wpuk_range_slider":
			field.wpukMin = 0;
			field.wpukMax = 100;
			field.wpukDefault = 50;
			break;
	<?php }

}


if (class_exists('GF_Field')) {
    class WPUK_GF_Field_RangeSlider extends GF_Field {
        public $type = 'wpuk_range_slider';

        /**
         * Field Title in Form Editor
         */
        public function get_form_editor_field_title() {
            return esc_attr__('Range Slider', 'ultimakit-for-wp');
        }

        /**
         * Add custom button to Form Editor Advanced Fields
         */
        public function get_form_editor_button() {
            return array(
                'group' => 'advanced_fields',
                'text'  => $this->get_form_editor_field_title(),
            );
        }

        /**
         * Add settings for Min, Max, Default Value
         */
        public function get_form_editor_field_settings() {
            return array(
                'label_setting',
                'description_setting',
                'css_class_setting',
                'wpuk_min_setting',
                'wpuk_max_setting',
                'wpuk_default_setting',
            );
        }

        /**
         * Reject values the slider cannot produce: non-numeric input, or a number outside the
         * configured min/max. The browser enforces both, but a crafted POST skips the browser.
         * A blank value is left alone, as in GF_Field_Number (required is checked by GF itself).
         */
        public function validate($value, $form) {
            if (rgblank($value)) {
                return;
            }

            // Same bounds get_field_input() renders.
            $min_value = isset($this->wpukMin) ? intval($this->wpukMin) : 0;
            $max_value = isset($this->wpukMax) ? intval($this->wpukMax) : 100;

            // Like the browser, treat a max below the min as the min.
            $max_value = max($min_value, $max_value);

            if (!is_numeric($value) || floatval($value) < $min_value || floatval($value) > $max_value) {
                $this->failed_validation  = true;
                $this->validation_message = empty($this->errorMessage)
                    /* translators: 1: minimum value, 2: maximum value. */
                    ? sprintf(esc_html__('Please select a value from %1$s to %2$s.', 'ultimakit-for-wp'), $min_value, $max_value)
                    : $this->errorMessage;
            }
        }

        /**
         * Render the field input on the frontend
         */
        public function get_field_input($form, $value = '', $entry = null) {
            $form_id   = $form['id'];
            $field_id  = $this->id;
            $min_value = isset($this->wpukMin) ? intval($this->wpukMin) : 0;
            $max_value = isset($this->wpukMax) ? intval($this->wpukMax) : 100;
            $default_value = isset($this->wpukDefault) ? intval($this->wpukDefault) : 50;

            /*
             * get_field_input() also renders the admin entry-edit screen and any re-render
             * after a validation failure. Always emitting the default meant the slider showed
             * 50 regardless of what was submitted, and saving an entry persisted that 50 over
             * the real answer.
             */
            $current_value = ( '' !== $value && null !== $value ) ? intval( $value ) : $default_value;
            $current_value = max( $min_value, min( $max_value, $current_value ) );

            ob_start();
            ?>
			<div class="wpuk-range-control">
				<input 
					id="wpuk_inputRange_<?php echo esc_attr($form_id . '_' . $field_id); ?>" 
					type="range" 
					min="<?php echo esc_attr($min_value); ?>" 
					max="<?php echo esc_attr($max_value); ?>" 
					value="<?php echo esc_attr($current_value); ?>" 
					data-thumbwidth="20" 
					name="input_<?php echo esc_attr($field_id); ?>"
					class="wpuk-range-slider"
				>
				<output class="wpuk-range-output" id="wpuk_output_<?php echo esc_attr($form_id . '_' . $field_id); ?>"><?php echo esc_html($current_value); ?></output>
			</div>
            <style>
                .wpuk-range-control {
                    position: relative;
                }
                .wpuk-range-slider {
                    display: block;
                    width: 100%;
                    margin: 0;
                    -webkit-appearance: none;
                    outline: none;
                    border: none !important;
                    background: none !important;
                    box-shadow: none !important;
                }
                .wpuk-range-slider::-webkit-slider-runnable-track {
                    position: relative;
                    height: 12px;
                    border: 1px solid #b2b2b2;
                    border-radius: 5px;
                    background-color: #e2e2e2;
                    box-shadow: inset 0 1px 2px 0 rgba(0, 0, 0, 0.1);
                }
                .wpuk-range-slider::-webkit-slider-thumb {
                    position: relative;
                    top: -5px;
                    width: 20px;
                    height: 20px;
                    border: 1px solid #999;
                    -webkit-appearance: none;
                    background-color: #fff;
                    box-shadow: inset 0 -1px 2px 0 rgba(0, 0, 0, 0.25);
                    border-radius: 100%;
                    cursor: pointer;
                }
                .wpuk-range-output {
                    position: absolute;
                    top: -32px;
                    display: none;
                    width: 50px;
                    height: 24px;
                    border: 1px solid #e2e2e2;
                    background-color: #fff;
                    border-radius: 3px;
                    color: #777;
                    font-size: .8em;
                    line-height: 24px;
                    text-align: center;
                }
                .wpuk-range-slider:active + .wpuk-range-output {
                    display: block;
                    transform: translateX(-50%);
                }
            </style>
            <script>
                function wpukRangeSetup() {
					const rangeInputs = document.querySelectorAll('input[type="range"].wpuk-range-slider');

					rangeInputs.forEach(function(control) {
						// Bind once, even when re-run after an AJAX re-render.
						if (control.dataset.wpukRangeBound) {
							control.dispatchEvent(new Event('input'));
							return;
						}
						control.dataset.wpukRangeBound = '1';

						control.addEventListener('input', function() {
							const controlMin = parseFloat(control.min) || 0;
							const controlMax = parseFloat(control.max) || 100;
							const controlVal = parseFloat(control.value);
							const controlThumbWidth = parseFloat(control.getAttribute('data-thumbwidth')) || 20; // Default thumb width

							const range = controlMax - controlMin;
							const position = ((controlVal - controlMin) / range) * 100;

							// Calculate position offset
							const positionOffset = Math.round(controlThumbWidth * position / 100) - (controlThumbWidth / 2);

							const output = control.nextElementSibling; // Select the output element directly
							if (output) {
								output.style.left = `calc(${position}% - ${positionOffset}px)`;
								output.textContent = controlVal;
							}
						});

						// Trigger input event on load to align output initially
						control.dispatchEvent(new Event('input'));
					});
				}

				/*
				 * Run on DOMContentLoaded and again on gform_post_render: for AJAX forms and
				 * re-renders after a validation failure DOMContentLoaded has already fired,
				 * so the slider output would never have been wired up.
				 */
				if (document.readyState === 'loading') {
					document.addEventListener('DOMContentLoaded', wpukRangeSetup);
				} else {
					wpukRangeSetup();
				}

				if (window.jQuery) {
					jQuery(document).on('gform_post_render', wpukRangeSetup);
				}
            </script>
            <?php
            return ob_get_clean();
        }
    }

    // Register the field.
    GF_Fields::register(new WPUK_GF_Field_RangeSlider());
}