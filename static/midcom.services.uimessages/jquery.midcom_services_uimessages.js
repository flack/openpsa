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

$.fn.midcom_services_uimessage = function(options) {
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

    $('<i class="close fa fa-times-circle"></i>')
        .click(function() {
            var message = $(this).parent();
            message.slideUp('fast');
            clearTimeout(message.data('timer'));

            // Return without removing the object
            if (!MIDCOM_SERVICES_UIMESSAGES_REMOVE) {
                return;
            }

            // Remove the element after some safety margin
            message.data('timer', setTimeout(function() {
                message.remove();
            }, MIDCOM_SERVICES_UIMESSAGES_SLIDE_DELAY));
        })
        .prependTo('#' + id);

    switch (options.type) {
        case MIDCOM_SERVICES_UIMESSAGES_TYPE_INFO:
            $('#' + id).data('timer', setTimeout(function() {
                $('#' + id).slideUp(MIDCOM_SERVICES_UIMESSAGES_SLIDE_SPEED);

                // Return without removing the object
                if (!MIDCOM_SERVICES_UIMESSAGES_REMOVE) {
                    return;
                }

                // Remove the element after some safety margin
                $('#' + id).data('timer', setTimeout(function() {
                    $('#' + id).remove();
                }, MIDCOM_SERVICES_UIMESSAGES_SLIDE_DELAY));
            }, MIDCOM_SERVICES_UIMESSAGES_SLIDE_DELAY));

            break;

        case MIDCOM_SERVICES_UIMESSAGES_TYPE_ERROR:
            if (MIDCOM_SERVICES_UIMESSAGES_ERROR_HIGHLIGHT) {
                $('#' + id).addClass('pulsate');
            }
            break;

        case MIDCOM_SERVICES_UIMESSAGES_TYPE_OK:
        case MIDCOM_SERVICES_UIMESSAGES_TYPE_WARNING:
        default:
            // Do nothing?
            break;

    }

    MIDCOM_SERVICES_UIMESSAGES_INDEX++;
}
