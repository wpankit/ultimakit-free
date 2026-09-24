<?php
/**
 * Class UltimaKit_Module_Quick_Admin_Search
 *
 * @since 1.0.0
 * @package    UltimaKit
 */

/**
 * Class UltimaKit_Module_Quick_Admin_Search
 *
 * @since 1.0.0
 */
class UltimaKit_Module_Quick_Admin_Search extends UltimaKit_Module_Manager {
	/**
	 *
	 * @var string
	 */
	protected $ID = 'ultimakit_quick_admin_search';

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
	protected $category = 'Admin Interface';

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
	protected $read_more_link = 'quick-search-posts-and-terms-from-admin-panel-in-wordpress';

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
		$this->name        = __( 'Quick Admin Search', 'ultimakit-for-wp' );
		$this->description = __( 'Your Search: Effortlessly Navigate Through the Admin Panel', 'ultimakit-for-wp' );
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
			add_action( 'admin_enqueue_scripts', array( $this, 'add_scripts' ) );
			add_action( 'admin_bar_menu', array( $this, 'add_search_input_to_bar' ), 1000 );
			add_action( 'wp_ajax_ultimakit_admin_search_action', array( $this, 'ultimakit_admin_search_action' ) );
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

		wp_enqueue_style(
			'ultimakit-module-style-' . $this->ID,
			plugins_url( '/module-style.css', __FILE__ ),
			array(),
			filemtime( plugin_dir_path( __FILE__ ) . 'module-style.css' )
		);

		wp_enqueue_script(
			'ultimakit-module-script-' . $this->ID,
			plugins_url( '/module-script.js', __FILE__ ),
			array( 'jquery' ),
			ULTIMAKIT_FOR_WP_VERSION,
			true
		);

		wp_localize_script(
			'ultimakit-module-script-' . $this->ID,
			'ultimakit_quick_admin_search',
			array(
				'ajax_url'   => admin_url( 'admin-ajax.php' ),
				'ajax_nonce' => wp_create_nonce( 'ultimakit-quick-admin-search' ),
			)
		);
	}


	public function add_search_input_to_bar( $admin_bar ) {

		if ( current_user_can( 'edit_posts' ) && is_admin() ) {
			$admin_bar->add_node(
				array(
					'id'    => 'ultimakit-admin-bar-search',
					'title' => '<input type="text" id="ultimakit-admin-bar-search-input" placeholder="' . __( 'Quick Admin Search', 'ultimakit-for-wp' ) . '">',
				)
			);
		}
	}

	public function ultimakit_admin_search_action() {
		// Verify the AJAX request to prevent CSRF attacks
		check_ajax_referer( 'ultimakit-quick-admin-search', 'nonce' );

		// Sanitize and validate the search term
		$search_term = isset( $_POST['search'] ) ? sanitize_text_field( $_POST['search'] ) : '';
		if ( empty( $search_term ) ) {
			wp_send_json_error( array( 'message' => __( 'Search term is empty.', 'ultimakit-for-wp' ) ) );
		}

		// Check if the request is made from an admin context with AJAX
		$is_admin = is_admin() && defined( 'DOING_AJAX' ) && DOING_AJAX;

		// Perform the searches
		$results = array(
			'Posts' => $this->search_posts( $search_term, $is_admin ),
			'Terms' => $this->search_terms( $search_term, $is_admin ),
		);

		// Filter non-empty results
		$results = array_filter( $results );

		// Output results or "No results found" message
		if ( ! empty( $results ) ) {
			$output = $this->format_search_results( $results );
			wp_send_json_success( array( 'html' => $output ) );
		} else {
			wp_send_json_error( array( 'message' => __( 'No results found', 'ultimakit-for-wp' ) ) );
		}
	}

	private function format_search_results( $results ) {
		$output = '<ul>';
		foreach ( $results as $type => $items ) {
			$output .= "<li><strong>$type</strong><ul>";
			foreach ( $items as $item ) {
				$output .= $item;
			}
			$output .= '</ul></li>';
		}
		$output .= '</ul>';
		return $output;
	}

	private function search_posts( $search, $is_admin ) {
		$query_args = array(
			's'              => $search,
			'post_type'      => 'any',
			'posts_per_page' => 5,
		);

		$query   = new WP_Query( $query_args );
		$results = array();

		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				if ( current_user_can( 'edit_post', get_the_ID() ) ) {
					$link      = $is_admin ? get_edit_post_link() : get_permalink();
					$results[] = '<li><a href="' . esc_url( $link ) . '">' . esc_html( get_the_title() ) . ' (' . esc_html( get_post_type() ) . ')</a></li>';
				}
			}
			wp_reset_postdata();  // Reset post data after a custom query
		}

		return $results;
	}

	private function search_terms( $search, $is_admin ) {
		$taxonomies = get_taxonomies( array( 'public' => true ) );
		$terms      = get_terms(
			array(
				'taxonomy'   => $taxonomies,
				'name__like' => $search,
				'number'     => 5,
				'hide_empty' => false,
			)
		);
		$results    = array();

		if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
			foreach ( $terms as $term ) {
				if ( current_user_can( 'edit_term', $term->term_id ) ) {
					$link      = $is_admin ? $this->get_edit_term_link( $term->term_id, $term->taxonomy ) : get_term_link( $term );
					$results[] = '<li><a href="' . esc_url( $link ) . '">' . esc_html( $term->name ) . ' (' . esc_html( $term->taxonomy ) . ')</a></li>';
				}
			}
		}

		return $results;
	}


	private function get_edit_post_link() {
		global $post;
		return admin_url( 'post.php?post=' . $post->ID . '&action=edit' );
	}

	private function get_edit_term_link( $term_id, $taxonomy ) {
		return admin_url( 'edit-tags.php?action=edit&taxonomy=' . $taxonomy . '&tag_ID=' . $term_id . '&post_type=' . get_post_type() );
	}
}
