/**
 * This is the javascript file for the module.
 *
 * @package UltimaKit_
 */

(function ( $ ) {
	'use strict';

	jQuery(document).ready(function ($) {

		const toastConf = {
			timeOut: 1000,
			positionClass: 'toast-top-center',
			progressBar: true,
			closeButton: true,
			preventDuplicates: true,
			iconClasses: {
				success: "toast-success",
				warning: "toast-warning"
			},
		};

		if( $("#ultimakit_module_custom_Post_Type_Taxonomy_modal").length > 0 ) {

		    $('.modal-footer .btn-secondary').on('click', function (e) {
		        e.preventDefault();
		        window.location.reload();
		    });

		    $('#rew_slug').on('input', function() {
			    var original = $(this).val();
			    var value = original.replace(/[^a-z_-]/g, '').slice(0, 20);
			    $(this).val(value).css('border-color', value.length ? '' : 'red');
			    $('#slug-error').toggle(value.length < original.length);
			});

			$('.wpuk_save_module_settings').on('click', function (e) {
		        e.preventDefault();

				var mode   = getParameterByName('mode');
				var cpt_id = getParameterByName('cpt_id');
				var page   = getParameterByName('page');
				var ctx_id = getParameterByName('ctx_id');

		        var allFormsData = [];

				$('.module_settings').each(function () {
					var formDataObject = {};
					var supports = [];

					$(this).serializeArray().forEach(function (field) {
						field.name === 'wp_supports[]' ? supports.push(field.value) : formDataObject[field.name] = field.value;
					});

					if (supports.length) formDataObject['supports'] = supports.join(',');

					allFormsData.push(formDataObject);
				});

				var form_data = new FormData();
				var action = ( 'wp-ultimakit-custom-taxonomies' === page ) ? 'save_custom_taxonomy' : 'save_custom_post_type';
				var id     = ( 'wp-ultimakit-custom-taxonomies' === page ) ? ctx_id : cpt_id;

				form_data.append('action', action);
				form_data.append('nonce', ultimakit_custom_post_type.ajax_nonce);
				form_data.append('formData', JSON.stringify(allFormsData));

				form_data.append('mode', ( 'edit' === mode ) ? mode : 'new');
				if ( 'edit' === mode ) {
					form_data.append('id', id);
				}

				$.ajax({
					url: ultimakit_custom_post_type.ajax_url,
					type: 'POST',
					contentType: false,
					processData: false,
					data: form_data,
					beforeSend: function() {
						$('body').css('cursor', 'progress');
					},
					complete: function() {
						$('body').css('cursor', 'default');
					},
					success: function (response) {
						if( response.success === true ){
							toastr.success( response.data.message, '', toastConf );
							setTimeout(function(){
								window.location.href = ultimakit_custom_post_type.admin_url;
							},1000);
						}
					},
					error: function (xhr) {
						var msg = ( xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message )
							? xhr.responseJSON.data.message
							: 'An error occurred. Please try again.';
						toastr.error( msg, '', toastConf );
					}
				});
		    });

			function getParameterByName(name) {
				var params = new URLSearchParams(window.location.search);
				return params.get(name);
			}

			var cpt_id = getParameterByName('cpt_id');

			if (cpt_id) {
				fetchCPTData(cpt_id, 'cpt');
			}

			var ctx_id = getParameterByName('ctx_id');

			if (ctx_id) {
				fetchCPTData(ctx_id, 'ctx');
			}

			function fetchCPTData(cpt_id, type) {
				type = type || 'cpt';
				$.ajax({
					url: ultimakit_custom_post_type.ajax_url,
					type: 'POST',
					dataType: 'json',
					data: {
						action: 'ultimakit_get_cpt_ctx_data',
						nonce: ultimakit_custom_post_type.ajax_nonce,
						cpt_id: cpt_id,
						ctx_id: ctx_id,
						type: type
					},
					success: function(response) {
						if (response.success) {
							$.each(response.data, function(key, value) {
								if( 'post_type_slug' === key ){
									$('#rew_slug').val(value);
								} else {
									if( 'settings' === key ){
										value = JSON.parse(value);
										$.each(value, function(key_x, value_x) {
											if('supports' === key_x){
												var supportsValues = value_x.split(',');
												$('input[name="wp_supports[]"]').each(function() {
													var checkboxValue = $(this).val();
													$(this).prop('checked', supportsValues.includes(checkboxValue));
												});
											} else {
												$('#' + key_x).val(value_x);
											}
										});
									} else {
										$('#' + key).val(value);
									}
								}
							});
						} else {
							toastr.error( 'Error fetching CPT data: ' + response.data, '', toastConf );
						}
					},
					error: function() {
						toastr.error( 'An error occurred while fetching the CPT data.', '', toastConf );
					}
				});
			}
	    }


		if( $("#custom-post-types").length > 0 ) {

			if( $('.cpt_status').length > 0 ) {
				$('.cpt_status').change(function() {
					var isChecked = $(this).is(':checked');
					var id = $(this).attr('data-id');
					var status = isChecked ? '1' : '0';

					var form_data = new FormData();
					form_data.append( 'action', 'ultimakit_change_cpt_status' );
					form_data.append( 'nonce', ultimakit_custom_post_type.ajax_nonce );
					form_data.append( 'status', status );
					form_data.append( 'id', id );

					$.ajax({
						url: ultimakit_custom_post_type.ajax_url,
						type: 'POST',
						contentType: false,
						processData: false,
						data: form_data,
						success: function (response) {
							if( response.success === true ){
								toastr.success( response.data, '', toastConf );
								setTimeout(function(){
									window.location.reload();
								},1000);
							}
						},
						error: function (response) {
							toastr.error( response.data, '', toastConf );
						}
					});
				});
			}

			if( $('.ultimakit_cpt_action').length > 0 ) {
				$('.ultimakit_cpt_action').click(function(e) {
					var id   = $(this).attr('data-id');
					var mode = $(this).attr('data-mode');

					if( 'delete' === mode ){
						e.preventDefault();
						if ( ! window.confirm( 'Are you sure you want to delete this Post Type? This action cannot be undone.' ) ) {
							return;
						}
						var form_data = new FormData();
						form_data.append( 'action', 'ultimakit_delete_cpt' );
						form_data.append( 'nonce', ultimakit_custom_post_type.ajax_nonce );
						form_data.append( 'id', id );
						$.ajax({
							url: ultimakit_custom_post_type.ajax_url,
							type: 'POST',
							contentType: false,
							processData: false,
							data: form_data,
							success: function (response) {
								if( response.success === true ){
									toastr.success( response.data, '', toastConf );
								}
								setTimeout(function(){
									window.location.reload();
								},2000);
							},
							error: function (response) {
								toastr.error( response.data, '', toastConf );
							}
						});
					}
				});
			}

		}

		if( $("#custom-taxonomies").length > 0 ) {
			if( $('.ctx_status').length > 0 ) {
				$('.ctx_status').change(function() {
					var isChecked = $(this).is(':checked');
					var id = $(this).attr('data-id');
					var status = isChecked ? '1' : '0';

					var form_data = new FormData();
					form_data.append( 'action', 'ultimakit_change_ctx_status' );
					form_data.append( 'nonce', ultimakit_custom_post_type.ajax_nonce );
					form_data.append( 'status', status );
					form_data.append( 'id', id );

					$.ajax({
						url: ultimakit_custom_post_type.ajax_url,
						type: 'POST',
						contentType: false,
						processData: false,
						data: form_data,
						success: function (response) {
							if( response.success === true ){
								toastr.success( response.data, '', toastConf );
								setTimeout(function(){
									window.location.reload();
								},1000);
							}
						},
						error: function (response) {
							toastr.error( response.data, '', toastConf );
						}
					});
				});
			}

			if( $('.ultimakit_ctx_action').length > 0 ) {
				$('.ultimakit_ctx_action').click(function(e) {
					var id   = $(this).attr('data-id');
					var mode = $(this).attr('data-mode');

					if( 'delete' === mode ){
						e.preventDefault();
						if ( ! window.confirm( 'Are you sure you want to delete this Taxonomy? This action cannot be undone.' ) ) {
							return;
						}
						var form_data = new FormData();
						form_data.append( 'action', 'ultimakit_delete_ctx' );
						form_data.append( 'nonce', ultimakit_custom_post_type.ajax_nonce );
						form_data.append( 'id', id );
						$.ajax({
							url: ultimakit_custom_post_type.ajax_url,
							type: 'POST',
							contentType: false,
							processData: false,
							data: form_data,
							success: function (response) {
								if( response.success === true ){
									toastr.success( response.data, '', toastConf );
								}
								setTimeout(function(){
									window.location.reload();
								},2000);
							},
							error: function (response) {
								toastr.error( response.data, '', toastConf );
							}
						});
					}
				});
			}
		}

	});


})( jQuery );
