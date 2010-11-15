<?php
$_MIDCOM->auth->require_admin_user();

@ini_set('memory_limit', -1);
@ini_set('max_execution_time', 0);

$qb = midcom_db_attachment::new_query_builder();
$qb->add_constraint('sitegroup', '=', $_MIDGARD['sitegroup']);
$qb->add_order('metadata.created', 'DESC');

echo "<p>STARTING...</p>\n";

$atts = $qb->execute_unchecked();

echo "<p>" . count($atts) . " attachments to process...</p>\n";

foreach ($atts as $att)
{
    $att->file_to_cache();;
}

echo "<p>DONE</p>\n";
?>