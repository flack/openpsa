// Match /
'index' => array
(
    'handler' => array('org_openpsa_slideshow_handler_index', 'index'),
),
// Match /subfolders
'index_subfolders' => array
(
    'handler' => array('org_openpsa_slideshow_handler_index', 'subfolders'),
    'fixed_args' => 'subfolders',
),

// Match /edit/
'edit' => array
(
    'handler' => array('org_openpsa_slideshow_handler_edit', 'edit'),
    'fixed_args' => 'edit'
),

// Match /edit/ajax/
'edit_ajax' => array
(
    'handler' => array('org_openpsa_slideshow_handler_edit', 'edit_ajax'),
    'fixed_args' => array('edit', 'ajax')
),

// Match /recreate/
'recreate' => array
(
    'handler' => array('org_openpsa_slideshow_handler_edit', 'recreate'),
    'fixed_args' => 'recreate'
),

// Match /recreate_folder_thumbnails/
'recreate_folder_thumbnails' => array
(
    'handler' => array('org_openpsa_slideshow_handler_edit', 'recreate_folder_thumbnails'),
    'fixed_args' => 'recreate_folder_thumbnails'
),