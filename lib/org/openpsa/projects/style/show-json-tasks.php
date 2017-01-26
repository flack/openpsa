<?php
$task_array = array();
$tasks = $data['provider']->get_rows();
$formatter = $data['l10n']->get_formatter();

foreach ($tasks as $task) {
    $task_array[] = array(
        'title' => $task['task'],
        'priority' => $task['priority'],
        'priority_title' => $data['priority_array'][$task['index_priority']],
        'planned_hours' => $task['planned_hours'],
        'approved_hours' => $task['approved_hours'],
        'reported_hours' => $task['reported_hours'],
        'start' => $formatter->date(strtotime($task['start'])),
        'end' => $formatter->date(strtotime($task['end'])),
    );
}

echo json_encode($task_array);
