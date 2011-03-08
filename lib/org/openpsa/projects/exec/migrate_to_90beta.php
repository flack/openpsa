<?php
if (!class_exists('org_openpsa_task_old'))
{
    throw new midcom_error('MgdSchemas for the converter could not be found');
}

include MIDCOM_ROOT . '/../tools/project_converter.php';

set_time_limit(50000);
ini_set('memory_limit', "800M");

while(@ob_end_flush());
echo "<pre>\n";

$qb = new midgard_query_builder('org_openpsa_task_old');
$qb->add_constraint('orgOpenpsaObtype', '=', 6000); //ORG_OPENPSA_OBTYPE_PROJECT
$projects = $qb->execute();

foreach ($projects as $project)
{
    $runner = new project_converter($project);
    $runner->execute();
}
echo "</pre>\n";
?>