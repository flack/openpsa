// Match /config/
'config' => array
(
    'handler' => array('midcom_baseclasses_components_handler_configuration', 'config'),
    'fixed_args' => 'config',
),

// Match /create/
'create' => array
(
    'handler' => array('net_nemein_redirector_handler_tinyurl', 'create'),
    'fixed_args' => 'create',
),

// Match /edit/{$tinyurl}/
'edit' => array
(
    'handler' => array('net_nemein_redirector_handler_tinyurl', 'edit'),
    'fixed_args' => 'edit',
    'variable_args' => 1,
),

// Match /delete/{$tinyurl}/
'delete' => array
(
    'handler' => array('net_nemein_redirector_handler_tinyurl', 'delete'),
    'fixed_args' => 'delete',
    'variable_args' => 1,
),

// Match /{$tinyurl}/
'tinyurl' => array
(
    'handler' => array('net_nemein_redirector_handler_redirect', 'redirect'),
    'variable_args' => 1,
),
