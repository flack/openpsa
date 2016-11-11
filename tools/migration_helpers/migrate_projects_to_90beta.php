<?php
midcom::get()->auth->require_admin_user();
if (!class_exists('org_openpsa_task_old')) {
    throw new midcom_error('MgdSchemas for the converter could not be found');
}

include MIDCOM_ROOT . '/../tools/project_converter.php';

midcom::get()->disable_limits();

while (@ob_end_flush());
echo "<pre>\n";

$qb = new midgard_query_builder('org_openpsa_task_old');
$qb->add_constraint('orgOpenpsaObtype', '=', 6000); //ORG_OPENPSA_OBTYPE_PROJECT
$projects = $qb->execute();

foreach ($projects as $project) {
    $runner = new project_converter($project);
    $runner->execute();
}

/*
 * Salesprojects that do not have associated projects need to be processed separately. For the sake of
 * simplicity, we create a dummy project and feed them through the normal converter
 */
$qb = new midgard_query_builder('org_openpsa_salesproject_old');
$salesprojects = $qb->execute();

foreach ($salesprojects as $salesproject) {
    $project = new org_openpsa_task_old();
    $project->orgOpenpsaObtype = 6000;
    $project->create();
    $runner = new project_converter($project);
    $runner->set_salesproject($salesproject);
    $runner->execute();
}

echo "</pre>\n";
