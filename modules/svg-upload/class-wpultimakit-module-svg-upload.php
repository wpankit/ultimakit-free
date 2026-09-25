<?php
/**
 * Class UltimaKit_Module_Hide_Admin_Bar
 *
 * @since 1.0.0
 * @package    UltimaKit
 */

/**
 * Class UltimaKit_Module_Enhance_List_Table
 *
 * @since 1.0.0
 */
class UltimaKit_Module_SVG_Upload extends UltimaKit_Module_Manager {
	/**
	 * @var string
	 */
	protected $ID = 'ultimakit_svg_upload';

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
	protected $read_more_link = 'svg-upload-in-wordpress';

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
		$this->name        = __( 'SVG Upload', 'ultimakit-for-wp' );
		$this->description = __( 'Files can only be uploaded by logged in users.', 'ultimakit-for-wp' );
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
			add_filter( 'wp_check_filetype_and_ext', array( $this, 'ultimakit_svg_upload' ), 10, 4 );
			add_filter( 'upload_mimes', array( $this, 'ultimakit_cc_mime_types' ) );
			add_filter( 'wp_handle_upload_prefilter', array( $this, 'ultimakit_sanitize_svg_upload' ) );
		}
	}

	public function ultimakit_svg_upload( $data, $file, $filename, $mimes ) {
		$filetype = wp_check_filetype( $filename, $mimes );

		/*
		 * Only take over the result for SVG. Returning our own array for every upload
		 * discarded WordPress's finfo-based content-vs-extension verification site-wide,
		 * which is what stops a disguised file being accepted under a trusted extension.
		 */
		if ( 'svg' !== $filetype['ext'] ) {
			return $data;
		}

		return array(
			'ext'             => $filetype['ext'],
			'type'            => $filetype['type'],
			'proper_filename' => $data['proper_filename'],
		);
	}

	public function ultimakit_cc_mime_types( $mimes ) {
		$mimes['svg'] = 'image/svg+xml';
		return $mimes;
	}

	/**
	 * Sanitize an uploaded SVG before WordPress stores it.
	 *
	 * SVG is executable markup: an unsanitized file can carry <script>, on* handlers or
	 * javascript: URLs that run in the site's origin when the file is opened, which turns
	 * any upload-capable account into stored XSS against an administrator.
	 *
	 * @param array $file Entry from $_FILES as passed through wp_handle_upload_prefilter.
	 * @return array
	 */
	public function ultimakit_sanitize_svg_upload( $file ) {
		if ( ! empty( $file['error'] ) || empty( $file['tmp_name'] ) ) {
			return $file;
		}

		$filetype = wp_check_filetype( isset( $file['name'] ) ? $file['name'] : '' );
		$is_svg   = ( isset( $filetype['ext'] ) && 'svg' === $filetype['ext'] )
			|| ( isset( $file['type'] ) && in_array( $file['type'], array( 'image/svg+xml', 'image/svg' ), true ) );

		if ( ! $is_svg ) {
			return $file;
		}

		if ( ! class_exists( 'DOMDocument' ) ) {
			$file['error'] = __( 'SVG uploads require the PHP DOM extension so the file can be sanitized.', 'ultimakit-for-wp' );
			return $file;
		}

		$markup = file_get_contents( $file['tmp_name'] );

		if ( false === $markup || '' === trim( (string) $markup ) ) {
			$file['error'] = __( 'The SVG file could not be read.', 'ultimakit-for-wp' );
			return $file;
		}

		$clean = $this->ultimakit_sanitize_svg_markup( $markup );

		if ( null === $clean ) {
			$file['error'] = __( 'This SVG could not be sanitized and was rejected.', 'ultimakit-for-wp' );
			return $file;
		}

		file_put_contents( $file['tmp_name'], $clean );

		return $file;
	}

	/**
	 * Strip everything executable out of SVG markup.
	 *
	 * @param string $markup Raw SVG.
	 * @return string|null Sanitized SVG, or null when it cannot be parsed.
	 */
	protected function ultimakit_sanitize_svg_markup( $markup ) {
		// Remove any doctype up front: it is the vehicle for entity-expansion attacks.
		$markup = preg_replace( '/<!DOCTYPE[^>]*+>/i', '', $markup );
		$markup = preg_replace( '/<!ENTITY[^>]*+>/i', '', $markup );

		$previous_errors = libxml_use_internal_errors( true );

		$dom                     = new DOMDocument();
		$dom->preserveWhiteSpace = false;

		// LIBXML_NONET blocks network access; entity substitution stays off by omitting LIBXML_NOENT.
		$loaded = $dom->loadXML( $markup, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING );

		libxml_clear_errors();
		libxml_use_internal_errors( $previous_errors );

		if ( ! $loaded || ! $dom->documentElement ) {
			return null;
		}

		if ( 'svg' !== strtolower( $dom->documentElement->localName ) ) {
			return null;
		}

		$this->ultimakit_scrub_svg_node( $dom->documentElement );

		$output = $dom->saveXML( $dom->documentElement );

		return ( false === $output ) ? null : $output;
	}

	/**
	 * Recursively remove disallowed elements and attributes.
	 *
	 * @param DOMElement $element Element to scrub.
	 * @return void
	 */
	protected function ultimakit_scrub_svg_node( $element ) {
		static $allowed_elements = array(
			'svg',
			'g',
			'defs',
			'symbol',
			'use',
			'title',
			'desc',
			'metadata',
			'switch',
			'a',
			'style',
			'path',
			'rect',
			'circle',
			'ellipse',
			'line',
			'polyline',
			'polygon',
			'text',
			'tspan',
			'textpath',
			'image',
			'clippath',
			'mask',
			'pattern',
			'marker',
			'lineargradient',
			'radialgradient',
			'stop',
			'filter',
			'fegaussianblur',
			'feoffset',
			'feblend',
			'fecolormatrix',
			'fecomposite',
			'feflood',
			'femerge',
			'femergenode',
			'femorphology',
			'fedropshadow',
			'feturbulence',
			'fedisplacementmap',
			'fetile',
			'fespecularlighting',
			'fediffuselighting',
			'fepointlight',
			'fedistantlight',
			'fespotlight',
			'fecomponenttransfer',
			'fefuncr',
			'fefuncg',
			'fefuncb',
			'fefunca',
		);

		// Walk a static list: the live NodeList shifts as nodes are removed.
		$children = array();
		foreach ( $element->childNodes as $child ) {
			$children[] = $child;
		}

		foreach ( $children as $child ) {
			if ( XML_PI_NODE === $child->nodeType ) {
				$element->removeChild( $child );
				continue;
			}

			if ( XML_ELEMENT_NODE !== $child->nodeType ) {
				continue;
			}

			if ( ! in_array( strtolower( $child->localName ), $allowed_elements, true ) ) {
				$element->removeChild( $child );
				continue;
			}

			if ( 'style' === strtolower( $child->localName ) && $this->ultimakit_css_is_unsafe( $child->textContent ) ) {
				$element->removeChild( $child );
				continue;
			}

			$this->ultimakit_scrub_svg_node( $child );
		}

		$this->ultimakit_scrub_svg_attributes( $element );
	}

	/**
	 * Remove event handlers and unsafe URL/style attributes from one element.
	 *
	 * @param DOMElement $element Element to scrub.
	 * @return void
	 */
	protected function ultimakit_scrub_svg_attributes( $element ) {
		if ( ! $element->hasAttributes() ) {
			return;
		}

		$attributes = array();
		foreach ( $element->attributes as $attribute ) {
			$attributes[] = $attribute;
		}

		foreach ( $attributes as $attribute ) {
			$name  = strtolower( $attribute->nodeName );
			$local = strtolower( $attribute->localName );
			$value = $attribute->nodeValue;

			// Any on* handler.
			if ( 0 === strpos( $name, 'on' ) ) {
				$element->removeAttributeNode( $attribute );
				continue;
			}

			if ( 'style' === $local && $this->ultimakit_css_is_unsafe( $value ) ) {
				$element->removeAttributeNode( $attribute );
				continue;
			}

			if ( in_array( $local, array( 'href', 'src', 'from', 'to', 'values', 'begin', 'attributename' ), true )
				&& $this->ultimakit_url_is_unsafe( $value ) ) {
				$element->removeAttributeNode( $attribute );
				continue;
			}
		}
	}

	/**
	 * @param string $value CSS text.
	 * @return bool
	 */
	protected function ultimakit_css_is_unsafe( $value ) {
		$value = strtolower( (string) $value );
		$value = preg_replace( '/\s+/', '', $value );

		foreach ( array( 'javascript:', 'expression(', '@import', 'behavior:', '-moz-binding' ) as $needle ) {
			if ( false !== strpos( $value, $needle ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @param string $value Attribute value.
	 * @return bool
	 */
	protected function ultimakit_url_is_unsafe( $value ) {
		// Strip whitespace and control characters used to smuggle "java\nscript:".
		$normalized = strtolower( preg_replace( '/[\s\x00-\x1F\x7F]+/', '', (string) $value ) );

		if ( 0 === strpos( $normalized, 'javascript:' ) || 0 === strpos( $normalized, 'vbscript:' ) ) {
			return true;
		}

		// Allow inline raster images, reject data: URLs that can carry markup or script.
		if ( 0 === strpos( $normalized, 'data:' ) ) {
			return ! preg_match( '#^data:image/(png|jpe?g|gif|webp);base64,#', $normalized );
		}

		return false;
	}
}
