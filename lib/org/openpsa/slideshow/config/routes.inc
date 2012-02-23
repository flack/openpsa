// Match /
'index' => array
(
    'handler' => array('org_openpsa_slideshow_handler_index', 'index'),
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