<?php
$ip_sudo = midcom::get()->auth->require_admin_or_ip('midcom.services.indexer');

midcom::get()->disable_limits();

while (@ob_end_flush());
echo "<pre>\n";

echo "<h1>Update Class Storage</h1>\n";
flush();

midgard_storage::create_base_storage();
echo "  Created base storage\n";

if (!empty($_GET['type'])) {
    $types = (array) $_GET['type'];
} else {
    $types = midcom_connection::get_schema_types();
}

$start = microtime(true);
foreach ($types as $type) {
    if (midgard_storage::class_storage_exists($type)) {
        midgard_storage::update_class_storage($type);
        echo "  Updated storage for {$type}\n";
    } else {
        midgard_storage::create_class_storage($type);
        echo "  Created storage for {$type}\n";
    }
    flush();
}
echo "Processed " . count($types) . " schema types in " . round(microtime(true) - $start, 2) . "s";
echo "\n\nDone.";
echo "</pre>";
if ($ip_sudo) {
    midcom::get()->auth->drop_sudo();
}
ob_start();