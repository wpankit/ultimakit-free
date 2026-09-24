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

        function scanImages(page = 1) {
            var $button = $('#scan-images');
            var $container = $('#results-container');
            
            $button.prop('disabled', true).text(ultimakit_alt_text_checker.scanning);
            $container.html('<div class="spinner is-active" style="float: none; margin: 0 0 0 10px;"></div>');
            
            $.ajax({
                url: ultimakit_alt_text_checker.url,
                type: 'POST',
                data: {
                    action: 'scan_missing_alt_text',
                    page: page,
                    nonce: ultimakit_alt_text_checker.scan_alt_text
                },
                success: function(response) {
                    $container.html(response);
                },
                error: function() {
                    $container.html('<div class="notice notice-error"><p>' + ultimakit_alt_text_checker.scan_error + '</p></div>');
                },
                complete: function() {
                    $button.prop('disabled', false).text(ultimakit_alt_text_checker.scan_button);
                }
            });
        }
        
        $('#scan-images').on('click', function() {
            scanImages();
        });
        
        $(document).on('click', '.prev-page, .next-page', function(e) {
            e.preventDefault();
            scanImages($(this).data('page'));
            $('html, body').animate({ scrollTop: 0 }, 'slow');
        });
        
        $(document).on('click', '.save-alt-text', function() {
            var $button = $(this);
            var $row = $button.closest('tr');
            var imageId = $button.data('image-id');
            var altText = $('#alt-text-' + imageId).val();
            
            if (!altText.trim()) {
                alert(ultimakit_alt_text_checker.enter_alt_text);
                return;
            }
            
            $button.prop('disabled', true).text(ultimakit_alt_text_checker.saving);
            
            $.ajax({
                url: ultimakit_alt_text_checker.url,
                type: 'POST',
                data: {
                    action: 'update_alt_text',
                    image_id: imageId,
                    alt_text: altText,
                    nonce: ultimakit_alt_text_checker.update_alt_text
                },
                success: function(response) {
                    if(response.success) {
                        $('#status-' + imageId).html(ultimakit_alt_text_checker.updated).css('color', 'green');
                        $row.addClass('updated');
                    } else {
                        $('#status-' + imageId).html(ultimakit_alt_text_checker.error).css('color', 'red');
                    }
                },
                error: function() {
                    $('#status-' + imageId).html(ultimakit_alt_text_checker.error).css('color', 'red');
                },
                complete: function() {
                    $button.prop('disabled', false).text(ultimakit_alt_text_checker.save);
                }
            });
        });

	});

})( jQuery );