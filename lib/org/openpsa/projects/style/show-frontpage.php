<?php
$prefix = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX);
$formatter = $data['l10n']->get_formatter();
?>
<div class="wide">
    <h1><?php echo $data['l10n']->get('projects'); ?></h1>
    <?php
    echo "<table class='list'>\n";
    echo "  <thead>\n";
    echo "    <tr>\n";
    echo "        <th>" . $data['l10n']->get('project') . "</th>\n";
    echo "        <th>" . $data['l10n']->get('start') . "</th>\n";
    echo "        <th>" . $data['l10n']->get('end') . "</th>\n";
    echo "        <th colspan=\"4\">" . $data['l10n']->get('tasks') . "</th>\n";
    echo "        <th>" . $data['l10n']->get('hours') . "</th>\n";
    echo "    </tr>\n";

    echo "  </thead>\n";
    ?>
      <tfoot>
       <tr>
        <td colspan="8">
        <?php
        printf($data['l10n']->get('%d closed projects'), $data['closed_count']);
        ?>
        </td>
       </tr>
      </tfoot>
    <?php
    foreach ($data['customers'] as $customer => $projects)
    {
        try
        {
            $customer = new org_openpsa_contacts_group_dba($customer);
            $customer_title = $customer->official;
        }
        catch (midcom_error $e)
        {
            $customer_title = $data['l10n']->get('no customer');
        }

        echo "    <tr>\n";
        echo "        <th colspan=\"9\">{$customer_title}</th>\n";
        echo "    </tr>\n";

        $class = "odd";

        $position = '';

        foreach ($projects as $i => $project)
        {
            if ($class == "even")
            {
                $class = 'odd';
            }
            else
            {
                $class = "even";
            }
            if ($i == sizeof($projects) - 1)
            {
                $position = 'bottom';
            }

            $task_count = $project->get_task_count();
            $hours = $project->get_task_hours();

            echo "    <tr class='{$class}'>\n";
            echo "        <td class='multivalue'>";
            $toggle_class = (array_sum($task_count) > 0) ? 'expand-icon' : 'hidden-icon';
            echo "<img class='" . $toggle_class . "' id='project_" . $project->id . "' onclick=\"show_tasks_for_project(this, '{$prefix}task/list/json/{$project->guid}/', '{$prefix}task/');\" src=\"" . MIDCOM_STATIC_URL . "/stock-icons/16x16/plus" . $position . ".png\" alt=\"" . $data['l10n']->get($project->status_type) . "\" title=\"" . $data['l10n']->get('show tasks') . "\" />\n";

            echo "<img class='status-icon' src=\"" . MIDCOM_STATIC_URL . "/stock-icons/16x16/" . $project->get_icon() . "\" alt=\"" . $data['l10n']->get($project->status_type) . "\" title=\"" . $data['l10n']->get($project->status_type) . "\" />";
            echo "        <a href=\"{$prefix}project/{$project->guid}/\">{$project->title}</a></td>\n";
            echo "        <td> " . $formatter->date($project->start) . "</td>\n";
            echo "        <td> " . $formatter->date($project->end) . "</td>\n";
            echo "        <td class=\"numeric\"><span title=\"" . $data['l10n']->get('not_started') . "\">{$task_count['not_started']}</span></td>\n";
            echo "        <td class=\"numeric\"><span title=\"" . $data['l10n']->get('ongoing') . "\">{$task_count['ongoing']}</span></td>\n";
            echo "        <td class=\"numeric\"><span title=\"" . $data['l10n']->get('on_hold') . "\">{$task_count['on_hold']}</span></td>\n";
            echo "        <td class=\"numeric\"><span title=\"" . $data['l10n']->get('closed') . "\">{$task_count['closed']}</span></td>\n";
            echo "        <td class=\"numeric\"> " ;
            echo "            <span title=\"" . $data['l10n']->get('reported') .  "\">" . round($hours['reportedHours'], 2) . "</span>";
            if ($hours['plannedHours'] > 0)
            {
                echo          " / <span title=\"" . $data['l10n']->get('planned hours') .  "\">" . round($hours['plannedHours'], 2) . "</span>";
            }
            echo "        </td>\n";

            echo "    </tr>\n";
        }
    }
    ?>
    </table>
</div>