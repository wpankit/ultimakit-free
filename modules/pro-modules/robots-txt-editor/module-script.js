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
        // Save Changes
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

        $('#save-robots').on('click', function() {
            const $button = $(this);
            const $message = $('#save-message');
            const $content = $('#robots-content');
            const createBackup = $('#backup_robots').is(':checked');
            
            $button.prop('disabled', true)
                   .html('<span class="spinner-border spinner-border-sm me-2"></span>Saving...');
            
            $message.removeClass('alert-success alert-danger d-none');
    
            $.ajax({
                url: ultimakitRobotsTxtEditor.ajaxurl,
                type: 'POST',
                data: {
                    action: 'save_robots_txt',
                    nonce: ultimakitRobotsTxtEditor.nonce,
                    content: $content.val(),
                    backup: createBackup
                },
                success: function(response) {
                    toastr.success( response.data, '', toastConf );
                    $button.prop('disabled', false)
                           .html(ultimakitRobotsTxtEditor.saveButton);
                },
                error: function() {
                    toastr.error( ultimakitRobotsTxtEditor.saveError, '', toastConf );
                },
                complete: function() {
                    toastr.success( ultimakitRobotsTxtEditor.saveSuccess, '', toastConf );
                    
                    setTimeout(function() {
                        $message.addClass('d-none');
                    }, 3000);
                }
            });
        });
    
        // Reset to Default
        $('#reset-robots').on('click', function() {
            if (confirm(ultimakitRobotsTxtEditor.resetConfirm)) {
                $('#robots-content').val(`User-agent: *
    Disallow: /wp-admin/
    Disallow: /wp-includes/
    
    Sitemap: ${window.location.origin}/sitemap.xml`);
            }
        });
    });

})( jQuery );