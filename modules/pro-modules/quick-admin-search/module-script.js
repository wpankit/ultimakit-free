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

    $(document).ready(function() {
	    setupAdminSearch();
	});

	function setupAdminSearch() {
	    var $input = $('#ultimakit-admin-bar-search-input');
	    var $inputWrapper = $('<div>', { id: 'ultimakit-admin-bar-search-input-wrapper' }).insertBefore($input);
	    $input.appendTo($inputWrapper);
	    var $spinner = $('<div>', { id: 'ultimakit-admin-bar-search-spinner' }).appendTo($inputWrapper);
	    var $dropdown = $('<div>', { id: 'ultimakit-admin-bar-search-dropdown' }).css('display', 'none').insertAfter($inputWrapper);

	    // Event handling for search input
	    $input.on('keyup', function() {
	        handleSearch($input, $spinner, $dropdown);
	    });

	    // Global click to hide the dropdown if clicked outside
	    $(document).on('click', function(e) {
	        if (!$(e.target).closest('#ultimakit-admin-bar-search-input-wrapper').length) {
	            $dropdown.hide();
	        }
	    });
	}

	function handleSearch($input, $spinner, $dropdown) {
	    var search = $input.val();
	    if (search.length > 2) {
	        $spinner.show();
	        performSearch(search, $spinner, $dropdown);
	    } else {
	        $dropdown.hide();
	    }
	}

	function performSearch(search, $spinner, $dropdown) {
	    $.ajax({
	        url: ultimakit_quick_admin_search.ajax_url,
	        type: 'post',
	        data: {
	            action: 'ultimakit_admin_search_action',
	            nonce: ultimakit_quick_admin_search.ajax_nonce,
	            search: search,
	            is_admin: window.location.href.indexOf("/wp-admin/") > -1
	        },
	        success: function(response) {
	            if (response.data && response.data.html) {
	                $dropdown.empty().append(response.data.html).show();
	            } else {
	                $dropdown.empty().append('<div>No results found.</div>').show();
	            }
	            if (window.location.href.indexOf("/wp-admin/") > -1) {
	                $dropdown.find('a').attr('onclick', 'window.location=this.href;return false;');
	            }
	            $spinner.hide();
	        },
	        error: function() {
	            $dropdown.empty().append('<div>Error performing search.</div>').show();
	            $spinner.hide();
	        }
	    });
	}


})( jQuery );