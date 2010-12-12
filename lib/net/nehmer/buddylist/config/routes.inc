// Generic and personal welcome pages
'welcome' => Array
(
    'handler' => Array('net_nehmer_buddylist_handler_welcome', 'welcome'),
),

// Delete handler, only there for POST request data processing
'delete' => Array
(
    'handler' => Array('net_nehmer_buddylist_handler_delete', 'delete'),
    'fixed_args' => 'delete',
),

'pending_list' => Array
(
    'handler' => Array('net_nehmer_buddylist_handler_pending', 'list'),
    'fixed_args' => Array('pending', 'list'),
),
'pending_process' => Array
(
    'handler' => Array('net_nehmer_buddylist_handler_pending', 'process'),
    'fixed_args' => Array('pending', 'process'),
),

'request' => Array
(
    'handler' => Array('net_nehmer_buddylist_handler_request', 'request'),
    'fixed_args' => Array('request'),
    'variable_args' => 1,
),
