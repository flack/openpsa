$(document).ready(function()
{
    $('a[data-dialog="delete"]').on('click', function(event)
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

    $('a[data-dialog="datamanager"]').on('click', function(event)
    {
        event.preventDefault();
        if (!$(this).hasClass('active'))
        {
            if ($('.midcom-workflow-dialog').length > 0)
            {
                $('.midcom-workflow-dialog .ui-dialog-content').dialog('close');
            }

            var button = $(this),
                dialog = $('<div></div>').insertAfter($(this)),
                iframe = $('<iframe src="' + $(this).attr('href') + '"'
                    + ' frameborder="0"'
                    + ' marginwidth="0"'
                    + ' marginheight="0"'
                    + ' width="100%"'
                    + ' height="100%"'
                    + ' scrolling="auto" />');

            button.addClass('active');
            dialog
                .append(iframe)
                .dialog(
                    {
                        height: 550,
                        width: 700,
                        dialogClass: 'midcom-workflow-dialog',
                        close: function() {
                            button.removeClass('active');
                            dialog
                                .dialog('destroy')
                                .remove();
                        }
                    });
        }
    });
});
