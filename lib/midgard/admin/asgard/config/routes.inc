/**
 * Asgard "welcome page"
 *
 * Match /asgard/
 */
'welcome' => array
(
    'handler' => array ('midgard_admin_asgard_handler_welcome', 'welcome'),
    'fixed_args' => array(),
    'variable_args' => 0,
),
/**
 * Component listing page
 *
 * Match /components/
 */
'components' => array
(
    'handler' => array ('midgard_admin_asgard_handler_components', 'list'),
    'fixed_args' => array('components'),
    'variable_args' => 0,
),
/**
 * Component listing page
 *
 * Match /components/
 */
'components_component' => array
(
    'handler' => array ('midgard_admin_asgard_handler_components', 'component'),
    'fixed_args' => array('components'),
    'variable_args' => 1,
),
/**
 * Component configuration view
 *
 * Match /components/configuration/<component>
 */
'components_configuration' => array
(
    'handler' => array ('midgard_admin_asgard_handler_component_configuration', 'view'),
    'fixed_args' => array('components', 'configuration'),
    'variable_args' => 1,
),
/**
 * Component configuration editor
 *
 * Match /components/configuration/edit/<component>
 */
'components_configuration_edit' => array
(
    'handler' => array ('midgard_admin_asgard_handler_component_configuration', 'edit'),
    'fixed_args' => array('components', 'configuration', 'edit'),
    'variable_args' => 1,
),
/**
 * Component configuration editor
 *
 * Match /components/configuration/edit/<component>
 */
'components_configuration_edit_folder' => array
(
    'handler' => array ('midgard_admin_asgard_handler_component_configuration', 'edit'),
    'fixed_args' => array('components', 'configuration', 'edit'),
    'variable_args' => 2,
),
/**
 * Trashed items of MgdSchema
 *
 * Match /asgard/
 */
'trash' => array
(
    'handler' => array ('midgard_admin_asgard_handler_undelete', 'trash'),
    'fixed_args' => array('trash'),
),
/**
 * User preferences page
 *
 * Match /preferences/
 */
'preferences' => array
(
    'handler' => array ('midgard_admin_asgard_handler_preferences', 'preferences'),
    'fixed_args' => array('preferences'),
    'variable_args' => 0,
),
/**
 * AJAX interface for remembering user preferences set on the fly
 *
 * Match /preferences/ajax/
 */
'preferences_ajax' => array
(
    'handler' => array ('midgard_admin_asgard_handler_preferences', 'ajax'),
    'fixed_args' => array('preferences', 'ajax'),
    'variable_args' => 0,
),
/**
 * User preferences page for any person
 *
 * Match /preferences/<person guid>/
 */
'preferences_guid' => array
(
    'handler' => array ('midgard_admin_asgard_handler_preferences', 'preferences'),
    'fixed_args' => array('preferences'),
    'variable_args' => 1,
),
/**
 * Front page of a MgdSchema
 *
 * Match /asgard/
 */
'type' => array
(
    'handler' => array ('midgard_admin_asgard_handler_type', 'type'),
    'fixed_args' => array(),
    'variable_args' => 1,
),
/**
 * Trashed items of MgdSchema
 *
 * Match /asgard/
 */
'trash_type' => array
(
    'handler' => array ('midgard_admin_asgard_handler_undelete', 'trash_type'),
    'fixed_args' => array('trash'),
    'variable_args' => 1,
),
/**
 * Open an object in the user's preferred mode
 *
 * Match /asgard/object/open/<guid>/
 */
'object_open' => array
(
    'handler' => array ('midgard_admin_asgard_handler_object_manage', 'open'),
    'fixed_args' => array ('object', 'open'),
    'variable_args' => 1,
),
/**
 * View an object
 *
 * Match /asgard/object/view/<guid>/
 */
'object_view' => array
(
    'handler' => array ('midgard_admin_asgard_handler_object_manage', 'view'),
    'fixed_args' => array ('object', 'view'),
    'variable_args' => 1,
),
/**
 * Edit an object
 *
 * Match /asgard/object/edit/<guid>/
 */
'object_edit' => array
(
    'handler' => array ('midgard_admin_asgard_handler_object_manage', 'edit'),
    'fixed_args' => array ('object', 'edit'),
    'variable_args' => 1,
),
/**
 * Edit object metadata
 *
 * Match /asgard/object/metadata/<guid>/
 */
'object_metadata' => array
(
    'handler' => array ('midgard_admin_asgard_handler_object_metadata', 'edit'),
    'fixed_args' => array ('object', 'metadata'),
    'variable_args' => 1,
),
/**
 * Edit object parameters
 *
 * Match /asgard/object/parameters/<guid>/
 */
'object_parameters' => array
(
    'handler' => array ('midgard_admin_asgard_handler_object_parameters', 'edit'),
    'fixed_args' => array ('object', 'parameters'),
    'variable_args' => 1,
),
/**
 * Edit object permissions
 *
 * Match /asgard/object/permissions/<guid>/
 */
'object_permissions' => array
(
    'handler' => array ('midgard_admin_asgard_handler_object_permissions', 'edit'),
    'fixed_args' => array ('object', 'permissions'),
    'variable_args' => 1,
),
/**
 * Copy object
 *
 * Match /asgard/object/copy/<guid>/
 */
'object_copy' => array
(
    'handler' => array ('midgard_admin_asgard_handler_object_manage', 'copy'),
    'fixed_args' => array ('object', 'copy'),
    'variable_args' => 1,
),
/**
 * Copy object tree
 *
 * Match /asgard/object/copy/tree/<guid>/
 */
'object_copy_tree' => array
(
    'handler' => array ('midgard_admin_asgard_handler_object_manage', 'copy'),
    'fixed_args' => array ('object', 'copy', 'tree'),
    'variable_args' => 1,
),
/**
 * Create a new file
 *
 * Match /files/
 */
'object_attachments' => array
(
    'handler' => array ('midgard_admin_asgard_handler_object_attachments', 'create'),
    'fixed_args' => array ('object', 'attachments'),
    'variable_args' => 1,
),
/**
 * Edit a file
 *
 * Match /files/<filename>
 */
'object_attachments_edit' => array
(
    'handler' => array ('midgard_admin_asgard_handler_object_attachments', 'edit'),
    'fixed_args' => array ('object', 'attachments'),
    'variable_args' => 2,
),
/**
 * Delete a file
 *
 * Match /files/<filename>/delete/
 */
'object_attachments_delete' => array
(
    'handler' => array ('midgard_admin_asgard_handler_object_attachments', 'delete'),
    'fixed_args' => array ('object', 'attachments', 'delete'),
    'variable_args' => 2,
),
/**
 * Create a toplevel object with chooser
 *
 * Match /asgard/object/create/type/<parent guid>/
 */
'object_create_chooser' => array
(
    'handler' => array ('midgard_admin_asgard_handler_object_manage', 'create'),
    'fixed_args' => array ('object', 'create', 'chooser'),
    'variable_args' => 1,
),
/**
 * Create an object
 *
 * Match /asgard/object/create/type/<parent guid>/
 */
'object_create' => array
(
    'handler' => array ('midgard_admin_asgard_handler_object_manage', 'create'),
    'fixed_args' => array ('object', 'create'),
    'variable_args' => 2,
),

/**
 * Create a toplevel object
 *
 * Match /asgard/object/create/type/<parent guid>/
 */
'object_create_toplevel' => array
(
    'handler' => array ('midgard_admin_asgard_handler_object_manage', 'create'),
    'fixed_args' => array ('object', 'create'),
    'variable_args' => 1,
),

/**
 * Delete an object
 *
 * Match /asgard/object/delete/<guid>/
 */
'object_delete' => array
(
    'handler' => array ('midgard_admin_asgard_handler_object_manage', 'delete'),
    'fixed_args' => array ('object', 'delete'),
    'variable_args' => 1,
),
/**
 * Show 'object deleted' page
 *
 * Match /asgard/object/deleted/<guid>
 */
'object_deleted' => array
(
    'handler' => array ('midgard_admin_asgard_handler_object_deleted', 'deleted'),
    'fixed_args' => array ('object', 'deleted'),
    'variable_args' => 1,
),

/**
 * Revision control system of an object.
 */
/**
 * RCS history
 *
 * Match /asgard/object/rcs/<guid>
 */
'object_rcs_history' => array
(
    'handler' => array('midgard_admin_asgard_handler_object_rcs','history'),
    'fixed_args' => array ('object', 'rcs'),
    'variable_args' => 1,
),
/**
 * RCS history
 *
 * Match /asgard/object/rcs/<guid>
 */
'object_rcs_preview' => array
(
    'handler' => array('midgard_admin_asgard_handler_object_rcs','preview'),
    'fixed_args' => array('object', 'rcs', 'preview'),
    'variable_args' => 2,
),
'object_rcs_diff' => array
(
    'handler' => array('midgard_admin_asgard_handler_object_rcs','diff'),
    'fixed_args' => array('object', 'rcs', 'diff'),
    'variable_args' => 3,
),
'object_rcs_restore' => array
(
    'handler' => array('midgard_admin_asgard_handler_object_rcs','restore'),
    'fixed_args' => array('object', 'rcs', 'restore'),
    'variable_args' => 2,
),
