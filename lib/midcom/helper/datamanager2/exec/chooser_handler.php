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

$response = new midcom_response_xml;
$response->status = 0;

if (! isset($_REQUEST["query"]))
{
    $response->errstr = "Search term not defined"; //TODO: Localize message

    debug_add("Empty query string. Quitting now.");
    $response->send();
}

$query = $_REQUEST["query"];
$query = str_replace("*", "%", $query);
$query = preg_replace('/%+/', '%', $query);

$map = array
(
    'component', 'class',
    '_callback_class', '_callback_args',
    '_renderer_callback_class', '_renderer_callback_args',
    'constraints', 'searchfields', 'orders',
    'result_headers', 'generate_path_for',
    'auto_wildcards',
    'reflector_key'
);
$extra_params = unserialize(base64_decode($_REQUEST['extra_params']));

debug_print_r('extra params', $extra_params);

foreach ($map as $map_key)
{
    if (!empty($extra_params[$map_key]))
    {
        $$map_key = $extra_params[$map_key];
    }
    else
    {
        $$map_key = false;
    }
}


// Handle automatic wildcards
if (   !empty($auto_wildcards)
    && strpos($query, '%') === false)
{
    switch($auto_wildcards)
    {
        case 'both':
            $query = "%{$query}%";
            break;
        case 'start':
            $query = "%{$query}";
            break;
        case 'end':
            $query = "{$query}%";
            break;
        default:
            debug_add("Don't know how to handle auto_wildcards value '{$auto_wildcards}'", MIDCOM_LOG_WARN);
            break;
    }
}

if (!empty($_callback_class))
{
    if (! class_exists($_callback_class))
    {
        // Try auto-load.
        $path = MIDCOM_ROOT . '/' . str_replace('_', '/', $_callback_class) . '.php';
        if (! file_exists($path))
        {
            debug_add("Auto-loading of the callback class {$_callback_class} from {$path} failed: File does not exist.", MIDCOM_LOG_ERROR);
            return false;
        }
        require_once($path);
    }

    if (! class_exists($_callback_class))
    {
        debug_add("The callback class {$_callback_class} was defined as option for the field {$this->name} but did not exist.", MIDCOM_LOG_ERROR);
        return false;
    }
    $_callback = new $_callback_class($_callback_args);
    $results = $_callback->run_search($query, $_REQUEST);
}
else
{
    // Load component if possible
    midcom::get('componentloader')->load_graceful($component);

    // Could not get required class defined, abort
    if (!class_exists($class))
    {
        $response->errstr = "Class {$class} could not be loaded";
        $response->send();
    }

    // No fields to search by, abort
    if (empty($searchfields))
    {
        $response->errstr = "No fields to search for defined";
        $response->send();
    }

    $qb = @call_user_func(array($class, 'new_query_builder'));
    if (! $qb)
    {
        debug_add("use midgard_query_builder");
        $qb = new midgard_query_builder($class);
    }

    if (   is_array($constraints)
        && !empty($constraints))
    {
        ksort($constraints);
        reset($constraints);
        foreach ($constraints as $key => $data)
        {
            if (   !array_key_exists('value', $data)
                || empty($data['field'])
                || empty($data['op']))
            {
                debug_add("addconstraint loop: Constraint #{$key} is not correctly defined, skipping", MIDCOM_LOG_WARN);
                continue;
            }
            debug_add("Adding constraint: {$data['field']} {$data['op']} " . gettype($data['value']) . " '{$data['value']}'");
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
        foreach ($searchfields as $field)
        {
            debug_add("adding search (ORed) constraint: {$field} LIKE '{$query}'");
            $qb->add_constraint($field, 'LIKE', $query);
        }
        $qb->end_group();
    }

    if (is_array($orders))
    {
        ksort($orders);
        reset($orders);
        foreach ($orders as $data)
        {
            foreach ($data as $field => $order)
            {
                debug_add("adding order: {$field}, {$order}");
                $qb->add_order($field, $order);
            }
        }
    }

    $results = $qb->execute();
    if ($results === false)
    {
        $response->errstr = "Error when executing QB";
        $response->send();
    }
}

if (   count($results) <= 0
    || !is_array($results))
{
    $response->status = 2;
    $response->errstr = "No results found";
    $response->send();
}

$response->status = 1;
$response->errstr = "";
$items = array();

foreach ($results as $object)
{
    if (!empty($reflector_key))
    {
        debug_add("Using reflector with key {$reflector_key}");
        $reflector_type = get_class($object);

        $reflector = new midgard_reflection_property($reflector_type);

        if ($reflector->is_link($reflector_key))
        {
            $linked_type = $reflector->get_link_name($reflector_key);

            $object = new $linked_type($object->$reflector_key);
        }

        debug_print_r('reflected object', $object);
    }

    $id = @$object->id;
    $guid = @$object->guid;

    debug_add("adding result: id={$id} guid={$guid}");

    $result = array
    (
        'id' => $id,
        'guid' => $guid
    );

    debug_print_r('$result_headers', $result_headers);
    if (   !is_array($result_headers)
        || (   !empty($reflector_key)
            && !$result_headers))
    {
        $value = @$object->get_label();
        debug_add("adding header item: name=label value={$value}");
        $result['label'] = "<![CDATA[{$value}]]>";
    }
    else
    {
        foreach ($result_headers as $header_item)
        {
            $item_name = $header_item['name'];

            if (preg_match('/^metadata\.(.+)$/', $item_name, $regs))
            {
                $metadata_property = $regs[1];
                $value = @$object->metadata->$metadata_property;

                switch ($metadata_property)
                {
                    case 'created':
                    case 'revised':
                    case 'published':
                    case 'schedulestart':
                    case 'scheduleend':
                    case 'imported':
                    case 'exported':
                    case 'approved':
                        if ($value)
                        {
                            $value = strftime('%x %X', $value);
                        }
                        break;

                    case 'creator':
                    case 'revisor':
                    case 'approver':
                    case 'locker':
                        if ($value)
                        {
                            $person = new midcom_db_person($value);
                            $value = $person->name;
                        }
                        break;
                }
            }
            else
            {
                $value = @$object->$item_name;
            }

            if (   $generate_path_for == $item_name
                /**
                 * Shouldn't these be handled by the 'clever' classes ?
                 * Also: is_a() would be better way the check for classes
                 */
                || (   $class == 'midcom_db_topic'
                    && $item_name == 'extra')
                || (   in_array($class, array('midcom_db_group', 'midcom_db_group'))
                    && $item_name == 'name'))
            {
                $value = midcom_helper_reflector_tree::resolve_path($object);
            }

            $item_name = str_replace('.', '_', $item_name);

            debug_add("adding header item: name={$item_name} value={$value}");
            $result[$item_name] = "<![CDATA[{$value}]]>";
        }
    }
    $items[] = $result;

}

debug_print_r('Got results', $items);
$response->results = array
(
    'result' => $items
);

$response->send();
?>