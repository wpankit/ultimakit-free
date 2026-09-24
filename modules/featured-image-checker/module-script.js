(function($) {
    'use strict';

    const FeaturedImageChecker = {
        init: function() {
            this.bindEvents();
        },

        bindEvents: function() {
            $('#scan-images').on('click', this.startScan.bind(this));
        },

        startScan: function() {
            const $button = $('#scan-images');
            const $progress = $('#scan-progress');
            
            $button.prop('disabled', true);
            $progress.removeClass('d-none');
            
            $.ajax({
                url: ultimakitFeaturedImage.ajaxurl,
                type: 'POST',
                data: {
                    action: 'scan_missing_featured_images',
                    nonce: ultimakitFeaturedImage.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.showResults(1);
                    } else {
                        this.showError(response.data);
                    }
                },
                error: () => {
                    this.showError(ultimakitFeaturedImage.error);
                },
                complete: () => {
                    $button.prop('disabled', false);
                    $progress.addClass('d-none');
                }
            });
        },

        showResults: function(page) {
            $.ajax({
                url: ultimakitFeaturedImage.ajaxurl,
                type: 'POST',
                data: {
                    action: 'get_missing_featured_images',
                    nonce: ultimakitFeaturedImage.nonce,
                    page: page
                },
                success: (response) => {
                    if (response.success) {
                        this.renderResults(response.data);
                    }
                }
            });
        },

        renderResults: function(data) {
            const $tbody = $('#results-body');
            const $pagination = $('#pagination');
            
            $tbody.empty();
            
            if (data.items.length === 0) {
                $tbody.html('<tr><td colspan="5" class="text-center">No missing featured images found.</td></tr>');
                return;
            }

            data.items.forEach(item => {
                $tbody.append(`
                    <tr>
                        <td>${item.title}</td>
                        <td>${item.type}</td>
                        <td>${item.author}</td>
                        <td>${item.date}</td>
                        <td>
                            <a href="${item.edit_url}" class="btn btn-sm btn-primary" target="_blank">
                                Edit
                            </a>
                        </td>
                    </tr>
                `);
            });

            this.renderPagination(data.current_page, data.total_pages);
        },

        renderPagination: function(currentPage, totalPages) {
            const $pagination = $('#pagination');
            const $ul = $pagination.find('ul');
            
            if (totalPages <= 1) {
                $pagination.addClass('d-none');
                return;
            }

            $pagination.removeClass('d-none');
            $ul.empty();

            for (let i = 1; i <= totalPages; i++) {
                $ul.append(`
                    <li class="page-item ${i === currentPage ? 'active' : ''}">
                        <a class="page-link" href="#" data-page="${i}">${i}</a>
                    </li>
                `);
            }

            $ul.find('a').on('click', (e) => {
                e.preventDefault();
                this.showResults($(e.currentTarget).data('page'));
            });
        },

        showError: function(message) {
            const $tbody = $('#results-body');
            $tbody.html(`
                <tr>
                    <td colspan="5" class="text-center text-danger">
                        ${message}
                    </td>
                </tr>
            `);
        }
    };

    $(document).ready(function() {
        FeaturedImageChecker.init();
    });

})(jQuery);