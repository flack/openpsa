<?php
$prefix = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX);
$task =& $data['task'];
echo "<tr class=\"{$data['class']}\">\n<td class='multivalue'>\n<a class='celltitle' href=\"{$prefix}task/{$task->guid}/\">{$task->title}</a>\n";

if ($task->manager)
{
    // FIXME: List resources instead
    $task->get_members();
    if ( count($task->resources) > 0)
    {
        $resources_string = '';
        foreach ($task->resources as $id => $boolean)
        {
            $contact = org_openpsa_widgets_contact::get($id);
            $resources_string .= ' ' . $contact->show_inline();
        }
        echo sprintf($data['l10n']->get("proposed to %s"), $resources_string);
    }
}

echo "</td>\n<td>\n";

if ($parent = $task->get_parent())
{
    if (is_a($parent, 'org_openpsa_projects_project'))
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
if (isset($data['priority_array']) && array_key_exists($task->priority, $data['priority_array']))
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