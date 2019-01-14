<?php
$hour_report = $data['object'];
$task = org_openpsa_projects_task_dba::get_cached($hour_report->task);
$task_label = $task->title;
if ($data['projects_url']) {
    $task_label = "<a href=\"{$data['projects_url']}task/{$task->guid}\">{$task_label}</a>";
}
?>
<tr class="hour_report &(data['class']);">
    <td class="time">
        <?php
        echo $data['l10n']->get_formatter()->time($data['time']);
        ?>
    </td>
    <td>
        <?php
        echo $task_label;
        ?>
    </td>
    <td>&(hour_report->description);</td>
    <td class="numeric">&(hour_report->hours);h</td>
</tr>