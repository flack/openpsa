var active = null;
jQuery.fn.check_all = function(target)
{
    var checked = jQuery(this).attr('checked') ? true : false;
    
    jQuery(target).find("input[type='checkbox']").each(function(i)
    {
        // Skip the write protected
        if (jQuery(this).attr('disabled'))
        {
            return;
        }
        
        if (checked)
        {
            jQuery(this).attr('checked', 'checked');
        }
        else
        {
            jQuery(this).attr('checked', '');
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
        if (jQuery(this).attr('disabled'))
        {
            return;
        }
        
        if (jQuery(this).attr('checked'))
        {
            jQuery(this).attr('checked', '');
        }
        else
        {
            jQuery(this).attr('checked', 'checked');
        }
        
        // Trigger the onChange event of the input
        jQuery(this).change();
    });
    
    jQuery(this).attr('checked', '');
}

jQuery(document).ready(function()
{
    jQuery('#midcom_admin_user_batch_process tbody tr').find('td:first').addClass('first');
    jQuery('#midcom_admin_user_batch_process tbody tr').find('td:last').addClass('last');
    
    jQuery("#midcom_admin_user_batch_process tbody input[type='checkbox']").each(function(i)
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
            
            if (jQuery(this).attr('checked'))
            {
                jQuery(object).addClass('row_selected');
            }
            else
            {
                jQuery(object).removeClass('row_selected');
            }
        });
    });
    
    // Change on the user action
    jQuery('#midcom_admin_user_action').change(function()
    {
        if (active)
        {
            jQuery(active).css({display: 'none'});
        }
        
        // On each change the passwords field has to go - otherwise it might
        // change secretly all the passwords of selected people
        jQuery('#midcom_admin_user_action_passwords').remove();
        jQuery('#midcom_admin_user_batch_process').submit(function()
        {
            var action = MIDCOM_PAGE_PREFIX + '__mfa/asgard_midcom.admin.user/';
            jQuery(this).attr('action', action);
        });
        
        jQuery(this).attr('value');
        switch (jQuery(this).attr('value'))
        {
            case 'passwords':
                active = '#midcom_admin_user_action_passwords';
                
                if (document.getElementById('midcom_admin_user_action_passwords'))
                {
                    jQuery('#midcom_admin_user_action_passwords').css({display:'block'});
                    break;
                }
                
                jQuery('<div></div>')
                    .attr('id', 'midcom_admin_user_action_passwords')
                    .appendTo('#midcom_admin_user_batch_process');
                
                // Load the form for outputting the style
                date = new Date();
                jQuery('#midcom_admin_user_action_passwords').load(MIDCOM_PAGE_PREFIX + '__mfa/asgard_midcom.admin.user/password/batch/?ajax&timestamp=' + date.getTime());
                
                jQuery('#midcom_admin_user_batch_process').submit(function()
                {
                    var action = MIDCOM_PAGE_PREFIX + '__mfa/asgard_midcom.admin.user/password/batch/?ajax';
                    jQuery(this).attr('action', action);
                });
                break;
            
            case 'groupadd':
                jQuery('#midcom_admin_user_group').css({display: 'inline'});
                active = '#midcom_admin_user_group';
                break;
            
            default:
                active = null;
                
                // Return the original submit functionality
                jQuery('#midcom_admin_user_batch_process').submit(function()
                {
                    var action = MIDCOM_PAGE_PREFIX + '__mfa/asgard_midcom.admin.user/';
                    jQuery(this).attr('action', action);
                    return true;
                });
        }
    });
    
    jQuery('#midcom_admin_user_batch_process table').tablesorter(
    {
    //    widgets: ['column_highlight'],
        sortList: [[2,0]]
    });
});

