$(document).ready(function()
{
    $('body').on('click', 'a[data-dialog="delete"]', function(event)
    {
        event.preventDefault();
        var button = $(this),
            options = {
                title:  button.data('dialog-heading'),
                resizable: false,
                modal: true,
                buttons: {}
            },
            label = button.text();

        if (label.trim() === '')
        {
            label = button.data('dialog-heading');
        }

        options.buttons[label] = function() {
            $('<form action="' + button.attr('href') + '" method="post">')
                .append($('<input type="submit" name="' + button.data('form-id') + '">'))
                .append($('<input type="hidden" name="referrer" value=' + location.pathname + '">'))
                .hide()
                .prependTo('body');
            $('input[name="' + button.data('form-id') + '"]').click();
        };
        options.buttons[button.data('dialog-cancel-label')] = function() {
            $( this ).dialog( "close" );
        };
        $('<div>')
            .append($('<p>' + button.data('dialog-text') + '</p>'))
            .appendTo($('body'))
            .dialog(options);
    });

    $('body').on('click', 'a[data-dialog="datamanager"]', function(event)
    {
        event.preventDefault();
        if (!$(this).hasClass('active'))
        {
            create_datamanager_dialog($(this));
        }
    });
});

function create_datamanager_dialog(control)
{
    if ($('.midcom-workflow-dialog').length > 0)
    {
        $('.midcom-workflow-dialog .ui-dialog-content').dialog('close');
    }

    var dialog = $('<div id="midcom-datamanager-dialog"></div>').insertAfter(control),
        src = $(control).attr('href') ? ' src="' + $(control).attr('href') + '"': '',
        iframe = $('<iframe' + src + ' name="datamanager-dialog"'
                   + ' frameborder="0"'
                   + ' marginwidth="0"'
                   + ' marginheight="0"'
                   + ' width="100%"'
                   + ' height="100%"'
                   + ' scrolling="auto" />');

    control.addClass('active');
    dialog
        .append(iframe)
        .dialog(
            {
                height: 550,
                width: 700,
                dialogClass: 'midcom-workflow-dialog',
                buttons: [],
                close: function() {
                    control.removeClass('active');
                    dialog
                        .dialog('destroy')
                        .remove();
                }
            });
}
