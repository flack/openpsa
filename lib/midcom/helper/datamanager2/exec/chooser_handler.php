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

$extra_params = unserialize(base64_decode($_REQUEST['extra_params']));

debug_print_r('extra params', $extra_params);

try
{
    $extra_params['term'] = $_REQUEST['query'];
    $extra_params['limit'] = $_REQUEST['limit'];
    $handler = new midcom_helper_datamanager2_ajax_autocomplete($extra_params);
}
catch (midcom_error $e)
{
    $response->errstr = $e->getMessage(); //TODO: Localize message
    $response->send();
}

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

if (!empty($_callback_class))
{
    if (! class_exists($_callback_class))
    {
        debug_add("The callback class {$_callback_class} was defined as option for the field {$this->name} but did not exist.", MIDCOM_LOG_ERROR);
        return false;
    }
    $_callback = new $_callback_class($_callback_args);
    $query = $handler->get_querystring();
    $results = $_callback->run_search($query, $_REQUEST);
}
else
{
    try
    {
        $results = $handler->get_objects();
    }
    catch (midcom_error $e)
    {
        $response->errstr = $e->getMessage(); //TODO: Localize message
        $response->send();
    }
}

if (count($results) == 0)
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