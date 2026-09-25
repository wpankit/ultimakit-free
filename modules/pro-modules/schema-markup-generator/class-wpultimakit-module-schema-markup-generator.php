<?php
/**
 * Class UltimaKit_Module_Schema_Markup_Generator
 *
 * @since 1.0.0
 * @package    UltimaKit
 */

/**
 * Class UltimaKit_Module_Meta_Tag_Editor
 *
 * This class provides methods to control the display of dashboard widgets based on user preferences.
 * It allows users to selectively hide certain dashboard widgets to streamline their WordPress dashboard
 * experience and improve usability.
 *
 * @since 1.0.0
 */
class UltimaKit_Module_Schema_Markup_Generator extends UltimaKit_Module_Manager {
	/**
	 *
	 * @var string
	 */
	protected $ID = 'ultimakit_module_schema_markup_generator';

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
	protected $read_more_link = 'schema-markup-generator-in-wordpress';

	/**
	 * The settings associated with the module, if any.
	 *
	 * @var array
	 */
	protected $settings;

	/**
	 * Supported schema types.
	 *
	 * @var array
	 */
	private $supported_schemas = array(
		'FAQPage'       => 'FAQ Page',
		'Product'       => 'Product',
		'Review'        => 'Review',
		'Article'       => 'Article',
		'LocalBusiness' => 'Local Business',
	);


	/**
	 *
	 * Initializes the module with default values for properties and prepares
	 * any necessary setup or hooks into WordPress. This may include setting
	 * initial values, registering hooks, or preparing resources needed for
	 * the module to function properly within WordPress.
	 */
	public function __construct() {
		$this->name        = __( 'Schema Markup Generator', 'ultimakit-for-wp' );
		$this->description = __( 'Generate schema markup for your WordPress articles.', 'ultimakit-for-wp' );
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

			// Add meta box
			add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );

			// Save post meta
			add_action( 'save_post', array( $this, 'save_schema_data' ) );

			// Output schema in head
			add_action( 'wp_head', array( $this, 'output_schema_markup' ) );

			// AJAX handlers
			add_action( 'wp_ajax_validate_schema', array( $this, 'ajax_validate_schema' ) );

		}
	}


	/**
	 * Add meta box to post editor.
	 */
	public function add_meta_box() {
		$post_types = apply_filters( 'ultimakit_schema_post_types', array( 'post', 'page', 'product' ) );

		foreach ( $post_types as $post_type ) {
			add_meta_box(
				'ultimakit_schema_markup',
				__( 'Schema Markup Generator', 'ultimakit-for-wp' ),
				array( $this, 'render_meta_box' ),
				$post_type,
				'normal',
				'high'
			);
		}
	}

	/**
	 * Render meta box content.
	 *
	 * @param WP_Post $post Post object.
	 */
	public function render_meta_box( $post ) {
		// Security nonce
		wp_nonce_field( 'ultimakit_schema_markup_nonce', 'ultimakit_schema_markup_nonce' );

		// Get saved data
		$schema_type = get_post_meta( $post->ID, '_schema_type', true );
		$schema_data = get_post_meta( $post->ID, '_schema_data', true );

		// Decode saved JSON data
		$saved_data = ! empty( $schema_data ) ? json_decode( $schema_data, true ) : array();
		?>
		<div class="ultimakit-schema-markup-generator">
			<!-- Schema Type Selector -->
			<div class="schema-type-selector">
				<label for="schema_type"><?php _e( 'Select Schema Type:', 'ultimakit-for-wp' ); ?></label>
				<select id="schema_type" name="schema_type" class="widefat">
					<option value=""><?php _e( 'Select Schema Type', 'ultimakit-for-wp' ); ?></option>
					<?php foreach ( $this->supported_schemas as $value => $label ) : ?>
						<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $schema_type, $value ); ?>>
							<?php echo esc_html( $label ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</div>

			<!-- Schema Builders -->
			<div class="schema-builders">
				<!-- FAQ Schema Builder -->
				<div id="faqpage-schema-builder" class="schema-builder" style="display: none;">
					<h3><?php _e( 'FAQ Schema Builder', 'ultimakit-for-wp' ); ?></h3>
					<div class="faq-items">
						<div class="faq-controls">
							<button type="button" class="button add-faq-item">
								<?php _e( 'Add FAQ Item', 'ultimakit-for-wp' ); ?>
							</button>
						</div>
						<div id="faq-items-container">
							<?php
							if ( ! empty( $saved_data['mainEntity'] ) ) {
								foreach ( $saved_data['mainEntity'] as $faq ) {
									$this->render_faq_item( $faq );
								}
							}
							?>
						</div>
					</div>
				</div>

				<!-- Product Schema Builder -->
				<div id="product-schema-builder" class="schema-builder" style="display: none;">
					<h3><?php _e( 'Product Schema Builder', 'ultimakit-for-wp' ); ?></h3>
					<table class="form-table">
						<tr>
							<th><label for="product_name"><?php _e( 'Product Name', 'ultimakit-for-wp' ); ?></label></th>
							<td>
								<input type="text" id="product_name" name="product_schema[name]" 
									class="widefat" value="<?php echo esc_attr( $saved_data['name'] ?? '' ); ?>"
									required>
							</td>
						</tr>
						<tr>
							<th><label for="product_description"><?php _e( 'Description', 'ultimakit-for-wp' ); ?></label></th>
							<td>
								<textarea id="product_description" name="product_schema[description]" 
									class="widefat" required><?php echo esc_textarea( $saved_data['description'] ?? '' ); ?></textarea>
							</td>
						</tr>
						<tr>
								<th><label for="product_price"><?php _e( 'Price', 'ultimakit-for-wp' ); ?></label></th>
							<td>
								<input type="number" id="product_price" name="product_schema[price]" 
									step="0.01" min="0" 
									value="<?php echo esc_attr( $saved_data['offers']['price'] ?? '' ); ?>"
									required>
							</td>
						</tr>
						<tr>
							<th><label for="product_currency"><?php _e( 'Currency', 'ultimakit-for-wp' ); ?></label></th>
							<td>
								<select id="product_currency" name="product_schema[currency]" required>
									<?php
									$currencies        = $this->get_currency_options();
									$selected_currency = $saved_data['offers']['priceCurrency'] ?? 'USD';
									foreach ( $currencies as $code => $name ) {
										printf(
											'<option value="%s" %s>%s</option>',
											esc_attr( $code ),
											selected( $selected_currency, $code, false ),
											esc_html( $name )
										);
									}
									?>
								</select>
							</td>
						</tr>
						<tr>
							<th><label for="product_availability"><?php _e( 'Availability', 'ultimakit-for-wp' ); ?></label></th>
							<td>
								<select id="product_availability" name="product_schema[availability]" required>
									<?php
									$availability_options  = $this->get_availability_options();
									$selected_availability = $saved_data['offers']['availability'] ?? 'https://schema.org/InStock';
									foreach ( $availability_options as $value => $label ) {
										printf(
											'<option value="%s" %s>%s</option>',
											esc_attr( $value ),
											selected( $selected_availability, $value, false ),
											esc_html( $label )
										);
									}
									?>
								</select>
							</td>
						</tr>
					</table>
				</div>

				<!-- Review Schema Builder -->
				<div id="review-schema-builder" class="schema-builder" style="display: none;">
					<h3><?php _e( 'Review Schema Builder', 'ultimakit-for-wp' ); ?></h3>
					<table class="form-table">
						<tr>
							<th><label for="review_item_name"><?php _e( 'Item Name', 'ultimakit-for-wp' ); ?></label></th>
							<td>
								<input type="text" id="review_item_name" name="review_schema[itemName]" 
									class="widefat" value="<?php echo esc_attr( $saved_data['itemReviewed']['name'] ?? '' ); ?>"
									required>
							</td>
						</tr>
						<tr>
							<th><label for="review_author"><?php _e( 'Review Author', 'ultimakit-for-wp' ); ?></label></th>
							<td>
								<input type="text" id="review_author" name="review_schema[author]" 
									class="widefat" value="<?php echo esc_attr( $saved_data['author']['name'] ?? '' ); ?>"
									required>
							</td>
						</tr>
						<tr>
							<th><label for="review_rating"><?php _e( 'Rating', 'ultimakit-for-wp' ); ?></label></th>
							<td>
								<input type="number" id="review_rating" name="review_schema[rating]" 
									min="1" max="5" step="0.1" 
									value="<?php echo esc_attr( $saved_data['reviewRating']['ratingValue'] ?? '' ); ?>"
									required>
								<span class="description"><?php _e( 'Rating from 1 to 5', 'ultimakit-for-wp' ); ?></span>
							</td>
						</tr>
						<tr>
							<th><label for="review_content"><?php _e( 'Review Content', 'ultimakit-for-wp' ); ?></label></th>
							<td>
								<textarea id="review_content" name="review_schema[reviewBody]" 
									class="widefat" required><?php echo esc_textarea( $saved_data['reviewBody'] ?? '' ); ?></textarea>
							</td>
						</tr>
					</table>
				</div>


				<!-- Article Schema Builder -->
				<div id="article-schema-builder" class="schema-builder" style="display: none;">
					<h3><?php _e( 'Article Schema Builder', 'ultimakit-for-wp' ); ?></h3>
					<table class="form-table">
						<tr>
							<th><label for="article_headline"><?php _e( 'Headline', 'ultimakit-for-wp' ); ?></label></th>
							<td>
								<input type="text" id="article_headline" name="article_schema[headline]" 
									class="widefat" value="<?php echo esc_attr( $saved_data['headline'] ?? '' ); ?>"
									maxlength="110" required>
								<p class="description"><?php _e( 'Maximum 110 characters', 'ultimakit-for-wp' ); ?></p>
							</td>
						</tr>
						<tr>
							<th><label for="article_description"><?php _e( 'Description', 'ultimakit-for-wp' ); ?></label></th>
							<td>
								<textarea id="article_description" name="article_schema[description]" 
									class="widefat" rows="3" required><?php echo esc_textarea( $saved_data['description'] ?? '' ); ?></textarea>
							</td>
						</tr>
						<tr>
							<th><label for="article_author"><?php _e( 'Author Name', 'ultimakit-for-wp' ); ?></label></th>
							<td>
								<input type="text" id="article_author" name="article_schema[author]" 
									class="widefat" value="<?php echo esc_attr( $saved_data['author']['name'] ?? '' ); ?>"
									required>
							</td>
						</tr>
						<tr>
							<th><label for="article_published_date"><?php _e( 'Date Published', 'ultimakit-for-wp' ); ?></label></th>
							<td>
								<input type="date" id="article_published_date" name="article_schema[datePublished]" 
									value="<?php echo esc_attr( $saved_data['datePublished'] ?? '' ); ?>"
									required>
							</td>
						</tr>
						<tr>
							<th><label for="article_image"><?php _e( 'Featured Image URL', 'ultimakit-for-wp' ); ?></label></th>
							<td>
								<input type="url" id="article_image" name="article_schema[image]" 
									class="widefat" value="<?php echo esc_url( $saved_data['image'] ?? '' ); ?>">
								<button type="button" class="button select-image">
									<?php _e( 'Select Image', 'ultimakit-for-wp' ); ?>
								</button>
							</td>
						</tr>
						<tr>
							<th><label for="article_publisher"><?php _e( 'Publisher Name', 'ultimakit-for-wp' ); ?></label></th>
							<td>
								<input type="text" id="article_publisher" name="article_schema[publisher]" 
									class="widefat" value="<?php echo esc_attr( $saved_data['publisher']['name'] ?? '' ); ?>"
									required>
							</td>
						</tr>
						
						<tr>
							<th><label for="article_publisher_logo"><?php _e( 'Publisher Logo URL', 'ultimakit-for-wp' ); ?></label></th>
							<td>
								<input type="url" id="article_publisher_logo" name="article_schema[publisherLogo]" 
									class="widefat" value="<?php echo esc_url( $saved_data['publisher']['logo'] ?? '' ); ?>">
								<button type="button" class="button select-image">
									<?php _e( 'Select Logo', 'ultimakit-for-wp' ); ?>
								</button>
							</td>
						</tr>
					</table>
				</div>

				<!-- Local Business Schema Builder -->
				<div id="localbusiness-schema-builder" class="schema-builder" style="display: none;">
					<h3><?php _e( 'Local Business Schema Builder', 'ultimakit-for-wp' ); ?></h3>
					<table class="form-table">
						<tr>
							<th><label for="business_name"><?php _e( 'Business Name', 'ultimakit-for-wp' ); ?></label></th>
							<td>
								<input type="text" id="business_name" name="business_schema[name]" 
									class="widefat" value="<?php echo esc_attr( $saved_data['name'] ?? '' ); ?>"
									required>
							</td>
						</tr>
						<tr>
							<th><label for="business_description"><?php _e( 'Description', 'ultimakit-for-wp' ); ?></label></th>
							<td>
								<textarea id="business_description" name="business_schema[description]" 
									class="widefat" rows="3" required><?php echo esc_textarea( $saved_data['description'] ?? '' ); ?></textarea>
							</td>
						</tr>
						<tr>
							<th><label for="business_type"><?php _e( 'Business Type', 'ultimakit-for-wp' ); ?></label></th>
							<td>
								<select id="business_type" name="business_schema[type]" required>
									<?php
									$business_types = array(
										'LocalBusiness'    => 'Local Business',
										'Store'            => 'Store',
										'Restaurant'       => 'Restaurant',
										'Hotel'            => 'Hotel',
										'Service'          => 'Service Provider',
										'MedicalBusiness'  => 'Medical Business',
										'AutomotiveBusiness' => 'Automotive Business',
										'FinancialService' => 'Financial Service',
										'SportsActivityLocation' => 'Sports Facility',
										'EducationalOrganization' => 'Educational Organization',
										'ProfessionalService' => 'Professional Service',
									);

									// Get saved business type
									$saved_type = isset( $saved_data['@type'] ) ? $saved_data['@type'] : 'LocalBusiness';

									foreach ( $business_types as $value => $label ) {
										printf(
											'<option value="%s" %s>%s</option>',
											esc_attr( $value ),
											selected( $saved_type, $value, false ),
											esc_html( $label )
										);
									}
									?>
								</select>
							</td>
						</tr>
						<tr>
							<th><label><?php _e( 'Address', 'ultimakit-for-wp' ); ?></label></th>
							<td>
								<p>
									<input type="text" name="business_schema[street]" 
										placeholder="<?php esc_attr_e( 'Street Address', 'ultimakit-for-wp' ); ?>"
										class="widefat" value="<?php echo esc_attr( $saved_data['address']['streetAddress'] ?? '' ); ?>"
										required>
								</p>
								<p>
									<input type="text" name="business_schema[city]" 
										placeholder="<?php esc_attr_e( 'City', 'ultimakit-for-wp' ); ?>"
										class="widefat" value="<?php echo esc_attr( $saved_data['address']['addressLocality'] ?? '' ); ?>"
										required>
								</p>
								<p>
									<input type="text" name="business_schema[region]" 
										placeholder="<?php esc_attr_e( 'State/Region', 'ultimakit-for-wp' ); ?>"
										class="widefat" value="<?php echo esc_attr( $saved_data['address']['addressRegion'] ?? '' ); ?>"
										required>
								</p>
								<p>
									<input type="text" name="business_schema[postal]" 
										placeholder="<?php esc_attr_e( 'Postal Code', 'ultimakit-for-wp' ); ?>"
										class="widefat" value="<?php echo esc_attr( $saved_data['address']['postalCode'] ?? '' ); ?>"
										required>
								</p>
								<p>
									<input type="text" name="business_schema[country]" 
										placeholder="<?php esc_attr_e( 'Country', 'ultimakit-for-wp' ); ?>"
										class="widefat" value="<?php echo esc_attr( $saved_data['address']['addressCountry'] ?? '' ); ?>"
										required>
								</p>
							</td>
						</tr>
						<tr>
							<th><label for="business_phone"><?php _e( 'Phone Number', 'ultimakit-for-wp' ); ?></label></th>
							<td>
								<input type="tel" id="business_phone" name="business_schema[phone]" 
									class="widefat" value="<?php echo esc_attr( $saved_data['telephone'] ?? '' ); ?>"
									required>
							</td>
						</tr>
						<tr>
							<th><label for="business_email"><?php _e( 'Email', 'ultimakit-for-wp' ); ?></label></th>
							<td>
								<input type="email" id="business_email" name="business_schema[email]" 
									class="widefat" value="<?php echo esc_attr( $saved_data['email'] ?? '' ); ?>">
							</td>
						</tr>
						<tr>
							<th><label for="business_website"><?php _e( 'Website', 'ultimakit-for-wp' ); ?></label></th>
							<td>
								<input type="url" id="business_website" name="business_schema[website]" 
									class="widefat" value="<?php echo esc_url( $saved_data['url'] ?? '' ); ?>">
							</td>
						</tr>
						<tr>
							<th><label><?php _e( 'Opening Hours', 'ultimakit-for-wp' ); ?></label></th>
							<td>
								<div class="business-hours">
									<?php
									$days = array(
										'Monday'    => 'Mo',
										'Tuesday'   => 'Tu',
										'Wednesday' => 'We',
										'Thursday'  => 'Th',
										'Friday'    => 'Fr',
										'Saturday'  => 'Sa',
										'Sunday'    => 'Su',
									);

									// Parse saved opening hours
									$parsed_hours  = array();
									$opening_hours = isset( $saved_data['openingHours'] ) ? $saved_data['openingHours'] : '';
									if ( ! empty( $opening_hours ) && is_array( $opening_hours ) ) {
										foreach ( $opening_hours as $hours_string ) {
											// Split "Mo 20:39-12:39" into ["Mo", "20:39-12:39"]
											$parts = explode( ' ', $hours_string );
											if ( count( $parts ) === 2 ) {
												$day_abbr = $parts[0];
												$times    = explode( '-', $parts[1] );

												// Find the full day name from abbreviation
												$full_day = array_search( $day_abbr, $days );
												if ( $full_day ) {
													$parsed_hours[ strtolower( $full_day ) ] = array(
														'from' => $times[0],
														'to' => $times[1],
													);
												}
											}
										}
									}

									// Render form fields
									foreach ( $days as $day => $abbr ) {
										$day_key   = strtolower( $day );
										$is_open   = isset( $parsed_hours[ $day_key ] );
										$from_time = $is_open ? $parsed_hours[ $day_key ]['from'] : '';
										$to_time   = $is_open ? $parsed_hours[ $day_key ]['to'] : '';
										?>
										<div class="business-hours-day">
											<label>
												<input type="checkbox" 
													name="business_schema[hours][<?php echo $day_key; ?>][open]" 
													<?php checked( $is_open ); ?>>
												<?php echo esc_html( $day ); ?>
											</label>
											<input type="time" 
												name="business_schema[hours][<?php echo $day_key; ?>][from]" 
												value="<?php echo esc_attr( $from_time ); ?>" 
												<?php disabled( ! $is_open ); ?>>
											<span>to</span>
											<input type="time" 
												name="business_schema[hours][<?php echo $day_key; ?>][to]" 
												value="<?php echo esc_attr( $to_time ); ?>" 
												<?php disabled( ! $is_open ); ?>>
										</div>
										<?php
									}
									?>
								</div>
							</td>
						</tr>
						<tr>
							<th><label for="business_price_range"><?php _e( 'Price Range', 'ultimakit-for-wp' ); ?></label></th>
							<td>
								<select id="business_price_range" name="business_schema[priceRange]">
									<option value=""><?php _e( 'Select Price Range', 'ultimakit-for-wp' ); ?></option>
									<option value="$" <?php selected( $saved_data['priceRange'] ?? '', '$' ); ?>><?php _e( '$ (Inexpensive)', 'ultimakit-for-wp' ); ?></option>
									<option value="$$" <?php selected( $saved_data['priceRange'] ?? '', '$$' ); ?>><?php _e( '$$ (Moderate)', 'ultimakit-for-wp' ); ?></option>
									<option value="$$$" <?php selected( $saved_data['priceRange'] ?? '', '$$$' ); ?>><?php _e( '$$$ (Expensive)', 'ultimakit-for-wp' ); ?></option>
									<option value="$$$$" <?php selected( $saved_data['priceRange'] ?? '', '$$$$' ); ?>><?php _e( '$$$$ (Very Expensive)', 'ultimakit-for-wp' ); ?></option>
								</select>
							</td>
						</tr>
					</table>
				</div>

			</div>
			</div>

			<!-- Schema Preview -->
			<div class="schema-preview">
				<h3><?php _e( 'Generated Schema Preview', 'ultimakit-for-wp' ); ?></h3>
				<div class="schema-validation-status"></div>
				<textarea id="schema_data" name="schema_data" rows="10" class="widefat" readonly></textarea>
				<p class="description">
					<?php _e( 'This is the generated JSON-LD schema. It will be automatically updated as you fill in the form above.', 'ultimakit-for-wp' ); ?>
				</p>
				<button type="button" class="button validate-schema">
					<?php _e( 'Validate Schema', 'ultimakit-for-wp' ); ?>
				</button>
			</div>
		</div>
		<?php
		$this->enqueue_scripts();
	}

	/**
	 * Get saved custom schema
	 */
	private function get_saved_custom_schema() {
		$schema_data = get_post_meta( get_the_ID(), 'schema_data', true );
		return isset( $schema_data['custom_schema'] ) ? $schema_data['custom_schema'] : '';
	}

	/**
	 * Render FAQ item template.
	 *
	 * @param array $faq Optional FAQ data.
	 */
	private function render_faq_item( $faq = array() ) {
		?>
		<div class="faq-item">
			<span class="remove-faq dashicons dashicons-trash"></span>
			<p>
				<label><?php _e( 'Question:', 'ultimakit-for-wp' ); ?></label>
				<input type="text" class="widefat faq-question" 
					name="faq_schema[questions][]" 
					value="<?php echo esc_attr( $faq['name'] ?? '' ); ?>"
					required>
			</p>
			<p>
				<label><?php _e( 'Answer:', 'ultimakit-for-wp' ); ?></label>
				<textarea class="widefat faq-answer" 
					name="faq_schema[answers][]" 
					required><?php echo esc_textarea( $faq['acceptedAnswer']['text'] ?? '' ); ?></textarea>
			</p>
		</div>
		<?php
	}

	/**
	 * Get currency options.
	 *
	 * @return array
	 */
	private function get_currency_options() {
		return array(
			'USD' => __( 'US Dollar', 'ultimakit-for-wp' ),
			'EUR' => __( 'Euro', 'ultimakit-for-wp' ),
			'GBP' => __( 'British Pound Sterling', 'ultimakit-for-wp' ),
			'JPY' => __( 'Japanese Yen', 'ultimakit-for-wp' ),
			'AUD' => __( 'Australian Dollar', 'ultimakit-for-wp' ),
			'CAD' => __( 'Canadian Dollar', 'ultimakit-for-wp' ),
			'CHF' => __( 'Swiss Franc', 'ultimakit-for-wp' ),
			'CNY' => __( 'Chinese Yuan', 'ultimakit-for-wp' ),
			'INR' => __( 'Indian Rupee', 'ultimakit-for-wp' ),
			'NZD' => __( 'New Zealand Dollar', 'ultimakit-for-wp' ),
			'AED' => __( 'United Arab Emirates Dirham', 'ultimakit-for-wp' ),
			'AFN' => __( 'Afghan Afghani', 'ultimakit-for-wp' ),
			'ALL' => __( 'Albanian Lek', 'ultimakit-for-wp' ),
			'AMD' => __( 'Armenian Dram', 'ultimakit-for-wp' ),
			'ANG' => __( 'Netherlands Antillean Guilder', 'ultimakit-for-wp' ),
			'AOA' => __( 'Angolan Kwanza', 'ultimakit-for-wp' ),
			'ARS' => __( 'Argentine Peso', 'ultimakit-for-wp' ),
			'AWG' => __( 'Aruban Florin', 'ultimakit-for-wp' ),
			'AZN' => __( 'Azerbaijani Manat', 'ultimakit-for-wp' ),
			'BAM' => __( 'Bosnia-Herzegovina Convertible Mark', 'ultimakit-for-wp' ),
			'BBD' => __( 'Barbadian Dollar', 'ultimakit-for-wp' ),
			'BDT' => __( 'Bangladeshi Taka', 'ultimakit-for-wp' ),
			'BGN' => __( 'Bulgarian Lev', 'ultimakit-for-wp' ),
			'BHD' => __( 'Bahraini Dinar', 'ultimakit-for-wp' ),
			'BIF' => __( 'Burundian Franc', 'ultimakit-for-wp' ),
			'BMD' => __( 'Bermudan Dollar', 'ultimakit-for-wp' ),
			'BND' => __( 'Brunei Dollar', 'ultimakit-for-wp' ),
			'BOB' => __( 'Bolivian Boliviano', 'ultimakit-for-wp' ),
			'BRL' => __( 'Brazilian Real', 'ultimakit-for-wp' ),
			'BSD' => __( 'Bahamian Dollar', 'ultimakit-for-wp' ),
			'BTN' => __( 'Bhutanese Ngultrum', 'ultimakit-for-wp' ),
			'BWP' => __( 'Botswanan Pula', 'ultimakit-for-wp' ),
			'BYN' => __( 'Belarusian Ruble', 'ultimakit-for-wp' ),
			'BZD' => __( 'Belize Dollar', 'ultimakit-for-wp' ),
			'CDF' => __( 'Congolese Franc', 'ultimakit-for-wp' ),
			'CLP' => __( 'Chilean Peso', 'ultimakit-for-wp' ),
			'COP' => __( 'Colombian Peso', 'ultimakit-for-wp' ),
			'CRC' => __( 'Costa Rican Colón', 'ultimakit-for-wp' ),
			'CUP' => __( 'Cuban Peso', 'ultimakit-for-wp' ),
			'CVE' => __( 'Cape Verdean Escudo', 'ultimakit-for-wp' ),
			'CZK' => __( 'Czech Republic Koruna', 'ultimakit-for-wp' ),
			'DJF' => __( 'Djiboutian Franc', 'ultimakit-for-wp' ),
			'DKK' => __( 'Danish Krone', 'ultimakit-for-wp' ),
			'DOP' => __( 'Dominican Peso', 'ultimakit-for-wp' ),
			'DZD' => __( 'Algerian Dinar', 'ultimakit-for-wp' ),
			'EGP' => __( 'Egyptian Pound', 'ultimakit-for-wp' ),
			'ERN' => __( 'Eritrean Nakfa', 'ultimakit-for-wp' ),
			'ETB' => __( 'Ethiopian Birr', 'ultimakit-for-wp' ),
			'FJD' => __( 'Fijian Dollar', 'ultimakit-for-wp' ),
			'FKP' => __( 'Falkland Islands Pound', 'ultimakit-for-wp' ),
			'GEL' => __( 'Georgian Lari', 'ultimakit-for-wp' ),
			'GGP' => __( 'Guernsey Pound', 'ultimakit-for-wp' ),
			'GHS' => __( 'Ghanaian Cedi', 'ultimakit-for-wp' ),
			'GIP' => __( 'Gibraltar Pound', 'ultimakit-for-wp' ),
			'GMD' => __( 'Gambian Dalasi', 'ultimakit-for-wp' ),
			'GNF' => __( 'Guinean Franc', 'ultimakit-for-wp' ),
			'GTQ' => __( 'Guatemalan Quetzal', 'ultimakit-for-wp' ),
			'GYD' => __( 'Guyanaese Dollar', 'ultimakit-for-wp' ),
			'HKD' => __( 'Hong Kong Dollar', 'ultimakit-for-wp' ),
			'HNL' => __( 'Honduran Lempira', 'ultimakit-for-wp' ),
			'HRK' => __( 'Croatian Kuna', 'ultimakit-for-wp' ),
			'HTG' => __( 'Haitian Gourde', 'ultimakit-for-wp' ),
			'HUF' => __( 'Hungarian Forint', 'ultimakit-for-wp' ),
			'IDR' => __( 'Indonesian Rupiah', 'ultimakit-for-wp' ),
			'ILS' => __( 'Israeli New Sheqel', 'ultimakit-for-wp' ),
			'IMP' => __( 'Manx Pound', 'ultimakit-for-wp' ),
			'IQD' => __( 'Iraqi Dinar', 'ultimakit-for-wp' ),
			'IRR' => __( 'Iranian Rial', 'ultimakit-for-wp' ),
			'ISK' => __( 'Icelandic Króna', 'ultimakit-for-wp' ),
			'JEP' => __( 'Jersey Pound', 'ultimakit-for-wp' ),
			'JMD' => __( 'Jamaican Dollar', 'ultimakit-for-wp' ),
			'JOD' => __( 'Jordanian Dinar', 'ultimakit-for-wp' ),
			'KES' => __( 'Kenyan Shilling', 'ultimakit-for-wp' ),
			'KGS' => __( 'Kyrgystani Som', 'ultimakit-for-wp' ),
			'KHR' => __( 'Cambodian Riel', 'ultimakit-for-wp' ),
			'KMF' => __( 'Comorian Franc', 'ultimakit-for-wp' ),
			'KPW' => __( 'North Korean Won', 'ultimakit-for-wp' ),
			'KRW' => __( 'South Korean Won', 'ultimakit-for-wp' ),
			'KWD' => __( 'Kuwaiti Dinar', 'ultimakit-for-wp' ),
			'KYD' => __( 'Cayman Islands Dollar', 'ultimakit-for-wp' ),
			'KZT' => __( 'Kazakhstani Tenge', 'ultimakit-for-wp' ),
			'LAK' => __( 'Laotian Kip', 'ultimakit-for-wp' ),
			'LBP' => __( 'Lebanese Pound', 'ultimakit-for-wp' ),
			'LKR' => __( 'Sri Lankan Rupee', 'ultimakit-for-wp' ),
			'LRD' => __( 'Liberian Dollar', 'ultimakit-for-wp' ),
			'LSL' => __( 'Lesotho Loti', 'ultimakit-for-wp' ),
			'LYD' => __( 'Libyan Dinar', 'ultimakit-for-wp' ),
			'MAD' => __( 'Moroccan Dirham', 'ultimakit-for-wp' ),
			'MDL' => __( 'Moldovan Leu', 'ultimakit-for-wp' ),
			'MGA' => __( 'Malagasy Ariary', 'ultimakit-for-wp' ),
			'MKD' => __( 'Macedonian Denar', 'ultimakit-for-wp' ),
			'MMK' => __( 'Myanma Kyat', 'ultimakit-for-wp' ),
			'MNT' => __( 'Mongolian Tugrik', 'ultimakit-for-wp' ),
			'MOP' => __( 'Macanese Pataca', 'ultimakit-for-wp' ),
			'MRU' => __( 'Mauritanian Ouguiya', 'ultimakit-for-wp' ),
			'MUR' => __( 'Mauritian Rupee', 'ultimakit-for-wp' ),
			'MVR' => __( 'Maldivian Rufiyaa', 'ultimakit-for-wp' ),
			'MWK' => __( 'Malawian Kwacha', 'ultimakit-for-wp' ),
			'MXN' => __( 'Mexican Peso', 'ultimakit-for-wp' ),
			'MYR' => __( 'Malaysian Ringgit', 'ultimakit-for-wp' ),
			'MZN' => __( 'Mozambican Metical', 'ultimakit-for-wp' ),
			'NAD' => __( 'Namibian Dollar', 'ultimakit-for-wp' ),
			'NGN' => __( 'Nigerian Naira', 'ultimakit-for-wp' ),
			'NIO' => __( 'Nicaraguan Córdoba', 'ultimakit-for-wp' ),
			'NOK' => __( 'Norwegian Krone', 'ultimakit-for-wp' ),
			'NPR' => __( 'Nepalese Rupee', 'ultimakit-for-wp' ),
			'OMR' => __( 'Omani Rial', 'ultimakit-for-wp' ),
			'PAB' => __( 'Panamanian Balboa', 'ultimakit-for-wp' ),
			'PEN' => __( 'Peruvian Nuevo Sol', 'ultimakit-for-wp' ),
			'PGK' => __( 'Papua New Guinean Kina', 'ultimakit-for-wp' ),
			'PHP' => __( 'Philippine Peso', 'ultimakit-for-wp' ),
			'PKR' => __( 'Pakistani Rupee', 'ultimakit-for-wp' ),
			'PLN' => __( 'Polish Złoty', 'ultimakit-for-wp' ),
			'PYG' => __( 'Paraguayan Guarani', 'ultimakit-for-wp' ),
			'QAR' => __( 'Qatari Rial', 'ultimakit-for-wp' ),
			'RON' => __( 'Romanian Leu', 'ultimakit-for-wp' ),
			'RSD' => __( 'Serbian Dinar', 'ultimakit-for-wp' ),
			'RUB' => __( 'Russian Ruble', 'ultimakit-for-wp' ),
			'RWF' => __( 'Rwandan Franc', 'ultimakit-for-wp' ),
			'SAR' => __( 'Saudi Riyal', 'ultimakit-for-wp' ),
			'SBD' => __( 'Solomon Islands Dollar', 'ultimakit-for-wp' ),
			'SCR' => __( 'Seychellois Rupee', 'ultimakit-for-wp' ),
			'SDG' => __( 'Sudanese Pound', 'ultimakit-for-wp' ),
			'SEK' => __( 'Swedish Krona', 'ultimakit-for-wp' ),
			'SGD' => __( 'Singapore Dollar', 'ultimakit-for-wp' ),
			'SHP' => __( 'Saint Helena Pound', 'ultimakit-for-wp' ),
			'SLL' => __( 'Sierra Leonean Leone', 'ultimakit-for-wp' ),
			'SOS' => __( 'Somali Shilling', 'ultimakit-for-wp' ),
			'SRD' => __( 'Surinamese Dollar', 'ultimakit-for-wp' ),
			'SSP' => __( 'South Sudanese Pound', 'ultimakit-for-wp' ),
			'STN' => __( 'São Tomé and Príncipe Dobra', 'ultimakit-for-wp' ),
			'SVC' => __( 'Salvadoran Colón', 'ultimakit-for-wp' ),
			'SYP' => __( 'Syrian Pound', 'ultimakit-for-wp' ),
			'SZL' => __( 'Swazi Lilangeni', 'ultimakit-for-wp' ),
			'THB' => __( 'Thai Baht', 'ultimakit-for-wp' ),
			'TJS' => __( 'Tajikistani Somoni', 'ultimakit-for-wp' ),
			'TMT' => __( 'Turkmenistani Manat', 'ultimakit-for-wp' ),
			'TND' => __( 'Tunisian Dinar', 'ultimakit-for-wp' ),
			'TOP' => __( 'Tongan Paʻanga', 'ultimakit-for-wp' ),
			'TRY' => __( 'Turkish Lira', 'ultimakit-for-wp' ),
			'TTD' => __( 'Trinidad and Tobago Dollar', 'ultimakit-for-wp' ),
			'TWD' => __( 'New Taiwan Dollar', 'ultimakit-for-wp' ),
			'TZS' => __( 'Tanzanian Shilling', 'ultimakit-for-wp' ),
			'UAH' => __( 'Ukrainian Hryvnia', 'ultimakit-for-wp' ),
			'UGX' => __( 'Ugandan Shilling', 'ultimakit-for-wp' ),
			'UYU' => __( 'Uruguayan Peso', 'ultimakit-for-wp' ),
			'UZS' => __( 'Uzbekistan Som', 'ultimakit-for-wp' ),
			'VES' => __( 'Venezuelan Bolívar Soberano', 'ultimakit-for-wp' ),
			'VND' => __( 'Vietnamese Dong', 'ultimakit-for-wp' ),
			'VUV' => __( 'Vanuatu Vatu', 'ultimakit-for-wp' ),
			'WST' => __( 'Samoan Tala', 'ultimakit-for-wp' ),
			'XAF' => __( 'CFA Franc BEAC', 'ultimakit-for-wp' ),
			'XCD' => __( 'East Caribbean Dollar', 'ultimakit-for-wp' ),
			'XDR' => __( 'Special Drawing Rights', 'ultimakit-for-wp' ),
			'XOF' => __( 'CFA Franc BCEAO', 'ultimakit-for-wp' ),
			'XPF' => __( 'CFP Franc', 'ultimakit-for-wp' ),
			'YER' => __( 'Yemeni Rial', 'ultimakit-for-wp' ),
			'ZAR' => __( 'South African Rand', 'ultimakit-for-wp' ),
			'ZMW' => __( 'Zambian Kwacha', 'ultimakit-for-wp' ),
			'ZWL' => __( 'Zimbabwean Dollar', 'ultimakit-for-wp' ),
		);
	}

	/**
	 * Get availability options.
	 *
	 * @return array
	 */
	private function get_availability_options() {
		return array(
			'https://schema.org/InStock'    => __( 'In Stock', 'ultimakit-for-wp' ),
			'https://schema.org/OutOfStock' => __( 'Out of Stock', 'ultimakit-for-wp' ),
			'https://schema.org/PreOrder'   => __( 'Pre-Order', 'ultimakit-for-wp' ),
			'https://schema.org/BackOrder'  => __( 'Back Order', 'ultimakit-for-wp' ),
		);
	}

	/**
	 * Save schema data.
	 *
	 * @param int $post_id Post ID.
	 */
	public function save_schema_data( $post_id ) {
		// Security checks
		if ( ! isset( $_POST['ultimakit_schema_markup_nonce'] ) ||
			! wp_verify_nonce( $_POST['ultimakit_schema_markup_nonce'], 'ultimakit_schema_markup_nonce' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Save schema type
		$schema_type = isset( $_POST['schema_type'] ) ? sanitize_text_field( $_POST['schema_type'] ) : '';
		update_post_meta( $post_id, '_schema_type', $schema_type );

		// Initialize schema data array
		$schema_data = array(
			'@context' => 'https://schema.org',
			'@type'    => $schema_type,
		);

		// Process schema data based on type
		switch ( $schema_type ) {
			case 'FAQPage':  // Changed from 'FAQ' to 'FAQPage'
				if ( isset( $_POST['faq_schema'] ) ) {
					$questions = isset( $_POST['faq_schema']['questions'] ) ? $_POST['faq_schema']['questions'] : array();
					$answers   = isset( $_POST['faq_schema']['answers'] ) ? $_POST['faq_schema']['answers'] : array();

					$mainEntity = array();
					for ( $i = 0; $i < count( $questions ); $i++ ) {
						if ( ! empty( $questions[ $i ] ) && ! empty( $answers[ $i ] ) ) {
							$mainEntity[] = array(
								'@type'          => 'Question',
								'name'           => sanitize_text_field( $questions[ $i ] ),
								'acceptedAnswer' => array(
									'@type' => 'Answer',
									'text'  => wp_kses_post( $answers[ $i ] ),
								),
							);
						}
					}
					$schema_data['mainEntity'] = $mainEntity;
				}
				break;

			case 'Product':
				if ( isset( $_POST['product_schema'] ) ) {
					$product_data               = $_POST['product_schema'];
					$schema_data['name']        = sanitize_text_field( $product_data['name'] );
					$schema_data['description'] = sanitize_textarea_field( $product_data['description'] );
					$schema_data['offers']      = array(
						'@type'         => 'Offer',
						'price'         => floatval( $product_data['price'] ),
						'priceCurrency' => sanitize_text_field( $product_data['currency'] ),
						'availability'  => sanitize_text_field( $product_data['availability'] ),
					);

					// Add optional fields if they exist
					if ( ! empty( $product_data['image'] ) ) {
						$schema_data['image'] = esc_url( $product_data['image'] );
					}
					if ( ! empty( $product_data['sku'] ) ) {
						$schema_data['sku'] = sanitize_text_field( $product_data['sku'] );
					}
					if ( ! empty( $product_data['brand'] ) ) {
						$schema_data['brand'] = array(
							'@type' => 'Brand',
							'name'  => sanitize_text_field( $product_data['brand'] ),
						);
					}
				}
				break;

			case 'Review':
				if ( isset( $_POST['review_schema'] ) ) {
					$review_data                  = $_POST['review_schema'];
					$schema_data['itemReviewed']  = array(
						'@type' => 'Thing',
						'name'  => sanitize_text_field( $review_data['itemName'] ),
					);
					$schema_data['author']        = array(
						'@type' => 'Person',
						'name'  => sanitize_text_field( $review_data['author'] ),
					);
					$schema_data['reviewRating']  = array(
						'@type'       => 'Rating',
						'ratingValue' => floatval( $review_data['rating'] ),
						'bestRating'  => '5',
					);
					$schema_data['reviewBody']    = sanitize_textarea_field( $review_data['reviewBody'] );
					$schema_data['datePublished'] = current_time( 'Y-m-d' );
				}
				break;

			case 'Article':
				if ( isset( $_POST['article_schema'] ) ) {
					$article_data                 = $_POST['article_schema'];
					$schema_data['headline']      = sanitize_text_field( $article_data['headline'] );
					$schema_data['author']        = array(
						'@type' => 'Person',
						'name'  => sanitize_text_field( $article_data['author'] ),
					);
					$schema_data['datePublished'] = sanitize_text_field( $article_data['datePublished'] );
					$schema_data['dateModified']  = current_time( 'Y-m-d\TH:i:s\Z' );
					$schema_data['description']   = sanitize_textarea_field( $article_data['description'] );

					if ( ! empty( $article_data['image'] ) ) {
						$schema_data['image'] = esc_url( $article_data['image'] );
					}
					if ( ! empty( $article_data['publisher'] ) ) {
						$schema_data['publisher'] = array(
							'@type' => 'Organization',
							'name'  => sanitize_text_field( $article_data['publisher'] ),
							'logo'  => esc_url( $article_data['publisherLogo'] ),
						);
					}
				}
				break;

			case 'LocalBusiness':
				if ( isset( $_POST['business_schema'] ) ) {
					$business_data = $_POST['business_schema'];

					$schema_data['name']        = sanitize_text_field( $business_data['name'] );
					$schema_data['description'] = sanitize_textarea_field( $business_data['description'] );
					$schema_data['address']     = array(
						'@type'           => 'PostalAddress',
						'streetAddress'   => sanitize_text_field( $business_data['street'] ),
						'addressLocality' => sanitize_text_field( $business_data['city'] ),
						'addressRegion'   => sanitize_text_field( $business_data['region'] ),
						'postalCode'      => sanitize_text_field( $business_data['postal'] ),
						'addressCountry'  => sanitize_text_field( $business_data['country'] ),
					);

					if ( ! empty( $business_data['phone'] ) ) {
						$schema_data['telephone'] = sanitize_text_field( $business_data['phone'] );
					}
					if ( ! empty( $business_data['email'] ) ) {
						$schema_data['email'] = sanitize_email( $business_data['email'] );
					}
					if ( ! empty( $business_data['website'] ) ) {
						$schema_data['url'] = esc_url( $business_data['website'] );
					}

					// Save business type
					if ( ! empty( $business_data['type'] ) ) {
						$schema_data['@type'] = sanitize_text_field( $business_data['type'] );
					}

					// Ensure opening hours are saved correctly
					if ( isset( $business_data['hours'] ) ) {

						$hours_data    = $business_data['hours'];
						$opening_hours = array();

						// Map full day names to abbreviations
						$day_map = array(
							'monday'    => __( 'Mo', 'ultimakit-for-wp' ),
							'tuesday'   => __( 'Tu', 'ultimakit-for-wp' ),
							'wednesday' => __( 'We', 'ultimakit-for-wp' ),
							'thursday'  => __( 'Th', 'ultimakit-for-wp' ),
							'friday'    => __( 'Fr', 'ultimakit-for-wp' ),
							'saturday'  => __( 'Sa', 'ultimakit-for-wp' ),
							'sunday'    => __( 'Su', 'ultimakit-for-wp' ),
						);
						foreach ( $hours_data as $day => $hours ) {
							if ( isset( $hours['open'] ) && $hours['open'] ) {
								$from = sanitize_text_field( $hours['from'] );
								$to   = sanitize_text_field( $hours['to'] );
								if ( $from && $to ) {
									$opening_hours[ $day ] = array(
										'abbr' => $day_map[ $day ],
										'from' => $from,
										'to'   => $to,
									);
								}
							}
						}

						// Group days with same hours
						$hour_groups = array();
						foreach ( $opening_hours as $day_data ) {
							$time_range = $day_data['from'] . '-' . $day_data['to'];
							if ( ! isset( $hour_groups[ $time_range ] ) ) {
								$hour_groups[ $time_range ] = array();
							}
							$hour_groups[ $time_range ][] = $day_data['abbr'];
						}

						// Format opening hours strings
						$formatted_hours = array();
						foreach ( $hour_groups as $time_range => $days ) {
							$formatted_hours[] = implode( ',', $days ) . ' ' . $time_range;
						}

						$schema_data['openingHours'] = $formatted_hours;

					}
					if ( ! empty( $business_data['priceRange'] ) ) {
						$schema_data['priceRange'] = sanitize_text_field( $business_data['priceRange'] );
					}
				}
				break;
		}

		// Save the complete schema data
		if ( ! empty( $schema_data['@type'] ) ) {
			// The values above come from slashed $_POST, and update_post_meta() unslashes what it stores,
			// which stripped the JSON escapes (\" and \uXXXX). Unslash the data, then slash the JSON.
			update_post_meta( $post_id, '_schema_data', wp_slash( wp_json_encode( wp_unslash( $schema_data ), JSON_PRETTY_PRINT ) ) );
		} else {
			delete_post_meta( $post_id, '_schema_data' );
		}
	}

	/**
	 * Sanitize schema data.
	 *
	 * @param string $schema_data JSON schema data.
	 * @return string|false Sanitized JSON or false on error.
	 */
	private function sanitize_schema_data( $schema_data ) {
		$decoded = json_decode( $schema_data, true );
		if ( json_last_error() !== JSON_ERROR_NONE ) {
			return false;
		}

		// Recursively sanitize the array
		$sanitized = $this->sanitize_schema_array( $decoded );

		return wp_json_encode( $sanitized );
	}

	/**
	 * Recursively sanitize schema array.
	 *
	 * @param array $array Schema data array.
	 * @return array Sanitized array.
	 */
	private function sanitize_schema_array( $array ) {
		foreach ( $array as $key => $value ) {
			if ( is_array( $value ) ) {
				$array[ $key ] = $this->sanitize_schema_array( $value );
			} else {
				$array[ $key ] = sanitize_text_field( $value );
			}
		}
		return $array;
	}

	/**
	 * Output schema markup in the head section.
	 */
	public function output_schema_markup() {
		if ( is_singular() ) {
			global $post;
			$schema_data = get_post_meta( $post->ID, '_schema_data', true );
			$schema      = ! empty( $schema_data ) ? json_decode( $schema_data, true ) : null;

			if ( ! empty( $schema ) && is_array( $schema ) ) {
				echo "\n<!-- UltimaKit Schema Markup -->\n";
				// Re-encode rather than running kses over JSON; JSON_HEX_TAG stops "</script>" breaking out.
				echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE ) . "</script>\n";
				echo "<!-- / UltimaKit Schema Markup -->\n\n";
			}
		}
	}

	/**
	 * AJAX handler for schema validation.
	 */
	public function ajax_validate_schema() {
		check_ajax_referer( 'ultimakit_schema_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( __( 'Insufficient permissions', 'ultimakit-for-wp' ) );
		}

		$schema_data = isset( $_POST['schema'] ) ? stripslashes( $_POST['schema'] ) : '';

		if ( empty( $schema_data ) ) {
			wp_send_json_error( __( 'No schema data provided', 'ultimakit-for-wp' ) );
		}

		// Validate JSON syntax
		$decoded = json_decode( $schema_data );

		// Debug information
		$json_error     = json_last_error();
		$json_error_msg = json_last_error_msg();

		if ( $json_error !== JSON_ERROR_NONE ) {
			$error_message = sprintf(
				__( 'JSON Error (%1$d): %2$s. Raw data: %3$s', 'ultimakit-for-wp' ),
				$json_error,
				$json_error_msg,
				substr( $schema_data, 0, 255 ) // First 255 characters of raw data for debugging
			);
			wp_send_json_error( $error_message );
		}

		// Validate schema structure
		$validation_result = $this->validate_schema_structure( $decoded );

		if ( isset( $validation_result['valid'] ) && $validation_result['valid'] ) {
			wp_send_json_success( __( 'Schema validation passed', 'ultimakit-for-wp' ) );
		} else {
			wp_send_json_error( isset( $validation_result['message'] ) ? $validation_result['message'] : 'Error' );
		}
	}

	/**
	 * Validate schema structure.
	 *
	 * @param object $schema Schema data.
	 * @return array Validation result.
	 */
	private function validate_schema_structure( $schema ) {

		// Basic schema validation
		if ( ! isset( $schema->{'@context'} ) || $schema->{'@context'} !== 'https://schema.org' ) {
			return array(
				'valid'   => false,
				'message' => __( 'Invalid @context value. Expected "https://schema.org"', 'ultimakit-for-wp' ),
			);
		}

		if ( ! isset( $schema->{'@type'} ) ) {
			return array(
				'valid'   => false,
				'message' => __( 'Missing @type property', 'ultimakit-for-wp' ),
			);
		}

		if ( ! in_array( $schema->{'@type'}, array_keys( $this->supported_schemas ) ) ) {
			return array(
				'valid'   => false,
				'message' => __( 'Invalid or unsupported @type: ' . $schema->{'@type'}, 'ultimakit-for-wp' ),
			);
		}

		// Type-specific validation
		switch ( $schema->{'@type'} ) {
			case 'FAQPage':
				return $this->validate_faq_schema( $schema );
			case 'Product':
				return $this->validate_product_schema( $schema );
			case 'Review':
				return $this->validate_review_schema( $schema );
			case 'Article':
				return $this->validate_article_schema( $schema );
			case 'LocalBusiness':
				return $this->validate_local_business_schema( $schema );
			default:
				return array(
					'valid'   => true,
					'message' => __( 'Schema structure appears valid', 'ultimakit-for-wp' ),
				);
		}
	}

	/**
	 * Validate Article Schema
	 *
	 * @param object $schema The schema data to validate
	 * @return array Validation result with 'valid' status and 'message'
	 */
	private function validate_article_schema( $schema ) {
		// Check if schema is an object
		if ( ! is_object( $schema ) ) {
			return array(
				'valid'   => false,
				'message' => __( 'Invalid schema format: must be an object', 'ultimakit-for-wp' ),
			);
		}

		// Validate required @context and @type
		if ( ! isset( $schema->{'@context'} ) || $schema->{'@context'} !== 'https://schema.org' ) {
			return array(
				'valid'   => false,
				'message' => __( 'Invalid or missing @context: must be "https://schema.org"', 'ultimakit-for-wp' ),
			);
		}

		// Validate article type
		$validArticleTypes = array(
			__( 'Article', 'ultimakit-for-wp' ),
			__( 'NewsArticle', 'ultimakit-for-wp' ),
			__( 'BlogPosting', 'ultimakit-for-wp' ),
			__( 'TechArticle', 'ultimakit-for-wp' ),
			__( 'ScholarlyArticle', 'ultimakit-for-wp' ),
			__( 'SocialMediaPosting', 'ultimakit-for-wp' ),
		);

		if ( ! isset( $schema->{'@type'} ) || ! in_array( $schema->{'@type'}, $validArticleTypes ) ) {
			return array(
				'valid'   => false,
				'message' => __( 'Invalid @type: must be one of ' . implode( ', ', $validArticleTypes ), 'ultimakit-for-wp' ),
			);
		}

		// Validate required properties
		if ( ! isset( $schema->headline ) || empty( $schema->headline ) ) {
			return array(
				'valid'   => false,
				'message' => __( 'Missing required property: headline', 'ultimakit-for-wp' ),
			);
		}

		// Validate headline length (Google's limit is 110 characters)
		if ( strlen( $schema->headline ) > 110 ) {
			return array(
				'valid'   => false,
				'message' => __( 'Headline must not exceed 110 characters', 'ultimakit-for-wp' ),
			);
		}

		// Validate author
		if ( isset( $schema->author ) ) {
			if ( ! is_object( $schema->author ) ) {
				return array(
					'valid'   => false,
					'message' => __( 'Invalid author structure: must be an object', 'ultimakit-for-wp' ),
				);
			}

			if ( ! isset( $schema->author->{'@type'} ) ||
				! in_array( $schema->author->{'@type'}, array( 'Person', 'Organization' ) ) ) {
				return array(
					'valid'   => false,
					'message' => __( 'Invalid author @type: must be Person or Organization', 'ultimakit-for-wp' ),
				);
			}

			if ( ! isset( $schema->author->name ) || empty( $schema->author->name ) ) {
				return array(
					'valid'   => false,
					'message' => __( 'Missing required author property: name', 'ultimakit-for-wp' ),
				);
			}
		}

		// Validate publisher (required)
		if ( ! isset( $schema->publisher ) || ! is_object( $schema->publisher ) ) {
			return array(
				'valid'   => false,
				'message' => __( 'Missing or invalid publisher', 'ultimakit-for-wp' ),
			);
		}

		if ( ! isset( $schema->publisher->{'@type'} ) || $schema->publisher->{'@type'} !== 'Organization' ) {
			return array(
				'valid'   => false,
				'message' => __( 'Invalid publisher @type: must be Organization', 'ultimakit-for-wp' ),
			);
		}

		if ( ! isset( $schema->publisher->name ) || empty( $schema->publisher->name ) ) {
			return array(
				'valid'   => false,
				'message' => __( 'Missing required publisher property: name', 'ultimakit-for-wp' ),
			);
		}

		// Validate dates
		if ( isset( $schema->datePublished ) ) {
			if ( ! $this->is_valid_date( $schema->datePublished ) ) {
				return array(
					'valid'   => false,
					'message' => __( 'Invalid datePublished format: must be ISO 8601 date', 'ultimakit-for-wp' ),
				);
			}
		}

		if ( isset( $schema->dateModified ) ) {
			if ( ! $this->is_valid_date( $schema->dateModified ) ) {
				return array(
					'valid'   => false,
					'message' => __( 'Invalid dateModified format: must be ISO 8601 date', 'ultimakit-for-wp' ),
				);
			}
		}

		// Validate image
		if ( isset( $schema->image ) ) {
			if ( is_string( $schema->image ) ) {
				if ( ! filter_var( $schema->image, FILTER_VALIDATE_URL ) ) {
					return array(
						'valid'   => false,
						'message' => __( 'Invalid image URL format', 'ultimakit-for-wp' ),
					);
				}
			} elseif ( is_object( $schema->image ) ) {
				if ( ! isset( $schema->image->{'@type'} ) || $schema->image->{'@type'} !== 'ImageObject' ) {
					return array(
						'valid'   => false,
						'message' => __( 'Invalid image object: missing or invalid @type', 'ultimakit-for-wp' ),
					);
				}

				if ( ! isset( $schema->image->url ) || ! filter_var( $schema->image->url, FILTER_VALIDATE_URL ) ) {
					return array(
						'valid'   => false,
						'message' => __( 'Invalid image object: missing or invalid URL', 'ultimakit-for-wp' ),
					);
				}
			} else {
				return array(
					'valid'   => false,
					'message' => __( 'Invalid image format: must be URL string or ImageObject', 'ultimakit-for-wp' ),
				);
			}
		}

		// Validate mainEntityOfPage if present
		if ( isset( $schema->mainEntityOfPage ) ) {
			if ( is_string( $schema->mainEntityOfPage ) ) {
				if ( ! filter_var( $schema->mainEntityOfPage, FILTER_VALIDATE_URL ) ) {
					return array(
						'valid'   => false,
						'message' => __( 'Invalid mainEntityOfPage URL format', 'ultimakit-for-wp' ),
					);
				}
			} elseif ( is_object( $schema->mainEntityOfPage ) ) {
				if ( ! isset( $schema->mainEntityOfPage->{'@type'} ) ||
					! in_array( $schema->mainEntityOfPage->{'@type'}, array( 'WebPage', 'URL' ) ) ) {
					return array(
						'valid'   => false,
						'message' => __( 'Invalid mainEntityOfPage object: invalid @type', 'ultimakit-for-wp' ),
					);
				}
			}
		}

		// Validate articleBody if present
		if ( isset( $schema->articleBody ) && empty( $schema->articleBody ) ) {
			return array(
				'valid'   => false,
				'message' => __( 'Article body cannot be empty if provided', 'ultimakit-for-wp' ),
			);
		}

		// If all validations pass
		return array(
			'valid'   => true,
			'message' => __( 'Article schema structure is valid', 'ultimakit-for-wp' ),
		);
	}

	/**
	 * Helper method to validate ISO 8601 date format
	 *
	 * @param string $date Date string to validate
	 * @return boolean
	 */
	private function is_valid_date( $date ) {
		if ( ! is_string( $date ) ) {
			return false;
		}

		try {
			$dateTime = new DateTime( $date );
			// Accept any valid ISO 8601 format
			return (bool) strtotime( $date );
		} catch ( Exception $e ) {
			return false;
		}
	}

	/**
	 * Validate FAQ schema structure.
	 *
	 * @param object $schema Schema data.
	 * @return array Validation result.
	 */
	private function validate_faq_schema( $schema ) {
		if ( ! isset( $schema->mainEntity ) || ! is_array( $schema->mainEntity ) ) {
			return array(
				'valid'   => false,
				'message' => __( 'FAQ schema must have mainEntity array', 'ultimakit-for-wp' ),
			);
		}

		foreach ( $schema->mainEntity as $item ) {
			if ( ! isset( $item->{'@type'} ) || $item->{'@type'} !== 'Question' ) {
				return array(
					'valid'   => false,
					'message' => __( 'Each FAQ item must have @type: Question', 'ultimakit-for-wp' ),
				);
			}

			if ( ! isset( $item->name ) || empty( $item->name ) ) {
				return array(
					'valid'   => false,
					'message' => __( 'Each Question must have a name (question text)', 'ultimakit-for-wp' ),
				);
			}

			if ( ! isset( $item->acceptedAnswer ) || ! isset( $item->acceptedAnswer->{'@type'} ) ||
				$item->acceptedAnswer->{'@type'} !== 'Answer' ||
				! isset( $item->acceptedAnswer->text ) ||
				empty( $item->acceptedAnswer->text ) ) {
				return array(
					'valid'   => false,
					'message' => __( 'Each Question must have a valid acceptedAnswer with @type: Answer and text', 'ultimakit-for-wp' ),
				);
			}
		}

		return array(
			'valid'   => true,
			'message' => __( 'FAQ schema structure is valid', 'ultimakit-for-wp' ),
		);
	}

	/**
	 * Validate Product schema structure.
	 *
	 * @param object $schema Schema data.
	 * @return array Validation result.
	 */
	private function validate_product_schema( $schema ) {
		$required_fields = array( 'name', 'description', 'offers' );

		foreach ( $required_fields as $field ) {
			if ( ! isset( $schema->$field ) ) {
				return array(
					'valid'   => false,
					'message' => __( "Missing required field: {$field}", 'ultimakit-for-wp' ),
				);
			}
		}

		if ( ! isset( $schema->offers->{'@type'} ) || $schema->offers->{'@type'} !== 'Offer' ) {
			return array(
				'valid'   => false,
				'message' => __( 'Invalid offers structure', 'ultimakit-for-wp' ),
			);
		}

		return array(
			'valid'   => true,
			'message' => __( 'Product schema structure is valid', 'ultimakit-for-wp' ),
		);
	}

	/**
	 * Validate Review schema structure.
	 *
	 * @param object $schema Schema data.
	 * @return array Validation result.
	 */
	private function validate_review_schema( $schema ) {
		$required_fields = array( 'itemReviewed', 'author', 'reviewRating', 'reviewBody' );

		foreach ( $required_fields as $field ) {
			if ( ! isset( $schema->$field ) ) {
				return array(
					'valid'   => false,
					'message' => __( "Missing required field: {$field}", 'ultimakit-for-wp' ),
				);
			}
		}

		if ( ! isset( $schema->reviewRating->{'@type'} ) ||
			$schema->reviewRating->{'@type'} !== 'Rating' ||
			! isset( $schema->reviewRating->ratingValue ) ) {
			return array(
				'valid'   => false,
				'message' => __( 'Invalid review rating structure', 'ultimakit-for-wp' ),
			);
		}

		return array(
			'valid'   => true,
			'message' => __( 'Review schema structure is valid', 'ultimakit-for-wp' ),
		);
	}

	/**
	 * Enqueue necessary scripts and styles.
	 */
	private function enqueue_scripts() {
		wp_enqueue_style(
			'ultimakit-schema-generator',
			plugin_dir_url( __FILE__ ) . 'module-style.css',
			array(),
			'1.0.0'
		);

		wp_enqueue_script(
			'ultimakit-schema-generator',
			plugin_dir_url( __FILE__ ) . 'module-script.js',
			array( 'jquery' ),
			'1.0.0',
			true
		);

		wp_localize_script(
			'ultimakit-schema-generator',
			'ultimakitSchema',
			array(
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'ultimakit_schema_nonce' ),
				'strings' => array(
					'validationSuccess' => __( 'Schema validation passed successfully!', 'ultimakit-for-wp' ),
					'validationError'   => __( 'Schema validation failed: ', 'ultimakit-for-wp' ),
					'confirmDelete'     => __( 'Are you sure you want to delete this item?', 'ultimakit-for-wp' ),
				),
			)
		);
	}


	/**
	 * Validate Local Business Schema
	 *
	 * @param mixed $schema The schema data to validate
	 * @return array Validation result with 'valid' status and 'message'
	 */
	private function validate_local_business_schema( $schema ) {
		// Check if schema is an object
		if ( ! is_object( $schema ) ) {
			return array(
				'valid'   => false,
				'message' => __( 'Invalid schema format: must be an object', 'ultimakit-for-wp' ),
			);
		}

		// Validate required properties
		if ( ! isset( $schema->{'@type'} ) ) {
			return array(
				'valid'   => false,
				'message' => __( 'Missing required property: @type', 'ultimakit-for-wp' ),
			);
		}

		if ( ! isset( $schema->{'@context'} ) || $schema->{'@context'} !== 'https://schema.org' ) {
			return array(
				'valid'   => false,
				'message' => __( 'Invalid or missing @context: must be "https://schema.org"', 'ultimakit-for-wp' ),
			);
		}

		// Validate business type
		$validBusinessTypes = array(
			'LocalBusiness',
			'Store',
			'Restaurant',
			'Hotel',
			'Service',
			'MedicalBusiness',
			'AutomotiveBusiness',
			'FinancialService',
			'SportsActivityLocation',
			'EducationalOrganization',
			'ProfessionalService',
		);

		if ( ! in_array( $schema->{'@type'}, $validBusinessTypes ) ) {
			return array(
				'valid'   => false,
				'message' => __( 'Invalid @type: must be a valid LocalBusiness type', 'ultimakit-for-wp' ),
			);
		}

		// Validate name (required)
		if ( ! isset( $schema->name ) || empty( $schema->name ) ) {
			return array(
				'valid'   => false,
				'message' => __( 'Missing required property: name', 'ultimakit-for-wp' ),
			);
		}

		// Validate address structure if present
		if ( isset( $schema->address ) ) {
			if ( ! isset( $schema->address->{'@type'} ) || $schema->address->{'@type'} !== 'PostalAddress' ) {
				return array(
					'valid'   => false,
					'message' => __( 'Invalid address structure: missing or invalid @type', 'ultimakit-for-wp' ),
				);
			}

			// Check for required address fields
			$requiredAddressFields = array( 'streetAddress', 'addressLocality', 'addressRegion', 'postalCode' );
			foreach ( $requiredAddressFields as $field ) {
				if ( ! isset( $schema->address->$field ) || empty( $schema->address->$field ) ) {
					return array(
						'valid'   => false,
						'message' => __( "Missing required address field: $field", 'ultimakit-for-wp' ),
					);
				}
			}
		}

		// Validate opening hours if present
		if ( isset( $schema->openingHoursSpecification ) ) {
			if ( ! is_array( $schema->openingHoursSpecification ) && ! is_object( $schema->openingHoursSpecification ) ) {
				return array(
					'valid'   => false,
					'message' => __( 'Invalid openingHoursSpecification: must be an array or object', 'ultimakit-for-wp' ),
				);
			}

			foreach ( $schema->openingHoursSpecification as $hours ) {
				if ( ! isset( $hours->{'@type'} ) || $hours->{'@type'} !== 'OpeningHoursSpecification' ) {
					return array(
						'valid'   => false,
						'message' => __( 'Invalid opening hours structure: missing or invalid @type', 'ultimakit-for-wp' ),
					);
				}

				if ( ! isset( $hours->dayOfWeek ) || ! isset( $hours->opens ) || ! isset( $hours->closes ) ) {
					return array(
						'valid'   => false,
						'message' => __( 'Missing required opening hours fields: dayOfWeek, opens, or closes', 'ultimakit-for-wp' ),
					);
				}
			}
		}

		// Validate geo coordinates if present
		if ( isset( $schema->geo ) ) {
			if ( ! isset( $schema->geo->{'@type'} ) || $schema->geo->{'@type'} !== 'GeoCoordinates' ) {
				return array(
					'valid'   => false,
					'message' => __( 'Invalid geo structure: missing or invalid @type', 'ultimakit-for-wp' ),
				);
			}

			if ( ! isset( $schema->geo->latitude ) || ! isset( $schema->geo->longitude ) ) {
				return array(
					'valid'   => false,
					'message' => __( 'Missing required geo fields: latitude or longitude', 'ultimakit-for-wp' ),
				);
			}
		}

		// Validate contact information if present
		if ( isset( $schema->email ) && ! is_email( $schema->email ) ) {
			return array(
				'valid'   => false,
				'message' => __( 'Invalid email format', 'ultimakit-for-wp' ),
			);
		}

		// Validate contact information if present
		if ( isset( $schema->telephone ) ) {
			// Remove all non-numeric characters except +
			$phone = preg_replace( '/[^0-9+]/', '', $schema->telephone );

			// Check if the cleaned phone number matches international format
			if ( ! preg_match( '/^\+?[0-9]{10,15}$/', $phone ) ) {
				return array(
					'valid'   => false,
					'message' => __( 'Invalid telephone format. Please use international format', 'ultimakit-for-wp' ),
				);
			}
		}

		// Validate URL if present
		if ( isset( $schema->url ) && ! filter_var( $schema->url, FILTER_VALIDATE_URL ) ) {
			return array(
				'valid'   => false,
				'message' => __( 'Invalid URL format', 'ultimakit-for-wp' ),
			);
		}

		// Validate price range if present
		if ( isset( $schema->priceRange ) ) {
			$validPriceRanges = array( '$', '$$', '$$$', '$$$$' );
			if ( ! in_array( $schema->priceRange, $validPriceRanges ) ) {
				return array(
					'valid'   => false,
					'message' => __( 'Invalid priceRange: must be $, $$, $$$, or $$$$', 'ultimakit-for-wp' ),
				);
			}
		}

		// Validate social media URLs if present
		if ( isset( $schema->sameAs ) ) {
			if ( ! is_array( $schema->sameAs ) ) {
				return array(
					'valid'   => false,
					'message' => __( 'Invalid sameAs: must be an array of URLs', 'ultimakit-for-wp' ),
				);
			}

			foreach ( $schema->sameAs as $url ) {
				if ( ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
					return array(
						'valid'   => false,
						'message' => __( 'Invalid social media URL format', 'ultimakit-for-wp' ),
					);
				}
			}
		}

		// If all validations pass
		return array(
			'valid'   => true,
			'message' => __( 'Local Business schema structure is valid', 'ultimakit-for-wp' ),
		);
	}

	/**
	 * Convert stdClass object to array recursively
	 *
	 * @param mixed $data The data to convert
	 * @return array
	 */
	private function convert_to_array( $data ) {
		if ( is_object( $data ) ) {
			$data = json_decode( json_encode( $data ), true );
		}
		return (array) $data;
	}

	/**
	 * Safely get value from schema data
	 *
	 * @param array|object $schema The schema data
	 * @param string $key The key to get
	 * @param mixed $default Default value if key doesn't exist
	 * @return mixed
	 */
	private function get_schema_value( $schema, $key, $default = null ) {
		if ( is_object( $schema ) ) {
			return property_exists( $schema, $key ) ? $schema->$key : $default;
		}
		return isset( $schema[ $key ] ) ? $schema[ $key ] : $default;
	}

	/**
	 * Recursively remove empty elements from an array
	 *
	 * @param array $array The array to clean
	 * @return array Cleaned array
	 */
	private function remove_empty_elements( $array ) {
		foreach ( $array as $key => $value ) {
			if ( is_array( $value ) ) {
				$array[ $key ] = $this->remove_empty_elements( $value );
			}

			if ( empty( $array[ $key ] ) && $array[ $key ] !== 0 ) {
				unset( $array[ $key ] );
			}
		}

		return $array;
	}
}
