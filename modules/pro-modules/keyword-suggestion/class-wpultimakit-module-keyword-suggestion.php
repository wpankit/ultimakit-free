<?php
/**
 * Class UltimaKit_Module_Keyword_Suggestion
 *
 * @since 1.0.0
 * @package    UltimaKit
 */

/**
 * Class UltimaKit_Module_Keyword_Suggestion
 *
 * This class provides methods to control the display of dashboard widgets based on user preferences.
 * It allows users to selectively hide certain dashboard widgets to streamline their WordPress dashboard
 * experience and improve usability.
 *
 * @since 1.0.0
 */
class UltimaKit_Module_Keyword_Suggestion extends UltimaKit_Module_Manager {
	/**
	 * Unique identifier for this module
	 * @var string
	 */
	protected $ID = 'ultimakit_module_keyword_suggestion';

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
	 * Required plan level to access this module
	 * @var string
	 */
	protected $plan = 'pro';

	/**
	 * Module category for organization
	 * @var string
	 */
	protected $category = 'Content Management';

	/**
	 * Module type for filtering
	 * @var string
	 */
	protected $type = 'WordPress';

	/**
	 * Whether module is currently active
	 * @var bool
	 */
	protected $is_active;

	/**
	 * Current module version
	 * @var string
	 */
	protected $version = '1.0.0';

	public function __construct() {
		$this->name        = __( 'Keyword Suggestion', 'ultimakit-for-wp' );
		$this->description = __( 'Suggest related keywords while users write content.', 'ultimakit-for-wp' );
		$this->is_active   = $this->isModuleActive( $this->ID );

		$this->initializeModule();
	}

	protected function initializeModule() {
		if ( $this->is_active ) {
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
			add_action( 'wp_ajax_get_keyword_suggestions', array( $this, 'ajax_get_keyword_suggestions' ) );
			add_action( 'add_meta_boxes', array( $this, 'add_keyword_suggestion_box' ) );
		}
	}

	public function add_keyword_suggestion_box() {
		$post_types = array( 'post', 'page' );
		foreach ( $post_types as $post_type ) {
			add_meta_box(
				'ultimakit_keyword_suggestions',
				__( 'Keyword Suggestions', 'ultimakit-for-wp' ),
				array( $this, 'render_keyword_suggestion_box' ),
				$post_type,
				'normal',
				'high'
			);
		}
	}

	public function render_keyword_suggestion_box( $post ) {
		?>
		<div id="ultimakit-keyword-suggestions">
			<div class="suggestion-input">
				<input type="text" id="keyword-input" class="widefat" 
						placeholder="<?php esc_attr_e( 'Type to get suggestions...', 'ultimakit-for-wp' ); ?>">
			</div>
			<div id="suggestion-results" class="suggestion-results"></div>
			<div class="suggestion-loading" style="display:none;">
				<?php esc_html_e( 'Loading suggestions...', 'ultimakit-for-wp' ); ?>
			</div>
		</div>
		<?php
	}

	public function enqueue_admin_assets( $hook ) {
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ) ) ) {
			return;
		}

		wp_enqueue_style(
			$this->ID . '-admin',
			plugin_dir_url( __FILE__ ) . 'module-style.css',
			array(),
			$this->version
		);

		wp_enqueue_script(
			$this->ID . '-admin',
			plugin_dir_url( __FILE__ ) . 'module-script.js',
			array( 'jquery' ),
			$this->version,
			true
		);

		wp_localize_script(
			$this->ID . '-admin',
			'ultimakitKeywordSuggestion',
			array(
				'ajaxurl'    => admin_url( 'admin-ajax.php' ),
				'nonce'      => wp_create_nonce( 'ultimakit_keyword_suggestion_nonce' ),
				'loading'    => __( 'Loading suggestions...', 'ultimakit-for-wp' ),
				'no_results' => __( 'No suggestions found', 'ultimakit-for-wp' ),
				'error'      => __( 'Error fetching suggestions', 'ultimakit-for-wp' ),
			)
		);
	}

	public function ajax_get_keyword_suggestions() {
		check_ajax_referer( 'ultimakit_keyword_suggestion_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( __( 'Permission denied', 'ultimakit-for-wp' ), 403 );
			return;
		}

		$keyword = isset( $_POST['keyword'] ) ? sanitize_text_field( $_POST['keyword'] ) : '';
		if ( empty( $keyword ) ) {
			wp_send_json_error( 'No keyword provided' );
			return;
		}

		$suggestions = $this->get_google_suggestions( $keyword );

		if ( ! empty( $suggestions ) ) {
			wp_send_json_success( $suggestions );
		} else {
			wp_send_json_error( 'No suggestions found' );
		}
	}

	private function get_google_suggestions( $keyword ) {
		$url = 'https://suggestqueries.google.com/complete/search';

		$params = array(
			'client' => 'firefox',
			'q'      => $keyword,
			'hl'     => 'en',
		);

		$request_url = add_query_arg( $params, $url );

		$response = wp_remote_get(
			$request_url,
			array(
				'timeout' => 10,
				'headers' => array(
					'Accept'     => 'application/json',
					'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			$this->log_error( 'Google API Error: ' . $response->get_error_message() );
			return array();
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		return ( is_array( $data ) && isset( $data[1] ) ) ? $data[1] : array();
	}

	private function log_error( $message ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( '[UltimaKit Keyword Suggestion] ' . $message );
		}
	}
}