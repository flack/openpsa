<?php
/**
 * Setup file for running unit tests
 */
require_once dirname(__DIR__) . '/vendor/autoload.php';

define('OPENPSA_TEST_ROOT', __DIR__ . DIRECTORY_SEPARATOR);
$GLOBALS['midcom_config_local'] = array();

// Check that the environment is a working one
if (extension_loaded('midgard2'))
{
    $GLOBALS['midcom_config_local']['person_class'] = 'openpsa_person';

    // Open connection
    $midgard = midgard_connection::get_instance();

    // Workaround for https://github.com/midgardproject/midgard-php5/issues/49
    if (   !$midgard->is_connected()
        && $path = ini_get('midgard.configuration_file'))
    {
        $config = new midgard_config();
        $config->read_file_at_path($path);
        $midgard->open_config($config);
    }

    // if we still can't connect to a DB, we'll create a new one
    if (!$midgard->is_connected())
    {
        $installer = openpsa\installer\mgd2setup::get(OPENPSA_TEST_ROOT . '__output');
        $installer->dbtype = 'SQLite';
        $installer->run();

        require_once dirname(__DIR__) . '/tools/bootstrap.php';
        $GLOBALS['midcom_config_local']['midcom_root_topic_guid'] = openpsa_prepare_topics();
    }
}
else if (extension_loaded('midgard'))
{
    if (file_exists(OPENPSA_TEST_ROOT . 'mgd1-connection.inc.php'))
    {
        include OPENPSA_TEST_ROOT . 'mgd1-connection.inc.php';
    }
    else
    {
        include OPENPSA_TEST_ROOT . 'mgd1-connection-default.inc.php';
    }
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
require_once OPENPSA_TEST_ROOT . '/utilities/bootstrap.php';
?>
