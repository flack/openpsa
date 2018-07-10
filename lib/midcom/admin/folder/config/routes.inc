/**
 * Basic functionalities such as creation, editing and deleting
 * topic objects.
 */
/**
 * Create a new topic
 *
 * Match /create/
 */
'create' => [
    'handler' => ['midcom_admin_folder_handler_edit', 'edit'],
    'fixed_args' => ['create'],
],

/**
 * Edit a topic
 *
 * Match /edit/
 */
'edit' => [
    'handler' => ['midcom_admin_folder_handler_edit', 'edit'],
    'fixed_args' => ['edit'],
],

/**
 * Delete a topic
 *
 * Match /delete/
 */
'delete' => [
    'handler' => ['midcom_admin_folder_handler_delete', 'delete'],
    'fixed_args' => ['delete'],
],

/**
 * Approval pseudo locations, which redirect back to the original page
 * after saving the new status.
 */
/**
 * Approve a topic object
 *
 * Match /metadata/approve/
 */
'approve' => [
    'handler' => ['midcom_admin_folder_handler_approvals', 'approval'],
    'fixed_args' => ['approve'],
],

/**
 * Unapprove a topic object
 *
 * Match /metadata/unapprove/
 */
'unapprove' => [
    'handler' => ['midcom_admin_folder_handler_approvals', 'approval'],
    'fixed_args' => ['unapprove'],
],

/**
 * Miscellaneous other functionalities
 */
/**
 * Metadata editing
 *
 * Match /metadata/<object guid>/
 */
'metadata' => [
    'handler' => ['midcom_admin_folder_handler_metadata', 'metadata'],
    'fixed_args' => ['metadata'],
    'variable_args' => 1,
],

/**
 * Object moving
 *
 * Match /move/<object guid>/
 */
'move' => [
    'handler' => ['midcom_admin_folder_handler_move', 'move'],
    'fixed_args' => ['move'],
    'variable_args' => 1,
],

// Match /order/
'order' => [
    'handler' => ['midcom_admin_folder_handler_order', 'order'],
    'fixed_args' => ['order'],
],
