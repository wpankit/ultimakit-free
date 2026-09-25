<?php
/**
 * Class UltimaKit_Module_Lock_Submissions_After_A_Deadline
 *
 * @since 1.0.0
 * @package    UltimaKit
 */

/**
 * Class UltimaKit_Module_Lock_Submissions_After_A_Deadline
 *
 * This class provides methods to control the display of dashboard widgets based on user preferences.
 * It allows users to selectively hide certain dashboard widgets to streamline their WordPress dashboard
 * experience and improve usability.
 *
 * @since 1.0.0
 */
class UltimaKit_Module_Lock_Submissions_After_A_Deadline extends UltimaKit_Module_Manager {
	/**
	 * Unique identifier for the Hide Admin Bar module.
	 *
	 * @var string
	 */
	protected $ID = 'ultimakit_module_gf_lock_submissions_after_a_deadline';

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
	protected $read_more_link = 'lock-form-submissions-after-a-deadline-in-gravity-forms';

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
		$this->name        = __( 'Lock Form Submissions After a Deadline', 'ultimakit-for-wp' );
		$this->description = __( 'Set a deadline after which submissions will be locked.', 'ultimakit-for-wp' );
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
			// Display a message if the submission deadline has passed
			add_filter( 'gform_get_form_filter', array( $this, 'wpuk_check_submission_deadline' ), 10, 2 );

			/*
			 * gform_get_form_filter only swaps the rendered markup, so anyone holding a page
			 * that was loaded before the deadline — an open tab, the back button, a cached
			 * page — or anyone POSTing directly could still submit and have the entry stored.
			 * Enforce the deadline server-side at validation time as well.
			 */
			add_filter( 'gform_validation', array( $this, 'wpuk_validate_submission_deadline' ) );
			add_filter( 'gform_validation_message', array( $this, 'wpuk_deadline_validation_message' ), 10, 2 );

			// Save custom form settings
			add_filter( 'gform_pre_form_settings_save', array( $this, 'wpuk_save_custom_form_settings' ) );

			add_filter( 'gform_form_settings_fields', array( $this, 'wpuk_add_custom_form_settings' ), 10, 2 );
		}
	}

	/**
	 * Form IDs whose submission was rejected because the deadline had passed.
	 *
	 * @var array
	 */
	private $deadline_locked_forms = array();

	/**
	 * Whether this form's submission deadline is in the past.
	 *
	 * @param array $form Gravity Forms form object.
	 * @return bool
	 */
	protected function wpuk_deadline_has_passed( $form ) {
		$submission_deadline = isset( $form['submission_deadline'] ) ? trim( (string) $form['submission_deadline'] ) : '';

		if ( '' === $submission_deadline ) {
			return false;
		}

		/*
		 * The deadline is free text sanitized only with sanitize_text_field(), so an
		 * unparseable value used to throw an uncaught DateTime exception — a fatal on every
		 * front-end page rendering the form. Treat anything unparseable as "no deadline".
		 */
		try {
			$wp_timezone   = wp_timezone();
			$current_time  = new DateTime( 'now', $wp_timezone );
			$deadline_time = new DateTime( $submission_deadline, $wp_timezone );
		} catch ( Exception $e ) {
			return false;
		}

		return $current_time > $deadline_time;
	}

	/**
	 * The configured lock message, or a sensible default.
	 *
	 * @param array $form Gravity Forms form object.
	 * @return string
	 */
	protected function wpuk_get_lock_message( $form ) {
		return ! empty( $form['lock_message'] )
			? $form['lock_message']
			: __( 'This form is no longer accepting submissions.', 'ultimakit-for-wp' );
	}

	/**
	 * Reject submissions made after the deadline.
	 *
	 * @param array $validation_result Gravity Forms validation result.
	 * @return array
	 */
	public function wpuk_validate_submission_deadline( $validation_result ) {
		$form = rgar( $validation_result, 'form' );

		if ( is_array( $form ) && $this->wpuk_deadline_has_passed( $form ) ) {
			$validation_result['is_valid'] = false;

			$this->deadline_locked_forms[ (int) rgar( $form, 'id' ) ] = true;
		}

		return $validation_result;
	}

	/**
	 * Show the lock message instead of the generic validation error.
	 *
	 * @param string $message Existing validation message.
	 * @param array  $form    Gravity Forms form object.
	 * @return string
	 */
	public function wpuk_deadline_validation_message( $message, $form ) {
		if ( isset( $this->deadline_locked_forms[ (int) rgar( $form, 'id' ) ] ) ) {
			return '<div class="validation_error gform_deadline_message">' . esc_html( $this->wpuk_get_lock_message( $form ) ) . '</div>';
		}

		return $message;
	}

	public function wpuk_add_custom_form_settings( $fields, $form ) {
		$submission_deadline = isset( $form['submission_deadline'] ) ? esc_attr( $form['submission_deadline'] ) : '';
		$lock_message        = isset( $form['lock_message'] ) ? esc_textarea( $form['lock_message'] ) : __( 'This form is no longer accepting submissions. The submission deadline has passed.', 'ultimakit-for-wp' );

		$fields['submission_settings'] = array(
			'title'  => __( 'Lock Form Submissions After a Deadline', 'ultimakit-for-wp' ),
			'fields' => array(
				array(
					'id'      => 'submission_deadline',
					'name'    => 'submission_deadline',
					'label'   => __( 'Submission Deadline', 'ultimakit-for-wp' ),
					'type'    => 'text',
					'value'   => $submission_deadline,
					'tooltip' => __( 'Set a deadline after which submissions will be locked.', 'ultimakit-for-wp' ),
					'class'   => 'datetime-picker',
				),
				array(
					'id'      => 'lock_message',
					'name'    => 'lock_message',
					'label'   => __( 'Lock Message', 'ultimakit-for-wp' ),
					'type'    => 'textarea',
					'value'   => $lock_message,
					'tooltip' => __( 'Message to display when the form is locked.', 'ultimakit-for-wp' ),
				),
			),
		);

		add_action(
			'admin_enqueue_scripts',
			function () {
				wp_enqueue_script( 'jquery-ui-datepicker' );

				wp_enqueue_script(
					'jquery-ui-timepicker-addon',
					plugins_url( '/jquery-ui-timepicker-addon.min.js', __FILE__ ),
					array( 'jquery', 'jquery-ui-datepicker' ),
					ULTIMAKIT_FOR_WP_VERSION,
					true
				);

				wp_enqueue_style(
					'jquery-ui-css',
					plugins_url( '/jquery-ui.css', __FILE__ ),
				);

				wp_enqueue_style(
					'jquery-ui-timepicker-addon-css',
					plugins_url( '/jquery-ui-timepicker-addon.min.css', __FILE__ ),
				);
			}
		);

		// Add inline JavaScript and CSS for the date-time picker and calendar icon
		add_action(
			'admin_footer',
			function () {
				?>
			<style>
				.datetime-picker {
					padding-right: 30px;
					background: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" fill="gray" viewBox="0 0 24 24" width="24" height="24"><path d="M19 4h-1V2h-2v2H8V2H6v2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 16H5V10h14v10zm0-12H5V6h14v2z"></path></svg>') no-repeat right 5px center;
					background-size: 16px;
				}
				.ui-datepicker {
					font-size: 14px;
					width: auto;
					padding: 10px;
					box-sizing: border-box;
				}
				.ui-datepicker-calendar td {
					text-align: center;
					vertical-align: middle;
				}
				.ui-datepicker td span,
				.ui-datepicker td a {
					display: block;
					padding: 5px;
					text-align: center;
				}
				.ui-datepicker-calendar {
					width: 100%;
				}
				.ui-datepicker-calendar table {
					width: 100%;
					table-layout: fixed;
				}
				.ui-datepicker-header {
					background-color: #f7f7f7;
					border-bottom: 1px solid #ccc;
				}
				.ui-datepicker-prev, .ui-datepicker-next {
					cursor: pointer;
				}
			</style>
			<script type="text/javascript">
				document.addEventListener('DOMContentLoaded', function () {
					const datetimeFields = document.querySelectorAll('.datetime-picker');
					datetimeFields.forEach(function (field) {
						if (typeof jQuery !== 'undefined' && typeof jQuery.ui !== 'undefined') {
							jQuery(field).datetimepicker({
								dateFormat: 'yy-mm-dd',
								timeFormat: 'HH:mm:ss',
								showButtonPanel: true,
								controlType: 'select',
								oneLine: true,
							});
						}
					});
				});
			</script>
				<?php
			}
		);

		return $fields;
	}

	public function wpuk_save_custom_form_settings( $form ) {
		$form['submission_deadline'] = isset( $_POST['_gform_setting_submission_deadline'] ) ? sanitize_text_field( $_POST['_gform_setting_submission_deadline'] ) : '';
		$form['lock_message']        = isset( $_POST['_gform_setting_lock_message'] ) ? sanitize_textarea_field( $_POST['_gform_setting_lock_message'] ) : '';

		return $form;
	}

	public function wpuk_check_submission_deadline( $form_string, $form ) {
		// Compare the current time with the deadline
		if ( $this->wpuk_deadline_has_passed( $form ) ) {
			$lock_message = $this->wpuk_get_lock_message( $form );

			// Replace the form with the lock message
			return '<style>.gform_deadline_message {
					padding: 20px;
					background-color: #f8d7da;
					color: #721c24;
					border: 1px solid #f5c6cb;
					border-radius: 5px;
					font-size: 16px;
					margin-bottom: 20px;
					text-align: center;
				}</style><div class="gform_deadline_message">' . esc_html( $lock_message ) . '</div>';
		}

		// If the deadline has not passed, return the form as usual
		return $form_string;
	}
}
