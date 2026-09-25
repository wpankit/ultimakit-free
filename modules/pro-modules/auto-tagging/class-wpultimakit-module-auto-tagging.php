<?php
/**
 * Class UltimaKit_Module_Auto_Tagging
 *
 * @since 1.0.0
 * @package    UltimaKit
 */

/**
 * Class UltimaKit_Module_Auto_Tagging
 *
 * This class provides functionality to automatically suggest tags for posts based on their content.
 * It analyzes post content and generates relevant tag suggestions to help users with content organization.
 *
 * @since 1.0.0
 */
class UltimaKit_Module_Auto_Tagging extends UltimaKit_Module_Manager {
	/**
	 * Module identifier
	 * @var string
	 */
	protected $ID = 'ultimakit_module_auto_tagging';

	/**
	 * Module name
	 * @var string
	 */
	protected $name;

	/**
	 * Module description
	 * @var string
	 */
	protected $description;

	/**
	 * Module plan
	 * @var string
	 */
	protected $plan = 'pro';

	/**
	 * Module category
	 * @var string
	 */
	protected $category = 'Content Management';

	/**
	 * Module type
	 * @var string
	 */
	protected $type = 'WordPress';

	/**
	 * Module active status
	 * @var bool
	 */
	protected $is_active;

	/**
	 * Default settings
	 * @var array
	 */
	protected $default_settings = array(
		'min_word_length' => 4,
		'max_suggestions' => 10,
		'excluded_words'  => array(),
		'post_types'      => array( 'post' ),
	);

	/**
	 * Module version
	 * @var string
	 */
	protected $version = '1.0.0';

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->name        = __( 'Auto Tagging', 'ultimakit-for-wp' );
		$this->description = __( 'Automatically suggest tags for posts based on their content.', 'ultimakit-for-wp' );
		$this->is_active   = $this->isModuleActive( $this->ID );

		$this->initializeModule();
	}

	/**
	 * Initialize module functionality
	 */
	protected function initializeModule() {
		if ( $this->is_active ) {
			add_action( 'admin_menu', array( $this, 'add_admin_menu' ), 20 );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
			add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
			add_action( 'wp_ajax_get_tag_suggestions', array( $this, 'ajax_get_tag_suggestions' ) );
			add_action( 'wp_ajax_ultimakit_save_auto_tagging_settings', array( $this, 'save_settings' ) );
			// Add this new action
			add_action( 'wp_ajax_ultimakit_save_post_tags', array( $this, 'save_post_tags' ) );
		}
	}

	/**
	 * Add admin menu
	 */
	public function add_admin_menu() {
		add_submenu_page(
			'wp-ultimakit-dashboard',
			__( 'Auto Tagging', 'ultimakit-for-wp' ),
			__( 'Auto Tagging', 'ultimakit-for-wp' ),
			'manage_options',
			'wp-ultimakit-auto-tagging',
			array( $this, 'render_admin_page' )
		);
	}

	/**
	 * Enqueue admin assets
	 */
	public function enqueue_admin_assets( $hook ) {
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php', 'ultimakit-for-wp_page_wp-ultimakit-auto-tagging' ) ) ) {
			return;
		}

		wp_enqueue_script(
			$this->ID . '-admin',
			plugin_dir_url( __FILE__ ) . 'module-script.js',
			array( 'jquery' ),
			$this->version,
			true
		);

		wp_localize_script(
			$this->ID . '-admin',
			'ultimakitAutoTagging',
			array(
				'ajaxurl'            => admin_url( 'admin-ajax.php' ),
				'nonce'              => wp_create_nonce( 'ultimakit_auto_tagging_nonce' ),
				'success_message'    => __( 'Settings saved successfully!', 'ultimakit-for-wp' ),
				'error_message'      => __( 'An error occurred. Please try again.', 'ultimakit-for-wp' ),
				'generating_text'    => __( 'Generating...', 'ultimakit-for-wp' ),
				'generate_text'      => __( 'Generate Tags', 'ultimakit-for-wp' ),
				'tags_added_message' => __( 'Selected tags have been added successfully.', 'ultimakit-for-wp' ),
			)
		);
	}

	/**
	 * Add meta box to post editor
	 */
	public function add_meta_box() {
		$post_types = $this->getModuleSettings( $this->ID, 'post_types', $this->default_settings['post_types'] );

		foreach ( $post_types as $post_type ) {
			add_meta_box(
				'ultimakit-auto-tagging',
				__( 'Suggested Tags', 'ultimakit-for-wp' ),
				array( $this, 'render_meta_box' ),
				$post_type,
				'side'
			);
		}
	}

	/**
	 * Render meta box content
	 */
	public function render_meta_box( $post ) {
		?>
		<div id="ultimakit-tag-suggestions">
			<div id="suggestion-list" class="suggestion-list"></div>
			<button type="button" id="generate-tags" class="button button-secondary" style="margin-top: 10px;">
				<?php esc_html_e( 'Generate Tags', 'ultimakit-for-wp' ); ?>
			</button>
			<button type="button" id="add-selected-tags" class="button button-primary" style="display:none; margin-top: 10px;">
				<?php esc_html_e( 'Add Selected Tags', 'ultimakit-for-wp' ); ?>
			</button>
		</div>
		<?php
	}

	/**
	 * Generate tag suggestions from content
	 *
	 * @param string $content The post content
	 * @return array Array of suggested tags
	 */
	private function generate_tag_suggestions( $content ) {
		// Get settings
		$settings = array(
			'min_word_length' => $this->getModuleSettings( $this->ID, 'min_word_length', $this->default_settings['min_word_length'] ),
			'max_suggestions' => $this->getModuleSettings( $this->ID, 'max_suggestions', $this->default_settings['max_suggestions'] ),
			'excluded_words'  => $this->getModuleSettings( $this->ID, 'excluded_words', $this->default_settings['excluded_words'] ),
		);

		// Clean and prepare content
		$content = strip_tags( $content );
		$content = strtolower( $content );

		// Remove common words and punctuation
		$content = preg_replace( '/[^\p{L}\p{N}\s]/u', '', $content );

		// Get words array
		$words = str_word_count( $content, 1 );

		// Filter words
		$filtered_words = array_filter(
			$words,
			function ( $word ) use ( $settings ) {
				return strlen( $word ) >= $settings['min_word_length']
				&& ! in_array( $word, $settings['excluded_words'] )
				&& ! in_array( $word, $this->get_common_words() );
			}
		);

		// Count word frequency
		$word_count = array_count_values( $filtered_words );

		// Sort by frequency
		arsort( $word_count );

		// Get top suggestions
		return array_slice( array_keys( $word_count ), 0, $settings['max_suggestions'] );
	}

	/**
	 * Get common words to exclude
	 *
	 * @return array Array of common words
	 */
	private function get_common_words() {
		return array(
			'the',
			'be',
			'to',
			'of',
			'and',
			'a',
			'in',
			'that',
			'have',
			'i',
			'it',
			'for',
			'not',
			'on',
			'with',
			'he',
			'as',
			'you',
			'do',
			'at',
			'this',
			'but',
			'his',
			'by',
			'from',
			'they',
			'we',
			'say',
			'her',
			'she',
			'or',
			'an',
			'will',
			'my',
			'one',
			'all',
			'would',
			'there',
			'their',
			'what',
			'so',
			'up',
			'out',
			'if',
			'about',
			'who',
			'get',
			'which',
			'go',
			'me',
		);
	}

	/**
	 * AJAX handler for getting tag suggestions
	 */
	public function ajax_get_tag_suggestions() {
		// Verify nonce
		check_ajax_referer( 'ultimakit_auto_tagging_nonce', 'nonce' );

		// Check permissions
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( __( 'Permission denied', 'ultimakit-for-wp' ) );
			return;
		}

		// Get post ID and content
		$post_id = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;
		if ( ! $post_id ) {
			wp_send_json_error( __( 'Invalid post ID', 'ultimakit-for-wp' ) );
			return;
		}

		// Only analyse posts the current user is allowed to edit.
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			wp_send_json_error( __( 'Permission denied', 'ultimakit-for-wp' ) );
			return;
		}

		$post = get_post( $post_id );
		if ( ! $post ) {
			wp_send_json_error( __( 'Post not found', 'ultimakit-for-wp' ) );
			return;
		}

		// Generate suggestions
		$suggestions = $this->generate_tag_suggestions( $post->post_content );

		// Get existing tags
		$existing_tags = wp_get_post_tags( $post_id, array( 'fields' => 'names' ) );

		// Filter out existing tags
		$suggestions = array_diff( $suggestions, $existing_tags );

		if ( empty( $suggestions ) ) {
			wp_send_json_success(
				array(
					'html' => '<p>' . __( 'No new tag suggestions available.', 'ultimakit-for-wp' ) . '</p>',
				)
			);
			return;
		}

		// Build HTML for suggestions
		$html = '';
		foreach ( $suggestions as $tag ) {
			$html .= sprintf(
				'<label class="suggestion-item" style="margin: 5px;"><input type="checkbox" value="%1$s"> %2$s</label>',
				esc_attr( $tag ),
				esc_html( $tag )
			);
		}

		wp_send_json_success( array( 'html' => $html ) );
	}

	/**
	 * Save settings
	 */
	public function save_settings() {
		// Verify nonce
		check_ajax_referer( 'ultimakit_auto_tagging_nonce', 'nonce' );

		// Check permissions
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Permission denied' );
			return;
		}

		// Get and sanitize the settings
		$settings = array(
			'min_word_length' => isset( $_POST['min_word_length'] ) ? absint( $_POST['min_word_length'] ) : $this->default_settings['min_word_length'],
			'max_suggestions' => isset( $_POST['max_suggestions'] ) ? absint( $_POST['max_suggestions'] ) : $this->default_settings['max_suggestions'],
			'post_types'      => isset( $_POST['post_types'] ) ? array_map( 'sanitize_text_field', (array) $_POST['post_types'] ) : $this->default_settings['post_types'],
			'excluded_words'  => isset( $_POST['excluded_words'] ) && is_string( $_POST['excluded_words'] ) ? array_map( 'sanitize_text_field', explode( ',', wp_unslash( $_POST['excluded_words'] ) ) ) : $this->default_settings['excluded_words'],
		);

		// Save settings
		$result = $this->ultimakit_update_module_setting( $this->ID, 'settings', $settings, false );

		if ( $result ) {
			wp_send_json_success( __( 'Settings saved successfully', 'ultimakit-for-wp' ) );
		} else {
			wp_send_json_error( __( 'Failed to save settings', 'ultimakit-for-wp' ) );
		}
	}

	/**
	 * Render admin page
	 */
	public function render_admin_page() {
		// Get current settings
		$settings = array(
			'min_word_length' => $this->getModuleSettings( $this->ID, 'min_word_length', $this->default_settings['min_word_length'] ),
			'max_suggestions' => $this->getModuleSettings( $this->ID, 'max_suggestions', $this->default_settings['max_suggestions'] ),
			'post_types'      => $this->getModuleSettings( $this->ID, 'post_types', $this->default_settings['post_types'] ),
			'excluded_words'  => $this->getModuleSettings( $this->ID, 'excluded_words', $this->default_settings['excluded_words'] ),
		);

		?>
		<div class="wrap">
			<?php $this->ultimakit_get_header(); ?>
			<div class="container bg-white text-dark p-3 mb-3">
				<ul class="nav nav-tabs" id="wpukTabs" role="tablist">
					<li class="nav-item" role="presentation">
						<a class="nav-link active" id="settings-tab" data-bs-toggle="tab" href="#auto-tagging-settings" role="tab" aria-controls="auto-tagging-settings" aria-selected="true">
							<?php esc_html_e( 'Settings', 'ultimakit-for-wp' ); ?>
						</a>
					</li>
				</ul>

				<div class="tab-content" id="wpukTabsContent">
					<div class="tab-pane fade show active" id="auto-tagging-settings" role="tabpanel">
						<div class="row mt-3">
							<div class="col-12">
								<div class="card" style="width: 100%; display: contents;">
									<div class="card-body">
										<form id="auto-tagging-settings-form">
											<div class="mb-3">
												<label class="form-label"><?php esc_html_e( 'Enable Auto Tagging for:', 'ultimakit-for-wp' ); ?></label>
												<?php
												$available_post_types = get_post_types( array( 'public' => true ), 'objects' );
												foreach ( $available_post_types as $post_type ) {
													$checked = in_array( $post_type->name, $settings['post_types'] ) ? 'checked' : '';
													echo '<br />';
													echo '<label class="checkbox-item">';
													echo '<input type="checkbox" name="post_types[]" value="' . esc_attr( $post_type->name ) . '" ' . $checked . '>';
													echo '<span class="checkbox-label">' . esc_html( $post_type->labels->name ) . '</span>';
													echo '</label>';
												}
												?>
											</div>
											<div class="mb-3">
												<label for="min-word-length" class="form-label"><?php esc_html_e( 'Minimum Word Length', 'ultimakit-for-wp' ); ?></label>
												<input type="number" class="form-control" id="min-word-length" name="min_word_length" 
													value="<?php echo esc_attr( $settings['min_word_length'] ); ?>" min="2" max="20">
											</div>
											<div class="mb-3">
												<label for="max-suggestions" class="form-label"><?php esc_html_e( 'Maximum Number of Suggestions', 'ultimakit-for-wp' ); ?></label>
												<input type="number" class="form-control" id="max-suggestions" name="max_suggestions" 
													value="<?php echo esc_attr( $settings['max_suggestions'] ); ?>" min="1" max="50">
											</div>
											<div class="mb-3">
												<label for="excluded-words" class="form-label"><?php esc_html_e( 'Excluded Words (comma-separated)', 'ultimakit-for-wp' ); ?></label>
												<textarea class="form-control" id="excluded-words" name="excluded_words" rows="3"><?php echo esc_textarea( implode( ', ', $settings['excluded_words'] ) ); ?></textarea>
											</div>
											<button type="submit" class="btn btn-primary">
												<?php esc_html_e( 'Save Settings', 'ultimakit-for-wp' ); ?>
											</button>
										</form>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php
	}


	/**
	 * Save tags to post
	 */
	public function save_post_tags() {
		// Verify nonce
		check_ajax_referer( 'ultimakit_auto_tagging_nonce', 'nonce' );

		// Check permissions
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( __( 'Permission denied', 'ultimakit-for-wp' ) );
			return;
		}

		// Get post ID and tags
		$post_id  = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;
		$new_tags = isset( $_POST['tags'] ) && is_array( $_POST['tags'] ) ? array_map( 'sanitize_text_field', $_POST['tags'] ) : array();

		if ( ! $post_id || empty( $new_tags ) ) {
			wp_send_json_error( __( 'Invalid data', 'ultimakit-for-wp' ) );
			return;
		}

		// edit_posts alone is not enough: the user must be able to edit this post and assign tags.
		$tag_taxonomy = get_taxonomy( 'post_tag' );
		if ( ! current_user_can( 'edit_post', $post_id ) || ! $tag_taxonomy || ! current_user_can( $tag_taxonomy->cap->assign_terms ) ) {
			wp_send_json_error( __( 'Permission denied', 'ultimakit-for-wp' ) );
			return;
		}

		// Get existing tags
		$existing_tags = wp_get_post_tags( $post_id, array( 'fields' => 'names' ) );

		// Merge existing and new tags, removing duplicates
		$all_tags = array_unique( array_merge( $existing_tags, $new_tags ) );

		// Save tags
		$result = wp_set_post_tags( $post_id, $all_tags, false );

		if ( $result !== false && ! is_wp_error( $result ) ) {
			// Get updated tags string for display
			$tags_input = implode( ', ', $all_tags );

			wp_send_json_success(
				array(
					'message'    => __( 'Tags added successfully', 'ultimakit-for-wp' ),
					'tags_input' => $tags_input,
				)
			);
		} else {
			wp_send_json_error( __( 'Failed to add tags', 'ultimakit-for-wp' ) );
		}
	}
}