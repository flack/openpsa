<?php
/**
 * Handler for the tags-widget searches
 *
 * @package midcom.helper.datamanager2
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

// Common variables
$items = array();
$mode = 'callback';
$_callback = null;
$callback_args = array();

$response = new midcom_response_xml;
$response->status = 0;

if (! isset($_REQUEST["query"]))
{
    $response->errstr = "Search term not defined"; //TODO: Localize message

    debug_add("Empty query string. Quitting now.");
    $response->send();
}

$query = $_REQUEST["query"];

// Get local copies of other variables from request
$map = array('component', 'class', 'object_id', 'id_field', 'callback', 'callback_args');
foreach ($map as $varname)
{
    if (!empty($_REQUEST[$varname]))
    {
        $$varname = $_REQUEST[$varname];
        if ($varname == 'callback_args')
        {
            $callback_args = json_decode($callback_args);
        }
    }
    else
    {
        $$varname = false;
    }
}

if (   !empty($component)
    && !empty($class)
    && !empty($object_id)
    && !empty($id_field))
{
    $mode = 'object';
}

if ($mode == 'object')
{
    midcom::get('componentloader')->load_library('net.nemein.tag');

    // Load component if required
    if (!class_exists($class))
    {
        midcom::get('componentloader')->load_graceful($component);
    }
    // Could not get required class defined, abort
    if (!class_exists($class))
    {
        $response->errstr = "Class {$class} could not be loaded";
        $reponse->send();
    }

    $qb = call_user_func(array($class, 'new_query_builder'));
    $qb->add_constraint($id_field, '=', $object_id);
    $results = $qb->execute();

    if ($results === false)
    {
        $response->errstr = "Error when executing QB";
        $reponse->send();
    }

    $object = $results[0];

    $tags = net_nemein_tag_handler::get_object_tags($object);

    foreach ($tags as $name => $link)
    {
        $data = array
        (
            'id' => $name,
            'name' => $name,
            'color' => '8596b6'
        );
        $items[$name] = $data;
    }
}
else
{
    if (! class_exists($callback))
    {
        // Try auto-load.
        $path = MIDCOM_ROOT . '/' . str_replace('_', '/', $callback) . '.php';
        if (! file_exists($path))
        {
            debug_add("Auto-loading of the callback class {$callback} from {$path} failed: File does not exist.", MIDCOM_LOG_ERROR);
            return false;
        }
        require_once($path);
    }

    if (! class_exists($callback))
    {
        debug_add("The callback class {$callback} was defined as option for the field {$this->name} but did not exist.", MIDCOM_LOG_ERROR);
        return false;
    }
    $_callback = new $callback($callback_args);

    $all_items = $_callback->list_all();
    foreach ($all_items as $id => $data)
    {
        $items[$id] = $data;
    }
}

$results = array();
$added_keys = array();
foreach ($items as $id => $item)
{
    foreach ($item as $key => $value)
    {
        if (strpos(strtolower($value), $query) !== false)
        {
            if (! array_key_exists($id, $added_keys))
            {
                $results[] = $item;
                $added_keys[$id] = true;
            }
        }
    }
}

if (empty($results))
{
    $response->errstr = "No results"; //TODO: Localize message
    debug_add("No results.");
    $response->send();
}
$response->status = 1;
$items = array('result');
foreach ($results as $i => $result)
{
    $items['result'][] = array
    (
        'id' => $result['id'],
        'name' => $result['name'],
        'color' => $result['color']
    );
}
$response->results = $items;
$response->send();
?>