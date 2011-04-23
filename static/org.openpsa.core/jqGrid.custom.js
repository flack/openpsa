var org_openpsa_jqgrid_presets = {
    autowidth: true,
    altRows: true,
    altclass: 'even',
    deselectAfterSort: false,
    forceFit: true,
    gridview: true,
    headertitles: true,
    height: 'auto',
    hoverrows: true,
    shrinkToFit: true,
    sortable: true
};

$.jgrid.defaults = $.extend($.jgrid.defaults, org_openpsa_jqgrid_presets);

var org_openpsa_grid_resize =
{
    attach_events: function(scope)
    {
        if ($('.fill-height', scope).length > 0)
        {
            $('.fill-height table.ui-jqgrid-btable', scope).jqGrid('setGridParam', {onHeaderClick: function()
            {
                $(window).trigger('resize');
            }});

            org_openpsa_grid_resize.fill_height(scope);

            jQuery(window).resize(function()
            {
                org_openpsa_grid_resize.fill_height(scope);
            });
        }

        $('.full-width table.ui-jqgrid-btable', scope).each(function()
        {
            var id = $(this).attr('id');

            var resizer = function()
            {
                var panel = jQuery("#gbox_" + id).closest('.ui-tabs-panel');
                if (panel.hasClass('ui-tabs-hide'))
                {
                    return;
                }
                var new_width = jQuery("#gbox_" + id).parent().attr('clientWidth') - 5;
                try
                {
                    jQuery("#" + id).jqGrid().setGridWidth(new_width);
                }
                catch(e){}
            };
            resizer();

            jQuery(window).resize(function()
            {
                resizer();
            });
        });
    },
    fill_height: function(scope)
    {
        var grids_height = 0,
        controls_height = 0,
        container_height = $('#content-text').height() - $('.fill-height', scope).position().top;

        $('.fill-height', scope).each(function() {
            var part_height = $(this).outerHeight(true),
            grid_body = $("table.ui-jqgrid-btable", $(this)),
            grid_height = grid_body.parent().parent().outerHeight();

            if ($('#' + grid_body.attr('id')).jqGrid('getGridParam', 'gridstate') == 'visible')
            {
                grids_height += grid_body.outerHeight();
            }

            if (grid_height > part_height)
            {
                controls_height += part_height;
            }
            else
            {
                controls_height += (part_height - grid_height);
            }
        });

        $('.fill-height table.ui-jqgrid-btable', scope).each(function()
        {
            var id = $(this).attr('id'),
            factor = 1,
            new_height;

            if (grids_height > 0)
            {
                factor = $('#' + id).outerHeight() / grids_height;
            }

            new_height = (container_height - controls_height) * factor;

            try
            {
                $("#" + id).jqGrid().setGridHeight(new_height);
            }
            catch(e){}
        });
    }
};

var org_openpsa_grid_editable =
{
    grid_id: '',
    last_added_row: 0,
    options:
    {
        keys: true,
        afterrestorefunc: this.after_restore
    },

    enable_inline: function (grid_id, custom_options)
    {
        var lastsel;
        self = this;
        self.options = jQuery.extend({}, custom_options, self.options);

        self.grid_id = grid_id;
        $('#' + grid_id).jqGrid('setGridParam',
        {
            onSelectRow: function(id)
            {
                if (id && id !== lastsel)
                {
                    $('#' + id).restoreRow(lastsel);
                    lastsel = id;
                }
                self.editRow(id);
            },
        });
        self.add_inline_controls();
        var create_button_parameters =
        {
            caption: "",
            buttonicon: "ui-icon-plus",
            onClickButton: function()
            {
                var new_id = 'new_' + self.last_added_row++;
                $('#' + self.grid_id).jqGrid('addRowData', new_id, {}, 'last');
            },
        };
        $('#' + grid_id)
            .jqGrid('navGrid', "#p_" + grid_id, {add: false, del:false, refresh: false, edit: false, search: false})
            .jqGrid('navButtonAdd', "#p_" + grid_id, create_button_parameters);

    },
    editRow: function(id)
    {
        $('#' + self.grid_id).jqGrid('editRow', id, self.options);
        $('#edit_button_' + id).addClass('hidden');
        $('#save_button_' + id).show();
        $('#cancel_button_' + id).show();
    },
    saveRow: function(id)
    {
        $('#' + self.grid_id).jqGrid('saveRow', id, self.options);
        $('#edit_button_' + id).removeClass('hidden');
        this.after_restore(id);
    },
    restoreRow: function(id)
    {
        $('#' + self.grid_id).jqGrid('restoreRow', id, self.options);
        $('#edit_button_' + id).removeClass('hidden');
        this.after_restore(id);
    },
    deleteRow: function(id)
    {
        var edit_url = $('#' + self.grid_id).jqGrid('getGridParam', 'editurl')
        rowdata = $('#' + self.grid_id).jqGrid('getRowData', id),
        rowdata.oper = 'del';

        $.post(edit_url, rowdata, function(data, textStatus, jqXHR)
        {
            $('#' + self.grid_id).jqGrid('delRowData', id);
        });
    },
    after_restore: function(id)
    {
        $('#save_button_' + id).hide();
        $('#cancel_button_' + id).hide();
    },
    add_inline_controls: function()
    {
        var rowids = jQuery("#" + self.grid_id).jqGrid('getDataIDs');
        for (var i = 0; i < rowids.length; i++)
        {
            var current_rowid = rowids[i];
            be = "<input class='row_button row_edit' id='edit_button_" + current_rowid + "' type='button' value='E' />";
            bs = "<input class='row_button row_save' id='save_button_" + current_rowid + "' type='button' value='S' />";
            bc = "<input class='row_button row_cancel' id='cancel_button_" + current_rowid + "' type='button' value='C' />";
            bd = "<input class='row_button row_delete' id='delete_button_" + current_rowid + "' type='button' value='D' />";
            $("#" + self.grid_id).jqGrid('setRowData', current_rowid, {actions: be + bs + bc + bd});
            $("#edit_button_" + current_rowid).bind('click', function()
            {
                var id = $(this).attr('id').replace(/^edit_button_/, '');
                self.editRow(id);
            });
            $("#delete_button_" + current_rowid).bind('click', function()
            {
                var id = $(this).attr('id').replace(/^delete_button_/, '');
                self.deleteRow(id);
            });
            $("#save_button_" + current_rowid).bind('click', function()
            {
                var id = $(this).attr('id').replace(/^save_button_/, '');
                self.saveRow(id);
            })
                .hide();
            $("#cancel_button_" + current_rowid).bind('click', function()
            {
                var id = $(this).attr('id').replace(/^cancel_button_/, '');
                self.restoreRow(id);
            })
                .hide();
        }
    }

};

var org_openpsa_export_csv =
{
    configs: {},
    separator: ';',
    add: function (config)
    {
        this.configs[config.id] = config;

        $('#' + config.id + '_export input[type="submit"]').bind('click', function()
        {
            var id = $(this).parent().attr('id').replace(/_export$/, '');
            org_openpsa_export_csv.prepare_data(id);
        });
    },
    prepare_data: function(id)
    {
        var config = this.configs[id];
        var rows = jQuery('#' + config.id).jqGrid('getRowData');

        var data = '';

        for (var field in config.fields)
        {
            data += this.trim(config.fields[field]) + this.separator;
        }

        data += '\n';

        for (var i = 0; i < rows.length; i++)
        {
            for (field in config.fields)
            {
                if (typeof rows[i][field] != 'undefined')
                {
                    data += this.trim(rows[i][field]) + this.separator;
                }
            }
            data += '\n';
        }
        document.getElementById(config.id + '_csvdata').value += data;
    },
    trim: function(input)
    {
        var output = input.replace(/\n|\r/g, " " ); // remove line breaks
        output = output.replace(/\s+/g, " " ); // Shorten long whitespace
        output = output.replace(/^\s+/g, "" ); // strip leading ws
        output = output.replace(/\s+$/g, "" ); // strip trailing ws
        return output.replace(/<\/?([a-z][a-z0-9]*)\b[^>]*>/gi, ''); //strip HTML tags
    }
};

var org_openpsa_batch_processing =
{
    initialize: function(config)
    {
        var widgets_to_add = [];
        //build action form and associated widgets
        var action_select = '<div class="action_select_div" id="' + config.id + '_batch" style="display: none;">';
        action_select += '<input type="hidden" name="batch_grid_id" value="' + config.id + '" />';
        action_select += '<select id="' + config.id + '_batch_select" class="action_select" name="action" size="1">';

        $.each(config.options, function(key, values)
        {
            action_select += '<option value="' + key + '" >' + values.label + '</option>';
            if (typeof values.widget_config != 'undefined')
            {
                var widget_id = config.id + '__' + key;
                widgets_to_add.push({id: widget_id, insertAfter: '#' + config.id + '_batch_select', widget_config: values.widget_config});
            }
        });
        action_select += '</select><input type="submit" name="send" /></div>';
        $(action_select).appendTo($('#form_' + config.id));

        $.each(widgets_to_add, function(index, widget_conf)
        {
            midcom_helper_datamanager2_autocomplete.create_widget(widget_conf);
        });

        $('#' + config.id + '_batch_select').bind('change', function(event)
        {
            var grid_id = $(event.target).attr('id').replace(/_batch_select$/, ''),
            selected_option = $(event.target).val();
            $('.batch_widget').hide();
            $('#' + config.id + '_batch').css('display', 'inline');
            $('#' + config.id + '__' + selected_option + '_search_input').show();
        });

        //hook action select into grid so that it'll get shown when necessary
        $('#' + config.id).jqGrid('setGridParam',
        {
            onSelectRow: function(id)
            {
                if ($('#' + config.id).jqGrid('getGridParam', 'selarrrow').length == 0)
                {
                    $('#' + config.id + '_batch').hide();
                }
                else
                {
                    $('#' + config.id + '_batch').show();
                }
            },
            onSelectAll: function(rowids, status)
            {
                if (!status)
                {
                    $('#' + config.id + '_batch').hide();
                }
                else
                {
                    $('#' + config.id + '_batch').show();
                }
            }
        });

        //make sure grid POSTs our selection
        $("#form_" + config.id).bind('submit', function()
        {
            var i,
            s = $("#" + config.id).jqGrid('getGridParam', 'selarrrow');
            for (i = 0; i < s.length; i++)
            {
                jQuery('<input type="hidden" name="entries[' + s[i] + ']" value="On" />').appendTo('#form_' + config.id);
            }
        });
    }
}

$(document).ready(function(){
    org_openpsa_grid_resize.attach_events($(this));
});

$('#tabs').bind('tabsload', function(event, ui){
    org_openpsa_grid_resize.attach_events($(ui.panel));
});
