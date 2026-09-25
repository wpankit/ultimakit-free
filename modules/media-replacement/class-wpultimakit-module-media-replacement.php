<?php
/**
 * Class UltimaKit_Module_Media_Replacement
 *
 * @since 1.0.0
 * @package    UltimaKit
 */

/**
 * Class UltimaKit_Module_
 *
 * This class provides methods to control the display of dashboard widgets based on user preferences.
 * It allows users to selectively hide certain dashboard widgets to streamline their WordPress dashboard
 * experience and improve usability.
 *
 * @since 1.0.0
 */
class UltimaKit_Module_Media_Replacement extends UltimaKit_Module_Manager {
	/**
	 * Unique identifier for the Hide Admin Bar module.
	 *
	 * @var string
	 */
	protected $ID = 'ultimakit_media_replacement';

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
	protected $plan = 'free';

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
	protected $read_more_link = 'media-replacement-in-wordpress';

	/**
	 * The settings associated with the module, if any.
	 *
	 * @var array
	 */
	protected $settings;

	/**
	 * Initializes the module with default values for properties and prepares
	 * any necessary setup or hooks into WordPress. This may include setting
	 * initial values, registering hooks, or preparing resources needed for
	 * the module to function properly within WordPress.
	 */
	public function __construct() {
		$this->name        = __( 'Media Replacement', 'ultimakit-for-wp' );
		$this->description = __( 'Replace media with a custom image or video or any other content.', 'ultimakit-for-wp' );
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

			// Add media replacement link to media actions
			add_filter( 'media_row_actions', array( $this, 'add_media_action' ), 10, 2 );

			// Add media replacement link to edit attachment screen
			add_action( 'attachment_submitbox_misc_actions', array( $this, 'add_replace_media_button' ), 10 );

			// Add submenu page for media replacement
			add_action( 'admin_menu', array( $this, 'add_submenu_page' ) );

			// Handle form submission
			add_action( 'admin_init', array( $this, 'handle_form_submission' ) );

			// Enqueue scripts and styles
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );

		}
	}


	/**
	 * Add media replacement link to media actions
	 */
	public function add_media_action( $actions, $post ) {
		if ( current_user_can( 'edit_post', $post->ID ) ) {
			$url                      = admin_url( 'upload.php?page=ultimakit-replace-media&attachment_id=' . $post->ID );
			$actions['replace_media'] = '<a href="' . esc_url( $url ) . '">' . __( 'Replace Media', 'ultimakit-for-wp' ) . '</a>';
		}
		return $actions;
	}

	/**
	 * Add replace media button to edit attachment screen
	 */
	public function add_replace_media_button() {
		global $post;

		if ( current_user_can( 'edit_post', $post->ID ) ) {
			$url = admin_url( 'upload.php?page=ultimakit-replace-media&attachment_id=' . $post->ID );
			echo '<div class="misc-pub-section">';
			echo '<a href="' . esc_url( $url ) . '" class="button button-secondary">' . __( 'Replace Media File', 'ultimakit-for-wp' ) . '</a>';
			echo '</div>';
		}
	}

	/**
	 * Add submenu page for media replacement
	 */
	public function add_submenu_page() {
		add_submenu_page(
			'upload.php',
			__( 'Replace Media', 'ultimakit-for-wp' ),
			__( 'Replace Media', 'ultimakit-for-wp' ),
			'upload_files',
			'ultimakit-replace-media',
			array( $this, 'render_replace_media_page' )
		);
	}

	/**
	 * Enqueue admin assets
	 */
	public function enqueue_admin_assets( $hook ) {

		if ( 'media_page_ultimakit-replace-media' !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'ultimakit-media-replacement',
			plugin_dir_url( __FILE__ ) . 'module-style.css',
			array(),
			'1.0.0'
		);
	}

	/**
	 * Render replace media page
	 */
	public function render_replace_media_page() {
		// Check if user has permission
		if ( ! current_user_can( 'upload_files' ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.', 'ultimakit-for-wp' ) );
		}

		// Get attachment ID from URL
		$attachment_id = isset( $_GET['attachment_id'] ) ? intval( $_GET['attachment_id'] ) : 0;

		// Check if attachment exists
		if ( ! $attachment_id || ! get_post( $attachment_id ) ) {
			$error_message  = '<div class="ultimakit-error-message">';
			$error_message .= '<div class="error-icon"><span class="dashicons dashicons-warning"></span></div>';
			$error_message .= '<div class="error-content">';
			$error_message .= '<h2>' . __( 'Error: Invalid Attachment', 'ultimakit-for-wp' ) . '</h2>';
			$error_message .= '<p>' . __( 'The attachment ID you provided is invalid or does not exist.', 'ultimakit-for-wp' ) . '</p>';
			$error_message .= '<p>' . __( 'Please check the URL and try again, or return to the media library.', 'ultimakit-for-wp' ) . '</p>';
			$error_message .= '<a href="' . admin_url( 'upload.php' ) . '" class="button button-primary">' . __( 'Return to Media Library', 'ultimakit-for-wp' ) . '</a>';
			$error_message .= '</div>';
			$error_message .= '</div>';

			// Add inline styles for the error message
			$error_styles = '
			<style>
				.ultimakit-error-message {
					max-width: 500px;
					margin: 50px auto;
					background: #fff;
					border-left: 4px solid #dc3232;
					box-shadow: 0 1px 1px rgba(0,0,0,.04);
					padding: 20px;
					display: flex;
					align-items: flex-start;
				}
				.ultimakit-error-message .error-icon {
					margin-right: 20px;
				}
				.ultimakit-error-message .dashicons-warning {
					font-size: 48px;
					color: #dc3232;
					width: 48px;
					height: 48px;
				}
				.ultimakit-error-message .error-content {
					flex: 1;
				}
				.ultimakit-error-message h2 {
					margin-top: 0;
					color: #dc3232;
					font-size: 18px;
				}
				.ultimakit-error-message p {
					font-size: 14px;
					margin-bottom: 15px;
				}
				.ultimakit-error-message .button {
					display: inline-block;
					margin-top: 10px;
				}
			</style>
			';

			wp_die( $error_message . $error_styles );
		}

		// Get attachment details
		$attachment      = get_post( $attachment_id );
		$attachment_url  = wp_get_attachment_url( $attachment_id );
		$attachment_path = get_attached_file( $attachment_id );
		$file_type       = wp_check_filetype( basename( $attachment_path ) );

		?>
		<div class="wrap ultimakit-media-replacement">
			<h1><?php _e( 'Replace Media File', 'ultimakit-for-wp' ); ?></h1>
			
			<div class="media-replacement-container">
				<div class="media-preview">
					<h2><?php _e( 'Current Media', 'ultimakit-for-wp' ); ?></h2>
					<?php if ( wp_attachment_is_image( $attachment_id ) ) : ?>
						<?php echo wp_get_attachment_image( $attachment_id, 'medium' ); ?>
					<?php else : ?>
						<div class="media-icon">
							<?php echo wp_get_attachment_image( $attachment_id, 'thumbnail', true ); ?>
						</div>
					<?php endif; ?>
					
					<p><strong><?php _e( 'Filename:', 'ultimakit-for-wp' ); ?></strong> <?php echo basename( $attachment_path ); ?></p>
				</div>
				
				<div class="media-replacement-form">
					<h2><?php _e( 'Upload Replacement', 'ultimakit-for-wp' ); ?></h2>
					
					<form method="post" enctype="multipart/form-data">
						<?php wp_nonce_field( 'ultimakit_replace_media_' . $attachment_id, 'ultimakit_media_replacement_nonce' ); ?>
						<input type="hidden" name="attachment_id" value="<?php echo $attachment_id; ?>">
						
						<div class="form-field">
							<label for="replacement_file"><?php _e( 'Select Replacement File', 'ultimakit-for-wp' ); ?></label>
							<input type="file" name="replacement_file" id="replacement_file" required>
							<p class="description">
								<?php _e( 'The replacement file should be of the same type as the original file.', 'ultimakit-for-wp' ); ?>
							</p>
						</div>
						
						<div class="form-actions">
							<input type="submit" name="ultimakit_replace_media_submit" class="button button-primary" value="<?php _e( 'Replace Media File', 'ultimakit-for-wp' ); ?>">
							<a href="<?php echo admin_url( 'upload.php' ); ?>" class="button button-secondary"><?php _e( 'Cancel', 'ultimakit-for-wp' ); ?></a>
						</div>
					</form>
					
					<div class="replacement-notice">
						<p><strong><?php _e( 'Note:', 'ultimakit-for-wp' ); ?></strong> <?php _e( 'The original filename will be preserved to ensure all links continue to work.', 'ultimakit-for-wp' ); ?></p>
						<p><strong><?php _e( 'Warning:', 'ultimakit-for-wp' ); ?></strong> <?php _e( 'Once replaced, this action cannot be undone.', 'ultimakit-for-wp' ); ?></p>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Handle form submission
	 */
	public function handle_form_submission() {
		if ( ! isset( $_POST['ultimakit_replace_media_submit'] ) ) {
			return;
		}

		// Check nonce
		$attachment_id = isset( $_POST['attachment_id'] ) ? intval( $_POST['attachment_id'] ) : 0;
		if ( ! isset( $_POST['ultimakit_media_replacement_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ultimakit_media_replacement_nonce'] ) ), 'ultimakit_replace_media_' . $attachment_id ) ) {
			wp_die( __( 'Security check failed. Please try again.', 'ultimakit-for-wp' ) );
		}

		// Check if user has permission
		if ( ! current_user_can( 'upload_files' ) || ! current_user_can( 'edit_post', $attachment_id ) ) {
			wp_die( __( 'You do not have sufficient permissions to perform this action.', 'ultimakit-for-wp' ) );
		}

		// Check if file was uploaded
		if ( ! isset( $_FILES['replacement_file'] ) || $_FILES['replacement_file']['error'] !== UPLOAD_ERR_OK ) {
			wp_die( __( 'Error uploading file. Please try again.', 'ultimakit-for-wp' ) );
		}

		if ( 'attachment' !== get_post_type( $attachment_id ) ) {
			wp_die( __( 'Invalid attachment.', 'ultimakit-for-wp' ) );
		}

		/*
		 * The replacement keeps the original filename and extension, so without checking the
		 * real type a user could swap a .jpg for markup, or replace an already-sanitized SVG
		 * with a malicious one. Verify the upload and require it to match the original type.
		 */
		$replacement = $_FILES['replacement_file'];

		if ( ! is_uploaded_file( $replacement['tmp_name'] ) ) {
			wp_die( __( 'Invalid upload.', 'ultimakit-for-wp' ) );
		}

		$checked = wp_check_filetype_and_ext( $replacement['tmp_name'], isset( $replacement['name'] ) ? $replacement['name'] : '' );

		if ( empty( $checked['type'] ) || ! in_array( $checked['type'], (array) get_allowed_mime_types(), true ) ) {
			wp_die( __( 'That file type is not permitted.', 'ultimakit-for-wp' ) );
		}

		if ( get_post_mime_type( $attachment_id ) !== $checked['type'] ) {
			wp_die( __( 'The replacement file must be the same type as the original.', 'ultimakit-for-wp' ) );
		}

		// Get original file details
		$original_file_path = get_attached_file( $attachment_id );
		$original_file_name = basename( $original_file_path );

		// Replace the file
		$upload_dir  = wp_upload_dir();
		$target_path = $upload_dir['basedir'] . '/' . dirname( str_replace( $upload_dir['basedir'], '', $original_file_path ) ) . '/' . $original_file_name;

		// Move uploaded file to target location
		if ( ! move_uploaded_file( $_FILES['replacement_file']['tmp_name'], $target_path ) ) {
			wp_die( __( 'Error moving the uploaded file. Please check directory permissions.', 'ultimakit-for-wp' ) );
		}

		// Update attachment metadata
		$metadata = wp_generate_attachment_metadata( $attachment_id, $target_path );
		wp_update_attachment_metadata( $attachment_id, $metadata );

		// Clear any caches
		clean_attachment_cache( $attachment_id );

		// Redirect back with success message
		wp_redirect( admin_url( 'post.php?post=' . $attachment_id . '&action=edit&message=1' ) );
		exit;
	}
}

