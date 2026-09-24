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

        $('.wpuk-readability-score-history-toggle').on('click', function(e) {
            e.preventDefault();
            $(this).toggleClass('open');
            $('.score-history').slideToggle(200);
        });

        const $metaBox = $('.wpuk-readability-score');
        const $calculateBtn = $metaBox.find('.calculate-score');
        const $scoreDisplay = $metaBox.find('.score-display');
        
        let isCalculating = false;

        // Function to get score class
        function getScoreClass(score) {
            if (score >= 90) return 'score-very-easy';
            if (score >= 80) return 'score-easy';
            if (score >= 70) return 'score-fairly-easy';
            if (score >= 60) return 'score-standard';
            if (score >= 50) return 'score-fairly-difficult';
            if (score >= 30) return 'score-difficult';
            return 'score-very-difficult';
        }

        // Function to update score display
        function updateScoreDisplay(data) {
            // Remove all existing score classes
            const scoreClasses = [
                'score-very-easy',
                'score-easy',
                'score-fairly-easy',
                'score-standard',
                'score-fairly-difficult',
                'score-difficult',
                'score-very-difficult'
            ];
            
            const scoreHtml = `
                <div class="score-value ${getScoreClass(data.score)}">${data.score}</div>
                <div class="score-label">${data.label}</div>
            `;
            
            $scoreDisplay.html(scoreHtml);
            
            // Add animation class
            $scoreDisplay.addClass('updated').delay(500).queue(function() {
                $(this).removeClass('updated').dequeue();
            });
        }

        // Handle calculate button click
        $calculateBtn.on('click', function(e) {
            e.preventDefault();
            
            if (isCalculating) return;
            
            const postId = $('#post_ID').val();
            const nonce = $('#wpuk_readability_nonce').val();
            
            if (!postId || !nonce) {
                showError('Missing required data');
                return;
            }

            isCalculating = true;
            $calculateBtn.addClass('is-busy').prop('disabled', true);
            showMessage(wpukReadabilityScore.calculating_score);

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'calculate_readability',
                    post_id: postId,
                    nonce: nonce
                },
                success: function(response) {
                    if (response.success) {
                        updateScoreDisplay(response.data);
                    } else {
                        showError(response.data.message || wpukReadabilityScore.error_calculating_score);
                    }
                },
                error: function() {
                    showError(wpukReadabilityScore.error_network_error);
                },
                complete: function() {
                    isCalculating = false;
                    $calculateBtn.removeClass('is-busy').prop('disabled', false);
                }
            });
        });

        // Show error message
        function showError(message) {
            const errorHtml = `
                <div class="error-message">
                    ${message}
                </div>
            `;
            $scoreDisplay.html(errorHtml);
        }

        // Show message
        function showMessage(message) {
            const messageHtml = `
                <div class="info-message">
                    ${message}
                </div>
            `;
            $scoreDisplay.html(messageHtml);
        }

        // Handle post save (if using Classic Editor)
        $(document).on('submit', '#post', function() {
            const $scoreValue = $metaBox.find('.score-value');
            if ($scoreValue.length) {
                const currentScore = parseFloat($scoreValue.text());
                const scoreClass = getScoreClass(currentScore);
                $scoreValue.attr('class', 'score-value ' + scoreClass);
            }
        });

        // Handle Gutenberg save
        if (window.wp && wp.data && wp.data.subscribe) {
            wp.data.subscribe(() => {
                const isSaving = wp.data.select('core/editor')?.isSavingPost();
                const isAutosaving = wp.data.select('core/editor')?.isAutosavingPost();

                if (isSaving && !isAutosaving) {
                    const $scoreValue = $metaBox.find('.score-value');
                    if ($scoreValue.length) {
                        const currentScore = parseFloat($scoreValue.text());
                        const scoreClass = getScoreClass(currentScore);
                        $scoreValue.attr('class', 'score-value ' + scoreClass);
                    }
                }
            });
        }
    });


})( jQuery );