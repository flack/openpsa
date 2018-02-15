// Handle /hours/task/batch/
'hours_task_action' => array
(
    'handler' => array('org_openpsa_expenses_handler_hours_admin', 'batch'),
    'fixed_args' => array('hours', 'task', 'batch'),
),

// Handle /hours/task/<guid>
'list_hours_task' => array
(
    'handler' => array('org_openpsa_expenses_handler_hours_list', 'task'),
    'fixed_args' => array('hours', 'task'),
    'variable_args' => 1,
),

// Handle /hours/invoice/<guid>
'list_hours_invoice' => array
(
    'handler' => array('org_openpsa_expenses_handler_hours_list', 'invoice'),
    'fixed_args' => array('hours', 'invoice'),
    'variable_args' => 1,
),

// Handle /hours/
'list_hours' => array
(
    'handler' => array('org_openpsa_expenses_handler_hours_list', 'list'),
    'fixed_args' => array('hours'),
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

// Handle /hours/create/task/<schema>/<guid>
'hours_create_task' => array
(
    'handler' => array('org_openpsa_expenses_handler_hours_admin', 'create'),
    'fixed_args' => array('hours', 'create', 'task'),
    'variable_args' => 2,
),

// Handle /hours/create/invoice/<schema>/<guid>
'hours_create_invoice' => array
(
    'handler' => array('org_openpsa_expenses_handler_hours_admin', 'create'),
    'fixed_args' => array('hours', 'create', 'invoice'),
    'variable_args' => 2,
),

// Handle /csv/<type>
'csv' => array
(
    'handler' => array('org_openpsa_expenses_handler_csv', 'csv'),
    'fixed_args' => 'csv',
    'variable_args' => 1,
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
