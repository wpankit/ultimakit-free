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

		if( $('.ultimakit_module_utm_builder').length > 0 ) {

			$('#ultimakit_generate_url').hide();

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

		    $('.ultimakit_module_utm_builder').on('click', function (e) {
		        e.preventDefault(); // Prevent the default action of the click event
		        $("#ultimakit_module_utm_builder_modal").modal('show'); // Show the modal
		    });


		    $('.ultimakit_module_utm_builder_form').on('click', function (e) {
		        e.preventDefault(); // Prevent the default action of the click event

		        var baseUrl = $('#website_url').val();
                var utmCampaignID = $('#campaign_id').val();
                var utmCampaign = $('#campaign_name').val();
                var utmSource = $('#campaign_source').val();
                var utmMedium = $('#campaign_medium').val();
                var utmTerm = $('#campaign_term').val();
                var utmContent = $('#campaign_content').val();

                if (!baseUrl) {
                	toastr.error( ultimakit_utm_builder.url_req, '', toastConf );
	                return;
	            }

                var utmParams = [];
                if (utmCampaignID) utmParams.push('utm_id=' + encodeURIComponent(utmCampaignID));
                if (utmCampaign) utmParams.push('utm_campaign=' + encodeURIComponent(utmCampaign));
                if (utmSource) utmParams.push('utm_source=' + encodeURIComponent(utmSource));
                if (utmMedium) utmParams.push('utm_medium=' + encodeURIComponent(utmMedium));
                if (utmTerm) utmParams.push('utm_term=' + encodeURIComponent(utmTerm));
                if (utmContent) utmParams.push('utm_content=' + encodeURIComponent(utmContent));

                var utmUrl = baseUrl;
                if (utmParams.length > 0) {
                    utmUrl += (baseUrl.indexOf('?') === -1 ? '?' : '&') + utmParams.join('&');
                }

                $('#ultimakit_generate_url').val(utmUrl);
                $('#ultimakit_generate_url').show();
		    });

		    $('#copy-button').on('click', function(e){
		    	e.preventDefault();
                var $temp = $("<input>");
                $("body").append($temp);
                $temp.val($('#ultimakit_generate_url').val()).select();
                document.execCommand("copy");
                $temp.remove();
                toastr.success( ultimakit_utm_builder.url_copied, '', toastConf );
            });

		}

	});


})( jQuery );