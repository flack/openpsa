/**
 * List users
 *
 * Match /user-editor/
 */
'user_list' => array
(
    'handler' => array('midcom_admin_user_handler_list', 'list'),
),
/**
 * Edit a user
 *
 * Match /user-editor/edit/<guid>/
 */
'user_edit' => array
(
    'handler' => array('midcom_admin_user_handler_user_edit', 'edit'),
    'fixed_args' => 'edit',
    'variable_args' => 1,
),
/**
 * Generate random passwords
 *
 * Match /user-editor/password/
 */
'user_passwords' => array
(
    'handler' => array('midcom_admin_user_handler_user_edit', 'passwords'),
    'fixed_args' => 'password',
),
/**
 * Generate random passwords
 *
 * Match /user-editor/password/email/
 */
'user_passwords_batch' => array
(
    'handler' => array('midcom_admin_user_handler_user_edit', 'batch'),
    'fixed_args' => array('password', 'batch'),
),
/**
 * Edit a user's account
 *
 * Match /user-editor/account/<guid>/
 */
'user_edit_account' => array
(
    'handler' => array('midcom_admin_user_handler_user_edit', 'edit_account'),
    'fixed_args' => 'account',
    'variable_args' => 1,
),
/**
 * Create new user
 *
 * Match /create/
 */
'user_create' => array
(
    'handler' => array('midcom_admin_user_handler_user_create', 'create'),
    'fixed_args' => 'create',
),
/**
 * List groups
 *
 * Match /user-editor/group/
 */
'group_list' => array
(
    'handler' => array('midcom_admin_user_handler_group_list', 'list'),
    'fixed_args' => 'group',
),
/**
 * Move a group
 *
 * Match /user-editor/group/
 */
'group_move' => array
(
    'handler' => array('midcom_admin_user_handler_group_list', 'move'),
    'fixed_args' => array('group', 'move'),
    'variable_args' => 1,
),
/**
 * Edit a group
 *
 * Match /user-editor/group/edit/<guid>/
 */
'group_edit' => array
(
    'handler' => array('midcom_admin_user_handler_group_edit', 'edit'),
    'fixed_args' => array('group', 'edit'),
    'variable_args' => 1,
),
/**
 * List folders group has privileges to
 *
 * Match /user-editor/group/folders/<guid>/
 */
'group_folders' => array
(
    'handler' => array('midcom_admin_user_handler_group_permissions', 'folders'),
    'fixed_args' => array('group', 'folders'),
    'variable_args' => 1,
),
/**
 * Create new user
 *
 * Match /create/
 */
'group_create' => array
(
    'handler' => array('midcom_admin_user_handler_group_create', 'create'),
    'fixed_args' => array('group', 'create'),
),
