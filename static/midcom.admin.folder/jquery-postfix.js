jQuery(document).ready(function()
{
    jQuery('#midcom_admin_folder_order_form ul.sortable input').css('display', 'none');
    jQuery('#midcom_admin_folder_order_form_sort_type div.form_toolbar').css('display', 'none');
    
    jQuery('#midcom_admin_folder_order_form_sort_type select').change(function()
    {
        jQuery('#midcom_admin_folder_order_form_sort_type').submit();
    });
    
    jQuery('#midcom_admin_folder_order_form_sort_type').submit(function()
    {
        var date = new Date();
        var location = jQuery('#midcom_admin_folder_order_form_sort_type').attr('action') + '?ajax&time=' + date.getTime();
        
        jQuery('#midcom_admin_folder_order_form_sort_type').ajaxSubmit
        (
            {
                url: location,
                target: '#midcom_admin_folder_order_form_wrapper'
            }
        );
        window.location.hash = '#midcom_admin_folder_order_form_wrapper';
        return false;
    });
    
    jQuery('#midcom_admin_folder_order_form').submit(function()
    {
        jQuery(this).find('ul').each(function(i)
        {
            var count = jQuery(this).find('li').size();
            
            jQuery(this).find('li').each(function(i)
            {
                jQuery(this).find('input').attr('value', i);
            });
        });
    });
});
