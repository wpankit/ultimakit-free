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
        let isScanning = false;
    
        function updateProgress(percent) {
            $('#scan-progress')
                .removeClass('d-none')
                .find('.progress-bar')
                .css('width', percent + '%')
                .attr('aria-valuenow', percent);
        }
    
        function scanLinks(page = 1) {
            if (isScanning) return;
            
            isScanning = true;
            const $button = $('#scan-links');
            const $container = $('#results-container');
            const checkExternal = $('#check_external').is(':checked');
            const checkInternal = $('#check_internal').is(':checked');
    
            if (!checkExternal && !checkInternal) {
                alert(ultimakit_link_checker.select_type);
                isScanning = false;
                return;
            }
    
            $button.prop('disabled', true)
                    .html('<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>' + 
                            ultimakit_link_checker.scanning);
    
            updateProgress(10);
    
            $.ajax({
                url: ultimakit_link_checker.url,
                type: 'POST',
                data: {
                    action: 'scan_broken_links',
                    nonce: ultimakit_link_checker.scan_nonce,
                    page: page,
                    check_external: checkExternal,
                    check_internal: checkInternal
                },
                success: function(response) {
                    updateProgress(100);
                    $container.html(response);
                    
                    setTimeout(function() {
                        $('#scan-progress').addClass('d-none');
                        $button.prop('disabled', false)
                                .html(ultimakit_link_checker.scan_again_button);
                        isScanning = false;
                    }, 500);
                },
                error: function() {
                    $container.html(
                        '<div class="alert alert-danger">' + 
                        ultimakit_link_checker.scan_error + 
                        '</div>'
                    );
                },
                complete: function() {
                    setTimeout(function() {
                        $('#scan-progress').addClass('d-none');
                        $button.prop('disabled', false)
                                .html(ultimakit_link_checker.scan_again_button);
                        isScanning = false;
                    }, 500);
                }
            });
        }
    
        $('#scan-links').on('click', function() {
            scanLinks();
        });
    
        $(document).on('click', '.pagination .page-link', function(e) {
            e.preventDefault();
            const page = $(this).data('page');
            if (page) {
                scanLinks(page);
                $('html, body').animate({ scrollTop: 0 }, 'slow');
            }
        });
    
        $(document).on('click', '.update-link', function() {
            const $button = $(this);
            const $row = $button.closest('tr');
            const postId = $button.data('post-id');
            const oldUrl = $button.data('old-url');
            const newUrl = $('#url-' + postId).val();
    
            if (!newUrl.trim()) {
                alert(ultimakit_link_checker.enter_url);
                return;
            }
    
            $button.prop('disabled', true)
                    .html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>');
    
            $.ajax({
                url: ultimakit_link_checker.url,
                type: 'POST',
                data: {
                    action: 'update_link',
                    nonce: ultimakit_link_checker.update_nonce,
                    post_id: postId,
                    old_url: oldUrl,
                    new_url: newUrl
                },
                success: function(response) {
                    if (response.success) {
                        $row.addClass('table-success');
                        setTimeout(() => $row.fadeOut('slow'), 1000);
                    } else {
                        alert(ultimakit_link_checker.scan_error);
                    }
                },
                error: function() {
                    alert(ultimakit_link_checker.scan_error);
                },
                complete: function() {
                    $button.prop('disabled', false).html(ultimakit_link_checker.save);
                }
            });
        });
    });


})( jQuery );