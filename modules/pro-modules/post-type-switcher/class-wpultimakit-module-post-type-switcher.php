<?php
/**
 * Class UltimaKit_Module_Post_Type_Switcher
 *
 * @since 1.0.0
 * @package    UltimaKit
 */

/**
 * Class UltimaKit_Module_Post_Type_Switcher
 *
 * @since 1.0.0
 */
class UltimaKit_Module_Post_Type_Switcher extends UltimaKit_Module_Manager {
	/**
	 * @var string
	 */
	protected $ID = 'ultimakit_module_post_type_switcher';

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
	protected $read_more_link = 'post-type-switcher-in-wordpress';

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
		$this->name        = __( 'Post Type Switcher', 'ultimakit-for-wp' );
		$this->description = __( 'Switch post type between available post types quickly and easily.', 'ultimakit-for-wp' );
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
			add_action( 'admin_head', array( $this, 'ultimakit_admin_head' ) );
			// Override
			add_action( 'wp_ajax_ultimakit_post_type_switcher', array( $this, 'ultimakit_post_type_switcher' ) );
			add_filter( 'wp_insert_attachment_data', array( $this, 'ultimakit_override_type' ), 10, 2 );
			add_filter( 'wp_insert_post_data', array( $this, 'ultimakit_override_type' ), 10, 2 );
			add_filter( 'add_meta_boxes', array( $this, 'ultimakit_global_notice_meta_box' ) );
			// Pass object into an action
			do_action( 'ultimakit_post_type_switcher', $this );
		}
	}


	public static function init() {
		static $instance = null;
		if ( is_null( $instance ) ) {
			$instance = new UltimaKit_Module_Post_Type_Switcher( get_called_class(), ULTIMAKIT_FOR_WP_VERSION );
		}
		return $instance;
	} // init

	public function ultimakit_global_notice_meta_box() {
		$exclude = array( 'attachment', 'elementor_library', 'elementor_library', 'e-landing-page' );
		$screens = get_post_types( array( 'public' => true ), 'objects' );
		foreach ( $screens as $posttype => $screen ) {
			if ( ! in_array( $screen->name, $exclude ) ) {
				add_meta_box(
					'ultimakit_ctp_switcher',
					__( 'Post Type Switcher', 'ultimakit-for-wp' ),
					array( $this, 'ultimakit_global_notice_meta_box_callback' ),
					$screen->name,
					'side',
					'high'
				);

			}
		}
	}
	public function ultimakit_global_notice_meta_box_callback() {
		// Post types
		$post_types = $this->get_post_types();
		$post_type  = get_post_type();
		$cpt_object = get_post_type_object( $post_type );
		$exclude    = array( 'attachment', 'elementor_library', 'elementor_library', 'e-landing-page' );
		// wpext if object does not exist or produces an error
		if ( empty( $cpt_object ) || is_wp_error( $cpt_object ) ) {
			return;
		}
		if ( ! in_array( $cpt_object, $post_types, true ) ) {
			$post_types[ $post_type ] = $cpt_object;
		}?>
	<div class="wpext-pub-section wpext-pub-section-last post-type-switcher">
		<label for="ultimakit_post_type"><?php esc_html_e( 'Post Type:', 'ultimakit-for-wp' ); ?></label>
		<label id="post-type-display"><?php echo esc_html( $cpt_object->labels->singular_name ); ?></label>

		<?php if ( current_user_can( $cpt_object->cap->publish_posts ) ) : ?>
			<label id="post-type-display">
			<a href="#" id="edit-post-type-switcher" class="hide-if-no-js"><?php esc_html_e( 'Edit', 'ultimakit-for-wp' ); ?></a> </label>
			<div id="post-type-select">
				<select name="ultimakit_post_type" id="ultimakit_post_type">
					<option value="#"><?php esc_html_e( ' Select Post Type ', 'ultimakit-for-wp' ); ?></option>
					<?php

					foreach ( $post_types as $_post_type => $cpt ) :
						if ( ! in_array( $cpt->name, $exclude ) && $cpt->name != $post_type ) {

							if ( ! current_user_can( $cpt->cap->publish_posts ) ) :
								continue;
							endif;

							?>
						<option value="<?php echo esc_attr( $cpt->name ); ?>" <?php selected( $post_type, $_post_type ); ?>>
							<?php echo esc_html( $cpt->labels->singular_name ); ?></option>
										<?php
						}
					endforeach;

					?>
				</select>
				<input type="hidden" id="ultimakit_post_id" name="ultimakit_post_id" value="<?php echo esc_html( isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0 ); ?>">
				<a href="#" id="save-post-type-switcher" class="button-primary  hide-if-no-js button"><?php esc_html_e( 'OK', 'ultimakit-for-wp' ); ?></a>
				<a href="#" id="cancel-post-type-switcher" class="hide-if-no-js"><?php esc_html_e( 'Cancel', 'ultimakit-for-wp' ); ?></a>
			</div>
			<?php

			wp_nonce_field( 'post-type-selector', 'wpext-nonce-select' );

			endif;

		?>
		</div>

		<?php
	}

	/**
	 * Handles an admin-ajax request to change post types.
	 *
	 * Note that these use $_GET values specifically, to avoid collisions with
	 * upstream requests.
	 *
	 * @since 1.2.0
	 */
	public function ultimakit_post_type_switcher() {
		check_ajax_referer( 'ultimakit_post_type_switcher', 'nonce' );

		// wpext if missing data
		if (
				empty( $_POST['ultimakit_post_type'] )
			|| empty( $_POST['ultimakit_post_id'] )
		) {
			return wp_die( esc_html__( 'Missing data.', 'ultimakit-for-wp' ) );
		}
		// Post type information
		$post_id          = absint( $_POST['ultimakit_post_id'] );
		$post_type        = sanitize_key( $_POST['ultimakit_post_type'] );
		$post_type_object = get_post_type_object( $post_type );

		// An unknown post type would fatal on ->cap below.
		if ( ! $post_type_object ) {
			return wp_die( esc_html__( 'Sorry, you cannot do this.', 'ultimakit-for-wp' ) );
		}

		// Only allow the destination types the switcher dropdown offers (no internal types).
		if ( ! in_array( $post_type, $this->get_allowed_destination_types(), true ) ) {
			return wp_die( esc_html__( 'Sorry, you cannot do this.', 'ultimakit-for-wp' ) );
		}

		/*
		 * The capability check below only covers the DESTINATION post type. Without a check
		 * on the source object an author could convert any other author's post or page.
		 */
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return wp_die( esc_html__( 'Sorry, you cannot do this.', 'ultimakit-for-wp' ) );
		}

		// wpext if user isn't capable or nonce fails
		if ( ! current_user_can( $post_type_object->cap->publish_posts ) ) {
			return wp_die( esc_html__( 'Sorry, you cannot do this.', 'ultimakit-for-wp' ) );
		}

		// Update the post type
		set_post_type( $post_id, $post_type );
		get_edit_post_link( $post_id );
		exit;
	}
	/**
	 * Override post_type in wp_insert_post()
	 *
	 * - Not during autosave
	 * - Check nonce
	 * - Check user capabilities
	 * - Check $_POST input name
	 * - Check if revision or current post-type
	 * - Check new post-type exists
	 * - Check that user can publish posts of new type
	 *
	 * @since 1.2.0
	 *
	 * @param  array $data
	 * @param  array $postarr
	 *
	 * @return Maybe modified $data
	 */
	public function ultimakit_override_type( $data = array(), $postarr = array() ) {
		// wpext if form field is missing
		if ( empty( $_REQUEST['ultimakit_post_type'] ) || empty( $_REQUEST['wpext-nonce-select'] ) ) {
			return $data;
		}

		// wpext if no specific post ID is being saved
		if ( empty( $postarr['post_ID'] ) ) {
			return $data;
		}
		$post_id          = absint( $postarr['post_ID'] );
		$post_type        = sanitize_key( $_REQUEST['ultimakit_post_type'] );
		$post_type_object = get_post_type_object( $post_type );

		// wpext if empty post type
		if ( empty( $post_id ) || empty( $post_type ) || empty( $post_type_object ) ) {
			return $data;
		}

		// wpext if the new type is not one the switcher dropdown offers (no internal types)
		if ( ! in_array( $post_type, $this->get_allowed_destination_types(), true ) ) {
			return $data;
		}

		// wpext if no change
		if ( $post_type === $data['post_type'] ) {
			return $data;
		}
		if ( $post_id !== $postarr['ID'] ) {
			return $data;
		}

		// wpext if user cannot 'edit_post' on the current post ID
		if ( ! current_user_can( 'edit_post', $postarr['ID'] ) ) {
			return $data;
		}

		// wpext if user cannot 'publish_posts' on the new type
		if ( ! current_user_can( $post_type_object->cap->publish_posts ) ) {
			return $data;
		}

		// wpext if nonce is invalid
		if ( ! wp_verify_nonce( $_REQUEST['wpext-nonce-select'], 'post-type-selector' ) ) {
			return $data;
		}

		// wpext if autosave
		if ( wp_is_post_autosave( $postarr['ID'] ) ) {
			return $data;
		}

		// wpext if revision
		if ( wp_is_post_revision( $postarr['ID'] ) ) {
			return $data;
		}

		// wpext if it's a revision
		if ( in_array( $postarr['post_type'], array( $post_type, 'revision' ), true ) ) {
			return $data;
		}

		// Update post type
		$data['post_type'] = $post_type;

		// Return modified post data
		return $data;
	}
	/**
	 * Adds needed JS and CSS to admin header
	 *
	 * @since 1.2.0
	 *
	 * @return If on post-new.php
	 */
	public function ultimakit_admin_head() {
		?>
		<script type="text/javascript">
			var ultimakit_pts_obj = <?php echo wp_json_encode( array( 'ajax_url' => admin_url( 'admin-ajax.php' ), 'ajax_nonce' => wp_create_nonce( 'ultimakit_post_type_switcher' ) ) ); ?>;
			jQuery( document ).ready( function($) {
				jQuery( '.wpext-pub-section.curtime.wpext-pub-section-last' ).removeClass( 'wpext-pub-section-last' );
				jQuery( '#edit-post-type-switcher' ).on( 'click', function(e) {
					jQuery( this ).hide();
					jQuery( '#post-type-select' ).slideDown();
					e.preventDefault();
				});
				jQuery( '#save-post-type-switcher' ).on( 'click', function(e) {
					jQuery( '#post-type-select' ).slideUp();
					jQuery( '#edit-post-type-switcher' ).show();
					jQuery( '#post-type-display' ).text( jQuery( '#ultimakit_post_type :selected' ).text() );
					// alert(jQuery( '#ultimakit_post_type').val());
					var ultimakit_post_type = jQuery( '#ultimakit_post_type').val();
					var ultimakit_post_id = jQuery( '#ultimakit_post_id').val();
					/*Ajax Start*/
					jQuery.ajax({
						url:ultimakit_pts_obj.ajax_url,
						type: 'post',
						data: {
						'action': 'ultimakit_post_type_switcher',
						'ultimakit_post_type': ultimakit_post_type,
						'ultimakit_post_id': ultimakit_post_id,
						'nonce' : ultimakit_pts_obj.ajax_nonce
					},
						success: function (response) {
							// window.location.replace(response);
							if(response != null){
							location.reload();
							}
						}
						});
					e.preventDefault();
				});
				jQuery( '#cancel-post-type-switcher' ).on( 'click', function(e) {
					jQuery( '#post-type-select' ).slideUp();
					jQuery( '#edit-post-type-switcher' ).show();
					e.preventDefault();
				});

			});
		</script>
		<style type="text/css">
			div#ultimakit_ctp_switcher .inside {
				padding-right: 10px;
			}
			#wpbody-content .inline-edit-row .inline-edit-col-right .alignleft + .alignleft {
				float: right;
			}
			#post-type-select {
				line-height: 2.5em;
				margin-top: 3px;
				display: none;
			}
			#post-type-select select#ultimakit_post_type {
				margin-right: 2px;
			}
			#post-type-select a#save-post-type-switcher {
				vertical-align: middle;
				margin-right: 2px;
			}
			#post-type-display {
				font-weight: bold;
			}
			.wp-list-table .column-post_type {
				width: 10%;
			}
		</style>

		<?php
	}
	/**
	 * Get switchable post type objects, based on post-type arguments.
	 *
	 * @since 1.2.0
	 *
	 * @param string $output objects|names
	 * @return array
	 */
	private function get_post_types( $output = 'objects' ) {
		// Get switchable types
		$types = get_post_types( $this->get_post_type_args(), $output );
		// Unset attachment types, since support seems to be broken
		if ( isset( $types['attachment'] ) ) {
			unset( $types['attachment'] );
		}
		// Return switchable types
		return $types;
	}

	/**
	 * Post type names the switcher dropdown offers as a destination.
	 *
	 * @return array
	 */
	private function get_allowed_destination_types() {
		$exclude = array( 'attachment', 'elementor_library', 'e-landing-page' );
		return array_values( array_diff( array_keys( $this->get_post_types( 'names' ) ), $exclude ) );
	}

	/**
	 * Returns the array of arguments used to narrow down the switchable post
	 * types from the globally registered $wp_post_types array.
	 *
	 * @since 1.2.0
	 *
	 * @return array
	 */
	private function get_post_type_args() {
		return (array) apply_filters(
			'ultimakit_post_type_filter',
			array(
				'public'  => true,
				'show_ui' => true,
			)
		);
	}
}
