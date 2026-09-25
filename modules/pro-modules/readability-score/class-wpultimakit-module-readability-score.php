<?php
/**
 * Class UltimaKit_Module_Readability_Score
 *
 * @since 1.0.0
 * @package    UltimaKit
 */

/**
 * Class UltimaKit_Module_Readability_Score
 *
 * This class provides methods to control the display of dashboard widgets based on user preferences.
 * It allows users to selectively hide certain dashboard widgets to streamline their WordPress dashboard
 * experience and improve usability.
 *
 * @since 1.0.0
 */
class UltimaKit_Module_Readability_Score extends UltimaKit_Module_Manager {
	/**
	 *
	 * @var string
	 */
	protected $ID = 'ultimakit_module_readability_score';

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
	protected $read_more_link = 'get-readability-score-in-wordpress';

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
		$this->name        = __( 'Readability Score', 'ultimakit-for-wp' );
		$this->description = __( 'This tool helps you get the readability score of your content.', 'ultimakit-for-wp' );
		$this->is_active   = $this->isModuleActive( $this->ID );

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

			add_action( 'add_meta_boxes', array( $this, 'add_readability_meta_box' ) );
			add_action( 'save_post', array( $this, 'save_readability_score' ) );
			add_action( 'wp_ajax_calculate_readability', array( $this, 'ajax_calculate_readability' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
		}
	}

	/**
	 * Enqueue admin assets
	 */
	public function enqueue_admin_assets( $hook ) {

		wp_enqueue_style(
			$this->ID,
			plugin_dir_url( __FILE__ ) . '/module-style.css',
			array(),
			ULTIMAKIT_FOR_WP_VERSION
		);

		wp_enqueue_script(
			$this->ID,
			plugin_dir_url( __FILE__ ) . '/module-script.js',
			array( 'jquery' ),
			ULTIMAKIT_FOR_WP_VERSION,
			true
		);

		wp_localize_script(
			$this->ID,
			'wpukReadabilityScore',
			array(
				'ajaxurl'                 => admin_url( 'admin-ajax.php' ),
				'nonce'                   => wp_create_nonce( 'wpuk_readability_score_nonce' ),
				'history_title'           => __( 'Readability Score History', 'ultimakit-for-wp' ),
				'history_view'            => __( 'View History', 'ultimakit-for-wp' ),
				'history_no_history'      => __( 'No history available', 'ultimakit-for-wp' ),
				'history_date'            => __( 'Date', 'ultimakit-for-wp' ),
				'history_score'           => __( 'Score', 'ultimakit-for-wp' ),
				'history_change'          => __( 'Change', 'ultimakit-for-wp' ),
				'history_updated_by'      => __( 'Updated By', 'ultimakit-for-wp' ),
				'calculating_score'       => __( 'Calculating score...', 'ultimakit-for-wp' ),
				'error_calculating_score' => __( 'Error calculating score', 'ultimakit-for-wp' ),
				'error_no_permission'     => __( 'Permission denied', 'ultimakit-for-wp' ),
				'error_invalid_post_id'   => __( 'Invalid post ID', 'ultimakit-for-wp' ),
			)
		);
	}

	public function add_readability_meta_box() {
		// Get all post types
		$args              = array(
			'public'   => true,
			'_builtin' => false,
		);
		$custom_post_types = get_post_types( $args, 'names', 'and' );

		// Add default post types
		$post_types = array_merge( array( 'post', 'page' ), $custom_post_types );

		// Remove attachment post type if it exists
		if ( ( $key = array_search( 'attachment', $post_types ) ) !== false ) {
			unset( $post_types[ $key ] );
		}

		// Add meta box to each post type
		foreach ( $post_types as $post_type ) {
			add_meta_box(
				'wpuk_readability_score',
				__( 'Readability Score', 'ultimakit-for-wp' ),
				array( $this, 'render_meta_box' ),
				$post_type,
				'normal',
				'default'
			);
		}
	}

	// In the render_meta_box method:
	public function render_meta_box( $post ) {
		wp_nonce_field( 'wpuk_readability_score', 'wpuk_readability_nonce' );
		$score       = get_post_meta( $post->ID, '_wpuk_readability_score', true );
		$score_class = $this->get_score_class( $score ); // Add this method to PHP class
		?>
		<div class="wpuk-readability-score">
			<div class="score-display">
				<?php if ( $score ) : ?>
					<div class="score-value <?php echo esc_attr( $score_class ); ?>">
						<?php echo esc_html( $score ); ?>
					</div>
					<div class="score-label"><?php echo esc_html( $this->get_score_label( $score ) ); ?></div>
				<?php else : ?>
					<p><?php _e( 'No readability score calculated yet.', 'ultimakit-for-wp' ); ?></p>
				<?php endif; ?>
			</div>
			<button type="button" class="button calculate-score">
				<?php _e( 'Calculate Score', 'ultimakit-for-wp' ); ?>
			</button>
		</div>
		<?php if ( $score ) : ?>
			<style>
				.score-history .score-history-table {
					width: 100%;
					border-spacing: 0;
					border: 1px solid #c3c4c7;
					background: #fff;
					box-shadow: 0 1px 1px rgba(0,0,0,.04);
					border-radius: 3px;
				}

				.score-history .score-history-table th,
				.score-history .score-history-table td {
					padding: 8px 10px;
					text-align: left;
					line-height: 1.5em;
					vertical-align: middle;
					font-size: 13px;
				}

				.score-history .score-history-table th {
					font-weight: 600;
					border-bottom: 1px solid #c3c4c7;
					color: #2c3338;
					background: #f0f0f1;
					padding: 10px;
				}

				.score-history .score-history-table tr {
					background: #fff;
				}

				.score-history .score-history-table tr:nth-child(odd) {
					background: #f6f7f7;
				}

				.score-history .score-history-table td {
					border-bottom: 1px solid #c3c4c7;
					color: #50575e;
				}

				.score-history .score-history-table tr:last-child td {
					border-bottom: none;
				}

				/* Score History specific styles */
				.score-history .history-score {
					font-weight: 600;
					color: #2c3338;
				}

				.score-history .history-trend {
					display: inline-block;
					padding: 2px 8px;
					border-radius: 3px;
					font-size: 12px;
					font-weight: 500;
				}

				.score-history .trend-up {
					background: #edfaef;
					color: #008a20;
				}

				.score-history .trend-down {
					background: #fcf0f1;
					color: #d63638;
				}

				.score-history .trend-same {
					background: #f0f0f1;
					color: #50575e;
				}

				/* Empty state */
				.score-history .score-history-table td.no-items {
					text-align: center;
					padding: 15px;
					color: #646970;
				}

				/* Responsive */
				@media screen and (max-width: 782px) {
					.score-history .score-history-table th,
					.score-history .score-history-table td {
						padding: 10px;
						font-size: 14px;
					}
				}

				.score-history-toggle{
					margin-top: 10px; 
					margin-bottom: 15px;
				}
			</style>
			<div class="score-history-toggle">
				<a href="#" class="wpuk-readability-score-history-toggle"><?php _e( 'View History', 'ultimakit-for-wp' ); ?></a>
			</div>
			<div class="score-history" style="display: none;">
				<table class="score-history-table">
					<thead>
						<tr>
							<th><?php _e( 'Date', 'ultimakit-for-wp' ); ?></th>
							<th><?php _e( 'Score', 'ultimakit-for-wp' ); ?></th>
							<th><?php _e( 'Change', 'ultimakit-for-wp' ); ?></th>
							<th><?php _e( 'Updated By', 'ultimakit-for-wp' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php
						$history = $this->get_score_history( $post->ID );
						if ( ! empty( $history ) ) {
							$previous_score = null;
							foreach ( array_reverse( $history ) as $entry ) {
								$change      = $previous_score !== null ? $entry['score'] - $previous_score : 0;
								$trend_class = $change > 0 ? 'trend-up' : ( $change < 0 ? 'trend-down' : 'trend-same' );
								$user_info   = get_userdata( $entry['user_id'] );
								?>
								<tr>
									<td><?php echo date_i18n( get_option( 'date_format' ), strtotime( $entry['timestamp'] ) ); ?></td>
									<td class="history-score"><?php echo esc_html( $entry['score'] ); ?></td>
									<td>
										<span class="history-trend <?php echo esc_attr( $trend_class ); ?>">
											<?php
											echo $change > 0 ? '+' : '';
											echo round( $change, 1 );
											?>
										</span>
									</td>
									<td><?php echo esc_html( $user_info ? $user_info->display_name : __( 'Unknown', 'ultimakit-for-wp' ) ); ?></td>
								</tr>
								<?php
								$previous_score = $entry['score'];
							}
						} else {
							?>
							<tr>
								<td colspan="4"><?php _e( 'No history available', 'ultimakit-for-wp' ); ?></td>
							</tr>
							<?php
						}
						?>
					</tbody>
				</table>
			</div>
			<?php
		endif;
	}

	// Add this method to the PHP class
	private function get_score_class( $score ) {
		if ( empty( $score ) ) {
			return '';
		}

		if ( $score >= 90 ) {
			return 'score-very-easy';
		}
		if ( $score >= 80 ) {
			return 'score-easy';
		}
		if ( $score >= 70 ) {
			return 'score-fairly-easy';
		}
		if ( $score >= 60 ) {
			return 'score-standard';
		}
		if ( $score >= 50 ) {
			return 'score-fairly-difficult';
		}
		if ( $score >= 30 ) {
			return 'score-difficult';
		}
		return 'score-very-difficult';
	}

	private function calculate_readability_score( $content ) {
		// Remove HTML tags and decode entities
		$text = wp_strip_all_tags( $content );
		$text = html_entity_decode( $text );

		// Calculate basic metrics
		$sentences       = preg_split( '/[.!?]+/', $text, -1, PREG_SPLIT_NO_EMPTY );
		$words           = str_word_count( $text, 1 );
		$total_words     = count( $words );
		$total_sentences = count( $sentences );

		if ( $total_sentences == 0 || $total_words == 0 ) {
			return 0;
		}

		// Calculate Flesch Reading Ease Score instead of Grade Level
		$avg_sentence_length = $total_words / $total_sentences;

		// Calculate syllables
		$total_syllables = 0;
		foreach ( $words as $word ) {
			$total_syllables += $this->count_syllables( $word );
		}

		// Flesch Reading Ease formula
		$score = 206.835 - ( 1.015 * $avg_sentence_length ) - ( 84.6 * ( $total_syllables / $total_words ) );

		// Ensure score stays within 0-100 range
		$score = max( 0, min( 100, $score ) );

		return round( $score, 1 );
	}

	private function get_score_label( $score ) {
		if ( $score >= 90 ) {
			return __( 'Very Easy to Read', 'ultimakit-for-wp' );
		}
		if ( $score >= 80 ) {
			return __( 'Easy to Read', 'ultimakit-for-wp' );
		}
		if ( $score >= 70 ) {
			return __( 'Fairly Easy to Read', 'ultimakit-for-wp' );
		}
		if ( $score >= 60 ) {
			return __( 'Standard', 'ultimakit-for-wp' );
		}
		if ( $score >= 50 ) {
			return __( 'Fairly Difficult', 'ultimakit-for-wp' );
		}
		if ( $score >= 30 ) {
			return __( 'Difficult', 'ultimakit-for-wp' );
		}
		return __( 'Very Difficult', 'ultimakit-for-wp' );
	}

	private function count_syllables( $word ) {
		$word               = strtolower( $word );
		$word               = preg_replace( '/[^a-z]/', '', $word );
		$syllables          = 0;
		$vowels             = array( 'a', 'e', 'i', 'o', 'u', 'y' );
		$previous_was_vowel = false;

		foreach ( str_split( $word ) as $char ) {
			$is_vowel = in_array( $char, $vowels );
			if ( $is_vowel && ! $previous_was_vowel ) {
				++$syllables;
			}
			$previous_was_vowel = $is_vowel;
		}

		if ( $word && substr( $word, -1 ) === 'e' ) {
			--$syllables;
		}

		return max( 1, $syllables );
	}

	public function ajax_calculate_readability() {
		check_ajax_referer( 'wpuk_readability_score', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied', 'ultimakit-for-wp' ) ) );
			return;
		}

		$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
		if ( ! $post_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid post ID', 'ultimakit-for-wp' ) ) );
			return;
		}

		$post = get_post( $post_id );
		// The post must exist and be editable by this user before we read it or write its meta.
		if ( ! $post || ! current_user_can( 'edit_post', $post_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied', 'ultimakit-for-wp' ) ) );
			return;
		}

		$score = $this->calculate_readability_score( $post->post_content );
		update_post_meta( $post_id, '_wpuk_readability_score', $score );

		wp_send_json_success(
			array(
				'score' => $score,
				'label' => $this->get_score_label( $score ),
			)
		);
	}

	/**
	 * Save readability score when post is saved
	 *
	 * @param int $post_id The ID of the post being saved
	 * @return void
	 */
	public function save_readability_score( $post_id ) {
		// Check if autosave
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Verify nonce
		if ( ! isset( $_POST['wpuk_readability_nonce'] ) ||
			! wp_verify_nonce( $_POST['wpuk_readability_nonce'], 'wpuk_readability_score' ) ) {
			return;
		}

		// Check user permissions
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Get post
		$post = get_post( $post_id );

		// Skip revisions and autosaves
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}

		// Skip if post type is attachment
		if ( $post->post_type === 'attachment' ) {
			return;
		}

		// Calculate and save the score
		$score = $this->calculate_readability_score( $post->post_content );

		// Save score as post meta
		update_post_meta( $post_id, '_wpuk_readability_score', $score );

		// Save last updated timestamp
		update_post_meta( $post_id, '_wpuk_readability_score_updated', current_time( 'mysql' ) );

		// Optional: Save score history
		$this->save_score_history( $post_id, $score );
	}

	/**
	 * Save score history for tracking improvements
	 *
	 * @param int $post_id The ID of the post
	 * @param float $score The calculated readability score
	 * @return void
	 */
	private function save_score_history( $post_id, $score ) {
		$history = get_post_meta( $post_id, '_wpuk_readability_score_history', true );

		if ( ! is_array( $history ) ) {
			$history = array();
		}

		// Add new score to history
		$history[] = array(
			'score'     => $score,
			'timestamp' => current_time( 'mysql' ),
			'user_id'   => get_current_user_id(),
		);

		// Keep only last 10 entries
		if ( count( $history ) > 10 ) {
			$history = array_slice( $history, -10 );
		}

		update_post_meta( $post_id, '_wpuk_readability_score_history', $history );
	}

	/**
	 * Get score history for a post
	 *
	 * @param int $post_id The ID of the post
	 * @return array Score history
	 */
	public function get_score_history( $post_id ) {
		$history = get_post_meta( $post_id, '_wpuk_readability_score_history', true );
		return is_array( $history ) ? $history : array();
	}

	/**
	 * Get the last updated date for the readability score
	 *
	 * @param int $post_id The ID of the post
	 * @return string|false Last updated date or false if not found
	 */
	public function get_last_updated( $post_id ) {
		return get_post_meta( $post_id, '_wpuk_readability_score_updated', true );
	}
}
