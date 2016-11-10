<?php
$group = $data['current_group'];
$query_data = $data['query_data'];
$span = 4;
if (array_key_exists('hour_type_filter', $query_data)) {
    $span++;
}
if (array_key_exists('invoiceable_filter', $query_data)) {
    $span++;
}
?>
                <tbody class="group">
                    <tr class="header">
                        <th colspan="&(span);">&(group['title']);</th>
                    </tr>