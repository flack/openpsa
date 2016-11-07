// Match /project/edit/<GUID>
'project-edit' => array
(
    'handler' => array('org_openpsa_projects_handler_project_crud', 'update'),
    'fixed_args' => array('project', 'edit'),
    'variable_args' => 1,
),
// Match /project/new
'project-new' => array
(
    'handler' => array('org_openpsa_projects_handler_project_crud', 'create'),
    'fixed_args' => array('project', 'new'),
),
// Match /project/<GUID>
'project' => array
(
    'handler' => array('org_openpsa_projects_handler_project_crud', 'read'),
    'fixed_args' => 'project',
    'variable_args' => 1,
),

// Match /task/list/<mode>/<param>/<param2>
'task-list-3' => array
(
    'handler' => array('org_openpsa_projects_handler_task_list', 'list'),
    'fixed_args' => array('task', 'list'),
    'variable_args' => 3,
),
// Match /task/list/<mode>/<param>
'task-list-2' => array
(
    'handler' => array('org_openpsa_projects_handler_task_list', 'list'),
    'fixed_args' => array('task', 'list'),
    'variable_args' => 2,
),
// Match /task/list/<mode>
'task-list-1' => array
(
    'handler' => array('org_openpsa_projects_handler_task_list', 'list'),
    'fixed_args' => array('task', 'list'),
    'variable_args' => 1,
),
// Match /task/list/
'task-list-user' => array
(
    'handler' => array('org_openpsa_projects_handler_task_list', 'list_user'),
    'fixed_args' => array('task', 'list'),
),

// Match /task/edit/<GUID>
'task_edit' => array
(
    'handler' => array('org_openpsa_projects_handler_task_crud', 'update'),
    'fixed_args' => array('task', 'edit'),
    'variable_args' => 1,
),
// Match /task/delete/<GUID>
'task_delete' => array
(
    'handler' => array('org_openpsa_projects_handler_task_crud', 'delete'),
    'fixed_args' => array('task', 'delete'),
    'variable_args' => 1,
),

// Match /task/resourcing/<GUID>
'task_resourcing' => array
(
    'handler' => array('org_openpsa_projects_handler_task_resourcing', 'resourcing'),
    'fixed_args' => array('task', 'resourcing'),
    'variable_args' => 1,
),
// Match /task/resourcing/prospects/<GUID>
'task_resourcing_prospects' => array
(
    'handler' => array('org_openpsa_projects_handler_task_resourcing', 'list_prospects'),
    'fixed_args' => array('task', 'resourcing', 'prospects'),
    'variable_args' => 1,
),
// Match /task/resourcing/prospect/<GUID>
'task_resourcing_prospect_slots' => array
(
    'handler' => array('org_openpsa_projects_handler_task_resourcing', 'prospect_slots'),
    'fixed_args' => array('task', 'resourcing', 'prospect'),
    'variable_args' => 1,
),

// Match /task/new
'task-new' => array
(
    'handler' => array('org_openpsa_projects_handler_task_crud', 'create'),
    'fixed_args' => array('task', 'new'),
),
// Match /task/new/<Target type>/<Target GUID>
'task-new-2' => array
(
    'handler' => array('org_openpsa_projects_handler_task_crud', 'create'),
    'fixed_args' => array('task', 'new'),
    'variable_args' => 2,
),
// Match /task/<GUID>
'task_view' => array
(
    'handler' => array('org_openpsa_projects_handler_task_crud', 'read'),
    'fixed_args' => 'task',
    'variable_args' => 1,
),

// Match /workflow/<GUID>/<action>
'workflow-action' => array
(
    'handler' => array('org_openpsa_projects_handler_workflow', 'action'),
    'fixed_args' => 'workflow',
    'variable_args' => 2,
),
// Match /workflow/<GUID>
'workflow' => array
(
    'handler' => array('org_openpsa_projects_handler_workflow', 'post'),
    'fixed_args' => 'workflow',
    'variable_args' => 1,
),

// Match /
'frontpage' => array
(
    'handler' => array('org_openpsa_projects_handler_frontpage', 'frontpage'),
),
