/* function to parse the input and exchanges , with .
*/
function parse_input(string)
{
    search_string = ',';
    replace_string = '.';

    return string.replace(search_string , replace_string);
}

function calculate_row(id)
{
    price_unit = parse_input($("#price_per_unit_" + id).val());
    units = parse_input($("#units_" + id).val());
    //check if they are numbers
    if(isNaN(price_unit)
        && isNaN(units))
    {
        sum = 0;
    }
    else
    {
        sum = price_unit * units;
    }
    $('#row_sum_' + id).html(sum.toFixed(2));
}

function calculate_total(table)
{
    var total = 0;
    table.find('tbody tr').each(function()
    {
        if ($(this).find('input[type="checkbox"]').is(':checked'))
        {
            total += $(this).find('.units').val() * $(this).find('.price_per_unit').val()
        }
    });

    table.find('tfoot .totals').text(total.toFixed(2));
}

$(document).ready(function()
{
    $('.projects table')
        .delegate('input[type="text"]', 'change', function()
        {
            var task_id = $(this).closest('tr').attr('id').replace('task_', '');
            calculate_row(task_id);
            calculate_total($(this).closest('table'));
        })
        .delegate('input[type="checkbox"]', 'change', function()
        {
            calculate_total($(this).closest('table'));
        })
        .each(function()
        {
            calculate_total($(this));
        });
});