<tr class="&(data['class']);" id="task_<?php echo $data['task']->id;?>">
    <td>
        <input type="checkbox"<?php echo $data['disabled']; ?> name="org_openpsa_invoices_invoice_tasks[<?php echo $data['task']->id; ?>]" checked="checked" value="1" />
    </td>
    <td>
        <?php
        if ($data['projects_url']) {
            echo "<a href=\"{$data['projects_url']}task/{$data['task']->guid}/\">" . $data['task']->get_label() . "</a>\n";
        } else {
            echo $data['task']->get_label();
        }
        ?>
    </td>
    <td class="numeric">
        <?php echo $data['reported_hours']; ?>
    </td>
    <td class="numeric">
        <input id="units_<?php echo $data['task']->id;?>" class="units" type="text"<?php echo $data['disabled']; ?> size="6" name="org_openpsa_invoices_invoice_tasks_units[<?php echo $data['task']->id; ?>]" value="<?php echo $data['invoiceable_units']; ?>" />
    </td>
    <td class="numeric">
        <input id="price_per_unit_<?php echo $data['task']->id;?>" class="price_per_unit" type="text"<?php echo $data['disabled']; ?> size="6" name="org_openpsa_invoices_invoice_tasks_price[<?php echo $data['task']->id; ?>]" value="<?php echo $data['default_price']; ?>" />
    </td>
    <td id="row_sum_<?php echo $data['task']->id;?>" class="numeric">
        <?php echo $data['invoiceable_units'] * $data['default_price'];?>
    </td>
</tr>