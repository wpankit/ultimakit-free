<?php

class UltimaKit_Helpers {

	/**
	 * Per-request cache of the entire module settings table.
	 *
	 * The settings table is small (a few hundred rows at most) but was previously read
	 * with one query per module per lookup, which meant hundreds of queries on every
	 * request. It is now read once and served from memory.
	 *
	 * @var array|null
	 */
	protected static $ultimakit_settings_cache = null;

	public function __construct() {
	}

	/**
	 * Read every module setting using a single query.
	 *
	 * @return array {
	 *     @type array $values   [ module_name ][ setting_key ] => raw (still serialized) value.
	 *     @type array $autoload Same shape, limited to rows flagged autoload.
	 * }
	 */
	public static function ultimakit_get_all_settings() {
		if ( null !== self::$ultimakit_settings_cache ) {
			return self::$ultimakit_settings_cache;
		}

		$cached = wp_cache_get( 'ultimakit_all_settings', 'ultimakit' );
		if ( is_array( $cached ) ) {
			self::$ultimakit_settings_cache = $cached;
			return $cached;
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'ultimakit_module_settings';

		$settings = array(
			'values'   => array(),
			'autoload' => array(),
		);

		$rows = $wpdb->get_results( "SELECT module_name, setting_key, setting_value, autoload FROM {$table_name}", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		if ( $rows ) {
			foreach ( $rows as $row ) {
				$settings['values'][ $row['module_name'] ][ $row['setting_key'] ] = $row['setting_value'];

				if ( ! empty( $row['autoload'] ) ) {
					$settings['autoload'][ $row['module_name'] ][ $row['setting_key'] ] = $row['setting_value'];
				}
			}
		}

		self::$ultimakit_settings_cache = $settings;
		wp_cache_set( 'ultimakit_all_settings', $settings, 'ultimakit' );

		return $settings;
	}

	/**
	 * Drop the settings cache after any write.
	 */
	public static function ultimakit_flush_settings_cache() {
		self::$ultimakit_settings_cache = null;
		wp_cache_delete( 'ultimakit_all_settings', 'ultimakit' );
		wp_cache_delete( 'ultimakit_autoload_settings', 'ultimakit' );
	}

	public function ultimakit_asset_condition() {
		if ( isset( $_GET['page'] ) && in_array( $_GET['page'], ULTIMAKIT_FOR_WP_ALLOWED_PAGES ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Retrieves or displays the custom header content for the theme.
	 *
	 * This function is a custom implementation for fetching or rendering the header content.
	 * It can be used to include custom header templates or dynamic header elements specific
	 * to the theme or plugin. The function can be tailored to support different header styles
	 * or configurations based on context or preferences set in the theme options.
	 */
	public function ultimakit_get_header() {
		?>
		<div class="ultimakit-header">
			<div class="header-brand">
				<img src="<?php echo esc_url( ULTIMAKIT_FOR_WP_LOGO ); ?>" alt="UltimaKit Logo" class="logo">
				<div class="version-info"><?php echo esc_html_e( 'Current version:', 'ultimakit-for-wp' ); ?> <?php echo esc_html_e( ULTIMAKIT_FOR_WP_VERSION ); ?></div>
			</div>
			
			<div class="header-actions">
				<a class="btn btn-primary" target="_blank" rel="noopener noreferrer" href="https://wordpress.org/support/plugin/ultimakit-for-wp/">
					<?php esc_html_e( 'Get support', 'ultimakit-for-wp' ); ?>
				</a>
			</div>
		</div>
		<?php
	}

	public function ultimakit_generate_form( $args = array(), $modal_type = '' ) {
		$modal_title   = sanitize_text_field( $args['title'] );
		$fields        = $args['fields'];
		$custom_option = ( isset( $args['custom_option'] ) ) ? $args['custom_option'] : '';
		?>
		<!-- Modal -->
		<div class="wpuk_modal " id="<?php echo esc_attr( $this->ID ); ?>_modal" tabindex="-1" aria-labelledby="<?php echo esc_attr( $this->ID ); ?>_modal" aria-hidden="true">
			<div class="<?php echo esc_attr( $modal_type ); ?>">
				<div class="">
					<div class="modal-body">
						<form id="<?php echo esc_attr( $args['ID'] ); ?>_form" class="module_settings" method="post">
							<input type="hidden" id="module_id" value="<?php echo esc_attr( $args['ID'] ); ?>">
						<?php $this->ultimakit_generate_fields( $fields ); ?>
					</div>
					<div class="modal-footer">
						<!-- <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php esc_html_e( 'Reset', 'ultimakit-for-wp' ); ?></button> -->
						<button type="submit" class="btn btn-primary wpuk_save_module_settings" custom-option="<?php echo esc_attr( $custom_option ); ?>" id="<?php echo esc_attr( $this->ID ); ?>_form"><?php esc_html_e( 'Save changes', 'ultimakit-for-wp' ); ?></button>

					</div>
					</form>
				</div>
			</div>
		</div>
		<?php
	}

	public function ultimakit_generate_modal( $args = array(), $modal_type = '' ) {
		$modal_title = sanitize_text_field( $args['title'] );
		$fields      = $args['fields'];
		$close_btn   = ( isset( $args['close_btn'] ) ) ? sanitize_text_field( $args['close_btn'] ) : __( 'Close', 'ultimakit-for-wp' );
		$save_btn    = ( isset( $args['save_btn'] ) ) ? sanitize_text_field( $args['save_btn'] ) : __( 'Save changes', 'ultimakit-for-wp' );

		if ( ! $this->ultimakit_asset_condition() ) {
			return;
		}
		?>
		<!-- Modal -->
		<div class="wpuk_modal modal modal-lg fade" id="<?php echo esc_attr( $args['ID'] ); ?>_modal" tabindex="-1" aria-labelledby="<?php echo esc_attr( $this->ID ); ?>_modal" aria-hidden="true">
			<div class="modal-dialog modal-dialog-centered <?php echo esc_html_e( $modal_type ); ?>">
				<div class="modal-content">
					<div class="modal-header">
						<h5 class="modal-title"><?php echo esc_html( $modal_title ); ?></h5>
					</div>
					<div class="modal-body">
						<form id="<?php echo esc_attr( $args['ID'] ); ?>_form" class="module_settings" method="post">
							<input type="hidden" id="module_id" value="<?php echo esc_attr( $args['ID'] ); ?>">
							<?php $this->ultimakit_generate_fields( $fields ); ?>
					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo esc_html( $close_btn ); ?></button>
						<button type="submit" class="btn btn-primary wpuk_save_module_settings <?php echo esc_attr( $this->ID ); ?>_form" id="<?php echo esc_attr( $this->ID ); ?>_form"><?php echo esc_html( $save_btn ); ?></button>

					</div>
					</form>
				</div>
			</div>
		</div>
		<?php
	}

	public function ultimakit_generate_fields( $fields ) {

		if ( ! empty( $fields ) ) {
			echo '<ul>';
			foreach ( $fields as $key => $value ) {
				echo '<li>';
				switch ( $value['type'] ) {

					case 'text':
						echo '<label for="' . esc_attr( $key ) . '">' . esc_html( $value['label'] ) . '</label><br />';
						$placeholder = isset( $value['placeholder'] ) ? esc_attr( $value['placeholder'] ) : '';
						$required    = isset( $value['required'] ) ? 'required' : '';
						$valueAttr   = isset( $value['value'] ) ? esc_attr( $value['value'] ) : '';

						echo '<input type="text" ' . $required . ' id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '" value="' . $valueAttr . '" placeholder="' . $placeholder . '">';

						if ( ! empty( $value['desc'] ) ) {
							echo '<br /><small>' . wp_kses_post( $value['desc'] ) . '</small>';
						}
						break;
					case 'number':
							echo '<label for="' . esc_attr( $key ) . '">' . esc_html( $value['label'] ) . '</label><br />';
							$placeholder = isset( $value['placeholder'] ) ? esc_attr( $value['placeholder'] ) : '';
							$required    = isset( $value['required'] ) ? 'required' : '';
							$valueAttr   = isset( $value['value'] ) ? esc_attr( $value['value'] ) : '';

							echo '<input type="number" ' . $required . ' id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '" value="' . $valueAttr . '" placeholder="' . $placeholder . '">';

						if ( ! empty( $value['desc'] ) ) {
							echo '<br /><small>' . wp_kses_post( $value['desc'] ) . '</small>';
						}
						break;
					case 'color':
						echo '<label for="' . esc_attr( $key ) . '">' . esc_html( $value['label'] ) . '</label><br />';
						echo '<input type="text" id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '" class="ultimakit-color-picker" value="' . esc_attr( $value['value'] ) . '">';
						if ( ! empty( $value['desc'] ) ) {
							echo '<br /><small>' . wp_kses_post( $value['desc'] ) . '</small>';
						}
						break;
					case 'hidden':
						echo '<input type="hidden" id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '" value="' . esc_attr( $value['value'] ) . '">';
						break;
					case 'textarea':
						echo '<label for="' . esc_attr( $key ) . '">' . esc_html( $value['label'] ) . '</label><br />';
						echo '<textarea rows="5" id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '">' . esc_attr( $value['value'] ) . '</textarea>';
						if ( ! empty( $value['desc'] ) ) {
							echo '<br /><small>' . wp_kses_post( $value['desc'] ) . '</small>';
						}
						break;
					case 'textarea2':
						echo '<label for="' . esc_attr( $key ) . '">' . esc_html( $value['label'] ) . '</label><br />';
						echo '<textarea rows="5" id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '">' . esc_attr( $value['value'] ) . '</textarea>';
						if ( ! empty( $value['desc'] ) ) {
							echo '<br /><small>' . wp_kses_post( $value['desc'] ) . '</small>';
						}
						break;
					case 'password':
						echo '<label for="' . esc_attr( $key ) . '">' . esc_html( $value['label'] ) . '</label><br />';
						echo '<input type="password" id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '" value="' . esc_attr( $value['value'] ) . '">';
						if ( ! empty( $value['desc'] ) ) {
							echo '<br /><small>' . wp_kses_post( $value['desc'] ) . '</small>';
						}
						break;
					case 'checkbox':
						$checked = ( 'on' === $value['value'] ) ? 'checked' : '';
						echo '<input ' . esc_attr( $checked ) . ' type="checkbox" id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '" value="' . esc_attr( $value['value'] ) . '"> <label for="' . esc_attr( $key ) . '">' . esc_html( $value['label'] ) . '</label>';
						if ( ! empty( $value['desc'] ) ) {
							echo '<br /><small>' . wp_kses_post( $value['desc'] ) . '</small>';
						}
						break;
					case 'switch':
						$checked = ( 'on' === $value['value'] ) ? 'checked' : '';
						echo '<div class="form-check form-switch module-switch"><input ' . esc_attr( $checked ) . ' class="form-check-input" type="checkbox" id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '" value="' . esc_attr( $value['value'] ) . '"> <label class="form-check-label switch-label" for="' . esc_attr( $key ) . '">toggle me</label>' . esc_html( $value['label'] );
						if ( ! empty( $value['desc'] ) ) {
							echo '<br /><small>' . wp_kses_post( $value['desc'] ) . '</small>';
						}
						echo '</div>';

						break;
					case 'radio':
						$checked = ( 'on' === $value['value'] ) ? 'checked="checked"' : '';
						echo '<input ' . esc_attr( $checked ) . ' type="radio" name="' . esc_attr( $key ) . '"> <label for="' . esc_attr( $key ) . '"> ' . esc_html( $value['label'] ) . '</label>';

						break;
					case 'select':
						echo '<label for="' . esc_attr( $key ) . '">' . esc_html( $value['label'] ) . '</label><br/>';
						echo '<select name="' . esc_attr( $key ) . '" id="' . esc_attr( $key ) . '">';
						if ( ! empty( $value['options'] ) ) {
							foreach ( $value['options'] as $op_key => $op_value ) {
								// Check if 'default' exists in $value, if not, set an empty string or a fallback value
								$default_value = isset( $value['default'] ) ? $value['default'] : '';

								// Use the $default_value for comparison
								$selected = selected( $op_key, $default_value, false );

								echo '<option ' . esc_attr( $selected ) . ' value="' . esc_attr( $op_key ) . '">' . esc_html( $op_value ) . '</option>';
							}
						}

						echo '</select>';
						if ( ! empty( $value['desc'] ) ) {
							echo '<br /><small>' . wp_kses_post( $value['desc'] ) . '</small>';
						}

						break;

					case 'select2':
						echo '<label for="' . esc_attr( $key ) . '">' . esc_html( $value['label'] ) . '</label><br/>';
						echo '<select name="' . esc_attr( $key ) . '" id="' . esc_attr( $key ) . '" class="form-control select2" multiple="multiple">';
						if ( ! empty( $value['options'] ) ) {
							foreach ( $value['options'] as $op_key => $op_value ) {
								$selected = selected( $op_key, $value['default'], false );
								echo '<option ' . esc_attr( $selected ) . ' value="' . esc_attr( $op_key ) . '">' . esc_html( $op_value ) . '</option>';
							}
						}
						echo '</select>';
						if ( ! empty( $value['desc'] ) ) {
							echo '<br /><small>' . wp_kses_post( $value['desc'] ) . '</small>';
						}

						break;

					case 'html':
						/*
						 * Modules build these fields from their own markup: Hide Admin Notices'
						 * per-role toggle grid, and the image pickers in Scroll To Top, Login Logo
						 * Customizer and Maintenance Mode. The old list stripped div, h6, img,
						 * input and label, and button ids, so those screens rendered as bare text
						 * with dead upload buttons. kses still drops every attribute not listed.
						 */
						echo wp_kses($value['value'], array(
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
							'h6' => array(),
							'span' => array(
								'class' => array()
							),
							'br' => array(),
							'div' => array(
								'class' => array(),
								'id' => array(),
								'data-role' => array(),
							),
							'img' => array(
								'src' => array(),
								'id' => array(),
								'class' => array(),
								'width' => array(),
								'height' => array(),
								'alt' => array(),
							),
							'input' => array(
								'type' => array(),
								'id' => array(),
								'class' => array(),
								'name' => array(),
								'value' => array(),
								'style' => array(),
								'placeholder' => array(),
								'checked' => array(),
							),
							'label' => array(
								'for' => array(),
								'class' => array(),
							),
							'button' => array(
								'href' => array(),
								'title' => array(),
								'target' => array(),
								'class' => array(),
								'id' => array(),
								'type' => array(),
							)
						));
						break;
				}
				echo '</li>';
			}
			echo '</ul>';
		}
	}
	public function show_featured_image_column() {
		$post_types = get_post_types(
			array(
				'public' => true,
			),
			'names'
		);
		foreach ( $post_types as $post_type_key => $post_type_name ) {
			if ( post_type_supports( $post_type_key, 'thumbnail' ) ) {
				add_filter( "manage_{$post_type_name}_posts_columns", array( $this, 'add_featured_image_column' ), 999 );
				add_action(
					"manage_{$post_type_name}_posts_custom_column",
					array( $this, 'add_featured_image' ),
					10,
					2
				);
			}
		}
	}

	public function add_featured_image_column( $columns ) {
		$new_columns = array();
		foreach ( $columns as $key => $value ) {
			if ( 'title' == $key ) {
				// We add featured image column before the 'title' column
				$new_columns['wpuk-featured-image'] = 'Featured Image';
			}
			if ( 'thumb' == $key ) {
				// For WooCommerce products, we add featured image column before it's native thumbnail column
				$new_columns['wpuk-featured-image'] = 'Product Image';
			}
			$new_columns[ $key ] = $value;
		}
		// Replace WooCommerce thumbnail column with ASE featured image column
		if ( array_key_exists( 'thumb', $new_columns ) ) {
			unset( $new_columns['thumb'] );
		}
		return $new_columns;
	}

	public function add_featured_image( $column_name, $id ) {
		if ( 'wpuk-featured-image' === $column_name ) {

			if ( has_post_thumbnail( $id ) ) {
				$size = 'thumbnail';
				echo get_the_post_thumbnail( $id, $size, '' );
			} else {
				echo '<img src="' . esc_url( plugins_url( 'assets/img/default_featured_image.jpg', __DIR__ ) ) . '" />';
			}
		}
	}

	public function show_excerpt_column() {
		$post_types = get_post_types(
			array(
				'public' => true,
			),
			'names'
		);
		foreach ( $post_types as $post_type_key => $post_type_name ) {
			if ( post_type_supports( $post_type_key, 'excerpt' ) ) {
				add_filter( "manage_{$post_type_name}_posts_columns", array( $this, 'add_excerpt_column' ) );
				add_action(
					"manage_{$post_type_name}_posts_custom_column",
					array( $this, 'add_excerpt' ),
					10,
					2
				);
			}
		}
	}

	public function add_excerpt_column( $columns ) {
		$new_columns = array();
		foreach ( $columns as $key => $value ) {
			$new_columns[ $key ] = $value;
			if ( $key == 'title' ) {
				$new_columns['wpuk-excerpt'] = 'Excerpt';
			}
		}
		return $new_columns;
	}

	public function add_excerpt( $column_name, $id ) {

		if ( 'wpuk-excerpt' === $column_name ) {
			$excerpt = get_the_excerpt( $id );
			// about 310 characters
			$excerpt = substr( $excerpt, 0, 160 );
			// truncate to 160 characters
			$short_excerpt = substr( $excerpt, 0, strrpos( $excerpt, ' ' ) );
			echo wp_kses_post( $short_excerpt );
		}
	}

	public function show_id_column() {
		// For pages and hierarchical post types list table
		add_filter( 'manage_pages_columns', array( $this, 'add_id_column' ) );
		add_action(
			'manage_pages_custom_column',
			array( $this, 'add_id_echo_value' ),
			10,
			2
		);
		// For posts and non-hierarchical custom posts list table
		add_filter( 'manage_posts_columns', array( $this, 'add_id_column' ) );
		add_action(
			'manage_posts_custom_column',
			array( $this, 'add_id_echo_value' ),
			10,
			2
		);
		// For media list table
		add_filter( 'manage_media_columns', array( $this, 'add_id_column' ) );
		add_action(
			'manage_media_custom_column',
			array( $this, 'add_id_echo_value' ),
			10,
			2
		);
		// For list table of all taxonomies
		$taxonomies = get_taxonomies(
			array(
				'public' => true,
			),
			'names'
		);
		foreach ( $taxonomies as $taxonomy ) {
			add_filter( 'manage_edit-' . $taxonomy . '_columns', array( $this, 'add_id_column' ) );
			add_action(
				'manage_' . $taxonomy . '_custom_column',
				array( $this, 'add_id_return_value' ),
				10,
				3
			);
		}
		// For users list table
		add_filter( 'manage_users_columns', array( $this, 'add_id_column' ) );
		add_action(
			'manage_users_custom_column',
			array( $this, 'add_id_return_value' ),
			10,
			3
		);
		// For comments list table
		add_filter( 'manage_edit-comments_columns', array( $this, 'add_id_column' ) );
		add_action(
			'manage_comments_custom_column',
			array( $this, 'add_id_echo_value' ),
			10,
			3
		);
	}

	public function add_id_column( $columns ) {
		$columns['wpuk-id'] = 'ID';
		return $columns;
	}

	public function add_id_echo_value( $column_name, $id ) {
		if ( 'wpuk-id' === $column_name ) {
			echo esc_html( $id );
		}
	}

	public function show_id_in_action_row() {
		add_filter(
			'page_row_actions',
			array( $this, 'add_id_in_action_row' ),
			10,
			2
		);
		add_filter(
			'post_row_actions',
			array( $this, 'add_id_in_action_row' ),
			10,
			2
		);
		add_filter(
			'cat_row_actions',
			array( $this, 'add_id_in_action_row' ),
			10,
			2
		);
		add_filter(
			'tag_row_actions',
			array( $this, 'add_id_in_action_row' ),
			10,
			2
		);
		add_filter(
			'media_row_actions',
			array( $this, 'add_id_in_action_row' ),
			10,
			2
		);
		add_filter(
			'comment_row_actions',
			array( $this, 'add_id_in_action_row' ),
			10,
			2
		);
		add_filter(
			'user_row_actions',
			array( $this, 'add_id_in_action_row' ),
			10,
			2
		);
	}

	public function add_id_in_action_row( $actions, $object ) {
		if ( current_user_can( 'edit_posts' ) ) {
			// For pages, posts, custom post types, media/attachments, users
			if ( property_exists( $object, 'ID' ) ) {
				$id = $object->ID;
			}
			// For taxonomies
			if ( property_exists( $object, 'term_id' ) ) {
				$id = $object->term_id;
			}
			// For comments
			if ( property_exists( $object, 'comment_ID' ) ) {
				$id = $object->comment_ID;
			}
			$actions['wpuk-list-table-item-id'] = '<span class="wpuk-list-table-item-id">ID: ' . $id . '</span>';
		}
		return $actions;
	}

	public function show_custom_taxonomy_filters( $post_type ) {
		$post_taxonomies = get_object_taxonomies( $post_type, 'objects' );
		// Only show custom taxonomy filters for post types other than 'post'
		if ( 'post' != $post_type ) {
			array_walk( $post_taxonomies, array( $this, 'output_taxonomy_filter' ) );
		}
	}

	public function output_taxonomy_filter( $post_taxonomy ) {
		// Only show taxonomy filter when the taxonomy is hierarchical
		if ( true === $post_taxonomy->hierarchical ) {
			$get = ( isset( $_GET[ $post_taxonomy->query_var ] ) ) ? $_GET[ $post_taxonomy->query_var ] : '';
			wp_dropdown_categories(
				array(
					'show_option_all' => sprintf( 'All %s', $post_taxonomy->label ),
					'orderby'         => 'name',
					'order'           => 'ASC',
					'hide_empty'      => false,
					'hide_if_empty'   => true,
					'selected'        => sanitize_text_field( $get ),
					'hierarchical'    => true,
					'name'            => $post_taxonomy->query_var,
					'taxonomy'        => $post_taxonomy->name,
					'value_field'     => 'slug',
				)
			);
		}
	}

	public function hide_comments_column() {
		$post_types = get_post_types(
			array(
				'public' => true,
			),
			'names'
		);
		foreach ( $post_types as $post_type_key => $post_type_name ) {
			if ( post_type_supports( $post_type_key, 'comments' ) ) {
				if ( 'attachment' != $post_type_name ) {
					// For list tables of pages, posts and other post types
					add_filter( "manage_{$post_type_name}_posts_columns", array( $this, 'remove_comment_column' ) );
				} else {
					// For list table of media/attachment
					add_filter( 'manage_media_columns', array( $this, 'remove_comment_column' ) );
				}
			}
		}
	}

	public function remove_comment_column( $columns ) {
		unset( $columns['comments'] );
		return $columns;
	}

	public function hide_post_tags_column() {
		$post_types = get_post_types(
			array(
				'public' => true,
			),
			'names'
		);
		foreach ( $post_types as $post_type_key => $post_type_name ) {
			if ( $post_type_name == 'post' ) {
				add_filter( 'manage_posts_columns', array( $this, 'remove_post_tags_column' ) );
			}
		}
	}

	public function remove_post_tags_column( $columns ) {
		unset( $columns['tags'] );
		return $columns;
	}

	public function is_table_exists( $table_name ) {
		global $wpdb;
		$query = $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table_name ) );
		if ( $wpdb->get_var( $query ) == $table_name ) {
			return true;
		}
		return false;
	}

	/**
	 * Resolve the visitor's IP address.
	 *
	 * REMOTE_ADDR is the only value a client cannot forge. This previously preferred
	 * X-Forwarded-For and friends, which let an attacker send a different fake IP with every
	 * request and so never trip the brute-force lockout, and let them forge lockout records
	 * and audit-log entries against someone else.
	 *
	 * Sites genuinely behind a reverse proxy can opt back in to a specific header via the
	 * ultimakit_trusted_proxy_header filter, e.g. return 'HTTP_CF_CONNECTING_IP'. Only enable
	 * that when the proxy is one you control and it overwrites the header on every request.
	 *
	 * @return string
	 */
	public function ultimakit_get_the_user_ip() {
		$trusted_header = apply_filters( 'ultimakit_trusted_proxy_header', '' );

		if ( $trusted_header && ! empty( $_SERVER[ $trusted_header ] ) ) {
			$forwarded = explode( ',', sanitize_text_field( wp_unslash( $_SERVER[ $trusted_header ] ) ) );

			// Rightmost entry is the one appended by the nearest (trusted) proxy.
			$candidate = trim( (string) end( $forwarded ) );

			if ( filter_var( $candidate, FILTER_VALIDATE_IP ) ) {
				return $candidate;
			}
		}

		$remote_addr = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';

		if ( filter_var( $remote_addr, FILTER_VALIDATE_IP ) ) {
			return $remote_addr;
		}

		return 'UNKNOWN';
	}

	public function get_all_user_roles() {
		global $wp_roles;

		$roles = array();

		if ( ! isset( $wp_roles ) ) {
			$wp_roles = new WP_Roles();
		}

		foreach ( $wp_roles->roles as $role => $details ) {
			$roles[ $role ] = $details['name'];
		}

		return $roles;
	}

	public function is_woocommerce_active() {
		return class_exists( 'WooCommerce' );
	}


	public function get_module_block( $all_modules = array() ) {
		if ( ! empty( $all_modules ) ) {
			foreach ( $all_modules as $module ) {
				/*
				 * Sidebar filtering is done client-side against data-filter. Modules used to
				 * be rendered once per category into hidden duplicate grids, which doubled
				 * the DOM and produced duplicate element IDs for every module toggle.
				 *
				 * Gravity Forms modules form their own bucket; everything else is bucketed
				 * by category, matching what the sidebar offers.
				 */
				$filter_slug = ( 'Gravity Forms' === $module['type'] )
					? $this->string_to_slug( 'Gravity Forms' )
					: $this->string_to_slug( $module['category'] );
				?>
				<div class="module-block <?php echo esc_attr( $this->add_non_paying_classes( $module['plan'] ) ); ?> <?php echo esc_attr( $module['category'] ); ?> <?php echo esc_attr( $module['type'] ); ?> <?php echo ( true === $module['is_active'] ) ? 'active' : 'inactive'; ?> <?php echo esc_attr( $module['plan'] ); ?>-plan" data-filter="<?php echo esc_attr( $filter_slug ); ?>" data-category="<?php echo esc_attr( $this->string_to_slug( $module['category'] ) ); ?>" data-type="<?php echo esc_attr( $this->string_to_slug( $module['type'] ) ); ?>" data-plan="<?php echo esc_attr( $module['plan'] ); ?>">
					<!-- Module Title -->
					<h5 class="module-title"><?php echo esc_html( $module['name'] ); ?></h5>

					<!-- Module Description -->
					<p class="module-description"><?php echo esc_html( $module['description'] ); ?></p>

					<!-- Module Switch -->
					<div class="form-check form-switch module-switch">
						<input type="checkbox" class="form-check-input ultimakit_module_action" module-name="<?php echo esc_attr( $module['name'] ); ?>" id="<?php echo esc_attr( $module['id'] ); ?>" 
																														<?php
																														if ( true === $module['is_active'] ) {
																															echo 'checked'; }
																														?>
						>
						<label class="form-check-label switch-label" for="<?php echo esc_attr( $module['id'] ); ?>">Toggle me</label>
					</div>

					<!-- Settings Link -->
					<?php
					if ( isset( $module['settings'] ) && 'yes' == $module['settings'] ) {
						?>
							<a href="javascript:void()" class="
							<?php
							if ( ! $module['is_active'] ) {
								echo 'ultimakit_hide_settings '; }
							?>
							learn-more-link <?php echo esc_attr( $module['id'] ); ?>"><?php echo esc_html( __( 'Settings', 'ultimakit-for-wp' ) ); ?></a>
						<?php } ?>

					<!-- Module Badges -->
					<span class="plugin-badge"><?php echo esc_html( $module['type'] ); ?></span>
				</div>
				<?php
			}
		} else {
			return array();
		}
	}


	/**
	 * Extra classes for a module card based on its plan.
	 *
	 * Nothing is locked since 3.0.0, when every former Pro module became free, so this
	 * returns no classes. Kept because the module grid template still calls it.
	 *
	 * @param string $plan Module plan from metadata.json.
	 * @return string
	 */
	public function add_non_paying_classes( $plan = 'free' ) {
		return '';
	}

	public function woo_activation_check() {
		return is_plugin_active( 'woocommerce/woocommerce.php' );
	}

	/**
	 * Function to check migration status, create custom table, and migrate settings if needed.
	 */
	public function ultimakit_check_and_migrate_settings() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'ultimakit_module_settings';

		// Check if migration has already been performed
		$migration_completed = get_option( 'ultimakit_migration_completed' );
		if ( $migration_completed ) {
			return;
		}

		// Create the custom table if it does not exist
		if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) != $table_name ) {
			$charset_collate = $wpdb->get_charset_collate();

			$sql = "CREATE TABLE IF NOT EXISTS $table_name (
				id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
				module_name VARCHAR(255) NOT NULL,
				setting_key VARCHAR(255) NOT NULL,
				setting_value LONGTEXT,
				autoload BOOLEAN DEFAULT FALSE,
				UNIQUE KEY module_setting (module_name, setting_key)
			) $charset_collate;";

			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			dbDelta( $sql );
		}

		// Retrieve the old serialized settings option
		$old_settings = get_option( 'ultimakit_options' );

		if ( $old_settings && is_array( $old_settings ) ) {
			// Loop through each module's settings and insert into the custom table
			foreach ( $old_settings as $module_name => $settings ) {
				foreach ( $settings as $setting_key => $setting_value ) {
					// Insert each setting into the new custom settings table
					$autoload = ( $setting_key === 'enabled' ); // Set autoload true for "enabled" settings only
					$this->ultimakit_update_module_setting( $module_name, $setting_key, $setting_value, $autoload );
				}
			}
		}

		// Optionally delete the old serialized option to clean up the database
		delete_option( 'ultimakit_options' );

		// Mark migration as complete to prevent reruns
		return update_option( 'ultimakit_migration_completed', true );
	}

	/**
	 * Helper function to update module settings in the custom table.
	 */
	public function ultimakit_update_module_setting( $module_name, $setting_key, $setting_value, $autoload = false ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'ultimakit_module_settings';

		// Check if the setting already exists
		$exists = $wpdb->get_var(
			$wpdb->prepare( "SELECT COUNT(*) FROM $table_name WHERE module_name = %s AND setting_key = %s", $module_name, $setting_key )
		);

		if ( $exists ) {
			// Update existing setting
			$result = $wpdb->update(
				$table_name,
				array(
					'setting_value' => maybe_serialize( $setting_value ),
					'autoload'      => $autoload,
				),
				array(
					'module_name' => $module_name,
					'setting_key' => $setting_key,
				)
			);
		} else {
			// Insert new setting
			$result = $wpdb->insert(
				$table_name,
				array(
					'module_name'   => $module_name,
					'setting_key'   => $setting_key,
					'setting_value' => maybe_serialize( $setting_value ),
					'autoload'      => $autoload,
				)
			);
		}

		self::ultimakit_flush_settings_cache();

		return $result;
	}

	public function get_modules_count( $admin ) {
		return count( $admin->getAllModules() );
	}

	public function get_modules_count_by_category( $admin, $category, $types = array() ) {
		return count( $admin->getAllModulesByCategory( $category, $types ) );
	}

	/**
	 * Get countries array values.
	 *
	 * @return [type] [description]
	 */
	public function get_countries() {

		$countries = array(
			'AF' => 'Afghanistan (‫افغانستان‬‎)',
			'AX' => 'Åland Islands (Åland)',
			'AL' => 'Albania (Shqipëri)',
			'DZ' => 'Algeria (‫الجزائر‬‎)',
			'AS' => 'American Samoa',
			'AD' => 'Andorra',
			'AO' => 'Angola',
			'AI' => 'Anguilla',
			'AQ' => 'Antarctica',
			'AG' => 'Antigua and Barbuda',
			'AR' => 'Argentina',
			'AM' => 'Armenia (Հայաստան)',
			'AW' => 'Aruba',
			'AC' => 'Ascension Island',
			'AU' => 'Australia',
			'AT' => 'Austria (Österreich)',
			'AZ' => 'Azerbaijan (Azərbaycan)',
			'BS' => 'Bahamas',
			'BH' => 'Bahrain (‫البحرين‬‎)',
			'BD' => 'Bangladesh (বাংলাদেশ)',
			'BB' => 'Barbados',
			'BY' => 'Belarus (Беларусь)',
			'BE' => 'Belgium (België)',
			'BZ' => 'Belize',
			'BJ' => 'Benin (Bénin)',
			'BM' => 'Bermuda',
			'BT' => 'Bhutan (འབྲུག)',
			'BO' => 'Bolivia',
			'BA' => 'Bosnia and Herzegovina (Босна и Херцеговина)',
			'BW' => 'Botswana',
			'BV' => 'Bouvet Island',
			'BR' => 'Brazil (Brasil)',
			'IO' => 'British Indian Ocean Territory',
			'VG' => 'British Virgin Islands',
			'BN' => 'Brunei',
			'BG' => 'Bulgaria (България)',
			'BF' => 'Burkina Faso',
			'BI' => 'Burundi (Uburundi)',
			'KH' => 'Cambodia (កម្ពុជា)',
			'CM' => 'Cameroon (Cameroun)',
			'CA' => 'Canada',
			'IC' => 'Canary Islands (islas Canarias)',
			'CV' => 'Cape Verde (Kabu Verdi)',
			'BQ' => 'Caribbean Netherlands',
			'KY' => 'Cayman Islands',
			'CF' => 'Central African Republic (République centrafricaine)',
			'EA' => 'Ceuta and Melilla (Ceuta y Melilla)',
			'TD' => 'Chad (Tchad)',
			'CL' => 'Chile',
			'CN' => 'China (中国)',
			'CX' => 'Christmas Island',
			'CP' => 'Clipperton Island',
			'CC' => 'Cocos (Keeling) Islands (Kepulauan Cocos (Keeling))',
			'CO' => 'Colombia',
			'KM' => 'Comoros (‫جزر القمر‬‎)',
			'CD' => 'Congo (DRC) (Jamhuri ya Kidemokrasia ya Kongo)',
			'CG' => 'Congo (Republic) (Congo-Brazzaville)',
			'CK' => 'Cook Islands',
			'CR' => 'Costa Rica',
			'CI' => 'Côte d\'Ivoire',
			'HR' => 'Croatia (Hrvatska)',
			'CU' => 'Cuba',
			'CW' => 'Curaçao',
			'CY' => 'Cyprus (Κύπρος)',
			'CZ' => 'Czech Republic (Česká republika)',
			'DK' => 'Denmark (Danmark)',
			'DG' => 'Diego Garcia',
			'DJ' => 'Djibouti',
			'DM' => 'Dominica',
			'DO' => 'Dominican Republic (República Dominicana)',
			'EC' => 'Ecuador',
			'EG' => 'Egypt (‫مصر‬‎)',
			'SV' => 'El Salvador',
			'GQ' => 'Equatorial Guinea (Guinea Ecuatorial)',
			'ER' => 'Eritrea',
			'EE' => 'Estonia (Eesti)',
			'ET' => 'Ethiopia',
			'FK' => 'Falkland Islands (Islas Malvinas)',
			'FO' => 'Faroe Islands (Føroyar)',
			'FJ' => 'Fiji',
			'FI' => 'Finland (Suomi)',
			'FR' => 'France',
			'GF' => 'French Guiana (Guyane française)',
			'PF' => 'French Polynesia (Polynésie française)',
			'TF' => 'French Southern Territories (Terres australes françaises)',
			'GA' => 'Gabon',
			'GM' => 'Gambia',
			'GE' => 'Georgia (საქართველო)',
			'DE' => 'Germany (Deutschland)',
			'GH' => 'Ghana (Gaana)',
			'GI' => 'Gibraltar',
			'GR' => 'Greece (Ελλάδα)',
			'GL' => 'Greenland (Kalaallit Nunaat)',
			'GD' => 'Grenada',
			'GP' => 'Guadeloupe',
			'GU' => 'Guam',
			'GT' => 'Guatemala',
			'GG' => 'Guernsey',
			'GN' => 'Guinea (Guinée)',
			'GW' => 'Guinea-Bissau (Guiné Bissau)',
			'GY' => 'Guyana',
			'HT' => 'Haiti',
			'HM' => 'Heard & McDonald Islands',
			'HN' => 'Honduras',
			'HK' => 'Hong Kong (香港)',
			'HU' => 'Hungary (Magyarország)',
			'IS' => 'Iceland (Ísland)',
			'IN' => 'India (भारत)',
			'ID' => 'Indonesia',
			'IR' => 'Iran (‫ایران‬‎)',
			'IQ' => 'Iraq (‫العراق‬‎)',
			'IE' => 'Ireland',
			'IM' => 'Isle of Man',
			'IL' => 'Israel (‫תירבע‬‎)',
			'IT' => 'Italy (Italia)',
			'JM' => 'Jamaica',
			'JP' => 'Japan (日本)',
			'JE' => 'Jersey',
			'JO' => 'Jordan (‫الأردن‬‎)',
			'KZ' => 'Kazakhstan (Казахстан)',
			'KE' => 'Kenya',
			'KI' => 'Kiribati',
			'XK' => 'Kosovo (Kosovë)',
			'KW' => 'Kuwait (‫الكويت‬‎)',
			'KG' => 'Kyrgyzstan (Кыргызстан)',
			'LA' => 'Laos (ລາວ)',
			'LV' => 'Latvia (Latvija)',
			'LB' => 'Lebanon (‫لبنان‬‎)',
			'LS' => 'Lesotho',
			'LR' => 'Liberia',
			'LY' => 'Libya (‫ليبيا‬‎)',
			'LI' => 'Liechtenstein',
			'LT' => 'Lithuania (Lietuva)',
			'LU' => 'Luxembourg',
			'MO' => 'Macau (澳門)',
			'MK' => 'Macedonia (FYROM) (Македонија)',
			'MG' => 'Madagascar (Madagasikara)',
			'MW' => 'Malawi',
			'MY' => 'Malaysia',
			'MV' => 'Maldives',
			'ML' => 'Mali',
			'MT' => 'Malta',
			'MH' => 'Marshall Islands',
			'MQ' => 'Martinique',
			'MR' => 'Mauritania (‫موريتانيا‬‎)',
			'MU' => 'Mauritius (Moris)',
			'YT' => 'Mayotte',
			'MX' => 'Mexico (México)',
			'FM' => 'Micronesia',
			'MD' => 'Moldova (Republica Moldova)',
			'MC' => 'Monaco',
			'MN' => 'Mongolia (Монгол)',
			'ME' => 'Montenegro (Crna Gora)',
			'MS' => 'Montserrat',
			'MA' => 'Morocco (‫المغرب‬‎)',
			'MZ' => 'Mozambique (Moçambique)',
			'MM' => 'Myanmar (Burma) (မြန်မာ)',
			'NA' => 'Namibia (Namibië)',
			'NR' => 'Nauru',
			'NP' => 'Nepal (नेपाल)',
			'NL' => 'Netherlands (Nederland)',
			'NC' => 'New Caledonia (Nouvelle-Calédonie)',
			'NZ' => 'New Zealand',
			'NI' => 'Nicaragua',
			'NE' => 'Niger (Nijar)',
			'NG' => 'Nigeria',
			'NU' => 'Niue',
			'NF' => 'Norfolk Island',
			'MP' => 'Northern Mariana Islands',
			'KP' => 'North Korea (조선 민주주의 인민 공화국)',
			'NO' => 'Norway (Norge)',
			'OM' => 'Oman (‫عُمان‬‎)',
			'PK' => 'Pakistan (‫پاکستان‬‎)',
			'PW' => 'Palau',
			'PS' => 'Palestine (‫فلسطين‬‎)',
			'PA' => 'Panama (Panamá)',
			'PG' => 'Papua New Guinea',
			'PY' => 'Paraguay',
			'PE' => 'Peru (Perú)',
			'PH' => 'Philippines',
			'PN' => 'Pitcairn Islands',
			'PL' => 'Poland (Polska)',
			'PT' => 'Portugal',
			'PR' => 'Puerto Rico',
			'QA' => 'Qatar (‫قطر‬‎)',
			'RE' => 'Réunion (La Réunion)',
			'RO' => 'Romania (România)',
			'RU' => 'Russia (Россия)',
			'RW' => 'Rwanda',
			'BL' => 'Saint Barthélemy (Saint-Barthélemy)',
			'SH' => 'Saint Helena',
			'KN' => 'Saint Kitts and Nevis',
			'LC' => 'Saint Lucia',
			'MF' => 'Saint Martin (Saint-Martin (partie française))',
			'PM' => 'Saint Pierre and Miquelon (Saint-Pierre-et-Miquelon)',
			'WS' => 'Samoa',
			'SM' => 'San Marino',
			'ST' => 'São Tomé and Príncipe (São Tomé e Príncipe)',
			'SA' => 'Saudi Arabia (‫المملكة العربية السعودية‬‎)',
			'SN' => 'Senegal (Sénégal)',
			'RS' => 'Serbia (Србија)',
			'SC' => 'Seychelles',
			'SL' => 'Sierra Leone',
			'SG' => 'Singapore',
			'SX' => 'Sint Maarten',
			'SK' => 'Slovakia (Slovensko)',
			'SI' => 'Slovenia (Slovenija)',
			'SB' => 'Solomon Islands',
			'SO' => 'Somalia (Soomaaliya)',
			'ZA' => 'South Africa',
			'GS' => 'South Georgia & South Sandwich Islands',
			'KR' => 'South Korea (대한민국)',
			'SS' => 'South Sudan (‫جنوب السودان‬‎)',
			'ES' => 'Spain (España)',
			'LK' => 'Sri Lanka (ශ්‍රී ලංකාව)',
			'VC' => 'St. Vincent & Grenadines',
			'SD' => 'Sudan (‫السودان‬‎)',
			'SR' => 'Suriname',
			'SJ' => 'Svalbard and Jan Mayen (Svalbard og Jan Mayen)',
			'SZ' => 'Swaziland',
			'SE' => 'Sweden (Sverige)',
			'CH' => 'Switzerland (Schweiz)',
			'SY' => 'Syria (‫سوريا‬‎)',
			'TW' => 'Taiwan (台灣)',
			'TJ' => 'Tajikistan',
			'TZ' => 'Tanzania',
			'TH' => 'Thailand (ไทย)',
			'TL' => 'Timor-Leste',
			'TG' => 'Togo',
			'TK' => 'Tokelau',
			'TO' => 'Tonga',
			'TT' => 'Trinidad and Tobago',
			'TA' => 'Tristan da Cunha',
			'TN' => 'Tunisia (‫تونس‬‎)',
			'TR' => 'Turkey (Türkiye)',
			'TM' => 'Turkmenistan',
			'TC' => 'Turks and Caicos Islands',
			'TV' => 'Tuvalu',
			'UM' => 'U.S. Outlying Islands',
			'VI' => 'U.S. Virgin Islands',
			'UG' => 'Uganda',
			'UA' => 'Ukraine (Україна)',
			'AE' => 'United Arab Emirates (‫الإمارات العربية المتحدة‬‎)',
			'GB' => 'United Kingdom',
			'US' => 'United States',
			'UY' => 'Uruguay',
			'UZ' => 'Uzbekistan (Oʻzbekiston)',
			'VU' => 'Vanuatu',
			'VA' => 'Vatican City (Città del Vaticano)',
			'VE' => 'Venezuela',
			'VN' => 'Vietnam (Việt Nam)',
			'WF' => 'Wallis and Futuna',
			'EH' => 'Western Sahara (‫الصحراء الغربية‬‎)',
			'YE' => 'Yemen (‫اليمن‬‎)',
			'ZM' => 'Zambia',
			'ZW' => 'Zimbabwe',
		);

		return $countries;
	}

	/**
     * Check if table exists
     *
     * @return bool
     */
    public function table_exists($transient_key, $table_name) {
        global $wpdb;

        $table_exists = get_transient($transient_key);
        
        if ($table_exists === false) {
            $table_exists = $wpdb->get_var(
                $wpdb->prepare(
                    "SHOW TABLES LIKE %s",
                    $table_name
                )
            ) !== null;

            if ($table_exists) {
                set_transient($transient_key, true, DAY_IN_SECONDS);
            }
        }

        return (bool) $table_exists;
    }

	public function string_to_slug($string) {
		// Convert string to lowercase
		$string = strtolower($string);
		
		// Remove special characters and replace with spaces
		$string = preg_replace('/[^a-z0-9\s-]/', '', $string);
		
		// Replace multiple spaces and hyphens with a single underscore
		$string = preg_replace('/[\s-]+/', '_', $string);
		
		// Remove underscores from the beginning and end
		$string = trim($string, '_');
		
		return $string;
	}
	
}
