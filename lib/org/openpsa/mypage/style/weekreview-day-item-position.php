<?php
$position = $data['object'];
$position_label = $position->get_city_string();

// Add Google Maps link for now
$position_label = "<a href=\"http://maps.google.com/?ie=UTF8&om=0&z=16&ll={$position->latitude},{$position->longitude}&t=h\" target=\"_blank\">{$position_label}</a>";

$position_label .= ' (' . sprintf($_MIDCOM->i18n->get_string('source %s', 'org.routamc.positioning'), $_MIDCOM->i18n->get_string($position->importer, 'org.routamc.positioning')) . ')';
?>
<tr class="position &(data['class']);">
    <td class="time">
        <?php
        echo date('H:i', $data['time']);
        ?>
    </td>
    <td>
        <?php
        echo $position_label;
        ?>
    </td>
    <td>&nbsp;</td>
    <td>&nbsp;</td>
</tr>