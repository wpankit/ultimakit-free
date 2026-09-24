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

	    $('.ultimakit_module_activity_logs').on('click', function (e) {
	        e.preventDefault(); // Prevent the default action of the click event
	        $("#ultimakit_module_activity_logs_modal").modal('show'); // Show the modal
	    });

		$('#delete-logs-button').on('click', function (e) {
	        e.preventDefault(); // Prevent the default action of the click event
	        
			if (!confirm(ultimakitL10n.confirmDeleteAll)) {
				return;
			}
		
			jQuery.ajax({
				url: ajaxurl,
				type: 'POST',
				data: {
					action: 'ultimakit_delete_all_logs',
					nonce: ultimakitL10n.deleteLogsNonce
				},
				beforeSend: function() {
					// Show loading state
					jQuery('#delete-logs-button').prop('disabled', true);
				},
				success: function(response) {
					if (response.success) {
						// Show success message
						alert(response.data.message);
						// Reload the page or update the logs table
						location.reload();
					} else {
						// Show error message
						alert(response.data.message);
					}
				},
				error: function() {
					alert(ultimakitL10n.ajaxError);
				},
				complete: function() {
					// Reset button state
					jQuery('#delete-logs-button').prop('disabled', false);
				}
			});
	    });

			
	});


})( jQuery );