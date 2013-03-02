if (!jQuery)
{
    jQuery = jQuery.noConflict();
}
jQuery(document).ready(function()
{
    jQuery('<div></div>')
        .attr('id', 'midgard_admin_asgard_resizer')
        .css('left', jQuery('#content').css('margin-left'))
        .mouseover(function()
        {
            jQuery(this).addClass('hover');
        })
        .mouseout(function()
        {
            jQuery(this).removeClass('hover');
        })
        .appendTo('#container-wrapper');

    jQuery('#midgard_admin_asgard_resizer').draggable({
        axis: 'axis-x',
        containment: '#container-wrapper',
        stop: function()
        {
            var offset = jQuery(this).offset().left;

            if (offset < 0)
            {
                offset = 0;
            }

            var navigation_width = offset - 31;
            var content_margin_left = offset + 1;

            jQuery('#navigation').css('width', navigation_width + 'px');
            jQuery('#content').css('margin-left', content_margin_left + 'px');

            jQuery.post(MIDGARD_ROOT + '__mfa/asgard/preferences/ajax/', {offset: offset});
        }
    });
});
