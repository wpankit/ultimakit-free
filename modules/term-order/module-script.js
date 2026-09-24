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

    	if( $('.ultimakit_module_term_order').length > 0 ){

		    $('.ultimakit_module_term_order').on('click', function (e) {
		        e.preventDefault(); // Prevent the default action of the click event
		        $("#ultimakit_module_term_order_modal").modal('show'); // Show the modal
		    });
		    
		}

	    $('#the-list').sortable({
	        update: function(event, ui) {

	            var termOrder = [];
	            $(this).find('tr').each(function(index) {
	                var termId = $(this).attr('id').replace('tag-', '');
	                termOrder.push({ term_id: termId, position: index });
	            });

	            $.post(ultimakit_term_order.ajax_url, {
	                action: 'ultimakit_save_term_order',
	                nonce: ultimakit_term_order.ajax_nonce,
	                order: termOrder,
	                taxonomy: ultimakit_term_order.taxonomy
	            },
	            function(response) {

	            	console.log( response );
	                if (response.success) {
	                    // Handle success (e.g., show a notification)
	                } else {
	                    // Handle error (e.g., show an error message)
	                }
	            });
	        }
	    });

	    // update_term_meta($term_id, 'order', $position
	});



})( jQuery );