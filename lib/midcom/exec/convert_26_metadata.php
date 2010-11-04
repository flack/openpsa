<?php
/**
 * this script converts 2.6 parameters based metadata to current 1.8 format
 */

$_MIDCOM->auth->require_valid_user('basic');
$_MIDCOM->auth->require_admin_user();

$GLOBALS['midcom_config']['show_hidden_objects'] = true;
$GLOBALS['midcom_config']['show_unapproved_objects'] = true;

while(@ob_end_flush());

function convert_metadata(&$object)
{
    static $seen = array();
    if (isset($seen[$object->guid]))
    {
        echo "GUID {$object->guid} already processed!<br/>\n";
        return;
    }
    if (! $_MIDCOM->dbclassloader->is_midcom_db_object($object))
    {
        $object = $_MIDCOM->dbfactory->convert_midgard_to_midcom($object);
    }
    $meta = midcom_helper_metadata::retrieve($object);
    if (!is_object($meta))
    {
        echo "FAILED to instantiate metadata helper for GUID {$object->guid}<br/>\n";
        return;
    }
    $params = $object->list_parameters('midcom.helper.metadata');
    if (empty($params))
    {
        // In fact we should never hit this but let's abort early anyway if we have nothing to do
        return;
    }
    $multivalue = array();
    foreach ($params as $name => $value)
    {
        // property specific operations
        switch ($name)
        {
            // Map names
            case 'schedule_start':
            case 'schedule_end':
            case 'nav_noentry':
                $new_name = str_replace('_', '', $name);
                break;
            case 'hide':
                $new_name = 'hidden';
                break;
            // These are stored to params in 2.8 as well so skip them
            case 'keywords':
            case 'description':
                continue 2;
            // By default re-store with same name via metadata service
            default:
                $new_name = $name;
        }
        $multivalue[$new_name] = $value;
        // Clear old values to do this only once
        $object->delete_parameter('midcom.helper.metadata', $name);
    }
    if (empty($multivalue))
    {
        // Nothing to do, skip
        return;
    }
    if (!$meta->set_multiple($multivalue))
    {
        echo "FAILED to update metadata, errstr: " . midcom_application::get_error_string() . "<br/>\n&nbps;&nbps;&nbps;Tried to set values:<pre>\n";
        var_dump($multivalue);
        echo "</pre>\n";
        // Restore old parameter values
        foreach ($params as $name => $value)
        {
            $object->set_parameter('midcom.helper.metadata', $name, $value);
        }
    }
    echo "GUID {$object->guid}, converted following properties: " . implode(', ', array_keys($multivalue)) . "<br>\n";
}

foreach ($_MIDGARD['schema']['types'] as $mgdschema => $dummy)
{
    flush();
    echo "<h2>Processing class {$mgdschema}</h2>\n";
    $qb = new midgard_query_builder($mgdschema);
    $qb->add_constraint('sitegroup', '=', $_MIDGARD['sitegroup']);
    $qb->add_constraint('parameter.domain', '=', 'midcom.helper.metadata');
    $objects = $qb->execute();
    unset($qb);
    if (!is_array($objects))
    {
        echo "FATAL QB ERROR<br/>\n";
        continue;
    }
    $found = count($objects);
    foreach ($objects as $k => $object)
    {
        convert_metadata($object);
        unset($objects[$k]);
    }
    unset($objects);
}

echo "<br/><br/>Done."; flush();
ob_start();
$_MIDCOM->cache->invalidate_all()
?>