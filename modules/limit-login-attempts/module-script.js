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

	    $('.ultimakit_module_limit_login_attempts').on('click', function (e) {
	        e.preventDefault(); // Prevent the default action of the click event
	        $("#ultimakit_module_limit_login_attempts_modal").modal('show'); // Show the modal
	    });


	    $('.ultimakit_user_block_list').on('click', function (e) {
	        e.preventDefault(); // Prevent the default action of the click event

	        var ip = jQuery(this).attr('data-ip');
	        var id = jQuery(this).attr('id');

	        if (confirm("Are you sure?")) {} else {
	            return false;
	        }

	        /*Ajax Start*/
			jQuery.ajax({
				url:ultimakit_limit_login.ajax_url,
				type: 'get',
				beforeSend: function() {
	                $('.ultimakit_user_block_list').prop('disabled', true);
	                $('body').css('cursor', 'progress');
	            },
	            complete: function() {
			        $('body').css('cursor', 'default');
			    },
				data: {
					'action': 'ultimakit_unblock_user',
					'ip': ip,
					'nonce' : ultimakit_limit_login.ajax_nonce 
				},success: function (response) {
					alert('User successfully unblocked.');
					setTimeout(function(){
						window.location.reload();
					},1000);
				}	
			});
			
	    });
	});


})( jQuery );