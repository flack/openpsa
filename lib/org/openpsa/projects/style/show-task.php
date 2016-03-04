<?php
$view_task = $data['object_view'];
$task = $data['object'];
$task->get_members();

$prefix = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX);

$siteconfig = org_openpsa_core_siteconfig::get_instance();
$sales_url = $siteconfig->get_node_full_url('org.openpsa.sales');
$expenses_url = $siteconfig->get_node_relative_url('org.openpsa.expenses');
?>
<div class="org_openpsa_projects_task">
    <div class="sidebar">
        <?php
        if ($task->agreement)
        {
            try
            {
                $agreement = org_openpsa_sales_salesproject_deliverable_dba::get_cached($task->agreement);
                $output = $agreement->deliverable_html;

                if ($sales_url)
                {
                    $salesproject = org_openpsa_sales_salesproject_dba::get_cached($agreement->salesproject);
                    $output = "<a href=\"{$sales_url}salesproject/{$salesproject->guid}/#deliverable_{$agreement->guid}\">{$output}</a>\n";
                }

                echo '<div class="area">';
                echo "<h2>" . $data['l10n']->get('agreement') . "</h2>\n";
                echo $output;
                echo '</div>';
            }
            catch (midcom_error $e){}
        }

        if ($task->manager)
        {
            echo '<div class="area">';
            echo "<h2>" . $data['l10n']->get('manager') . "</h2>\n";
            $contact = org_openpsa_widgets_contact::get($task->manager);
            echo $contact->show_inline();
            echo '</div>';
        }

        $remote_search = $task->get_parameter('org.openpsa.projects.projectbroker', 'remote_search');
        if ($remote_search)
        {
            echo "<div class=\"area resources search\">\n";
            if ($remote_search == 'REQUEST_SEARCH')
            {
                echo $data['l10n']->get('remote resource search requested');
            }
            else if ($remote_search == 'SEARCH_IN_PROGRESS')
            {
                echo $data['l10n']->get('remote resource search in progress');
                // TODO: Link to results listing
            }
            echo "</div>\n";
        }
        else if (count($task->resources) > 0)
        {
            echo '<div class="area">';
            echo "<h2>" . $data['l10n']->get('resources') . "</h2>\n";
            foreach (array_keys($task->resources) as $contact_id)
            {
                $contact = org_openpsa_widgets_contact::get($contact_id);
                echo $contact->show_inline() . " ";
            }
            echo "</div>\n";
        }

        if (count($task->contacts) > 0)
        {
            echo '<div class="area">';
            echo "<h2>" . $data['l10n']->get('contacts') . "</h2>\n";
            foreach (array_keys($task->contacts) as $contact_id)
            {
                $contact = org_openpsa_widgets_contact::get($contact_id);
                echo $contact->show();
            }
            echo "</div>\n";
        }
        ?>
<div class="org_openpsa_helper_box history status">
    <?php
    $qb = org_openpsa_projects_task_status_dba::new_query_builder();
    $qb->add_constraint('task', '=', $task->id);
    $qb->add_order('timestamp', 'DESC');
    $qb->add_order('type', 'DESC');
    $ret = $qb->execute();

    if (!empty($ret))
    {
        echo "<h3>" . $data['l10n']->get('status history') . "</h3>\n";

        echo "<div class=\"current-status {$task->status_type}\">" . $data['l10n']->get('task status') . ': ' . $data['l10n']->get($task->status_type) . "</div>\n";

        echo "<ul>\n";

        $fallback_creator = midcom_db_person::get_cached(1);
        foreach ($ret as $status_change)
        {
            echo "<li>";

            $status_changer_label = $data['l10n']->get('system');
            $target_person_label = $data['l10n']->get('system');

            if (    $status_change->metadata->creator
                 && $status_change->metadata->creator != $fallback_creator->guid)
            {
                $status_changer = org_openpsa_widgets_contact::get($status_change->metadata->creator);
                $status_changer_label = $status_changer->show_inline();
            }

            if ($status_change->targetPerson)
            {
                $target_person = org_openpsa_widgets_contact::get($status_change->targetPerson);
                $target_person_label = $target_person->show_inline();
            }

            $message = sprintf($data['l10n']->get($status_change->get_status_message()), $status_changer_label, $target_person_label);
            $status_changed = strftime('%x %H:%M', $status_change->metadata->created);
            echo "<span class=\"date\">{$status_changed}</span>: <br />{$message}";

            echo "</li>\n";
        }
        echo "</ul>\n";
    }
    ?>
</div>

    </div>

    <div class="main">
        <?php if ($view_task['tags'])
        { ?>
            <div class="tags">(&(view_task['tags']:h);)</div>
        <?php } ?>
        <h1><?php echo $data['l10n']->get('task'); ?>: &(view_task['title']:h);</h1>
  <div class="midcom_helper_datamanager2_view">
    <div class="field status <?php echo $task->status_type; ?>">
        <?php echo '<div class="title">' . $data['l10n']->get('task status') . ': </div>';
        echo '<div class="value">' . $data['l10n']->get($task->status_type) . '</div>';
        ?>
    </div>
    <div class="field">
        <?php echo '<div class="title">' . $data['l10n']->get('timeframe') . ': </div>';
        echo '<div class="value">' . $view_task['start'] . ' &ndash; ' . $view_task['end'] . '</div>';
        ?>
    </div>
    <?php
        if (   array_key_exists('priority', $data['datamanager']->types)
            && array_key_exists($task->priority, $data['datamanager']->types['priority']->options))
        {
            ?>
        <div class="field priority">
            <?php echo '<div class="title">' . $data['l10n']->get('priority') . ': </div>';
            echo '<div class="value">' . $data['l10n']->get($data['datamanager']->types['priority']->options[$task->priority]) . '</div>';
            ?>
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

        <div class="bookings">
            <?php
            echo "<h2>" . $data['l10n']->get('booked times') . "</h2>\n";
            if (count($data['task_bookings']['confirmed']) > 0)
            {
                echo "<ul>\n";
                foreach ($data['task_bookings']['confirmed'] as $booking)
                {
                    echo "<li>";
                    echo strftime('%x', $booking->start) . ' ' . date('H', $booking->start) . '-' . date('H', $booking->end);

                    if ($data['calendar_node'])
                    {
                        echo ": <a href=\"#\" onclick=\"" . org_openpsa_calendar_interface::calendar_editevent_js($booking->guid, $data['calendar_node']) . "\">{$booking->title}</a>";
                    }
                    else
                    {
                        echo ": {$booking->title}";
                    }

                    echo " (";
                    foreach ($booking->participants as $participant_id => $display)
                    {
                        $participant = org_openpsa_widgets_contact::get($participant_id);
                        echo $participant->show_inline();
                    }
                    echo ")</li>\n";
                }
                echo "</ul>\n";
            }

            if ($data['task_booked_percentage'] >= 105)
            {
                $status = 'acceptable';
            }
            else if ($data['task_booked_percentage'] >= 95)
            {
                $status = 'ok';
            }
            else if ($data['task_booked_percentage'] >= 75)
            {
                $status = 'acceptable';
            }
            else
            {
                $status = 'bad';
            }
            echo "<p class=\"{$status}\">" . sprintf($data['l10n']->get('%s of %s planned hours booked'), $data['task_booked_time'], $task->plannedHours) . ".\n";
            if ($task->resources)
            {
                echo "<a href=\"{$prefix}task/resourcing/{$task->guid}/\">" . $data['l10n']->get('schedule resources') . "</a>";
            }
            echo ".</p>\n";
            ?>
        </div>
        <div class="hours">
          <?php midcom::get()->dynamic_load($expenses_url . "hours/task/all/" . $task->guid . "/"); ?>
        </div>
    </div>

</div>
