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


		$('#save-sitemap-settings').on('click', function(e) {
			e.preventDefault();
			
			$.ajax({
				url: ultimakitSitemap.ajaxurl,
				type: 'POST',
				data: {
					action: 'ultimakit_save_sitemap_settings',
					nonce: ultimakitSitemap.nonce,
					excluded_items: $('input[name="excluded_items[]"]:checked').map(function() {
						return $(this).val();
					}).get(),
					frequency: $('#sitemap-frequency').val()
				},
				beforeSend: function() {
					$('#save-sitemap-settings, #generate-sitemap').prop('disabled', true);
				},
				success: function(response) {
					if (response.success) {
						toastr.success( response.data, '', toastConf );
					} else {
						toastr.error( response.data, '', toastConf );
					}
					setTimeout(function(){
						window.location.reload();
					},2000);
				},
				error: function(xhr, status, error) {
					console.error('Ajax error:', error);
					toastr.error( ultimakitSitemap.error_saving_settings, '', toastConf );
				},
				complete: function() {
					$('#save-sitemap-settings, #generate-sitemap').prop('disabled', false);
				}
			});
		});
	
		$('#generate-sitemap').on('click', function(e) {
			e.preventDefault();
			
			const $button = $(this);
			
			$.ajax({
				url: ultimakitSitemap.ajaxurl,
				type: 'POST',
				data: {
					action: 'ultimakit_generate_sitemap',
					nonce: ultimakitSitemap.nonce
				},
				beforeSend: function() {
					$button.prop('disabled', true).text('Generating...');
				},
				success: function(response) {
					if (response.success) {
						// alert('Sitemap generated successfully!');
						toastr.success( response.data, '', toastConf );
					} else {
						toastr.error( response.data, '', toastConf );
					}
					setTimeout(function(){
						window.location.reload();
					},2000);
				},
				error: function() {
					toastr.error( ultimakitSitemap.error_generating_sitemap, '', toastConf );
				},
				complete: function() {
					$button.prop('disabled', false).text('Generate Sitemap');
				}
			});
		});

		$('#delete-sitemap').on('click', function(e) {
			e.preventDefault();
			
			const $button = $(this);
			
			$.ajax({
				url: ultimakitSitemap.ajaxurl,
				type: 'POST',
				data: {
					action: 'ultimakit_delete_sitemap',
					nonce: ultimakitSitemap.nonce
				},
				beforeSend: function() {
					$button.prop('disabled', true).text('Deleting...');
				},
				success: function(response) {
					if (response.success) {
						toastr.success( response.data, '', toastConf );
					} else {
						toastr.error( response.data, '', toastConf );
					}

					setTimeout(function(){
						window.location.reload();
					},2000);
				},
				error: function() {
					toastr.error( ultimakitSitemap.error_deleting_sitemap, '', toastConf );
				},
				complete: function() {
					$button.prop('disabled', false).text('Delete Sitemap');
				}
			});
		});

	});

})( jQuery );