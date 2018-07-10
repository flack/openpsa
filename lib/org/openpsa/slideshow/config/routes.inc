// Match /
'index' => [
    'handler' => ['org_openpsa_slideshow_handler_index', 'index'],
],
// Match /subfolders
'index_subfolders' => [
    'handler' => ['org_openpsa_slideshow_handler_index', 'subfolders'],
    'fixed_args' => 'subfolders',
],

// Match /edit/
'edit' => [
    'handler' => ['org_openpsa_slideshow_handler_edit', 'edit'],
    'fixed_args' => 'edit'
],

// Match /edit/ajax/
'edit_ajax' => [
    'handler' => ['org_openpsa_slideshow_handler_edit', 'edit_ajax'],
    'fixed_args' => ['edit', 'ajax']
],

// Match /recreate/
'recreate' => [
    'handler' => ['org_openpsa_slideshow_handler_edit', 'recreate'],
    'fixed_args' => 'recreate'
],

// Match /recreate_folder_thumbnails/
'recreate_folder_thumbnails' => [
    'handler' => ['org_openpsa_slideshow_handler_edit', 'recreate_folder_thumbnails'],
    'fixed_args' => 'recreate_folder_thumbnails'
],