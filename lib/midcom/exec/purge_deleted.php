<?php
$_MIDCOM->auth->require_valid_user('basic');
$_MIDCOM->auth->require_admin_user();
midcom::get()->disable_limits();

$chunk_size = 1000;

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

while(@ob_end_flush());
echo "<pre>\n";
flush();
foreach (midcom_connection::get_schema_types() as $mgdschema)
{
    if ($mgdschema == '__midgard_cache')
    {
        continue;
    }
    echo "<h2>Processing class {$mgdschema}</h2>";
    flush();

    $offset = 0;
    $total = 0;
    $purged = 0;
    do
    {
        $qb = new midgard_query_builder($mgdschema);
        $qb->add_constraint('metadata.deleted', '<>', 0);
        $qb->add_constraint('metadata.revised', '<', gmdate('Y-m-d H:i:s', $cut_off));
        $qb->include_deleted();
        $qb->set_limit($chunk_size);
        $qb->set_offset($offset);
        $offset += $chunk_size;
        $objects = $qb->execute();
        unset($qb);
        if (!is_array($objects))
        {
            echo "FATAL QB ERROR\n";
            continue;
        }
        $total += count($objects);
        foreach ($objects as $obj)
        {
            if (!$obj->purge())
            {
                echo "ERROR: Failed to purge <tt>{$obj->guid}</tt>, deleted: {$obj->metadata->deleted},  revised: {$obj->metadata->revised}. errstr: " . midcom_connection::get_error_string() . "\n";
                continue 1;
            }
            $purged++;
        }
    } while (count($objects) > 0);
    echo "Found {$total} objects, purged {$purged} objects\n";
    flush();
}

echo "Done.\n";
echo "</pre>";
ob_start();
?>