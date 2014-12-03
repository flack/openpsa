var MIDCOM_SERVICES_UIMESSAGES_TYPE_INFO = 'info',
MIDCOM_SERVICES_UIMESSAGES_TYPE_OK = 'ok',
MIDCOM_SERVICES_UIMESSAGES_TYPE_WARNING = 'warning',
MIDCOM_SERVICES_UIMESSAGES_TYPE_ERROR = 'error',
MIDCOM_SERVICES_UIMESSAGES_TYPE_DEBUG = 'ok',

MIDCOM_SERVICES_UIMESSAGES_REMOVE = true,
MIDCOM_SERVICES_UIMESSAGES_ERROR_HIGHLIGHT = true,
MIDCOM_SERVICES_UIMESSAGES_SLIDE_SPEED = 1000,
MIDCOM_SERVICES_UIMESSAGES_SLIDE_DELAY = 2000,

MIDCOM_SERVICES_UIMESSAGES_INDEX = 0;

$.midcom_services_uimessage_add = function(options) {
    $('#midcom_services_uimessages_wrapper').midcom_services_uimessage(options);
};

$.fn.midcom_services_uimessage = function(options)
{
    var id = 'midcom_services_uimessages_' + MIDCOM_SERVICES_UIMESSAGES_INDEX;

    $('<div></div>')
        .attr('id', id)
        .addClass('midcom_services_uimessages')
        .addClass(options.type)
        .appendTo('#midcom_services_uimessages_wrapper');

    $('<h3></h3>')
        .html(options.title)
        .appendTo('#' + id);

    $('<div></div>')
        .html(options.message)
        .addClass('message')
        .appendTo('#' + id);

    $('<img />')
        .attr({
            src: MIDCOM_STATIC_URL + '/stock-icons/16x16/cancel.png',
            alt: 'X'
        })
        .addClass('close')
        .click(function()
        {
            $(this).parent().slideUp('fast');
            $(this).parent().unbind($(this).attr('id') + '_timer');

            // Return without removing the object
            if (!MIDCOM_SERVICES_UIMESSAGES_REMOVE)
            {
                return;
            }

            // Remove the element after some safety margin
            $(this).parent().oneTime(MIDCOM_SERVICES_UIMESSAGES_SLIDE_DELAY, $(this).attr('id') + '_timer', function()
            {
                $(this).remove();
            });
        })
        .prependTo('#' + id);

    switch (options.type)
    {
        case MIDCOM_SERVICES_UIMESSAGES_TYPE_INFO:
        case MIDCOM_SERVICES_UIMESSAGES_TYPE_OK:
            $('#' + id).oneTime(MIDCOM_SERVICES_UIMESSAGES_SLIDE_DELAY, id + '_timer', function()
            {
                $(this).slideUp(MIDCOM_SERVICES_UIMESSAGES_SLIDE_SPEED);

                // Return without removing the object
                if (!MIDCOM_SERVICES_UIMESSAGES_REMOVE)
                {
                    return;
                }

                // Remove the element after some safety margin
                $(this).oneTime(MIDCOM_SERVICES_UIMESSAGES_SLIDE_DELAY, $(this).attr('id') + '_timer', function()
                {
                    $(this).remove();
                });
            });

            break;

        case MIDCOM_SERVICES_UIMESSAGES_TYPE_ERROR:
            if (MIDCOM_SERVICES_UIMESSAGES_ERROR_HIGHLIGHT)
            {
                $('#' + id).everyTime(7000, id + '_shake', function()
                {
                    $(this).effect('pulsate', { times: 1}, 500);
                });
            }


        case MIDCOM_SERVICES_UIMESSAGES_TYPE_WARNING:
        default:
            // Do nothing?
            break;

    }

    MIDCOM_SERVICES_UIMESSAGES_INDEX++;
}
