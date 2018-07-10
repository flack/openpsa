// Match /project/edit/<GUID>
'project-edit' => [
    'handler' => ['org_openpsa_projects_handler_project_crud', 'update'],
    'fixed_args' => ['project', 'edit'],
    'variable_args' => 1,
],
// Match /project/new
'project-new' => [
    'handler' => ['org_openpsa_projects_handler_project_crud', 'create'],
    'fixed_args' => ['project', 'new'],
],
// Match /project/<GUID>
'project' => [
    'handler' => ['org_openpsa_projects_handler_project_view', 'read'],
    'fixed_args' => 'project',
    'variable_args' => 1,
],

// Match /task/list/agreement/<id>
'task-list-agreement' => [
    'handler' => ['org_openpsa_projects_handler_task_list_agreement', 'list'],
    'fixed_args' => ['task', 'list', 'agreement'],
    'variable_args' => 1,
],
// Match /task/list/json/<guid>
'task-list-json' => [
    'handler' => ['org_openpsa_projects_handler_task_list_project', 'json'],
    'fixed_args' => ['task', 'list', 'json'],
    'variable_args' => 1,
],
// Match /task/list/project/<guid>
'task-list-project' => [
    'handler' => ['org_openpsa_projects_handler_task_list_project', 'list'],
    'fixed_args' => ['task', 'list', 'project'],
    'variable_args' => 1,
],
// Match /task/list/task/<guid>
'task-list-subtask' => [
    'handler' => ['org_openpsa_projects_handler_task_list_task', 'list'],
    'fixed_args' => ['task', 'list', 'task'],
    'variable_args' => 1,
],
// Match /task/list/<status>
'task-list' => [
    'handler' => ['org_openpsa_projects_handler_task_list', 'list'],
    'fixed_args' => ['task', 'list'],
    'variable_args' => 1,
],
// Match /task/list/
'task-list-user' => [
    'handler' => ['org_openpsa_projects_handler_task_list_user', 'list'],
    'fixed_args' => ['task', 'list'],
],

// Match /task/edit/<GUID>
'task_edit' => [
    'handler' => ['org_openpsa_projects_handler_task_crud', 'update'],
    'fixed_args' => ['task', 'edit'],
    'variable_args' => 1,
],
// Match /task/delete/<GUID>
'task_delete' => [
    'handler' => ['org_openpsa_projects_handler_task_crud', 'delete'],
    'fixed_args' => ['task', 'delete'],
    'variable_args' => 1,
],

// Match /task/resourcing/<GUID>
'task_resourcing' => [
    'handler' => ['org_openpsa_projects_handler_task_resourcing', 'resourcing'],
    'fixed_args' => ['task', 'resourcing'],
    'variable_args' => 1,
],
// Match /task/resourcing/prospects/<GUID>
'task_resourcing_prospects' => [
    'handler' => ['org_openpsa_projects_handler_task_resourcing', 'list_prospects'],
    'fixed_args' => ['task', 'resourcing', 'prospects'],
    'variable_args' => 1,
],
// Match /task/resourcing/prospect/<GUID>
'task_resourcing_prospect_slots' => [
    'handler' => ['org_openpsa_projects_handler_task_resourcing', 'prospect_slots'],
    'fixed_args' => ['task', 'resourcing', 'prospect'],
    'variable_args' => 1,
],

// Match /task/new
'task-new' => [
    'handler' => ['org_openpsa_projects_handler_task_crud', 'create'],
    'fixed_args' => ['task', 'new'],
],
// Match /task/new/<Target type>/<Target GUID>
'task-new-2' => [
    'handler' => ['org_openpsa_projects_handler_task_crud', 'create'],
    'fixed_args' => ['task', 'new'],
    'variable_args' => 2,
],
// Match /task/<GUID>
'task_view' => [
    'handler' => ['org_openpsa_projects_handler_task_view', 'read'],
    'fixed_args' => 'task',
    'variable_args' => 1,
],

// Match /workflow/<GUID>/<action>
'workflow-action' => [
    'handler' => ['org_openpsa_projects_handler_workflow', 'action'],
    'fixed_args' => 'workflow',
    'variable_args' => 2,
],
// Match /workflow/<GUID>
'workflow' => [
    'handler' => ['org_openpsa_projects_handler_workflow', 'post'],
    'fixed_args' => 'workflow',
    'variable_args' => 1,
],

// Match /
'frontpage' => [
    'handler' => ['org_openpsa_projects_handler_frontpage', 'frontpage'],
],
