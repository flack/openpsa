var COLUMN_TITLE = 'Please enter the column name';

var change_label = function()
{
    var field_name = prompt(COLUMN_TITLE, jQuery(this).parent().find('input.field_name').val());
    jQuery(this).html(field_name);
    jQuery(this).parent().find('input.field_name').val(field_name);
}

jQuery.fn.toggleClick = function(){
    var functions = arguments;
    return this.click(function(){
            var iteration = $(this).data('iteration') || 0;
            functions[iteration].apply(this, arguments);
            iteration = (iteration + 1) % functions.length ;
            $(this).data('iteration', iteration);
    });
};

jQuery.fn.create_tablesorter = function(options)
{
    jQuery(this).addClass('jquery-enabled');
    jQuery(this).find('th.index').text('');
    jQuery(this).find('td.midcom_helper_datamanager2_helper_sortable input').css({display: 'none'});
    
    jQuery(this).create_tablesorter_rows(options);
    jQuery(this).create_tablesorter_columns(options);
    jQuery(this).rearrange_scores();
    jQuery(this).check_column_positions(options);
    
    // Column adding and sorting
    if (jQuery(options.sortable_columns))
    {
        jQuery(this).initialize_column_creation(options);
    }
    
    // Convert add/remove buttons
    jQuery(this).find('button').each(function(i)
    {
        jQuery(this).find('img').appendTo(jQuery(this).parent());
    });
    
    // Create the sortable rows
    jQuery(this).create_tablesorter_rows(options);
    
    if (options.sortable_rows)
    {
        jQuery('tbody td.midcom_helper_datamanager2_helper_sortable').css({display: 'table-cell'});
    }
        
    jQuery(this).find('th.index').css({display: 'table-cell'});
    
    // IE6 compliant hovering
    jQuery(this).find('tbody tr')
        .mouseover(function()
        {
            // Fix IE6 hover
            jQuery(this).addClass('hover');
        })
        .mouseout(function()
        {
            // Fix IE6 hover
            jQuery(this).removeClass('hover');
        });
    
    
    jQuery(this).find('tbody td.midcom_helper_datamanager2_helper_sortable').each(function(i)
    {
        if (jQuery(this).find('img.down').size() == 0)
        {
            jQuery('<img />')
                .addClass('down')
                .addClass('enabled')
                .attr({
                    src: MIDCOM_STATIC_URL + '/stock-icons/16x16/down.png',
                    alt: 'Down'
                })
                .click(function()
                {
                    jQuery(this).move_row('down');
                })
                .prependTo(jQuery(this));
        }
        
        if (jQuery(this).find('img.up').size() == 0)
        {
            jQuery('<img />')
                .addClass('up')
                .addClass('enabled')
                .attr({
                    src: MIDCOM_STATIC_URL + '/stock-icons/16x16/up.png',
                    alt: 'Up'
                })
                .click(function()
                {
                    jQuery(this).move_row('up');
                })
                .prependTo(jQuery(this));
        }
    });
    
    jQuery(this).find('tbody tr').find('td:first').each(function(i)
    {
        if (   jQuery(this).find('img.delete').size() > 0
            || options.allow_delete == false)
        {
            return;
        }
        
        jQuery('<img />')
            .addClass('delete')
            .attr({
                    src: MIDCOM_STATIC_URL + '/stock-icons/16x16/trash.png',
                    alt: 'Delete'
            })
            .toggleClick(
                function()
                {
                	console.log("add class");
                    jQuery(this).parents('tr')
                        .addClass('deleted')
                        .find('input, select, textarea').each(function(i)
                        {
                            var name = jQuery(this).attr('name');
                            name = '___' + name;
                            jQuery(this).attr('name', name);
                            jQuery(this).prop('disabled', true);
                        });
                },
                function()
                {
                	console.log("remove class");
                    jQuery(this).parents('tr')
                        .removeClass('deleted')
                        .find('input, select, textarea').each(function(i)
                        {
                            var name = jQuery(this).attr('name');
                            name = name.replace(/^___/, '');
                            jQuery(this).attr('name', name);
                            jQuery(this).prop("disabled", false);
                        });
                        
                }
            )
            .prependTo(jQuery(this))
            .show();
    });
    
    // Check the amount of rows presented
    var row_count = jQuery(this).find('tbody tr').size();
    
    if (   options.max_row_count != 0
        && row_count >= options.max_row_count)
    {
        jQuery(this)
            .find('tfoot td.new_row').fadeTo(500, 0.5)
            .unbind('click');
    }
}

jQuery.fn.create_tablesorter_rows = function(options)
{
    jQuery(this).find('tfoot td.new_row')
        .unbind('click')
        .click(function()
        {
            var new_row = jQuery(this).parent().clone(true);
            jQuery(new_row).find('img.add-row').remove();
            
            date = new Date();
            timestamp = date.getTime();
            
            // Insert the rows
            jQuery(new_row)
                .addClass('new_row')
                .attr(
                {
                    id: 'row_' + timestamp
                })
                .appendTo(jQuery(this).parents('table.jquery-enabled').find('tbody'));
            
            jQuery(new_row).find('input, select, textarea').each(function(i)
            {
                var name = jQuery(this).attr('name');
                
                jQuery(this).parent().removeClass('new_row');
                
                if (name)
                {
                    name = name.replace(/index/, timestamp);
                    jQuery(this).attr('name', name);
                }
                
                var id = jQuery(this).attr('id');
                
                if (id)
                {
                    id = id.replace(/index/, 'row_' + timestamp);
                    jQuery(this).attr('id', id);
                }
                
                var value = jQuery(this).val();
                
                if (value)
                {
                    value = value.replace(/index/, timestamp);
                    jQuery(this).val(value);
                }
            });
            
            jQuery(new_row).find('td')
                .unbind('click')
                .removeClass('new_row');
            
            jQuery(this).parents('table.jquery-enabled').create_tablesorter(options);
            jQuery(this).rearrange_scores();
            
            // Check the amount of rows presented
            var row_count = jQuery(this).parents('table.jquery-enabled').find('tbody tr').size();
            
            if (   options.max_row_count != 0
                && row_count >= options.max_row_count)
            {
                jQuery(this)
                    .fadeTo(500, 0.5)
                    .unbind('click');
                return false;
            }
        });
    
    // Less than two, no point in initializing the sortable
    if (!options.sortable_rows
        || jQuery(this).find('tbody td.midcom_helper_datamanager2_helper_sortable').size() < 2)
    {
        jQuery('tbody td.midcom_helper_datamanager2_helper_sortable').css({display: 'none'});
        jQuery(this).find('th.index').css({display: 'none'});
        return;
    }
}

jQuery.fn.move_row = function(direction)
{
    if (!jQuery(this).hasClass('enabled'))
    {
        return false;
    }
    
    var parent = jQuery(this).parents('tr');
    
    jQuery(parent).removeClass('hover');
    
    switch (direction)
    {
        case 'up':
            jQuery(parent).insertBefore(jQuery(parent).prev('tr'));
            jQuery(this).parents('table.jquery-enabled').rearrange_scores();
            break;
        case 'down':
            jQuery(parent).insertAfter(jQuery(parent).next('tr'));
            jQuery(this).parents('table.jquery-enabled').rearrange_scores();
            break;
    }
}

jQuery.fn.rearrange_scores = function()
{
    var size = jQuery(this).find('tbody td.midcom_helper_datamanager2_helper_sortable').size();
    
    jQuery(this).find('tbody td.midcom_helper_datamanager2_helper_sortable').each(function(i)
    {
        var last_index = size - 1;
        
        switch (i)
        {
            case 0:
                jQuery(this).find('.up')
                    .fadeTo(500, 0.3)
                    .removeClass('enabled');
                
                if (!jQuery(this).find('.down').hasClass('enabled'))
                {
                    jQuery(this).find('.down')
                        .addClass('enabled')
                        .fadeTo(500, 1);
                }
                
                break;
            
            case last_index:
                jQuery(this).find('.down')
                    .fadeTo(500, 0.3)
                    .removeClass('enabled');
                
                if (!jQuery(this).find('.up').hasClass('enabled'))
                {
                    jQuery(this).find('.up')
                        .addClass('enabled')
                        .fadeTo(500, 1);
                }
                
                break;
            
            default:
                // Stays somewhere in between the first and the last
                jQuery(this).find('.up, .down').each(function(n)
                {
                    // This was already enabled, no need to make any changes
                    if (jQuery(this).hasClass('enabled'))
                    {
                        return;
                    }
                    
                    jQuery(this)
                        .addClass('enabled')
                        .fadeTo(500, 1);
                });
        }
        
        jQuery(this).find('input.image_sortable, input.downloads_sortable').val(i + 1);
    });
    
    jQuery(this).find('tbody tr:odd')
        .addClass('odd')
        .removeClass('even');
    
    jQuery(this).find('tbody tr:even')
        .addClass('even')
        .removeClass('odd');
}

jQuery.fn.create_tablesorter_columns = function(options)
{
    if (!options.sortable_columns)
    {
        return;
    }
    
    jQuery(this).find('thead th').each(function(i)
    {
        if (   jQuery(this).hasClass('index')
            || jQuery(this).hasClass('new_column')
            || jQuery(this).hasClass('add_column')
            || jQuery(this).find('img').size() >= 1)
        {
            return;
        }
        
        jQuery('<img />')
            .css({
            
            })
            .addClass('column_sort move_left enabled')
            .attr({
                alt: 'move left',
                src: MIDCOM_STATIC_URL + '/stock-icons/16x16/stock_left.png',
            })
            .click(function()
            {
                if (!jQuery(this).hasClass('enabled'))
                {
                    return;
                }
                jQuery(this).parent().move_column('left', options);
            })
            .prependTo(jQuery(this));
        
        jQuery('<img />')
            .css({
            
            })
            .addClass('column_sort move_right enabled')
            .attr({
                alt: 'move left',
                src: MIDCOM_STATIC_URL + '/stock-icons/16x16/stock_right.png',
            })
            .click(function()
            {
                if (!jQuery(this).hasClass('enabled'))
                {
                    return;
                }
                jQuery(this).parent().move_column('right', options);
            })
            .appendTo(jQuery(this));
        
        if (jQuery(this).hasClass('deletable'))
        {
            jQuery('<img />')
                .css({
                
                })
                .addClass('enabled delete')
                .attr({
                    alt: 'delete',
                    src: MIDCOM_STATIC_URL + '/stock-icons/16x16/trash.png',
                })
                .click(function()
                {
                    var class_name = jQuery(this).parent().attr('class');
                    
                    if (!class_name)
                    {
                        return;
                    }
                    
                    class_name = class_name.replace(/tabledata_header /, '');
                    class_name = class_name.replace(/ deletable/, '');
                    class_name = class_name.replace(/ deleted/, '');
                    
                    jQuery(this).parent().delete_column(class_name, options);
                })
                .prependTo(jQuery(this));
        }
        
        jQuery(this).hover(
            function()
            {
                jQuery(this).find('img').addClass('hover');
            },
            function()
            {
                jQuery(this).find('img').removeClass('hover');
            }
        );
    });
    
    // Disable the moving outside table borders
    jQuery(options.table_id).find('thead th:first').next().find('img.move_left')
        .removeClass('enabled')
        .fadeTo(500, 0.5);
    
    jQuery(options.table_id).find('thead th:first').next().next().find('img.move_left')
        .addClass('enabled')
        .fadeTo(500, 1.0);
        
    // Disable the moving outside table borders
    jQuery(options.table_id).find('thead th:last').prev().find('img.move_right')
        .removeClass('enabled')
        .fadeTo(500, 0.5);
    
    jQuery(options.table_id).find('thead th:last').prev().prev().find('img.move_right')
        .addClass('enabled')
        .fadeTo(500, 1.0);
        
}

jQuery.fn.move_column = function(direction, options)
{
    class_name = jQuery(this).attr('class');
    class_name = class_name.replace(/tabledata_header /, '');
    class_name = class_name.replace(/ deletable/, '');
    class_name = class_name.replace(/ deleted/, '');
    
    jQuery(options.table_id).find('td.' + class_name + ', th.' + class_name).each(function(i)
    {
        if (direction == 'left')
        {
            jQuery(this).insertBefore(jQuery(this).prev());
        }
        else
        {
            jQuery(this).insertAfter(jQuery(this).next());
        }
        
        jQuery(this).find('img.column_sort').removeClass('hover');
    });
    
    jQuery(options.table_id).create_tablesorter_columns(options);
}

jQuery.fn.check_column_positions = function(options)
{

}

jQuery.fn.delete_column = function(column_id, options)
{
    if (jQuery(options.table_id).find('.' + column_id).hasClass('deleted'))
    {
        jQuery(options.table_id).find('.' + column_id)
            .removeClass('deleted')
            .find('input, select, textarea').prop('disabled', false);
    }
    else
    {
        jQuery(options.table_id).find('.' + column_id)
            .addClass('deleted')
            .find('input, select, textarea').prop('disabled', true);
    }
    
    if (jQuery(this).hasClass('deleted'))
    {
        jQuery('<input type="hidden" name="midcom_helper_datamanager2_tabledata_widget_delete[' + options.field_name + '][]"/>')
            .addClass('delete_input')
            .val(column_id)
            .appendTo(jQuery(this));
    }
    else
    {
        jQuery(this).find('input.delete_input').remove();
    }
}

jQuery.fn.initialize_column_creation = function(options)
{
    if (jQuery(this).find('img.add_new_column').size() > 0)
    {
        return;
    }
    
    if (jQuery(this).find('img.add_new_column').size() > 0)
    {
        return;
    }
    
    jQuery('<img />')
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
            jQuery('<th></th>')
                .attr('id', 'new_column_' + timestamp)
                .addClass('deletable')
                .insertBefore(jQuery(this).parent());
            
            jQuery('<span></span>')
                .addClass('field_name')
                .dblclick(change_label)
                .html(field_name)
                .appendTo('#new_column_' + timestamp);
            
            // Input for changing the column name
            jQuery('<input type="hidden" id="new_column_' + timestamp + '_input" name="midcom_helper_datamanager2_sortable_column[' + options.field_name + '][new_' + timestamp + ']" />')
                .addClass('field_name')
                .val(field_name)
                .appendTo(jQuery('#new_column_' + timestamp));
            
            // Insert a new column for each row
            jQuery(this).parents(options.table_id).find('tbody tr, tfoot tr').each(function(i)
            {
                var row = jQuery(this).attr('id');
                
                if (row)
                {
                    row = row.replace(/^row_/, '');
                }
                else
                {
                    row = '';
                }
                
                jQuery('<td></td>')
                    .addClass('new_column_' + timestamp)
                    .appendTo(jQuery(this));
                
                jQuery('<input type="text" />')
                    .addClass('column_field tabledata_widget_text')
                    .attr({
                        name: 'midcom_helper_datamanager2_type_tabledata[' + options.field_name + '][' + row + '][new_' + timestamp + ']',
                        id: 'midcom_helper_datamanager2_type_tabledata_' + options.field_name + '_new_' + timestamp + '_' + i
                    })
                    .appendTo(jQuery(this).find('td.new_column_' + timestamp));
            });
            
            // Recreate the sortable columns
            jQuery(this).create_tablesorter_columns(options);
        })
        .appendTo(jQuery(this).find('thead th.add_column'));
}

jQuery(document).ready(function()
{
    jQuery('table.midcom_helper_datamanager2_tabledata_widget th span.allow_rename')
        .dblclick(change_label);
});
