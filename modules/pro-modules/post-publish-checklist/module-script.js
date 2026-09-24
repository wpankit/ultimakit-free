jQuery(document).ready(function($) {
    $('#add-checklist-item').on('click', function() {
        var newItem = $('#new-checklist-item').val().trim();
        if (newItem) {
            $('#checklist-items').append('<li><label><input type="checkbox" name="checklist_items[]" value="' + newItem + '"> ' + newItem + '</label></li>');
            $('#new-checklist-item').val(''); // Clear input
        }
    });
});