/**
 * This is the javascript file for the Post Per Page module.
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

	    // Handle modal opening for Post Per Page module
	    $('.ultimakit_post_per_page').on('click', function (e) {
	        e.preventDefault(); // Prevent the default action of the click event
	        $("#ultimakit_post_per_page_modal").modal('show'); // Show the modal
	    });

	    // Handle form submission for Post Per Page module
	    $('.ultimakit_post_per_page_form').on('submit', function (e) {
	        e.preventDefault(); // Prevent the default form submission

	        var formData = $(this).serialize();
	        var $submitButton = $(this).find('button[type="submit"]');
	        var originalText = $submitButton.text();

	        // Show loading state
	        $submitButton.prop('disabled', true).text('Saving...');

	        /*Ajax Start*/
			jQuery.ajax({
				url: ultimakit_post_per_page.ajax_url,
				type: 'POST',
				data: formData + '&action=ultimakit_update_settings&nonce=' + ultimakit_post_per_page.ajax_nonce + '&module_id=ultimakit_post_per_page&save_mode=settings',
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
						$('#ultimakit_post_per_page_modal .modal-body').prepend(
							'<div class="notice notice-success is-dismissible"><p>Settings saved successfully!</p></div>'
						);
						
						// Auto-dismiss the modal after 2 seconds
						setTimeout(function() {
							$("#ultimakit_post_per_page_modal").modal('hide');
						}, 2000);
					} else {
						// Show error message
						$('#ultimakit_post_per_page_modal .modal-body').prepend(
							'<div class="notice notice-error is-dismissible"><p>Error saving settings. Please try again.</p></div>'
						);
					}
				},
				error: function() {
					// Show error message
					$('#ultimakit_post_per_page_modal .modal-body').prepend(
						'<div class="notice notice-error is-dismissible"><p>Error saving settings. Please try again.</p></div>'
					);
				}
			});
			/*Ajax end here*/ 
	    });

	    // Handle number input validation
	    $('input[type="number"]').on('input', function() {
	        var value = parseInt($(this).val());
	        var min = parseInt($(this).attr('min')) || 1;
	        var max = parseInt($(this).attr('max')) || 100;
	        var $field = $(this);
	        var $errorMsg = $field.siblings('.error-message');
	        
	        // Remove existing error message
	        $errorMsg.remove();
	        $field.removeClass('error');
	        
	        if (value < min || value > max) {
	            $field.after('<span class="error-message" style="color: #dc3232; font-size: 12px;">Please enter a value between ' + min + ' and ' + max + '.</span>');
	            $field.addClass('error');
	        }
	    });

	    // Handle default value changes
	    $('#default').on('change', function() {
	        var defaultValue = $(this).val();
	        var $postTypeFields = $('input[id^="post_type_"]');
	        
	        if (defaultValue && defaultValue > 0) {
	            // Show hint about default value
	            if ($('.default-hint').length === 0) {
	                $postTypeFields.each(function() {
	                    var $field = $(this);
	                    var $hint = $field.siblings('.default-hint');
	                    if ($hint.length === 0) {
	                        $field.after('<span class="default-hint" style="color: #666; font-size: 11px; display: block; margin-top: 2px;">Leave empty to use default (' + defaultValue + ')</span>');
	                    }
	                });
	            }
	        } else {
	            // Remove hints if no default value
	            $('.default-hint').remove();
	        }
	    });

	    // Auto-update hints when default value changes
	    $('#default').on('input', function() {
	        $(this).trigger('change');
	    });

	    // Handle post type field changes
	    $('input[id^="post_type_"]').on('input', function() {
	        var value = $(this).val();
	        var $hint = $(this).siblings('.default-hint');
	        
	        if (value && value > 0) {
	            $hint.hide();
	        } else {
	            $hint.show();
	        }
	    });

	    // Add tooltips for better UX
	    $('.form-group').each(function() {
	        var $group = $(this);
	        var $label = $group.find('label');
	        var $desc = $group.find('.description');
	        
	        if ($desc.length > 0) {
	            $label.attr('title', $desc.text());
	        }
	    });

	    // Handle modal close to clear any error messages
	    $('#ultimakit_post_per_page_modal').on('hidden.bs.modal', function() {
	        $('.error-message').remove();
	        $('.notice').remove();
	        $('input').removeClass('error');
	    });

	    // Add keyboard shortcuts
	    $(document).on('keydown', function(e) {
	        // Ctrl/Cmd + S to save
	        if ((e.ctrlKey || e.metaKey) && e.keyCode === 83) {
	            e.preventDefault();
	            if ($('#ultimakit_post_per_page_modal').hasClass('show')) {
	                $('.ultimakit_post_per_page_form').submit();
	            }
	        }
	        
	        // Escape to close modal
	        if (e.keyCode === 27) {
	            if ($('#ultimakit_post_per_page_modal').hasClass('show')) {
	                $('#ultimakit_post_per_page_modal').modal('hide');
	            }
	        }
	    });

	});

})( jQuery ); 