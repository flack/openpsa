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

$runner = new midcom_config_test();

$runner->check_midcom();
$runner->check_php();

// memcached is recommended
if (! class_exists('Memcache'))
{
    $runner->println('Memcache', WARNING, 'The PHP Memcache module is recommended for efficient MidCOM operation.');
}
else
{
    if ($GLOBALS['midcom_config']['cache_module_memcache_backend'] == '')
    {
        $runner->println('Memcache', WARNING, 'The PHP Memcache module is recommended for efficient MidCOM operation. It is available but is not set to be in use.');
    }
    else
    {
        if (midcom_services_cache_backend_memcached::$memcache_operational)
        {
            $runner->println('Memcache', OK);
        }
        else
        {
            $runner->println('Memcache', ERROR, "The PHP Memcache module is available and set to be in use, but it cannot be connected to.");
        }
    }
}

// bytecode cache is recommended
if(ini_get("apc.enabled") == "1")
{
    $runner->println("PHP bytecode cache", OK, "APC is enabled");
}
else if(ini_get("eaccelerator.enable") == "1")
{
    $runner->println("PHP bytecode cache", OK, "eAccelerator is enabled");
}
else
{
    $runner->println("PHP bytecode cache", WARNING, "A PHP bytecode cache is recommended for efficient MidCOM operation");
}

// EXIF Reading
if (! function_exists('read_exif_data'))
{
    $runner->println('EXIF reader', WARNING, 'PHP-EXIF is not available. It required for proper operation of Image Gallery components.');
}
else
{
    $runner->println('EXIF reader', OK);
}

// ImageMagick
$cmd = "{$GLOBALS['midcom_config']['utility_imagemagick_base']}identify -version";
exec ($cmd, $output, $result);
if ($result !== 0 && $result !== 1)
{
    $runner->println('External Utility: ImageMagick', ERROR, 'The existence ImageMagick toolkit could not be verified, it is required for all kinds of image processing in MidCOM.');
}
else
{
    $runner->println('External Utility: ImageMagick', OK);
}

// Other utilities
$runner->check_for_utility('find', WARNING, 'The find utility is required for bulk upload processing in the image galleries, you should install it if you plan to deploy Image Galleries.');
$runner->check_for_utility('file', ERROR, 'The file utility is required for all kindes of Mime-Type identifications. You have to install it for proper MidCOM operations.');
$runner->check_for_utility('unzip', WARNING, 'The unzip utility is required for bulk upload processing in the image galleries, you should install it if you plan to deploy Image Galleries.');
$runner->check_for_utility('tar', WARNING, 'The tar utility is required for bulk upload processing in the image galleries, you should install it if you plan to deploy Image Galleries.');
$runner->check_for_utility('gzip', WARNING, 'The gzip utility is required for bulk upload processing in the image galleries, you should install it if you plan to deploy Image Galleries.');
$runner->check_for_utility('jpegtran', WARNING, 'The jpegtran utility is used for lossless JPEG operations, even though ImageMagick can do the same conversions, the lossless features provided by this utility are used where appropriate, so its installation is recommended unless it is known to cause problems.', 'The jpegtran utility is used for lossless rotations of JPEG images. If there are problems with image rotations, disabling jpegtran, which will cause ImageMagick to be used instead, probably helps.');

$runner->check_for_utility('diff', WARNING, 'diff is needed by the versioning library‥ You can also use the pear library Text_Diff');

if ($GLOBALS['midcom_config']['indexer_backend'])
{
    $runner->check_for_utility('catdoc', ERROR, 'Catdoc is required to properly index Microsoft Word documents. It is strongly recommended to install it, otherwise Word documents will be indexed as binary files.');
    $runner->check_for_utility('pdftotext', ERROR, 'pdftotext is required to properly index Adobe PDF documents. It is strongly recommended to install it, otherwise PDF documents will be indexed as binary files.');
    $runner->check_for_utility('unrtf', ERROR, 'unrtf is required to properly index Rich Text Format documents. It is strongly recommended to install it, otherwise RTF documents will be indexed as binary files.');
}
?>
</table>
</body>
