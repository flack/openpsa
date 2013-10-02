<?php
midcom::get('auth')->require_admin_user();
midcom::get()->disable_limits();

$chunk_size = 1000;

if (!isset($_GET['days']))
{
    $grace_days = midcom::get('config')->get('cron_purge_deleted_after');
}
else
{
    $grace_days = $_GET['days'];
}
$handler = new midcom_cron_purgedeleted;
$handler->set_cutoff((int) $grace_days);
$handler->quiet = false;

echo "<h1>Purge deleted objects</h1>\n";
echo "<p>Current grace period is {$grace_days} days, use ?days=x to set to other value</p>\n";

while(@ob_end_flush());
echo "<pre>\n";
flush();
$handler->execute();
echo "Done.\n";
echo "</pre>";
ob_start();
?>