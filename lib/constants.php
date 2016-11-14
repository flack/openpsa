<?php
/**
 * Constants for the MidCOM System
 *
 * @package midcom
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 *MidCOM Default Error Codes (-> HTTP)
 */
define('MIDCOM_ERROK', 200);
define('MIDCOM_ERRNOTFOUND', 404);
define('MIDCOM_ERRFORBIDDEN', 403);
define('MIDCOM_ERRAUTH', 401);
define('MIDCOM_ERRCRIT', 500);

// MidCOM NAP URL Information Constants

/**
 * MidCOM Meta Data Constants
 */
define('MIDCOM_NAV_URL', 0);
define('MIDCOM_NAV_NAME', 1);
define('MIDCOM_NAV_NODEID', 2);
define('MIDCOM_NAV_ID', 5);
define('MIDCOM_NAV_TYPE', 6);
define('MIDCOM_NAV_SCORE', 9);
define('MIDCOM_NAV_GUID', 10);
define('MIDCOM_NAV_COMPONENT', 12);
define('MIDCOM_NAV_FULLURL', 13);
define('MIDCOM_NAV_PERMALINK', 14);
define('MIDCOM_NAV_NOENTRY', 15);
define('MIDCOM_NAV_OBJECT', 16);
define('MIDCOM_NAV_RELATIVEURL', 17);
define('MIDCOM_NAV_ABSOLUTEURL', 18);
define('MIDCOM_NAV_SUBNODES', 19);
//define ('MIDCOM_NAV_LEAVES', 20); /* Yet unused. */
define('MIDCOM_NAV_ACL', 22);
define('MIDCOM_NAV_ICON', 23);
define('MIDCOM_NAV_CONFIGURATION', 24);
define('MIDCOM_NAV_LEAFID', 25);
define('MIDCOM_NAV_SORTABLE', 26);

/**
 * MidCOM Component Context Keys
 */
define('MIDCOM_CONTEXT_ANCHORPREFIX', 0);
define('MIDCOM_CONTEXT_SUBSTYLE', 1);
define('MIDCOM_CONTEXT_ROOTTOPIC', 3);
define('MIDCOM_CONTEXT_CONTENTTOPIC', 4);
define('MIDCOM_CONTEXT_COMPONENT', 6);
define('MIDCOM_CONTEXT_PAGETITLE', 9);
define('MIDCOM_CONTEXT_LASTMODIFIED', 10);
define('MIDCOM_CONTEXT_PERMALINKGUID', 11);
define('MIDCOM_CONTEXT_URI', 12);
define('MIDCOM_CONTEXT_HANDLERID', 13);
define('MIDCOM_CONTEXT_ROOTTOPICID', 14);
define('MIDCOM_CONTEXT_URLTOPICS', 15);
define('MIDCOM_CONTEXT_SHOWCALLBACK', 16);

/**
 * INTERNAL Context Keys, not accessible from outside midcom_application.
 */
define('MIDCOM_CONTEXT_CUSTOMDATA', 1000);

/**
 * Debugger
 */
define('MIDCOM_LOG_DEBUG', 4);
define('MIDCOM_LOG_INFO', 3);
define('MIDCOM_LOG_WARN', 2);
define('MIDCOM_LOG_ERROR', 1);
define('MIDCOM_LOG_CRIT', 0);

/**
 * MidCOM Core Status Codes
 */
define('MIDCOM_STATUS_PREPARE', 0);
define('MIDCOM_STATUS_CANHANDLE', 1);
define('MIDCOM_STATUS_HANDLE', 2);
define('MIDCOM_STATUS_CONTENT', 3);
define('MIDCOM_STATUS_CLEANUP', 4);
define('MIDCOM_STATUS_ABORT', 5);

/**
 * MidCOM NAP Sorting Modes
 */
define('MIDCOM_NAVORDER_DEFAULT', 0);
define('MIDCOM_NAVORDER_ARTICLESFIRST', 1);
define('MIDCOM_NAVORDER_TOPICSFIRST', 2);
define('MIDCOM_NAVORDER_SCORE', 3);

/**
 * MidCOM Toolbar Service
 */

/**
 * Element URL
 *
 * @see midcom_helper_toolbar
 */
define('MIDCOM_TOOLBAR_URL', 0);
/**
 * Element Label
 *
 * @see midcom_helper_toolbar
 */
define('MIDCOM_TOOLBAR_LABEL', 1);
/**
 * Element Helptext
 *
 * @see midcom_helper_toolbar
 */
define('MIDCOM_TOOLBAR_HELPTEXT', 2);
/**
 * Element Icon (Relative URL to MIDCOM_STATIC_URL root),
 * e.g. 'stock-icons/16x16/attach.png'.
 *
 * @see midcom_helper_toolbar
 */
define('MIDCOM_TOOLBAR_ICON', 3);
/**
 * Element Enabled state
 *
 * @see midcom_helper_toolbar
 */
define('MIDCOM_TOOLBAR_ENABLED', 4);
/**
 * Original element URL as defined by the callee.
 *
 * @see midcom_helper_toolbar
 */
define('MIDCOM_TOOLBAR__ORIGINAL_URL', 5);
/**
 * Options array.
 *
 * @see midcom_helper_toolbar
 */
define('MIDCOM_TOOLBAR_OPTIONS', 6);
/**
 * Set this to true if you just want to hide this element
 * from the output.
 *
 * @see midcom_helper_toolbar
 */
define('MIDCOM_TOOLBAR_HIDDEN', 7);

/**
 * Add a subobject here if you want to have nested menus.
 *
 * @see midcom_helper_toolbar
 */
define('MIDCOM_TOOLBAR_SUBMENU', 8);

/**
 * Use an HTTP POST form request if this is true. The default is not to do so.
 *
 * @see midcom_helper_toolbar
 */
define('MIDCOM_TOOLBAR_POST', 9);

/**
 * Optional arguments for a POST request.
 *
 * @see midcom_helper_toolbar
 */
define('MIDCOM_TOOLBAR_POST_HIDDENARGS', 10);

/**
 * Identifier for a node toolbar for a request context.
 *
 * @see midcom_services_toolbars
 */
define('MIDCOM_TOOLBAR_NODE', 100);

/**
 * Identifier for a view toolbar for a request context.
 *
 * @see midcom_services_toolbars
 */
define('MIDCOM_TOOLBAR_VIEW', 101);

/**
 * Identifier for a host toolbar for a request context.
 *
 * @see midcom_services_toolbars
 */
define('MIDCOM_TOOLBAR_HOST', 104);

/**
 * Identifier for a help toolbar for a request context.
 *
 * @see midcom_services_toolbars
 */
define('MIDCOM_TOOLBAR_HELP', 105);

/**
 * Identifier for a custom object toolbar.
 *
 * @see midcom_services_toolbars
 */
define('MIDCOM_TOOLBAR_OBJECT', 102);
/**
 * The accesskey for this button
 *
 * @see midcom_services_toolbars
 */
define('MIDCOM_TOOLBAR_ACCESSKEY', 103);

/**
 * Identifier for a node metadata for a request context.
 *
 * @see midcom_services_metadata
 */
define('MIDCOM_METADATA_NODE', 100);

/**
 * Identifier for a view metadata for a request context.
 *
 * @see midcom_services_metadata
 */
define('MIDCOM_METADATA_VIEW', 101);

/**
 * MidCOM Privilege System
 */

/**
 * Allow the privilege.
 */
define('MIDCOM_PRIVILEGE_ALLOW', 1);
/**
 * Deny the privilege.
 */
define('MIDCOM_PRIVILEGE_DENY', 2);
/**
 * Inherit the privilege from the parent.
 */
define('MIDCOM_PRIVILEGE_INHERIT', 3);

/**
 * Privilege array name entry
 */
define('MIDCOM_PRIVILEGE_NAME', 100);
/**
 * Privilege array assignee entry
 */
define('MIDCOM_PRIVILEGE_ASSIGNEE', 101);
/**
 * Privilege array value entry
 */
define('MIDCOM_PRIVILEGE_VALUE', 102);

/**
 * Magic scope value for privileges assigned to EVERYONE
 */
define('MIDCOM_PRIVILEGE_SCOPE_EVERYONE', 0);
/**
 * Magic scope value for privileges assigned to all unauthenticated users
 */
define('MIDCOM_PRIVILEGE_SCOPE_ANONYMOUS', 10);
/**
 * Magic scope value for privileges assigned to all authenticated users
 */
define('MIDCOM_PRIVILEGE_SCOPE_USERS', 10);
/**
 * Starting scope value for root groups
 */
define('MIDCOM_PRIVILEGE_SCOPE_ROOTGROUP', 100);
/**
 * Magic scope value for owner privileges.
 */
define('MIDCOM_PRIVILEGE_SCOPE_OWNER', 65050);
/**
 * Magic scope value for user privileges.
 */
define('MIDCOM_PRIVILEGE_SCOPE_USER', 65100);

/**
 * MidCOM Operation Bitfield constant, used for the definition of watch operations
 * in component manifests.
 *
 * @see midcom_core_manifest
 */

/**
 * Matches all known operations.
 */
define('MIDCOM_OPERATION_ALL', 0xFFFFFFFF);

/**
 * DBA object creation. This excludes parameter operations.
 */
define('MIDCOM_OPERATION_DBA_CREATE', 0x1);

/**
 * DBA object update, this includes all attachment and parameter operations.
 */
define('MIDCOM_OPERATION_DBA_UPDATE', 0x2);

/**
 * DBA object deletion. This excludes parameter operations.
 */
define('MIDCOM_OPERATION_DBA_DELETE', 0x4);

/**
 * DBA object import. This includes parameters & attachments.
 */
define('MIDCOM_OPERATION_DBA_IMPORT', 0x8);

/**
 * All known DBA operations.
 */
define('MIDCOM_OPERATION_DBA_ALL', 0xF);

/**
 * MidCOM Cron constants
 *
 * @see midcom_services_cron
 */

/**
 * Execute once every minute.
 */
define('MIDCOM_CRON_MINUTE', 10);

/**
 * Execute once every hour.
 */
define('MIDCOM_CRON_HOUR', 20);

/**
 * Execute once every day.
 */
define('MIDCOM_CRON_DAY', 30);
