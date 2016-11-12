$(document).ready(function()
{
    $('#midgard_admin_user_batch_process tbody tr td:first').addClass('first');
    $('#midgard_admin_user_batch_process tbody tr td:last').addClass('last');
    $('#midgard_admin_user_batch_process tbody tr:even').addClass('even');

    $("#midgard_admin_user_batch_process tbody").on('change', 'input[type="checkbox"]', function()
    {
        $(this).closest('tr').toggleClass('row_selected', $(this).prop('checked'));
        var all_selected = ($('#midgard_admin_user_batch_process tbody input[type="checkbox"]:not(":checked")').length === 0),
        none_selected = ($('#midgard_admin_user_batch_process tbody input[type="checkbox"]:checked').length === 0);
        $('#select_all').prop('checked', all_selected);
        $('#midgard_admin_user_action')
            .prop('disabled', none_selected)
            .trigger('change');
    });

    $('#invert_selection').on('click', function(event)
    {
        $('#midgard_admin_user_batch_process table tbody input[type="checkbox"]').each(function()
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

        $('#midgard_admin_user_batch_process table tbody input[type="checkbox"]').each(function()
        {
            // Skip the write protected
            if (   !$(this).prop('disabled')
                && $(this).prop('checked') !== checked)
            {
                $(this).click();
            }
        });
    });

    $('#midgard_admin_user_batch_process').submit(function()
    {
        var action = MIDCOM_PAGE_PREFIX + '__mfa/asgard_midgard.admin.user/batch/' + $('#midgard_admin_user_action').val() + '/';

        if ($('#midgard_admin_user_search').val())
        {
            action += '?midgard_admin_user_search=' + $('#midgard_admin_user_search').val();
        }
        $(this).attr('action', action);
        return true;
    });

    // Change on the user action
    $('#midgard_admin_user_action').change(function()
    {
        // On each change the passwords field has to go - otherwise it might
        // change secretly all the passwords of selected people
        $('#midgard_admin_user_action_passwords').remove();

        switch ($(this).val())
        {
            case 'passwords':
                $('#midgard_admin_user_group').hide();

                $('<div></div>')
                    .attr('id', 'midgard_admin_user_action_passwords')
                    .appendTo('#midgard_admin_user_batch_process');

                // Load the form for outputting the style
                $('#midgard_admin_user_action_passwords').load(MIDCOM_PAGE_PREFIX + '__mfa/asgard_midgard.admin.user/password/email/');

                break;

            case 'groupadd':
                $('#midgard_admin_user_group').css({display: 'inline'});
                break;
        }
        $(this).nextAll('input[type="submit"]').prop('disabled', !$(this).val());
    });

    $('#midgard_admin_user_batch_process table tbody input[type="checkbox"]:first').trigger('change');

    $('#midgard_admin_user_batch_process table').tablesorter(
    {
    //    widgets: ['column_highlight'],
        sortList: [[2,0]]
    });
});

