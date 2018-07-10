'name' => 'org.openpsa.projects',
'icon' => 'tasks',
'purecode' => false,
'privileges' => array(),
'class_mapping' => array
(
    'org_openpsa_task' => 'org_openpsa_projects_task_dba',
    'org_openpsa_project' => 'org_openpsa_projects_project',
    'org_openpsa_task_resource' => 'org_openpsa_projects_task_resource_dba',
    'org_openpsa_task_status' => 'org_openpsa_projects_task_status_dba',
),
'description' => 'Manage tasks and projects, report hours towards them.',