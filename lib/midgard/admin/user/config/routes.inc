/**
 * List users
 *
 * Match /user-editor/
 */
'user_list' => array
(
    'handler' => array('midgard_admin_user_handler_list', 'list'),
),
/**
 * Batch process users
 *
 * Match /user-editor/batch/action
 */
'user_batch' => array
(
    'handler' => array('midgard_admin_user_handler_list', 'batch'),
    'fixed_args' => 'batch',
    'variable_args' => 1,
),
/**
 * Generate random passwords
 *
 * Match /user-editor/password/batch/
 */
'user_password_email' => array
(
    'handler' => array('midgard_admin_user_handler_list', 'password_email'),
    'fixed_args' => array('password', 'email'),
),
/**
 * Edit a user
 *
 * Match /user-editor/edit/<guid>/
 */
'user_edit' => array
(
    'handler' => array('midgard_admin_user_handler_user_edit', 'edit'),
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
    'handler' => array('midgard_admin_user_handler_user_account', 'passwords'),
    'fixed_args' => 'password',
),
/**
 * Edit a user's account
 *
 * Match /user-editor/account/<guid>/
 */
'user_edit_account' => array
(
    'handler' => array('midgard_admin_user_handler_user_account', 'edit'),
    'fixed_args' => 'account',
    'variable_args' => 1,
),
/**
 * Delete a user's account
 *
 * Match /user-editor/account/delete/<guid>/
 */
'user_delete_account' => array
(
    'handler' => array('midgard_admin_user_handler_user_account', 'delete'),
    'fixed_args' => array('account', 'delete'),
    'variable_args' => 1,
),
/**
 * Create new user
 *
 * Match /create/
 */
'user_create' => array
(
    'handler' => array('midgard_admin_user_handler_user_create', 'create'),
    'fixed_args' => 'create',
),
/**
 * List groups
 *
 * Match /user-editor/group/
 */
'group_list' => array
(
    'handler' => array('midgard_admin_user_handler_group_list', 'list'),
    'fixed_args' => 'group',
),
/**
 * Move a group
 *
 * Match /user-editor/group/
 */
'group_move' => array
(
    'handler' => array('midgard_admin_user_handler_group_list', 'move'),
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
    'handler' => array('midgard_admin_user_handler_group_edit', 'edit'),
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
    'handler' => array('midgard_admin_user_handler_group_permissions', 'folders'),
    'fixed_args' => array('group', 'folders'),
    'variable_args' => 1,
),
/**
 * Create new user
 *
 * Match /group/create/
 */
'group_create' => array
(
    'handler' => array('midgard_admin_user_handler_group_create', 'create'),
    'fixed_args' => array('group', 'create'),
),
