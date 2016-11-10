<?php
$event = $data['object'];

$event_label = $event->title;
if (!empty($data['calendar_node'])) {
    $event_url = "{$data['calendar_node'][MIDCOM_NAV_ABSOLUTEURL]}event/{$event->guid}";
    $event_js = org_openpsa_calendar_interface::get_viewer_attributes($event->guid, $data['calendar_node']);
    $event_label = "<a {$event_js}>{$event_label}</a>";
}
?>
<tr class="event &(data['class']);">
    <td class="time">
        <?php
        echo $data['l10n']->get_formatter()->timeframe($event->start, $event->end, 'time');
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