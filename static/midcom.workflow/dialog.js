function refresh_opener(url) {
    if (url === undefined) {
        url = window.parent.location.href;
    }
    var button = window.parent.$('[data-dialog="dialog"][data-refresh-opener].active');

    if (button.length > 0) {
        if (   button.data('refresh-opener') === false
            && button.closest('.ui-tabs').length === 0) {
            close();
            return;
        }
        url = window.parent.location.href;
    }
    window.parent.location.href = url;
}

function close() {
    var dialog = window.parent.$('#midcom-dialog');
    if (dialog.length > 0) {
        dialog.dialog('close');
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

$(document).ready(function() {
    var title = document.title,
        buttons = [];

    if (   typeof window.parent.$ !== "undefined"
        && window.parent.$('#midcom-dialog').length > 0 ) {
        var dialog = window.parent.$('#midcom-dialog');
        dialog.dialog('option', 'title', title);

        $('body').on('submit', '.midcom-dialog-delete-form', function(e) {
            e.preventDefault();
            var form = $(this).detach().appendTo(dialog);

            form.find('input[name="referrer"]')
                .val(window.parent.location.href);

            //somehow, the original submit button breaks when detaching
            form.append($('<input type="hidden" name="' + form.find('input[type="submit"]').attr('name') + '" value="x">'))
                .submit();
        });

        $(window).unload(function() {
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
            $('.datamanager2 .form_toolbar').hide();
        }
        if (extra_buttons.length > 0) {
            buttons = extra_buttons.concat(buttons);
        }

        dialog.dialog('option', 'buttons', buttons);
    } else {
        $('.midcom-view-toolbar').show();
    }
});
