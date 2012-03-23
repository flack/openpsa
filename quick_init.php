<?php
if (!extension_loaded('midgard2'))
{
    die("Midgard2 is not installed in your PHP environment.\n");
}
if (empty($argv[1]))
{
    $name = prompt('Enter project name', 'openpsa');
}
else
{
    $name = $argv[1];
}

function prompt($message, $default = null)
{
    echo $message;
    if (null !== $default)
    {
        echo ' [' . $default . ']';
    }
    echo ":\n";
    $handle = fopen('php://stdin', 'r');
    $input = trim(fgets($handle));
    if (   empty($input)
        && null !== $default)
    {
        return $default;
    }
    return $input;
}

function check_dir($directory)
{
    if (   !is_dir($directory)
        && !mkdir($directory))
    {
        echo "Failed to create directory " . $directory;
        exit(1);
    }
}

function link_file($path, $target, $link_directory)
{
    check_dir($link_directory);
    if (   !file_exists($link_directory . '/' . $target)
        && !link(dirname(__FILE__) . '/' . $path . '/' . $target, $link_directory . '/' . $target))
    {
        echo "Failed to create link " . $link_directory . '/' . $target;
        exit(1);
    }
}

$config = new midgard_config();
if (!$config->read_file($name, false))
{
    check_dir('/var/lib/' . $name);
    link_file('config', 'midgard_auth_types.xml', '/var/lib/' . $name . '/share');

    // Create a config file
    $config = new midgard_config();
    $config->dbtype = 'SQLite';
    $config->database = $argv[1];
    $config->dbdir = '/var/lib/' . $name;
    $config->blobdir = '/var/lib/' . $name . '/blobs';
    $config->sharedir = '/var/lib/' . $name . '/share';
    $config->vardir = '/var/lib/' . $name;
    $config->cachedir = '/var/cache/' . $name;
    $config->logfilename = '/var/log/' . $name . '/midgard.log';
    $config->loglevel = 'debug';
    if (!$config->save_file('' . $name . '', false))
    {
        echo "Failed to save Midgard2 config file to /etc/midgard2/conf.d\n";
        exit(1);
    }
    echo "Configuration file /etc/midgard2/conf.d/" . $name . " created.\n";
}

// Open a DB connection with the config
$midgard = midgard_connection::get_instance();
if (!$midgard->open_config($config))
{
    echo "Failed to open Midgard database connection to {$name}: " . $midgard->get_error_string() ."\n";
    exit(1);
}

require_once 'tools/bootstrap.php';

openpsa_prepare_database($config);
?>
