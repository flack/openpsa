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
    if (class_exists('midgard_reflector_object'))
    {
        // In Midgard2 we can have objects that don't
        // have metadata. These are implicitly purged.
        $ref = new midgard_reflector_object($mgdschema);
        if (!$ref->has_metadata_class($mgdschema))
        {
            continue;
        }
    }
    echo "<h2>Processing class {$mgdschema}</h2>\n";
    flush();

    $total = 0;
    $purged = 0;
    $failed_guids = array();
    do
    {
        $qb = new midgard_query_builder($mgdschema);
        $qb->add_constraint('metadata.deleted', '<>', 0);
        if (!empty($failed_guids))
        {
            $qb->add_constraint('guid', 'NOT IN', $failed_guids);
        }
        $qb->add_constraint('metadata.revised', '<', gmdate('Y-m-d H:i:s', $cut_off));
        $qb->include_deleted();
        $qb->set_limit($chunk_size);
        $objects = $qb->execute();
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
                $failed_guids[] = $obj->guid;
                continue 1;
            }
            $purged++;
        }
    } while (count($objects) > 0);
    echo "Found {$total} objects, purged {$purged} objects, " . sizeof($failed_guids) . " failures\n";
    flush();
}

echo "Done.\n";
echo "</pre>";
ob_start();
?>