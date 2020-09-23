<?php
midcom::get()->auth->require_admin_user();

midcom::get()->disable_limits();

$qb = midcom_db_attachment::new_query_builder();
$qb->add_order('metadata.created', 'DESC');

echo "<p>STARTING...</p>\n";

$atts = $qb->execute_unchecked();

echo "<p>" . count($atts) . " attachments to process...</p>\n";

echo "<pre>\n";
foreach ($atts as $att) {
    $att->file_to_cache();
}
echo "</pre>\n";

echo "<p>DONE</p>\n";
