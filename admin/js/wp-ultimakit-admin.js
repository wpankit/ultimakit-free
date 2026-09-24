/**
 * This is the javascript file for the module.
 *
 * @package UltimaKit
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

	jQuery( document ).ready(
		function ($) {	
			// Modern Dashboard Functionality
			initializeModernDashboard();
			
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
			
			// Initialize Modern Dashboard
			var activeCategory = 'all';
			var searchTimer = null;

			function initializeModernDashboard() {
				// Assigned here, not at declaration: this runs before the var initialiser above.
				activeCategory = 'all';

				// Category Navigation
				$('.category-item').on('click', function () {
					activeCategory = String($(this).data('category') || 'all');

					$('.category-item').removeClass('active');
					$(this).addClass('active');

					applyFilters();
				});

				// Search: debounced so a long module list does not re-filter on every keystroke.
				$('#ultimakit_search_module').on('input', function (event) {
					var keyCode = event.keyCode || event.which;
					if (keyCode === 13) {
						event.preventDefault();
					}

					clearTimeout(searchTimer);
					searchTimer = setTimeout(applyFilters, 150);
				});

				// View Toggle
				$('#ultimakit_small_screen').on('click', function () {
					$('.ultimakit-modern-dashboard').addClass('compact-view');
					updateOption('ultimakit_modules_list_view', 'small');
					$(this).addClass('active').siblings().removeClass('active');
					showToast('Compact view enabled', 'success');
				});

				$('#ultimakit_full_screen').on('click', function () {
					$('.ultimakit-modern-dashboard').removeClass('compact-view');
					updateOption('ultimakit_modules_list_view', 'full');
					$(this).addClass('active').siblings().removeClass('active');
					showToast('Full view enabled', 'success');
				});

				// Initialize view mode. Fall back to whatever the server already rendered
				// so the stored preference wins on a fresh browser.
				const stored = localStorage.getItem('ultimakit_modules_list_view');
				const viewMode = stored || ($('.ultimakit-modern-dashboard').hasClass('compact-view') ? 'small' : 'full');
				if (viewMode === 'small') {
					$('.ultimakit-modern-dashboard').addClass('compact-view');
					$('#ultimakit_small_screen').addClass('active');
				} else {
					$('#ultimakit_full_screen').addClass('active');
				}

				applyFilters();
			}

			/**
			 * Single filtering pass over the module cards.
			 *
			 * Every module is in the DOM exactly once and carries data-filter, so category and
			 * search are applied together here and the per-category counts are tallied in the
			 * same loop -- no reading back computed styles, and no dividing counts by two to
			 * undo duplicate markup.
			 */
			function applyFilters() {
				const search = ($('#ultimakit_search_module').val() || '').toLowerCase().trim();
				const counts = {};
				let visibleTotal = 0;
				let matchedTotal = 0;

				$('.module-block').each(function () {
					const module = this;
					const $module = $(module);
					const bucket = String($module.data('filter') || '');

					if (module.ultimakitTitle === undefined) {
						module.ultimakitTitle = ($module.find('.module-title').text() || '').toLowerCase();
						module.ultimakitDesc = ($module.find('.module-description').text() || '').toLowerCase();
					}

					const matchesSearch = !search ||
						module.ultimakitTitle.indexOf(search) !== -1 ||
						module.ultimakitDesc.indexOf(search) !== -1;

					const matchesCategory = activeCategory === 'all' || bucket === activeCategory;

					if (matchesSearch) {
						counts[bucket] = (counts[bucket] || 0) + 1;
						matchedTotal++;
					}

					const show = matchesSearch && matchesCategory;
					if (show) {
						visibleTotal++;
					}

					// Only touch the DOM when the state actually changes.
					if (module.ultimakitVisible !== show) {
						module.ultimakitVisible = show;
						$module.toggle(show);
					}
				});

				$('#ultimakit-no-results').toggle(visibleTotal === 0);

				// Update the sidebar counts from the tally above.
				$('.category-item').each(function () {
					const $item = $(this);
					const slug = String($item.data('category') || '');
					const count = slug === 'all' ? matchedTotal : (counts[slug] || 0);

					$item.find('.category-count').text(count);
					$item.toggleClass('has-search-results', !!search && count > 0);
				});
			}

			// Helper function to get option
			function getOption(key) {
				return localStorage.getItem(key) || 'full';
			}
			
			// Helper function to update option
			function updateOption(key, value) {
				localStorage.setItem(key, value);
			}
			
			// Show toast notification
			function showToast(message, type = 'info') {
				const toastConfig = {
					timeOut: 2000,
					positionClass: 'toast-top-center',
					progressBar: true,
					closeButton: true,
					preventDuplicates: true,
					iconClasses: {
						success: "toast-success",
						warning: "toast-warning",
						error: "toast-error",
						info: "toast-info"
					},
				};
				
				toastr[type](message, '', toastConfig);
			}
			
			// Migrate settings
		    $('#ultimakit_migrate_settings').on('click', function(e) {
		        e.preventDefault();

				const toastConf = {
					timeOut: 1000, // Adjust display time as needed (in milliseconds).
					positionClass: 'toast-top-right', // Adjust position as needed.
					progressBar: true, // Show a progress bar.
					closeButton: true,
					preventDuplicates: true,
					iconClasses: {
						success: "toast-success",
				        warning: "toast-warning" // Specify a single CSS class for warning messages.
				    },
				};

		      	$.ajax({
				    url: ultimakit_ajax.url,
				    type: 'POST',
				    data: {
				        action: 'ultimakit_migrate_settings',
						nonce: ultimakit_ajax.nonce
				    },
				    success: function (response) {
						toastr.success( 'Settings have been migrated successfully.', '', toastConf );

		            	setTimeout(function(){
		            		window.location.reload();
		            	},1000);
				    },
				    error: function () {
				        toastr.error('Error: AJAX request failed');
				    },
				});
		    });

			// Export settings
		    $('#ultimakit_export_settings').on('click', function(e) {
				e.preventDefault();
			
				$.ajax({
					url: ultimakit_ajax.url,
					type: 'POST',
					data: {
						action: 'export_ultimakit_settings',
						nonce: ultimakit_ajax.nonce,
					},
					success: function (response) {
						if (response.success) {
							toastr.success('Settings have been exported successfully.', '', toastConf);

							// Build the download in the browser. The payload contains secrets,
							// so it is never written to a publicly readable URL on the server.
							const blob = new Blob(
								[JSON.stringify(response.data.settings, null, 2)],
								{ type: 'application/json' }
							);
							const objectUrl = URL.createObjectURL(blob);
							const link = document.createElement('a');
							link.href = objectUrl;
							link.download = response.data.filename;
							document.body.appendChild(link);
							link.click();
							document.body.removeChild(link);
							URL.revokeObjectURL(objectUrl);
						} else {
							toastr.error(response.data || 'An error occurred while exporting.');
						}
					},
					error: function () {
						toastr.error('Error: AJAX request failed');
					},
				});
			});

		    // Import settings
		    $('#ultimakit_import_settings').on('change', function(e) {
		        e.preventDefault();

		        var file_data = $('#ultimakit_import_settings').prop('files')[0];
		        var form_data = new FormData();
		        form_data.append('json_file', file_data);
		        form_data.append('action', 'import_ultimakit_settings'); // WordPress AJAX action
		        form_data.append('nonce', ultimakit_ajax.nonce); // Security nonce

		        const toastConf = {
					timeOut: 1000, // Adjust display time as needed (in milliseconds).
					positionClass: 'toast-top-right', // Adjust position as needed.
					progressBar: true, // Show a progress bar.
					closeButton: true,
					preventDuplicates: true,
					iconClasses: {
						success: "toast-success",
				        warning: "toast-warning" // Specify a single CSS class for warning messages.
				    },
				};

		        $.ajax({
		            url: ultimakit_ajax.url, // WordPress admin AJAX URL
		            type: 'POST',
		            contentType: false,
		            processData: false,
		            data: form_data,
		            success: function (response) {
		            	toastr.success( 'Settings have been imported successfully.', '', toastConf );

		            	setTimeout(function(){
		            		window.location.reload();
		            	},1000);
		            },
		            error: function (response) {
		                toastr.error( 'Failed to import settings.', '', toastConf );
		            }
		        });

		    });

			let settingsActions = $( '.ultimakit_settings_action' );
			settingsActions.on(
				'change',
				function (event) {
					const restUrl   = ultimakit_ajax.url;
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

					// The event object contains information about the change event.
					if (event.target.checked) {
						status = 'on';
					} else {
						status = 'off';
					}

					// Make a REST API request.
					$.ajax(
					{
						url: restUrl, // Use your endpoint route.
						type: 'POST',
						data: {
							action: 'ultimakit_uninstall_settings',
							stat: status,
							nonce: ultimakit_ajax.nonce,
						},
						beforeSend: function (xhr) {
							xhr.setRequestHeader( 'X-WP-Nonce', ultimakit_ajax.nonce ); // Include the nonce in the request header.
						},
						success: function (response) {
							toastr.success( 'Settings Updated', '', toastConf );
						},
						error: function () {
							// Handle errors.
							toastr.error( 'Error: AJAX request failed', '', toastConf );
						},
					});
				}
			);

			// Select the checkbox element by its ID.
			let checkbox = $( '.ultimakit_module_action' );
			// Add a change event listener to the checkbox.
			checkbox.on(
				'change',
				function (event) {
					let module_id     = jQuery( this ).attr( 'id' );
					let module_status = '';
					let module_name = jQuery( this ).attr( 'module-name' );

					// The event object contains information about the change event.
					if (event.target.checked) {
						module_status = 'on';
					} else {
						module_status = 'off';
					}

					const restUrl   = ultimakit_ajax.url;
					const toastConf = {
						timeOut: 1500, // Adjust display time as needed (in milliseconds).
						positionClass: 'toast-top-center', // Adjust position as needed.
						progressBar: true, // Show a progress bar.
						closeButton: true,
						preventDuplicates: true,
						"showEasing": "linear",
						"hideEasing": "linear",
						"showMethod": "fadeIn",
						"hideMethod": "fadeOut",
						iconClasses: {
							success: "toast-success",
					        warning: "toast-warning" 
					    },
					};
					// Make a REST API request.
					$.ajax(
						{
							url: restUrl, // Use your endpoint route.
							type: 'POST',
							data: {
								action: 'ultimakit_update_settings',
								module_id: module_id,
								module_status: module_status,
								nonce: ultimakit_ajax.nonce,
							},
							beforeSend: function (xhr) {
								xhr.setRequestHeader( 'X-WP-Nonce', ultimakit_ajax.nonce ); // Include the nonce in the request header.
							},
							success: function (response) {
								if ('on' === response.data.status) {
									toastr.success( module_name + ' ' + response.data.message, '', toastConf );
									if ( 'on' == module_status ) {
										jQuery( '.' + module_id ).show();
									} else {
										jQuery( '.' + module_id ).hide();
									}
									setTimeout(
										function () {
											window.location.reload();
										},
										1500
									);
								} else {
									toastr.error( module_name + ' ' + response.data.message, '', toastConf );

									setTimeout(
										function () {
											window.location.reload();
										},
										1500
									);
								}
							},
							error: function () {
								// Handle errors.
								toastr.error( 'Error: AJAX request failed', '', toastConf );
							},
						}
					);
				}
			);

			$( '.module_settings' ).submit(
				function (e) {
					e.preventDefault(); // Prevent the default form submit.
					let settingData = {};
					let module_id   = $( this ).attr( 'id' );

					$( '#' + module_id ).find( ':input' ).each(
						function () {
							if (this.name && ! this.disabled) {
								if ( 'checkbox' == this.type || 'radio' == this.type ) {
									if ($( this ).prop( 'checked' )) {
										settingData[this.name] = 'on';
									} else {
										settingData[this.name] = 'off';
									}
								} else {
									if( this.type !== 'html' ){
										settingData[this.name] = $( this ).val();
									}
								}
							}
						}
					);

					let customOption = null;
					if ($('.wpuk_save_module_settings').length > 0) {
					    customOption = $('.wpuk_save_module_settings').attr('custom-option');
						settingData['custom_option'] = customOption;
					}

					// You can use 'inputData' as needed, like sending to server via AJAX.
					const restUrl   = ultimakit_ajax.url;
					const toastConf = {
						timeOut: 1000, // Adjust display time as needed (in milliseconds).
						positionClass: 'toast-top-right', // Adjust position as needed.
						progressBar: true, // Show a progress bar.
						closeButton: true,
						preventDuplicates: true,
						iconClasses: {
							success: "toast-success",
					    },
					};
					
					// Make a REST API request.
					$.ajax(
						{
							url: restUrl, // Use your endpoint route.
							type: 'POST',
							data: {
								action: 'ultimakit_update_settings',
								module_id: module_id.replace( '_form', '' ),
								module_settings: settingData,
								nonce: ultimakit_ajax.nonce,
								save_mode: 'settings'
							},
							beforeSend: function (xhr) {
								xhr.setRequestHeader( 'X-WP-Nonce', ultimakit_ajax.nonce ); // Include the nonce in the request header.
							},
							success: function (response) {
								if (response.success) {
									toastr.success( response.data.message, '', toastConf );
									setTimeout(
										function () {
											$( '.wpuk_modal' ).hide();
											window.location.reload();
										},
										1000
									);
								} else {
									// Display an error toast.
									toastr.success( 'Success: ' + response.data.message, '', toastConf );
								}
							},
							error: function () {
								// Handle errors.
								toastr.error( 'Error: AJAX request failed', '', toastConf );
							},
						}
					);
				}
			);


			
			$("#moduleFilterForm").on('submit', function (event) {
				event.preventDefault();
			});

			/**
			 * Persist the chosen view density server-side.
			 *
			 * The category/search filtering, tab counters and per-card style writes that used
			 * to live here were removed: modules are rendered once now, and applyFilters()
			 * above is the single source of truth for what is visible.
			 */
			function ultimakit_update_module_width( view = 'full' ) {
				$.ajax({
					url: ultimakit_ajax.url,
					type: 'POST',
					data: {
						action: 'ultimakit_update_module_width',
						view: view,
						nonce: ultimakit_ajax.nonce
					},
					success: function (response) {
					},
					error: function (error) {
						toastr.error('Error: AJAX request failed');
					},
				});
			}

			$('#ultimakit_small_screen').on('click', function (e) {
				e.preventDefault();
				ultimakit_update_module_width('small');
			});

			$('#ultimakit_full_screen').on('click', function (e) {
				e.preventDefault();
				ultimakit_update_module_width('full');
			});

			$(document).ready(function() {
				// Only target tabs within #wpukTabs
				$('#wpukTabs a[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
					// Get the href (tab ID) of the active tab
					var activeTabId = $(e.target).attr('href').substring(1);
					localStorage.setItem('wpukActiveTab', activeTabId); // Changed key name to be more specific
				});
			
				// Check for active tab in localStorage and activate it
				var activeTab = localStorage.getItem('wpukActiveTab');
				if (activeTab) {
					$(`#wpukTabs a[href="#${activeTab}"]`).tab('show');
				}
			});
		}
	);
	

	

	

})( jQuery );