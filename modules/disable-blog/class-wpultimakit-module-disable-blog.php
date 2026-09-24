<?php
/**
 * Class UltimaKit_Module_Disable_Blog
 *
 * @since 1.0.0
 * @package    UltimaKit
 */

/**
 * Class UltimaKit_Module_Disable_Blog
 *
 * This class provides methods to control the display of dashboard widgets based on user preferences.
 * It allows users to selectively hide certain dashboard widgets to streamline their WordPress dashboard
 * experience and improve usability.
 *
 * @since 1.0.0
 */
class UltimaKit_Module_Disable_Blog extends UltimaKit_Module_Manager {
	/**
	 *
	 * @var string
	 */
	protected $ID = 'ultimakit_module_disable_blog';

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
	protected $category = 'Disable Components';

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
	protected $read_more_link = 'disable-blog-in-wordpress';

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
		$this->name        = __( 'Disable Blog', 'ultimakit-for-wp' );
		$this->description = __( 'If you\'re not using the blog feature, turn it off completely in WordPress.', 'ultimakit-for-wp' );
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
			add_action( 'admin_bar_menu', array( $this, 'ultimakit_admin_bar_blog_link' ), 999 );
			add_action( 'admin_menu', array( $this, 'ultimakit_blog_sidebar_menu' ), 10, 1 );
			add_action( 'wp_dashboard_setup', array( $this, 'ultimakit_meta_boxes' ), 10, 1 );
			add_action( 'template_redirect', array( $this, 'ultimakit_redirect_public_pages' ) );
			add_action( 'init', array( $this, 'ultimakit_modify_post_type_arguments' ), 99 );
			add_action( 'widgets_init', array( $this, 'ultimakit_unregister_basic_widgets' ), 100 );
			add_action( 'init', array( $this, 'ultimakit_unregister_post_type' ), 100 );
			add_action( 'admin_notices', array( $this, 'ultimakit_admin_notices' ) );
			add_action( 'init', array( $this, 'ultimakit_disable_comments_and_feed_for_post_type' ), 999 );
		}
	}


	public function ultimakit_admin_bar_blog_link( $wp_admin_bar ) {
		$wp_admin_bar->remove_node( 'new-post' );
	}

	public function ultimakit_blog_sidebar_menu() {
		$menu_slug = array(
			'edit.php', // Posts
		);

		// Remove each menu item
		foreach ( $menu_slug as $main ) {
			remove_menu_page( $main );
		}

		remove_submenu_page( 'tools.php', 'tools.php' );
		remove_submenu_page( 'options-general.php', 'options-writing.php' );
		remove_submenu_page( 'options-general.php', 'options-discussion.php' );

		global $pagenow;
		$page_slug = array(
			'edit.php',
			'post-new.php',
			'edit-tags.php',
			'options-writing.php',
			'options-discussion.php',
		);

		if ( in_array( $pagenow, $page_slug, true ) && $_SERVER['REQUEST_METHOD'] === 'GET' && ( ! isset( $_GET['post_type'] ) || isset( $_GET['post_type'] ) && $_GET['post_type'] === 'post' ) ) {
			wp_safe_redirect( admin_url( 'edit.php?post_type=page' ), 301 );
			exit;
		}
	}

	public function ultimakit_meta_boxes() {
		remove_action( 'welcome_panel', 'wp_welcome_panel' );
		$meta_box = array(
			'dashboard_primary' => 'side',
		);

		foreach ( $meta_box as $id => $context ) {
			remove_meta_box( $id, 'dashboard', $context );
		}
	}

	public function ultimakit_redirect_public_pages() {
		$sitemap            = get_query_var( 'sitemap', false );
		$sitemap_stylesheet = get_query_var( 'sitemap-stylesheet', false );

		if ( is_admin()
			|| ! get_option( 'page_on_front' )
			|| ! empty( $sitemap )
			|| ! empty( $sitemap_stylesheet ) ) {
			return;
		}

		$page_id      = get_option( 'page_on_front' );
		$homepage_url = get_permalink( $page_id );
		$redirect_url = false;

		// The public pages to potentially be redirected.
		global $post;
		$public_redirects = array(
			'post'             => ( $post instanceof WP_Post && is_singular( 'post' ) ),
			'post_tag_archive' => is_tag(),
			'category_archive' => is_category(),
			'blog_page'        => is_home(),
			'date_archive'     => is_date(),
			'author_archive'   => ( is_author() && true === $this->functions->disable_author_archives() ),
		);

		foreach ( $public_redirects as $filtername => $bool ) {
			if ( true === $bool ) {
				$filter       = 'ultimakit_redirect_' . $filtername;
				$redirect_url = apply_filters( $filter, $homepage_url );
				break;
			}
		}

		if ( ! $redirect_url ) {
			return;
		}

		if ( apply_filters( $filter, true ) ) {
			wp_redirect( $redirect_url );
		}
	}

	public function ultimakit_modify_post_type_arguments() {
		global $wp_post_types;

		if ( isset( $wp_post_types['post'] ) ) {
			$arguments_to_remove = array(
				'has_archive',
				'public',
				'publicly_queryable',
				'rewrite',
				'query_var',
				'show_ui',
				'show_in_admin_bar',
				'show_in_nav_menus',
				'show_in_menu',
				'show_in_rest',
			);

			foreach ( $arguments_to_remove as $arg ) {
				if ( isset( $wp_post_types['post']->$arg ) ) {
					$wp_post_types['post']->$arg = false;
				}
			}

			// Exclude from search.
			$wp_post_types['post']->exclude_from_search = true;

			// Remove supports.
			$wp_post_types['post']->supports = array();
		}
	}

	public function ultimakit_unregister_basic_widgets() {
		unregister_widget( 'WP_Widget_Recent_Posts' );    // Recent posts
		unregister_widget( 'WP_Widget_Recent_Comments' ); // Recent comments
		remove_action( 'widgets_init', 'WP_Widget_Recent_Comments' );
	}

	public function ultimakit_unregister_post_type() {
		unregister_post_type( 'post' );
	}

	public function ultimakit_admin_notices() {
		$current_screen = get_current_screen();
		$screens        = array( 'plugins', 'options-reading', 'edit' );

		if ( ! $this->has_front_page() ) {
			if ( 'options-reading' === $current_screen->base ) {
				$message_link = ' ' . __( 'Select a page for your homepage below.', 'ultimakit-for-wp' );
			} else {
				// If we're not on the Reading Options page, then direct the user there.
				$reading_options_page = get_admin_url( null, 'options-reading.php' );
				$message_link         = ' ' . sprintf( __( 'Change in <a href="%s">Reading Settings</a>.', 'ultimakit-for-wp' ), $reading_options_page );
			}

			$message = __( 'Ultimakit For WP disable Blog is not fully active until a static page is selected for the site\'s homepage.', 'ultimakit-for-wp' ) . $message_link;
			printf( '<div class="%s"><p>%s</p></div>', 'notice notice-error blog-error', wp_kses_post( $message ) );

		} elseif ( 'options-reading' === $current_screen->base
				&& get_option( 'page_for_posts' ) === get_option( 'page_on_front' ) ) {
			$message = __( 'Ultimakit For WP disable Blog requires a homepage that is different from the post page. The "posts page" will be redirected to the homepage.', 'ultimakit-for-wp' );
			printf( '<div class="%s"><p>%s</p></div>', 'notice notice-error blog-error', esc_attr( $message ) );
		}
	}

	public function has_front_page() {
		return 'page' === get_option( 'show_on_front' ) && absint( get_option( 'page_on_front' ) );
	}

	public function ultimakit_disable_comments_and_feed_for_post_type() {
		// Disable support for comments and trackbacks for the 'post' post type
		remove_post_type_support( 'post', 'comments' );
		remove_post_type_support( 'post', 'trackbacks' );

		// Remove feed links for the 'post' post type
		remove_action( 'wp_head', 'feed_links', 2 );
		remove_action( 'wp_head', 'feed_links_extra', 3 );
	}
}
