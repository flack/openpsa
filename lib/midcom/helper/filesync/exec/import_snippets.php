<?php
$importer = midcom_helper_filesync_importer::create('snippet');
$trusted_ips = $importer->_config->get('trusted_ips');
$ip_sudo = false;

if ($trusted_ips
    && in_array($_SERVER['REMOTE_ADDR'], $trusted_ips))
{
    if (! $_MIDCOM->auth->request_sudo('midcom.helper.filesync'))
    {
        throw new midcom_error('Failed to acquire SUDO rights. Aborting.');
    }
    $ip_sudo = true;
}
else
{
    $_MIDCOM->auth->require_admin_user();
}
$_MIDCOM->cache->content->enable_live_mode();
$_MIDCOM->header('Content-Type: text/plain');

$importer->import();
echo "Import from {$importer->root_dir} completed\n";
if ($ip_sudo)
{
    $_MIDCOM->auth->drop_sudo();
}
?>