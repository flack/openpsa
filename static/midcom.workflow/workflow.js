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
});
