<?php
/**
 * @package midcom
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

///////////////////////////////////////////////////////////
// Ease debugging and make sure the code actually works(tm)
if (version_compare(PHP_VERSION, '5.3.0', '<'))
{
    error_reporting(E_ALL);
}
else
{
    // Ignore deprecation warnings on PHP 5.3 because they're caused by our PEAR dependencies
    error_reporting(E_ALL & ~E_STRICT & ~E_DEPRECATED);
}

///////////////////////////////////
// Try to be smart about the paths:
// Define default constants
if (! defined('MIDCOM_ROOT'))
{
    define('MIDCOM_ROOT', dirname(__FILE__));
}

require(MIDCOM_ROOT . '/compat/environment.php');
midcom_compat_environment::initialize();

if (! defined('MIDCOM_STATIC_ROOT'))
{
    $pos = strrpos(MIDCOM_ROOT, '/');
    if ($pos === false)
    {
        // No slash, this is strange
        _midcom_stop_request('MIDCOM_ROOT did not contain a slash, this should not happen and is most probably the cause of a configuration error.');
    }
    define('MIDCOM_STATIC_ROOT', substr(MIDCOM_ROOT, 0, $pos) . '/static');
}
if (! defined('MIDCOM_STATIC_URL'))
{
    define('MIDCOM_STATIC_URL', '/midcom-static');
}
if (! defined('MIDCOM_CONFIG_FILE_BEFORE'))
{
    define('MIDCOM_CONFIG_FILE_BEFORE', '/etc/midgard/midcom.conf');
}
if (! defined('MIDCOM_CONFIG_FILE_AFTER'))
{
    define('MIDCOM_CONFIG_FILE_AFTER', '/etc/midgard/midcom-after.conf');
}

///////////////////////////////////////
//Constants, Globals and Configuration
require(MIDCOM_ROOT . '/constants.php');
require(MIDCOM_ROOT. '/midcom/connection.php');
require(MIDCOM_ROOT. '/midcom/config/midcom_config.php');
ini_set('track_errors', '1');
require(MIDCOM_ROOT. '/errors.php');

//////////////////////////////////////////////////////////////
// Set the MIDCOM_XDEBUG constant accordingly, if not yet set.

if (! defined('MIDCOM_XDEBUG'))
{
    if (function_exists('xdebug_start_profiling'))
    {
        define('MIDCOM_XDEBUG', 1);
    }
    else if (function_exists('xdebug_break'))
    {
        define('MIDCOM_XDEBUG', 2);
    }
    else
    {
        define('MIDCOM_XDEBUG', 0);
    }
}

/////////////////////
// Start the Debugger
require(MIDCOM_ROOT. '/midcom/debug.php');

debug_add("Start of MidCOM run: {$_SERVER['REQUEST_URI']}");

/**
 * Automatically load missing class files
 *
 * @param string $class_name Name of a missing PHP class
 */
function midcom_autoload($class_name)
{
    static $autoloaded = 0;

    $path = MIDCOM_ROOT . '/' . str_replace('_', '/', $class_name) . '.php';
    $path = str_replace('//', '/_', $path);

    if (   basename($path) == 'dba.php'
        || basename($path) == 'db.php')
    {
        // DBA object files are named objectname.php

        // Ensure we have the component loaded
        if (!$_MIDCOM->dbclassloader->load_component_for_class($class_name))
        {
            // Failed to load the component
            return;
        }
        if (class_exists($class_name))
        {
            return;
        }

        $path = dirname($path) . '.php';
    }

    if (   basename($path) == 'interface.php'
        && $class_name != 'midcom_baseclasses_components_interface')
    {
        // MidCOM component interfaces are named midcom/interface.php
        $_MIDCOM->dbclassloader->load_component_for_class($class_name);
        return;
    }

    if (!file_exists($path))
    {
        $alternative_path = str_replace('.php', '/main.php', $path);

        if (!file_exists($alternative_path))
        {
            /**
             * Enable when debugging autoloading issues, otherwise it's just noise
             *
            debug_add("Autoloader got '{$path}' and tried {$alternative_path} but neither was not found, aborting");
            debug_print_function_stack("Failed to autoload {$class_name}, called from");
             */
            return;
        }
        $path = $alternative_path;
    }

    require($path);
    $autoloaded++;
}
// Register autoloader so we get all MidCOM classes loaded automatically
spl_autoload_register('midcom_autoload');

///////////////////////////////////
// Load first-level supporting code
// Note that the cache check hit depends on the i18n and auth code.
require(MIDCOM_ROOT . '/midcom/services/auth/main.php');
$auth = new midcom_services_auth();
$auth->initialize();

//////////////////////////////////////
// Load and start up the cache system,
// this might already end the request
// on a content cache hit.
require(MIDCOM_ROOT . '/midcom/services/cache/main.php');
$GLOBALS['midcom_cache'] = new midcom_services_cache();
$GLOBALS['midcom_cache']->initialize();

///////////////////////////////////////////////
// Load all required MidCOM Framework Libraries

// Helpers and First-Generation services
// Services
require(MIDCOM_ROOT . '/midcom/services/_i18n_l10n.php');

/////////////////////////////////////
// Instantiate the MidCOM main class
require_once(MIDCOM_ROOT . '/midcom/compat/superglobal.php');

$_MIDCOM = new midcom_compat_superglobal();

$_MIDCOM->auth = $auth;
$_MIDCOM->cache = $GLOBALS['midcom_cache'];

$_MIDCOM->initialize();

if (file_exists(MIDCOM_CONFIG_FILE_AFTER))
{
    include(MIDCOM_CONFIG_FILE_AFTER);
}
?>
