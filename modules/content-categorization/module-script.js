(function($) {
    'use strict';

    const CategorySuggestion = {
        init: function() {
            this.suggestionBox = $('#ultimakit-category-suggestions');
            this.input = this.suggestionBox.find('#content-input');
            this.results = this.suggestionBox.find('#suggestion-results');
            this.loading = this.suggestionBox.find('.suggestion-loading');

            this.bindEvents();
        },

        bindEvents: function() {
            this.suggestionBox.find('#suggest-categories').on('click', this.getSuggestions.bind(this));
            this.results.on('click', '.suggestion-item', this.insertCategory.bind(this));
        },

        getSuggestions: function() {
            const content = this.input.val().trim();
            if (content.length === 0) {
                this.results.empty();
                return;
            }

            this.loading.show();
            this.results.empty();

            $.ajax({
                url: ultimakitCategorySuggestion.ajaxurl,
                type: 'POST',
                data: {
                    action: 'get_category_suggestions',
                    content: content,
                    nonce: ultimakitCategorySuggestion.nonce
                },
                success: (response) => {
                    if (response.success && response.data.length) {
                        this.showSuggestions(response.data);
                    } else {
                        this.results.html(
                            '<div class="no-suggestions">' + 
                            ultimakitCategorySuggestion.no_results + 
                            '</div>'
                        );
                    }
                },
                error: () => {
                    this.results.html(
                        '<div class="suggestion-error">' + 
                        ultimakitCategorySuggestion.error + 
                        '</div>'
                    );
                },
                complete: () => {
                    this.loading.hide();
                }
            });
        },

        showSuggestions: function(suggestions) {
            const html = suggestions.map(suggestion => 
                `<div class="suggestion-item">${suggestion}</div>`
            ).join('');
            
            this.results.html(html);
        },

        insertCategory: function(e) {
            const category = $(e.target).text();
            const existingCategories = $('#post_category input:checked').map(function() {
                return $(this).next('label').text();
            }).get();

            if (!existingCategories.includes(category)) {
                $('#post_category input[value="' + category + '"]').prop('checked', true);
            }

            this.input.val('');
            this.results.empty();
        }
    };

    $(document).ready(function() {
        CategorySuggestion.init();
    });

})(jQuery);