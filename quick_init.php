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

if (!$config->create_blobdir())
{
    echo "Failed to create file attachment storage directory to {$config->blobdir}:" . $midgard->get_error_string() . "\n";
    exit(1);
}

// Create storage
if (!midgard_storage::create_base_storage())
{
    if ($midgard->get_error_string() != 'MGD_ERR_OK')
    {
        echo "Failed to create base database structures" . $midgard->get_error_string() . "\n";
        exit(1);
    }
}
else
{
    echo "Database initialized, preparing storage for MgdSchema classes:\n";
}

$re = new ReflectionExtension('midgard2');
$classes = $re->getClasses();
foreach ($classes as $refclass)
{
    $parent_class = $refclass->getParentClass();
    if (!$parent_class)
    {
        continue;
    }
    if ($parent_class->getName() != 'midgard_object')
    {
        continue;
    }
    $type = $refclass->getName();
            
    midgard_storage::create_class_storage($type);
    midgard_storage::update_class_storage($type);
    echo "  Created storage for {$type}\n";
}
?>
