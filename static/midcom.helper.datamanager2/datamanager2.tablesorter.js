var COLUMN_TITLE = 'Please enter the column name';

var change_label = function()
{
    var field_name = prompt(COLUMN_TITLE, $(this).parent().find('input.field_name').val());
    $(this).html(field_name);
    $(this).parent().find('input.field_name').val(field_name);
};

$.fn.toggleClick = function(){
    var functions = arguments;
    return this.click(function(){
            var iteration = $(this).data('iteration') || 0;
            functions[iteration].apply(this, arguments);
            iteration = (iteration + 1) % functions.length ;
            $(this).data('iteration', iteration);
    });
};

$.fn.create_tablesorter = function(options)
{
    $(this).addClass('jquery-enabled');
    $(this).find('th.index').text('');
    $(this).find('td.midcom_helper_datamanager2_helper_sortable input').css({display: 'none'});

    $(this).create_tablesorter_rows(options);
    $(this).create_tablesorter_columns(options);
    $(this).rearrange_scores();
    $(this).check_column_positions(options);

    // Column adding and sorting
    if ($(options.sortable_columns))
    {
        $(this).initialize_column_creation(options);
    }

    // Convert add/remove buttons
    $(this).find('button').each(function()
    {
        $(this).find('img').appendTo($(this).parent());
    });

    // Create the sortable rows
    $(this).create_tablesorter_rows(options);

    if (options.sortable_rows)
    {
        $('tbody td.midcom_helper_datamanager2_helper_sortable').css({display: 'table-cell'});
    }

    $(this).find('th.index').css({display: 'table-cell'});

    // IE6 compliant hovering
    $(this).find('tbody tr')
        .mouseover(function()
        {
            // Fix IE6 hover
            $(this).addClass('hover');
        })
        .mouseout(function()
        {
            // Fix IE6 hover
            $(this).removeClass('hover');
        });

    $(this).find('tbody td.midcom_helper_datamanager2_helper_sortable').each(function()
    {
        if ($(this).find('img.down').size() == 0)
        {
            $('<img />')
                .addClass('down')
                .addClass('enabled')
                .attr({
                    src: MIDCOM_STATIC_URL + '/stock-icons/16x16/down.png',
                    alt: 'Down'
                })
                .click(function()
                {
                    $(this).move_row('down');
                })
                .prependTo($(this));
        }

        if ($(this).find('img.up').size() == 0)
        {
            $('<img />')
                .addClass('up')
                .addClass('enabled')
                .attr({
                    src: MIDCOM_STATIC_URL + '/stock-icons/16x16/up.png',
                    alt: 'Up'
                })
                .click(function()
                {
                    $(this).move_row('up');
                })
                .prependTo($(this));
        }
    });

    $(this).find('tbody tr').find('td:first').each(function()
    {
        if (   $(this).find('img.delete').size() > 0
            || options.allow_delete == false)
        {
            return;
        }

        $('<img />')
            .addClass('delete')
            .attr({
                    src: MIDCOM_STATIC_URL + '/stock-icons/16x16/trash.png',
                    alt: 'Delete'
            })
            .toggleClick(
                function()
                {
                    $(this).parents('tr')
                        .addClass('deleted')
                        .find('input, select, textarea').each(function(i)
                        {
                            var name = $(this).attr('name');
                            name = '___' + name;
                            $(this).attr('name', name);
                            $(this).prop('disabled', true);
                        });
                },
                function()
                {
                    $(this).parents('tr')
                        .removeClass('deleted')
                        .find('input, select, textarea').each(function()
                        {
                            var name = $(this).attr('name');
                            name = name.replace(/^___/, '');
                            $(this).attr('name', name);
                            $(this).prop("disabled", false);
                        });

                }
            )
            .prependTo($(this))
            .show();
    });

    // Check the amount of rows presented
    var row_count = $(this).find('tbody tr').size();

    if (   options.max_row_count != 0
        && row_count >= options.max_row_count)
    {
        $(this)
            .find('tfoot td.new_row').fadeTo(500, 0.5)
            .unbind('click');
    }
};

$.fn.create_tablesorter_rows = function(options)
{
    $(this).find('tfoot td.new_row')
        .unbind('click')
        .click(function()
        {
            var new_row = $(this).parent().clone(true),
                date = new Date(),
                timestamp = date.getTime();

            $(new_row).find('img.add-row').remove();

            // Insert the rows
            $(new_row)
                .addClass('new_row')
                .attr(
                {
                    id: 'row_' + timestamp
                })
                .appendTo($(this).parents('table.jquery-enabled').find('tbody'));

            $(new_row).find('input, select, textarea').each(function()
            {
                var name = $(this).attr('name');

                $(this).parent().removeClass('new_row');

                if (name)
                {
                    name = name.replace(/index/, timestamp);
                    $(this).attr('name', name);
                }

                var id = $(this).attr('id');

                if (id)
                {
                    id = id.replace(/index/, 'row_' + timestamp);
                    $(this).attr('id', id);
                }

                var value = $(this).val();

                if (value)
                {
                    value = value.replace(/index/, timestamp);
                    $(this).val(value);
                }
            });

            $(new_row).find('td')
                .unbind('click')
                .removeClass('new_row');

            $(this).parents('table.jquery-enabled').create_tablesorter(options);
            $(this).rearrange_scores();

            // Check the amount of rows presented
            var row_count = $(this).parents('table.jquery-enabled').find('tbody tr').size();

            if (   options.max_row_count != 0
                && row_count >= options.max_row_count)
            {
                $(this)
                    .fadeTo(500, 0.5)
                    .unbind('click');
                return false;
            }
        });

    // Less than two, no point in initializing the sortable
    if (!options.sortable_rows
        || $(this).find('tbody td.midcom_helper_datamanager2_helper_sortable').size() < 2)
    {
        $('tbody td.midcom_helper_datamanager2_helper_sortable').css({display: 'none'});
        $(this).find('th.index').css({display: 'none'});
    }
};

$.fn.move_row = function(direction)
{
    if (!$(this).hasClass('enabled'))
    {
        return false;
    }

    var parent = $(this).parents('tr');

    $(parent).removeClass('hover');

    switch (direction)
    {
        case 'up':
            $(parent).insertBefore($(parent).prev('tr'));
            $(this).parents('table.jquery-enabled').rearrange_scores();
            break;
        case 'down':
            $(parent).insertAfter($(parent).next('tr'));
            $(this).parents('table.jquery-enabled').rearrange_scores();
            break;
    }
};

$.fn.rearrange_scores = function()
{
    var size = $(this).find('tbody td.midcom_helper_datamanager2_helper_sortable').size();

    $(this).find('tbody td.midcom_helper_datamanager2_helper_sortable').each(function(i)
    {
        var last_index = size - 1;

        switch (i)
        {
            case 0:
                $(this).find('.up')
                    .fadeTo(500, 0.3)
                    .removeClass('enabled');

                if (!$(this).find('.down').hasClass('enabled'))
                {
                    $(this).find('.down')
                        .addClass('enabled')
                        .fadeTo(500, 1);
                }

                break;

            case last_index:
                $(this).find('.down')
                    .fadeTo(500, 0.3)
                    .removeClass('enabled');

                if (!$(this).find('.up').hasClass('enabled'))
                {
                    $(this).find('.up')
                        .addClass('enabled')
                        .fadeTo(500, 1);
                }

                break;

            default:
                // Stays somewhere in between the first and the last
                $(this).find('.up, .down').each(function()
                {
                    // This was already enabled, no need to make any changes
                    if ($(this).hasClass('enabled'))
                    {
                        return;
                    }

                    $(this)
                        .addClass('enabled')
                        .fadeTo(500, 1);
                });
        }

        $(this).find('input.image_sortable, input.downloads_sortable').val(i + 1);
    });

    $(this).find('tbody tr:odd')
        .addClass('odd')
        .removeClass('even');

    $(this).find('tbody tr:even')
        .addClass('even')
        .removeClass('odd');
};

$.fn.create_tablesorter_columns = function(options)
{
    if (!options.sortable_columns)
    {
        return;
    }

    $(this).find('thead th').each(function()
    {
        if (   $(this).hasClass('index')
            || $(this).hasClass('new_column')
            || $(this).hasClass('add_column')
            || $(this).find('img').size() >= 1)
        {
            return;
        }

        $('<img />')
            .css({

            })
            .addClass('column_sort move_left enabled')
            .attr({
                alt: 'move left',
                src: MIDCOM_STATIC_URL + '/stock-icons/16x16/stock_left.png'
            })
            .click(function()
            {
                if (!$(this).hasClass('enabled'))
                {
                    return;
                }
                $(this).parent().move_column('left', options);
            })
            .prependTo($(this));

        $('<img />')
            .css({

            })
            .addClass('column_sort move_right enabled')
            .attr({
                alt: 'move left',
                src: MIDCOM_STATIC_URL + '/stock-icons/16x16/stock_right.png'
            })
            .click(function()
            {
                if (!$(this).hasClass('enabled'))
                {
                    return;
                }
                $(this).parent().move_column('right', options);
            })
            .appendTo($(this));

        if ($(this).hasClass('deletable'))
        {
            $('<img />')
                .css({

                })
                .addClass('enabled delete')
                .attr({
                    alt: 'delete',
                    src: MIDCOM_STATIC_URL + '/stock-icons/16x16/trash.png'
                })
                .click(function()
                {
                    var class_name = $(this).parent().attr('class');

                    if (!class_name)
                    {
                        return;
                    }

                    class_name = class_name.replace(/tabledata_header /, '');
                    class_name = class_name.replace(/ deletable/, '');
                    class_name = class_name.replace(/ deleted/, '');

                    $(this).parent().delete_column(class_name, options);
                })
                .prependTo($(this));
        }

        $(this).hover(
            function()
            {
                $(this).find('img').addClass('hover');
            },
            function()
            {
                $(this).find('img').removeClass('hover');
            }
        );
    });

    // Disable the moving outside table borders
    $(options.table_id).find('thead th:first').next().find('img.move_left')
        .removeClass('enabled')
        .fadeTo(500, 0.5);

    $(options.table_id).find('thead th:first').next().next().find('img.move_left')
        .addClass('enabled')
        .fadeTo(500, 1.0);

    // Disable the moving outside table borders
    $(options.table_id).find('thead th:last').prev().find('img.move_right')
        .removeClass('enabled')
        .fadeTo(500, 0.5);

    $(options.table_id).find('thead th:last').prev().prev().find('img.move_right')
        .addClass('enabled')
        .fadeTo(500, 1.0);
};

$.fn.move_column = function(direction, options)
{
    var class_name = $(this).attr('class');
    class_name = class_name.replace(/tabledata_header /, '');
    class_name = class_name.replace(/ deletable/, '');
    class_name = class_name.replace(/ deleted/, '');

    $(options.table_id).find('td.' + class_name + ', th.' + class_name).each(function()
    {
        if (direction == 'left')
        {
            $(this).insertBefore($(this).prev());
        }
        else
        {
            $(this).insertAfter($(this).next());
        }

        $(this).find('img.column_sort').removeClass('hover');
    });

    $(options.table_id).create_tablesorter_columns(options);
};

$.fn.check_column_positions = function(options)
{

};

$.fn.delete_column = function(column_id, options)
{
    if ($(options.table_id).find('.' + column_id).hasClass('deleted'))
    {
        $(options.table_id).find('.' + column_id)
            .removeClass('deleted')
            .find('input, select, textarea').prop('disabled', false);
    }
    else
    {
        $(options.table_id).find('.' + column_id)
            .addClass('deleted')
            .find('input, select, textarea').prop('disabled', true);
    }

    if ($(this).hasClass('deleted'))
    {
        $('<input type="hidden" name="midcom_helper_datamanager2_tabledata_widget_delete[' + options.field_name + '][]"/>')
            .addClass('delete_input')
            .val(column_id)
            .appendTo($(this));
    }
    else
    {
        $(this).find('input.delete_input').remove();
    }
};

$.fn.initialize_column_creation = function(options)
{
    if ($(this).find('img.add_new_column').size() > 0)
    {
        return;
    }

    if ($(this).find('img.add_new_column').size() > 0)
    {
        return;
    }

    $('<img />')
        .addClass('enabled add_new_column')
        .attr({
            src: MIDCOM_STATIC_URL + '/stock-icons/16x16/list-add.png',
            alt: 'Add column'
        })
        .click(function()
        {
            // Prompt for the field name from the user
            var field_name = prompt(COLUMN_TITLE, '');

            var date = new Date();
            var timestamp = date.getTime();

            if (!field_name)
            {
                return;
            }

            // Create a new table head cell
            $('<th></th>')
                .attr('id', 'new_column_' + timestamp)
                .addClass('deletable')
                .insertBefore($(this).parent());

            $('<span></span>')
                .addClass('field_name')
                .dblclick(change_label)
                .html(field_name)
                .appendTo('#new_column_' + timestamp);

            // Input for changing the column name
            $('<input type="hidden" id="new_column_' + timestamp + '_input" name="midcom_helper_datamanager2_sortable_column[' + options.field_name + '][new_' + timestamp + ']" />')
                .addClass('field_name')
                .val(field_name)
                .appendTo($('#new_column_' + timestamp));

            // Insert a new column for each row
            $(this).parents(options.table_id).find('tbody tr, tfoot tr').each(function(i)
            {
                var row = $(this).attr('id');

                if (row)
                {
                    row = row.replace(/^row_/, '');
                }
                else
                {
                    row = '';
                }

                $('<td></td>')
                    .addClass('new_column_' + timestamp)
                    .appendTo($(this));

                $('<input type="text" />')
                    .addClass('column_field tabledata_widget_text')
                    .attr({
                        name: 'midcom_helper_datamanager2_type_tabledata[' + options.field_name + '][' + row + '][new_' + timestamp + ']',
                        id: 'midcom_helper_datamanager2_type_tabledata_' + options.field_name + '_new_' + timestamp + '_' + i
                    })
                    .appendTo($(this).find('td.new_column_' + timestamp));
            });

            // Recreate the sortable columns
            $(this).create_tablesorter_columns(options);
        })
        .appendTo($(this).find('thead th.add_column'));
};

$(document).ready(function()
{
    $('table.midcom_helper_datamanager2_tabledata_widget th span.allow_rename')
        .dblclick(change_label);
});
