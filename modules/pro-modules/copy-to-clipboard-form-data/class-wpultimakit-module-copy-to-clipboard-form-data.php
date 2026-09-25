<?php
/**
 * Class UltimaKit_Module_Copy_To_Clipboard_Form_Data
 *
 * @since 1.0.0
 * @package    UltimaKit
 */

/**
 * Class UltimaKit_Module_Copy_To_Clipboard_Form_Data
 *
 * This class provides methods to control the display of dashboard widgets based on user preferences.
 * It allows users to selectively hide certain dashboard widgets to streamline their WordPress dashboard
 * experience and improve usability.
 *
 * @since 1.0.0
 */
class UltimaKit_Module_Copy_To_Clipboard_Form_Data extends UltimaKit_Module_Manager {
	/**
	 * Unique identifier for the Hide Admin Bar module.
	 *
	 * @var string
	 */
	protected $ID = 'ultimakit_module_gf_copy_to_clipboard';

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
	protected $read_more_link = 'disable-defaults-styles-in-gravity-forms';

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
		$this->name        = __( 'Copy Form Data to Clipboard Button', 'ultimakit-for-wp' );
		$this->description = __( 'This module adds a button to Gravity Forms submissions that allows users to copy all form data to their clipboard with a single click.', 'ultimakit-for-wp' );
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

			// Add a custom menu item to the Form Settings page menu
			add_filter( 'gform_form_settings_menu', array( $this, 'wpuk_custom_form_settings_menu_item' ) );

			/*
			 * The save handler must run BEFORE the render handler. Both were registered on
			 * the same hook at the same priority, so they fired in registration order —
			 * render first — and the settings page kept showing the previous values until
			 * the admin reloaded manually.
			 */
			add_action( 'gform_form_settings_page_wpuk_button_settings', array( $this, 'wpuk_save_custom_form_settings' ), 9, 1 );

			// Handle displaying content for our custom menu when selected
			add_action( 'gform_form_settings_page_wpuk_button_settings', array( $this, 'wpuk_custom_form_settings_page' ), 10 );

			add_filter( 'gform_confirmation', array( $this, 'wpuk_add_copy_to_clipboard_button' ), 10, 4 );

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
		$arguments['title'] = __( 'Copy Form Data to Clipboard Button (Gravity Forms)', 'ultimakit-for-wp' );

		$arguments['fields'] = array(
			'enable_global_settings' => array(
				'type'  => 'switch',
				'label' => __( 'Enable Global Settings', 'ultimakit-for-wp' ),
				'value' => $this->getModuleSettings( $this->ID, 'enable_global_settings' ),
			),
			'btn_label'              => array(
				'type'  => 'text',
				'label' => __( 'Button Label', 'ultimakit-for-wp' ),
				'value' => $this->getModuleSettings( $this->ID, 'btn_label' ),
			),
			'btn_txt_color'          => array(
				'type'  => 'color',
				'label' => __( 'Button Text Color', 'ultimakit-for-wp' ),
				'value' => $this->getModuleSettings( $this->ID, 'btn_txt_color' ),
			),
			'btn_bg_color'           => array(
				'type'  => 'color',
				'label' => __( 'Button Background Color', 'ultimakit-for-wp' ),
				'value' => $this->getModuleSettings( $this->ID, 'btn_bg_color' ),
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

		wp_enqueue_style( 'wp-color-picker' );

		wp_enqueue_script(
			'ultimakit-module-script-' . $this->ID,
			plugins_url( '/module-script.js', __FILE__ ),
			array( 'jquery', 'wp-color-picker' ),
			ULTIMAKIT_FOR_WP_VERSION,
			true
		);
	}


	public function wpuk_custom_form_settings_menu_item( $menu_items ) {
		// Check if the menu item already exists
		foreach ( $menu_items as $menu_item ) {
			if ( $menu_item['name'] === 'wpuk_button_settings' ) {
				return $menu_items; // Exit if the item already exists
			}
		}

		// Add the menu item with a custom icon
		$menu_items[] = array(
			'name'  => 'wpuk_button_settings', // Unique identifier
			'label' => __( 'Copy To Clipboard', 'ultimakit-for-wp' ), // Tab label
			'icon'  => 'dashicons dashicons-clipboard', // Icon class
		);

		return $menu_items;
	}


	/**
	 * Whether the settings were just saved, so the render pass can show the notice.
	 *
	 * @var bool
	 */
	private $settings_saved = false;

	public function wpuk_custom_form_settings_page( $form ) {
		// Retrieve saved values or set defaults
		static $executed = false;

		// Prevent duplicate execution
		if ( $executed ) {
			return;
		}
		$executed = true;

		$form_id = rgget( 'id' ); // Gravity Forms appends the form ID as 'id' in the URL
		$form    = GFFormsModel::get_form_meta( $form_id );

		$enable_feature  = ! empty( $form['wpuk_enable_copy_to_clipboard'] );
		$button_label    = rgar( $form, 'wpuk_button_label', 'Copy To Clipboard' );
		$button_color    = rgar( $form, 'wpuk_button_color', '#000000' );
		$button_bg_color = rgar( $form, 'wpuk_button_bg_color', '#FFFFFF' );

		GFFormSettings::page_header();

		if ( $this->settings_saved ) {
			echo '<div id="message" class="updated notice notice-success"><p>' . esc_html__( 'Settings saved.', 'ultimakit-for-wp' ) . '</p></div>';
		}
		?>
		<form method="post" id="gform-settings">
			<?php wp_nonce_field( 'wpuk_save_button_settings', 'wpuk_save_button_settings_nonce' ); ?>
			<h3><?php _e( 'Copy To Clipboard Settings By UltimaKit For Gravity Forms', 'ultimakit-for-wp' ); ?></h3>
			<table class="form-table">
				<tbody>
					<!-- Enable/Disable Copy to Clipboard Feature -->
					<tr>
						<th scope="row">
							<label for="wpuk_enable_copy_to_clipboard"><?php _e( 'Enable/Disable', 'ultimakit-for-wp' ); ?></label>
						</th>
						<td>
							<input type="checkbox" name="wpuk_enable_copy_to_clipboard" id="wpuk_enable_copy_to_clipboard" value="1" <?php checked( $enable_feature ); ?> />
							<p class="description"><?php _e( 'Check to enable the "Copy To Clipboard" feature for this form.', 'ultimakit-for-wp' ); ?></p>
						</td>
					</tr>
					<!-- Button Label -->
					<tr>
						<th scope="row">
							<label for="wpuk_button_label"><?php _e( 'Button Label', 'ultimakit-for-wp' ); ?></label>
						</th>
						<td>
							<input type="text" name="wpuk_button_label" id="wpuk_button_label" value="<?php echo esc_attr( $button_label ); ?>" />
							<p class="description"><?php _e( 'Enter the text to display on the button.', 'ultimakit-for-wp' ); ?></p>
						</td>
					</tr>
					<!-- Button Color -->
					<tr>
						<th scope="row">
							<label for="wpuk_button_color"><?php _e( 'Button Text Color', 'ultimakit-for-wp' ); ?></label>
						</th>
						<td>
							<input type="color" name="wpuk_button_color" id="wpuk_button_color" value="<?php echo esc_attr( $button_color ); ?>" />
							<p class="description"><?php _e( 'Select the text color for the button.', 'ultimakit-for-wp' ); ?></p>
						</td>
					</tr>
					<!-- Button Background Color -->
					<tr>
						<th scope="row">
							<label for="wpuk_button_bg_color"><?php _e( 'Button Background Color', 'ultimakit-for-wp' ); ?></label>
						</th>
						<td>
							<input type="color" name="wpuk_button_bg_color" id="wpuk_button_bg_color" value="<?php echo esc_attr( $button_bg_color ); ?>" />
							<p class="description"><?php _e( 'Select the background color for the button.', 'ultimakit-for-wp' ); ?></p>
						</td>
					</tr>
				</tbody>
			</table>
			<p class="submit">
				<button type="submit" name="submit" class="button button-primary"><?php _e( 'Save Settings', 'ultimakit-for-wp' ); ?></button>
			</p>
		</form>
		<?php

		GFFormSettings::page_footer();
	}

	public function wpuk_save_custom_form_settings( $form ) {

		static $executed = false;

		// Prevent duplicate execution
		if ( $executed ) {
			return;
		}
		$executed = true;

		// Verify the nonce for security
		if ( ! isset( $_POST['wpuk_save_button_settings_nonce'] ) || ! wp_verify_nonce( $_POST['wpuk_save_button_settings_nonce'], 'wpuk_save_button_settings' ) ) {
			return;
		}

		// Get the form ID from the request
		$form_id = rgget( 'id' ); // Gravity Forms appends the form ID as 'id' in the URL

		if ( empty( $form_id ) ) {
			return;
		}

		// Fetch the form data
		$form = GFFormsModel::get_form_meta( $form_id );

		if ( empty( $form ) ) {
			return;
		}

		// Sanitize and save the custom settings
		$form['wpuk_enable_copy_to_clipboard'] = rgpost( 'wpuk_enable_copy_to_clipboard' ) ? true : false;
		$form['wpuk_button_label']             = sanitize_text_field( rgpost( 'wpuk_button_label' ) );
		$form['wpuk_button_color']             = sanitize_hex_color( rgpost( 'wpuk_button_color' ) );
		$form['wpuk_button_bg_color']          = sanitize_hex_color( rgpost( 'wpuk_button_bg_color' ) );

		// Update the form meta
		GFFormsModel::update_form_meta( $form_id, $form );

		/*
		 * Record the notice rather than echoing it here. This callback runs before the page
		 * markup exists, and previously it echoed after GFFormSettings::page_footer() had
		 * already closed the panel, so the message rendered loose at the bottom of the page.
		 */
		$this->settings_saved = true;
	}


	public function wpuk_add_copy_to_clipboard_button( $confirmation, $form, $entry, $ajax ) {

		/*
		 * gform_confirmation passes a STRING for message confirmations but an ARRAY for
		 * redirect confirmations ( array( 'redirect' => $url ) ). Appending to an array is
		 * a fatal TypeError on PHP 8, so any form using a redirect confirmation died on
		 * submit with a white screen. Nothing to append to in that case — hand it back.
		 */
		if ( ! is_string( $confirmation ) ) {
			return $confirmation;
		}

		// Generate the form data
		$form_data = array();
		foreach ( $form['fields'] as $field ) {
			if ( ! empty( $field->displayOnly ) ) {
				continue;
			}

			$label = $field->label;

			/*
			 * Name, Address, Checkbox and Date fields store their data under sub-keys
			 * ("1.3", "1.6", ...) and never under the bare field id, so rgar($entry, $id)
			 * returned nothing and those fields were silently missing from the copied text.
			 */
			if ( is_array( $field->get_entry_inputs() ) ) {
				$raw = RGFormsModel::get_lead_field_value( $entry, $field );
			} else {
				$raw = rgar( $entry, (string) $field->id );
			}

			$value = GFCommon::get_lead_field_display( $field, $raw, rgar( $entry, 'currency' ) );

			if ( is_array( $value ) ) {
				$value = implode( ', ', array_filter( $value, 'strlen' ) );
			}

			$value = trim( wp_strip_all_tags( (string) $value ) );

			if ( '' !== $value ) {
				$form_data[] = "{$label}: {$value}";
			}
		}

		$form_data_string = implode( "\n", $form_data );

		if ( 'on' === $this->getModuleSettings( $this->ID, 'enable_global_settings' ) ) {
			$button_label    = $this->getModuleSettings( $this->ID, 'btn_label' );
			$button_color    = $this->getModuleSettings( $this->ID, 'btn_txt_color', '#000000' );
			$button_bg_color = $this->getModuleSettings( $this->ID, 'btn_bg_color', '#FFFFFF' );
		} else {

			// Check if the feature is enabled for this form
			if ( empty( $form['wpuk_enable_copy_to_clipboard'] ) ) {
				return $confirmation; // Return the default confirmation if not enabled
			}

			$button_label    = rgar( $form, 'wpuk_button_label', 'Copy To Clipboard' );
			$button_color    = rgar( $form, 'wpuk_button_color', '#000000' );
			$button_bg_color = rgar( $form, 'wpuk_button_bg_color', '#FFFFFF' );
		}

		// These land in a CSS colour context, so only allow real colour values through.
		$button_color    = sanitize_hex_color( $button_color ) ? sanitize_hex_color( $button_color ) : '#000000';
		$button_bg_color = sanitize_hex_color( $button_bg_color ) ? sanitize_hex_color( $button_bg_color ) : '#FFFFFF';

		$confirmation .= '<style>#wpuk_copy_button {
				background-color: ' . $button_bg_color . ';
				color: ' . $button_color . ';
				border: none;
				padding: 10px 20px;
				cursor: pointer;
				font-size: 14px;
				border-radius: 5px;
				border: 1px solid ' . $button_color . ';
			}
			#wpuk_copy_button:hover {
				background-color: ' . $button_bg_color . ';
			}
		</style>';
		// Add the Copy to Clipboard button
		$confirmation .= '<div class="gform_footer"><textarea id="wpuk_form_data" style="display:none;">' . esc_textarea( $form_data_string ) . '</textarea>';
		$confirmation .= '<button type="button" id="wpuk_copy_button" class="button">' . esc_html( $button_label ) . '</button>';
		$confirmation .= '
			<script>
				document.getElementById("wpuk_copy_button").addEventListener("click", function() {
					var textArea = document.getElementById("wpuk_form_data");
					textArea.style.display = "block";
					textArea.select();
					document.execCommand("copy");
					textArea.style.display = "none";
					alert("Form data copied to clipboard!");
				});
			</script>
			</div>
		';

		return $confirmation;
	}
}
