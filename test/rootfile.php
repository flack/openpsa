<?php
/**
 * Setup file for running unit tests
 */
require __DIR__ . '/utilities/autoload.php';

openpsa_test_setup(__DIR__);

$GLOBALS['midcom_config_local'] = [];

// Check that the environment is a working one
if (!midcom_connection::setup(dirname(__DIR__) . DIRECTORY_SEPARATOR)) {
    // if we can't connect to a DB, we'll create a new one
    openpsa\installer\midgard2\setup::install(OPENPSA2_UNITTEST_OUTPUT_DIR, 'SQLite');

    require_once dirname(__DIR__) . '/tools/bootstrap.php';
    $GLOBALS['midcom_config_local']['midcom_root_topic_guid'] = openpsa_prepare_topics();
}

// Load configuration
if (file_exists(OPENPSA_TEST_ROOT . 'config.inc.php')) {
    include OPENPSA_TEST_ROOT . 'config.inc.php';
} else {
    include OPENPSA_TEST_ROOT . '../config-default.inc.php';
}

//Get required helpers
require OPENPSA_TEST_ROOT . '/utilities/bootstrap.php';
