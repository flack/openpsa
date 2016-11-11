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

// Match /open/<filetype>/<object guid>
'open' => Array
(
    'handler' => Array('midcom_helper_imagepopup_handler_links', 'open'),
    'fixed_args' => Array('open'),
    'variable_args' => 2,
),

// Match /open/<filetype>
'open_noobject' => Array
(
    'handler' => Array('midcom_helper_imagepopup_handler_links', 'open'),
    'fixed_args' => Array('open'),
    'variable_args' => 1,
),

// Match /folder/<filetype>/<object guid>
'list_folder' => Array
(
    'handler' => Array('midcom_helper_imagepopup_handler_list', 'list'),
    'fixed_args' => Array('folder'),
    'variable_args' => 2,
),

// Match /folder/<filetype>
'list_folder_noobject' => Array
(
    'handler' => Array('midcom_helper_imagepopup_handler_list', 'list'),
    'fixed_args' => Array('folder'),
    'variable_args' => 1,
),

// Match /unified/<filetype>/<object guid>
'list_unified' => Array
(
    'handler' => Array('midcom_helper_imagepopup_handler_list', 'list'),
    'fixed_args' => Array('unified'),
    'variable_args' => 2,
),

// Match /unified/<filetype>
'list_unified_noobject' => Array
(
    'handler' => Array('midcom_helper_imagepopup_handler_list', 'list'),
    'fixed_args' => Array('unified'),
    'variable_args' => 1,
),

// Match /links/<filetype>/<object guid>
'list_links' => Array
(
    'handler' => Array('midcom_helper_imagepopup_handler_links', 'links'),
    'fixed_args' => Array('links'),
    'variable_args' => 2,
),

// Match /links/<filetype>
'list_links_noobject' => Array
(
    'handler' => Array('midcom_helper_imagepopup_handler_links', 'links'),
    'fixed_args' => Array('links'),
    'variable_args' => 1,
),

// Match /<filetype>/<object guid>
'list_object' => Array
(
    'handler' => Array('midcom_helper_imagepopup_handler_list', 'list'),
    'variable_args' => 2,
),
