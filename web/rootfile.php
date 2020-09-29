<?php
require_once dirname(__DIR__) . '/vendor/autoload.php';
$GLOBALS['midcom_config_local'] = [];

// Check that the environment is a working one
midcom_connection::setup(dirname(__DIR__) . DIRECTORY_SEPARATOR);

$GLOBALS['midcom_config_local']['theme'] = 'OpenPsa2';

if (file_exists(dirname(__DIR__) . '/config.inc.php')) {
    include dirname(__DIR__) . '/config.inc.php';
} else {
    include dirname(__DIR__) . '/config-default.inc.php';
}

if (! defined('MIDCOM_STATIC_URL')) {
    define('MIDCOM_STATIC_URL', '/midcom-static');
}

if (file_exists(dirname(__DIR__) . '/themes/' . $GLOBALS['midcom_config_local']['theme'] . '/config.inc.php')) {
    include dirname(__DIR__) . '/themes/' . $GLOBALS['midcom_config_local']['theme'] . '/config.inc.php';
}

// Start request processing
$midcom = midcom::get();
$midcom->codeinit();
