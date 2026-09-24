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

	    $('.ultimakit_module_smtp_email').on('click', function (e) {
	        e.preventDefault(); // Prevent the default action of the click event
	        $("#ultimakit_module_smtp_email_modal").modal('show'); // Show the modal
	    });


	    $('.module_template').on('click', function (e) {
	        e.preventDefault(); // Prevent the default action of the click event

	        /*Ajax Start*/
			jQuery.ajax({
				url:ultimakit_smtp_email.ajax_url,
				type: 'get',
				beforeSend: function() {
	                $('body').css('cursor', 'progress');
	            },
	            complete: function() {
			        $('body').css('cursor', 'default');
			    },
				data: {
					'action': 'module_template_action',
					'nonce' : ultimakit_smtp_email.ajax_nonce 
				},success: function (response) {
				}	
			});
			/*Ajax end here*/ 
	    });

	   
	    

	});


})( jQuery );