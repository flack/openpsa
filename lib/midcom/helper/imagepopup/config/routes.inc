// Match /upload/image/<object guid>/
'upload_image' => [
    'handler' => ['midcom_helper_imagepopup_handler_upload', 'upload'],
    'fixed_args' => ['upload', 'image'],
    'variable_args' => 1,
],

// Match /upload/image/
'upload_image_noobject' => [
    'handler' => ['midcom_helper_imagepopup_handler_upload', 'upload'],
    'fixed_args' => ['upload', 'image'],
],

// Match /open/<filetype>/<object guid>
'open' => [
    'handler' => ['midcom_helper_imagepopup_handler_links', 'open'],
    'fixed_args' => ['open'],
    'variable_args' => 2,
],

// Match /open/<filetype>
'open_noobject' => [
    'handler' => ['midcom_helper_imagepopup_handler_links', 'open'],
    'fixed_args' => ['open'],
    'variable_args' => 1,
],

// Match /folder/<filetype>/<object guid>
'list_folder' => [
    'handler' => ['midcom_helper_imagepopup_handler_list', 'list'],
    'fixed_args' => ['folder'],
    'variable_args' => 2,
],

// Match /folder/<filetype>
'list_folder_noobject' => [
    'handler' => ['midcom_helper_imagepopup_handler_list', 'list'],
    'fixed_args' => ['folder'],
    'variable_args' => 1,
],

// Match /unified/<filetype>/<object guid>
'list_unified' => [
    'handler' => ['midcom_helper_imagepopup_handler_list', 'list'],
    'fixed_args' => ['unified'],
    'variable_args' => 2,
],

// Match /unified/<filetype>
'list_unified_noobject' => [
    'handler' => ['midcom_helper_imagepopup_handler_list', 'list'],
    'fixed_args' => ['unified'],
    'variable_args' => 1,
],

// Match /links/<filetype>/<object guid>
'list_links' => [
    'handler' => ['midcom_helper_imagepopup_handler_links', 'links'],
    'fixed_args' => ['links'],
    'variable_args' => 2,
],

// Match /links/<filetype>
'list_links_noobject' => [
    'handler' => ['midcom_helper_imagepopup_handler_links', 'links'],
    'fixed_args' => ['links'],
    'variable_args' => 1,
],

// Match /<filetype>/<object guid>
'list_object' => [
    'handler' => ['midcom_helper_imagepopup_handler_list', 'list'],
    'variable_args' => 2,
],
