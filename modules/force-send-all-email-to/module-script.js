/**
 * This is the javascript file for the Force Send All Email To module.
 *
 * @package UltimaKit
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

	    // Handle modal opening for Force Send All Email To module
	    $('.ultimakit_force_send_all_email_to').on('click', function (e) {
	        e.preventDefault(); // Prevent the default action of the click event
	        $("#ultimakit_force_send_all_email_to_modal").modal('show'); // Show the modal
	    });

	    // Handle form submission for Force Send All Email To module
	    $('.ultimakit_force_send_all_email_to_form').on('submit', function (e) {
	        e.preventDefault(); // Prevent the default form submission

	        var formData = $(this).serialize();
	        var $submitButton = $(this).find('button[type="submit"]');
	        var originalText = $submitButton.text();

	        // Show loading state
	        $submitButton.prop('disabled', true).text('Saving...');

	        /*Ajax Start*/
			jQuery.ajax({
				url: ultimakit_force_send_all_email_to.ajax_url,
				type: 'POST',
				data: formData + '&action=ultimakit_update_settings&nonce=' + ultimakit_force_send_all_email_to.ajax_nonce,
				beforeSend: function() {
	                $('body').css('cursor', 'progress');
	            },
	            complete: function() {
			        $('body').css('cursor', 'default');
			        $submitButton.prop('disabled', false).text(originalText);
			    },
				success: function (response) {
					if (response.success) {
						// Show success message
						$('#ultimakit_force_send_all_email_to_modal .modal-body').prepend(
							'<div class="notice notice-success is-dismissible"><p>Settings saved successfully!</p></div>'
						);
						
						// Auto-dismiss the modal after 2 seconds
						setTimeout(function() {
							$("#ultimakit_force_send_all_email_to_modal").modal('hide');
						}, 2000);
					} else {
						// Show error message
						$('#ultimakit_force_send_all_email_to_modal .modal-body').prepend(
							'<div class="notice notice-error is-dismissible"><p>Error saving settings. Please try again.</p></div>'
						);
					}
				},
				error: function() {
					// Show error message
					$('#ultimakit_force_send_all_email_to_modal .modal-body').prepend(
						'<div class="notice notice-error is-dismissible"><p>Error saving settings. Please try again.</p></div>'
					);
				}
			});
			/*Ajax end here*/ 
	    });

	    // Handle warning notice dismissal
	    $('.notice-dismiss').on('click', function() {
	        $(this).parent().fadeOut();
	    });

	    // Email validation for target email field
	    $('#target_email').on('blur', function() {
	        var email = $(this).val();
	        var $field = $(this);
	        var $errorMsg = $field.siblings('.error-message');
	        
	        if (email && !isValidEmail(email)) {
	            if ($errorMsg.length === 0) {
	                $field.after('<span class="error-message" style="color: #dc3232; font-size: 12px;">Please enter a valid email address.</span>');
	            }
	            $field.addClass('error');
	        } else {
	            $errorMsg.remove();
	            $field.removeClass('error');
	        }
	    });

	    // Email validation function
	    function isValidEmail(email) {
	        var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
	        return emailRegex.test(email);
	    }

	    // Show confirmation dialog when enabling the module
	    $('#enable_logging, #show_warning').on('change', function() {
	        var isChecked = $(this).is(':checked');
	        var fieldName = $(this).attr('id');
	        
	        if (isChecked && fieldName === 'show_warning') {
	            if (!confirm('This will display a warning notice in the admin area when the module is active. Continue?')) {
	                $(this).prop('checked', false);
	            }
	        }
	    });

	});

})( jQuery ); 