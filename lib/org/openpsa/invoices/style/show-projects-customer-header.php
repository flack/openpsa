<?php
//$data =& $_MIDCOM->get_custom_context_data('request_data');
?>
<script type="text/javascript">

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
</script>

<h2><?php echo $data['customer_label']; ?></h2>

<form method="post" action="">
    <table class="list">
        <thead>
            <tr>
                <th><?php echo $data['l10n']->get('invoice'); ?></th>
                <th><?php echo $_MIDCOM->i18n->get_string('task', 'org.openpsa.projects'); ?></th>
                <th><?php echo $_MIDCOM->i18n->get_string('hours', 'org.openpsa.projects'); ?></th>
                <th><?php echo $data['l10n']->get('price'); ?></th>
                <th><?php echo $data['l10n']->get('sum'); ?></th>
            </tr>
        </thead>
        <tbody>