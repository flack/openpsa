'history' => [
    'handler' => ['midcom_admin_rcs_handler_rcs', 'history'],
    'variable_args' => 1,
],

'preview' => [
    'fixed_args' => ['preview'],
    'handler' => ['midcom_admin_rcs_handler_rcs', 'preview'],
    'variable_args' => 2,
],
'diff' => [
    'fixed_args' => ['diff'],
    'handler' => ['midcom_admin_rcs_handler_rcs', 'diff'],
    'variable_args' => 3,
],
'restore' => [
    'fixed_args' => ['restore'],
    'handler' => ['midcom_admin_rcs_handler_rcs', 'restore'],
    'variable_args' => 2,
],
