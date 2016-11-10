<?php
$contactwidget = new org_openpsa_widgets_contact($data['person']);
?>
<div class="org_openpsa_projects_projectbroker_prospect">
    <?php
    echo $contactwidget->show();
    ?>
    <table class="slots">
        <thead>
            <tr>
                <th><?php echo midcom::get()->i18n->get_string('event before', 'org.openpsa.calendar'); ?></th>
                <th><?php echo midcom::get()->i18n->get_string('available slot', 'org.openpsa.calendar'); ?></th>
                <th><?php echo midcom::get()->i18n->get_string('event after', 'org.openpsa.calendar'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php
            foreach ($data['slots'] as $k => $slot) {
                $dom_id = "{$data['prospect']->guid}_{$slot['start']}";
                echo "<tr id=\"{$dom_id}_tr\">\n";

                echo "    <td class=\"previous\">\n";
                if ($slot['previous']) {
                    $event = new org_openpsa_widgets_calendar_event($slot['previous']);
                    echo $event->render();
                } else {
                    echo '        ' . midcom::get()->i18n->get_string('no event', 'org.openpsa.calendar') . "\n";
                }
                echo "    </td>\n";
                $post_name = "org_openpsa_projects_prospects[{$data['prospect']->guid}][{$k}]";
                echo "    <td class=\"slot\" id=\"{$dom_id}\">\n";
                echo "        <input type=\"hidden\" name=\"{$post_name}[start]\" id=\"{$dom_id}_start\" value=\"{$slot['start']}\" />\n";
                echo "        <input type=\"hidden\" name=\"{$post_name}[end]\" id=\"{$dom_id}_end\" value=\"{$slot['end']}\" />\n";
                echo "        <input type=\"checkbox\" class=\"crirHiddenJS\" id=\"{$dom_id}_checkbox\" onchange=\"project_prospects_slot_changed('{$dom_id}')\" name=\"{$post_name}[used]\" value=\"1\" />\n";
                echo "        <label for=\"{$dom_id}_checkbox\">\n";

                $event = new org_openpsa_widgets_calendar_event();
                $event->start = $slot['start'];
                $event->end = $slot['end'];
                echo $event->render_timelabel(true);

                echo "        </label>\n";
                echo "    </td>\n";

                echo "    <td class=\"next\">\n";
                if ($slot['next']) {
                    $event = new org_openpsa_widgets_calendar_event($slot['next']);
                    echo $event->render();
                } else {
                    echo '        ' . midcom::get()->i18n->get_string('no event', 'org.openpsa.calendar') . "\n";
                }
                echo "    </td>\n";

                echo "</tr>\n";
            }
            ?>
        </tbody>
    </table>
</div>