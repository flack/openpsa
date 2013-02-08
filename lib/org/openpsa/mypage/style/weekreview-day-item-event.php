<?php
$event = $data['object'];

$event_label = $event->title;
if (!empty($data['calendar_node']))
{
    $event_url = "{$data['calendar_node'][MIDCOM_NAV_ABSOLUTEURL]}event/{$event->guid}";
    $event_js = org_openpsa_calendar_interface::calendar_editevent_js($event->guid, $data['calendar_node']);
    $event_label = "<a href=\"{$event_url}\" onclick=\"{$event_js}\">{$event_label}</a>";
}
?>
<tr class="event &(data['class']);">
    <td class="time">
        <?php
        echo date('H:i', $event->start) . '-' . date('H:i', $event->end);
        ?>
    </td>
    <td>
        <?php
        echo "{$event_label}";
        ?>
    </td>
    <td>
        <?php
        echo "{$event->location}";
        ?>
    </td>
    <td>&nbsp;</td>
</tr>