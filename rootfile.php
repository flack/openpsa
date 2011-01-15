<?php
// Check that the environment is a working one
if (!extension_loaded('midgard2'))
{
    throw new Exception("OpenPSA requires Midgard2 PHP extension to run");
}
if (!ini_get('midgard.superglobals_compat'))
{
    throw new Exception('You need to set midgard.superglobals_compat=On in your php.ini to run OpenPSA with Midgard2');
}
if (!class_exists('midgard_topic'))
{
    throw new Exception('You need to install OpenPSA MgdSchemas from the "schemas" directory to the Midgard2 schema directory');
}

ini_set('memory_limit', '68M');

// Path to the MidCOM environment
define('MIDCOM_ROOT', realpath(dirname(__FILE__)) . '/lib');
define('OPENPSA2_PREFIX', dirname($_SERVER['SCRIPT_NAME']));
define('OPENPSA2_THEME_ROOT', MIDCOM_ROOT . '/../themes/');

header('Content-Type: text/html; charset=utf-8');

// Initialize the $_MIDGARD superglobal
$_MIDGARD = array
(
    'argv' => array(),

    'user' => 0,
    'admin' => false,
    'root' => false,

    'auth' => false,
    'cookieauth' => false,

    // General host setup
    'page' => 0,
    'debug' => false,

    'host' => 0,
    'style' => 0,
    'author' => 0,
    'config' => array
    (
        'prefix' => '',
        'quota' => false,
        'unique_host_name' => 'openpsa',
        'auth_cookie_id' => 1,
    ),

    'schema' => array
    (
        'types' => array(),
    ),

    'theme' => 'OpenPsa2',
    'page_style' => '',
);

$_MIDGARD_CONNECTION =& midgard_connection::get_instance();

$GLOBALS['midcom_config_local'] = array();
$GLOBALS['midcom_config_local']['person_class'] = 'openpsa_person';
$GLOBALS['midcom_config_local']['theme'] = 'OpenPsa2';

if (file_exists(MIDCOM_ROOT . '/../config.inc.php'))
{
    include(MIDCOM_ROOT . '/../config.inc.php');
}
else
{
    //TODO: Hook in an installation wizard here, once it is written
    include(MIDCOM_ROOT . '/../config-default.inc.php');
}

if (! defined('MIDCOM_STATIC_URL'))
{
    define('MIDCOM_STATIC_URL', '/openpsa2-static');
}

// Include the MidCOM environment for running OpenPSA
require(MIDCOM_ROOT . '/midcom.php');

// Start request processing
$_MIDCOM->codeinit();

// Run Midgard1-compatible pseudo-templating
$template = mgd_preparse('<(ROOT)>');
$template_parts = explode('<(content)>', $template);
eval('?>' . $template_parts[0]);
$_MIDCOM->content();
if (isset($template_parts[1]))
{
    eval('?>' . $template_parts[1]);
}
$_MIDCOM->finish();
?>
