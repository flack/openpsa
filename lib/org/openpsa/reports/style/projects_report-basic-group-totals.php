<?php
$group = $data['current_group'];
$query_data = $data['query_data'];
$span = 3;
if (array_key_exists('hour_type_filter', $query_data))
{
    $span++;
}
if (array_key_exists('invoiceable_filter', $query_data))
{
    $span++;
}
?>
                    <tr class="totals">
                        <td colspan="&(span);"><?php printf($data['l10n']->get('%s total'), $group['title']); ?></td>
                        <td class="numeric"><?php printf('%01.2f', $group['total_hours']); ?></td>
                    </tr>