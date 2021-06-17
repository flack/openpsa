<?php
$view_task = $data['object_view'];
$task = $data['object'];
$task->get_members();
$formatter = $data['l10n']->get_formatter();
$siteconfig = org_openpsa_core_siteconfig::get_instance();
$expenses_url = $siteconfig->get_node_relative_url('org.openpsa.expenses');
?>
<div class="content-with-sidebar">
<div class="org_openpsa_projects_task main">
    <?php if ($view_task['tags']) { ?>
        <div class="tags">(&(view_task['tags']:h);)</div>
    <?php
    } ?>
    <h1><?php echo $data['l10n']->get('task'); ?>: &(view_task['title']:h);</h1>
    <div class="midcom_helper_datamanager2_view">
        <div class="field status <?php echo $task->status_type; ?>">
            <?php echo '<div class="title">' . $data['l10n']->get('task status') . ': </div>';
            echo '<div class="value">' . $data['l10n']->get($task->status_type) . '</div>';
            ?>
        </div>
        <div class="field">
            <?php echo '<div class="title">' . $data['l10n']->get('timeframe') . ': </div>';
            echo '<div class="value">' . $formatter->timeframe($task->start, $task->end, 'date') . '</div>';
            ?>
        </div>
        <?php
            if (array_key_exists('priority', $view_task)) {
                ?>
            <div class="field priority">
                <?php echo '<div class="title">' . $data['l10n']->get('priority') . ': </div>';
                echo '<div class="value">' . $view_task['priority'] . '</div>'; ?>
            </div>
                <?php
            }
            ?>
        <div class="field">
            <?php echo '<div class="title">' . $data['l10n']->get('description') . ': </div>';
            echo '<div class="value">' . $view_task['description'] . '</div>';
            ?>
        </div>
    </div>

    <?php
        if ($data['calendar_node']) {
            midcom::get()->dynamic_load($data['calendar_node'][MIDCOM_NAV_RELATIVEURL] . '/bookings/' . $task->guid . '/');
        }
        $tabs = [[
            'url' => $expenses_url . "hours/task/" . $task->guid . "/",
            'title' => $data['l10n']->get('hour reports'),
        ]];
        if ($data['has_subtasks']) {
            $url = $data['router']->generate('task-list-subtask', ['guid' => $task->guid]);
            $tabs[] = [
                'url' => substr($url, 1),
                'title' => $data['l10n']->get('tasks')
            ];
        }

        org_openpsa_widgets_ui::render_tabs($task->guid, $tabs);
    ?>
</div>
<aside>
    <?php
    if ($task->manager) {
        echo '<div class="area">';
        echo "<h2>" . $data['l10n']->get('manager') . "</h2>\n";
        $contact = org_openpsa_widgets_contact::get($task->manager);
        echo $contact->show_inline();
        echo '</div>';
    }

    if (!empty($task->resources)) {
        echo '<div class="area">';
        echo "<h2>" . $data['l10n']->get('resources') . "</h2>\n";
        foreach (array_keys($task->resources) as $contact_id) {
            $contact = org_openpsa_widgets_contact::get($contact_id);
            echo $contact->show_inline() . " ";
        }
        echo "</div>\n";
    }

    if (!empty($task->contacts)) {
        echo '<div class="area">';
        echo "<h2>" . $data['l10n']->get('contacts') . "</h2>\n";
        foreach (array_keys($task->contacts) as $contact_id) {
            $contact = org_openpsa_widgets_contact::get($contact_id);
            $contact->show();
        }
        echo "</div>\n";
    }

    $status_helper = new org_openpsa_projects_status($task);
    $status_helper->render();
    ?>
</aside>
</div>
