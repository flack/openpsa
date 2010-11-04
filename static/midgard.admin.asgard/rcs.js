var _l10n_select_two = 'select exactly two choices';

var prev = new Array(2);
prev[0] = '';
prev[1] = '';

jQuery(document).ready(function()
{
    jQuery('#midgard_admin_asgard_rcs_version_compare tbody td input[type="checkbox"]').click(function()
    {
        toggle_checkbox(this);
        
        if (jQuery(this).attr('checked'))
        {
            jQuery(this.parentNode.parentNode).addClass('selected');
        }
        else
        {
            jQuery(this.parentNode.parentNode).removeClass('selected');
        }
    });
    
    jQuery('#midgard_admin_asgard_rcs_version_compare').submit(function()
    {
        var count = 0;
        jQuery('#midgard_admin_asgard_rcs_version_compare').find('tbody td input[type="checkbox"]').each(function(i)
        {
            if (jQuery(this).attr('checked'))
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
    if (!jQuery(object).attr('checked'))
    {
        return;
    }
    
    if (prev[1])
    {
        jQuery('#' + prev[1]).attr('checked', '');
        jQuery('#' + prev[1] + '_row').removeClass('selected');
    }
    
    if (prev[0])
    {
        prev[1] = prev[0];
    }
    
    prev[0] = object.id;
}