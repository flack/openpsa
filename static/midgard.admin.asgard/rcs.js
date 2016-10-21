var _l10n_select_two = 'select exactly two choices';

var prev = new Array(2);
prev[0] = '';
prev[1] = '';

$(document).ready(function()
{
    $('#midgard_admin_asgard_rcs_version_compare tbody td input[type="checkbox"]').click(function()
    {
        toggle_checkbox(this);

        if ($(this).is(':checked'))
        {
            $(this.parentNode.parentNode).addClass('selected');
        }
        else
        {
            $(this.parentNode.parentNode).removeClass('selected');
        }
    });

    $('#midgard_admin_asgard_rcs_version_compare').submit(function()
    {
        var count = 0;
        $('#midgard_admin_asgard_rcs_version_compare').find('tbody td input[type="checkbox"]').each(function()
        {
            if ($(this).is(':checked'))
            {
                count++;
            }
        });

        if (count == 2)
        {
            return true;
        }

        alert(_l10n_select_two);
        return false;
    });
});

function toggle_checkbox(object)
{
    if (!$(object).is(':checked'))
    {
        return;
    }

    if (prev[1])
    {
        $('#' + prev[1]).prop('checked', false);
        $('#' + prev[1] + '_row').removeClass('selected');
    }

    if (prev[0])
    {
        prev[1] = prev[0];
    }

    prev[0] = object.id;
}
