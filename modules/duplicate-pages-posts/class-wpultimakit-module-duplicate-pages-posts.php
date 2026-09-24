<?php
/**
 * Class UltimaKit_Module_Duplicate_Pages_Posts
 *
 * @since 1.0.0
 * @package    UltimaKit
 */

/**
 * Class UltimaKit_Module_Duplicate_Pages_Posts
 *
 * This class provides methods to control the display of dashboard widgets based on user preferences.
 * It allows users to selectively hide certain dashboard widgets to streamline their WordPress dashboard
 * experience and improve usability.
 *
 * @since 1.0.0
 */
class UltimaKit_Module_Duplicate_Pages_Posts extends UltimaKit_Module_Manager {
	/**
	 * Unique identifier for the Hide Admin Bar module.
	 *
	 * @var string
	 */
	protected $ID = 'ultimakit_module_duplicate_pages_posts';

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
	protected $read_more_link = 'duplicate-pages-and-posts-in-wordpress';

	/**
	 * The settings associated with the module, if any.
	 *
	 * @var array
	 */
	protected $settings;

	/**
	 * Constructs the Hide Admin Bar module instance.
	 *
	 * Initializes the module with default values for properties and prepares
	 * any necessary setup or hooks into WordPress. This may include setting
	 * initial values, registering hooks, or preparing resources needed for
	 * the module to function properly within WordPress.
	 */
	public function __construct() {
		$this->name        = __( 'Duplicate Pages & Posts', 'ultimakit-for-wp' );
		$this->description = __( 'Duplicate pages and posts on the fly as you need to.', 'ultimakit-for-wp' );
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

			add_action( 'wp_ajax_ultimakit-duplicate-post', array( $this, 'ajax_duplicate_post' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ), 120 );
			add_filter( 'page_row_actions', array( $this, 'add_duplicate_button' ), 10, 2 );
			add_filter( 'post_row_actions', array( $this, 'add_duplicate_button' ), 10, 2 );
			add_action( 'admin_head-post.php', array( $this, 'ultimakit_product_duplicate_button' ) );
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

		$screen      = get_current_screen();
		$types_array = array( 'attachment', 'elementor_library', 'e-landing-page', 'product' );
		$types       = get_post_types( array( 'public' => true ), 'objects' );

		$p = array( 'ultimakit_post_nonce' => wp_create_nonce( 'ultimakit-ajax-nonce' ) );
		wp_add_inline_script( 'ultimakit-duplicator-post', 'const ultimakit_post_nonce = ' . json_encode( $p ), 'before' );

		foreach ( $types as $type ) {
			if ( ! in_array( $type->name, $types_array ) && preg_match( '/^edit-(' . $type->name . '|)/', $screen->id ) ) {
				wp_enqueue_script(
					'ultimakit-module-script-' . $this->ID,
					plugins_url( '/module-script.js', __FILE__ ),
					array( 'wp-element', 'wp-edit-post', 'wp-plugins', 'wp-i18n' ),
					ULTIMAKIT_FOR_WP_VERSION,
					true
				);
			}
		}
	}


	public function ajax_duplicate_post() {
		try {
			if ( ! isset( $_REQUEST['ultimakit_nonce'] ) || ! wp_verify_nonce( $_REQUEST['ultimakit_nonce'], 'ultimakit-ajax-nonce' ) ) {
				throw new \Exception( 'Invalid nonce!' );
			}

			$post_ID = isset( $_REQUEST['post_ID'] ) ? intval( $_REQUEST['post_ID'] ) : 0;

			if ( empty( $post_ID ) ) {
				throw new \Exception( 'Post ID not specified' );
			}

			$duplicate = $this->duplicate_post( $post_ID );

			if ( is_wp_error( $duplicate ) ) {
				throw new \Exception( $duplicate->get_error_message() );
			}

			$duplicate->url      = get_the_permalink( $duplicate->ID );
			$duplicate->edit_url = get_edit_post_link( $duplicate, 'json' );

			$result = array(
				'status'    => true,
				'duplicate' => $duplicate,
			);
		} catch ( \Exception $e ) {
			$result = array(
				'status' => false,
				'error'  => $e->getMessage(),
			);
		}

		wp_send_json( $result );
		wp_die();
	}

	public function duplicate_post( $post_ID ) {
		try {
			/*
			 * The generic edit_posts check said nothing about THIS post, so a contributor
			 * could duplicate — and read back — any private post, another author's draft,
			 * or a password-protected post including its post_password.
			 */
			if ( ! current_user_can( 'edit_post', $post_ID ) ) {
				throw new \Exception( 'Not allowed' );
			}

			if ( empty( $post_ID ) ) {
				throw new \Exception( 'Post ID not specified' );
			}

			$post = get_post( $post_ID );

			if ( ! $post ) {
				throw new \Exception( 'Source post not found' );
			}

			$new_post_author = $post->post_author;

			$args = array(
				'post_title'     => __( '[duplicate]', 'ultimakit-for-wp' ) . ' ' . esc_attr( $post->post_title ),
				'comment_status' => $post->comment_status,
				'ping_status'    => $post->ping_status,
				'post_author'    => $new_post_author,
				'post_content'   => $post->post_content,
				'post_excerpt'   => $post->post_excerpt,
				'post_parent'    => $post->post_parent,
				'post_password'  => $post->post_password,
				'post_status'    => 'draft',
				'post_type'      => $post->post_type,
				'to_ping'        => $post->to_ping,
				'menu_order'     => $post->menu_order,
			);

			$inserted = wp_insert_post( $args );

			if ( ! $inserted ) {
				throw new \Exception( 'Failed to save new post' );
			}

			if ( is_wp_error( $inserted ) ) {
				throw new \Exception( $inserted->get_error_message(), $inserted->get_error_code() );
			}

			$new = get_post( $inserted );

			$post_meta_keys = get_post_custom_keys( $post->ID );

			if ( ! empty( $post_meta_keys ) ) {
				foreach ( $post_meta_keys as $meta_key ) {
					$meta_values = get_post_custom_values( $meta_key, $post->ID );

					foreach ( $meta_values as $meta_value ) {
						$meta_value = maybe_unserialize( $meta_value );
						update_post_meta( $new->ID, $meta_key, wp_slash( $meta_value ) );
					}
				}
			}

			$taxonomies = get_object_taxonomies( $post );

			foreach ( $taxonomies as $taxonomy ) {
				$terms = get_the_terms( $post, $taxonomy );

				if ( is_wp_error( $terms ) || ! $terms ) {
					continue;
				}

				$list = array();

				foreach ( $terms as $term ) {
					$list[] = $term->term_id;
				}

				wp_set_post_terms( $new->ID, $list, $taxonomy );
			}

			if ( is_plugin_active( 'elementor/elementor.php' ) ) {
				$css = Elementor\Core\Files\CSS\Post::create( $new->ID );
				$css->update();
			}

			return $new;
		} catch ( \Exception $e ) {
			return new WP_Error( $e->getCode(), $e->getMessage() );
		}
	}

	public static function admin_scripts() {
	}

	public function add_duplicate_button( $actions, $post ) {
		$types_array = array( 'attachment', 'elementor_library', 'e-landing-page', 'product' );
		$types       = get_post_types( array( 'public' => true ), 'objects' );

		foreach ( $types as $type ) {
			if ( ! in_array( $type->name, $types_array ) && $post->post_type != 'product' ) {
				$actions['ultimakit_for_wp'] = sprintf(
					'<a href="%s" aria-label="%s" data-duplicate>%s</a>',
					admin_url( 'admin-ajax.php?action=ultimakit-duplicate-post&ultimakit_nonce=' . wp_create_nonce( 'ultimakit-ajax-nonce' ) . '&post_ID=' . $post->ID ),
					esc_attr( __( 'Duplicate', 'ultimakit-for-wp' ) ),
					__( 'Duplicate', 'ultimakit-for-wp' )
				);

				wp_enqueue_script( 'ultimakit-duplicator-post' );
			}
		}

		return $actions;
	}

	public function ultimakit_product_duplicate_button() {
		global $current_screen, $post;

		if ( 'product' != $current_screen->post_type ) {
			return;
		}

		$admin_url     = admin_url( 'admin-ajax.php?action=ultimakit-duplicate-post&ultimakit_nonce=' . wp_create_nonce( 'ultimakit-ajax-nonce' ) . '&post_ID=' . $post->ID );
		$duplicate_btn = '<a href= "' . $admin_url . '" class="page-title-action" data-duplicate>' . __( 'Duplicate', 'ultimakit-for-wp' ) . '</a>';

		echo "<script type='text/javascript'>jQuery(document).ready( function($) { jQuery('.wrap a:first').after('" . esc_attr( $duplicate_btn ) . "'); }); </script>";
	}
}
