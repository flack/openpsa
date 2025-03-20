$(document).ready(function() {
    $('form.org_openpsa_queryfilter .filter_input').each(function(index, item) {
        $(item).data('original_value', $(item).val());
    });
    $('form.org_openpsa_queryfilter .filter_apply').on('click', function() {
        var filter_values = {};
        $('form.org_openpsa_queryfilter input[type="text"]').each(function(index, element) {
            filter_values[element.name] = $(element).val();
        });
        $('form.org_openpsa_queryfilter select').each(function(index, element) {
            var filter_name = element.name.slice(0, element.name.length - 2);
            if ($.isArray($(element).val())) {
                filter_values[filter_name] = [];
                $.each($(element).val(), function(i, value) {
                    filter_values[filter_name].push(value);
                });
            } else if ($(element).val()) {
                filter_values[element.name] = $(element).val();
            }
        });

        if (!$.isEmptyObject(filter_values)) {
            const url = new URL($(this).closest('form').prop('action'));
            for (const [key, value] of Object.entries(filter_values)) {
                url.searchParams.delete(key + '[]');
                if (Array.isArray(value)) {
                    value.forEach(function(val) {
                        url.searchParams.append(key + '[]', val);
                    });
                } else {
                    url.searchParams.set(key, value);
                }
            }
            $(this).closest('form').prop('action', url.toString());
        }
        $(this).closest('form').submit();
    });
    $('form.org_openpsa_queryfilter .filter_unset').on('click', function() {
        const form = $(this).closest('form'),
            container = $(this).closest('.org_openpsa_filter_widget'),
            url = new URL(form.prop('action'));

        $(this).parent().find('input, select').each(function() {
            url.searchParams.delete($(this).attr('name'));
        });

        form
            .attr('action', url.toString())
            .append('<input type="hidden" name="unset_filter" value="' + container.attr('id') + '" />')
            .submit();
    });
    $('form.org_openpsa_queryfilter input').on('keypress', function(e) {
        if (e.which == 13) {
            $(this).closest('form').submit();
        }
    });
    $('form.org_openpsa_queryfilter .filter_input').on('change', function() {
        if ($(this).data('original_value') !== $(this).val()) {
            $(this).closest('.org_openpsa_filter_widget').addClass('filter-changed');
        } else {
            $(this).closest('.org_openpsa_filter_widget').removeClass('filter-changed');
        }
    });
});

const org_openpsa_filter = {
    init_timeframe: function (ids) {
        var datepickers = $('#' + ids.from + ', #' + ids.to).datepicker({
            dateFormat: 'yy-mm-dd',
            beforeShow: function (input) {
                var default_date = $(input).val(),
                    other_option = this.id == ids.from ? "minDate" : "maxDate",
                    option = this.id == ids.from ? "maxDate" : "minDate",
                    instance = $(this).data("datepicker"),
                    date = $.datepicker.parseDate(
                        instance.settings.dateFormat ||
                            $.datepicker._defaults.dateFormat,
                        default_date, instance.settings),
                    config = {defaultDate: default_date};

                config[option] = datepickers.not(this).val();
                datepickers.not(this).datepicker("option", other_option, date);
                return config;
            }
        });
    }
}
