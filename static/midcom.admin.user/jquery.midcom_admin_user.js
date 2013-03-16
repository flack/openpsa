$(document).ready(function()
{
    $('#midcom_admin_user_batch_process tbody tr td:first').addClass('first');
    $('#midcom_admin_user_batch_process tbody tr td:last').addClass('last');
    $('#midcom_admin_user_batch_process tbody tr:even').addClass('even');

    $("#midcom_admin_user_batch_process tbody").on('change', 'input[type="checkbox"]', function()
    {
        $(this).closest('tr').toggleClass('row_selected', $(this).prop('checked'));
        var all_selected = ($('#midcom_admin_user_batch_process tbody input[type="checkbox"]:not(":checked")').length === 0),
        none_selected = ($('#midcom_admin_user_batch_process tbody input[type="checkbox"]:checked').length === 0);
        $('#select_all').prop('checked', all_selected);
        $('#midcom_admin_user_action')
            .prop('disabled', none_selected)
            .trigger('change');
    });

    $('#invert_selection').on('click', function(event)
    {
        $('#midcom_admin_user_batch_process table tbody input[type="checkbox"]').each(function()
        {
            // Skip the write protected
            if (!$(this).prop('disabled'))
            {
                $(this).click();
            }
        });
        event.preventDefault();
    });

    $('#select_all').on('click', function()
    {
        var checked = $(this).is(':checked');

        $('#midcom_admin_user_batch_process table tbody input[type="checkbox"]').each(function()
        {
            // Skip the write protected
            if (   !$(this).prop('disabled')
                && $(this).prop('checked') !== checked)
            {
                $(this).click();
            }
        });
    });

    $('#midcom_admin_user_batch_process').submit(function()
    {
        var action = MIDCOM_PAGE_PREFIX + '__mfa/asgard_midcom.admin.user/batch/' + $('#midcom_admin_user_action').val() + '/';

        if ($('#midcom_admin_user_search').val())
        {
            action += '?midcom_admin_user_search=' + $('#midcom_admin_user_search').val();
        }
        $(this).attr('action', action);
        return true;
    });

    // Change on the user action
    $('#midcom_admin_user_action').change(function()
    {
        // On each change the passwords field has to go - otherwise it might
        // change secretly all the passwords of selected people
        $('#midcom_admin_user_action_passwords').remove();

        switch ($(this).val())
        {
            case 'passwords':
                $('#midcom_admin_user_group').hide();

                $('<div></div>')
                    .attr('id', 'midcom_admin_user_action_passwords')
                    .appendTo('#midcom_admin_user_batch_process');

                // Load the form for outputting the style
                date = new Date();
                $('#midcom_admin_user_action_passwords').load(MIDCOM_PAGE_PREFIX + '__mfa/asgard_midcom.admin.user/password/email/');

                break;

            case 'groupadd':
                $('#midcom_admin_user_group').css({display: 'inline'});
                break;
        }
        $(this).nextAll('input[type="submit"]').prop('disabled', !$(this).val());
    });

    $('#midcom_admin_user_batch_process table tbody input[type="checkbox"]:first').trigger('change');

    $('#midcom_admin_user_batch_process table').tablesorter(
    {
    //    widgets: ['column_highlight'],
        sortList: [[2,0]]
    });
});

