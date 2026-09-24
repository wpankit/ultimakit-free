<?php
/**
 * Class UltimaKit_Module_Simple_Notification_Bar
 *
 * @since 1.0.0
 * @package    UltimaKit
 */

/**
 * Class UltimaKit_Module_Simple_Notification_Bar
 *
 * @since 1.0.0
 */
class UltimaKit_Module_Simple_Notification_Bar extends UltimaKit_Module_Manager {
	/**
	 * @var string
	 */
	protected $ID = 'ultimakit_module_simple_notification_bar';

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
	protected $read_more_link = 'add-notification-bar-in-wordpress';

	/**
	 * The settings associated with the module, if any.
	 *
	 * @var array
	 */
	protected $settings;

	private $default_options = [
        'message' => 'This is a notification bar!',
        'background_color' => '#ffcc00',
        'text_color' => '#000000',
        'font_size' => '16px',
        'show_close_button' => true,
    ];

	/**
	 * Constructs the Simple Notification Bar module instance.
	 */
	public function __construct() {
		$this->name        = __( 'Top Notification Bar', 'ultimakit-for-wp' );
		$this->description = __( 'Adds a notification bar at the top of the site for announcements.', 'ultimakit-for-wp' );
		$this->is_active   = $this->isModuleActive( $this->ID );
		$this->settings    = 'yes';
		$this->initializeModule();
	}

	/**
	 * Initializes the module: registers hooks when active.
	 *
	 * @return void
	 */
	protected function initializeModule() {
		if ( $this->is_active ) {
			add_action( 'admin_enqueue_scripts', array( $this, 'add_scripts' ) );
			add_action( 'admin_menu', array( $this, 'register_settings_page' ) );
			add_action( 'admin_post_save_notification_bar_settings', array( $this, 'save_settings' ) );
			add_action( 'wp_footer', array( $this, 'display_notification_bar' ) );
		}
	}

	/**
	 * Registers the dedicated settings submenu page.
	 *
	 * @return void
	 */
	public function register_settings_page() {
		add_submenu_page(
			'wp-ultimakit-dashboard',
			__( 'Notification Bar Settings', 'ultimakit-for-wp' ),
			__( 'Notification Bar', 'ultimakit-for-wp' ),
			'manage_options',
			'wp-ultimakit-simple-notification-bar',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Renders the dedicated settings page.
	 *
	 * @return void
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$helper = new UltimaKit_Helpers();

		// Current saved values
		$noti_message       = $this->getModuleSettings( $this->ID, 'noti_bar_text_area', '' );
		$bg_color           = $this->getModuleSettings( $this->ID, 'wpuk_noti_bg_color', '#ffcc00' );
		$txt_color          = $this->getModuleSettings( $this->ID, 'wpuk_noti_txt_color', '#000000' );
		$txt_size           = $this->getModuleSettings( $this->ID, 'wpuk_noti_txt_size', 16 );
		$txt_weight         = $this->getModuleSettings( $this->ID, 'wpuk_noti_txt_weight', 400 );
		$show_close         = $this->getModuleSettings( $this->ID, 'wpuk_noti_btn', 'off' );
		$bar_position       = $this->getModuleSettings( $this->ID, 'bar_position', 'top' );
		$fixed_position     = $this->getModuleSettings( $this->ID, 'fixed_position', 'off' );
		$sticky_selector    = $this->getModuleSettings( $this->ID, 'sticky_selector', '' );
		$display_on_scroll  = $this->getModuleSettings( $this->ID, 'display_on_scroll', 'off' );
		$scroll_offset      = $this->getModuleSettings( $this->ID, 'scroll_offset', 100 );
		$bar_height         = $this->getModuleSettings( $this->ID, 'bar_height', 50 );
		$position_offset    = $this->getModuleSettings( $this->ID, 'position_offset', 0 );
		$display_after      = $this->getModuleSettings( $this->ID, 'display_after', 0 );
		$animation_duration = $this->getModuleSettings( $this->ID, 'animation_duration', 0 );
		$auto_close         = $this->getModuleSettings( $this->ID, 'auto_close', 0 );
		$display_shadow     = $this->getModuleSettings( $this->ID, 'display_shadow', 'off' );
		$display_reopen     = $this->getModuleSettings( $this->ID, 'display_reopen', 'off' );
		$reopen_image_url   = $this->getModuleSettings( $this->ID, 'reopen_image_url', '' );
		$reopen_offset      = $this->getModuleSettings( $this->ID, 'reopen_offset', 0 );
		$display_devices    = $this->getModuleSettings( $this->ID, 'display_devices', 'all' );
		$small_device_width = $this->getModuleSettings( $this->ID, 'small_device_width', 640 );
		$hide_small_window  = $this->getModuleSettings( $this->ID, 'hide_small_window', 'off' );
		$small_window_width = $this->getModuleSettings( $this->ID, 'small_window_width', 640 );

		$saved = isset( $_GET['saved'] ) && '1' === $_GET['saved'];
		?>
		<div class="wrap">
			<?php $helper->ultimakit_get_header(); ?>

			<?php if ( $saved ) : ?>
				<div class="notice notice-success is-dismissible">
					<p><?php esc_html_e( 'Settings saved successfully.', 'ultimakit-for-wp' ); ?></p>
				</div>
			<?php endif; ?>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="save_notification_bar_settings">
				<?php wp_nonce_field( 'save_notification_bar_settings', 'notification_bar_nonce' ); ?>

				<div class="mt-3">

						<!-- Content Settings -->
						<div class="col-12 bg-white p-3 mb-3">
							<h3 class="mb-3 pb-2 border-bottom"><?php esc_html_e( 'Notification Message', 'ultimakit-for-wp' ); ?></h3>
							<div class="notice notice-info inline" style="margin:0 0 12px;">
								<p><strong><?php esc_html_e( 'Allowed HTML:', 'ultimakit-for-wp' ); ?></strong>
								<?php esc_html_e( 'Links, bold, italic, span, line break.', 'ultimakit-for-wp' ); ?>
								<?php esc_html_e( 'Example:', 'ultimakit-for-wp' ); ?>
								<code>&lt;a href="https://example.com"&gt;Sale&lt;/a&gt; &lt;strong&gt;20% off&lt;/strong&gt;</code></p>
							</div>
							<?php
							wp_editor(
								$noti_message,
								'wpuk_notification_message_editor',
								array(
									'textarea_name' => 'noti_bar_text_area',
									'media_buttons' => false,
									'textarea_rows' => 8,
									'teeny'         => false,
									'quicktags'     => true,
								)
							);
							?>
						</div>

						<!-- Style Settings -->
						<div class="col-12 bg-white p-3 mb-3">
							<h3 class="mb-3 pb-2 border-bottom"><?php esc_html_e( 'Style', 'ultimakit-for-wp' ); ?></h3>
							<table class="form-table" role="presentation">
								<tr>
									<th scope="row"><label for="wpuk_noti_bg_color"><?php esc_html_e( 'Background Color', 'ultimakit-for-wp' ); ?></label></th>
									<td>
										<input type="text" id="wpuk_noti_bg_color" name="wpuk_noti_bg_color"
											value="<?php echo esc_attr( $bg_color ); ?>">
									</td>
								</tr>
								<tr>
									<th scope="row"><label for="wpuk_noti_txt_color"><?php esc_html_e( 'Text Color', 'ultimakit-for-wp' ); ?></label></th>
									<td>
										<input type="text" id="wpuk_noti_txt_color" name="wpuk_noti_txt_color"
											value="<?php echo esc_attr( $txt_color ); ?>">
									</td>
								</tr>
								<tr>
									<th scope="row"><label for="wpuk_noti_txt_size"><?php esc_html_e( 'Font Size (px)', 'ultimakit-for-wp' ); ?></label></th>
									<td>
										<input type="number" id="wpuk_noti_txt_size" name="wpuk_noti_txt_size"
											value="<?php echo esc_attr( $txt_size ); ?>" min="10" max="32" class="small-text">
										<p class="description"><?php esc_html_e( 'Choose a size between 10 and 32 pixels.', 'ultimakit-for-wp' ); ?></p>
									</td>
								</tr>
								<tr>
									<th scope="row"><label for="wpuk_noti_txt_weight"><?php esc_html_e( 'Font Weight', 'ultimakit-for-wp' ); ?></label></th>
									<td>
										<select id="wpuk_noti_txt_weight" name="wpuk_noti_txt_weight">
											<?php foreach ( array( 100, 200, 300, 400, 500, 600, 700, 800 ) as $w ) : ?>
												<option value="<?php echo esc_attr( $w ); ?>" <?php selected( $txt_weight, $w ); ?>><?php echo esc_html( $w ); ?></option>
											<?php endforeach; ?>
										</select>
									</td>
								</tr>
								<tr>
									<th scope="row"><?php esc_html_e( 'Show Close Button', 'ultimakit-for-wp' ); ?></th>
									<td>
										<label>
											<input type="checkbox" name="wpuk_noti_btn" value="on" <?php checked( $show_close, 'on' ); ?>>
											<?php esc_html_e( 'Enable', 'ultimakit-for-wp' ); ?>
										</label>
									</td>
								</tr>
							</table>
						</div>

						<!-- Position Settings -->
						<div class="col-12 bg-white p-3 mb-3">
							<h3 class="mb-3 pb-2 border-bottom"><?php esc_html_e( 'Position Settings', 'ultimakit-for-wp' ); ?></h3>
							<table class="form-table" role="presentation">
								<tr>
									<th scope="row"><label for="bar_position"><?php esc_html_e( 'Position', 'ultimakit-for-wp' ); ?></label></th>
									<td>
										<select id="bar_position" name="bar_position">
											<option value="top" <?php selected( $bar_position, 'top' ); ?>><?php esc_html_e( 'Top', 'ultimakit-for-wp' ); ?></option>
											<option value="bottom" <?php selected( $bar_position, 'bottom' ); ?>><?php esc_html_e( 'Bottom', 'ultimakit-for-wp' ); ?></option>
										</select>
									</td>
								</tr>
								<tr>
									<th scope="row"><?php esc_html_e( 'Fixed at Position', 'ultimakit-for-wp' ); ?></th>
									<td>
										<label>
											<input type="checkbox" name="fixed_position" value="on" <?php checked( $fixed_position, 'on' ); ?>>
											<?php esc_html_e( 'Enable', 'ultimakit-for-wp' ); ?>
										</label>
									</td>
								</tr>
								<tr>
									<th scope="row"><label for="sticky_selector"><?php esc_html_e( 'Theme Sticky Selector', 'ultimakit-for-wp' ); ?></label></th>
									<td>
										<input type="text" id="sticky_selector" name="sticky_selector"
											value="<?php echo esc_attr( $sticky_selector ); ?>" class="regular-text">
										<p class="description"><?php esc_html_e( 'CSS selector for sticky theme elements (e.g. #header_top)', 'ultimakit-for-wp' ); ?></p>
									</td>
								</tr>
							</table>
						</div>

						<!-- Display Settings -->
						<div class="col-12 bg-white p-3 mb-3">
							<h3 class="mb-3 pb-2 border-bottom"><?php esc_html_e( 'Display Settings', 'ultimakit-for-wp' ); ?></h3>
							<table class="form-table" role="presentation">
								<tr>
									<th scope="row"><?php esc_html_e( 'Display on Scroll', 'ultimakit-for-wp' ); ?></th>
									<td>
										<label>
											<input type="checkbox" name="display_on_scroll" value="on" <?php checked( $display_on_scroll, 'on' ); ?>>
											<?php esc_html_e( 'Enable', 'ultimakit-for-wp' ); ?>
										</label>
									</td>
								</tr>
								<tr>
									<th scope="row"><label for="scroll_offset"><?php esc_html_e( 'Scroll Offset (px)', 'ultimakit-for-wp' ); ?></label></th>
									<td>
										<input type="number" id="scroll_offset" name="scroll_offset"
											value="<?php echo esc_attr( $scroll_offset ); ?>" min="0" max="1000" class="small-text">
										<p class="description"><?php esc_html_e( 'Show notification after scrolling this many pixels.', 'ultimakit-for-wp' ); ?></p>
									</td>
								</tr>
								<tr>
									<th scope="row"><label for="bar_height"><?php esc_html_e( 'Bar Height (px)', 'ultimakit-for-wp' ); ?></label></th>
									<td>
										<input type="number" id="bar_height" name="bar_height"
											value="<?php echo esc_attr( $bar_height ); ?>" min="30" max="200" class="small-text">
									</td>
								</tr>
								<tr>
									<th scope="row"><label for="position_offset"><?php esc_html_e( 'Position Offset (px)', 'ultimakit-for-wp' ); ?></label></th>
									<td>
										<input type="number" id="position_offset" name="position_offset"
											value="<?php echo esc_attr( $position_offset ); ?>" min="0" max="500" class="small-text">
									</td>
								</tr>
								<tr>
									<th scope="row"><label for="display_after"><?php esc_html_e( 'Display After (seconds)', 'ultimakit-for-wp' ); ?></label></th>
									<td>
										<input type="number" id="display_after" name="display_after"
											value="<?php echo esc_attr( $display_after ); ?>" min="0" max="60" class="small-text">
									</td>
								</tr>
								<tr>
									<th scope="row"><label for="animation_duration"><?php esc_html_e( 'Animation Duration (seconds)', 'ultimakit-for-wp' ); ?></label></th>
									<td>
										<input type="number" id="animation_duration" name="animation_duration"
											value="<?php echo esc_attr( $animation_duration ); ?>" min="0" max="10" class="small-text">
									</td>
								</tr>
								<tr>
									<th scope="row"><label for="auto_close"><?php esc_html_e( 'Auto Close After (seconds)', 'ultimakit-for-wp' ); ?></label></th>
									<td>
										<input type="number" id="auto_close" name="auto_close"
											value="<?php echo esc_attr( $auto_close ); ?>" min="0" max="300" class="small-text">
										<p class="description"><?php esc_html_e( 'Set to 0 to disable auto-close.', 'ultimakit-for-wp' ); ?></p>
									</td>
								</tr>
								<tr>
									<th scope="row"><?php esc_html_e( 'Display Shadow', 'ultimakit-for-wp' ); ?></th>
									<td>
										<label>
											<input type="checkbox" name="display_shadow" value="on" <?php checked( $display_shadow, 'on' ); ?>>
											<?php esc_html_e( 'Enable', 'ultimakit-for-wp' ); ?>
										</label>
									</td>
								</tr>
							</table>
						</div>

						<!-- Reopen Button Settings -->
						<div class="col-12 bg-white p-3 mb-3">
							<h3 class="mb-3 pb-2 border-bottom"><?php esc_html_e( 'Reopen Button Settings', 'ultimakit-for-wp' ); ?></h3>
							<table class="form-table" role="presentation">
								<tr>
									<th scope="row"><?php esc_html_e( 'Display Reopen Button', 'ultimakit-for-wp' ); ?></th>
									<td>
										<label>
											<input type="checkbox" name="display_reopen" value="on" <?php checked( $display_reopen, 'on' ); ?>>
											<?php esc_html_e( 'Enable', 'ultimakit-for-wp' ); ?>
										</label>
									</td>
								</tr>
								<tr>
									<th scope="row"><label for="reopen_image_url"><?php esc_html_e( 'Reopen Button Image URL', 'ultimakit-for-wp' ); ?></label></th>
									<td>
										<input type="url" id="reopen_image_url" name="reopen_image_url"
											value="<?php echo esc_attr( $reopen_image_url ); ?>" class="regular-text">
									</td>
								</tr>
								<tr>
									<th scope="row"><label for="reopen_offset"><?php esc_html_e( 'Reopen Button Offset (px)', 'ultimakit-for-wp' ); ?></label></th>
									<td>
										<input type="number" id="reopen_offset" name="reopen_offset"
											value="<?php echo esc_attr( $reopen_offset ); ?>" min="0" max="200" class="small-text">
									</td>
								</tr>
							</table>
						</div>

						<!-- Device Settings -->
						<div class="col-12 bg-white p-3 mb-3">
							<h3 class="mb-3 pb-2 border-bottom"><?php esc_html_e( 'Device Settings', 'ultimakit-for-wp' ); ?></h3>
							<table class="form-table" role="presentation">
								<tr>
									<th scope="row"><label for="display_devices"><?php esc_html_e( 'Display On Devices', 'ultimakit-for-wp' ); ?></label></th>
									<td>
										<select id="display_devices" name="display_devices">
											<option value="all" <?php selected( $display_devices, 'all' ); ?>><?php esc_html_e( 'All Devices', 'ultimakit-for-wp' ); ?></option>
											<option value="small" <?php selected( $display_devices, 'small' ); ?>><?php esc_html_e( 'Small Devices Only', 'ultimakit-for-wp' ); ?></option>
											<option value="large" <?php selected( $display_devices, 'large' ); ?>><?php esc_html_e( 'Except Small Devices', 'ultimakit-for-wp' ); ?></option>
										</select>
									</td>
								</tr>
								<tr>
									<th scope="row"><label for="small_device_width"><?php esc_html_e( 'Small Device Max Width (px)', 'ultimakit-for-wp' ); ?></label></th>
									<td>
										<input type="number" id="small_device_width" name="small_device_width"
											value="<?php echo esc_attr( $small_device_width ); ?>" min="320" max="1200" class="small-text">
									</td>
								</tr>
								<tr>
									<th scope="row"><?php esc_html_e( 'Hide on Small Window', 'ultimakit-for-wp' ); ?></th>
									<td>
										<label>
											<input type="checkbox" name="hide_small_window" value="on" <?php checked( $hide_small_window, 'on' ); ?>>
											<?php esc_html_e( 'Enable', 'ultimakit-for-wp' ); ?>
										</label>
									</td>
								</tr>
								<tr>
									<th scope="row"><label for="small_window_width"><?php esc_html_e( 'Small Window Max Width (px)', 'ultimakit-for-wp' ); ?></label></th>
									<td>
										<input type="number" id="small_window_width" name="small_window_width"
											value="<?php echo esc_attr( $small_window_width ); ?>" min="320" max="1200" class="small-text">
									</td>
								</tr>
							</table>
						</div>

				</div><!-- .mt-3 -->

				<?php submit_button( __( 'Save Settings', 'ultimakit-for-wp' ) ); ?>

			</form>
		</div>
		<?php
	}

	/**
	 * Handles the settings form POST and saves all values.
	 *
	 * @return void
	 */
	public function save_settings() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions.', 'ultimakit-for-wp' ) );
		}

		check_admin_referer( 'save_notification_bar_settings', 'notification_bar_nonce' );

		$post = wp_unslash( $_POST );

		// Allow the same HTML tags that the frontend allows when displaying
		$settings = array(
			'noti_bar_text_area'  => wp_kses_post( $post['noti_bar_text_area'] ?? '' ),
			'wpuk_noti_bg_color'  => sanitize_hex_color( $post['wpuk_noti_bg_color'] ?? '#ffcc00' ),
			'wpuk_noti_txt_color' => sanitize_hex_color( $post['wpuk_noti_txt_color'] ?? '#000000' ),
			'wpuk_noti_txt_size'  => max( 10, min( 32, intval( $post['wpuk_noti_txt_size'] ?? 16 ) ) ),
			'wpuk_noti_txt_weight' => in_array( intval( $post['wpuk_noti_txt_weight'] ?? 400 ), array( 100, 200, 300, 400, 500, 600, 700, 800 ) )
				? intval( $post['wpuk_noti_txt_weight'] )
				: 400,
			'wpuk_noti_btn'       => isset( $post['wpuk_noti_btn'] ) ? 'on' : 'off',
			'bar_position'        => in_array( $post['bar_position'] ?? 'top', array( 'top', 'bottom' ) ) ? $post['bar_position'] : 'top',
			'fixed_position'      => isset( $post['fixed_position'] ) ? 'on' : 'off',
			'sticky_selector'     => sanitize_text_field( $post['sticky_selector'] ?? '' ),
			'display_on_scroll'   => isset( $post['display_on_scroll'] ) ? 'on' : 'off',
			'scroll_offset'       => max( 0, min( 1000, intval( $post['scroll_offset'] ?? 100 ) ) ),
			'bar_height'          => max( 30, min( 200, intval( $post['bar_height'] ?? 50 ) ) ),
			'position_offset'     => max( 0, min( 500, intval( $post['position_offset'] ?? 0 ) ) ),
			'display_after'       => max( 0, min( 60, intval( $post['display_after'] ?? 0 ) ) ),
			'animation_duration'  => max( 0, min( 10, intval( $post['animation_duration'] ?? 0 ) ) ),
			'auto_close'          => max( 0, min( 300, intval( $post['auto_close'] ?? 0 ) ) ),
			'display_shadow'      => isset( $post['display_shadow'] ) ? 'on' : 'off',
			'display_reopen'      => isset( $post['display_reopen'] ) ? 'on' : 'off',
			'reopen_image_url'    => esc_url_raw( $post['reopen_image_url'] ?? '' ),
			'reopen_offset'       => max( 0, min( 200, intval( $post['reopen_offset'] ?? 0 ) ) ),
			'display_devices'     => in_array( $post['display_devices'] ?? 'all', array( 'all', 'small', 'large' ) ) ? $post['display_devices'] : 'all',
			'small_device_width'  => max( 320, min( 1200, intval( $post['small_device_width'] ?? 640 ) ) ),
			'hide_small_window'   => isset( $post['hide_small_window'] ) ? 'on' : 'off',
			'small_window_width'  => max( 320, min( 1200, intval( $post['small_window_width'] ?? 640 ) ) ),
		);

		// Call directly on $this (which inherits UltimaKit_Helpers) to avoid
		// the null $this->helper issue when parent::__construct() is not called.
		$this->ultimakit_update_module_setting( $this->ID, 'settings', $settings, true );

		wp_redirect(
			add_query_arg(
				array(
					'page'  => 'wp-ultimakit-simple-notification-bar',
					'saved' => '1',
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Enqueues scripts and styles for the admin.
	 *
	 * @return void
	 */
	public function add_scripts() {

		wp_enqueue_style( 'wp-color-picker' );

		wp_enqueue_script(
			'ultimakit-module-script-' . $this->ID,
			plugins_url( '/module-script.js', __FILE__ ),
			array( 'jquery', 'wp-color-picker' ),
			ULTIMAKIT_FOR_WP_VERSION,
			true
		);

		wp_localize_script(
			'ultimakit-module-script-' . $this->ID,
			'wpuk_notification_bar',
			array(
				'settings_url' => admin_url( 'admin.php?page=wp-ultimakit-simple-notification-bar' ),
			)
		);
	}


	// Helper to return the allowed HTML for front-end display
	private function get_allowed_html() {
		return array(
			'a' => array(
				'href' => array(),
				'title' => array(),
				'target' => array(),
				'class' => array()
			),
			'b' => array(),
			'strong' => array(),
			'i' => array(),
			'em' => array(),
			'span' => array(
				'class' => array()
			),
			'br' => array(),
			'button' => array(
				'type' => array(),
				'title' => array(),
				'class' => array()
			)
		);
	}


    public function display_notification_bar() {

		// Check if the notification bar should be displayed
		if (empty($this->getModuleSettings($this->ID, 'noti_bar_text_area'))) {
			return;
		}

		// Get settings
		$position = $this->getModuleSettings($this->ID, 'bar_position', 'top');
		$fixed = $this->getModuleSettings($this->ID, 'fixed_position', 'off') === 'on';
		$bar_height = $this->getModuleSettings($this->ID, 'bar_height', 50);
		$position_offset = $this->getModuleSettings($this->ID, 'position_offset', 0);
		$display_shadow = $this->getModuleSettings($this->ID, 'display_shadow', 'off') === 'on';
		$animation_duration = $this->getModuleSettings($this->ID, 'animation_duration', 0);
		$auto_close = $this->getModuleSettings($this->ID, 'auto_close', 0);
		$display_reopen = $this->getModuleSettings($this->ID, 'display_reopen', 'off') === 'on';
		$reopen_offset = $this->getModuleSettings($this->ID, 'reopen_offset', 0);
		$reopen_image_url = $this->getModuleSettings($this->ID, 'reopen_image_url', '');
		$sticky_selector = $this->getModuleSettings($this->ID, 'sticky_selector', '');
		$display_on_scroll = $this->getModuleSettings($this->ID, 'display_on_scroll', 'off') === 'on';
		$scroll_offset = $this->getModuleSettings($this->ID, 'scroll_offset', 100);
		$display_after = $this->getModuleSettings($this->ID, 'display_after', 0);
		$display_devices = $this->getModuleSettings($this->ID, 'display_devices', 'all');
		$small_device_width = $this->getModuleSettings($this->ID, 'small_device_width', 640);
		$hide_small_window = $this->getModuleSettings($this->ID, 'hide_small_window', 'off') === 'on';
		$small_window_width = $this->getModuleSettings($this->ID, 'small_window_width', 640);

		// Define allowed HTML tags
		$allowed_html = $this->get_allowed_html();

		?>
		<style>
			#simple-notification-bar {
				background-color: <?php echo esc_attr($this->getModuleSettings($this->ID, 'wpuk_noti_bg_color', '#ffcc00')); ?>;
				color: <?php echo esc_attr($this->getModuleSettings($this->ID, 'wpuk_noti_txt_color', '#000000')); ?>;
				font-size: <?php echo esc_attr($this->getModuleSettings($this->ID, 'wpuk_noti_txt_size', 16)); ?>px;
				font-weight: <?php echo esc_attr($this->getModuleSettings($this->ID, 'wpuk_noti_txt_weight', 400)); ?>;
				padding: 10px 20px;
				text-align: center;
				position: <?php echo $fixed ? 'fixed' : 'absolute'; ?>;
				<?php echo esc_attr($position); ?>: <?php echo esc_attr($position_offset); ?>px;
				left: 0;
				right: 0;
				height: <?php echo esc_attr($bar_height); ?>px;
				width: 100%;
				z-index: 999999;
				<?php if ($display_shadow) : ?>box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);<?php endif; ?>
				display: none;
				transition: all <?php echo esc_attr($animation_duration); ?>s ease-in-out;
				box-sizing: border-box;
			}

			<?php if ($fixed && $position === 'top') : ?>
			body.has-notification-bar {
				padding-top: <?php echo esc_attr($bar_height + $position_offset); ?>px !important;
			}

			/* Handle admin bar overlap */
			body.admin-bar.has-notification-bar {
				padding-top: <?php echo esc_attr($bar_height + $position_offset + 32); ?>px !important;
			}

			@media (max-width: 782px) {
				body.admin-bar.has-notification-bar {
					padding-top: <?php echo esc_attr($bar_height + $position_offset + 46); ?>px !important;
				}
			}
			<?php endif; ?>

			<?php if ($fixed && $position === 'bottom') : ?>
			body.has-notification-bar {
				padding-bottom: <?php echo esc_attr($bar_height + $position_offset); ?>px !important;
			}
			<?php endif; ?>

			#simple-notification-bar-close {
				background: none;
				border: none;
				color: <?php echo esc_attr($this->getModuleSettings($this->ID, 'wpuk_noti_txt_color', '#000000')); ?> !important;
				font-size: 20px;
				margin-left: 10px;
				cursor: pointer;
				float: right;
				line-height: 1;
			}

			#simple-notification-bar-reopen {
				position: fixed;
				bottom: <?php echo esc_attr($reopen_offset); ?>px;
				right: 20px;
				cursor: pointer;
				z-index: 999998;
				display: none;
			}

			#simple-notification-bar-reopen img {
				max-width: 50px;
				height: auto;
			}

			@media (max-width: <?php echo esc_attr($small_device_width); ?>px) {
				<?php if ($display_devices === 'large') : ?>
				#simple-notification-bar {
					display: none !important;
				}
				<?php endif; ?>
			}

			@media (min-width: <?php echo esc_attr($small_device_width + 1); ?>px) {
				<?php if ($display_devices === 'small') : ?>
				#simple-notification-bar {
					display: none !important;
				}
				<?php endif; ?>
			}
		</style>

		<div id="simple-notification-bar">
			<div style="display: flex; align-items: center; justify-content: center; height: 100%;">
				<div style="flex: 1; text-align: center;">
					<?php echo wp_kses($this->getModuleSettings($this->ID, 'noti_bar_text_area'), $allowed_html); ?>
				</div>
				<?php if ('on' == $this->getModuleSettings($this->ID, 'wpuk_noti_btn')) : ?>
					<button id="simple-notification-bar-close">&times;</button>
				<?php endif; ?>
			</div>
		</div>

		<?php if ($display_reopen && !empty($reopen_image_url)) : ?>
			<div id="simple-notification-bar-reopen">
				<img src="<?php echo esc_url($reopen_image_url); ?>" alt="<?php esc_attr_e('Reopen Notification Bar', 'ultimakit-for-wp'); ?>" />
			</div>
		<?php endif; ?>

		<script>
			(function() {
				'use strict';

				// Configuration
				const config = {
					displayOnScroll: <?php echo $display_on_scroll ? 'true' : 'false'; ?>,
					scrollOffset: <?php echo esc_attr($scroll_offset); ?>,
					displayAfter: <?php echo esc_attr($display_after); ?> * 1000,
					autoClose: <?php echo esc_attr($auto_close); ?> * 1000,
					displayDevices: '<?php echo esc_attr($display_devices); ?>',
					smallDeviceWidth: <?php echo esc_attr($small_device_width); ?>,
					hideSmallWindow: <?php echo $hide_small_window ? 'true' : 'false'; ?>,
					smallWindowWidth: <?php echo esc_attr($small_window_width); ?>,
					stickySelector: '<?php echo esc_attr($sticky_selector); ?>',
					position: '<?php echo esc_attr($position); ?>',
					fixed: <?php echo $fixed ? 'true' : 'false'; ?>,
					barHeight: <?php echo esc_attr($bar_height); ?>,
					positionOffset: <?php echo esc_attr($position_offset); ?>
				};

				let hasBeenShown = false;
				let autoCloseTimer = null;

				function getElements() {
					return {
						bar: document.getElementById('simple-notification-bar'),
						closeButton: document.getElementById('simple-notification-bar-close'),
						reopenButton: document.getElementById('simple-notification-bar-reopen'),
						body: document.body
					};
				}

				function shouldDisplay() {
					const currentWidth = window.innerWidth;

					// Check small window setting
					if (config.hideSmallWindow && currentWidth <= config.smallWindowWidth) {
						return false;
					}

					// Check device display settings
					if (config.displayDevices === 'small' && currentWidth > config.smallDeviceWidth) {
						return false;
					}

					if (config.displayDevices === 'large' && currentWidth <= config.smallDeviceWidth) {
						return false;
					}

					return true;
				}

				function showBar() {
					const elements = getElements();
					if (!elements.bar || !shouldDisplay()) {
						return;
					}

					elements.bar.style.display = 'block';

					// Add body class for padding
					if (config.fixed) {
						elements.body.classList.add('has-notification-bar');

						// Handle admin bar if present
						if (elements.body.classList.contains('admin-bar') && config.position === 'top') {
							const adminBarHeight = window.innerWidth <= 782 ? 46 : 32;
							elements.bar.style.top = (adminBarHeight + config.positionOffset) + 'px';
						}
					}

					// Handle sticky selector
					if (config.stickySelector) {
						const stickyElement = document.querySelector(config.stickySelector);
						if (stickyElement && config.position === 'top') {
							stickyElement.style.top = (config.barHeight + config.positionOffset) + 'px';
						}
					}

					hasBeenShown = true;

					// Set auto-close timer
					if (config.autoClose > 0) {
						autoCloseTimer = setTimeout(hideBar, config.autoClose);
					}
				}

				function hideBar() {
					const elements = getElements();
					if (!elements.bar) {
						return;
					}

					elements.bar.style.display = 'none';

					// Remove body class
					elements.body.classList.remove('has-notification-bar');

					// Reset sticky element
					if (config.stickySelector) {
						const stickyElement = document.querySelector(config.stickySelector);
						if (stickyElement && config.position === 'top') {
							stickyElement.style.top = '';
						}
					}

					// Show reopen button
					if (elements.reopenButton) {
						elements.reopenButton.style.display = 'block';
					}

					// Clear auto-close timer
					if (autoCloseTimer) {
						clearTimeout(autoCloseTimer);
						autoCloseTimer = null;
					}
				}

				function handleScroll() {
					if (hasBeenShown || !config.displayOnScroll) {
						return;
					}

					if (window.scrollY >= config.scrollOffset) {
						showBar();
					}
				}

				function handleResize() {
					if (!shouldDisplay()) {
						hideBar();
					} else if (!hasBeenShown && !config.displayOnScroll) {
						showBar();
					}
				}

				function initialize() {
					const elements = getElements();

					if (!elements.bar) {
						return;
					}

					// Set up close button
					if (elements.closeButton) {
						elements.closeButton.addEventListener('click', hideBar);
					}

					// Set up reopen button
					if (elements.reopenButton) {
						elements.reopenButton.addEventListener('click', function() {
							elements.reopenButton.style.display = 'none';
							hasBeenShown = false;
							if (config.displayOnScroll) {
								// Reset scroll state
								if (window.scrollY >= config.scrollOffset) {
									showBar();
								}
							} else {
								showBar();
							}
						});
					}

					// Set up scroll listener
					if (config.displayOnScroll) {
						window.addEventListener('scroll', handleScroll);
					}

					// Set up resize listener
					let resizeTimer;
					window.addEventListener('resize', function() {
						clearTimeout(resizeTimer);
						resizeTimer = setTimeout(handleResize, 250);
					});

					// Initial display
					if (!config.displayOnScroll) {
						setTimeout(function() {
							if (!hasBeenShown) {
								showBar();
							}
						}, config.displayAfter);
					}
				}

				// Initialize when DOM is ready
				if (document.readyState === 'loading') {
					document.addEventListener('DOMContentLoaded', initialize);
				} else {
					initialize();
				}
			})();
		</script>
		<?php
	}

}
