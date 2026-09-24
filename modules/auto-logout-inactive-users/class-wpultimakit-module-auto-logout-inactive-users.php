<?php
/**
 * Class UltimaKit_Module_Auto_Logout_Inactive_Users
 *
 * @since 1.0.0
 * @package    UltimaKit
 */

/**
 * Class UltimaKit_Module_Auto_Logout_Inactive_Users
 *
 * This class provides methods to control the display of dashboard widgets based on user preferences.
 * It allows users to selectively hide certain dashboard widgets to streamline their WordPress dashboard
 * experience and improve usability.
 *
 * @since 1.0.0
 */
class UltimaKit_Module_Auto_Logout_Inactive_Users extends UltimaKit_Module_Manager {
	/**
	 *
	 * @var string
	 */
	protected $ID = 'ultimakit_auto_logout_inactive_users';

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
	protected $category = 'Log In/Out | Register';

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
	protected $read_more_link = 'auto-logout-inactive-users-in-wordpress';

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
		$this->name        = __( 'Auto-logout inactive users', 'ultimakit-for-wp' );
		$this->description = __( 'Automatically logout users after 10 minutes.', 'ultimakit-for-wp' );
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
			add_action( 'wp_footer', array( $this, 'add_script_in_footer' ) );
		}
	}

	public function add_script_in_footer() {
		?>
		<script type="text/javascript">
			var logoutUrl = '<?php echo htmlspecialchars_decode( wp_logout_url() ); ?>';
			var timeout;

			document.onload = resetTimeout;
			document.onmousemove = resetTimeout;
			document.onkeypress = resetTimeout;

			function resetTimeout() {
				clearTimeout( timeout );
				timeout = setTimeout( function () {
					window.location.href = logoutUrl;
				}, 600000 ); // 10 minutes
			}
		</script>
		<?php
	}
}
