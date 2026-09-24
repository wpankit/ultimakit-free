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

		if( $('.ultimakit_module_change_admin_email_form').length > 0 ){

		    $('.ultimakit_module_change_admin_email').on('click', function (e) {
		        e.preventDefault(); // Prevent the default action of the click event
		        $("#ultimakit_module_change_admin_email_modal").modal('show'); // Show the modal
		    });


		    $('.ultimakit_module_change_admin_email_form').on('click', function (e) {
		        e.preventDefault(); // Prevent the default action of the click event
		        
		        var admin_email = $("#admin_email").val();

		        const toastConf = {
					timeOut: 1000, // Adjust display time as needed (in milliseconds).
					positionClass: 'toast-top-center', // Adjust position as needed.
					progressBar: true, // Show a progress bar.
					closeButton: true,
					preventDuplicates: true,
					iconClasses: {
						success: "toast-success",
				        warning: "toast-warning" // Specify a single CSS class for warning messages.
				    },
				};

				var form_data = new FormData();
		        form_data.append( 'action', 'ultimakit_update_admin_email' ); // WordPress AJAX action
		        form_data.append( 'nonce', ultimakit_change_admin_email.ajax_nonce ); // Security nonce
		        form_data.append( 'admin_email', admin_email ); // Admin email

		        $.ajax({
		            url: ultimakit_change_admin_email.ajax_url, // WordPress admin AJAX URL
		            type: 'POST',
		            contentType: false,
		            processData: false,
		            data: form_data,
		            success: function (response) {
		            	if( response.success == true ){
		            		toastr.success( response.data, '', toastConf );
		            	}

		            	setTimeout(function(){
		            		window.location.reload();
		            	},1000);
		            },
		            error: function (response) {
		                toastr.error( response.data, '', toastConf );
		            }
		        });

		       
		    });

	   }
	    

	});


})( jQuery );