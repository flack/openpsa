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
        buttonText: '&#xf073;',
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
        var pickers = $(options.id + ', ' + options.later_than);
        pickers.datepicker('option', 'beforeShow', function (input) {
            var default_date = $(input).val(),
                other_option, option, other_picker,
                instance = $(this).data("datepicker"),
                date = $.datepicker.parseDate(
                    instance.settings.dateFormat ||
                        $.datepicker._defaults.dateFormat,
                    default_date, instance.settings),
                config = {defaultDate: default_date};

            if ('#' + this.id == options.later_than) {
                other_option = "minDate";
                option = "maxDate";
                other_picker = $(options.id);
            } else {
                other_option = "maxDate";
                option = "minDate";
                other_picker = $(options.later_than);
            }

            config[option] = other_picker.val();
            other_picker.datepicker("option", other_option, date);
            return config;
        });
    }
}
