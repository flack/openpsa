'name' => 'org.openpsa.projects',
'purecode' => false,
'privileges' => array(),
'class_mapping' => array
(
    'org_openpsa_task' => 'org_openpsa_projects_task_dba',
    'org_openpsa_project' => 'org_openpsa_projects_project',
    'org_openpsa_task_resource' => 'org_openpsa_projects_task_resource_dba',
    'org_openpsa_task_status' => 'org_openpsa_projects_task_status_dba',
    'org_openpsa_hour_report' => 'org_openpsa_projects_hour_report_dba',
),
'version' => '9.1.0-dev',
'state' => 'stable',
'package.xml' => array
(
    'summary' => 'Project/Task management and hour reporting',
    'description' => 'Manage tasks and projects, report hours towards them.',
    'maintainers' => array
    (
        'bergie' => array
        (
            'name' => 'Henri Bergius',
            'email' => 'henri.bergius@iki.fi',
            'role' => 'lead',
            'active' => 'no',
        ),
        'rambo' => array
        (
            'name' => 'Eero af Heurlin',
            'email' => 'eero.afheurlin@iki.fi',
            'role' => 'lead',
        ),
        'flack' => array
        (
            'name' => 'Andreas Flack',
            'email' => 'flack@contentcontrol-berlin.de',
            'role' => 'developer',
        ),
    ),
    'dependencies' => array
    (
        'org.openpsa.core' => array(),
        'org.openpsa.helpers' => array(),
        'midcom.helper.datamanager2' => array(),
        'org.openpsa.widgets' => array(),
        'midcom' => array(),
    ),
),

