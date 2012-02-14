<?php
midcom::get('auth')->require_valid_user('basic');
midcom::get('auth')->require_admin_user();
midcom::get('cache')->content->enable_live_mode();
$_MIDCOM->header('Content-Type: text/plain');
$exporter = midcom_helper_filesync_exporter::create('style');
// TODO: be smarter
if (!isset($_GET['root']))
{
    $exporter->export();
}
else
{
    $exporter->read_root($_GET['root']);
}
echo "Export to {$exporter->root_dir} completed\n";
?>