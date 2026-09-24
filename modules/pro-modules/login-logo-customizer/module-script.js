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

		$('.ultimakit_module_login_logo_customizer').on('click', function (e) {
			e.preventDefault(); // Prevent the default action of the click event
			$("#ultimakit_module_login_logo_customizer_modal").modal('show'); // Show the modal
		});

		var mediaUploader;
		$('#wpuk_file_upload_llc').on('click', function(e) {
			e.preventDefault();
			// If the uploader object has already been created, reopen the dialog
			if (mediaUploader) {
				mediaUploader.open();
				return;
			}
			// Extend the wp.media object
			mediaUploader = wp.media.frames.file_frame = wp.media({
				title: 'Choose Logo',
				button: {
					text: 'Use this logo'
				},
				multiple: false
			});
			// When a file is selected, grab the URL and set it as the input value
			mediaUploader.on('select', function() {
				var attachment = mediaUploader.state().get('selection').first().toJSON();
				$('#login_logo_url').val(attachment.url);
				$('#wpuk_img_placeholder').prop('src',attachment.url);
			});
			// Open the uploader dialog
			mediaUploader.open();
		});
	    

	});


})( jQuery );