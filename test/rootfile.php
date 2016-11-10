<?php
/**
 * Setup file for running unit tests
 */
require_once dirname(__DIR__) . '/vendor/autoload.php';

define('OPENPSA_TEST_ROOT', __DIR__ . DIRECTORY_SEPARATOR);
$GLOBALS['midcom_config_local'] = array();

// Check that the environment is a working one
if (!midcom_connection::setup(dirname(__DIR__) . DIRECTORY_SEPARATOR)) {
    // if we can't connect to a DB, we'll create a new one
    openpsa\installer\midgard2\setup::install(OPENPSA_TEST_ROOT . '__output', 'SQLite');

    /* @todo: This constant is a workaround to make sure the output
     * dir is not deleted again straight away. The proper fix would
    * of course be to delete the old output dir before running the
    * db setup, but this requires further changes in dependent repos
    */
    define('OPENPSA_DB_CREATED', true);
    require_once dirname(__DIR__) . '/tools/bootstrap.php';
    $GLOBALS['midcom_config_local']['midcom_root_topic_guid'] = openpsa_prepare_topics();
}

// Load configuration
if (file_exists(OPENPSA_TEST_ROOT . 'config.inc.php')) {
    include OPENPSA_TEST_ROOT . 'config.inc.php';
} else {
    include OPENPSA_TEST_ROOT . '../config-default.inc.php';
}

// Path to the MidCOM environment
if (!defined('MIDCOM_ROOT')) {
    define('MIDCOM_ROOT', realpath(OPENPSA_TEST_ROOT . '/../lib'));
}

//Get required helpers
require_once OPENPSA_TEST_ROOT . '/utilities/bootstrap.php';
