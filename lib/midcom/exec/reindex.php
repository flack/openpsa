<?php
/**
 * Reindex script.
 *
 * Iterates through all nodes and calls reindex_singlenode.php for each of them in the background.
 *
 * This may take some time.
 *
 * @package midcom
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id$
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

// IP Address Checks
$ips = $GLOBALS['midcom_config']['indexer_reindex_allowed_ips'];
$ip_sudo = false;
if (   $ips
    && in_array($_SERVER['REMOTE_ADDR'], $ips))
{
    if (! $_MIDCOM->auth->request_sudo('midcom.services.indexer'))
    {
        $_MIDCOM->generate_error(MIDCOM_ERRCRIT, 'Failed to acquire SUDO rights. Aborting.');
    }
    $ip_sudo = true;
}
else
{
    $_MIDCOM->auth->require_valid_user('basic');
    $_MIDCOM->auth->require_admin_user();
}

if ($GLOBALS['midcom_config']['indexer_backend'] === false)
{
    $_MIDCOM->generate_error(MIDCOM_ERRCRIT, 'No indexer backend has been defined. Aborting.');
}

switch($_SERVER['SERVER_PORT'])
{
    case 80:
        $current_uri = "http://{$_SERVER['SERVER_NAME']}{$_SERVER['REQUEST_URI']}";
        break;
    case 443:
        $current_uri = "https://{$_SERVER['SERVER_NAME']}{$_SERVER['REQUEST_URI']}";
        break;
    default:
        if (!empty($_SERVER['HTTPS']))
        {
            $current_uri = 'https://';
        }
        else
        {
            $current_uri = 'http://';
        }
        $current_uri .= "{$_SERVER['SERVER_NAME']}";

        if (!preg_match('/:' . $_SERVER['SERVER_PORT'] . '/', $_SERVER['SERVER_NAME']))
        {
            $current_uri .= ":{$_SERVER['SERVER_PORT']}";
        }
        $current_uri .= $_SERVER['REQUEST_URI'];
        break;
}

//check if language is passed - if not take the current-one
$language = $_MIDCOM->i18n->get_current_language();
if (isset($_REQUEST['language']))
{
    $language = $_REQUEST['language'];
}

$_MIDCOM->load_library('org.openpsa.httplib');
if (!class_exists('org_openpsa_httplib'))
{
    $singlep_uri = str_replace('midcom-exec-midcom/reindex.php', 'midcom-exec-midcom/reindex_singleprocess.php', $current_uri);
    $_MIDCOM->generate_error(MIDCOM_ERRCRIT, "We need org.openpsa.httplib installed to use the granular reindex, use {$singlep_uri} to get the old way.");
}


debug_add('Disabling script abort through client.');
ignore_user_abort(true);

debug_add("Setting Memorylimit to configured value of {$GLOBALS['midcom_config']['indexer_reindex_memorylimit']} MB");
ini_set('memory_limit', "{$GLOBALS['midcom_config']['indexer_reindex_memorylimit']}M");

$nap = new midcom_helper_nav();
$nodes = Array();
$nodeid = $nap->get_root_node();
$loader = $_MIDCOM->get_component_loader();
$indexer = $_MIDCOM->get_service('indexer');

// Use this to check that indexer is on-line (and hope the root topic isn't a gigantic wiki)
$root_node = $nap->get_node($nodeid);
$existing_documents = $indexer->query("__TOPIC_GUID:{$root_node[MIDCOM_NAV_OBJECT]->guid}");
if ($existing_documents === false)
{
    $msg = "Query '__TOPIC_GUID:{$root_node[MIDCOM_NAV_OBJECT]->guid}' returned false, indicating problem with indexer";
    $_MIDCOM->generate_error(MIDCOM_ERRCRIT, $msg);
    // this will exit
}
unset($existing_documents, $root_node);
// Disable ob
while(@ob_end_flush());

echo "<pre>\n";

debug_dump_mem("Initial Memory Usage");

$reindex_topic_uri = str_replace('midcom-exec-midcom/reindex.php', 'midcom-exec-midcom/reindex_singlenode.php', $current_uri);


$http_client = new org_openpsa_httplib();
if (   isset($_SERVER['PHP_AUTH_USER'])
    && !empty($_SERVER['PHP_AUTH_USER'])
    && isset($_SERVER['PHP_AUTH_PW'])
    && !empty($_SERVER['PHP_AUTH_PW']))
{
    $http_client->basicauth['user'] = $_SERVER['PHP_AUTH_USER'];
    $http_client->basicauth['password'] = $_SERVER['PHP_AUTH_PW'];
}

while (! is_null($nodeid))
{
    // Update script execution time
    // This should suffice for really large topics as well.
    set_time_limit(5000);

    // Reindex the node...
    $node = $nap->get_node($nodeid);
    echo "Processing node #{$nodeid}, {$node[MIDCOM_NAV_FULLURL]}: ";
    flush();
    //pass the node-id & the language
    $post_variables = array('nodeid' => $nodeid , 'language' => $language);
    $response = $http_client->post($reindex_topic_uri, $post_variables, array('User-Agent' => 'midcom-exec-midcom/reindex.php'));
    if ($response === false)
    {
        // returned with failure
        echo "failure.\n   Background processing failed, error: {$http_client->error}\n";
        echo "Url: " . $reindex_topic_uri . "?" . join("=", $post_variables) . "\n";
        $body = $http_client->_client->getResponseBody();
        if (!empty($body))
        {
            echo "   Background response body:\n---\n{$body}\n---\n\n";
        }
        unset($body);
    }
    if (!preg_match("#(\n|\r\n)Reindex complete for node http.*\s*</pre>\s*$#", $response))
    {
        // Does not end with 'Reindex complete for node...'
        echo "failure.\n   Background reindex returned unexpected data:\n---\n{$response}\n---\n\n";
        echo "Url: " . $reindex_topic_uri . "?" . join("=", $post_variables) . "\n";
    }
    else
    {
        // Background reindex ok
        echo "OK.\n";
    }
    flush();
    // Overwrite this so that we don't have funky dangling references
    $http_client->_client = null;

    debug_dump_mem("Mem usage after {$node[MIDCOM_NAV_RELATIVEURL]}; {$node[MIDCOM_NAV_COMPONENT]}");

    // Retrieve all child nodes and append them to $nodes:
    $childs = $nap->list_nodes($nodeid);
    if ($childs === false)
    {
        $_MIDCOM->generate_error(MIDCOM_ERRCRIT, "Failed to list the child nodes of {$nodeid}. Aborting.");
    }
    $nodes = array_merge($nodes, $childs);
    $nodeid = array_shift($nodes);
}

debug_add('Enabling script abort through client again.');
ignore_user_abort(false);

if ($ip_sudo)
{
    $_MIDCOM->auth->drop_sudo();
}

//re-enable ob
ob_start();
?>

Reindex complete
</pre>
