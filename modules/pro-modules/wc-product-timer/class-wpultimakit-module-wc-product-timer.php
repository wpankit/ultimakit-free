<?php
/**
 * Class UltimaKit_Module_Wc_Product_Timer
 *
 * @since 1.0.0
 * @package    UltimaKit
 */

/**
 * Class UltimaKit_Module_Wc_Product_Timer
 *
 * Module Version: 1.0.0
 *
 * This class provides methods to control the display of dashboard widgets based on user preferences.
 * It allows users to selectively hide certain dashboard widgets to streamline their WordPress dashboard
 * experience and improve usability.
 *
 * @since 1.0.0
 */
class UltimaKit_Module_Wc_Product_Timer extends UltimaKit_Module_Manager {
	/**
	 *
	 * @var string
	 */
	protected $ID = 'ultimakit_module_wc_product_timer';

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
	protected $category = 'WooCommerce';

	/**
	 * The type of module, indicating its platform or use case.
	 *
	 * @var string
	 */
	protected $type = 'WooCommerce';

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
	protected $read_more_link = 'set-product-timer-in-woocommerce';

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
		$this->name        = __( 'Woo - Product Timer', 'ultimakit-for-wp' );
		$this->description = __( 'Set a product timer in products. [Configure the timer from the product edit page.]', 'ultimakit-for-wp' );
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
			add_action( 'woocommerce_process_product_meta', array( $this, 'save_timer_display_location' ) );

			// Add a new tab to the product data tabs
			add_filter( 'woocommerce_product_data_tabs', array( $this, 'add_countdown_timer_tab' ) );

			// Display fields inside the new tab
			add_action( 'woocommerce_product_data_panels', array( $this, 'add_countdown_timer_fields' ) );

			// Save the countdown timer settings
			add_action( 'woocommerce_process_product_meta', array( $this, 'save_countdown_timer_fields' ) );

			// Apply the selected theme style and timer settings
			add_action( 'wp_enqueue_scripts', array( $this, 'apply_custom_timer_styles_and_label' ) );

			add_action( 'woocommerce_single_product_summary', array( $this, 'display_product_timer' ), 20 );
		}
	}

	public function save_timer_display_location( $post_id ) {
		if ( isset( $_POST['_timer_display_location'] ) ) {
			update_post_meta( $post_id, '_timer_display_location', sanitize_text_field( $_POST['_timer_display_location'] ) );
		}
	}

	public function add_countdown_timer_tab( $tabs ) {
		$tabs['countdown_timer'] = array(
			'label'  => __( 'Countdown Timer', 'ultimakit-for-wp' ),
			'target' => 'countdown_timer_data',
			'class'  => array( 'show_if_simple', 'show_if_variable' ),
			'icon'   => 'dashicons-clock',
		);
		return $tabs;
	}

	public function add_countdown_timer_fields() {
		global $woocommerce, $post;

		echo '<div id="countdown_timer_data" class="panel woocommerce_options_panel">';

		echo '<div class="options_group">';

		// Start date field
		woocommerce_wp_text_input(
			array(
				'id'          => '_timer_start_time',
				'label'       => __( 'Timer Start Date', 'ultimakit-for-wp' ),
				'type'        => 'datetime-local',
				'description' => __( 'Set the start date and time for the countdown timer.', 'ultimakit-for-wp' ),
			)
		);

		// End date field
		woocommerce_wp_text_input(
			array(
				'id'          => '_timer_end_time',
				'label'       => __( 'Timer End Date', 'ultimakit-for-wp' ),
				'type'        => 'datetime-local',
				'description' => __( 'Set the end date and time for the countdown timer.', 'ultimakit-for-wp' ),
			)
		);

		// Timer text field
		woocommerce_wp_text_input(
			array(
				'id'          => '_timer_text',
				'label'       => __( 'Timer Label Text', 'ultimakit-for-wp' ),
				'value'       => __( 'Time left:', 'ultimakit-for-wp' ),
				'description' => __( 'Text that appears before the timer (e.g., "Time left:")', 'ultimakit-for-wp' ),
			)
		);

		// Expired Timer text field
		woocommerce_wp_text_input(
			array(
				'id'          => '_timer_expired_text',
				'label'       => __( 'Timer Expired Label Text', 'ultimakit-for-wp' ),
				'placeholder' => __( 'Offer expired!', 'ultimakit-for-wp' ),
				'description' => __( 'Text that appears when the timer is expired (e.g., "Offer expired!")', 'ultimakit-for-wp' ),
			)
		);

		// Font size field
		woocommerce_wp_text_input(
			array(
				'id'          => '_timer_font_size',
				'label'       => __( 'Font Size (px)', 'ultimakit-for-wp' ),
				'type'        => 'number',
				'placeholder' => '16',
				'description' => __( 'Set the font size for the timer text in pixels.', 'ultimakit-for-wp' ),
			)
		);

		// Text alignment dropdown
		woocommerce_wp_select(
			array(
				'id'          => '_timer_alignment',
				'label'       => __( 'Text Alignment', 'ultimakit-for-wp' ),
				'description' => __( 'Choose how the timer text is aligned.', 'ultimakit-for-wp' ),
				'options'     => array(
					'center' => __( 'Center', 'ultimakit-for-wp' ),
					'left'   => __( 'Left', 'ultimakit-for-wp' ),
					'right'  => __( 'Right', 'ultimakit-for-wp' ),
				),
			)
		);

		// Font size field
		woocommerce_wp_text_input(
			array(
				'id'          => '_timer_width',
				'label'       => __( 'Width (px)', 'ultimakit-for-wp' ),
				'type'        => 'number',
				'placeholder' => '250',
				'description' => __( 'Set the timer size for the timer width in pixels. (Keep blank for 100% width)', 'ultimakit-for-wp' ),
			)
		);

		// Theme style dropdown
		woocommerce_wp_select(
			array(
				'id'          => '_timer_theme_style',
				'label'       => __( 'Timer Theme Style', 'ultimakit-for-wp' ),
				'description' => __( 'Choose a style theme for the countdown timer.', 'ultimakit-for-wp' ),
				'options'     => array(
					'minimal'   => __( 'Minimalist', 'ultimakit-for-wp' ),
					'bold'      => __( 'Bold', 'ultimakit-for-wp' ),
					'elegant'   => __( 'Elegant', 'ultimakit-for-wp' ),
					'dark_mode' => __( 'Dark Mode', 'ultimakit-for-wp' ),
					'vintage'   => __( 'Vintage', 'ultimakit-for-wp' ),
					'glass'     => __( 'Glassmorphism', 'ultimakit-for-wp' ),
				),
			)
		);

		// Display location dropdown field
		woocommerce_wp_select(
			array(
				'id'          => '_timer_display_location',
				'label'       => __( 'Timer Display Location', 'ultimakit-for-wp' ),
				'description' => __( 'Choose where to display the countdown timer on the product page.', 'ultimakit-for-wp' ),
				'options'     => array(
					'before_add_to_cart' => __( 'Before Add to Cart Button', 'ultimakit-for-wp' ),
					'after_add_to_cart'  => __( 'After Add to Cart Button', 'ultimakit-for-wp' ),
				),
			)
		);

		echo '</div>';
		echo '</div>';
	}

	public function save_countdown_timer_fields( $post_id ) {
		$fields = array(
			'_timer_start_time',
			'_timer_end_time',
			'_timer_text',
			'_timer_font_size',
			'_timer_alignment',
			'_timer_theme_style',
			'_timer_display_location',
			'_timer_expired_text',
			'_timer_width',
		);

		foreach ( $fields as $field ) {
			if ( isset( $_POST[ $field ] ) ) {
				$value = sanitize_text_field( $_POST[ $field ] );

				// Alignment is printed into a <style> block, so only the three offered values.
				if ( '_timer_alignment' === $field && ! in_array( $value, array( 'left', 'center', 'right' ), true ) ) {
					$value = 'center';
				}

				update_post_meta( $post_id, $field, $value );
			}
		}
	}

	public function apply_custom_timer_styles_and_label() {
		// Hooked on every front-end request; bail if WooCommerce's conditionals are not loaded.
		if ( ! function_exists( 'is_product' ) ) {
			return;
		}

		if ( is_product() ) {
			global $post;

			// Get saved settings
			$theme_style        = get_post_meta( get_the_ID(), '_timer_theme_style', true );
			$timer_text         = get_post_meta( get_the_ID(), '_timer_text', true ) ?: '';
			$timer_expired_text = get_post_meta( get_the_ID(), '_timer_expired_text', true ) ?: __( 'Offer expired!', 'ultimakit-for-wp' );

			// These are printed into the <style> block below: sizes as whole numbers, alignment
			// whitelisted (the meta can also be written outside the product form, e.g. REST).
			$font_size = absint( get_post_meta( get_the_ID(), '_timer_font_size', true ) ) ?: '16';
			$alignment = get_post_meta( get_the_ID(), '_timer_alignment', true );
			$alignment = in_array( $alignment, array( 'left', 'center', 'right' ), true ) ? $alignment : 'center';
			// "?:" binds looser than ".", so a blank width used to print "width: px;" instead of 100%.
			$timer_width = absint( get_post_meta( get_the_ID(), '_timer_width', true ) );
			$timer_width = $timer_width ? $timer_width . 'px' : '100%';

			?>
			<style>
				/* Base styles for timer */
				#ultimakit-product-timer {
					text-align: <?php echo esc_attr( $alignment ); ?>;
					font-size: <?php echo esc_attr( $font_size ); ?>px;
					padding: 0px 10px;
					width: <?php echo esc_attr( $timer_width ); ?>;
					display: flex;
					flex-wrap: wrap; /* Allow content to wrap to next row */
					align-items: <?php echo esc_attr( $alignment ); ?>; /* Center items vertically */
					justify-content: <?php echo esc_attr( $alignment ); ?>; /* Center items horizontally */
					gap: 5px; /* Space between label and digits */
					margin-bottom: 10px;
				}
	
				/* Label div styling */
				#ultimakit-product-timer .ultimakit-timer-label {
					flex: 0 1 auto; /* Allow it to resize automatically */
					white-space: nowrap; /* Prevent breaking in the middle of words */
					width: auto; /* Ensure the width is based on content */
				}
	
				/* Time digits div styling */
				#ultimakit-product-timer .ultimakit-timer-digits {
					flex: 0 1 auto; /* Allow it to resize automatically */
					white-space: nowrap; /* Prevent breaking in the middle of digits */
					width: auto; /* Ensure the width is based on content */
				}
	
				#ultimakit-product-timer span {
					color: inherit;
				}
				
				/* Theme Styles */
				<?php if ( $theme_style === 'minimal' ) : ?>
					/* Minimalist theme */
					#ultimakit-product-timer {
						background-color: #fff;
						border: 1px solid #e0e0e0;
					}
				<?php elseif ( $theme_style === 'bold' ) : ?>
					/* Bold theme */
					#ultimakit-product-timer {
						background-color: #0073aa;
						color: #fff;
					}
				<?php elseif ( $theme_style === 'elegant' ) : ?>
					/* Elegant theme */
					#ultimakit-product-timer {
						background-color: #f0f4f8;
						border: 2px solid #0073aa;
					}
				<?php elseif ( $theme_style === 'dark_mode' ) : ?>
					/* Dark Mode theme */
					#ultimakit-product-timer {
						background-color: #333;
						color: #eee;
						border: 1px solid #555;
					}
				<?php elseif ( $theme_style === 'vintage' ) : ?>
					/* Vintage theme */
					#ultimakit-product-timer {
						background-color: #fff8e1;
						border: 3px double #c49b66;
					}
				<?php elseif ( $theme_style === 'glass' ) : ?>
					/* Glassmorphism theme */
					#ultimakit-product-timer {
						background: rgba(255, 255, 255, 0.8);
						backdrop-filter: blur(10px);
						border-radius: 10px;
					}
				<?php endif; ?>
			</style>
			
			<script>
				document.addEventListener('DOMContentLoaded', function () {
					var timerElement = document.getElementById('ultimakit-product-timer');
					if (timerElement) {
						var endTime = new Date(timerElement.getAttribute('data-end-time')).getTime();
						var label = '<div class="ultimakit-timer-label"><?php echo esc_js( $timer_text ); ?></div>';
	
						var interval = setInterval(function () {
							var now = new Date().getTime();
							var distance = endTime - now;
							var hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
							var minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
							var seconds = Math.floor((distance % (1000 * 60)) / 1000);
	
							// Update timer display with styled spans and custom label
							timerElement.innerHTML = label + '<div class="ultimakit-timer-digits"> <span>' + hours + '</span>h <span>' + minutes + '</span>m <span>' + seconds + '</span>s </div>';
	
							// When the countdown ends
							if (distance < 0) {
								clearInterval(interval);
								timerElement.innerHTML = '<?php echo esc_js( $timer_expired_text ); ?>';
								timerElement.classList.add('expired'); // Add expired class for styling
							}
						}, 1000);
					}
				});
			</script>
			<?php
		}
	}


	// Display the countdown timer based on the selected location
	public function display_product_timer() {
		global $product;

		// Get timer start and end dates and display location from product meta
		$start_time       = get_post_meta( $product->get_id(), '_timer_start_time', true );
		$display_location = get_post_meta( $product->get_id(), '_timer_display_location', true );
		$end_time         = get_post_meta( $product->get_id(), '_timer_end_time', true );

		// Get WordPress timezone setting
		$timezone_string = get_option( 'timezone_string' );

		// Check if a valid timezone string is set, otherwise use UTC offset
		if ( $timezone_string ) {
			$timezone = new DateTimeZone( $timezone_string );
		} else {
			// Get UTC offset
			$utc_offset = get_option( 'gmt_offset' );
			$timezone   = new DateTimeZone( 'UTC' );
		}

		// Only display the timer if a valid end time is set
		if ( $end_time && strtotime( $end_time ) > time() ) {

			try {
				$datetime = new DateTime( $end_time );
				$datetime->setTimezone( $timezone );
			} catch ( Exception $e ) {
				// If there is an error with the date, output nothing
				return;
			}

			$formatted_end_time = $datetime->format( 'Y-m-d\TH:i:sP' );

			// Timer HTML
			$timer_html = '<div id="ultimakit-product-timer" data-end-time="' . esc_attr( $formatted_end_time ) . '"></div>';

			// Display the timer based on location
			switch ( $display_location ) {
				case 'before_add_to_cart':
					add_action(
						'woocommerce_single_product_summary',
						function () use ( $timer_html ) {
							echo $timer_html;
						},
						29
					); // Before add to cart button
					break;

				case 'after_add_to_cart':
					add_action(
						'woocommerce_single_product_summary',
						function () use ( $timer_html ) {
							echo $timer_html;
						},
						31
					); // After add to cart button
					break;

				default:
					// Fallback to a default position if none is selected
					add_action(
						'woocommerce_single_product_summary',
						function () use ( $timer_html ) {
							echo $timer_html;
						},
						25
					);
					break;
			}
		}
	}
}
