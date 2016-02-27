function refresh_opener(url)
{
    window.parent.location.href = url;
}

function close()
{
    var dialog = window.parent.$('iframe[src^="' + window.location.pathname + '"]:visible').parent();
    if (dialog.length > 0)
    {
        dialog.dialog('close');
    }
}

$(document).ready(function()
{
    var title = document.title,
        buttons = [],
        dialog = window.parent.$('iframe[src^="' + window.location.pathname + '"]:visible').parent();

    if (dialog.length > 0)
    {
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
        dialog.dialog('option', 'title', title)
            .dialog('option', 'buttons', buttons);
    }
});
