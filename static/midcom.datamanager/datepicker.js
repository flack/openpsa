function init_datepicker(options)
{
    $(options.id).datepicker({
        maxDate: new Date(options.max_date),
        minDate: new Date(options.min_date),
        dateFormat: $.datepicker.regional[Object.keys($.datepicker.regional)[Object.keys($.datepicker.regional).length - 1]].dateFormat || $.datepicker.ISO_8601,
        altField: options.alt_id,
        altFormat: $.datepicker.ISO_8601,
        prevText: '',
        nextText: '',
        showOn: options.showOn,
        buttonText: '',
        onClose: function() {
            $(options.id).next().focus();
        }
    }).on('change', function() {
        if ($(this).val() == '') {
            $(options.alt_id).val('');
        }
    });
    if ($(options.alt_id).val() && $(options.alt_id).val() !== '0000-00-00') {
        $(options.id).datepicker('setDate', new Date($(options.alt_id).val()));
    }

    if (options.hasOwnProperty('later_than')) {
        let pickers = $(options.id + ', ' + options.later_than),
            start_max = $(options.later_than).datepicker('option', 'maxDate'),
            end_min = $(options.id).datepicker('option', 'minDate');

        function parse_date(node)
        {
            const instance = $(node).data("datepicker"),
                value = $(node).val();

            return $.datepicker.parseDate(
                instance.settings.dateFormat || $.datepicker._defaults.dateFormat,
                value, instance.settings
            );
        }

        pickers.datepicker('option', 'beforeShow', function (input) {
            var default_date = $(input).val(),
                other_option, option, other_picker,
                date = parse_date(this),
                other_date,
                config = {defaultDate: default_date};

            if ('#' + this.id == options.later_than) {
                other_option = "minDate";
                option = "maxDate";
                other_picker = $(options.id);
                other_date = parse_date(other_picker);
                if (!other_date || other_date > start_max) {
                    other_date = start_max
                }
            } else {
                other_option = "maxDate";
                option = "minDate";
                other_picker = $(options.later_than);
                other_date = parse_date(other_picker);
                if (!other_date || other_date < end_min) {
                    other_date = end_min
                }
            }

            config[option] = other_date;
            other_picker.datepicker("option", other_option, date);
            return config;
        });
    }
}
