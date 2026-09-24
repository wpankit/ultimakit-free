(function($) {
    'use strict';

    const KeywordSuggestion = {
        init: function() {
            this.suggestionBox = $('#ultimakit-keyword-suggestions');
            this.input = this.suggestionBox.find('#keyword-input');
            this.results = this.suggestionBox.find('#suggestion-results');
            this.loading = this.suggestionBox.find('.suggestion-loading');
            this.timer = null;

            this.bindEvents();
        },

        bindEvents: function() {
            this.input.on('input', this.handleInput.bind(this));
            this.results.on('click', '.suggestion-item', this.insertKeyword.bind(this));
        },

        handleInput: function(e) {
            clearTimeout(this.timer);

            const keyword = $(e.target).val().trim();
            if (keyword.length < 3) {
                this.results.empty();
                return;
            }

            this.timer = setTimeout(() => {
                this.getSuggestions(keyword);
            }, 500);
        },

        getSuggestions: function(keyword) {
            this.loading.show();
            this.results.empty();

            $.ajax({
                url: ultimakitKeywordSuggestion.ajaxurl,
                type: 'POST',
                data: {
                    action: 'get_keyword_suggestions',
                    keyword: keyword,
                    nonce: ultimakitKeywordSuggestion.nonce
                },
                success: (response) => {
                    if (response.success && response.data.length) {
                        this.showSuggestions(response.data);
                    } else {
                        this.results.html(
                            '<div class="no-suggestions">' + 
                            ultimakitKeywordSuggestion.no_results + 
                            '</div>'
                        );
                    }
                },
                error: () => {
                    this.results.html(
                        '<div class="suggestion-error">' + 
                        ultimakitKeywordSuggestion.error + 
                        '</div>'
                    );
                },
                complete: () => {
                    this.loading.hide();
                }
            });
        },

        showSuggestions: function(suggestions) {
            // Suggestions come from a third party: insert them as text, never as HTML.
            const items = suggestions.map(suggestion =>
                $('<div class="suggestion-item"></div>').text(suggestion)
            );

            this.results.empty().append(items);
        },

        insertKeyword: function(e) {
            const keyword = $(e.target).text();
            const editor = wp.data.select('core/editor');
            
            if (editor) {
                // For Gutenberg editor
                const content = editor.getEditedPostContent();
                wp.data.dispatch('core/editor').editPost({
                    content: content + ' ' + keyword
                });
            } else {
                // For Classic editor
                const $content = $('#content');
                $content.val($content.val() + ' ' + keyword);
            }

            this.input.val('');
            this.results.empty();
        }
    };

    $(document).ready(function() {
        KeywordSuggestion.init();
    });

})(jQuery);