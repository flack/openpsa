$.fn.tree_checker = function() {
    $(this).on('change', 'label > input[type="checkbox"]', function() {
        if (this.checked) {
            $(this).closest('li').find('li')
                .removeClass('readonly')
                .find('input').prop('disabled', false);

            $(this).closest('label').removeClass('deselected');
        } else {
            $(this).closest('li').find('li')
                .addClass('readonly')
                .find('input').prop('disabled', true);
            $(this).closest('label').addClass('deselected');
        }
    });
}
