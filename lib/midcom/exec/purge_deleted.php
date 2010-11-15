<?php
$_MIDCOM->auth->require_valid_user('basic');
$_MIDCOM->auth->require_admin_user();

if (!isset($_GET['days']))
{
    $grace_days = 25;
}
else
{
    $grace_days = $_GET['days'];
}

echo "<h1>Purge deleted objects</h1>\n";
echo "<p>Current grace period is {$grace_days} days, use ?days=x to set to other value</p>\n";

// 1 second before midnight $grace_days ago
$cut_off = mktime(23, 59, 59, date('n'), date('j')-$grace_days, date('Y'));
foreach ($_MIDGARD['schema']['types'] as $mgdschema => $dummy)
{
    if ($mgdschema == '__midgard_cache')
    {
        continue;
    }
    echo "<h2>Processing class {$mgdschema}</h2>\n";
    $qb = new midgard_query_builder($mgdschema);
    $qb->add_constraint('metadata.deleted', '<>', 0);
    $qb->add_constraint('metadata.revised', '<', gmdate('Y-m-d H:i:s', $cut_off));
    $qb->include_deleted();
    $objects = $qb->execute();
    unset($qb);
    if (!is_array($objects))
    {
        echo "FATAL QB ERROR<br/>\n";
        continue;
    }
    $found = count($objects);
    $purged = 0;
    foreach ($objects as $obj)
    {
        if (!$obj->purge())
        {
            echo "ERROR: Failed to purge <tt>{$obj->guid}</tt>, deleted: {$obj->metadata->deleted},  revised: {$obj->metadata->revised}. errstr: " . midcom_connection::get_error_string() . "<br/>\n";
            continue;
        }
        $purged++;
    }
    echo "Found {$found} objects, purged {$purged} objects<br/>\n";
}

echo "<br/><br/>Done.";

?>