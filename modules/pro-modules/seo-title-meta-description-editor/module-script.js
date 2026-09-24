/**
 * This is the javascript file for the module.
 *
 * @package UltimaKit_
 */

(function ( $ ) {
	'use strict';

	/**
	 * All of the code for your admin-facing JavaScript source
	 * should reside in this file.
	 *
	 * Note: It has been assumed you will write jQuery code here, so the
	 * $ function reference has been prepared for usage within the scope
	 * of this function.
	 *
	 * This enables you to define handlers, for when the DOM is ready:
	 *
	 * $(function() {
	 *
	 * });
	 *
	 * When the window is loaded:
	 *
	 * $( window ).load(function() {
	 *
	 * });
	 *
	 * ...and/or other possibilities.
	 *
	 * Ideally, it is not considered best practise to attach more than a
	 * single DOM-ready or window-load handler for a particular page.
	 * Although scripts in the WordPress core, Plugins and Themes may be
	 * practising this, we should strive to set a better example in our own work.
	 */

	jQuery(document).ready(function ($) {

	    $('.ultimakit_seo_title_meta_description_editor').on('click', function (e) {
	        e.preventDefault(); // Prevent the default action of the click event
	        $("#ultimakit_seo_title_meta_description_editor_modal").modal('show'); // Show the modal
	    });

		const titleLimit = 60;
		const descriptionLimit = 160;

		// Function to update character counter.
		function updateCounter(input, counter, limit) {
			const remaining = limit - $(input).val().length;
			if( remaining < 0){
				remaining = 0;
			}
			$(counter).text(remaining + ' characters remaining');
		}

		// Bind the input events to update the counters.
		$('#ultimakit_seo_title').on('input', function() {
			updateCounter(this, '#seo-title-counter', titleLimit);
		});

		$('#ultimakit_seo_description').on('input', function() {
			updateCounter(this, '#seo-description-counter', descriptionLimit);
		});

		// Initialize counters on page load.
		updateCounter('#ultimakit_seo_title', '#seo-title-counter', titleLimit);
		updateCounter('#ultimakit_seo_description', '#seo-description-counter', descriptionLimit);

	});


})( jQuery );