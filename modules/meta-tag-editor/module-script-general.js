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

    // Wrap your code in a try-catch block
    try {
        jQuery(document).ready(function() {
            // Your modal code here
            jQuery('.ultimakit_module_meta_tag_editor').on('click', function() {
                // Modal code
                jQuery("#ultimakit_module_meta_tag_editor_modal").modal('show'); // Show the modal
            });
        });
    } catch (error) {
        console.error('Error in script:', error);
        console.trace();
    }


	jQuery(document).ready(function ($) {

        // Add click event to the meta tag editor modal
        if( $('.ultimakit_module_meta_tag_editor').length > 0 ){
            $('.ultimakit_module_meta_tag_editor').on('click', function (e) {
                e.preventDefault(); // Prevent the default action of the click event
                $("#ultimakit_module_meta_tag_editor_modal").modal('show'); // Show the modal
            });
        }

        jQuery('#ultimakit_module_meta_tag_editor_modal').modal();
        if( $('.ultimakit-meta-tag-editor').length > 0 ){
            const titleInput = $('#wpuk_meta_title');
            const descriptionInput = $('#wpuk_meta_description');
            const titleCount = $('#wpuk_title_count');
            const descriptionCount = $('#wpuk_description_count');
            const previewTitle = $('#wpuk_preview_title');
            const previewDescription = $('#wpuk_preview_description');

            function updatePreview() {
                const title = titleInput.val();
                const description = descriptionInput.val();

                previewTitle.text(title || 'Title Preview');
                previewDescription.text(description || 'Description Preview');
                titleCount.text(title.length);
                descriptionCount.text(description.length);

                // Add warning classes for length
                titleCount.toggleClass('warning', title.length > 60);
                descriptionCount.toggleClass('warning', description.length > 160);
            }

            titleInput.on('input', updatePreview);
            descriptionInput.on('input', updatePreview);

            // Initialize preview
            updatePreview();
        }

	});

})( jQuery );