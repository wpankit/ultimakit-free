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
        const $form = $('#wpuk-redirect-form');
        const $table = $('.wpuk-redirects-list table tbody');
        const $submitButton = $('#wpuk-redirect-submit');
        const $cancelButton = $('#wpuk-redirect-cancel');

        // Handle form submission
        $form.on('submit', function(e) {
            e.preventDefault();
    
            const redirectId = $('#redirect_id').val();
            const sourceUrl = $('#source_url').val();
            const targetUrl = $('#target_url').val();
    
            $.ajax({
                url: wpukRedirect.ajaxurl,
                type: 'POST',
                data: {
                    action: 'wpuk_save_redirect',
                    nonce: wpukRedirect.nonce,
                    redirect_id: redirectId,
                    source_url: sourceUrl,
                    target_url: targetUrl
                },
                success: function(response) {
                    if (response.success) {
                        const redirect = response.data.redirect;
                        
                        if (redirectId) {
                            // Update existing row
                            const $row = $table.find(`tr[data-id="${redirectId}"]`);
                            $row.find('td:eq(0)').text(redirect.source_url);
                            $row.find('td:eq(1)').text(redirect.target_url);
                        } else {
                            // Add new row
                            if ($table.find('tr').length === 1 && $table.find('td[colspan]').length) {
                                $table.empty();
                            }
    
                            const newRow = `
                                <tr data-id="${redirect.id}">
                                    <td>${redirect.source_url}</td>
                                    <td>${redirect.target_url}</td>
                                    <td>${redirect.hits}</td>
                                    <td>${redirect.last_accessed}</td>
                                    <td>
                                        <button class="btn btn-danger button-small wpuk-delete-redirect">Delete</button>
                                    </td>
                                </tr>
                            `;
                            $table.prepend(newRow);
                        }
    
                        resetForm();
                        showNotice(wpukRedirect.messages.success, 'success');
                    } else {
                        showNotice(response.data || wpukRedirect.messages.error, 'error');
                    }
                },
                error: function() {
                    showNotice(wpukRedirect.messages.error, 'error');
                }
            });
        });
        
        $(document).on('click', '.wpuk-edit-redirect', function() {
            const $row = $(this).closest('tr');
            const redirectId = $row.data('id');
            const sourceUrl = $row.find('td:eq(0)').text();
            const targetUrl = $row.find('td:eq(1)').text();
    
            // Populate form
            $('#redirect_id').val(redirectId);
            $('#source_url').val(sourceUrl);
            $('#target_url').val(targetUrl);
    
            // Update button text and show cancel button
            $submitButton.text('Update Redirect');
            $cancelButton.show();
    
            // Scroll to form
            $('html, body').animate({
                scrollTop: $form.offset().top - 50
            }, 500);
        });
    
        // Handle cancel button click
        $cancelButton.on('click', function() {
            resetForm();
        });
    
        // Reset form to initial state
        function resetForm() {
            $form[0].reset();
            $('#redirect_id').val('');
            $submitButton.text('Add Redirect');
            $cancelButton.hide();
        }

        // Handle redirect deletion
        $(document).on('click', '.wpuk-delete-redirect', function() {
            if (!confirm(wpukRedirect.messages.confirmDelete)) {
                return;
            }
    
            const $row = $(this).closest('tr');
            const redirectId = $row.data('id');
    
            $.ajax({
                url: wpukRedirect.ajaxurl,
                type: 'POST',
                data: {
                    action: 'wpuk_delete_redirect',
                    nonce: wpukRedirect.nonce,
                    id: redirectId
                },
                success: function(response) {
                    if (response.success) {
                        $row.fadeOut(400, function() {
                            $(this).remove();
                            if ($table.find('tr').length === 0) {
                                $table.html('<tr><td colspan="5">No redirects found.</td></tr>');
                            }
                        });
                    } else {
                        showNotice(response.data || wpukRedirect.messages.error, 'error');
                    }
                },
                error: function() {
                    showNotice(wpukRedirect.messages.error, 'error');
                }
            });
        });
    
        // Helper function to show notices
        function showNotice(message, type) {
            const $notice = $('<div>')
                .addClass(`notice notice-${type} is-dismissible`)
                .append($('<p>').text(message));
    
            $('.wrap > h1').after($notice);
    
            // Auto-dismiss after 3 seconds
            setTimeout(function() {
                $notice.fadeOut(400, function() {
                    $(this).remove();
                });
            }, 3000);
        }
    
        // Pre-fill source URL if provided in URL parameters
        const urlParams = new URLSearchParams(window.location.search);
        const sourceUrl = urlParams.get('source');
        if (sourceUrl) {
            $('#source_url').val(sourceUrl);
        }
    });
	

})( jQuery );