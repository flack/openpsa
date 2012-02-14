<?php
// IP Address Checks
$ips = $GLOBALS['midcom_config']['indexer_reindex_allowed_ips'];
$ip_sudo = false;
if (   $ips
    && in_array($_SERVER['REMOTE_ADDR'], $ips))
{
    if (! midcom::get('auth')->request_sudo('midcom.services.indexer'))
    {
        throw new midcom_error('Failed to acquire SUDO rights. Aborting.');
    }
    $ip_sudo = true;
}
else
{
    // Require user to Basic-authenticate for security reasons
    midcom::get('auth')->require_valid_user('basic');
    midcom::get('auth')->require_admin_user();
}

$_MIDCOM->cache->content->enable_live_mode();

_midcom_header('Content-Type: text/plain');

// Ensure cron doesn't timeout
@ini_set('max_execution_time', 0);

// Determine recurrence
$recurrence = MIDCOM_CRON_MINUTE;
if (isset($_GET['type']))
{
    switch ($_GET['type'])
    {
        case 'hour':
            $recurrence = MIDCOM_CRON_HOUR;
            break;

        case 'day':
            $recurrence = MIDCOM_CRON_DAY;
            break;
    }
}

// Instantiate cron service and run
$cron = new midcom_services_cron($recurrence);
$cron->execute();

if ($ip_sudo)
{
    midcom::get('auth')->drop_sudo();
}
?>