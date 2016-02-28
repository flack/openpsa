function refresh_opener(url)
{
    var button = window.parent.$('[data-dialog="datamanager"][data-refresh-opener].active');

    if (button.length > 0)
    {
        if (button.data('refresh-opener') === false)
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

        dialog.dialog('option', 'buttons', buttons);
    }
});
