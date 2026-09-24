<?php
/**
 * Class UltimaKit_Module_Wc_Custom_Sale_Badge_Text
 *
 * @since 1.0.0
 * @package    UltimaKit
 */

/**
 * Class UltimaKit_Module_Wc_Custom_Sale_Badge_Text
 *
 * Module Version: 1.0.0
 *
 * This class provides methods to control the display of dashboard widgets based on user preferences.
 * It allows users to selectively hide certain dashboard widgets to streamline their WordPress dashboard
 * experience and improve usability.
 *
 * @since 1.0.0
 */
class UltimaKit_Module_Wc_Custom_Sale_Badge_Text extends UltimaKit_Module_Manager {
	/**
	 *
	 * @var string
	 */
	protected $ID = 'ultimakit_module_wc_custom_sale_badge_text';

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
	protected $read_more_link = 'custom-sale-badget-text-in-woocommerce';

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
		$this->name        = __( 'Woo - Custom Sale Badge Text', 'ultimakit-for-wp' );
		$this->description = __( 'Set Custom Sale Badge Text on WooCommerce Shop Page.', 'ultimakit-for-wp' );
		$this->is_active   = $this->isModuleActive( $this->ID );
		$this->settings    = 'yes';
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
			add_action( 'admin_enqueue_scripts', array( $this, 'add_scripts' ) );
			add_action( 'admin_footer', array( $this, 'add_modal' ) );

			add_filter( 'woocommerce_sale_flash', array( $this, 'wc_custom_sale_badge_text' ), 10, 3 );
			add_action( 'wp_head', array( $this, 'wc_add_custom_css_to_badge' ) );

		}
	}


	/**
	 * Adds a modal dialog to the page.
	 *
	 * This function is responsible for initiating and rendering a modal dialog within the
	 * application or website interface. It typically involves setting up the necessary HTML
	 * and JavaScript for the modal to function and display correctly. The modal can be used
	 * for various purposes, such as displaying information, confirming actions, or collecting
	 * user input.
	 *
	 * @return void
	 */
	public function add_modal() {
		$arguments          = array();
		$arguments['ID']    = $this->ID;
		$arguments['title'] = __( 'Custom Sale Badge Text & Customisation', 'ultimakit-for-wp' );

		$arguments['fields'] = array(
			'custom_text_badge' => array(
				'type'  => 'text',
				'label' => __( 'Custom Sale Badge Text', 'ultimakit-for-wp' ),
				'value' => $this->getModuleSettings( $this->ID, 'custom_text_badge' ),
			),
			'background_color'  => array(
				'type'  => 'color',
				'label' => __( 'Badge Background Color', 'ultimakit-for-wp' ),
				'value' => $this->getModuleSettings( $this->ID, 'background_color' ),
			),
			'text_color'        => array(
				'type'  => 'color',
				'label' => __( 'Text Color', 'ultimakit-for-wp' ),
				'value' => $this->getModuleSettings( $this->ID, 'text_color' ),
			),
			'padding_top'       => array(
				'type'  => 'number',
				'label' => __( 'Padding Top (Numbers only)', 'ultimakit-for-wp' ),
				'value' => $this->getModuleSettings( $this->ID, 'padding_top' ),
			),
			'padding_right'     => array(
				'type'  => 'number',
				'label' => __( 'Padding Right (Numbers only)', 'ultimakit-for-wp' ),
				'value' => $this->getModuleSettings( $this->ID, 'padding_right' ),
			),
			'padding_bottom'    => array(
				'type'  => 'number',
				'label' => __( 'Padding Bottom (Numbers only)', 'ultimakit-for-wp' ),
				'value' => $this->getModuleSettings( $this->ID, 'padding_bottom' ),
			),
			'padding_left'      => array(
				'type'  => 'number',
				'label' => __( 'Padding Left (Numbers only)', 'ultimakit-for-wp' ),
				'value' => $this->getModuleSettings( $this->ID, 'padding_left' ),
			),
			'border_radius'     => array(
				'type'  => 'number',
				'label' => __( 'Border Radius (Numbers only)', 'ultimakit-for-wp' ),
				'value' => $this->getModuleSettings( $this->ID, 'border_radius' ),
			),
			'font_size'         => array(
				'type'  => 'number',
				'label' => __( 'Font Size (Numbers only)', 'ultimakit-for-wp' ),
				'value' => $this->getModuleSettings( $this->ID, 'font_size' ),
			),
			'border_color'      => array(
				'type'  => 'color',
				'label' => __( 'Border Color', 'ultimakit-for-wp' ),
				'value' => $this->getModuleSettings( $this->ID, 'border_color' ),
			),
			'border_width'      => array(
				'type'  => 'number',
				'value' => '1px',
				'label' => __( 'Border width', 'ultimakit-for-wp' ),
				'value' => $this->getModuleSettings( $this->ID, 'border_width' ),
			),
			'border_style'      => array(
				'type'     => 'select',
				'label'    => __( 'Border Style', 'ultimakit-for-wp' ),
				'options'  => array(
					'none'   => __( 'None', 'ultimakit-for-wp' ),
					'solid'  => __( 'Solid', 'ultimakit-for-wp' ),
					'dashed' => __( 'Dashed', 'ultimakit-for-wp' ),
					'dotted' => __( 'Dotted', 'ultimakit-for-wp' ),
					'double' => __( 'Double', 'ultimakit-for-wp' ),
				),
				'desc'     => __( 'Border style, select disable to hide border.', 'ultimakit-for-wp' ),
				'required' => '',
				'default'  => $this->getModuleSettings( $this->ID, 'border_style' ),
			),
		);
		$this->ultimakit_generate_modal( $arguments );
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

		wp_enqueue_style( 'wp-color-picker' );

		wp_enqueue_script(
			'ultimakit-module-script-' . $this->ID,
			plugins_url( '/module-script.js', __FILE__ ),
			array( 'jquery', 'wp-color-picker' ),
			ULTIMAKIT_FOR_WP_VERSION,
			true
		);
	}

	public function wc_custom_sale_badge_text( $html, $post, $product ) {

		// Your custom sale text
		$custom_text = $this->getModuleSettings( $this->ID, 'custom_text_badge' ); // Change this text to what you want

		// Keep WooCommerce's own badge while no custom text is set, rather than an empty one.
		if ( empty( $custom_text ) ) {
			return $html;
		}

		// Replace the default "Sale!" text
		$html = '<span class="onsale">' . esc_html( $custom_text ) . '</span>';

		return $html;
	}

	public function wc_add_custom_css_to_badge() {
		// Start the style tag only if there are values to add.
		// Every value lands in a <style> block: colours must be hex, sizes whole numbers and
		// the border style one of the offered options, otherwise it is treated as unset.
		$background_color = sanitize_hex_color( (string) $this->getModuleSettings( $this->ID, 'background_color' ) );
		$text_color       = sanitize_hex_color( (string) $this->getModuleSettings( $this->ID, 'text_color' ) );
		$padding_top      = absint( $this->getModuleSettings( $this->ID, 'padding_top' ) );
		$padding_bottom   = absint( $this->getModuleSettings( $this->ID, 'padding_bottom' ) );
		$padding_right    = absint( $this->getModuleSettings( $this->ID, 'padding_right' ) );
		$padding_left     = absint( $this->getModuleSettings( $this->ID, 'padding_left' ) );
		$border_radius    = absint( $this->getModuleSettings( $this->ID, 'border_radius' ) );
		$font_size        = absint( $this->getModuleSettings( $this->ID, 'font_size' ) );
		$border_width     = absint( $this->getModuleSettings( $this->ID, 'border_width' ) );
		$border_color     = sanitize_hex_color( (string) $this->getModuleSettings( $this->ID, 'border_color' ) );
		$border_style     = $this->getModuleSettings( $this->ID, 'border_style' );
		$border_style     = in_array( $border_style, array( 'none', 'solid', 'dashed', 'dotted', 'double' ), true ) ? $border_style : '';

		$styles = array();

		if ( ! empty( $background_color ) ) {
			$styles[] = "background-color: $background_color !important;";
		}

		if ( ! empty( $text_color ) ) {
			$styles[] = "color: $text_color !important;";
		}

		if ( ! empty( $padding_top ) ) {
			$styles[] = "padding-top: {$padding_top}px !important;";
		}

		if ( ! empty( $padding_bottom ) ) {
			$styles[] = "padding-bottom: {$padding_bottom}px !important;";
		}

		if ( ! empty( $padding_right ) ) {
			$styles[] = "padding-right: {$padding_right}px !important;";
		}

		if ( ! empty( $padding_left ) ) {
			$styles[] = "padding-left: {$padding_left}px !important;";
		}

		if ( ! empty( $border_radius ) ) {
			$styles[] = "border-radius: {$border_radius}px !important;";
		}

		if ( ! empty( $font_size ) ) {
			$styles[] = "font-size: {$font_size}px !important;";
		}

		// Check if all border properties are set (already read and sanitized above)
		if ( ! empty( $border_width ) && ! empty( $border_color ) && ! empty( $border_style ) ) {
			$border   = "{$border_width}px {$border_style} {$border_color}";
			$styles[] = "border: {$border} !important;";
		} else {
			$styles[] = 'border: none !important;';
		}

		if ( ! empty( $styles ) ) {
			echo '<style>
				.woocommerce span.onsale {
					' . implode( "\n", $styles ) . '
				}
				div.wc-block-components-product-sale-badge{
					' . implode( "\n", $styles ) . '
				}
			</style>';
		}

		// Only relabel badges when there is custom text; otherwise this blanked every badge.
		$custom_text = (string) $this->getModuleSettings( $this->ID, 'custom_text_badge' );

		if ( '' !== $custom_text && function_exists( 'is_woocommerce' ) && ( is_archive() || is_shop() || is_product_category() || is_product() ) ) {
			?>
			<script type="text/javascript">
				document.addEventListener('DOMContentLoaded', function() {

					// Function to update the sale badge text
					function updateSaleBadge() {
						const saleBadges = document.querySelectorAll('.onsale');
						saleBadges.forEach(function(badge) {
							badge.textContent = <?php echo wp_json_encode( (string) $this->getModuleSettings( $this->ID, 'custom_text_badge' ) ); ?>; // Replace with your desired text
						});

						const saleBadgesB = document.querySelectorAll('.wc-block-components-product-sale-badge span');
						saleBadgesB.forEach(function(badge) {
							badge.textContent = <?php echo wp_json_encode( (string) $this->getModuleSettings( $this->ID, 'custom_text_badge' ) ); ?>; // Replace with your desired text
						});
						
					}
					
					// Check immediately after DOM is ready
					updateSaleBadge();
					
					// Use a slight delay to ensure all elements are fully rendered
					setTimeout(updateSaleBadge, 500);
				});
			</script>
			<?php
		}
	}
}
