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
define('MIDCOM_STATIC_URL', '/openpsa2-static');
define('OPENPSA2_PREFIX', dirname($_SERVER['SCRIPT_NAME']));
define('OPENPSA2_THEME_ROOT', MIDCOM_ROOT . '/../themes/');

header('Content-Type: text/html; charset=utf-8');

// Include Midgard1 compatibility APIs needed for running OpenPSA under Midgard2
require(MIDCOM_ROOT . '/ragnaroek-compat.php');

midgard_connection::get_instance()->set_loglevel('warn');

// Initialize the $_MIDGARD superglobal
openpsa_prepare_superglobal();

$GLOBALS['midcom_config_local'] = array();
$GLOBALS['midcom_config_local']['person_class'] = 'openpsa_person';

if (file_exists(MIDCOM_ROOT . '/../config.inc.php'))
{
    include(MIDCOM_ROOT . '/../config.inc.php');
}
else
{
    //TODO: Hook in an installation wizard here, once it is written
    $GLOBALS['midcom_config_local']['log_level'] = 5;
    $GLOBALS['midcom_config_local']['log_filename'] = dirname(midgard_connection::get_instance()->config->logfilename) . '/midcom.log';
    $GLOBALS['midcom_config_local']['midcom_root_topic_guid'] = openpsa_prepare_topics();
    $GLOBALS['midcom_config_local']['auth_backend_simple_cookie_secure'] = false;
}

openpsa_parse_url();

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
