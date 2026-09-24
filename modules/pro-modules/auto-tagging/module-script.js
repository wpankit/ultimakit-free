/**
 * Auto Tagging Module Script
 * 
 * @package UltimaKit
 */

(function($) {
    'use strict';

    // Initialize when document is ready
    $(document).ready(function() {

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

        const AutoTagging = {
            init: function() {
                this.bindEvents();
                this.initSettingsForm();
            },

            bindEvents: function() {
                $('#generate-tags').on('click', this.generateTags);
                $('#add-selected-tags').on('click', this.addSelectedTags);
                $(document).on('change', '.suggestion-item input[type="checkbox"]', this.toggleAddButton);
            },

            initSettingsForm: function() {
                $('#auto-tagging-settings-form').on('submit', this.saveSettings);
            },

            /**
             * Generate tags for the current post
             */
            generateTags: function(e) {
                e.preventDefault();
                const $button = $(this);
                const $suggestionList = $('#suggestion-list');
                const postId = $('#post_ID').val();

                // Disable button and show loading state
                $button.prop('disabled', true).text(ultimakitAutoTagging.generating_text);

                // Make AJAX request
                $.ajax({
                    url: ultimakitAutoTagging.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'get_tag_suggestions',
                        post_id: postId,
                        nonce: ultimakitAutoTagging.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            $suggestionList.html(response.data.html);
                            if (response.data.html.indexOf('suggestion-item') !== -1) {
                                $('#add-selected-tags').hide();
                            }
                        } else {
                            $suggestionList.html('<p class="error">' + ultimakitAutoTagging.error_message + '</p>');
                        }
                    },
                    error: function() {
                        $suggestionList.html('<p class="error">' + ultimakitAutoTagging.error_message + '</p>');
                    },
                    complete: function() {
                        // Reset button state
                        $button.prop('disabled', false).text(ultimakitAutoTagging.generate_text);
                    }
                });
            },

            /**
             * Toggle visibility of Add Selected Tags button
             */
            toggleAddButton: function() {
                const $checkedBoxes = $('.suggestion-item input[type="checkbox"]:checked');
                const $addButton = $('#add-selected-tags');

                if ($checkedBoxes.length > 0) {
                    $addButton.show();
                } else {
                    $addButton.hide();
                }
            },

            /**
             * Add selected tags to the post
             */
            addSelectedTags: function(e) {
                e.preventDefault();
                
                const $button = $(this);
                const postId = $('#post_ID').val();
                const selectedTags = [];
                
                // Disable button while processing
                $button.prop('disabled', true);
                
                // Collect selected tags
                $('.suggestion-item input[type="checkbox"]:checked').each(function() {
                    selectedTags.push($(this).val());
                });

                if (selectedTags.length === 0) {
                    $button.prop('disabled', false);
                    return;
                }

                // Make AJAX request to save tags
                $.ajax({
                    url: ultimakitAutoTagging.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'ultimakit_save_post_tags',
                        post_id: postId,
                        tags: selectedTags,
                        nonce: ultimakitAutoTagging.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            // Update tag display if it exists
                            if (window.tagBox && window.tagBox.flushTags) {
                                window.tagBox.flushTags(
                                    $('#post_tag'), 
                                    response.data.tags_input
                                );
                            }

                            // Clear suggestions and hide add button
                            $('#suggestion-list').empty();
                            $('#add-selected-tags').hide();

                            // Show success message
                            const $message = $('<div class="notice notice-success is-dismissible"><p>' + 
                                ultimakitAutoTagging.tags_added_message + '</p></div>');
                            $('#suggestion-list').before($message);

                            // Auto-dismiss message
                            setTimeout(function() {
                                $message.fadeOut(function() {
                                    $(this).remove();
                                });
                            }, 3000);
                        } else {
                            // Show error message
                            const $message = $('<div class="notice notice-error is-dismissible"><p>' + 
                                ultimakitAutoTagging.error_message + '</p></div>');
                            $('#suggestion-list').before($message);
                        }
                    },
                    error: function() {
                        // Show error message
                        const $message = $('<div class="notice notice-error is-dismissible"><p>' + 
                            ultimakitAutoTagging.error_message + '</p></div>');
                        $('#suggestion-list').before($message);
                    },
                    complete: function() {
                        $button.prop('disabled', false);
                    }
                });
            },

            /**
             * Save module settings
             */
            saveSettings: function(e) {
                e.preventDefault();
                const $form = $(this);
                const $submitButton = $form.find('button[type="submit"]');

                // Disable submit button
                $submitButton.prop('disabled', true);

                // Prepare form data
                const formData = new FormData(this);
                formData.append('action', 'ultimakit_save_auto_tagging_settings');
                formData.append('nonce', ultimakitAutoTagging.nonce);

                // Make AJAX request
                $.ajax({
                    url: ultimakitAutoTagging.ajaxurl,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            // Show success message
                            toastr.success( ultimakitAutoTagging.success_message, '', toastConf );
                        } else {
                            // Show error message
                            toastr.error( ultimakitAutoTagging.error_message, '', toastConf );
                        }
                    },
                    error: function() {
                        // Show error message
                        toastr.error( ultimakitAutoTagging.error_message, '', toastConf );
                    },
                    complete: function() {
                        // Re-enable submit button
                        $submitButton.prop('disabled', false);
                    }
                });
            },

            /**
             * Show notification message
             */
            showNotification: function(type, message) {
                const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
                const $alert = $('<div class="alert ' + alertClass + ' alert-dismissible fade show" role="alert">' +
                    message +
                    '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>' +
                    '</div>');

                // Add alert to page
                $('#wpukTabsContent').prepend($alert);

                // Auto-dismiss after 3 seconds
                setTimeout(function() {
                    $alert.alert('close');
                }, 3000);
            }
        };

        // Initialize module
        AutoTagging.init();
    });
})(jQuery);