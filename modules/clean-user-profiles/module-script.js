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

	    $('.ultimakit_clean_user_profiles').on('click', function (e) {
	        e.preventDefault(); // Prevent the default action of the click event
	        $("#ultimakit_clean_user_profiles_modal").modal('show'); // Show the modal
	    });

	});


	jQuery(document).ready(function ($) {
	    // Example JSON string
	    var jsonString = ultimakit_clean_user_profiles.sections;

	    // Parse the JSON string to an actual JavaScript array
	    var arrayFromJson = JSON.parse(jsonString);

	    // Now that it's an array, you can safely iterate over it
	    $.each(arrayFromJson, function(index, value) {
	        $('.' + value).css('display', 'none');
	    });
	});



})( jQuery );