<?php
/**
 * Class UltimaKit_Module_Quick_Add_Post
 *
 * @since 1.0.0
 * @package    UltimaKit
 */

/**
 * Class UltimaKit_Module_Quick_Add_Post
 *
 * This class provides methods to control the display of dashboard widgets based on user preferences.
 * It allows users to selectively hide certain dashboard widgets to streamline their WordPress dashboard
 * experience and improve usability.
 *
 * @since 1.0.0
 */
class UltimaKit_Module_Quick_Add_Post extends UltimaKit_Module_Manager {
	/**
	 * Unique identifier for the Hide Admin Bar module.
	 *
	 * @var string
	 */
	protected $ID = 'ultimakit_quick_add_post';

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
	protected $read_more_link = 'quick-add-post-in-wordpress';

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
		$this->name        = __( 'Quick Add Post', 'ultimakit-for-wp' );
		$this->description = __( 'A new button to quickly add new posts to speed up your workflow.', 'ultimakit-for-wp' );
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
			add_action( 'admin_footer', array( $this, 'admin_scripts' ) );
		}
	}


	public function admin_scripts() {

		// Get the current screen and check if we're on the post editor screen
		$screen = get_current_screen();
		if ( $screen->id == 'post' ) { // Check for specific post types with $screen->post_type
			?>
			<script type="text/javascript">
				jQuery(document).ready(function($) {
					// Target the editor's header for the button placement
					$(document).on("click", "#ultimakit-add-quick-post", function() {
						// Define your button action here. Example: Redirect to a new post page
						window.location.href = "<?php echo esc_url( admin_url( 'post-new.php' ) ); ?>";
					});

					setTimeout(function(){
						$('.edit-post-header__toolbar').append('<button id="ultimakit-add-quick-post" class="components-button editor-post-publish-button editor-post-publish-button__button is-primary">Quick Post</button>');    

						$('.page-title-action').after('<button id="ultimakit-add-quick-post" class="page-title-action">Quick Post</button>');    
						
					},500);
					
				});
			</script>
			<?php
		}
	}
}

