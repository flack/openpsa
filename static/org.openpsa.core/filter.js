$(document).ready(function()
{
    $('form.org_openpsa_filter .filter_input').each(function(index, item)
    {
        $(item).data('original_value', $(item).val());
    });
    $('form.org_openpsa_filter .filter_apply').bind('click', function()
    {
        var filter_values = '?';
        $('form.org_openpsa_filter input[type="text"]').each(function(index, element)
        {
            if (filter_values.length > 1)
            {
                filter_values += '&';
            }
            filter_values += $(element).attr('name') + '=' + $(element).val();
        });
        $('form.org_openpsa_filter select').each(function(index, element)
        {
            var filter_name = $(element).attr('name').slice(0, $(element).attr('name').length - 2);
            $.each($(element).val(), function(i, value)
            {
                if (filter_values.length > 1)
                {
                    filter_values += '&';
                }

                filter_values += filter_name + '[' + i + ']=' + value;
            });
        });
        if (filter_values.length > 1)
        {
            $(this).closest('form').prop('action', $(this).closest('form').prop('action') + filter_values);
        }
        $(this).closest('form').submit();
    });
    $('form.org_openpsa_filter .filter_unset').bind('click', function()
    {
        var form = $(this).closest('form');
        form
            .append('<input type="hidden" name="unset_filter" value="' + form.attr('id') + '" />')
            .submit();
    });
    $('form.org_openpsa_filter input').bind('keypress', function(e)
    {
        if (e.which == 13)
        {
            $(this).closest('form').submit();
        }
    });
    $('form.org_openpsa_filter .filter_input').bind('change', function()
    {
        if ($(this).data('original_value') !== $(this).val())
        {
            $(this).closest('.org_openpsa_filter_widget').addClass('filter-changed');
        }
        else
        {
            $(this).closest('.org_openpsa_filter_widget').removeClass('filter-changed');
        }
    });
});

var org_openpsa_filter =
{
    init_timeframe: function (ids)
    {
        var datepickers = $('#' + ids.from + ', #' + ids.to).datepicker(
        {
            dateFormat: 'yy-mm-dd',
            beforeShow: function (input, inst)
            {
                var default_date = $(input).val(),
                other_option = this.id == ids.from ? "minDate" : "maxDate",
                option = this.id == ids.from ? "maxDate" : "minDate",
                instance = $(this).data("datepicker"),
                date = $.datepicker.parseDate(
                        instance.settings.dateFormat ||
                        $.datepicker._defaults.dateFormat,
                        default_date, instance.settings);
                datepickers.not(this).datepicker("option", other_option, date),
                config = {defaultDate: default_date};
                config[option] = datepickers.not(this).val();
                return config;
            }
        });
    }
}