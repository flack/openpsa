<?php
midcom::get()->auth->require_admin_user();
if (   !class_exists('org_routamc_gallery_photolink')
    || !class_exists('org_routamc_photostream_photo'))
{
    throw new midcom_error('MgdSchemas for the converter could not be found');
}

include MIDCOM_ROOT . '/../tools/gallery_converter.php';

echo "<pre>\n";
$runner = new gallery_converter;
$runner->execute();
echo "</pre>\n";
