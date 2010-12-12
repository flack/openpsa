/**
 * Basic functionalities such as creation, editing and deleting
 * topic objects.
 */
/**
 * Create a new topic
 *
 * Match /create/
 */
'create' => array
(
    'handler' => array('midcom_admin_folder_handler_edit', 'edit'),
    'fixed_args' => array ('create'),
),

/**
 * Edit a topic
 *
 * Match /edit/
 */
'edit' => array
(
    'handler' => array('midcom_admin_folder_handler_edit', 'edit'),
    'fixed_args' => array ('edit'),
),

/**
 * Delete a topic
 *
 * Match /delete/
 */
'delete' => array
(
    'handler' => array('midcom_admin_folder_handler_delete', 'delete'),
    'fixed_args' => array ('delete'),
),

/**
 * Approval pseudo locations, which redirect back to the original page
 * after saving the new status.
 */
/**
 * Approve a topic object
 *
 * Match /metadata/approve/
 */
'approve' => array
(
    'handler' => array('midcom_admin_folder_handler_approvals', 'approval'),
    'fixed_args' => array ('approve'),
),

/**
 * Unapprove a topic object
 *
 * Match /metadata/unapprove/
 */
'unapprove' => array
(
    'handler' => array('midcom_admin_folder_handler_approvals', 'approval'),
    'fixed_args' => array ('unapprove'),
),

/**
 * Miscellaneous other functionalities
 */
/**
 * Metadata editing
 *
 * Match /metadata/<object guid>/
 */
'metadata' => array
(
    'handler' => array('midcom_admin_folder_handler_metadata', 'metadata'),
    'fixed_args' => array ('metadata'),
    'variable_args' => 1,
),

/**
 * Object moving
 *
 * Match /move/<object guid>/
 */
'move' => array
(
    'handler' => array('midcom_admin_folder_handler_move', 'move'),
    'fixed_args' => array ('move'),
    'variable_args' => 1,
),

// Match /order/
'order' => array
(
    'handler' => array('midcom_admin_folder_handler_order', 'order'),
    'fixed_args' => array ('order'),
),
