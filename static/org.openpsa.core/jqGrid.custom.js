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

var org_openpsa_grid_resize = {
    attach_events: function(scope)
    {
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
    }
};

var org_openpsa_export_csv = {
    configs: {},
    separator: ';',
    add: function (config)
    {
        this.configs[config.id] = config;

        $('#' + config.id + '_export input[type="submit"]').bind('click', function()
        {
            var id = $(this).parent().attr('id').replace(/_export$/, '');
            org_openpsa_export_csv.export(id);
        });
    },
    export: function(id)
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

$(document).ready(function(){
    org_openpsa_grid_resize.attach_events($(this));
});

$('#tabs').bind('tabsload', function(event, ui){
    org_openpsa_grid_resize.attach_events($(ui.panel));
});

