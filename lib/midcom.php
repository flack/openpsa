<?php
/**
 * @package midcom
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

// ================================
// = MidgardMVC Ragnaland support =
// ================================
if (class_exists('midgardmvc_core')) {
    function _midcom_header($string, $replace = true, $http_response_code = null)
    {
        if ($http_response_code === null) {
            midgardmvc_core::get_instance()->dispatcher->header($string, $replace);
        } else {
            midgardmvc_core::get_instance()->dispatcher->header($string, $replace, $http_response_code);
        }
    }

    function _midcom_stop_request($message = '')
    {
        midgardmvc_core::get_instance()->dispatcher->end_request();
    }

    function _midcom_headers_sent()
    {
        return midgardmvc_core::get_instance()->dispatcher->headers_sent();
    }

    function _midcom_setcookie($name, $value = '', $expire = 0, $path = '/', $domain = null, $secure = false, $httponly = false)
    {
        return midgardmvc_core::get_instance()->dispatcher->setcookie($name, $value, $expire, $path, $domain, $secure, $httponly);
    }
} else {
    function _midcom_header($string, $replace = true, $http_response_code = null)
    {
        if ($http_response_code === null) {
            header($string, $replace);
        } else {
            header($string, $replace, $http_response_code);
        }
    }

    function _midcom_stop_request($message = '')
    {
        exit($message);
    }

    function _midcom_headers_sent()
    {
        return headers_sent();
    }

    function _midcom_setcookie($name, $value = '', $expire = 0, $path = '/', $domain = null, $secure = false, $httponly = false)
    {
        if (version_compare(PHP_VERSION, '5.2.0', '<'))
        {
            return setcookie($name, $value, $expire, $path, $domain, $secure);
        }
        else
        {
            return setcookie($name, $value, $expire, $path, $domain, $secure, $httponly);
        }
    }
}

////////////////////////////////////////////////////////
// First, block all Link prefetching requests as long as
// MidCOM isn't bulletproofed against this "feature".
// Ultimately, this is also a matter of performance...
if (   array_key_exists('HTTP_X_MOZ', $_SERVER)
    && $_SERVER['HTTP_X_MOZ'] == 'prefetch')
{
    _midcom_header('Cache-Control: no-cache');
    _midcom_header('Pragma: no-cache');
    _midcom_header('HTTP/1.0 403 Forbidden');
    echo '403: Forbidden<br><br>Prefetching not allowed.';
    _midcom_stop_request();
}

/**
 * Second, make sure the URLs not having query string (or midcom-xxx- -method signature)
 * have trailing slash or some extension in the "filename".
 *
 * This makes life much, much better when making static copies for whatever reason
 *
 * 2008-09-26: Now also rewrites urls ending in .html to end with trailing slash.
 */
$redirect_test_uri = (string)$_SERVER['REQUEST_URI'];
if (   !isset($_SERVER['MIDCOM_COMPAT_REDIR'])
    || (bool)$_SERVER['MIDCOM_COMPAT_REDIR'] !== false)
{
    $redirect_test_uri = preg_replace('/\.html$/', '', $redirect_test_uri);
}
if (   !preg_match('%\?|/$|midcom-.+-|/.*\.[^/]+$%', $redirect_test_uri)
    && (   !isset($_POST)
        || empty($_POST))
    )
{
    _midcom_header('HTTP/1.0 301 Moved Permanently');
    _midcom_header("Location: {$redirect_test_uri}/");
    $redirect_test_uri_clean = htmlentities($redirect_test_uri);
    echo "301: new location <a href='{$redirect_test_uri_clean}/'>{$redirect_test_uri_clean}/</a>";
    _midcom_stop_request();
}
unset($redirect_test_uri);

/** */

// Advertise the fact that this is a Midgard server
_midcom_header('X-Powered-By: Midgard/' . mgd_version());
//mgd_debug_start();

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
if (! defined('MIDCOM_STATIC_ROOT'))
{
    $pos = strrpos(MIDCOM_ROOT, '/');
    if ($pos === false)
    {
        // No slash, this is strange
        _midcom_stop_request('MIDCOM_ROOT did not contain a slash, this should not happen and is most probably the cause of a configuration error.');
    }
    define('MIDCOM_STATIC_ROOT', substr(MIDCOM_ROOT,0,$pos) . '/static');
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
/**
 * Do not mess about with the servers display_errors setting
 *
 * See http://trac.midgard-project.org/ticket/1688
 *
ini_set('display_errors', '0');
if ($GLOBALS['midcom_config']['display_php_errors'])
{
    ini_set('display_errors', '1');
}
 */
if ($GLOBALS['midcom_config']['enable_error_handler'])
{
    require(MIDCOM_ROOT. '/errors.php');
}
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

debug_add("Start of MidCOM run: {$_SERVER['REQUEST_URI']}", MIDCOM_LOG_DEBUG);

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
require(MIDCOM_ROOT . '/midcom/helper/misc.php');

/////////////////////////////////////
// Instantiate the MidCOM main class

/**
 * Doublecheck before requiring, in certain corner cases autoloader might have been faster then us
 *
 * @see http://trac.midgard-project.org/ticket/1324
 */
if (!class_exists('midcom_application'))
{
    require_once(MIDCOM_ROOT . '/midcom/application.php');
}

$_MIDCOM = new midcom_application();
$_MIDCOM->auth = $auth;
$_MIDCOM->cache = $GLOBALS['midcom_cache'];

$_MIDCOM->initialize();

if (file_exists(MIDCOM_CONFIG_FILE_AFTER))
{
    include(MIDCOM_CONFIG_FILE_AFTER);
}
?>
