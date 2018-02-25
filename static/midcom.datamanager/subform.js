function init_subform(id, sortable) {
    var container = $('#' + id),
        delete_button = $('<a class="button remove-item">-</a>'),
        add_button = $('<a class="button add-item">+</a>')
            .on('click', function(e)
            {
                e.preventDefault();
                add_form(container, add_button, delete_button, sortable);
            }),
        index = 0;

    container.on('click', 'a.button.remove-item', function(e) {
        e.preventDefault();
        $(this).closest('fieldset').remove();
        if (   container.data('max-count') > 0
            && container.data('max-count') >= container.find('fieldset').length
            && container.find('.add-item').length === 0) {
            container.append(add_button);
        }
    });

    container.children().each(function() {
        $(this).prepend(delete_button.clone());
        index++;
    });

    container.data('index', index);
    if (   container.data('max-count') === 0
        || container.data('max-count') > index) {
        container.append(add_button);
    }

    if (sortable === true) {
        container
            .sortable({items: '> fieldset'})
            .on('sortupdate', function() {
                $($(this).find('> .ui-sortable-handle').get().reverse()).each(function(index, element) {
                    $('#' + $(element).attr('id') + '_score').val(index);
                });
            });
    }

    if (container.data('index') === 0) {
        add_button.click();
    }
}

function add_form(container, add_button, delete_button, sortable) {
    var prototype = container.data('prototype'),
        index = container.data('index'),
        new_form = prototype.replace(/__name__/g, index);
    container.data('index', index + 1);
    $(new_form)
        .prepend(delete_button.clone())
        .insertBefore(add_button);

    if (   container.data('max-count') > 0
        && container.data('max-count') >= container.find('fieldset').length) {
        add_button.detach();
    }
    if (sortable === true) {
        container.sortable('refresh');
        container.trigger('sortupdate');
    }
}
