<?php
$report = $data['report'];
$query_data = $data['query_data'];
$span = 3;
if (array_key_exists('hour_type_filter', $query_data)) {
    $span++;
}
if (array_key_exists('invoiceable_filter', $query_data)) {
    $span++;
}
?>
                <tbody class="totals">
                    <tr class="totals">
                        <td colspan="&(span);"><?php echo $data['l10n']->get('totals'); ?></td>
                        <td class="numeric"><?php printf('%01.2f', $report['total_hours']); ?></td>
                    </tr>
                </tbody>