<form method="post">
<div style='padding-bottom:10px;'><?php echo "<input type='button' onclick='add_new_row();' value='" . $data['l10n']->get('add new invoice item') ."' />";?></div>
<table id="invoice_items" class="list">
<thead>
<tr>
<?php
    echo "<th>" . $_MIDCOM->i18n->get_string('description', 'midcom') . "</th>";
    echo "<th>" . $data['l10n']->get('price') . "</th>";
    echo "<th>" . $data['l10n']->get('quantity') . "</th>";
    echo "<th class='numeric'>" . $data['l10n']->get('sum') . "</th>";
    echo "<th></th>";
?>
</tr>
</thead>
<tbody>
<?php
    $invoice_sum = 0;
    foreach ($data['invoice_items'] as $i => $item)
    {
        $item_sum = $item->units * $item->pricePerUnit;
        $invoice_sum += $item_sum;
        echo "<tr id ='row_" . $i . "' class='invoice_item_row'>\n";
        echo "<td><textarea class='input_description' name='invoice_items[" . $i . "][description]' > " . $item->description . "</textarea></td>\n";
        echo "<td><input type='text' class='input_price_per_unit numeric' onchange=\"calculate_row('" . $i . "')\" id='price_per_unit_" . $i . "' name='invoice_items[" . $i ."][price_per_unit]' value='" . round($item->pricePerUnit, 2) . "' /></td>\n";
        echo "<td><input type='text' class='input_units numeric' onchange=\"calculate_row('" . $i . "')\" id='units_" . $i . "' name='invoice_items[" . $i ."][units]' value='" . round($item->units, 2) . "' /></td>\n";
        echo "<td class='row_sum numeric' id='row_sum_" . $i . "'>" . round($item_sum, 2) . "</td>\n";
        echo "<td><div class='remove_button' onclick=\"mark_remove(this , '" . $i . "')\">&nbsp;</div> </td>\n";
        echo "</tr>\n";
    }
?>
<tr id="row_invoice_sum">
<th colspan="3" class="">
<?php
echo $data['l10n']->get('invoice sum');
?>
</th>
<th id='invoice_sum' class="numeric">
<?php
    echo round($invoice_sum, 2);
?>
</th>
<th>&nbsp;</th>
</tr>
</tbody>
</table>
<div style='padding-top:15px;'>
<input type="submit" name="save" value="<?php echo $_MIDCOM->i18n->get_string('save', 'midcom') ?>"/>
<input type="submit" name="cancel" value="<?php echo $_MIDCOM->i18n->get_string('cancel', 'midcom') ?>"/>
</div>
</form>
