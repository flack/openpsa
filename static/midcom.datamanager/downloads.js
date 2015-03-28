function init_downloads(id)
{
    var container = $('#' + id),
        delete_button = $('<button class="remove-item">-</button>'),
        add_button = $('<button class="add-item">+</button>')
            .on('click', function(e)
            {
                e.preventDefault();
                add_form(container, add_button, delete_button);
            }),
        index = 0;

    container.on('click', 'button.remove-item', function(e)
    {
        e.preventDefault();
        $(this).closest('fieldset').remove();
        if (   container.data('max-count') > 0
            && container.data('max-count') >= container.find('fieldset').length
            && container.find('.add-item').length === 0)
        {
            container.append(add_button);
        }
    });

    container.children().each(function()
    {
        $(this).prepend(delete_button.clone());
        index++;
    });

    container.data('index', index);
    if (   container.data('max-count') === 0
        || container.data('max-count') > index)
    {
        container.append(add_button);
    }

    if (container.data('index') === 0)
    {
        add_button.click();
    }
}

function add_form(container, add_button, delete_button)
{
    var prototype = container.data('prototype'),
    index = container.data('index'),
    new_form = prototype.replace(/__name__/g, index);
    container.data('index', index + 1);
    $(new_form)
        .prepend(delete_button.clone())
        .insertBefore(add_button);

    if (   container.data('max-count') > 0
        && container.data('max-count') >= container.find('fieldset').length)
    {
        add_button.detach();
    }
}
