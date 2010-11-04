<?php
$prefix = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX);

$date_totals = array();

// Header line
echo "<table class='expenses'>\n";

$time = $data['week_start'];
echo "  <thead>\n";
echo "    <tr>\n";
echo "        <th></th>\n";
while ($time < $data['week_end'])
{
    $next_time = $time + 3600 * 24;
    echo "        <th><a href=\"{$prefix}hours/between/" . date('Y-m-d', $time) . "/" .  date('Y-m-d', $next_time) . "/\">" . strftime('%a', $time) . "</a></th>\n";

    // Hop to next day
    $time = $next_time;
}
echo "    </tr>\n";
echo "  </thead>\n";
$class = "even";
foreach ($data['tasks'] as $task => $days)
{
    $task =& $days['task_object'];
    $time = $data['week_start'];

    if ($class == "even")
    {
        $class = "";
    }
    else
    {
        $class = "even";
    }
    echo "    <tr class='{$class}'>\n";

    if (   !$task
        || !$task->guid)
    {
        echo "        <th>" . $data['l10n']->get('no task') . "</th>";
    }
    else
    {
        echo "        <th><a href=\"{$prefix}hours/task/{$task->guid}/\">" . $task->get_label() . "</a></th>";
    }
    while ($time < $data['week_end'])
    {
        $date_identifier = date('Y-m-d', $time);
        if (!isset($days[$date_identifier]))
        {
            echo "<th></th>\n";
        }
        else
        {
            $hours_total = $days[$date_identifier];

            if (!isset($date_totals[$date_identifier]))
            {
                $date_totals[$date_identifier] = 0;
            }
            $date_totals[$date_identifier] += $hours_total;

            echo "        <th class='numeric'>" . round($hours_total, 1) . "</th>\n";
        }
        // Hop to next day
        $time = $time + 3600 * 24;
    }
    echo "    </tr>\n";
    if (sizeof($days['persons']) < 1)
    {
        continue;
    }

    foreach ($days['persons'] as $person => $person_hours)
    {
        $person = org_openpsa_contacts_person_dba::get_cached($person);
        $time = $data['week_start'];

        if ($class == "even")
        {
            $class = "";
        }
        else
        {
            $class = "even";
        }
        echo "    <tr class='{$class}'>\n";

        if (   !$person
            || !$person->guid)
        {
            echo "        <td class='person'>" . $data['l10n']->get('no person') . "</td>";
        }
        else
        {
            echo "        <td class='person'>" . $person->name . "</td>";
        }
        while ($time < $data['week_end'])
        {
            $date_identifier = date('Y-m-d', $time);
            if (!isset($person_hours[$date_identifier]))
            {
                echo "<td></td>\n";
            }
            else
            {
                $hours_total = $person_hours[$date_identifier];

                echo "        <td class='numeric'>" . round($hours_total, 1) . "</td>\n";
            }
            // Hop to next day
            $time = $time + 3600 * 24;
        }
        echo "    </tr>\n";
    }
}

$time = $data['week_start'];
echo "    <tr class=\"totals\">\n";
echo "        <td></td>\n";
while ($time < $data['week_end'])
{
    $date_identifier = date('Y-m-d', $time);

    if (!isset($date_totals[$date_identifier]))
    {
        echo "<th class='numeric'>0</th>\n";
    }
    else
    {
        echo "        <th class='numeric'>" . round($date_totals[$date_identifier], 1) . "</th>\n";
    }
    // Hop to next day
    $time = $time + 3600 * 24;
}
echo "    </tr>\n";
echo "</table>\n";
?>