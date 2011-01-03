$(document).ready(function()
{
    $(".report_checkbox").click(
    function()
    {
        id = "sum_"+$(this).attr('id');
        invoice_value = $("#"+ id).html();
        sum_value = $("#new_invoice_sum").attr('value');
        if ($(this).is(':checked'))
        {
            sum_value = Number(sum_value) + Number(invoice_value);
        }
        else
        {
            sum_value = Number(sum_value) - Number(invoice_value);
        }
        $("#new_invoice_sum").attr('value' , sum_value);
    })
});

id_count = 1;
//function to add a new row
function add_new_row()
{
    html = "<tr id='row_" + id_count +"' class='invoice_item_row'>";
    /*html += "<td>" + position++ + "</td>";*/
    //html += "<td><input class='input_description' type='text' name='invoice_items_new[" + id_count + "][description]' value='' /></td>";
    html += "<td><textarea class='input_description' name='invoice_items_new[" + id_count + "][description]' ></textarea></td>";

    html += "<td><input class='input_price_per_unit numeric' type='text' onchange=\"calculate_row('" + id_count +"')\" id='price_per_unit_" + id_count +"' name='invoice_items_new[" + id_count + "][price_per_unit]' value='' /></td>";
    html += "<td><input class='input_units numeric' type='text' onchange=\"calculate_row('" + id_count +"')\" id='units_" + id_count +"' name='invoice_items_new[" + id_count + "][units]' value='' /></td>";
    html += "<td class='row_sum numeric' id='row_sum_" + id_count +"'>0</td>";
    html += "<td><div class='remove_button' onclick=\"remove_row('" + id_count + " ')\">\&nbsp;</div> </td>";
    html += "</tr>";
    id_count++;
    jQuery("#row_invoice_sum").before(html);
}
/* function to parse the input and exchanges , with .
*/
function parse_input(string)
{
    search_string = ',';
    replace_string = '.';

    return Number(string.replace(search_string , replace_string));
}

function calculate_row(id)
{
    //remove old invoice_item_sum from total sum
    recalculate_invoice_sum(id , true);

    price_unit = parse_input($("#price_per_unit_" + id).val());
    units = parse_input($("#units_" + id).val());
    //check if they are numbers
    if (isNaN(price_unit)
        && isNaN(units))
    {
        sum = 0;
    }
    else
    {
        sum = price_unit * units;
    }
    $('#row_sum_' + id).html(sum.toFixed(2));
    recalculate_invoice_sum(id , false);
}

//function to calculate the total sum with the row for the passed id
function recalculate_invoice_sum(id , remove)
{
    invoice_sum = parse_input($("#invoice_sum").html());
    row_sum = parse_input($('#row_sum_' + id).html());

    if (remove == true)
    {
        row_sum = row_sum * -1;
    }
    sum = (invoice_sum + row_sum);
    $("#invoice_sum").html(sum.toFixed(2));
}

//function to add an hidden input field - which indicates the handler to delete the row
function mark_remove(object , guid)
{
    //check if it is already marked as remove
    if($(object).children("input").length > 0)
    {
        $(object).children("input").remove();
        $(object).parent().parent().removeClass('remove');
        $(object).addClass('remove_button');
        recalculate_invoice_sum(guid , false);
    }
    else
    {
        html = "<input type='hidden' name='invoice_items[" + guid + "][delete]' value='delete' />";
        $(object).append(html);
        $(object).removeClass('remove_button');
        $(object).addClass('add_button');
        $(object).parent().parent().addClass('remove');
        recalculate_invoice_sum(guid , true);
    }
}
//function to completely remove the row with passed id
function remove_row(id)
{
    recalculate_invoice_sum(id , true);
    $("#row_" + id).remove();
}