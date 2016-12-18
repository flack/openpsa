<?php
use midgard\portable\driver;
use midgard\portable\storage\connection;

$basedir = dirname(__DIR__);

require_once $basedir . "/vendor/autoload.php";

$schema_dirs = array
(
    $basedir . '/schemas/'
);

$driver = new driver($schema_dirs, $basedir . '/var', '');

// CHANGE PARAMETERS AS REQUIRED:
$db_config = array
(
    'memory' => true,
    'driver' => 'pdo_sqlite',
);

connection::initialize($driver, $db_config, true);