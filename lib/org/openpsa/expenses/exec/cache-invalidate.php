<?php
midcom::get()->auth->require_admin_user();
midcom::get()->disable_limits();
ob_implicit_flush();
echo "<h1>Invalidating task caches:</h1>\n";

$qb = org_openpsa_projects_task_dba::new_query_builder();

foreach ($qb->execute() as $task) {
    $start = microtime(true);
    echo "Invalidating cache for task #{$task->id} {$task->title}... \n";
    if ($task->update_cache()) {
        $time_consumed = round(microtime(true) - $start, 2);
        echo "OK ({$time_consumed} secs, task has {$task->reportedHours}h reported)";
    } else {
        echo "ERROR: " . midcom_connection::get_error_string();
    }
    echo "<br />\n";
}
?>
<p>All done</p>
