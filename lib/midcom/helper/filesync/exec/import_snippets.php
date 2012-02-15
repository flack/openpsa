<?php
$importer = midcom_helper_filesync_importer::create('snippet');
$trusted_ips = $importer->_config->get('trusted_ips');
$ip_sudo = false;

if ($trusted_ips
    && in_array($_SERVER['REMOTE_ADDR'], $trusted_ips))
{
    if (! midcom::get('auth')->request_sudo('midcom.helper.filesync'))
    {
        throw new midcom_error('Failed to acquire SUDO rights. Aborting.');
    }
    $ip_sudo = true;
}
else
{
    midcom::get('auth')->require_admin_user();
}
midcom::get('cache')->content->enable_live_mode();
midcom::get()->header('Content-Type: text/plain');

$importer->import();
echo "Import from {$importer->root_dir} completed\n";
if ($ip_sudo)
{
    midcom::get('auth')->drop_sudo();
}
?>