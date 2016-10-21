$(document).ready(function()
{
    $('#midcom_admin_folder_order_form ul.sortable input').css('display', 'none');
    $('#midcom_admin_folder_order_form_sort_type div.form_toolbar').css('display', 'none');
    
    $('#midcom_admin_folder_order_form_sort_type select').on('change', function()
    {
        var form = $('#midcom_admin_folder_order_form_sort_type');
        $.post(form.attr('action') + '?ajax', form.serialize(), function(data)
        {
            $('#midcom_admin_folder_order_form_wrapper').html(data);
        });
        window.location.hash = '#midcom_admin_folder_order_form_wrapper';
    });

    $('#midcom_admin_folder_order_form').submit(function()
    {
        $(this).find('ul').each(function()
        {
            $(this).find('li').each(function(i)
            {
                $(this).find('input').val(i);
            });
        });
    });
});
