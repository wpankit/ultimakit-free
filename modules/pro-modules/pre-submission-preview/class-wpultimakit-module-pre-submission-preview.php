<?php
/**
 * Class UltimaKit_Module_Pre_Submission_Preview
 *
 * @since 1.0.0
 * @package    UltimaKit
 */

/**
 * Class UltimaKit_Module_Pre_Submission_Preview
 *
 * This class provides methods to control the display of dashboard widgets based on user preferences.
 * It allows users to selectively hide certain dashboard widgets to streamline their WordPress dashboard
 * experience and improve usability.
 *
 * @since 1.0.0
 */
class UltimaKit_Module_Pre_Submission_Preview extends UltimaKit_Module_Manager {
	/**
	 * @var string
	 */
	protected $ID = 'ultimakit_module_gf_pre_submissions_preview';

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
	protected $category = 'Content';

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
	protected $read_more_link = 'pre-submissions-preview-in-model-in-gravity-forms';

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
		$this->name        = __( 'Pre-Submission Preview', 'ultimakit-for-wp' );
		$this->description = __( 'Enable users to review all form entries in a preview format before final submission, reducing errors and improving completion accuracy.', 'ultimakit-for-wp' );
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
			
			add_action( 'wp_enqueue_scripts', array( $this, 'add_scripts' ) );

			// Add the "Preview" button to Gravity Forms
			add_filter('gform_submit_button', [$this, 'gf_pre_submission_preview_add_preview_button'], 10, 2);

			// Generate the preview modal
			add_action('wp_footer', [$this, 'gf_pre_submission_preview_modal']);
		}	
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
			'ultimakit-module-script-front-' . $this->ID,
			plugins_url( '/front-js.js', __FILE__ ),
			array( 'jquery' ),
			ULTIMAKIT_FOR_WP_VERSION,
			true
		);

		wp_enqueue_style(
			'ultimakit-module-style-front-' . $this->ID,
			plugins_url( '/front-css.css', __FILE__ ),
			array(),
			filemtime( plugin_dir_path( __FILE__ ) . 'front-css.css' )
		);

		wp_localize_script(
			'ultimakit-module-script-front-' . $this->ID,
			'ultimakit_pre_submissions',
			array(
				'field'   => __('Field','ultimakit-for-wp'),
				'your_response'   => __('Your Response','ultimakit-for-wp'),
				'no_response'   => __('No response','ultimakit-for-wp'),
				
			)
		);
	}

	public function gf_pre_submission_preview_add_preview_button($button, $form) {
		$preview_button = '<button type="button" class="gform_button gform_preview_button" onclick="gfShowPreview(' . $form['id'] . ')">Preview</button>';
		return $preview_button . $button;
	}

	public function gf_pre_submission_preview_modal() {
		?>
		<div id="gf-preview-modal" style="display: none;">
			<div class="gf-preview-modal-content">
				<h2><?php echo esc_html_e('Preview Your Submission','ultimakit-for-wp');?></h2>
				<div id="gf-preview-content"></div>
				<div class="gf-preview-buttons">
					<button type="button" class="gform_button" onclick="gfClosePreview()"><?php echo esc_html_e('Back to Edit','ultimakit-for-wp');?></button>
					<button type="button" class="gform_button gform_submit_button" onclick="gfSubmitForm()"><?php echo esc_html_e('Submit Form','ultimakit-for-wp');?></button>
				</div>
			</div>
		</div>
		<?php
	}

}
