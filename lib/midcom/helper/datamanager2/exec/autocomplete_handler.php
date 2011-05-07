<?php
/**
 * Handler for autocomplete searches
 *
 * @package midcom.helper.datamanager2
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

// Load component if possible
$_MIDCOM->componentloader->load_graceful($_REQUEST['component']);


$error = '';
// Could not get required class defined, abort
if (!class_exists($_REQUEST['class']))
{
    $error = "Class {$_REQUEST['class']} could not be loaded\n";
}

// No fields to search by, abort
if (empty($_REQUEST['searchfields']))
{
    $error = "No fields to search for defined\n";
}

if (empty($_REQUEST["term"]))
{
    $error = "Empty query string.";
}

if (empty($_REQUEST["id_field"]))
{
    $error = "Empty ID field.";
}

if ($error != '')
{
    _midcom_header("HTTP/1.0 400 Bad Request");
    echo $error;
    $_MIDCOM->finish();
    _midcom_stop_request();
}

if (!isset($_REQUEST['get_label_for']))
{
    $_REQUEST['get_label_for'] = null;
}

$query = $_REQUEST["term"];
if (   isset($_REQUEST['auto_wildcards'])
    && strpos($query, '%') === false)
{
    switch ($_REQUEST['auto_wildcards'])
    {
        case 'start':
            $query = '%' . $query;
            break;
        case 'end':
            $query = $query . '%';
            break;
        case 'both':
            $query = '%' . $query . '%';
            break;
        default:
            debug_add("Don't know how to handle auto_wildcards value '{$auto_wildcards}'", MIDCOM_LOG_WARN);
            break;
    }
}
$query = str_replace("*", "%", $query);
$query = preg_replace('/%+/', '%', $query);


$qb = @call_user_func(array($_REQUEST['class'], 'new_query_builder'));
if (! $qb)
{
    debug_add("use midgard_query_builder");
    $qb = new midgard_query_builder($class);
}

if (   !empty($_REQUEST['constraints'])
    && is_array($_REQUEST['constraints']))
{
    $constraints = $_REQUEST['constraints'];
    ksort($constraints);
    reset($constraints);
    foreach ($constraints as $key => $data)
    {
        if (   !array_key_exists('field', $data)
            || !array_key_exists('op', $data)
            || !array_key_exists('value', $data)
            || empty($data['field'])
            || empty($data['op']))
        {
            debug_add("Constraint #{$key} is not correctly defined, skipping", MIDCOM_LOG_WARN);
            continue;
        }
        $qb->add_constraint($data['field'], $data['op'], $data['value']);
    }
}

if (preg_match('/^%+$/', $query))
{
    debug_add('$query is all wildcards, don\'t waste time in adding LIKE constraints');
}
else
{
    $qb->begin_group('OR');
    foreach ($_REQUEST['searchfields'] as $field)
    {
        debug_add("adding search (ORed) constraint: {$field} LIKE '{$query}'");
        $qb->add_constraint($field, 'LIKE', $query);
    }
    $qb->end_group();
}

if (   !empty($_REQUEST['orders'])
    && is_array($_REQUEST['orders']))
{
    ksort($_REQUEST['orders']);
    reset($_REQUEST['orders']);
    foreach ($_REQUEST['orders'] as $data)
    {
        foreach ($data as $field => $order)
        {
            $qb->add_order($field, $order);
        }
    }
}

$results = $qb->execute();
if (   $results === false
    || !is_array($results))
{
    _midcom_header("HTTP/1.0 500 Server Error");
    echo "Error when executing QB\n";
    $_MIDCOM->finish();
    _midcom_stop_request();
}


// Common headers
$_MIDCOM->cache->content->content_type('application/json');
$_MIDCOM->header('Content-type: application/json; charset=UFT-8');

$items = array();

foreach ($results as $object)
{
    $item = array
    (
        'id' => $object->{$_REQUEST['id_field']},
        'label' => midcom_helper_datamanager2_widget_autocomplete::create_item_label($object, $_REQUEST['result_headers'], $_REQUEST['get_label_for']),
    );
    if (!empty($_REQUEST['categorize_by_parent_label']))
    {
        if ($parent = $object->get_parent())
        {
            $item['category'] = $parent->get_label();
        }
    }
    $item['value'] = $item['label'];

    $items[] = $item;
}

usort($items, array('midcom_helper_datamanager2_widget_autocomplete', 'sort_items'));


echo json_encode($items);
$_MIDCOM->finish();
?>