$(document).ready(function()
{
    $('form.filter .filter_apply').bind('click', function()
    {
        $(this).closest('form').submit();
    });
    $('form.filter .filter_unset').bind('click', function()
    {
        var form = $(this).closest('form');
        console.log(form);
        form
            .append('<input type="hidden" name="unset_filter" value="' + form.attr('id') + '" />')
            .submit();
    });
});