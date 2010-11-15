<?php
$prefix = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX);
$task =& $data['task'];
echo "<tr class=\"{$data['class']}\">\n<td class='multivalue'>\n<a class='celltitle' href=\"{$prefix}task/{$task->guid}/\">{$task->title}</a>\n";
if ($task->manager)
{
    $contact = org_openpsa_contactwidget::get($task->manager);
    echo sprintf($data['l10n']->get("from %s"), $contact->show_inline());
}
$task->get_members();
if ($_MIDCOM->auth->can_do('midgard:update', $task)
    && isset($task->resources[midcom_connection::get_user()]))
{
?>
<form method="post" action="<?php echo $prefix; ?>workflow/<?php echo $task->guid; ?>/">
    <!-- TODO: If we need all resources to accept task hide tools when we have accepted and replace with "pending acceptance from..." -->
    <ul class="task_tools">
        <li><input type="submit" name="org_openpsa_projects_workflow_action[accept]" class="yes" value="<?php echo $data['l10n']->get('accept'); ?>" /></li>
        <li><input type="submit" name="org_openpsa_projects_workflow_action[decline]" class="no" value="<?php echo $data['l10n']->get('decline'); ?>" /></li>
    </ul>
</form>
<?php
}

echo "</td>\n<td>";

if ($data['view'] == 'project_tasks')
{
    echo ' ' . strftime('%x', $task->start) . ' - ' . strftime('%x', $task->end) . "\n";
}
else if ($task->up)
{
    $parent = $task->get_parent();
    if ($parent->orgOpenpsaObtype == ORG_OPENPSA_OBTYPE_PROJECT)
    {
        $parent_url = "{$prefix}project/{$parent->guid}/";
    }
    else
    {
        $parent_url = "{$prefix}task/{$parent->guid}/";
    }
    echo " <a href=\"{$parent_url}\">{$parent->title}</a>\n";
}
echo "</td>\n";
echo "<td>\n";
if(isset($data['priority_array']) && array_key_exists($task->priority , $data['priority_array']))
{
    echo $data['l10n']->get($data['priority_array'][$task->priority]);
}
echo "</td>\n";
?>
    <td class="numeric">
      <span title="<?php echo $data['l10n']->get('planned hours'); ?>"><?php echo round($task->plannedHours, 2);
    ?>
    </span>
    </td>
    <td class="numeric">
      <span title="<?php echo $data['l10n']->get('reported'); ?>"><?php echo round($task->reportedHours, 2);
    ?>
    </span>
    </td>
    <td class="numeric">
      <span title="<?php echo $data['l10n']->get('invoiceable'); ?>"><?php echo round($task->invoiceableHours, 2);
    ?>
    </span>
    </td>
</tr>