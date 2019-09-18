function init_subform(id, sortable) {
    var container = $('#' + id),
        delete_button = $('<a class="button remove-item">-</a>'),
        add_button = $('<a class="button add-item">+</a>')
            .on('click', function(e) {
                e.preventDefault();
                add_form(container, add_button, delete_button, sortable);
            }),
        index = 0;

    container.on('click', 'a.remove-item', function(e) {
        e.preventDefault();
        $(this).parent().remove();
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
            .sortable({items: '> :not(a.add-item)'})
            .on('sortupdate', function() {
                $($(this).find('> .ui-sortable-handle').get().reverse()).each(function(index, element) {
                    $('#' + element.id + '_score').val(index);
                });
            });
    }

    if (container.data('index') === 0) {
        add_button.click();
    }

    add_button.on('click', function() {
        // If there is exactly one file selector, we're probably in some sort of attachment list,
        // so let's assume the user wants to add a file
        // (at some point this should probably be made configurable)
        if ($(this).prev().find('input[type="file"]').length === 1) {
            $(this).prev().find('input[type="file"]').click();
        }
    });
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
        && container.data('max-count') >= container.find('> :not(.button.add-item)').length) {
        add_button.detach();
    }
    if (sortable === true) {
        container.sortable('refresh');
        container.trigger('sortupdate');
    }
}
