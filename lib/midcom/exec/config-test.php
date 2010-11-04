<?php
if (! exec ('which which'))
{
    _midcom_stop_request("The 'which' utility cannot be found. It is required for configuration-testing. Aborting.");
}
$_MIDCOM->auth->require_admin_user();
?>
<html>
<head><title>MidCOM Configuration Test</title></head>
<body>

<h1>MidCOM Configuration Test</h1>

<p>This page performs a few tests on the MidCOM configuration.</p>

<table border="1" cellspacing="0" cellpadding="3">
  <tr>
    <th>Test</th>
    <th>Result</th>
    <th>Recommendations</th>
  </tr>
<?php

define('OK', 0);
define('WARNING', 1);
define('ERROR', 2);

/**
 * Check if a file exists in the include path
 *
 * @version      1.2.0
 * @author       Aidan Lister <aidan@php.net>
 * @param        string     $file       Name of the file to look for
 * @return       boolean       TRUE if the file exists, FALSE if it does not
 */
function midcom_file_exists_incpath ($file)
{
    $paths = explode(PATH_SEPARATOR, get_include_path());

    foreach ($paths as $path)
    {
        // Formulate the absolute path
        $fullpath = $path . DIRECTORY_SEPARATOR . $file;
        // Check it
        if (file_exists($fullpath))
        {
            return true;
        }
    }
    return false;
}

function println($testname, $result_code, $recommendations = '&nbsp;')
{
    echo "  <tr>\n";
    echo "    <td>{$testname}</td>\n";
    switch ($result_code)
    {
        case OK:
            echo "    <td style='color: green;'>OK</td>\n";
            break;

        case WARNING:
            echo "    <td style='color: orange;'>WARNING</td>\n";
            break;

        case ERROR:
            echo "    <td style='color: red;'>ERROR</td>\n";
            break;

        default:
            _midcom_stop_request("Unknown error code {$result_code}. Aborting.");
    }

    echo "    <td>{$recommendations}</td>\n";
    echo "  </tr>\n";
}

function ini_get_filesize($setting)
{
    $result = ini_get($setting);
    $last_char = substr($result, -1);
    if ($last_char == 'M')
    {
        $result = substr($result, 0, -1) * 1024 * 1024;
    }
    else if ($last_char == 'K')
    {
        $result = substr($result, 0, -1) * 1024;
    }
    else if ($last_char == 'G')
    {
        $result = substr($result, 0, -1) * 1024 * 1024 * 1024;
    }
    return $result;
}

function ini_get_boolean($setting)
{
    $result = ini_get($setting);
    if ($result == false || $result == "Off" || $result == "off" || $result == "" || $result == "0")
    {
        return false;
    }
    else
    {
        return true;
    }
}

function check_for_include_file($filename)
{
    return midcom_file_exists_incpath($filename);
}

function println_check_for_include_file($filename, $testname, $fail_code, $fail_recommendations)
{
    if (check_for_include_file($filename))
    {
        println($testname, OK);
    }
    else
    {
        println($testname, $fail_code, $fail_recommendations);
    }
}

function check_for_utility ($name, $testname, $fail_code, $fail_recommendations, $ok_notice = '&nbsp;')
{
    $executable = $GLOBALS['midcom_config']["utility_{$name}"];
    $testname = "External Utility: {$testname}";
    if (is_null($executable))
    {
        println($testname, $fail_code, "The path to the utility {$name} is not configured. {$fail_recommendations}");
    }
    else
    {
        exec ("which {$executable}", $output, $exitcode);
        if ($exitcode == 0)
        {
            println($testname, OK, $ok_notice);
        }
        else
        {
            println($testname, $fail_code, "The utility {$name} is not correctly configured: File ({$executable}) not found. {$fail_recommendations}");
        }
    }
}

function check_rcs()
{
    $config = $GLOBALS['midcom_config'];
    if (array_key_exists('midcom_services_rcs_enable', $config) && $config['midcom_services_rcs_enable'])
    {
        if (!is_writable($config['midcom_services_rcs_root']))
        {
            println("MidCOM RCS", ERROR, "You must make the directory <b>{$config['midcom_services_rcs_root']}</b> writable by your webserver!");
        }
        else if (!is_executable($config['midcom_services_rcs_bin_dir'] . "/ci"))
        {
            println("MidCOM RCS", ERROR, "You must make <b>{$config['midcom_services_rcs_bin_dir']}/ci</b> executable by your webserver!");
        }
        else
        {
            println("MidCOM RCS", OK);
        }
    }
    else
    {
            println("MidCOM RCS", WARNING, "The MidCOM RCS service is disabled.");
    }
}

// Some helpers
$i18n = $_MIDCOM->get_service('i18n');

if (version_compare(mgd_version(), '8.09.2', '<'))
{
    println('Midgard Version', ERROR, 'Midgard 8.09.2 or greater is required for this version of MidCOM.');
}
else
{
    println('Midgard Version', OK);
}

$version = phpversion();
if (version_compare($version, '5.1.0', '<'))
{
    println('PHP Version', ERROR, 'PHP 5.1.0 or greater is required for MidCOM.');
}
else
{
    println('PHP Version', OK);
}

// Available Memory for PHP

$cur_limit = ini_get_filesize('memory_limit');
if ($cur_limit >= (40 * 1024 * 1024))
{
    println('PHP Setting: memory_limit', OK);
}
else
{
    println('PHP Setting: memory_limit', ERROR, "MidCOM requires a minimum memory limit of 40 MB to operate correctly. Smaller amounts will lead to PHP Errors. Detected limit was {$cur_limit}.");
}

// Register Globals
if (array_key_exists('midcom_site', $GLOBALS))
{
    if (ini_get_boolean('register_globals'))
    {
        println('PHP Setting: register_globals', OK);
    }
    else
    {
        println('PHP Setting: register_globals', ERROR, 'register_globals is required for MidCOM-Template usage, which depends on NemeinAuthentication.');
    }
}
else
{
    if (ini_get_boolean('register_globals'))
    {
        println('PHP Setting: register_globals', WARNING, 'register_globals is enabled, it is recommended to turn this off for security reasons (unless you rely on Nemein Authentication somewhere).');
    }
    else
    {
        println('PHP Setting: register_globals', OK);
    }
}

// Track Errors.
if (ini_get_boolean('track_errors'))
{
    println('PHP Setting: track_errors', OK);
}
else
{
    println('PHP Setting: track_errors', WARNING, 'track_errors is disabled, it is strongly suggested to be activated as this allows the framework to handle more errors gracefully.');
}

// Upload File Size
$upload_limit = ini_get_filesize('upload_max_filesize');
if ($upload_limit >= (50 * 1024 * 1024))
{
    println('PHP Setting: upload_max_filesize', OK);
}
else
{
    println('PHP Setting: upload_max_filesize',
        WARNING, "To make bulk uploads (for exampe in the Image Gallery) useful, you should increase the Upload limit to something above 50 MB. (Current setting: {$upload_limit})");
}

$post_limit = ini_get_filesize('post_max_size');
if ($post_limit >= $upload_limit)
{
    println('PHP Setting: post_max_size', OK);
}
else
{
    println('PHP Setting: post_max_size', WARNING, 'post_max_size should be larger then upload_max_filesize, as both limits apply during uploads.');
}

// Magic Quotes
if (! ini_get_boolean('magic_quotes_gpc'))
{
    println('PHP Setting: magic_quotes_gpc', OK);
}
else
{
    println('PHP Setting: magic_quotes_gpc', ERROR, 'Magic Quotes must be turned off, Midgard/MidCOM does this explicitly where required.');
}
if (! ini_get_boolean('magic_quotes_runtime'))
{
    println('PHP Setting: magic_quotes_runtime', OK);
}
else
{
    println('PHP Setting: magic_quotes_runtime', ERROR, 'Magic Quotes must be turned off, Midgard/MidCOM does this explicitly where required.');
}


// iconv must be available.
if (! function_exists('iconv'))
{
    println('iconv', ERROR, 'The PHP iconv module is required for MidCOM operation.');
}
else
{
    println('iconv', OK);
}

// memcached is recommended
if (! class_exists('Memcache'))
{
    println('Memcache', WARNING, 'The PHP Memcache module is recommended for efficient MidCOM operation.');
}
else
{
    if ($GLOBALS['midcom_config']['cache_module_memcache_backend'] == '')
    {
        println('Memcache', WARNING, 'The PHP Memcache module is recommended for efficient MidCOM operation. It is available but is not set to be in use.');
    }
    else
    {
        if (midcom_services_cache_backend_memcached::$memcache_operational)
        {
            println('Memcache', OK);
        }
        else
        {
            println('Memcache', ERROR, "The PHP Memcache module is available and set to be in use, but it cannot be connected to.");
        }
    }
}

// bytecode cache is recommended
if(ini_get("apc.enabled") == "1")
{
    println("PHP bytecode cache", OK, "APC is enabled");
}
else if(ini_get("eaccelerator.enable") == "1")
{
    println("PHP bytecode cache", OK, "eAccelerator is enabled");
}
else
{
    println("PHP bytecode cache", WARNING, "A PHP bytecode cache is recommended for efficient MidCOM operation");
}

// Multibyte String Functions

if (! function_exists('mb_strlen'))
{
    println('Multi-Byte String functions', ERROR, 'The Multi-Byte String functions are unavailable, they are required for MidCOM operation.');
}
else
{
    println('Multi-Byte String functions', OK);
}

// EXIF Reading
if (! function_exists('read_exif_data'))
{
    println('EXIF reader', WARNING, 'PHP-EXIF is not available. It required for proper operation of Image Gallery components.');
}
else
{
    println('EXIF reader', OK);
}

// ImageMagick
$cmd = "{$GLOBALS['midcom_config']['utility_imagemagick_base']}identify -version";
exec ($cmd, $output, $result);
if ($result !== 0 && $result !== 1)
{
    println('External Utility: ImageMagick', ERROR, 'The existence ImageMagick toolkit could not be verified, it is required for all kinds of image processing in MidCOM.'); 
}
else
{
    println('External Utility: ImageMagick', OK);
}

// Other utilities
check_for_utility('find', 'find', WARNING, 'The find utility is required for bulk upload processing in the image galleries, you should install it if you plan to deploy Image Galleries.');
check_for_utility('file', 'file', ERROR, 'The file utility is required for all kindes of Mime-Type identifications. You have to install it for proper MidCOM operations.');
check_for_utility('unzip', 'unzip', WARNING, 'The unzip utility is required for bulk upload processing in the image galleries, you should install it if you plan to deploy Image Galleries.');
check_for_utility('tar', 'tar', WARNING, 'The tar utility is required for bulk upload processing in the image galleries, you should install it if you plan to deploy Image Galleries.');
check_for_utility('gzip', 'gzip', WARNING, 'The gzip utility is required for bulk upload processing in the image galleries, you should install it if you plan to deploy Image Galleries.');
check_for_utility('jpegtran', 'jpegtran', WARNING, 'The jpegtran utility is used for lossless JPEG operations, even though ImageMagick can do the same conversions, the lossless features provided by this utility are used where appropriate, so its installation is recommended unless it is known to cause problems.', 'The jpegtran utility is used for lossless rotations of JPEG images. If there are problems with image rotations, disabling jpegtran, which will cause ImageMagick to be used instead, probably helps.');

check_for_utility('diff','diff',WARNING, 'diff is needed by the versioning library‥ You can also use the pear library Text_Diff');

if ($GLOBALS['midcom_config']['indexer_backend'])
{
    check_for_utility('catdoc', 'catdoc', ERROR, 'Catdoc is required to properly index Microsoft Word documents. It is strongly recommended to install it, otherwise Word documents will be indexed as binary files.');
    check_for_utility('pdftotext', 'pdftotext', ERROR, 'pdftotext is required to properly index Adobe PDF documents. It is strongly recommended to install it, otherwise PDF documents will be indexed as binary files.');
    check_for_utility('unrtf', 'unrtf', ERROR, 'unrtf is required to properly index Rich Text Format documents. It is strongly recommended to install it, otherwise RTF documents will be indexed as binary files.');
}

// Validate the Cache Base Directory.
if  (! is_dir($GLOBALS['midcom_config']['cache_base_directory']))
{
    println('MidCOM cache base directory', ERROR, "The configured MidCOM cache base directory ({$GLOBALS['midcom_config']['cache_base_directory']}) does not exist or is not a directory. You have to create it as a directory writable by the Apache user.");
}
else if (! is_writable($GLOBALS['midcom_config']['cache_base_directory']))
{
    println('MidCOM cache base directory', ERROR, "The configured MidCOM cache base directory ({$GLOBALS['midcom_config']['cache_base_directory']}) is not writable by the Apache user. You have to create it as a directory writable by the Apache user.");
}
else
{
    println('MidCOM cache base directory', OK);
}


?>
</table>
</body>
