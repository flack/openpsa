<?php
$task_array = array();

$tasks = $data['provider']->get_rows();

foreach ($tasks as $task)
{
    $task_array[] = array
    (
        'title' => $task['task'],
        'priority' => $task['priority'],
        'priority_title' => $data['priority_array'][$task['index_priority']],
        'planned_hours' => $task['planned_hours'],
        'approved_hours' => $task['approved_hours'],
        'reported_hours' => $task['reported_hours'],
        'start' => date("d.m.Y", strtotime($task['start'])),
        'end' => date("d.m.Y", strtotime($task['end'])),
    );
}

echo json_encode($task_array);
?>
