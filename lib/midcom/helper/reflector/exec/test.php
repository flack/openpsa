<?php
/**
 * @package midcom.helper.reflector
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

if (   !isset($_GET['guid'])
    || empty($_GET['guid']))
{
    throw new midcom_error_notfound('Specify $guid via GET for info');
}

$object = midcom::get('dbfactory')->get_object_by_guid($_GET['guid']);
$reflector =& midcom_helper_reflector::get($object);

echo "Got " . $reflector->get_class_label() . ' "' . $reflector->get_object_label($object) . "\", dump<pre>\n";
var_dump($object);
echo "</pre>\n";

if (midcom_helper_reflector_tree::has_children($object))
{
    echo "Object has children<br/>\n";
    echo "Child counts <pre>\n";
    $counts = midcom_helper_reflector_tree::count_child_objects($object);
    print_r($counts);
    echo "</pre>\n";
    echo "Child objects dump<pre>\n";
    $children = midcom_helper_reflector_tree::get_child_objects($object);
    var_dump($children);
    echo "</pre>\n";
}
?>