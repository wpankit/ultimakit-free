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

		if( $('.ultimakit_module_redirect_after_login').length > 0 ){
		    $('.ultimakit_module_redirect_after_login').on('click', function (e) {
		        e.preventDefault(); // Prevent the default action of the click event
		        $("#ultimakit_module_redirect_after_login_modal").modal('show'); // Show the modal
		    });

		   	$('.select2').select2({
	            multiple: true,
	            tags: true
	        }).val( ultimakit_redirect_after_login.selected_roles.split(',') ).trigger('change');

	        $('.select2').on('change', function() {
		        var selectedValues = $(this).val();
		        $('#user_roles_list_val').val(selectedValues);
		    });
	    }

	});


})( jQuery );