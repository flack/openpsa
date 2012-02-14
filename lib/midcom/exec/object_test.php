<?php
$_MIDCOM->load_library('midcom.helper.reflector');

function obj_cleanup(&$object)
{
    if ($object->delete())
    {
        $object->purge();
        return true;
    }
    echo "Deletion failed for {$object->guid}, errstr: " . midcom_connection::get_error_string() . "<br>\n";
}

echo "<h1>Starting tests</h1>\n";

while(@ob_end_flush());

foreach (midcom_connection::get_schema_types() as $mgdschema)
{
    if (empty($mgdschema))
    {
        continue;
    }
    flush();
    $dummy = new $mgdschema();
    $midcom_class = midcom::get('dbclassloader')->get_midcom_class_name_for_mgdschema_object($dummy);
    if (empty($midcom_class))
    {
        continue;
    }
    if (   !midcom::get('dbclassloader')->load_mgdschema_class_handler($midcom_class)
        || !class_exists($midcom_class))
    {
        echo "<h2>ERROR: Could not load handler for {$midcom_class}</h2>\n";
        continue;
    }
    echo "<h2>Testing {$midcom_class}</h2>\n";
    if (isset($empty))
    {
        unset($empty);
    }
    $empty = new $midcom_class();
    if (!$empty->create())
    {
        echo 'Failed to create "empty" object, errstr: ' . midcom_connection::get_error_string() . "<br>\n";
        continue;
    }
    echo "Created new object<pre>\n";
    var_dump($empty);
    echo "</pre>\n";
    $empty->metadata->score = 99;
    if (!$empty->update())
    {
        echo "Update (score=99) failed, errstr: " . midcom_connection::get_error_string() . "<br>\n";
        obj_cleanup($empty);
        continue;
    }
    echo "Updated object<pre>\n";
    var_dump($empty);
    echo "</pre>\n";

    // Something else ??
    obj_cleanup($empty);
}
echo "<h1>All done</h1>";
flush();

ob_start();
?>