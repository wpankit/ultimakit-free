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

	    $('.ultimakit_module_hide_admin_notices_advanced').on('click', function (e) {
	        e.preventDefault(); // Prevent the default action of the click event
	        $("#ultimakit_module_hide_admin_notices_advanced_modal").modal('show'); // Show the modal
	    });

		var selectedPermissions = {};

		$('.role .form-check-input').on('change', function(e) {
		    e.preventDefault();
		    
		    var role = $(this).closest('.role').data('role');
		    var $role = $(this).closest('.role');

		    // Parse the existing JSON string from the input field
		    var existingPermissionsJson = $('#ultimakit_hide_notices_for').val();
		    var existingPermissions = existingPermissionsJson ? JSON.parse(existingPermissionsJson) : {};

		    // Initialize or update selectedPermissions for the current role
		    var selectedPermissions = existingPermissions[role] || [];

		    $role.find('.form-check-input').each(function() {
		        if ($(this).prop('checked')) {
		            if ($(this).hasClass('all_check')) {
		                selectedPermissions = ['all_check'];
		                $role.find('.plugin_check, .theme_check, .core_check').prop('checked', false);
		                return false; // Break the loop
		            }
		            selectedPermissions.push($(this).attr('class').split(' ').pop());
		        } else {
		            // Remove unchecked checkbox value from selectedPermissions
		            var index = selectedPermissions.indexOf($(this).attr('class').split(' ').pop());
		            if (index !== -1) {
		                selectedPermissions.splice(index, 1);
		            }
		        }
		    });

		    // Update or add the selectedPermissions for the current role
		    existingPermissions[role] = selectedPermissions;

		    // Remove the node if selectedPermissions is empty
		    if (selectedPermissions.length === 0) {
		        delete existingPermissions[role];
		    }

		    // Update the input field with the merged JSON string
		    $('#ultimakit_hide_notices_for').val(JSON.stringify(existingPermissions));
		});




	});


})( jQuery );