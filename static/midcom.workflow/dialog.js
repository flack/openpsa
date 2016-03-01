function refresh_opener(url)
{
    if (url === undefined)
    {
        url = window.parent.location.href;
    }
    var button = window.parent.$('[data-dialog="datamanager"][data-refresh-opener].active');

    if (button.length > 0)
    {
        if (   button.data('refresh-opener') === false
            && button.closest('.ui-tabs').length === 0)
        {
            close();
            return;
        }
        url = window.parent.location.href;
    }
    window.parent.location.href = url;
}

function close()
{
    var dialog = window.parent.$('#midcom-datamanager-dialog');
    if (dialog.length > 0)
    {
        dialog.dialog('close');
    }
}

var extra_buttons = [];
function add_dialog_button(url, label, options)
{
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

function add_post_button(url, label, options)
{
    var button = {
        text: label,
        'class': 'dialog-extra-button',
        click: function() {
            var form = $('<form action="' + url + '" method="post"></form>');
            $.each(options, function(key, value) {
                form.append($('<input type="hidden" name="' + key + '">').val(value))
            });
            form.appendTo('body').submit();
        }
    };
    extra_buttons.push(button);
}

$(document).ready(function()
{
    var title = document.title,
        buttons = [],
        dialog = window.parent.$('#midcom-datamanager-dialog');

    if (dialog.length > 0)
    {
        dialog.dialog('option', 'title', title);

        if ($('.datamanager2 .form_toolbar input').length > 0)
        {
            $('.datamanager2 .form_toolbar input').each(function() {
                var btn = $(this);
                buttons.push({
                    text: btn.val(),
                    click: function() {
                        if (btn.hasClass('cancel'))
                        {
                            dialog.dialog('close');
                        }
                        else
                        {
                            btn.click();
                        }
                    }
                });
            });
            $('.datamanager2 .form_toolbar').hide();
        }
        if (extra_buttons.length > 0)
        {
            buttons = extra_buttons.concat(buttons);
        }

        dialog.dialog('option', 'buttons', buttons);
    }
});
