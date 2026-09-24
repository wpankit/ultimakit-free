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


	jQuery(document).ready(function($) {
        // Save Settings
        $('#save-settings').on('click', function() {
            const $button = $(this);
            const defaultExpiry = $('#default_expiry').val();
            const enableEmail = $('#enable_email').is(':checked');
            
            $button.prop('disabled', true)
                   .html('<span class="spinner-border spinner-border-sm me-2"></span>Saving...');
    
            $.ajax({
                url: ultimakitContentExpiry.ajaxurl,
                type: 'POST',
                data: {
                    action: 'save_expiry_settings',
                    nonce: ultimakitContentExpiry.nonce,
                    default_expiry: defaultExpiry,
                    enable_email: enableEmail
                },
                success: function(response) {
                    if (response.success) {
                        showMessage('success', response.data);
                    } else {
                        showMessage('error', response.data);
                    }
                },
                error: function() {
                    showMessage('error', ultimakitContentExpiry.saveError);
                },
                complete: function() {
                    $button.prop('disabled', false)
                           .html(ultimakitContentExpiry.saveButton);
                }
            });
        });
    
        function showMessage(type, message) {
            const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
            const $alert = $('<div>')
                .addClass(`alert ${alertClass} mt-3`)
                .html(message)
                .insertAfter('#save-settings');
    
            setTimeout(() => $alert.fadeOut(() => $alert.remove()), 3000);
        }
    
        // Handle notice dismissal
        $(document).on('click', '.notice-dismiss', function() {
            const $notice = $(this).closest('.notice');
            
            $.ajax({
                url: ultimakitContentExpiry.ajaxurl,
                type: 'POST',
                data: {
                    action: 'dismiss_expiry_notice',
                    nonce: ultimakitContentExpiry.nonce
                }
            });
        });
    });

})( jQuery );