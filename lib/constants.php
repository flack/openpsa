<?php
/**
 * Constants for the MidCOM System
 *
 * @package midcom
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use Symfony\Component\HttpFoundation\Response;

const MIDCOM_ROOT = __DIR__;

/**
 *MidCOM Default Error Codes (-> HTTP)
 */
const MIDCOM_ERROK = Response::HTTP_OK;
const MIDCOM_ERRNOTFOUND = Response::HTTP_NOT_FOUND;
const MIDCOM_ERRFORBIDDEN = Response::HTTP_FORBIDDEN;
const MIDCOM_ERRAUTH = Response::HTTP_UNAUTHORIZED;
const MIDCOM_ERRCRIT = Response::HTTP_INTERNAL_SERVER_ERROR;

// MidCOM NAP URL Information Constants

/**
 * MidCOM Meta Data Constants
 */
const MIDCOM_NAV_URL = 0;
const MIDCOM_NAV_NAME = 1;
const MIDCOM_NAV_NODEID = 2;
const MIDCOM_NAV_ID = 5;
const MIDCOM_NAV_TYPE = 6;
const MIDCOM_NAV_SCORE = 9;
const MIDCOM_NAV_GUID = 10;
const MIDCOM_NAV_COMPONENT = 12;
const MIDCOM_NAV_FULLURL = 13;
const MIDCOM_NAV_PERMALINK = 14;
const MIDCOM_NAV_NOENTRY = 15;
const MIDCOM_NAV_OBJECT = 16;
const MIDCOM_NAV_RELATIVEURL = 17;
const MIDCOM_NAV_ABSOLUTEURL = 18;
const MIDCOM_NAV_SUBNODES = 19;
//define ('MIDCOM_NAV_LEAVES', 20); /* Yet unused. */
const MIDCOM_NAV_ACL = 22;
const MIDCOM_NAV_ICON = 23;
const MIDCOM_NAV_CONFIGURATION = 24;
const MIDCOM_NAV_LEAFID = 25;
const MIDCOM_NAV_SORTABLE = 26;

/**
 * MidCOM Component Context Keys
 */
const MIDCOM_CONTEXT_ANCHORPREFIX = 0;
const MIDCOM_CONTEXT_SUBSTYLE = 1;
const MIDCOM_CONTEXT_ROOTTOPIC = 3;
const MIDCOM_CONTEXT_CONTENTTOPIC = 4;
const MIDCOM_CONTEXT_COMPONENT = 6;
const MIDCOM_CONTEXT_PAGETITLE = 9;
const MIDCOM_CONTEXT_LASTMODIFIED = 10;
const MIDCOM_CONTEXT_PERMALINKGUID = 11;
const MIDCOM_CONTEXT_URI = 12;
const MIDCOM_CONTEXT_URLTOPICS = 15;
const MIDCOM_CONTEXT_SHOWCALLBACK = 16;

/**
 * INTERNAL Context Keys, not accessible from outside midcom_application.
 */
const MIDCOM_CONTEXT_CUSTOMDATA = 1000;

/**
 * Debugger
 */
const MIDCOM_LOG_DEBUG = 4;
const MIDCOM_LOG_INFO = 3;
const MIDCOM_LOG_WARN = 2;
const MIDCOM_LOG_ERROR = 1;
const MIDCOM_LOG_CRIT = 0;

/**
 * MidCOM NAP Sorting Modes
 */
const MIDCOM_NAVORDER_DEFAULT = 0;
const MIDCOM_NAVORDER_ARTICLESFIRST = 1;
const MIDCOM_NAVORDER_TOPICSFIRST = 2;
const MIDCOM_NAVORDER_SCORE = 3;

/**
 * MidCOM Toolbar Service
 */

/**
 * Element URL
 *
 * @see midcom_helper_toolbar
 */
const MIDCOM_TOOLBAR_URL = 0;
/**
 * Element Label
 *
 * @see midcom_helper_toolbar
 */
const MIDCOM_TOOLBAR_LABEL = 1;
/**
 * Element Helptext
 *
 * @see midcom_helper_toolbar
 */
const MIDCOM_TOOLBAR_HELPTEXT = 2;
/**
 * Element Icon (Relative URL to MIDCOM_STATIC_URL root),
 * e.g. 'stock-icons/16x16/attach.png'.
 *
 * @see midcom_helper_toolbar
 */
const MIDCOM_TOOLBAR_ICON = 3;

/**
 * Element Icon (as font-awesome class),
 * e.g. 'pencil-square-o'.
 *
 * @see midcom_helper_toolbar
 */
const MIDCOM_TOOLBAR_GLYPHICON = 11;

/**
 * Element Enabled state
 *
 * @see midcom_helper_toolbar
 */
const MIDCOM_TOOLBAR_ENABLED = 4;
/**
 * Original element URL as defined by the callee.
 *
 * @see midcom_helper_toolbar
 */
const MIDCOM_TOOLBAR__ORIGINAL_URL = 5;
/**
 * Options array.
 *
 * @see midcom_helper_toolbar
 */
const MIDCOM_TOOLBAR_OPTIONS = 6;
/**
 * Set this to true if you just want to hide this element
 * from the output.
 *
 * @see midcom_helper_toolbar
 */
const MIDCOM_TOOLBAR_HIDDEN = 7;

/**
 * Add a subobject here if you want to have nested menus.
 *
 * @see midcom_helper_toolbar
 */
const MIDCOM_TOOLBAR_SUBMENU = 8;

/**
 * Use an HTTP POST form request if this is true. The default is not to do so.
 *
 * @see midcom_helper_toolbar
 */
const MIDCOM_TOOLBAR_POST = 9;

/**
 * Optional arguments for a POST request.
 *
 * @see midcom_helper_toolbar
 */
const MIDCOM_TOOLBAR_POST_HIDDENARGS = 10;

/**
 * Identifier for a node toolbar for a request context.
 *
 * @see midcom_services_toolbars
 */
const MIDCOM_TOOLBAR_NODE = 100;

/**
 * Identifier for a view toolbar for a request context.
 *
 * @see midcom_services_toolbars
 */
const MIDCOM_TOOLBAR_VIEW = 101;

/**
 * Identifier for a host toolbar for a request context.
 *
 * @see midcom_services_toolbars
 */
const MIDCOM_TOOLBAR_HOST = 104;

/**
 * Identifier for a help toolbar for a request context.
 *
 * @see midcom_services_toolbars
 */
const MIDCOM_TOOLBAR_HELP = 105;

/**
 * Identifier for a custom object toolbar.
 *
 * @see midcom_services_toolbars
 */
const MIDCOM_TOOLBAR_OBJECT = 102;
/**
 * The accesskey for this button
 *
 * @see midcom_services_toolbars
 */
const MIDCOM_TOOLBAR_ACCESSKEY = 103;

/**
 * MidCOM Privilege System
 */

/**
 * Allow the privilege.
 */
const MIDCOM_PRIVILEGE_ALLOW = 1;
/**
 * Deny the privilege.
 */
const MIDCOM_PRIVILEGE_DENY = 2;
/**
 * Inherit the privilege from the parent.
 */
const MIDCOM_PRIVILEGE_INHERIT = 3;

/**
 * Privilege array name entry
 */
const MIDCOM_PRIVILEGE_NAME = 100;
/**
 * Privilege array assignee entry
 */
const MIDCOM_PRIVILEGE_ASSIGNEE = 101;
/**
 * Privilege array value entry
 */
const MIDCOM_PRIVILEGE_VALUE = 102;

/**
 * Magic scope value for privileges assigned to EVERYONE
 */
const MIDCOM_PRIVILEGE_SCOPE_EVERYONE = 0;
/**
 * Magic scope value for privileges assigned to all unauthenticated users
 */
const MIDCOM_PRIVILEGE_SCOPE_ANONYMOUS = 10;
/**
 * Magic scope value for privileges assigned to all authenticated users
 */
const MIDCOM_PRIVILEGE_SCOPE_USERS = 10;
/**
 * Starting scope value for root groups
 */
const MIDCOM_PRIVILEGE_SCOPE_ROOTGROUP = 100;
/**
 * Magic scope value for owner privileges.
 */
const MIDCOM_PRIVILEGE_SCOPE_OWNER = 65050;
/**
 * Magic scope value for user privileges.
 */
const MIDCOM_PRIVILEGE_SCOPE_USER = 65100;

/**
 * MidCOM Operation Bitfield constant, used for the definition of watch operations
 * in component manifests.
 *
 * @see midcom_core_manifest
 */

/**
 * Matches all known operations.
 */
const MIDCOM_OPERATION_ALL = 0xFFFFFFFF;

/**
 * DBA object creation. This excludes parameter operations.
 */
const MIDCOM_OPERATION_DBA_CREATE = 0x1;

/**
 * DBA object update, this includes all attachment and parameter operations.
 */
const MIDCOM_OPERATION_DBA_UPDATE = 0x2;

/**
 * DBA object deletion. This excludes parameter operations.
 */
const MIDCOM_OPERATION_DBA_DELETE = 0x4;

/**
 * DBA object import. This includes parameters & attachments.
 */
const MIDCOM_OPERATION_DBA_IMPORT = 0x8;

/**
 * All known DBA operations.
 */
const MIDCOM_OPERATION_DBA_ALL = 0xF;

/**
 * MidCOM Cron constants
 *
 * @see midcom_services_cron
 */

/**
 * Execute once every minute.
 */
const MIDCOM_CRON_MINUTE = 10;

/**
 * Execute once every hour.
 */
const MIDCOM_CRON_HOUR = 20;

/**
 * Execute once every day.
 */
const MIDCOM_CRON_DAY = 30;
