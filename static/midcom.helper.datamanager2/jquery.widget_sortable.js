jQuery.fn.create_sortable = function()
{
    // Hide the inputs
    jQuery(this).find('input[type="text"]').css({display: 'none'});
    
    jQuery(this).each(function(i)
    {
        jQuery(this).sortable({
            containment: 'parent',
            change: function(e, ui)
            {
                // Update all the text inputs to keep track on the changes
                jQuery(this.parentNode).find('input[type="text"]').each(function(i)
                {
                    jQuery(this).attr('value', i + 1);
                });
            }
        });
    });
}

jQuery(document).ready(function()
{
    jQuery('body p.sortable-help').css({display: 'none !important'});
    jQuery('body p.sortable-help-jquery').css({display: 'block !important'});
});
