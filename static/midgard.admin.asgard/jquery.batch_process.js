jQuery.fn.check_all = function(target)
{
    var checked = jQuery(this).is(':checked');
    
    jQuery(target).find("input[type='checkbox']").each(function(i)
    {
        // Skip the write protected
        if (jQuery(this).is(':disabled'))
        {
            return;
        }
        
        if (checked)
        {
            jQuery(this).prop('checked', true);
        }
        else
        {
            jQuery(this).prop('checked', false);
        }
        
        // Trigger the onChange event of the input
        jQuery(this).change();
    });
}

jQuery.fn.invert_selection = function(target)
{
    jQuery(target).find("input[type='checkbox']").each(function(i)
    {
        // Skip the write protected
        if (jQuery(this).is(':disabled'))
        {
            return;
        }
        
        jQuery(this).prop('checked', !jQuery(this).is(':checked'));
        
        // Trigger the onChange event of the input
        jQuery(this).change();
    });
    
    jQuery(this).prop('checked', false);
}

jQuery(document).ready(function()
{
    jQuery('#batch_process tbody tr').find('td:first').addClass('first');
    jQuery('#batch_process tbody tr').find('td:last').addClass('last');
    
    jQuery("#batch_process tbody input[type='checkbox']").each(function(i)
    {
        jQuery(this).change(function()
        {
            var object = this.parentNode;
            var n = 0;
            
            while (!object.tagName.match(/tr/i))
            {
                object = object.parentNode;
                
                // Protect against infinite loops
                if (n > 20)
                {
                    return;
                }
            }
            
            if (jQuery(this).is(':checked'))
            {
                jQuery(object).addClass('row_selected');
            }
            else
            {
                jQuery(object).removeClass('row_selected');
            }
        });
    });
});
