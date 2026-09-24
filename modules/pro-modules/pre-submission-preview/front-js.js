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
        // Variable to store the current form ID
        var currentFormId;

        // Show the preview modal
        window.gfShowPreview = function (formId) {
            // Store the form ID for later use
            currentFormId = formId;
            var form = $('#gform_' + formId);

            // Start table. Built with DOM methods and .text() so no field value or label is
            // ever parsed as HTML: values can arrive pre-filled from a URL, a save-and-continue
            // link or a failed cross-site POST, and .val() returns them decoded.
            var previewTable = $('<table class="gf-preview-table"></table>');
            var previewHeadRow = $('<tr></tr>').appendTo($('<thead></thead>').appendTo(previewTable));
            $('<th></th>').text(ultimakit_pre_submissions.field).appendTo(previewHeadRow);
            $('<th></th>').text(ultimakit_pre_submissions.your_response).appendTo(previewHeadRow);
            var previewBody = $('<tbody></tbody>').appendTo(previewTable);

            // Collect form data
            form.find('.gfield').each(function () {
                var field = $(this);
                var fieldLabel = field.find('.gfield_label').text().trim();
                var fieldValue = '';

                // Skip hidden fields
                if (field.css('display') === 'none') {
                    return true;
                }

                // Handle different field types
                if (field.find('input[type="radio"]').length) {
                    // Radio buttons
                    var selectedRadio = field.find('input[type="radio"]:checked');
                    if (selectedRadio.length) {
                        var choiceLabel = selectedRadio.next('label');
                        if (choiceLabel.length) {
                            fieldValue = choiceLabel.text().trim();
                        } else {
                            fieldValue = selectedRadio.val();
                        }
                    }
                } 
                else if (field.find('input[type="checkbox"]').length) {
                    // Checkboxes
                    var checkedBoxes = field.find('input[type="checkbox"]:checked');
                    var checkedValues = [];
                    
                    checkedBoxes.each(function() {
                        var checkboxLabel = $(this).next('label');
                        if (checkboxLabel.length) {
                            checkedValues.push(checkboxLabel.text().trim());
                        } else {
                            checkedValues.push($(this).val());
                        }
                    });
                    
                    fieldValue = checkedValues.join(', ');
                } 
                else if (field.find('select').length) {
                    // Select dropdowns
                    fieldValue = field.find('select option:selected').text().trim();
                } 
                else if (field.find('textarea').length) {
                    // Textareas
                    fieldValue = field.find('textarea').val();
                } 
                else if (field.find('input[type="file"]').length) {
                    // File uploads
                    var fileInput = field.find('input[type="file"]');
                    fieldValue = fileInput.val().split('\\').pop() || 'No file chosen';
                } 
                else {
                    // Regular input fields.
                    // .val() returns only the FIRST match, so multi-input fields (Name,
                    // Address) previewed as just the prefix / street line 1 — the user was
                    // confirming a preview that did not match what they were submitting.
                    var input = field.find('input[type="text"], input[type="email"], input[type="tel"], input[type="number"], input[type="url"]');
                    if (input.length) {
                        fieldValue = input.map(function () {
                            return (this.value || '').trim();
                        }).get().filter(function (v) {
                            return v !== '';
                        }).join(' ');
                    }
                }

                // Add row to table if field has a label and is not hidden
                if (fieldLabel && field.is(':visible')) {
                    var previewRow = $('<tr></tr>').appendTo(previewBody);
                    $('<td class="gf-preview-label"></td>').text(fieldLabel).appendTo(previewRow);
                    $('<td class="gf-preview-value"></td>').text(fieldValue || ultimakit_pre_submissions.no_response).appendTo(previewRow);
                }
            });

            // Display the preview content
            $('#gf-preview-content').empty().append(previewTable);
            $('#gf-preview-modal').fadeIn();
        };

        // Close the preview modal
        window.gfClosePreview = function () {
            $('#gf-preview-modal').fadeOut();
        };

        // Submit the form
        window.gfSubmitForm = function () {
            if (currentFormId) {
                // Close the modal first
                $('#gf-preview-modal').fadeOut();
                
                // Trigger the form submission
                $('#gform_submit_button_' + currentFormId).click();
                
                // Alternative submission method if the above doesn't work
                // $('#gform_' + currentFormId).submit();
            } else {
                console.error('Form ID not found');
            }
        };

        // Close modal when clicking outside
        $(document).on('click', '#gf-preview-modal', function(e) {
            if (e.target.id === 'gf-preview-modal') {
                gfClosePreview();
            }
        });

        // Add keyboard support for closing modal
        $(document).keyup(function(e) {
            if (e.key === "Escape") {
                gfClosePreview();
            }
        });
    });


})( jQuery );