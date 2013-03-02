$(document).ready(function()
{
    $('<div></div>')
        .attr('id', 'midgard_admin_asgard_resizer')
        .css('left', $('#content').css('margin-left'))
        .mouseover(function()
        {
            $(this).addClass('hover');
        })
        .mouseout(function()
        {
            $(this).removeClass('hover');
        })
        .appendTo('#container-wrapper');

    $('#midgard_admin_asgard_resizer').draggable({
        axis: 'axis-x',
        containment: '#container-wrapper',
        stop: function(event, ui)
        {
            var offset = ui.offset.left;

            if (offset < 0)
            {
                offset = 0;
            }

            var navigation_width = offset - 31;
            var content_margin_left = offset + 1;

            $('#navigation').css('width', navigation_width + 'px');
            $('#content').css('margin-left', content_margin_left + 'px');

            $.post(MIDGARD_ROOT + '__mfa/asgard/preferences/ajax/', {offset: offset});
        }
    });
});
