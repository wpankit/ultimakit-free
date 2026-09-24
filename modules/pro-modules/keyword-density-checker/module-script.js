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
        const $input = $('#wpuk-keyword-input');
        const $button = $('#wpuk-analyze-keyword');
        const $results = $('#wpuk-density-results');
        const $error = $('#wpuk-density-error');
        
        $button.on('click', function() {
            const keyword = $input.val().trim();
            
            if (!keyword) {
                showError(wpukKeywordDensity.no_keyword);
                return;
            }
    
            analyzeKeyword(keyword);
        });
    
        function getEditorContent() {
            // Check if classic editor exists
            if ($('#content').length > 0) {
                return $('#content').val();
            }
            
            // Check if TinyMCE editor is active
            if (typeof tinyMCE !== 'undefined' && tinyMCE.activeEditor && !tinyMCE.activeEditor.isHidden()) {
                return tinyMCE.activeEditor.getContent();
            }
            
            return wp.data.select('core/editor').getEditedPostContent();
            
        }

        function analyzeKeyword(keyword) {
            const content = getEditorContent();

            if (!content) {
                showError(wpukKeywordDensity.no_content);
                return;
            }
            $button.prop('disabled', true).text(wpukKeywordDensity.analyzing);
            $results.hide();
            $error.hide();
    
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'analyze_keyword_density',
                    nonce: wpukKeywordDensity.nonce,
                    keyword: keyword,
                    content: content
                },
                success: function(response) {
                    if (response.success) {
                        displayResults(response.data);
                    } else {
                        showError(response.data || wpukKeywordDensity.error);
                    }
                },
                error: function() {
                    showError(wpukKeywordDensity.error);
                },
                complete: function() {
                    $button.prop('disabled', false).text('Analyze');
                }
            });
        }
    
        function displayResults(data) {
            $('#wpuk-density-percentage').text(data.density + '%');
            $('#wpuk-keyword-count').text(data.count);
            $('#wpuk-density-status-text')
                .text(data.message)
                .removeClass('status-poor status-low status-good status-high')
                .addClass('status-' + data.status);
    
            $results.show();
            $error.hide();
        }
    
        function showError(message) {
            $error.html(message).show();
            $results.hide();
        }
    });


})( jQuery );