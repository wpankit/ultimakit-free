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
        // Handle clone link click
        $(document).on('click', '.clone-post', function(e) {
            e.preventDefault();
            var postId = $(this).data('post-id');
    
            if (!postId) {
                alert(ultimakitDuplicate.error);
                return;
            }
    
            $.ajax({
                url: ultimakitDuplicate.ajaxurl,
                type: 'POST',
                data: {
                    action: 'duplicate_post',
                    nonce: ultimakitDuplicate.nonce,
                    post_id: postId
                },
                success: function(response) {
                    if (response.success) {
                        alert(response.data.message);
                        // Optionally, refresh the page or redirect to the new post
                        location.reload(); // Reload the page to see the new post
                    } else {
                        alert(response.data);
                    }
                },
                error: function() {
                    alert(ultimakitDuplicate.error);
                }
            });
        });
    });

})( jQuery );