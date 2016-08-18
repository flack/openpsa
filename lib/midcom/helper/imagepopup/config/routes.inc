// Match /upload/image/<object guid>/
'upload_image' => Array
(
    'handler' => Array('midcom_helper_imagepopup_handler_upload', 'upload'),
    'fixed_args' => Array('upload', 'image'),
    'variable_args' => 1,
),

// Match /upload/image/
'upload_image_noobject' => Array
(
    'handler' => Array('midcom_helper_imagepopup_handler_upload', 'upload'),
    'fixed_args' => Array('upload', 'image'),
),

// Match /open/<schema>/<filetype>/<object guid>
'open' => Array
(
    'handler' => Array('midcom_helper_imagepopup_handler_links', 'open'),
    'fixed_args' => Array('open'),
    'variable_args' => 3,
),

// Match /open/<schema>/<filetype>
'open_noobject' => Array
(
    'handler' => Array('midcom_helper_imagepopup_handler_links', 'open'),
    'fixed_args' => Array('open'),
    'variable_args' => 2,
),

// Match /folder/<schema>/<filetype>/<object guid>
'list_folder' => Array
(
    'handler' => Array('midcom_helper_imagepopup_handler_list', 'list'),
    'fixed_args' => Array('folder'),
    'variable_args' => 3,
),

// Match /folder/<schema>/<filetype>
'list_folder_noobject' => Array
(
    'handler' => Array('midcom_helper_imagepopup_handler_list', 'list'),
    'fixed_args' => Array('folder'),
    'variable_args' => 2,
),

// Match /unified/<schema>/<filetype>/<object guid>
'list_unified' => Array
(
    'handler' => Array('midcom_helper_imagepopup_handler_list', 'list'),
    'fixed_args' => Array('unified'),
    'variable_args' => 3,
),

// Match /unified/<schema>/<filetype>
'list_unified_noobject' => Array
(
    'handler' => Array('midcom_helper_imagepopup_handler_list', 'list'),
    'fixed_args' => Array('unified'),
    'variable_args' => 2,
),

// Match /links/<schema>/<filetype>/<object guid>
'list_links' => Array
(
    'handler' => Array('midcom_helper_imagepopup_handler_links', 'links'),
    'fixed_args' => Array('links'),
    'variable_args' => 3,
),

// Match /links/<schema>/<filetype>
'list_links_noobject' => Array
(
    'handler' => Array('midcom_helper_imagepopup_handler_links', 'links'),
    'fixed_args' => Array('links'),
    'variable_args' => 2,
),

// Match /<schema>/<filetype>/<object guid>
'list_object' => Array
(
    'handler' => Array('midcom_helper_imagepopup_handler_list', 'list'),
    'variable_args' => 3,
),
