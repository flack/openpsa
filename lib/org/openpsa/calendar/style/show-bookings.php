<?php
$task = $data['task'];
$task->get_members();
$formatter = $data['l10n']->get_formatter();
$nap = new midcom_helper_nav();
$node = $nap->get_node($nap->get_current_node());
$projects_url = org_openpsa_core_siteconfig::get_instance()->get_node_full_url('org.openpsa.projects');
?>
<div class="bookings">
    <?php
    echo "<h2>" . $data['l10n']->get('booked times') . "</h2>\n";
    if (!empty($data['bookings']['confirmed'])) {
        echo "<ul>\n";
        foreach ($data['bookings']['confirmed'] as $booking) {
            echo "<li>";
            echo $formatter->timeframe($booking->start, $booking->end) . ': ';

            echo "<a " . org_openpsa_calendar_interface::get_viewer_attributes($booking->guid, $node) . ">{$booking->title}</a>";

            echo " (";
            foreach ($booking->participants as $participant_id => $display) {
                $participant = org_openpsa_widgets_contact::get($participant_id);
                echo $participant->show_inline();
            }
            echo ")</li>\n";
        }
        echo "</ul>\n";
    }

    $delta = abs(100 - $data['booked_percentage']);
    if ($delta <= 5) {
        $status = 'ok';
    } elseif ($delta <= 25) {
        $status = 'acceptable';
    } else {
        $status = 'bad';
    }

    echo "<p class=\"{$status}\">" . sprintf($data['l10n']->get('%s of %s planned hours booked'), $data['booked_time'], $task->plannedHours) . ".\n";
    if ($task->resources) {
        echo "<a href=\"{$projects_url}task/resourcing/{$task->guid}/\">" . $data['l10n']->get('schedule resources') . "</a>";
    }
    echo "</p>\n";
    ?>
</div>