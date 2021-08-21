function openpsa2_add_toolbar_toggle()
{
    let hide = $('<li class="hide-navigation"><a><i class="fa fa-angle-double-left"></i></a></li>')
        .on('click', function() {
            $('body').removeClass('navigation-visible');
            $('body').addClass('navigation-hidden');
            $(window).trigger('resize');
        });
    let show = $('<li class="show-navigation"><a><i class="fa fa-angle-double-right"></i></a></li>')
        .on('click', function() {
            $('body').addClass('navigation-visible');
            $('body').removeClass('navigation-hidden');
            $(window).trigger('resize');
        });

    if (document.getElementById('org_openpsa_toolbar') && $('#org_openpsa_toolbar > .view_toolbar').length === 0) {
        $('#org_openpsa_toolbar').append($('<ul class="midcom_toolbar view_toolbar"></ul>'));
    }
    $('#org_openpsa_toolbar > .view_toolbar')
        .prepend(show)
        .prepend(hide);
}
