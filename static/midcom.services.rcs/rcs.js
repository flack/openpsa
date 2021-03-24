function init_controls(selector) 
{
    const form = $(selector);
    
    if (form.find('tbody').children().length < 2) {
        form.find('[name="first"], [name="last"], [name="f_compare"]').css('visibility', 'hidden');
        return;
    }
    
    if (form.find('[name="first"]:checked').length === 0) {
        form.find('[name="first"]').eq(1).trigger('click');
    }
    
    if (form.find('[name="last"]:checked').length === 0) {
        form.find('[name="last"]').eq(0).trigger('click');
    }

    form.on('change', '[name="first"]', function() {
        $('[name="f_compare"]').prop('disabled', $(this).val() == $('[name="last"]:checked').val());
    });
    
    form.on('change', '[name="last"]', function() {
        $('[name="f_compare"]').prop('disabled', $(this).val() == $('[name="first"]:checked').val());
    });
}