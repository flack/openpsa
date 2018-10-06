$(document).ready(function() {
    $('<div></div>')
        .attr('id', 'midgard_admin_asgard_resizer')
        .css('left', $('#content').css('margin-left'))
        .mouseover(function() {
            $(this).addClass('hover');
        })
        .mouseout(function() {
            $(this).removeClass('hover');
        })
        .appendTo('#container-wrapper');

    $('#midgard_admin_asgard_resizer').draggable({
        axis: 'axis-x',
        containment: '#container-wrapper',
        stop: function(event, ui) {
            var offset = ui.offset.left,
                navigation_width = offset - 31,
                content_margin_left = offset + 1;

            if (offset < 0) {
                offset = 0;
            }

            $('#navigation').css('width', navigation_width + 'px');
            $('#content').css('margin-left', content_margin_left + 'px');

            $.post(MIDGARD_ROOT + '__mfa/asgard/preferences/ajax/', {offset: offset});
        }
    });

    $('body').on('click', '.section_content a', function() {
        var scroll_top = $('#navigation').scrollTop(),
        section = $('#navigation .section.expanded').find('h3').text();
        sessionStorage.setItem(section + '_scrolltop', scroll_top);
    });

    var section = $('#navigation .section.expanded').find('h3').text();
    if (sessionStorage.getItem(section + '_scrolltop')) {
        $('#navigation').scrollTop(sessionStorage.getItem(section + '_scrolltop'));
    }
});
