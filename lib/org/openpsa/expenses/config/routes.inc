// Handle /hours/task/batch/
'hours_task_action' => [
    'handler' => ['org_openpsa_expenses_handler_hours_admin', 'batch'],
    'fixed_args' => ['hours', 'task', 'batch'],
],

// Handle /hours/task/<guid>
'list_hours_task' => [
    'handler' => ['org_openpsa_expenses_handler_hours_list', 'task'],
    'fixed_args' => ['hours', 'task'],
    'variable_args' => 1,
],

// Handle /hours/invoice/<guid>
'list_hours_invoice' => [
    'handler' => ['org_openpsa_expenses_handler_hours_list', 'invoice'],
    'fixed_args' => ['hours', 'invoice'],
    'variable_args' => 1,
],

// Handle /hours/
'list_hours' => [
    'handler' => ['org_openpsa_expenses_handler_hours_list', 'list'],
    'fixed_args' => ['hours'],
],

// Handle /hours/edit/<guid>
'hours_edit' => [
    'handler' => ['org_openpsa_expenses_handler_hours_admin', 'edit'],
    'fixed_args' => ['hours', 'edit'],
    'variable_args' => 1,
],

// Handle /hours/delete/<guid>
'hours_delete' => [
    'handler' => ['org_openpsa_expenses_handler_hours_admin', 'delete'],
    'fixed_args' => ['hours', 'delete'],
    'variable_args' => 1,
],

// Handle /hours/create/<schema>
'hours_create' => [
    'handler' => ['org_openpsa_expenses_handler_hours_admin', 'create'],
    'fixed_args' => ['hours', 'create'],
    'variable_args' => 1,
],

// Handle /hours/create/task/<schema>/<guid>
'hours_create_task' => [
    'handler' => ['org_openpsa_expenses_handler_hours_admin', 'create'],
    'fixed_args' => ['hours', 'create', 'task'],
    'variable_args' => 2,
],

// Handle /hours/create/invoice/<schema>/<guid>
'hours_create_invoice' => [
    'handler' => ['org_openpsa_expenses_handler_hours_admin', 'create'],
    'fixed_args' => ['hours', 'create', 'invoice'],
    'variable_args' => 2,
],

// Handle /csv/<type>
'csv' => [
    'handler' => ['org_openpsa_expenses_handler_csv', 'csv'],
    'fixed_args' => 'csv',
    'variable_args' => 1,
],

// Handle /<timestamp>
'index_timestamp' => [
    'handler' => ['org_openpsa_expenses_handler_index', 'index'],
    'variable_args' => 1,
],
// Handle /
'index' => [
    'handler' => ['org_openpsa_expenses_handler_index', 'index'],
],
