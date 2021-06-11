<?php
$qb = org_openpsa_projects_role_dba::new_query_builder();
foreach ($qb->execute() as $role) {
    try {
        $project = new org_openpsa_projects_project($role->objectGuid);
    } catch (Exception $e) {
        echo $e->getMessage() . "\n";
        continue;
    }
    $role->project = $project->id;
    $role->update();
}
?>