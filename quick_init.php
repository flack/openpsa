<?php
if (!extension_loaded('midgard2'))
{
    die("Midgard2 is not installed in your PHP environment.\n");
}

$config = new midgard_config();
if (!$config->read_file('openpsa2', false))
{
    // Create a config file
    $config = new midgard_config();
    $config->dbtype = 'SQLite';
    $config->database = $argv[1];
    $config->dbdir = '/var/lib/openpsa2';
    $config->blobdir = '/var/lib/openpsa2/blobs';
    $config->sharedir = '/usr/share/openpsa2';
    $config->vardir = '/var/lib/openpsa2';
    $config->cachedir = '/var/cache/openpsa2';
    $config->logfilename = '/var/log/openpsa2/midgard.log';
    $config->loglevel = 'debug';
    if (!$config->save_file('openpsa2', false))
    {
        echo "Failed to save Midgard2 config file to /etc/midgard2/conf.d\n";
        exit(1);
    }
    echo "Configuration file /etc/midgard2/conf.d/openpsa2 created.\n";
}

// Open a DB connection with the config
$midgard = midgard_connection::get_instance();
if (!$midgard->open_config($config))
{
    echo "Failed to open Midgard database connection to {$argv[1]}: " . $midgard->get_error_string() ."\n";
    exit(1);
}

require_once 'tools/bootstrap.php';

openpsa_prepare_database($config);
?>
