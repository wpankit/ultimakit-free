(function() {
    const { select, subscribe } = wp.data;

    let lastContent = '';

    function countWords(str) {
        str = str.replace(/(^\s*)|(\s*$)/gi,"");
        str = str.replace(/[ ]{2,}/gi," ");
        str = str.replace(/\n /,"\n");
        return str.split(' ').filter(function(str){return str!="";}).length;
    }

    function updateWordCount() {
        const content = select('core/editor').getEditedPostContent();
        
        // Only update if content has changed
        if (content !== lastContent) {
            lastContent = content;
            
            // Strip HTML tags and count words
            const strippedContent = content.replace(/(<([^>]+)>)/gi, "");
            const wordCount = countWords(strippedContent);
            
            // Update word count display
            const wordCountElement = document.getElementById('current-word-count');
            if (wordCountElement) {
                wordCountElement.textContent = wordCount;
                
                // Get target word count
                const targetInput = document.getElementById('target-word-count');
                const targetCount = targetInput ? parseInt(targetInput.value) : 0;
                
                // Update colors based on target
                if (targetCount > 0) {
                    wordCountElement.style.color = wordCount >= targetCount ? '#00a32a' : '#cc1818';
                } else {
                    wordCountElement.style.color = '#1e1e1e';
                }
            }
        }
    }

    // Subscribe to content changes
    subscribe(updateWordCount);

    // Add event listener for target word count changes
    document.addEventListener('input', function(e) {
        if (e.target.id === 'target-word-count') {
            updateWordCount();
        }
    });
})();


