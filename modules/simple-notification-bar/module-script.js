/**
 * This is the javascript file for the module.
 *
 * @package UltimaKit_
 */

(function ( $ ) {
	'use strict';

	jQuery(document).ready(function ($) {

		// Navigate to dedicated settings page when the Settings link is clicked on the dashboard
		if ( $('.ultimakit_module_simple_notification_bar').length > 0 ) {
			$('.ultimakit_module_simple_notification_bar').on('click', function (e) {
				e.preventDefault();
				if ( typeof wpuk_notification_bar !== 'undefined' && wpuk_notification_bar.settings_url ) {
					window.location.href = wpuk_notification_bar.settings_url;
				}
			});
		}

		// Initialize color pickers on the settings page
		if ( $('#wpuk_noti_bg_color').length > 0 || $('#wpuk_noti_txt_color').length > 0 ) {
			if ( typeof $.fn.wpColorPicker !== 'undefined' ) {
				$('#wpuk_noti_bg_color').wpColorPicker();
				$('#wpuk_noti_txt_color').wpColorPicker();
			}
		}

		// Clamp number inputs to their min/max on the settings page
		$(document).on('input', 'input[type="number"]', function () {
			var value = parseInt($(this).val(), 10);
			var min   = parseInt($(this).attr('min'), 10);
			var max   = parseInt($(this).attr('max'), 10);

			if (!isNaN(min) && value < min) {
				$(this).val(min);
			}
			if (!isNaN(max) && value > max) {
				$(this).val(max);
			}
		});

	});

})( jQuery );
