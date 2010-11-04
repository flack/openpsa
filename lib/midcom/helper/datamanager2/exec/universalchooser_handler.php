<?php
/**
 * Handler for the universalchooser searches
 *
 * @package midcom.helper.datamanager2
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: universalchooser_handler.php 26504 2010-07-06 12:19:31Z rambo $
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */
//debug_print_r('_REQUEST',  $_REQUEST);
// Common variables
$encoding = 'UTF-8';

// Common headers
$_MIDCOM->cache->content->content_type('text/xml');
$_MIDCOM->header('Content-type: text/xml; charset=' . $encoding);
echo '<?xml version="1.0" encoding="' . $encoding . '" standalone="yes"?>' . "\n";
echo "<response>\n";

// Make sure we have search term
if (!isset($_REQUEST['search']))
{
    echo "    <status>0</status>\n";
    echo "    <errstr>Search term not defined</errstr>\n";
    echo "</response>\n";
    $_MIDCOM->finish();
    _midcom_stop_request();
}
// Convert traditional wildcard to SQL wildcard
$search = str_replace('*', '%', $_REQUEST['search']);
// Make sure we don't have multiple successive wildcards (performance killer)
$search = preg_replace('/%+/', '%', $search);

// Get local copies of other variables from request
$map = array('component', 'class', 'titlefield', 'idsuffix', 'idfield', 'searchfields', 'constraints', 'orders', 'hash', 'auto_wildcards');
foreach ($map as $varname)
{
    if (isset($_REQUEST[$varname]))
    {
        $$varname = $_REQUEST[$varname];
    }
    else
    {
        $$varname = false;
    }
}

// Get the shared secret
$shared_secret = null;
try
{
    $key_snippet = new midgard_snippet();
    $key_snippet->get_by_path('/sitegroup-config/midcom.helper.datamanager2/widget_universalchooser_key');
}
catch (midgard_error_exception $e)
{
    //FIXME: Make sure this is actually the correct code (and midgard-php sets the code)
    if ($e->getCode() !== 0)
    {
        throw $e;
    }
    $key_snippet = null;
}
if (   !is_object($key_snippet)
    || empty($key_snippet->doc))
{
    debug_add("Warning, cannot get shared secret (either not generated or error loading), try generating with /midcom-exec-midcom.helper.datamanager2/universalchooser_create_secret.php.", MIDCOM_LOG_WARN);
}
else
{
    $shared_secret = $key_snippet->doc;
}

$hashsource = $class . $idfield . $shared_secret . $component . $idsuffix;
if (is_array($constraints))
{
    ksort($constraints);
    reset($constraints);
    foreach ($constraints as $key => $data)
    {
        if (   !array_key_exists('field', $data)
            || !array_key_exists('op', $data)
            || !array_key_exists('value', $data)
            )
        {
            debug_add("hashsource loop: Constraint #{$key} is not fully defined, skipping", MIDCOM_LOG_WARN);
            continue;
        }
        $hashsource .= $data['field'] . $data['op'] . $data['value'];
    }
}

debug_add('handler hashsource: (B64)' . base64_encode($hashsource));
debug_add('given hash: ' . $hash . ', calculated: ' . md5($hashsource));
if ($hash != md5($hashsource))
{
    echo "    <status>0</status>\n";
    echo "    <errstr>Hash mismatch (if error persists contact system administrator)</errstr>\n";
    echo "</response>\n";
    $_MIDCOM->finish();
    _midcom_stop_request();
}

// Handle automatic wildcards
if (   !empty($auto_wildcards)
    && strpos($search, '%') === false)
{
    switch($auto_wildcards)
    {
        case 'both':
            $search = "%{$search}%";
            break;
        case 'start':
            $search = "%{$search}";
            break;
        case 'end':
            $search = "{$search}%";
            break;
        default:
            debug_add("Don't know how to handle auto_wildcards value '{$auto_wildcards}'", MIDCOM_LOG_WARN);
            break;
    }
}

/* 
 * Load component if possible. This is a workaround for cases where 
 * autoloader cannot fails to load the component, i.e. when the class name
 * doesn't end with _db or _dba. 
 * 
 * Should be removed once autoloader is smarter or all DBA classes follow the 
 * naming convention
 */
$_MIDCOM->componentloader->load_graceful($component);

// Could not get required class defined, abort
if (!class_exists($class))
{
    echo "    <status>0</status>\n";
    echo "    <errstr>Class {$class} could not be loaded</errstr>\n";
    echo "</response>\n";
    $_MIDCOM->finish();
    _midcom_stop_request();
}
// No fields to search by, abort
if (empty($searchfields))
{
    echo "    <status>0</status>\n";
    echo "    <errstr>No fields to search for defined</errstr>\n";
    echo "</response>\n";
    $_MIDCOM->finish();
    _midcom_stop_request();
}

// idfield or titlefield empty, abort
if (   empty($titlefield)
    || empty($idfield))
{
    echo "    <status>0</status>\n";
    echo "    <errstr>titlefield or idfield not defined</errstr>\n";
    echo "</response>\n";
    $_MIDCOM->finish();
    _midcom_stop_request();
}


$qb = call_user_func(array($class, 'new_query_builder'));
if (is_array($constraints))
{
    ksort($constraints);
    reset($constraints);
    foreach ($constraints as $key => $data)
    {
        if (   !array_key_exists('field', $data)
            || !array_key_exists('op', $data)
            || !array_key_exists('value', $data)
            || empty($data['field'])
            || empty($data['op'])
            )
        {
            debug_add("addconstraint loop: Constraint #{$key} is not correctly defined, skipping", MIDCOM_LOG_WARN);
            continue;
        }
        debug_add("Adding constraint: {$data['field']} {$data['op']} '{$data['value']}'");
        $qb->add_constraint($data['field'], $data['op'], $data['value']);
    }
}

if (preg_match('/^%+$/', $search))
{
    debug_add('$search is all wildcards, don\'t was time with adding LIKE constraints');
}
else
{
    $qb->begin_group('OR');
    foreach ($searchfields as $field)
    {
        debug_add("adding search (ORed) constraint: {$field} LIKE '{$search}'");
        $qb->add_constraint($field, 'LIKE', $search);
    }
    $qb->end_group();
}

if (is_array($orders))
{
    ksort($orders);
    reset($orders);
    foreach ($orders as $data)
    {
        foreach($data as $field => $order)
        {
            debug_add("adding order: {$field}, {$order}");
            $qb->add_order($field, $order);
        }
    }
}
$results = $qb->execute();
if ($results === false)
{
    echo "    <status>0</status>\n";
    echo "    <errstr>Error when executing QB</errstr>\n";
    echo "</response>\n";
    $_MIDCOM->finish();
    _midcom_stop_request();
}

echo "    <status>1</status>\n";
echo "    <errstr></errstr>\n";
//echo "    <errstr>All OK</errstr>\n";

echo "    <results>\n";
foreach ($results as $object)
{
    // Silence to avoid notices breaking the XML in case of nonexistent field
    $id = @$object->$idfield;
    if (is_array($titlefield))
    {
        ksort($titlefield);
        foreach($titlefield as $field)
        {
            if ($object->$field)
            {
                $gotfield = $field;
                break;
            }
        }
        reset($titlefield);
    }
    else
    {
        $gotfield = $titlefield;
    }


    $title = rawurlencode(@$object->$gotfield);
    debug_add("adding result: id={$id} title='{$title}' titlefield='{$gotfield}'");
    echo "      <line>\n";
    echo "          <id>{$id}</id>\n";
    echo "          <title><![CDATA[{$title}]]></title>\n";
    echo "      </line>\n";
}
echo "    </results>\n";

echo "</response>\n";
?>