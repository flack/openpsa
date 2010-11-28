<?php
$task_status = $data['object'];
$task = org_openpsa_projects_task_dba::get_cached($task_status->task);

$task_label = $task->get_label();
if ($data['projects_url'])
{
    $task_label = "<a href=\"{$data['projects_url']}task/{$task->guid}/\">{$task_label}</a>";
}

$status_changer_label = $_MIDCOM->i18n->get_string('system', 'org.openpsa.projects');
$target_person_label = $_MIDCOM->i18n->get_string('system', 'org.openpsa.projects');

$fallback_creator = midcom_db_person::get_cached(1);
if (    $task_status->metadata->creator
     && $task_status->metadata->creator != $fallback_creator->guid)
{
    $status_changer = org_openpsa_contactwidget::get($task_status->metadata->creator);
    $status_changer_label = $status_changer->show_inline();
}

if ($task_status->targetPerson)
{
    $target_person = org_openpsa_contactwidget::get($task_status->targetPerson);
    $target_person_label = $target_person->show_inline();
}

$message = sprintf($_MIDCOM->i18n->get_string($task_status->get_status_message(), 'org.openpsa.projects'), $status_changer_label, $target_person_label);
?>
<tr class="hour_report &(data['class']);">
    <td class="time">
        <?php
        echo date('H:i', $data['time']);
        ?>
    </td>
    <td>
        <?php
        echo "{$task_label}";
        ?>
    </td>
    <td class="multivalue">
        <?php
        echo "{$message}";
        ?>
    </td>
    <td>&nbsp;</td>
</tr>