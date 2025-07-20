/* function to parse the input and exchanges , with .
*/
function parse_input(string) {
    return parseFloat(string.replace(',' , '.'));
}

function format_number(input) {
    if ($.fn.fmatter) {
        return $.fn.fmatter.number(input, $.jgrid.locales[$.jgrid.defaults.locale].formatter);
    }
    return input.toFixed(2);
}

function calculate_row(id) {
    var price_unit = parse_input($("#price_per_unit_" + id).val()),
        units = parse_input($("#units_" + id).val()),
        sum = 0;
    //check if they are numbers
    if (!isNaN(price_unit) && !isNaN(units)) {
        sum = price_unit * units;
    }
    $('#row_sum_' + id).html(format_number(sum));
}

function calculate_total(table) {
    var total = 0;
    table.find('tbody tr').each(function() {
        if ($(this).find('input[type="checkbox"]').is(':checked')) {
            total += parse_input($(this).find('.units').val()) * parse_input($(this).find('.price_per_unit').val());
        }
    });

    table.find('tfoot .totals').text(format_number(total));
}

function hide_invoice_address() {
    if ($('#org_openpsa_invoices_use_contact_address').is(':checked')) {
        $(".invoice_address").hide();
    } else {
        $(".invoice_address").show();
    }
}

$(document).ready(function() {
    if ($('#org_openpsa_invoices_use_contact_address').length > 0) {
        hide_invoice_address();
        $('#org_openpsa_invoices_use_contact_address').change(function() {
            hide_invoice_address();
        });
    }

    $('.projects table')
        .on('change', 'input[type="text"]', function() {
            var task_id = $(this).closest('tr').attr('id').replace('task_', '');
            calculate_row(task_id);
            calculate_total($(this).closest('table'));
        })
        .on('change', 'input[type="checkbox"]', function() {
            calculate_total($(this).closest('table'));
        })
        .each(function() {
            calculate_total($(this));
        })
        .parent().on('submit', function() {
            $(this).find('.numeric input').each(function() {
                $(this).val(parse_input($(this).val()));
            });
        });

    $('#add-journal-entry').on('click', function() {
        var button = $(this),
            dialog,
            options = {
                title:  this.title,
                resizable: false,
                modal: true,
                buttons: {}
            },
            form = $('<form action="' + MIDCOM_PAGE_PREFIX + '__mfa/org.openpsa.relatedto/rest/journalentry/" method="post">'),
            text = $('<input type="text" required name="title" class="add-journal-text">').appendTo(form),
            submit = $('<input type="submit">')
                    .hide()
                    .appendTo(form);

        options.buttons[this.dataset.dialogSubmitLabel] = function() {
            submit.click();
        };
        options.buttons[this.dataset.dialogCancelLabel] = function() {
            $( this ).dialog( "close" );
        };
        dialog = $('<div>')
            .append(form)
            .appendTo($('body'))
            .dialog(options);

        form.on('submit', function(e) {
            e.preventDefault();
            $.post(this.action, {
                linkGuid: button.data('guid'),
                title: text.val()
            },
            function () {
                dialog.dialog("close");
                window.location.reload();
            });
        });
    });

    $('button.send_by_mail').on('click', function() {
        var guid = $(this).attr('id').replace('invoice_', '');
        var url = window.location.origin + '/invoices/invoice/action/send_by_mail/' + guid + '/';
        create_dialog($(this), 'send_by_mail', url);
    });
});
