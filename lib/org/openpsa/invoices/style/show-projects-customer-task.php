<tr class="&(data['class']);">
    <td>
        <input type="checkbox"<?php echo $data['disabled']; ?> name="org_openpsa_invoices_invoice_tasks[<?php echo $data['task']->id; ?>]" checked="checked" value="1" />
    </td>
    <td>
        <?php
        if ($data['projects_url'])
        {
            echo "<a href=\"{$data['projects_url']}task/{$data['task']->guid}/\">" . $data['task']->get_label() . "</a>\n";
        }
        else
        {
            echo $data['task']->get_label();
        }
        ?>
    </td>
    <td>
        <input id="units_<?php echo $data['task']->id;?>"type="text"<?php echo $data['disabled']; ?> size="6" name="org_openpsa_invoices_invoice_tasks_units[<?php echo $data['task']->id; ?>]" value="<?php echo $data['invoiceable_hours']; ?>" onchange="calculate_row('<?php echo $data['task']->id;?>')" />
    </td>
    <td>
        <input id="price_per_unit_<?php echo $data['task']->id;?>" type="text"<?php echo $data['disabled']; ?> size="6" name="org_openpsa_invoices_invoice_tasks_price[<?php echo $data['task']->id; ?>]" value="<?php echo $data['default_price']; ?>" onchange="calculate_row('<?php echo $data['task']->id;?>')" />
    </td>
    <td id="row_sum_<?php echo $data['task']->id;?>">
        <?php echo $data['invoiceable_hours'] * $data['default_price'];?>
    </td>
</tr>