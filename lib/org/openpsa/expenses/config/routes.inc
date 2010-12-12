// Handle /hours/task/batch/
'hours_task_action' => array
(
    'handler' => array('org_openpsa_expenses_handler_hours_admin', 'batch'),
    'fixed_args' => array('hours', 'task', 'batch'),
),

// Handle /hours/task/<guid>
'list_hours_task' => array
(
    'handler' => array('org_openpsa_expenses_handler_hours_list', 'list'),
    'fixed_args' => array('hours', 'task'),
    'variable_args' => 1,
),

// Handle /hours/task/all/<guid>
'list_hours_task_all' => array
(
    'handler' => array('org_openpsa_expenses_handler_hours_list', 'list'),
    'fixed_args' => array('hours', 'task', 'all'),
    'variable_args' => 1,
),

// Handle /hours/between/<from>/<to>
'list_hours_between' => array
(
    'handler' => array('org_openpsa_expenses_handler_hours_list', 'list'),
    'fixed_args' => array('hours', 'between'),
    'variable_args' => 2,
),

// Handle /hours/between/all/<from>/<to>
'list_hours_between_all' => array
(
    'handler' => array('org_openpsa_expenses_handler_hours_list', 'list'),
    'fixed_args' => array('hours', 'between', 'all'),
    'variable_args' => 2,
),

// Handle /hours/edit/<guid>
'hours_edit' => array
(
    'handler' => array('org_openpsa_expenses_handler_hours_admin', 'edit'),
    'fixed_args' => array('hours', 'edit'),
    'variable_args' => 1,
),

// Handle /hours/delete/<guid>
'hours_delete' => array
(
    'handler' => array('org_openpsa_expenses_handler_hours_admin', 'delete'),
    'fixed_args' => array('hours', 'delete'),
    'variable_args' => 1,
),

// Handle /hours/create/<schema>
'hours_create' => array
(
    'handler' => array('org_openpsa_expenses_handler_hours_admin', 'create'),
    'fixed_args' => array('hours', 'create'),
    'variable_args' => 1,
),

// Handle /hours/create/<schema>/<task>
'hours_create_task' => array
(
    'handler' => array('org_openpsa_expenses_handler_hours_admin', 'create'),
    'fixed_args' => array('hours', 'create'),
    'variable_args' => 2,
),

// Handle /<timestamp>
'index_timestamp' => array
(
    'handler' => Array('org_openpsa_expenses_handler_index', 'index'),
    'variable_args' => 1,
),
// Handle /
'index' => array
(
    'handler' => Array('org_openpsa_expenses_handler_index', 'index'),
),
