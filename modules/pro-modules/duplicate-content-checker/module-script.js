(function($) {
    'use strict';

    $(document).ready(function() {
        const toastConf = {
            timeOut: 3000,
            positionClass: 'toast-top-center',
            progressBar: true,
            closeButton: true,
            preventDuplicates: true,
            iconClasses: {
                success: "toast-success",
                error: "toast-error",
                warning: "toast-warning"
            },
        };

        // Handle Start Scan button
        $('#start-scan').on('click', function(e) {
            e.preventDefault();
            const $button = $(this);
            const $progress = $('#scan-progress');
            const $results = $('#scan-results');

            // Show progress bar and disable button
            $progress.removeClass('d-none');
            $progress.find('.progress-bar')
                .css('width', '0%')
                .attr('aria-valuenow', 0)
                .text('0%');
            
            $button.prop('disabled', true).text(ultimakitDuplicateChecker.scanning_message);

            // Simulate progress
            let progress = 0;
            const progressInterval = setInterval(function() {
                progress += 5;
                if (progress <= 90) {
                    $progress.find('.progress-bar')
                        .css('width', progress + '%')
                        .attr('aria-valuenow', progress)
                        .text(progress + '%');
                }
            }, 500);

            // Run scan
            $.ajax({
                url: ultimakitDuplicateChecker.ajaxurl,
                type: 'POST',
                data: {
                    action: 'ultimakit_run_duplicate_scan',
                    nonce: ultimakitDuplicateChecker.nonce
                },
                success: function(response) {
                    clearInterval(progressInterval);
                    
                    // Complete progress bar
                    $progress.find('.progress-bar')
                        .css('width', '100%')
                        .attr('aria-valuenow', 100)
                        .text('100%');

                    if (response.success) {
                        toastr.success(response.data.message, '', toastConf);
                        displayResults(response.data.results);
                    } else {
                        toastr.error(response.data, '', toastConf);
                        $results.html('<div class="alert alert-danger">' + response.data + '</div>');
                    }
                },
                error: function() {
                    clearInterval(progressInterval);
                    toastr.error(ultimakitDuplicateChecker.error_message, '', toastConf);
                    $results.html('<div class="alert alert-danger">' + ultimakitDuplicateChecker.error_message + '</div>');
                },
                complete: function() {
                    setTimeout(function() {
                        $progress.addClass('d-none');
                        $button.prop('disabled', false).text('Start Scan');
                    }, 1000);
                }
            });
        });

        // Handle Settings Form Submit
        $('#scanner-settings-form').on('submit', function(e) {
            e.preventDefault();
            const $form = $(this);
            const $submitButton = $form.find('button[type="submit"]');

            $submitButton.prop('disabled', true);

            $.ajax({
                url: ultimakitDuplicateChecker.ajaxurl,
                type: 'POST',
                data: {
                    action: 'ultimakit_save_scan_settings',
                    nonce: ultimakitDuplicateChecker.nonce,
                    frequency: $('#scan-frequency').val(),
                    post_types: $('input[name="post_types[]"]:checked').map(function() {
                        return $(this).val();
                    }).get()
                },
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.data, '', toastConf);
                    } else {
                        toastr.error(response.data, '', toastConf);
                    }
                },
                error: function() {
                    toastr.error('Error saving settings', '', toastConf);
                },
                complete: function() {
                    $submitButton.prop('disabled', false);
                }
            });
        });

        // Function to display scan results
        function displayResults(results) {
            const $results = $('#scan-results');
            
            if (Object.keys(results).length === 0) {
                $results.html('<div class="alert alert-success">No duplicate content found.</div>');
                return;
            }

            let html = '<div class="duplicate-content-results">';
            
            Object.keys(results).forEach(function(hash) {
                const group = results[hash];
                const original = group.original;
                const duplicates = group.duplicates;

                html += `
                    <div class="duplicate-group card mb-3">
                        <div class="card-header">
                            <h6 class="mb-0">Original Content</h6>
                        </div>
                        <div class="card-body">
                            <p><strong>Title:</strong> ${original.title}</p>
                            <p><strong>Type:</strong> ${original.type}</p>
                            <p><strong>URL:</strong> <a href="${original.url}" target="_blank">${original.url}</a></p>
                        </div>
                        <div class="card-header">
                            <h6 class="mb-0">Duplicate Entries (${duplicates.length})</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Title</th>
                                            <th>Type</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                `;

                duplicates.forEach(function(duplicate) {
                    html += `
                        <tr>
                            <td>${duplicate.title}</td>
                            <td>${duplicate.type}</td>
                            <td>
                                <a href="${duplicate.url}" target="_blank" class="btn btn-sm btn-info">View</a>
                                <a href="${window.location.origin}/wp-admin/post.php?post=${duplicate.id}&action=edit" class="btn btn-sm btn-primary">Edit</a>
                            </td>
                        </tr>
                    `;
                });

                html += `
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                `;
            });

            html += '</div>';
            $results.html(html);
        }
    });
})(jQuery);