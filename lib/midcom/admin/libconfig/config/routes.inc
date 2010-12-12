/**
 * List libraries
 *
 * Match /
 */
'index' => array
(
    'handler' => array ('midcom_admin_libconfig_handler_list', 'list'),
),
/**
 * Edit library config
 *
 * Match /edit/<component>/
 */
'edit' => array
(
    'handler' => array ('midcom_admin_libconfig_handler_edit', 'edit'),
    'fixed_args' => array ('edit'),
    'variable_args' => 1,
),
/**
 * Show current settings
 *
 * Match /view/<component>/
 */
'view' => array
(
    'handler' => array ('midcom_admin_libconfig_handler_view', 'view'),
    'fixed_args' => array ('view'),
    'variable_args' => 1,
),
