$(document).ready(function() {
    function fail(element, title, message) {
        element.addClass('ajax_save_failed');
        $.midcom_services_uimessage_add({
            type: 'error',
            title: title,
            message: message
        });
        return false;
    }

    $('.ajax_editable')
        .on('focus', function() {
            $(this)
                .data('orig-value', $(this).val())
                .addClass('ajax_focused');
        })
        .on('blur', function() {
            $(this).removeClass('ajax_focused');
            var ajaxUrl = $(this).data('ajax-url'),
                element = $(this);
            if (!ajaxUrl) {
                throw 'Handler URL for cannot be found';
            }

            if (element.val() !== element.data('orig-value')) {
                element.addClass('ajax_saving');
                $.post(ajaxUrl, {guid: element.data('guid'), title: element.val()}, function(data) {
                    element.removeClass('ajax_saving');
                    if (data.status !== true) {
                        return fail(element, 'Saving failed', data.message);
                    }
                    element.addClass('ajax_saved');
                    setTimeout(function() {
                        element.removeClass('ajax_saved');
                        element.addClass('ajax_saved_fade');
                    }, 1000);
                    setTimeout(function() {
                        element.removeClass('ajax_saved_fade');
                    }, 2000);
                })
                    .fail(function() {
                        fail(element, 'Request failed', 'Request failed');
                    });
            }

            //If empty return to the default value (if one is provided)
            if (   $(this).val() == ''
                && $(this).data('ajax-default')) {
                $(this).val($(this).data('ajax-default'));
            }
        });
});
