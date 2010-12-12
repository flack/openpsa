// Match /folder/<schema>/<object guid>
'list_folder' => Array
(
    'handler' => Array('midcom_helper_imagepopup_handler_list', 'list'),
    'fixed_args' => Array('folder'),
    'variable_args' => 2,
),

// Match /folder/<schema>
'list_folder_noobject' => Array
(
    'handler' => Array('midcom_helper_imagepopup_handler_list', 'list'),
    'fixed_args' => Array('folder'),
    'variable_args' => 1,
),

// Match /unified/<schema>/<object guid>
'list_unified' => Array
(
    'handler' => Array('midcom_helper_imagepopup_handler_list', 'list'),
    'fixed_args' => Array('unified'),
    'variable_args' => 2,
),

// Match /unified/<schema>
'list_unified_noobject' => Array
(
    'handler' => Array('midcom_helper_imagepopup_handler_list', 'list'),
    'fixed_args' => Array('unified'),
    'variable_args' => 1,
),

// Match /<schema>/<object guid>
'list_object' => Array
(
    'handler' => Array('midcom_helper_imagepopup_handler_list', 'list'),
    'variable_args' => 2,
),
