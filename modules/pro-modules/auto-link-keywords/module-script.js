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
        // Handle form submission
        $('#add-keyword-form').on('submit', function(e) {
            e.preventDefault();
            
            var data = {
                action: 'save_keyword',
                nonce: ultimakitAutoLink.nonce,
                keyword: $('#keyword').val(),
                url: $('#url').val(),
                limit: $('#limit').val(),
                case_sensitive: $('#case_sensitive').is(':checked')
            };
    
            $.post(ultimakitAutoLink.ajaxurl, data, function(response) {
                if (response.success) {
                    location.reload();
                } else {
                }
            });
        });

    
        // Handle keyword deletion
        $('.delete-keyword').on('click', function() {
            if (!confirm(ultimakitAutoLink.messages.delete_keyword_confirm_message)) {
                return;
            }
    
            var $button = $(this);
            var data = {
                action: 'delete_keyword',
                nonce: ultimakitAutoLink.nonce,
                id: $button.data('id')
            };
    
            $.post(ultimakitAutoLink.ajaxurl, data, function(response) {
                if (response.success) {
                    $button.closest('tr').remove();
                } else {
                    alert(ultimakitAutoLink.messages.delete_keyword_error_message);
                }
            });
        });
    });

})( jQuery );