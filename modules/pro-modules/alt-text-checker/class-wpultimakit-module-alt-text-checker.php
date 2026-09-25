<?php
/**
 * Class UltimaKit_Module_Alt_Text_Checker
 *
 * @since 1.0.0
 * @package    UltimaKit
 */

/**
 * Class UltimaKit_Module_Alt_Text_Checker
 *
 * This class provides methods to control the display of dashboard widgets based on user preferences.
 * It allows users to selectively hide certain dashboard widgets to streamline their WordPress dashboard
 * experience and improve usability.
 *
 * @since 1.0.0
 */
class UltimaKit_Module_Alt_Text_Checker extends UltimaKit_Module_Manager {
	/**
	 *
	 * @var string
	 */
	protected $ID = 'ultimakit_module_alt_text_checker';

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
	protected $read_more_link = 'alt-text-checker-in-wordpress';

	/**
	 * The settings associated with the module, if any.
	 *
	 * @var array
	 */
	protected $settings;

	/**
	 * Items per page for pagination
	 */
	private $items_per_page = 20;

	/**
	 *
	 * Initializes the module with default values for properties and prepares
	 * any necessary setup or hooks into WordPress. This may include setting
	 * initial values, registering hooks, or preparing resources needed for
	 * the module to function properly within WordPress.
	 */
	public function __construct() {
		$this->name        = __( 'Image Alt Text Checker', 'ultimakit-for-wp' );
		$this->description = __( 'This tool helps you find and fix images missing alt text on your website. Alt text is important for accessibility and SEO.', 'ultimakit-for-wp' );
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

			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
			add_action( 'admin_menu', array( $this, 'add_submenu_page' ), 20 );
			add_action( 'wp_ajax_scan_missing_alt_text', array( $this, 'scan_missing_alt_text' ) );
			add_action( 'wp_ajax_update_alt_text', array( $this, 'update_alt_text' ) );
		}
	}

	/**
	 * Add submenu page
	 */
	public function add_submenu_page() {
		// Check if parent menu exists
		global $submenu;
		if ( ! isset( $submenu['wp-ultimakit-dashboard'] ) ) {
			return;
		}

		add_submenu_page(
			'wp-ultimakit-dashboard',
			__( 'Image Alt Text Checker & Bulk Update', 'ultimakit-for-wp' ),
			__( 'Image Alt Text Checker & Bulk Update', 'ultimakit-for-wp' ),
			'manage_options',
			'wp-ultimakit-alt-checker',
			array( $this, 'render_page' )
		);
	}

	/**
	 * Enqueue admin assets.
	 *
	 * @param string $hook Current admin page.
	 */
	public function enqueue_admin_assets( $hook ) {

		wp_enqueue_script( 'jquery' );

		wp_enqueue_style(
			'ultimakit-alt-text-checker',
			plugin_dir_url( __FILE__ ) . 'module-css.css',
			array(),
			'1.0.0'
		);

		wp_enqueue_script(
			'ultimakit-alt-text-checker',
			plugin_dir_url( __FILE__ ) . 'module-script.js',
			array( 'jquery' ),
			'1.0.0',
			true
		);

		wp_localize_script(
			'ultimakit-alt-text-checker',
			'ultimakit_alt_text_checker',
			array(
				'url'             => admin_url( 'admin-ajax.php' ),
				'scan_alt_text'   => wp_create_nonce( 'scan_alt_text' ),
				'update_alt_text' => wp_create_nonce( 'update_alt_text' ),
				// Added strings
				'scanning'        => __( 'Scanning...', 'ultimakit-for-wp' ),
				'scan_button'     => __( 'Scan for Missing Alt Text', 'ultimakit-for-wp' ),
				'scan_error'      => __( 'An error occurred while scanning images.', 'ultimakit-for-wp' ),
				'enter_alt_text'  => __( 'Please enter alt text before saving.', 'ultimakit-for-wp' ),
				'saving'          => __( 'Saving...', 'ultimakit-for-wp' ),
				'save'            => __( 'Save', 'ultimakit-for-wp' ),
				'updated'         => __( 'Updated!', 'ultimakit-for-wp' ),
				'error'           => __( 'Error!', 'ultimakit-for-wp' ),
			)
		);
	}

	public function render_page() {
		$object = new UltimaKit_Helpers();
		?>
		<div class="wrap">
			<?php $object->ultimakit_get_header(); ?>
			<div class="container bg-white text-dark p-3 mb-3">
				<!-- Nav tabs -->
				<ul class="nav nav-tabs" id="wpukTabs" role="tablist">
					<li class="nav-item" role="presentation">
						<a class="nav-link active" id="settings-tab" data-bs-toggle="tab" href="#alt-text-checked-settings" role="tab" aria-controls="alt-text-checked-settings" aria-selected="true"><?php echo esc_html_e( 'Alt Text Checker', 'ultimakit-for-wp' ); ?></a>
					</li>
					
				</ul>

				<!-- Tab panes -->
				<div class="tab-content" id="wpukTabsContent">
					<div class="tab-pane fade show active" id="alt-text-checked-settings" role="tabpanel" aria-labelledby="settings-tab">
						<!-- Your modules content here -->
						<div class="row">
							
							<div class="notice notice-info">
								<p><?php _e( 'This tool helps you find and fix images missing alt text on your website. Alt text is important for accessibility and SEO.', 'ultimakit-for-wp' ); ?></p>
							</div>

							<button id="scan-images" class="btn btn-success bg-primary" style="width: 250px;"><?php _e( 'Scan for Missing Alt Text', 'ultimakit-for-wp' ); ?></button>
							<div id="results-container" style="margin-top: 20px;"></div>
						
						</div>
					</div>

				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Scan for images missing alt text
	 */
	public function scan_missing_alt_text() {
		check_ajax_referer( 'scan_alt_text', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'You do not have permission to perform this action.', 'ultimakit-for-wp' ),
					'html'    => '<div class="notice notice-error"><p>' . __( 'Error: Insufficient permissions to perform this action. Please contact your administrator.', 'ultimakit-for-wp' ) . '</p></div>',
				)
			);
		}

		$current_page = isset( $_POST['page'] ) ? absint( $_POST['page'] ) : 1;
		$offset       = ( $current_page - 1 ) * $this->items_per_page;

		$args = array(
			'post_type'      => 'attachment',
			'post_mime_type' => 'image',
			'posts_per_page' => $this->items_per_page,
			'offset'         => $offset,
			'meta_query'     => array(
				array(
					'key'     => '_wp_attachment_image_alt',
					'compare' => 'NOT EXISTS',
				),
			),
		);

		// Get total count for pagination
		$total_args                   = $args;
		$total_args['posts_per_page'] = -1;
		$total_args['fields']         = 'ids';
		$total_images                 = count( get_posts( $total_args ) );

		if ( $total_images > 0 ) {
			$total_pages = ceil( $total_images / $this->items_per_page );
		} else {
			$total_pages = 1;
		}

		$images = get_posts( $args );

		if ( empty( $images ) ) {
			echo '<div class="notice notice-success"><p>' . __( 'Great job! No images found missing alt text.', 'ultimakit-for-wp' ) . '</p></div>';
			wp_die();
		}

		echo '<div class="notice notice-warning"><p>' . sprintf( __( 'Found %s images missing alt text.', 'ultimakit-for-wp' ), $total_images ) . '</p></div>';

		echo '<table class="table table-striped table-bordered">';
		echo '<thead><tr>';
		echo '<th style="width: 150px;">' . __( 'Image', 'ultimakit-for-wp' ) . '</th>';
		echo '<th>' . __( 'Alt Text', 'ultimakit-for-wp' ) . '</th>';
		echo '<th style="width: 100px;">' . __( 'Action', 'ultimakit-for-wp' ) . '</th>';
		echo '<th style="width: 100px;">' . __( 'Status', 'ultimakit-for-wp' ) . '</th>';
		echo '</tr></thead>';

		foreach ( $images as $image ) {
			$image_url = wp_get_attachment_image_url( $image->ID, 'thumbnail' );
			$post_url  = get_edit_post_link( $image->ID );

			echo '<tr>';
			echo '<td>';
			echo '<img src="' . esc_url( $image_url ) . '" style="max-width: 100px;"><br>';
			echo '<a href="' . esc_url( $post_url ) . '" target="_blank">' . __( 'Edit Image', 'ultimakit-for-wp' ) . '</a>';
			echo '</td>';
			echo '<td><input type="text" id="alt-text-' . $image->ID . '" class="widefat" placeholder="' . __( 'Enter alt text...', 'ultimakit-for-wp' ) . '"></td>';
			echo '<td><button class="save-alt-text btn btn-success bg-primary" data-image-id="' . $image->ID . '">' . __( 'Save', 'ultimakit-for-wp' ) . '</button></td>';
			echo '<td><span id="status-' . $image->ID . '">' . __( 'Not updated', 'ultimakit-for-wp' ) . '</span></td>';
			echo '</tr>';
		}

		echo '</table>';

		if ( $total_pages > 1 ) {
			echo '<div class="tablenav bottom">';
				echo '<div class="tablenav-pages">';
				echo '<span class="displaying-num">' . sprintf( __( '%s items', 'ultimakit-for-wp' ), $total_images ) . '</span>';

			if ( $current_page > 1 ) {
				echo '<a class="prev-page button" href="#" data-page="' . ( $current_page - 1 ) . '">' . __( '‹', 'ultimakit-for-wp' ) . '</a>';
			}

				echo '<span class="paging-input">';
				echo $current_page . ' of ' . $total_pages;
				echo '</span>';

			if ( $current_page < $total_pages ) {
				echo '<a class="next-page button" href="#" data-page="' . ( $current_page + 1 ) . '">' . __( '›', 'ultimakit-for-wp' ) . '</a>';
			}

				echo '</div>';
			echo '</div>';
		}

		wp_die();
	}

	/**
	 * Update alt text for an image
	 */
	public function update_alt_text() {
		check_ajax_referer( 'update_alt_text', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Insufficient permissions', 'ultimakit-for-wp' ) );
		}

		$image_id = intval( $_POST['image_id'] );
		$alt_text = sanitize_text_field( $_POST['alt_text'] );

		if ( empty( $alt_text ) ) {
			wp_send_json_error( __( 'Alt text cannot be empty', 'ultimakit-for-wp' ) );
		}

		update_post_meta( $image_id, '_wp_attachment_image_alt', $alt_text );

		wp_send_json_success(
			array(
				'message' => __( 'Alt text updated successfully', 'ultimakit-for-wp' ),
			)
		);
	}
}
