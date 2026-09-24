jQuery(document).ready(function($) {
    function countWords(str) {
        // Remove HTML tags
        str = str.replace(/<\/?[^>]+(>|$)/g, "");
        // Remove special characters and extra spaces
        str = str.replace(/[^\w\s]/g, " ");
        str = str.replace(/(^\s*)|(\s*$)/gi, "");
        str = str.replace(/\s+/g, " ");
        return str.split(' ').filter(Boolean).length;
    }

    function updateWordCount() {
        let content = '';

        // Get content from TinyMCE if it's available and in Visual mode
        if (window.tinyMCE && window.tinyMCE.get('content') && !window.tinyMCE.get('content').isHidden()) {
            content = window.tinyMCE.get('content').getContent();
        } else {
            // Fallback to textarea content
            content = $('#content').val() || '';
        }

        const wordCount = countWords(content);
        const targetCount = parseInt($('#target-word-count').val()) || 0;
        
        $('#current-word-count').text(wordCount);
        
        if (targetCount > 0) {
            $('#current-word-count').css('color', wordCount >= targetCount ? '#00a32a' : '#cc1818');
        } else {
            $('#current-word-count').css('color', '#1e1e1e');
        }
    }

    // Initialize after TinyMCE is ready
    $(document).on('tinymce-editor-init', function(event, editor) {
        // Monitor Visual Editor changes
        editor.on('keyup change', updateWordCount);
        
        // Initial count
        updateWordCount();
    });

    // Monitor Text Editor changes
    $('#content').on('keyup change', updateWordCount);
    
    // Monitor target word count changes
    $('#target-word-count').on('input', updateWordCount);

    // Ensure initial count happens
    setTimeout(updateWordCount, 1000);
});