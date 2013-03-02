<?php
if (! exec ('which which'))
{
    throw new midcom_error("The 'which' utility cannot be found.");
}
midcom::get('auth')->require_admin_user();
?>
<html>
<head><title>Configuration Test</title></head>
<style type="text/css">
tr.test th
{
	text-align: left;
	white-space: nowrap;
	font-weight: normal
}
</style>
<body>

<h1>Configuration Test</h1>

<p>This page performs a few tests on the system configuration.</p>

<table border="1" cellspacing="0" cellpadding="2">
  <tr>
    <th>Test</th>
    <th>Result</th>
    <th>Recommendations</th>
  </tr>
<?php

$runner = new midcom_config_test();

$runner->check_midcom();
$runner->check_php();
$runner->print_header('External Utilities');
// ImageMagick
$cmd = midcom::get('config')->get('utility_imagemagick_base') . "identify -version";
exec ($cmd, $output, $result);
if ($result !== 0 && $result !== 1)
{
    $runner->println('ImageMagick', midcom_config_test::ERROR, 'The existence ImageMagick toolkit could not be verified, it is required for all kinds of image processing in MidCOM.');
}
else
{
    $runner->println('ImageMagick', midcom_config_test::OK);
}

// Other utilities
$runner->check_for_utility('find', midcom_config_test::WARNING, 'The find utility is required for bulk upload processing in the image galleries, you should install it if you plan to deploy Image Galleries.');
$runner->check_for_utility('file', midcom_config_test::ERROR, 'The file utility is required for all kindes of Mime-Type identifications. You have to install it for proper MidCOM operations.');
$runner->check_for_utility('unzip', midcom_config_test::WARNING, 'The unzip utility is required for bulk upload processing in the image galleries, you should install it if you plan to deploy Image Galleries.');
$runner->check_for_utility('tar', midcom_config_test::WARNING, 'The tar utility is required for bulk upload processing in the image galleries, you should install it if you plan to deploy Image Galleries.');
$runner->check_for_utility('gzip', midcom_config_test::WARNING, 'The gzip utility is required for bulk upload processing in the image galleries, you should install it if you plan to deploy Image Galleries.');
$runner->check_for_utility('jpegtran', midcom_config_test::WARNING, 'The jpegtran utility is used for lossless JPEG operations, even though ImageMagick can do the same conversions, the lossless features provided by this utility are used where appropriate, so its installation is recommended unless it is known to cause problems.', 'The jpegtran utility is used for lossless rotations of JPEG images. If there are problems with image rotations, disabling jpegtran, which will cause ImageMagick to be used instead, probably helps.');

$runner->check_for_utility('diff', midcom_config_test::WARNING, 'diff is needed by the versioning library‥ You can also use the pear library Text_Diff');

if (midcom::get('config')->get('indexer_backend'))
{
    $runner->check_for_utility('catdoc', midcom_config_test::ERROR, 'Catdoc is required to properly index Microsoft Word documents. It is strongly recommended to install it, otherwise Word documents will be indexed as binary files.');
    $runner->check_for_utility('pdftotext', midcom_config_test::ERROR, 'pdftotext is required to properly index Adobe PDF documents. It is strongly recommended to install it, otherwise PDF documents will be indexed as binary files.');
    $runner->check_for_utility('unrtf', midcom_config_test::ERROR, 'unrtf is required to properly index Rich Text Format documents. It is strongly recommended to install it, otherwise RTF documents will be indexed as binary files.');
}
?>
</table>
</body>
