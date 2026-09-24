<?php
/**
 * Class UltimaKit_Module_Seo_Title_Meta_Description_Editor
 *
 * @since 1.0.0
 * @package    UltimaKit
 */

/**
 * Class UltimaKit_Module_Seo_Title_Meta_Description_Editor
 *
 * This class provides methods to control the display of dashboard widgets based on user preferences.
 * It allows users to selectively hide certain dashboard widgets to streamline their WordPress dashboard
 * experience and improve usability.
 *
 * @since 1.0.0
 */
class UltimaKit_Module_Seo_Title_Meta_Description_Editor extends UltimaKit_Module_Manager {
	/**
	 *
	 * @var string
	 */
	protected $ID = 'ultimakit_seo_title_meta_description_editor';

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
	protected $category = 'Content Management';

	/**
	 * The type of module, indicating its platform or use case.
	 *
	 * @var string
	 */
	protected $type = 'WordPress';

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
	protected $read_more_link = 'seo-title-and-meta-description-editor-in-wordpress';

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
		$this->name        = __( 'SEO Title and Meta Description Editor', 'ultimakit-for-wp' );
		$this->description = __( 'Allow users to easily manage their SEO titles and meta descriptions, improving their search engine visibility without needing a complex SEO plugin', 'ultimakit-for-wp' );
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

			add_action( 'add_meta_boxes', array( $this, 'ultimakit_add_seo_meta_box' ) );
			add_action( 'save_post', array( $this, 'ultimakit_save_seo_meta' ) );
			add_action( 'wp_head', array( $this, 'ultimakit_output_seo_meta' ) );
			add_filter( 'pre_get_document_title', array( $this, 'ultimakit_filter_seo_title' ) );

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
			'ultimakit-module-script-' . $this->ID,
			plugins_url( '/module-script.js', __FILE__ ),
			array( 'jquery' ),
			ULTIMAKIT_FOR_WP_VERSION,
			true
		);
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
		$arguments['title'] = __( 'Set Custom Upload Folder', 'ultimakit-for-wp' );

		$current_folder = $this->getModuleSettings( $this->ID, 'upload_folder_path' ) ?? '[your_folder_name]';

		$arguments['fields'] = array(
			'upload_folder_path' => array(
				'type'  => 'text',
				'label' => __( 'Custom Upload Folder Path', 'ultimakit-for-wp' ),
				'value' => $this->getModuleSettings( $this->ID, 'upload_folder_path' ),
				'desc'  => '<strong>Current Path:</strong> ' . WP_CONTENT_DIR . '/uploads/' . $current_folder,
			),
		);

		$this->ultimakit_generate_modal( $arguments );
	}

	// Add meta boxes for SEO title and description.
	public function ultimakit_add_seo_meta_box() {
		add_meta_box(
			'ultimakit_seo_meta',
			__( 'SEO Settings', 'ultimakit-for-wp' ),
			array( $this, 'ultimakit_seo_meta_box_callback' ),
			array( 'post', 'page' ), // You can add custom post types here.
			'normal',
			'high'
		);
	}

	// Callback function to display the SEO fields.
	public function ultimakit_seo_meta_box_callback( $post ) {
		// Add nonce for security.
		wp_nonce_field( 'ultimakit_seo_meta', 'ultimakit_seo_meta_nonce' );

		// Retrieve current values if available.
		$seo_title       = get_post_meta( $post->ID, '_ultimakit_seo_title', true );
		$seo_description = get_post_meta( $post->ID, '_ultimakit_seo_description', true );

		// Set character limits.
		$title_limit       = 60;
		$description_limit = 160;

		// Display the fields.
		?>
		<p>
			<label for="ultimakit_seo_title"><?php echo __( 'SEO Title', 'ultimakit-for-wp' ); ?></label>
			<input type="text" id="ultimakit_seo_title" name="ultimakit_seo_title" value="<?php echo esc_attr( $seo_title ); ?>" class="widefat" />
			<small id="seo-title-counter"><strong><?php echo $title_limit - strlen( $seo_title ); ?></strong> <?php echo __( 'characters remaining', 'ultimakit-for-wp' ); ?></small>
		</p>
		<p>
			<label for="ultimakit_seo_description"><?php echo __( 'Meta Description', 'ultimakit-for-wp' ); ?></label>
			<textarea id="ultimakit_seo_description" name="ultimakit_seo_description" rows="4" class="widefat" ><?php echo esc_textarea( $seo_description ); ?></textarea>
			<small id="seo-description-counter"><strong><?php echo $description_limit - strlen( $seo_description ); ?></strong> <?php echo __( 'characters remaining', 'ultimakit-for-wp' ); ?></small>
		</p>
		<?php
	}

	public function ultimakit_save_seo_meta( $post_id ) {
		// Verify the nonce before proceeding.
		if ( ! isset( $_POST['ultimakit_seo_meta_nonce'] ) || ! wp_verify_nonce( $_POST['ultimakit_seo_meta_nonce'], 'ultimakit_seo_meta' ) ) {
			return $post_id;
		}

		// Check if the current user can edit the post.
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return $post_id;
		}

		// Save or update the SEO title.
		if ( isset( $_POST['ultimakit_seo_title'] ) ) {
			update_post_meta( $post_id, '_ultimakit_seo_title', sanitize_text_field( $_POST['ultimakit_seo_title'] ) );
		}

		// Save or update the SEO description.
		if ( isset( $_POST['ultimakit_seo_description'] ) ) {
			update_post_meta( $post_id, '_ultimakit_seo_description', sanitize_textarea_field( $_POST['ultimakit_seo_description'] ) );
		}
	}

	// Filter to modify the SEO title if set.
	public function ultimakit_filter_seo_title( $title ) {
		if ( is_singular() ) {
			global $post;

			// Get the custom SEO title.
			$seo_title = get_post_meta( $post->ID, '_ultimakit_seo_title', true );

			// If an SEO title is set, use it instead of the default title.
			if ( ! empty( $seo_title ) ) {
				return esc_html( $seo_title );
			}
		}

		// Return the default title if no custom SEO title is set.
		return $title;
	}
	public function ultimakit_output_seo_meta() {
		if ( is_singular() ) {
			global $post;
			// Get custom SEO description.
			$seo_description = get_post_meta( $post->ID, '_ultimakit_seo_description', true );

			// Output meta description if set.
			if ( ! empty( $seo_description ) ) {
				echo '<meta name="description" content="' . esc_attr( $seo_description ) . '">';
			}
		}
	}
}
