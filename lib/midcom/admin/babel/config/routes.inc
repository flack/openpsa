'select' => array
(
    'handler' => Array('midcom_admin_babel_handler_process', 'select'),
),
'status' => array
(
    'handler' => Array('midcom_admin_babel_handler_process', 'status'),
    'fixed_args' => 'status',
    'variable_args' => 1,
),
'edit' => array
(
    'handler' => Array('midcom_admin_babel_handler_process', 'edit'),
    'fixed_args' => 'edit',
    'variable_args' => 2,
),
'save' => array
(
    'handler' => Array('midcom_admin_babel_handler_process', 'save'),
    'fixed_args' => 'save',
    'variable_args' => 2,
),
