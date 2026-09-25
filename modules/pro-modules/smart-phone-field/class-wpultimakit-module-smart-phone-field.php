<?php
/**
 * Class UltimaKit_Module_Smart_Phone_Field
 *
 * @since 1.0.0
 * @package    UltimaKit
 */

/**
 * Class UltimaKit_Module_Smart_Phone_Field
 *
 * @since 1.0.0
 */
class UltimaKit_Module_Smart_Phone_Field extends UltimaKit_Module_Manager {
	/**
	 * @var string
	 */
	protected $ID = 'ultimakit_module_gf_smartphone_field';

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
	protected $read_more_link = 'add-smart-phone-field-in-gravity-forms';

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
		$this->name        = __( 'Smart Phone Field', 'ultimakit-for-wp' );
		$this->description = __( 'The field includes a dropdown for selecting the country code and a phone number input field.', 'ultimakit-for-wp' );
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
			add_action( 'wp_enqueue_scripts', array( $this, 'add_front_scripts' ), 99 );

			add_action( 'gform_field_standard_settings', array( $this, 'wpuk_smart_phone_standard_settings' ), 10, 2 );

			add_action( 'gform_editor_js', array( $this, 'wpuk_smart_phone_editor_js' ) );

			add_action( 'gform_editor_js_set_default_values', array( $this, 'wpuk_gform_editor_js_set_default_values' ) );

			add_filter( 'gform_pre_form_settings_save', array( $this, 'wpuk_gform_pre_form_settings_save' ) );

			add_action(
				'gform_pre_submission',
				function ( $form ) {
					foreach ( $form['fields'] as &$field ) {
						if ( $field->type === 'smart_phone' ) {
							$field_id     = $field->id;
							$phone        = rgpost( "input_{$field_id}" );
							$country_code = rgpost( "input_{$field_id}_country_code" );

							// Only a dial code may be prepended to the number: digits with an optional leading "+".
							$country_code = is_string( $country_code ) ? trim( $country_code ) : '';
							if ( ! preg_match( '/^\+?[0-9]+\z/', $country_code ) ) {
								$country_code = '';
							}

							if ( ! empty( $phone ) && ! empty( $country_code ) ) {
								// Check if the phone number already starts with the country code
								if ( strpos( $phone, $country_code ) !== 0 ) {
									// Append the country code only if it's not already present
									$_POST[ "input_{$field_id}" ] = "{$country_code}{$phone}";
								} else {
									// Keep the phone number as it is
									$_POST[ "input_{$field_id}" ] = $phone;
								}
							}
						}
					}
				}
			);

		}
	}

	public function add_front_scripts() {

		// intl-tel-input 17.0.19 (MIT), bundled to match intlTelInput.min.js; its flag sprites live in img/.
		wp_enqueue_style(
			'ultimakit-module-style-input-' . $this->ID,
			plugins_url( '/css/intlTelInput.css', __FILE__ ),
			array(),
			'17.0.19'
		);

		wp_enqueue_script(
			'ultimakit-module-script-input-' . $this->ID,
			plugins_url( '/intlTelInput.min.js', __FILE__ ),
			array( 'jquery' ),
			ULTIMAKIT_FOR_WP_VERSION,
			true
		);

		// Localize the script
		$localized_data = array(
			'ajax_url'       => admin_url( 'admin-ajax.php' ), // For AJAX requests
			'utils_script'   => plugins_url( '/utils.js', __FILE__ ), // Path to utils.js
			'errorMessage'   => __( 'Invalid number - please try again', 'ultimakit-for-wp' ),
			'successMessage' => __( 'Valid number! Full international format: ', 'ultimakit-for-wp' ),
		);

		// Pass the data to the script
		wp_localize_script(
			'ultimakit-module-script-input-' . $this->ID,
			'UltimaKitData', // JS object name
			$localized_data
		);
	}

	public function wpuk_smart_phone_standard_settings( $position, $form_id ) {
		$helper    = new UltimaKit_Helpers();
		$countries = $helper->get_countries();
		if ( $position == 25 ) { ?>
			<li class="wpuk_dial_mode field_setting">
				<label for="wpuk_dial_mode"><?php _e( 'Flag Options', 'ultimakit-for-wp' ); ?><button onclick="return false;" onkeypress="return false;" class="gf_tooltip tooltip tooltip_spf_flag_tooltips" aria-label="<?php echo esc_html_e( 'Choose flag option for getting flag and dial code in input field.', 'ultimakit-for-wp' ); ?>">
				<i class="gform-icon gform-icon--question-mark" aria-hidden="true"></i></button></label>
				<select name="wpuk_dial_mode" id="wpuk_dial_mode" onchange="SetFieldProperty('wpuk_dial_mode', this.value);">
					<option value=""><?php echo esc_html_e( 'Choose Flag', 'ultimakit-for-wp' ); ?></option>
					<option value="flagdial"><?php echo esc_html_e( 'Flag with dial code', 'ultimakit-for-wp' ); ?></option>
					<option value="flag"><?php echo esc_html_e( 'Flag only', 'ultimakit-for-wp' ); ?></option>
				</select>
			</li>
			<li class="wpuk_default_country field_setting">
				<label for="wpuk_default_country"><?php _e( 'Default Country', 'ultimakit-for-wp' ); ?></label>
				<select name="wpuk_default_country" id="wpuk_default_country" onchange="SetFieldProperty('wpuk_default_country', this.value);">
					<option value="">None</option>
					<?php
					if ( ! empty( $countries ) ) {
						foreach ( $countries as $code => $name ) {
							echo '<option value="' . esc_attr( $code ) . '">' . esc_html( $name ) . '</option>';
						}
					}
					?>
				</select>
			</li>
			<li class="wpuk_preferred_countries field_setting">
				<label for="wpuk_preferred_countries"><?php _e( 'Preferred Countries', 'ultimakit-for-wp' ); ?></label>
				<select 
					name="wpuk_preferred_countries[]" 
					id="wpuk_preferred_countries" 
					multiple="multiple" 
					onchange="SetFieldProperty('wpuk_preferred_countries', jQuery(this).val());"
					style="min-height: 100px"
				>
					<?php
					if ( ! empty( $countries ) ) {
						foreach ( $countries as $code => $name ) {
							echo '<option value="' . esc_attr( $code ) . '">' . esc_html( $name ) . '</option>';
						}
					}
					?>
				</select>
			</li>
			<li class="wpuk_hide_dial_code field_setting">
				<label for="wpuk_hide_dial_code"><?php _e( 'Show/Hide Country Code', 'ultimakit-for-wp' ); ?><button onclick="return false;" onkeypress="return false;" class="gf_tooltip tooltip tooltip_spf_flag_tooltips" aria-label="Choose option to show or hide country code in input field.">
				<i class="gform-icon gform-icon--question-mark" aria-hidden="true"></i>
			</button></label>
				<select name="wpuk_hide_dial_code" id="wpuk_hide_dial_code" onchange="SetFieldProperty('wpuk_hide_dial_code', this.value);">
					<option value=""><?php echo esc_html_e( 'Choose option', 'ultimakit-for-wp' ); ?></option>
					<option value="show"><?php echo esc_html_e( 'Show Country Code', 'ultimakit-for-wp' ); ?></option>
					<option value="hide"><?php echo esc_html_e( 'Hide Country Code', 'ultimakit-for-wp' ); ?></option>
				</select>
			</li>
	
			<?php
		}
	}

	public function wpuk_smart_phone_editor_js() {

		?>
		<script type="text/javascript">
			jQuery(document).on('gform_load_field_settings', function(event, field) {
				jQuery('#wpuk_default_country').val(field.wpuk_default_country || '');
				jQuery('#wpuk_only_country').val(field.wpuk_only_country || '');
				jQuery('#wpuk_dial_mode').val(field.wpuk_dial_mode || '');
				const preferredCountries = field.wpuk_preferred_countries || [];
				jQuery('#wpuk_preferred_countries')
					.val(preferredCountries)
					.trigger('change');
			});
		</script>
		<?php
	}

	public function wpuk_gform_editor_js_set_default_values() {
		?>
		/*
		 * gform_editor_js_set_default_values injects into a `switch ( field.type )`, so these
		 * cases have to be the FIELD TYPE, not property names. Keyed on property names none
		 * of them ever matched and a newly added Smart Phone field got no defaults at all.
		 *
		 * wpuk_preferred_countries must default to an array: it is passed to implode() when
		 * the field renders, and a string there is a fatal TypeError on PHP 8.
		 * 'flagcode' was also not one of the two values the setting offers (flagdial / flag).
		 */
		case 'smart_phone':
			field.wpuk_default_country = '';
			field.wpuk_only_country = '';
			field.wpuk_preferred_countries = [];
			field.wpuk_dial_mode = 'flag';
			break;
		<?php
	}

	public function wpuk_gform_pre_form_settings_save( $form ) {
		if ( isset( $_POST['wpuk_default_country'] ) ) {
			$form['wpuk_default_country'] = sanitize_text_field( $_POST['wpuk_default_country'] );
		}
		if ( isset( $_POST['wpuk_only_country'] ) ) {
			$form['wpuk_only_country'] = sanitize_text_field( $_POST['wpuk_only_country'] );
		}
		if ( isset( $_POST['wpuk_preferred_countries'] ) ) {
			$form['wpuk_preferred_countries'] = array_map( 'sanitize_text_field', $_POST['wpuk_preferred_countries'] );
		} else {
			$form['wpuk_preferred_countries'] = array();
		}

		return $form;
	}
}


if ( class_exists( 'GF_Field' ) ) {
	class WPUK_GF_Field_SmartPhone extends GF_Field {
		public $type = 'smart_phone';

		public function get_form_editor_field_title() {
			return esc_attr__( 'Smart Phone Field', 'ultimakit-for-wp' );
		}

		public function get_form_editor_button() {
			return array(
				'group' => 'advanced_fields',
				'text'  => $this->get_form_editor_field_title(),
				'icon'  => 'dashicons dashicons-phone',
			);
		}

		public function get_form_editor_field_settings() {
			/*
			 * 'label_setting' was listed twice, and 'wpuk_national_mode' has no matching
			 * settings markup rendered anywhere, so it referenced a control that never
			 * existed.
			 */
			return array(
				'label_setting',
				'description_setting',
				'css_class_setting',
				'wpuk_default_country',
				'wpuk_preferred_countries',
				'wpuk_dial_mode',
				'wpuk_hide_dial_code',
				'conditional_logic_field_setting',
				'error_message_setting',
				'label_placement_setting',
				'admin_label_setting',
				'rules_setting',
				'visibility_setting',
			);
		}

		public function get_field_input( $form, $value = '', $entry = null ) {
			$field_id    = $this->id;
			$form_id     = $form['id'];
			$saved_value = esc_attr( $value );

			$dial_mode       = isset( $this->wpuk_dial_mode ) ? $this->wpuk_dial_mode : false;
			$default_country = isset( $this->wpuk_default_country ) ? $this->wpuk_default_country : 'US';
			// isset() only guards "unset", not "wrong type" — implode() on a string is a
			// fatal TypeError on PHP 8, so normalise to an array here.
			$pref_country = isset( $this->wpuk_preferred_countries ) ? $this->wpuk_preferred_countries : array();
			if ( is_string( $pref_country ) ) {
				$pref_country = array_filter( array_map( 'trim', explode( ',', $pref_country ) ) );
			} elseif ( ! is_array( $pref_country ) ) {
				$pref_country = array();
			}
			$hide_dial_code = isset( $this->wpuk_hide_dial_code ) ? $this->wpuk_hide_dial_code : '';
			ob_start();
			echo '<style>.wpuk-phone-container{display: flex; flex-direction: column; gap: 8px; } .wpuk-phone { width: 100%; padding: 10px; font-size: 16px; }.iti__country,.iti__selected-flag{font-size:16px;}.success_br{border-color:green !important; transition: background-color 0.3s ease-in-out;}.warning_br{border-color:red !important; transition: background-color 0.3s ease-in-out;}</style>';
			?>
			<div id="wpuk-phone-container-<?php echo esc_attr( $field_id ); ?>" class="wpuk-phone-container">
				<input 
					id="wpuk-phone-<?php echo esc_attr( $field_id ); ?>"
					class="wpuk-phone" 
					type="tel" 
					name="input_<?php echo esc_attr( $field_id ); ?>"
					value="<?php echo $saved_value; ?>" 
					data-field-id="<?php echo esc_attr( $field_id ); ?>"
					data-dial-mode="<?php echo esc_attr( $dial_mode === 'flagdial' ? 'true' : 'false' ); ?>"
					data-default-country="<?php echo esc_attr( $default_country ); ?>"
					data-pref-country="<?php echo esc_attr( implode( ',', $pref_country ) ); ?>"
					data-hide-dial-code="<?php echo esc_attr( $hide_dial_code === 'show' ? 'false' : 'true' ); ?>"
				/>
				<input type="hidden" id="input_<?php echo esc_attr( $field_id ); ?>_country_code" name="input_<?php echo esc_attr( $field_id ); ?>_country_code" value="" />
			</div>
			<script>
				jQuery(document).ready(function($) {
					$('.wpuk-phone').each(function() {
						const phoneInput = $(this);
						const fieldId = phoneInput.data('field-id');
						const countryCodeInput = $(`#input_${fieldId}_country_code`);
						const dialMode = phoneInput.data('dial-mode') === true || phoneInput.data('dial-mode') === 'true';
						const hideDialCode = phoneInput.data('hide-dial-code') === true || phoneInput.data('hide-dial-code') === 'true';
						const initialCountry = phoneInput.data('default-country') || 'US';
						const prefCountry = phoneInput.data('pref-country') || '';

						// Parse preferred countries into an array
						const preferredCountries = prefCountry ? prefCountry.split(',').map(country => country.trim()) : [];

						const options = {
							initialCountry: initialCountry,
							strictMode: true,
							separateDialCode: dialMode,
							nationalMode: hideDialCode,
							preferredCountries: preferredCountries,
							utilsScript: "<?php echo plugins_url( '/utils.js', __FILE__ ); ?>", // Path to utils.js
						};

						// Initialize the library
						if (phoneInput.length > 0) {
							try {
								const iti = intlTelInput(phoneInput[0], options);

								/*
								 * Seed the hidden input immediately. It was only populated by the
								 * countrychange event, so users who accepted the default country —
								 * most of them — never fired it, the server-side handler saw an
								 * empty country code and never prepended the dial code. The stored
								 * number was then inconsistent with users who did switch country.
								 */
								const wpukSyncDialCode = function () {
									if (countryCodeInput.length > 0) {
										const countryData = iti.getSelectedCountryData();
										if (countryData && countryData.dialCode) {
											countryCodeInput.val("+" + countryData.dialCode);
										}
									}
								};

								wpukSyncDialCode();

								// Update the hidden input with the country code
								phoneInput.on("countrychange", wpukSyncDialCode);

								const reset = () => {
									if (iti.isValidNumber()) {
										phoneInput.removeClass('warning_br').addClass('success_br');
									} else {
										phoneInput.removeClass('success_br').addClass('warning_br');
									}
								};
								phoneInput.on('blur', reset );
							} catch (error) {
								console.error("Error initializing intlTelInput:", error);
							}
						}
					});
				});
			</script>

			<?php
			return ob_get_clean();
		}
	}

	GF_Fields::register( new WPUK_GF_Field_SmartPhone() );
}
