const dialog = window.frameElement ? window.parent.$(window.frameElement.parentNode) : null;

function refresh_opener(url) {
    if (url === undefined) {
        url = window.parent.location.href;
    }
    close();

    var button = window.parent.$('[data-dialog="dialog"][data-refresh-opener].active');
    if (button.length > 0) {
        if (   button.data('refresh-opener') === false
            && button.closest('.ui-tabs').length === 0) {
            return;
        }
        url = window.parent.location.href;
    }
    window.parent.location.href = url;
}

function close(data) {
    if (dialog) {
        dialog
            .trigger('dialogsaved', [data])
            .dialog('close');
    }
}

var extra_buttons = [];
function add_dialog_button(url, label, options) {
    var button = {
        text: label,
        'data-action': url,
        'class': 'dialog-extra-button',
        click: function(){}
    };
    $.each(options, function(key, value) {
        button[key] = value;
    });
    extra_buttons.push(button);
}

function add_post_button(url, label, options) {
    var button = {
        text: label,
        'class': 'dialog-extra-button',
        click: function() {
            var form = $('<form action="' + url + '" method="post"></form>'),
                dialog = window.parent.$('#midcom-dialog');
            $.each(options, function(key, value) {
                form.append($('<input type="hidden" name="' + key + '">').val(value));
            });
            form.appendTo('body').submit();
            dialog.dialog('option', 'buttons', []);
        }
    };
    extra_buttons.push(button);
}

function add_item(data) {
    if (dialog) {
        var widget_id = dialog.attr('id').replace(/_creation_dialog/, '');
        window.parent.midcom_helper_datamanager2_autocomplete.add_result_item(widget_id, data);
        close(data);
    }
}

function attach_to_parent_dialog(dialog) {
    let buttons = [];
    if (document.title) {
        dialog.dialog('option', 'title', document.title);
    }
    dialog.css('visibility', 'visible');

    $(window).on('unload', function() {
        dialog.nextAll('.ui-dialog-buttonpane').find('button')
            .prop('disabled', true)
            .addClass('ui-state-disabled');
    });

    if ($('.midcom-view-toolbar li').length > 0) {
        $('.midcom-view-toolbar li').each(function() {
            var btn = $(this).find('a'),
                options = {
                    click: function() {
                        btn.get(0).click();
                        btn.addClass('active');
                    }
                };

            add_dialog_button(btn.attr('href'), btn.text(), options);
        });
    }

    if ($('.datamanager2 .form_toolbar > *').length > 0) {
        $('.datamanager2 .form_toolbar > *').each(function() {
            var btn = $(this);
            buttons.push({
                text: btn.val() || btn.text(),
                click: function() {
                    if (btn.hasClass('cancel')) {
                        dialog.dialog('close');
                    } else {
                        btn.click();
                    }
                }
            });
        });
    }
    if (extra_buttons.length > 0) {
        buttons = extra_buttons.concat(buttons);
    }

    // This doesn't work under certain circumstances when flexbox is used somewhere in the page:
    // dialog.dialog('option', 'buttons', buttons);
    // @todo: The root of the problem seems to be that jquery can't set the content element
    // to a height of 0, so at some point this could be filed as a bug against their repo. Latest
    // stable (3.4.1) is affected. For now, we just copy the relevant part from jqueryui's
    // _createButtons method..

    var buttonset = dialog.nextAll('.ui-dialog-buttonpane').find('.ui-dialog-buttonset').empty();

    $.each(buttons, function (name, props) {
        var click, buttonOptions;
        props = $.isFunction(props) ? {click: props, text: name} : props;

        // Default to a non-submitting button
        props = $.extend({type: "button"}, props);

        // Change the context for the click callback to be the main element
        click = props.click;
        buttonOptions = {
            icon: props.icon,
            iconPosition: props.iconPosition,
            label: props.text
        };

        delete props.click;
        delete props.icon;
        delete props.iconPosition;
        delete props.text;

        $('<button></button>', props)
            .button(buttonOptions)
            .appendTo(buttonset)
            .on('click', function() {
                click.apply(dialog[0], arguments);
            });
    });
}

window.addEventListener('DOMContentLoaded', function() {
    if (dialog) {
        dialog.find(' > .fa-spinner').hide();
        if (window.hasOwnProperty('$')) {
            $('body').on('submit', '.midcom-dialog-delete-form', function(e) {
                e.preventDefault();
                var form = $(this).detach().appendTo(dialog);

                form.find('input[name="referrer"]')
                    .val(window.parent.location.href);

                //somehow, the original submit button breaks when detaching
                form.append($('<input type="hidden" name="' + form.find('input[type="submit"]').attr('name') + '" value="x">'))
                    .submit();
            });
            attach_to_parent_dialog(dialog);
        }
    } else if (window.hasOwnProperty('$')) {
        $('.midcom-view-toolbar, .datamanager2 .form_toolbar').show();
    }
});
