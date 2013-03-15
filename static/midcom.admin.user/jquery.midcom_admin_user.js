var active = null;
$(document).ready(function()
{
    $('#midcom_admin_user_batch_process tbody tr td:first').addClass('first');
    $('#midcom_admin_user_batch_process tbody tr td:last').addClass('last');
    $('#midcom_admin_user_batch_process tbody tr:even').addClass('even');

    $("#midcom_admin_user_batch_process tbody").on('change', 'input[type="checkbox"]', function()
    {
        $(this).closest('tr').toggleClass('row_selected', $(this).prop('checked'));
        var all_selected = ($('#midcom_admin_user_batch_process tbody input[type="checkbox"]:not(":checked")').length === 0);
        $('#select_all').prop('checked', all_selected);
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

    // Change on the user action
    $('#midcom_admin_user_action').change(function()
    {
        if (active)
        {
            $(active).hide();
        }

        // On each change the passwords field has to go - otherwise it might
        // change secretly all the passwords of selected people
        $('#midcom_admin_user_action_passwords').remove();
        $('#midcom_admin_user_batch_process').submit(function()
        {
            var action = MIDCOM_PAGE_PREFIX + '__mfa/asgard_midcom.admin.user/';
            $(this).attr('action', action);
        });

        $(this).attr('value');
        switch ($(this).attr('value'))
        {
            case 'passwords':
                active = '#midcom_admin_user_action_passwords';

                $('<div></div>')
                    .attr('id', 'midcom_admin_user_action_passwords')
                    .appendTo('#midcom_admin_user_batch_process');

                // Load the form for outputting the style
                date = new Date();
                $('#midcom_admin_user_action_passwords').load(MIDCOM_PAGE_PREFIX + '__mfa/asgard_midcom.admin.user/password/batch/?ajax&timestamp=' + date.getTime());

                $('#midcom_admin_user_batch_process').submit(function()
                {
                    var action = MIDCOM_PAGE_PREFIX + '__mfa/asgard_midcom.admin.user/password/batch/?ajax';
                    $(this).attr('action', action);
                });
                break;

            case 'groupadd':
                $('#midcom_admin_user_group').css({display: 'inline'});
                active = '#midcom_admin_user_group';
                break;

            default:
                active = null;

                // Return the original submit functionality
                $('#midcom_admin_user_batch_process').submit(function()
                {
                    var action = MIDCOM_PAGE_PREFIX + '__mfa/asgard_midcom.admin.user/';
                    $(this).attr('action', action);
                    return true;
                });
        }
    });

    $('#midcom_admin_user_batch_process table').tablesorter(
    {
    //    widgets: ['column_highlight'],
        sortList: [[2,0]]
    });
});

