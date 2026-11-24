$.midcom_services_uimessage_add = function(options) {
    $('#midcom_services_uimessages_wrapper').midcom_services_uimessage(options);
};

$.fn.midcom_services_uimessage = function(options) {
    const message = $('<div class="midcom_services_uimessages"></div>')
        .addClass(options.type)
        .on('animationend', function(event) {
            // message content may contain animations of its own
            if (event.target === this) {
                message.remove();
            }
        })
        .append(
            $('<i class="close fa fa-times-circle"></i>').on('click', function() {
                message.addClass('closing');
            }),
            $('<div class="content"></div>').append(
                $('<h3></h3>').html(options.title),
                $('<div class="message"></div>').html(options.message)
            )
        )
        .appendTo('#midcom_services_uimessages_wrapper');
};
