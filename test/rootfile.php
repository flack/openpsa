<?php
/**
 * Setup file for running unit tests
 *
 * Usage: phpunit --no-globals-backup ./
 */
$mgd_defaults = array
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

    'self' => '/',
    'prefix' => '',

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
);

$GLOBALS['midcom_config_local'] = array();

// Check that the environment is a working one
if (extension_loaded('midgard2'))
{
    if (!ini_get('midgard.superglobals_compat'))
    {
        throw new Exception('You need to set midgard.superglobals_compat=On in your php.ini to run OpenPSA with Midgard2');
    }

    $GLOBALS['midcom_config_local']['person_class'] = 'openpsa_person';

    // Initialize the $_MIDGARD superglobal
    $_MIDGARD = $mgd_defaults;
}
else if (extension_loaded('midgard'))
{
    if (file_exists(OPENPSA_TEST_ROOT . 'mgd1-connection.inc.php'))
    {
        include(OPENPSA_TEST_ROOT . 'mgd1-connection.inc.php');
    }
    else
    {
        include(OPENPSA_TEST_ROOT . 'mgd1-connection-default.inc.php');
    }
    $_MIDGARD = array_merge($mgd_defaults, $_MIDGARD);
}
else
{
    throw new Exception("OpenPSA requires Midgard PHP extension to run");
}
if (!class_exists('midgard_topic'))
{
    throw new Exception('You need to install OpenPSA MgdSchemas from the "schemas" directory to the Midgard2 schema directory');
}

// Load configuration
if (file_exists(OPENPSA_TEST_ROOT . 'config.inc.php'))
{
    include OPENPSA_TEST_ROOT . 'config.inc.php';
}
else
{
    include OPENPSA_TEST_ROOT . '../config-default.inc.php';
}

// Path to the MidCOM environment
if (!defined('MIDCOM_ROOT'))
{
    define('MIDCOM_ROOT', realpath(OPENPSA_TEST_ROOT . '/../lib'));
}

//Get required helpers
require_once MIDCOM_ROOT . '/../test/utilities/testcase.php';
require_once MIDCOM_ROOT . '/../test/utilities/bootstrap.php';
?>
