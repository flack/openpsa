jQuery.fn.tree_checker = function()
{
    jQuery(this).find('label').toggle(
    function()
    {
        var id = '#' + jQuery(this).attr('for');

        jQuery(id).prop('checked', false);
        jQuery(this.parentNode).find('li label').toggle(function(){}, function(){});
        jQuery(this.parentNode).find('li').addClass('readonly');

        jQuery(this).addClass('deselected');
    },
    function()
    {
        var id = '#' + jQuery(this).attr('for');

        jQuery(id).prop('checked', true);
        jQuery(this.parentNode).find('li').tree_checker();
        jQuery(this.parentNode).find('li').removeClass('readonly');

        jQuery(this).removeClass('deselected');
    });

    jQuery(this).find('input').click(function()
    {
        if (jQuery(this).is(':checked'))
        {
            jQuery(this.parentNode).find('li').removeClass('readonly');
            jQuery(this.parentNode).find("label[for='" + jQuery(this).attr('id') + "']").removeClass('deselected');
        }
        else
        {
            jQuery(this.parentNode).find('li').addClass('readonly');
            jQuery(this.parentNode).find("label[for='" + jQuery(this).attr('id') + "']").addClass('deselected');
        }
    });
}
