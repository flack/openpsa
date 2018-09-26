<?php
midcom::get()->auth->require_admin_user();
midcom::get()->disable_limits();
ob_implicit_flush(true);

if (!isset($_GET['days'])) {
    $grace_days = midcom::get()->config->get('cron_purge_deleted_after');
} else {
    $grace_days = $_GET['days'];
}
$handler = new midcom_cron_purgedeleted;
$handler->set_cutoff((int) $grace_days);

echo "<h1>Purge deleted objects</h1>\n";
echo "<p>Current grace period is {$grace_days} days, use ?days=x to set to other value</p>\n";

echo "<pre>\n";

foreach ($handler->get_classes() as $mgdschema) {
    echo "<p><strong>Processing class {$mgdschema}</strong>\n";
    $stats = $handler->process_class($mgdschema);

    foreach ($stats['errors'] as $error) {
        echo '  ERROR:' . $error . "\n";
    }
    if ($stats['found'] > 0) {
        echo "  Purged {$stats['purged']} deleted objects, " . count($stats['errors']) . " failures\n";
    } else {
        echo "  No matching objects found\n";
    }

    echo "<p>";
}

echo "Done.\n";
echo "</pre>";
