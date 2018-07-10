/**
 * Asgard "welcome page"
 *
 * Match /asgard/
 */
'welcome' => [
    'handler' => ['midgard_admin_asgard_handler_welcome', 'welcome'],
    'fixed_args' => [],
    'variable_args' => 0,
],
/**
 * Asgard "shell"
 *
 * Match /shell/
 */
'shell' => [
    'handler' => ['midgard_admin_asgard_handler_shell', 'shell'],
    'fixed_args' => ['shell'],
],
/**
 * Component listing page
 *
 * Match /components/
 */
'components' => [
    'handler' => ['midgard_admin_asgard_handler_components', 'list'],
    'fixed_args' => ['components'],
    'variable_args' => 0,
],
/**
 * Component listing page
 *
 * Match /components/
 */
'components_component' => [
    'handler' => ['midgard_admin_asgard_handler_components', 'component'],
    'fixed_args' => ['components'],
    'variable_args' => 1,
],
/**
 * Component configuration view
 *
 * Match /components/configuration/<component>
 */
'components_configuration' => [
    'handler' => ['midgard_admin_asgard_handler_component_configuration', 'view'],
    'fixed_args' => ['components', 'configuration'],
    'variable_args' => 1,
],
/**
 * Component configuration editor
 *
 * Match /components/configuration/edit/<component>
 */
'components_configuration_edit' => [
    'handler' => ['midgard_admin_asgard_handler_component_configuration', 'edit'],
    'fixed_args' => ['components', 'configuration', 'edit'],
    'variable_args' => 1,
],
/**
 * Component configuration editor
 *
 * Match /components/configuration/edit/<component>
 */
'components_configuration_edit_folder' => [
    'handler' => ['midgard_admin_asgard_handler_component_configuration', 'edit'],
    'fixed_args' => ['components', 'configuration', 'edit'],
    'variable_args' => 2,
],
/**
 * Trashed items of MgdSchema
 *
 * Match /asgard/
 */
'trash' => [
    'handler' => ['midgard_admin_asgard_handler_undelete', 'trash'],
    'fixed_args' => ['trash'],
],
/**
 * User preferences page
 *
 * Match /preferences/
 */
'preferences' => [
    'handler' => ['midgard_admin_asgard_handler_preferences', 'preferences'],
    'fixed_args' => ['preferences'],
    'variable_args' => 0,
],
/**
 * AJAX interface for remembering user preferences set on the fly
 *
 * Match /preferences/ajax/
 */
'preferences_ajax' => [
    'handler' => ['midgard_admin_asgard_handler_preferences', 'ajax'],
    'fixed_args' => ['preferences', 'ajax'],
    'variable_args' => 0,
],
/**
 * User preferences page for any person
 *
 * Match /preferences/<person guid>/
 */
'preferences_guid' => [
    'handler' => ['midgard_admin_asgard_handler_preferences', 'preferences'],
    'fixed_args' => ['preferences'],
    'variable_args' => 1,
],
/**
 * Front page of a MgdSchema
 *
 * Match /asgard/
 */
'type' => [
    'handler' => ['midgard_admin_asgard_handler_type', 'type'],
    'fixed_args' => [],
    'variable_args' => 1,
],
/**
 * Trashed items of MgdSchema
 *
 * Match /asgard/
 */
'trash_type' => [
    'handler' => ['midgard_admin_asgard_handler_undelete', 'trash_type'],
    'fixed_args' => ['trash'],
    'variable_args' => 1,
],
/**
 * Open an object in the user's preferred mode
 *
 * Match /asgard/object/open/<guid>/
 */
'object_open' => [
    'handler' => ['midgard_admin_asgard_handler_object_manage', 'open'],
    'fixed_args' => ['object', 'open'],
    'variable_args' => 1,
],
/**
 * View an object
 *
 * Match /asgard/object/view/<guid>/
 */
'object_view' => [
    'handler' => ['midgard_admin_asgard_handler_object_manage', 'view'],
    'fixed_args' => ['object', 'view'],
    'variable_args' => 1,
],
/**
 * Edit an object
 *
 * Match /asgard/object/edit/<guid>/
 */
'object_edit' => [
    'handler' => ['midgard_admin_asgard_handler_object_manage', 'edit'],
    'fixed_args' => ['object', 'edit'],
    'variable_args' => 1,
],
/**
 * Edit object metadata
 *
 * Match /asgard/object/metadata/<guid>/
 */
'object_metadata' => [
    'handler' => ['midgard_admin_asgard_handler_object_metadata', 'edit'],
    'fixed_args' => ['object', 'metadata'],
    'variable_args' => 1,
],
/**
 * Edit object parameters
 *
 * Match /asgard/object/parameters/<guid>/
 */
'object_parameters' => [
    'handler' => ['midgard_admin_asgard_handler_object_parameters', 'edit'],
    'fixed_args' => ['object', 'parameters'],
    'variable_args' => 1,
],
/**
 * Edit object permissions
 *
 * Match /asgard/object/permissions/<guid>/
 */
'object_permissions' => [
    'handler' => ['midgard_admin_asgard_handler_object_permissions', 'edit'],
    'fixed_args' => ['object', 'permissions'],
    'variable_args' => 1,
],
/**
 * Copy object
 *
 * Match /asgard/object/copy/<guid>/
 */
'object_copy' => [
    'handler' => ['midgard_admin_asgard_handler_object_manage', 'copy'],
    'fixed_args' => ['object', 'copy'],
    'variable_args' => 1,
],
/**
 * Copy object tree
 *
 * Match /asgard/object/copy/tree/<guid>/
 */
'object_copy_tree' => [
    'handler' => ['midgard_admin_asgard_handler_object_manage', 'copy'],
    'fixed_args' => ['object', 'copy', 'tree'],
    'variable_args' => 1,
],
/**
 * Create a new file
 *
 * Match /files/
 */
'object_attachments' => [
    'handler' => ['midgard_admin_asgard_handler_object_attachments', 'create'],
    'fixed_args' => ['object', 'attachments'],
    'variable_args' => 1,
],
/**
 * Edit a file
 *
 * Match /files/<filename>
 */
'object_attachments_edit' => [
    'handler' => ['midgard_admin_asgard_handler_object_attachments', 'edit'],
    'fixed_args' => ['object', 'attachments'],
    'variable_args' => 2,
],
/**
 * Delete a file
 *
 * Match /files/<filename>/delete/
 */
'object_attachments_delete' => [
    'handler' => ['midgard_admin_asgard_handler_object_attachments', 'delete'],
    'fixed_args' => ['object', 'attachments', 'delete'],
    'variable_args' => 2,
],
/**
 * Create a toplevel object with chooser
 *
 * Match /asgard/object/create/type/<parent guid>/
 */
'object_create_chooser' => [
    'handler' => ['midgard_admin_asgard_handler_object_manage', 'create'],
    'fixed_args' => ['object', 'create', 'chooser'],
    'variable_args' => 1,
],
/**
 * Create an object
 *
 * Match /asgard/object/create/type/<parent guid>/
 */
'object_create' => [
    'handler' => ['midgard_admin_asgard_handler_object_manage', 'create'],
    'fixed_args' => ['object', 'create'],
    'variable_args' => 2,
],

/**
 * Create a toplevel object
 *
 * Match /asgard/object/create/type/<parent guid>/
 */
'object_create_toplevel' => [
    'handler' => ['midgard_admin_asgard_handler_object_manage', 'create'],
    'fixed_args' => ['object', 'create'],
    'variable_args' => 1,
],

/**
 * Delete an object
 *
 * Match /asgard/object/delete/<guid>/
 */
'object_delete' => [
    'handler' => ['midgard_admin_asgard_handler_object_manage', 'delete'],
    'fixed_args' => ['object', 'delete'],
    'variable_args' => 1,
],
/**
 * Show 'object deleted' page
 *
 * Match /asgard/object/deleted/<guid>
 */
'object_deleted' => [
    'handler' => ['midgard_admin_asgard_handler_object_deleted', 'deleted'],
    'fixed_args' => ['object', 'deleted'],
    'variable_args' => 1,
],

/**
 * Revision control system of an object.
 */
/**
 * RCS history
 *
 * Match /asgard/object/rcs/<guid>
 */
'object_rcs_history' => [
    'handler' => ['midgard_admin_asgard_handler_object_rcs','history'],
    'fixed_args' => ['object', 'rcs'],
    'variable_args' => 1,
],
/**
 * RCS history
 *
 * Match /asgard/object/rcs/<guid>
 */
'object_rcs_preview' => [
    'handler' => ['midgard_admin_asgard_handler_object_rcs','preview'],
    'fixed_args' => ['object', 'rcs', 'preview'],
    'variable_args' => 2,
],
'object_rcs_diff' => [
    'handler' => ['midgard_admin_asgard_handler_object_rcs','diff'],
    'fixed_args' => ['object', 'rcs', 'diff'],
    'variable_args' => 3,
],
'object_rcs_restore' => [
    'handler' => ['midgard_admin_asgard_handler_object_rcs','restore'],
    'fixed_args' => ['object', 'rcs', 'restore'],
    'variable_args' => 2,
],
