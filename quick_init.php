<?php
if (count($argv) != 2)
{
    die("Usage: php quick_init.php midgardconffile\n");
}

if (!extension_loaded('midgard2'))
{
    die("Midgard2 is not installed in your PHP environment.\n");
}

// Create a config file
$config = new midgard_config();
$config->dbtype = 'SQLite';
$config->database = $argv[1];
$config->databasedir = '/tmp';
$config->tablecreate = true;
$config->tableupdate = true;
$config->loglevel = 'debug';
if (!$config->save_file($argv[1], false))
{
    die("Failed to save Midgard2 config file");
}
echo "Configuration file /etc/midgard2/conf.d/{$argv[1]} created.\n";

// Open a DB connection with the config
$midgard = midgard_connection::get_instance();
if (!$midgard->open_config($config))
{
    die("Failed to open Midgard database connection to {$argv[1]}: " . $midgard->get_error_string() ."\n");
}

if (!$config->create_blobdir())
{
    die("Failed to create file attachment storage directory to {$config->blobdir}:" . $midgard->get_error_string() . "\n");
}

// Create storage
midgard_storage::create_base_storage();
echo "Database initialized, preparing storage for MgdSchema classes:\n";

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
    echo "  Created storage for {$type}\n";
}
?>
