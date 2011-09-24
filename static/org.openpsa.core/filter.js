$(document).ready(function()
{
    $('form.filter .filter_apply').bind('click', function()
    {
        $(this).closest('form').submit();
    });
    $('form.filter .filter_unset').bind('click', function()
    {
        var form = $(this).closest('form');
        form
            .append('<input type="hidden" name="unset_filter" value="' + form.attr('id') + '" />')
            .submit();
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