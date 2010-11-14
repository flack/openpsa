<?php
$prefix = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX);
$task =& $data['task'];
$action = 'remove_complete';
$checked = ' checked="checked"';

//TODO: Check deliverables
//NOTE: The hidden input is there on purpose, if we remove a check from checkbox, it will not get posted at all...
?>
<tr class="&(data['class']);">
<td class="multivalue">
<form method="post" action="<?php echo $prefix; ?>workflow/<?php echo $task->guid; ?>/">
        <input type="hidden" name="org_openpsa_projects_workflow_action[&(action);]" value="true" />
        <input type="checkbox"&(checked:h); name="org_openpsa_projects_workflow_dummy" value="true" onchange="this.form.submit()" /><a href="<?php echo $prefix; ?>task/<?php echo $task->guid; ?>/"><?php echo $task->title; ?></a><br />

<?php
//PONDER: Check ACL in stead ?
if ($_MIDGARD['user'] == $task->manager)
{
?>
    <form method="post" action="<?php echo $prefix; ?>workflow/<?php echo $task->guid; ?>">
        <ul class="task_tools">
            <li><input type="submit" name="org_openpsa_projects_workflow_action[approve]" class="yes" value="<?php echo $data['l10n']->get('approve'); ?>" /></li>
            <!-- PONDER: This is kind of redundant  when one can just remove the checkbox -->
            <li><input type="submit" name="org_openpsa_projects_workflow_action[reject]" class="no" value="<?php echo $data['l10n']->get('dont approve'); ?>" /></li>
        </ul>
    </form>
<?php
}
else if ($task->manager)
{
    $contact = org_openpsa_contactwidget::get($task->manager);

    echo sprintf($data['l10n']->get("pending approval by %s"), $contact->show_inline());
}
?>
</td>
<td>
<?php
if ($task->up)
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
?>
</td>
<?php
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