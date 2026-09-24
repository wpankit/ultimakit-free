<?php
/**
 * Class UltimaKit_Module_Post_Page_Order
 *
 * @since 1.0.0
 * @package    UltimaKit
 */

/**
 * Class UltimaKit_Module_Post_Page_Order
 *
 * This class provides methods to control the display of dashboard widgets based on user preferences.
 * It allows users to selectively hide certain dashboard widgets to streamline their WordPress dashboard
 * experience and improve usability.
 *
 * @since 1.0.0
 */
class UltimaKit_Module_Post_Page_Order extends UltimaKit_Module_Manager {
	/**
	 * @var string
	 */
	protected $ID = 'ultimakit_post_page_order';

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
	protected $category = 'Utilities';

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
	protected $read_more_link = 'order-post-and-pages-in-wordpress';

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
		$this->name        = __( 'Post & Page Order', 'ultimakit-for-wp' );
		$this->description = __( 'Drag and drop pages to re-order page and post into a custom order.', 'ultimakit-for-wp' );
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

			add_post_type_support( 'post', 'page-attributes' );
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ), 110 );
			add_action( 'wp_insert_post', array( $this, 'post_created' ), 10, 3 );
			add_action( 'rest_api_init', array( $this, 'route_register' ) );

			add_action( 'manage_posts_custom_column', array( $this, 'manage_columns_column' ), 10, 2 );
			add_action( 'manage_pages_custom_column', array( $this, 'manage_columns_column' ), 10, 2 );
			add_filter( 'manage_edit-post_sortable_columns', array( $this, 'add_sortable_column' ), 10, 1 );
			add_filter( 'manage_edit-page_sortable_columns', array( $this, 'add_sortable_column' ), 10, 1 );
			add_action( 'admin_notices', array( $this, 'notice' ) );
			add_action( 'current_screen', array( $this, 'redirect_to_orderby_menu_order' ) );
		}
	}




	public function admin_scripts() {
		$screen = get_current_screen();

		if ( $screen->id == 'edit-post' || $screen->id == 'edit-page' || $screen->id == 'post' ) {
			wp_enqueue_script( 'jquery-ui-sortable', false, array( 'jquery', 'jquery-ui-core' ), ULTIMAKIT_FOR_WP_VERSION, true );
			wp_enqueue_script(
				'ultimakit-post-order',
				plugins_url( '/module-script.js', __FILE__ ),
				array(),
				ULTIMAKIT_FOR_WP_VERSION,
				true
			);
			wp_enqueue_style(
				'ultimakit-post-order',
				plugins_url( '/module-script.css', __FILE__ ),
				array(),
				ULTIMAKIT_FOR_WP_VERSION
			);
			wp_localize_script(
				'wp-api',
				'wpApiSettings',
				array(
					'root'  => esc_url_raw( rest_url() ),
					'nonce' => wp_create_nonce( 'wp_rest' ),
				)
			);
			wp_enqueue_script( 'wp-api' );
		}
	}

	public function manage_columns_column( $name, $post_ID ) {
		if ( $name !== 'ultimakit_order' ) {
			return;
		}
		$order = get_post_field( 'menu_order', $post_ID );
		echo absint( $order ) ? absint( $order ) : 0;
	} // manage_columns_column


	public function add_sortable_column( $columns ) {
		$columns['ultimakit_order'] = 'menu_order';
		return $columns;
	} // add_sortable_column


	public function redirect_to_orderby_menu_order() {
		$screen = get_current_screen();
		if ( $screen->id === 'edit-post' || $screen->id === 'edit-page' ) {
			if ( ! isset( $_REQUEST['orderby'] ) ) {
				$path   = wp_parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH );
				$query  = wp_parse_url( $_SERVER['REQUEST_URI'], PHP_URL_QUERY );
				$extra  = http_build_query(
					array(
						'orderby' => 'menu_order',
						'order'   => 'asc',
					)
				);
				$query .= ( $query ? '&' : '' ) . $extra;
				$url    = $path . '?' . $query;
				wp_redirect( $url, 302, 'ultimakit-for-wp' );
				exit;
			} elseif ( $_REQUEST['orderby'] === 'menu_order' ) {
				// check if we have any with order = 0;
				global $wpdb;
				$post_type = str_replace( 'edit-', '', $screen->id );
				$query     = $wpdb->prepare(
					" 
            SELECT COUNT(1) 
            FROM {$wpdb->posts} 
            WHERE `post_type` = %s
                AND `post_status` IN ('publish', 'pending', 'draft', 'future', 'private')
                AND `menu_order` = 1 
          ",
					$post_type
				);

				$zeros = $wpdb->get_var( $query );

				if ( $zeros ) {
					$page    = 1;
					$counter = 1;

					while ( $posts = get_posts(
						array(
							'post_type'   => str_replace( 'edit-', '', $screen->id ),
							'post_status' => array( 'publish', 'pending', 'draft', 'future', 'private' ),
							'fields'      => 'ids',
							'orderby'     => 'menu_order',
							'order'       => 'ASC',
							'numberposts' => 100,
							'paged'       => $page,
						)
					) ) {
						foreach ( $posts as $post_ID ) {

							wp_update_post(
								array(
									'ID'         => $post_ID,
									'menu_order' => $counter,
								)
							);

							++$counter;
						}

						++$page;
					}
				}
			}
		}
	} // redirect_to_orderby_menu_order

	public function orderby_menu_order( $orderby, $query ) {
		/*
		if( !is_admin() ) {
		return 'menu_order';
		}
		*/

		return $orderby;
	} //


	public function post_created( int $post_ID, WP_Post $post, $update ) {
		if ( $update ) {
			return;
		}

		if ( ! in_array( $post->post_type, array( 'post', 'page' ) ) ) {
			return;
		}

		if ( $post->menu_order !== 0 ) {
			return;
		}

		// get post maximum menu_order
		$args = array(
			'post_type'   => $post->post_type,
			'post_status' => array( 'publish', 'pending', 'draft', 'future', 'private' ),
			'numberposts' => 1,
			'orderby'     => 'menu_order',
			'order'       => 'DESC',
		);

		$top = get_posts( $args );

		if ( ! count( $top ) ) {
			return;
		}

		$post->menu_order = $top[0]->menu_order + 1;

		wp_update_post( $post, false, false );
	} // post_created


	public function notice() {
		$screen = get_current_screen();

		if ( $screen->id === 'edit-post' || $screen->id === 'edit-page' ) {
			if ( ! isset( $_REQUEST['orderby'] ) || $_REQUEST['orderby'] !== 'menu_order' ) {
				?>
		<div class="notice notice-info is-dismissible">
			<p><?php echo esc_html_e( 'To be able to reorder posts by drag and drop, please order posts by Order field', 'ultimakit-for-wp' ); ?></p>
		</div>
				<?php
			}
		}
	} // notice

	public function route_register() {

		register_rest_route(
			'ultimakit/v1',
			'/reorder',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'reorder_route' ),
				'permission_callback' => array( $this, 'route_rights_check' ),
			)
		);
	} // route_register

	public function route_rights_check() {
		return true;
		// return current_user_can( 'edit_others_posts' );
	}

	public function reorder_route() {
		try {
			$items = sanitize_post( $_POST['items'] );
			if ( empty( $items ) ) {
				throw new \Exception( 'Empty request' );
			}
			$errors = array();
			$saved  = array();

			foreach ( $items as $item ) {
				try {
					if ( empty( $item['id'] ) ) {
						throw new \Exception( 'Item does not have ID: ' . json_encode( $item ) );
					}

					$post = get_post( $item['id'] );

					if ( ! $post ) {
						throw new \Exception( 'Post not found: ' . json_encode( $item ) );
					}

					if ( $post->menu_order === $item['order'] ) {
						throw new \Exception( "Item {$item->id} order has not changed" );
					}

					$post->menu_order = $item['order'];

					$updated = wp_update_post( $post );

					if ( ! $updated ) {
						throw new \Exception( "Update of post {$item->id} failed" );
					}

					if ( is_wp_error( $updated ) ) {
						throw new \Exception( "Updated of post {$item->id} failed with " . implode( "\n", $updated->errors ) );
					}

					$saved[] = $item;
				} catch ( \Exception $e ) {
					$errors[] = $e->getMessage();
				}
			}
			$result = array(
				'status' => true,
				'errors' => $errors,
				'saved'  => $saved,
			);
		} catch ( \Exception $e ) {
			$result = array(
				'status' => false,
				'error'  => $e->getMessage(),
			);
		}
		wp_send_json( $result );
		wp_die();
	} // reorder_route
}
