// Administrative stuff
'edit' => array
(
    'handler' => array('net_nehmer_static_handler_admin', 'edit'),
    'fixed_args' => 'edit',
    'variable_args' => 1,
),
'delete' => array
(
    'handler' => array('net_nehmer_static_handler_admin', 'delete'),
    'fixed_args' => 'delete',
    'variable_args' => 1,
),
'create' => array
(
    'handler' => array('net_nehmer_static_handler_create', 'create'),
    'fixed_args' => 'create',
    'variable_args' => 1,
),
'createindex' => array
(
    'handler' => array('net_nehmer_static_handler_create', 'create'),
    'fixed_args' => 'createindex',
    'variable_args' => 1,
),
'config' => array
(
    'handler' => array('midcom_baseclasses_components_handler_configuration', 'config'),
    'fixed_args' => 'config',
),
// AJAX version of view, which skips style.
'view_raw' => array
(
    'handler' => array('net_nehmer_static_handler_view', 'view'),
    'fixed_args' => 'raw',
    'variable_args' => 1,
),

'view' => array
(
    'handler' => array('net_nehmer_static_handler_view', 'view'),
    'variable_args' => 1,
),
