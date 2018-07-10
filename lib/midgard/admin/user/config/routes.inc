/**
 * List users
 *
 * Match /user-editor/
 */
'user_list' => [
    'handler' => ['midgard_admin_user_handler_list', 'list'],
],
/**
 * Batch process users
 *
 * Match /user-editor/batch/action
 */
'user_batch' => [
    'handler' => ['midgard_admin_user_handler_list', 'batch'],
    'fixed_args' => 'batch',
    'variable_args' => 1,
],
/**
 * Generate random passwords
 *
 * Match /user-editor/password/batch/
 */
'user_password_email' => [
    'handler' => ['midgard_admin_user_handler_list', 'password_email'],
    'fixed_args' => ['password', 'email'],
],
/**
 * Edit a user
 *
 * Match /user-editor/edit/<guid>/
 */
'user_edit' => [
    'handler' => ['midgard_admin_user_handler_user_edit', 'edit'],
    'fixed_args' => 'edit',
    'variable_args' => 1,
],
/**
 * Generate random passwords
 *
 * Match /user-editor/password/
 */
'user_passwords' => [
    'handler' => ['midgard_admin_user_handler_user_account', 'passwords'],
    'fixed_args' => 'password',
],
/**
 * Edit a user's account
 *
 * Match /user-editor/account/<guid>/
 */
'user_edit_account' => [
    'handler' => ['midgard_admin_user_handler_user_account', 'edit'],
    'fixed_args' => 'account',
    'variable_args' => 1,
],
/**
 * Delete a user's account
 *
 * Match /user-editor/account/delete/<guid>/
 */
'user_delete_account' => [
    'handler' => ['midgard_admin_user_handler_user_account', 'delete'],
    'fixed_args' => ['account', 'delete'],
    'variable_args' => 1,
],
/**
 * Create new user
 *
 * Match /create/
 */
'user_create' => [
    'handler' => ['midgard_admin_user_handler_user_create', 'create'],
    'fixed_args' => 'create',
],
/**
 * List groups
 *
 * Match /user-editor/group/
 */
'group_list' => [
    'handler' => ['midgard_admin_user_handler_group_list', 'list'],
    'fixed_args' => 'group',
],
/**
 * Move a group
 *
 * Match /user-editor/group/
 */
'group_move' => [
    'handler' => ['midgard_admin_user_handler_group_list', 'move'],
    'fixed_args' => ['group', 'move'],
    'variable_args' => 1,
],
/**
 * Edit a group
 *
 * Match /user-editor/group/edit/<guid>/
 */
'group_edit' => [
    'handler' => ['midgard_admin_user_handler_group_edit', 'edit'],
    'fixed_args' => ['group', 'edit'],
    'variable_args' => 1,
],
/**
 * List folders group has privileges to
 *
 * Match /user-editor/group/folders/<guid>/
 */
'group_folders' => [
    'handler' => ['midgard_admin_user_handler_group_permissions', 'folders'],
    'fixed_args' => ['group', 'folders'],
    'variable_args' => 1,
],
/**
 * Create new user
 *
 * Match /group/create/
 */
'group_create' => [
    'handler' => ['midgard_admin_user_handler_group_create', 'create'],
    'fixed_args' => ['group', 'create'],
],
