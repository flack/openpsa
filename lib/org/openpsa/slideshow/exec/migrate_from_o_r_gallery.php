<?php
midcom::get('auth')->require_admin_user();
if (   !class_exists('org_routamc_gallery_photolink')
    || !class_exists('org_routamc_photostream_photo'))
{
    throw new midcom_error('MgdSchemas for the converter could not be found');
}

include MIDCOM_ROOT . '/../tools/gallery_converter.php';


if (!empty($_GET['node']))
{
    midcom::get()->disable_limits();
    while(@ob_end_flush());
    echo "<pre>\n";
    $runner = new gallery_converter((int) $_GET['node']);
    $runner->execute();
    echo "</pre>\n";
}
else
{ ?>
    <form method="get" action="">
    Enter node ID: <input type="text" name="node" />
    <input type="submit" value="Go" />
    </form>
<?php } ?>