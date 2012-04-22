<?php
midcom::get('auth')->require_admin_user();
midcom::get()->disable_limits();

//Note: You have to run this multiple times, offset does not take deletions into account
$chunk_size = 1000;
$offset = 0;
$valid_persons = array();
$valid_targets = array();
$invalid_targets = array();

$person_qb = midcom_db_person::new_query_builder();
$person_qb->include_deleted();
$persons = $person_qb->execute();

foreach ($persons as $person)
{
    $valid_persons[] = $person->id;
    $valid_targets[] = $person->guid;
}
unset($persons);

while(@ob_end_flush());
echo "<pre>\n";
flush();

do
{
    $qb = midcom_helper_activitystream_activity_dba::new_query_builder();
    $qb->add_constraint('actor', 'NOT IN', $valid_persons);
    $qb->add_constraint('actor', '<>', 0);
    $qb->set_limit($chunk_size);
    $results = $qb->execute();
    echo "Deleting " . sizeof($results) . " entries for purged persons\n";
    flush();
    foreach ($results as $result)
    {
        if (!$result->delete())
        {
            echo 'ERROR: Deleting entry ' . $result->guid . ' failed: ' . midcom_connection::get_error_string() . " \n";
        }
        else
        {
            $result->purge();
        }
    }

} while (sizeof($results) > 0);

do
{
    $qb = midcom_helper_activitystream_activity_dba::new_query_builder();
    $qb->add_constraint('target', 'NOT IN', $valid_targets);
    $qb->set_limit($chunk_size);
    $qb->set_offset($offset);
    $results = $qb->execute();

    foreach ($results as $result)
    {
        if (in_array($result->target, $valid_targets))
        {
            continue;
        }
        if (in_array($result->target, $invalid_targets))
        {
            continue;
        }
        try
        {
            $object = midcom::get('dbfactory')->get_object_by_guid($result->target);
            $valid_targets[] = $object->guid;
        }
        catch (midcom_error $e)
        {
            if (midcom_connection::get_error() === MGD_ERR_OBJECT_DELETED)
            {
                $valid_targets[] = $result->target;
            }
            else if (   midcom_connection::get_error() === MGD_ERR_OBJECT_PURGED
                     || midcom_connection::get_error() === MGD_ERR_NOT_EXISTS)
            {
                $invalid_targets[] = $result->target;
                $delete_qb = midcom_helper_activitystream_activity_dba::new_query_builder();
                $delete_qb->add_constraint('target', '=', $result->target);
                $to_delete = $delete_qb->execute();
                if (sizeof($to_delete) == 0)
                {
                    continue;
                }
                echo "Deleting " . sizeof($to_delete) . " entries for purged target " . $result->target . " \n";
                flush();
                foreach ($to_delete as $entry)
                {
                    if (!$entry->delete())
                    {
                        echo 'ERROR: Deleting entry ' . $entry->guid . ' failed: ' . midcom_connection::get_error_string() . " \n";
                    }
                    else
                    {
                        $entry->purge();
                    }
                }
            }
            else
            {
                throw new midcom_error('Unexpected error: ' . midcom_connection::get_error_string() . ', ' . $e->getMessage());
            }
        }
    }
    $offset += $chunk_size;

} while (sizeof($results) > 0);

echo "Done.\n";
echo "</pre>";
ob_start();
?>