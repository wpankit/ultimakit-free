<?php
/**
 * Class UltimaKit_Module_Disable_Comments
 *
 * @since 1.0.0
 * @package    UltimaKit
 */

/**
 * Class UltimaKit_Module_Disable_Comments
 *
 * This class provides methods to control the display of dashboard widgets based on user preferences.
 * It allows users to selectively hide certain dashboard widgets to streamline their WordPress dashboard
 * experience and improve usability.
 *
 * @since 1.0.0
 */
class UltimaKit_Module_Disable_Comments extends UltimaKit_Module_Manager {
	/**
	 *
	 * @var string
	 */
	protected $ID = 'ultimakit_module_disable_comments';

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
	protected $read_more_link = 'disable-comments-in-wordpress';

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
		$this->name        = __( 'Disable Comments', 'ultimakit-for-wp' );
		$this->description = __( 'Disable the entire comment feature from WordPress if not utilised.', 'ultimakit-for-wp' );
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
			add_action( 'admin_enqueue_scripts', array( $this, 'add_scripts' ), 110 );

			add_action( 'widgets_init', array( $this, 'ultimakit_unregister_basic_widgets' ) );
			add_action( 'admin_menu', array( $this, 'ultimakit_disable_comments' ), '9999' );
			add_filter( 'comments_open', array( $this, 'ultimakit_disable_all_comment_status' ), 10, 2 );
			add_action( 'wp_dashboard_setup', array( $this, 'ultimakit_disable_default_dashboard_widgets' ), 999 );
			add_action( 'wp_before_admin_bar_render', array( $this, 'ultimakit_admin_bar_comment_link' ) );
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
	}

	public function ultimakit_disable_comments( $post_status ) {
		global $menu, $pagenow;

		remove_meta_box( 'dashboard_recent_comments', 'dashboard', 'normal' );
		$types = get_post_types( array( 'public' => true ), 'objects' );

		foreach ( $types as $post_type ) {
			if ( post_type_supports( $post_type->name, 'comments' ) && $post_type->name != 'product' ) {
				remove_post_type_support( $post_type->name, 'comments' );
				remove_post_type_support( $post_type->name, 'trackbacks' );
			}
		}

		foreach ( $types as $post_type ) {
			if ( $post_type->name != 'product' ) {
				remove_menu_page( 'edit-comments.php' );
				remove_menu_page( 'comment.php' );
				remove_meta_box( 'postcustom', 'post', 'normal' );
				remove_meta_box( 'commentsdiv', 'post', 'normal' );
				remove_action( 'admin_bar_menu', 'wp_admin_bar_comments_menu', 60 );
				add_filter( 'comments_array', '__return_empty_array', 10, 2 );
				add_filter( 'comments_open', '__return_false', 20, 2 );
			}
		}

		foreach ( $menu as $comment_menu ) {
			if ( $pagenow == 'edit-comments.php' || $pagenow == 'comment.php' ) {
				do_action( 'admin_page_access_denied' );
				wp_die( esc_html( 'Sorry, Comments are disabled.', 'ultimakit-for-wp' ), 403 );
			}
		}
	}

	public function ultimakit_disable_all_comment_status( $open, $post_id ) {
		$types        = get_post_types( array( 'public' => true ), 'objects' );
		$current_type = get_post_type( $post_id );

		foreach ( $types as $post_type ) {
			$post = get_post( $post_id );
			if ( $post->post_type != 'product' ) {
				return false;
			}
			return $open;
		}
	}

	public function ultimakit_disable_default_dashboard_widgets() {
		global $wp_meta_boxes;
		unset( $wp_meta_boxes['dashboard']['normal']['core']['dashboard_activity'] );
	}

	public function ultimakit_unregister_basic_widgets() {
		unregister_widget( 'WP_Widget_Recent_Posts' );    // Recent posts
		unregister_widget( 'WP_Widget_Recent_Comments' ); // Recent comments
	}

	public function ultimakit_admin_bar_comment_link() {
		global $wp_admin_bar;
		$types = get_post_types( array( 'public' => true ), 'objects' );

		foreach ( $types as $post_type ) {
			if ( $post_type->name != 'product' ) {
				$wp_admin_bar->remove_menu( 'comments' );
			}
		}
	}
}
