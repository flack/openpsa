<?php
$hour = $data['current_row']['hour'];
$task = $data['current_row']['task'];
$person = $data['current_row']['person'];
$query_data = $data['query_data'];
?>
                    <tr class="item">
<?php   switch ($data['grouping']) {
            case 'date': ?>
                        <td>&(person.rname);</td>
<?php           break;
            case 'person': ?>
                        <td><?php echo $data['l10n']->get_formatter()->date($hour->date); ?></td>
<?php           break;
        } ?>
                        <td>&(task.title);</td>
<?php   if (array_key_exists('hour_type_filter', $query_data)) {
            ?>
                        <td>&(hour.reportType);</td>
<?php
        }   ?>
<?php   if (array_key_exists('invoiceable_filter', $query_data)) {
            if ($hour->invoiceable) {
                $hour_invoiceable_str = $data['l10n_midcom']->get('yes');
            } else {
                $hour_invoiceable_str = $data['l10n_midcom']->get('no');
            } ?>
                        <td>&(hour_invoiceable_str);</td>
<?php
        }       ?>
                        <td>&(hour.description);</td>
                        <td class="numeric"><?php printf('%01.2f', $hour->hours); ?></td>
                    </tr>