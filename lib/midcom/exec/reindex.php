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
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

$ip_sudo = midcom::get()->auth->require_admin_or_ip('midcom.services.indexer');

if (midcom::get()->config->get('indexer_backend') === false)
{
    throw new midcom_error('No indexer backend has been defined. Aborting.');
}

//check if language is passed - if not take the current-one
$language = midcom::get()->i18n->get_current_language();
if (isset($_REQUEST['language']))
{
    $language = $_REQUEST['language'];
}

debug_add('Disabling script abort through client.');
ignore_user_abort(true);

midcom::get()->disable_limits();
$start = microtime(true);

$nap = new midcom_helper_nav();
$nodes = array();
$nodeid = $nap->get_root_node();
$loader = midcom::get()->componentloader;
$indexer = midcom::get()->indexer;

// Use this to check that indexer is online (and hope the root topic isn't a gigantic wiki)
$root_node = $nap->get_node($nodeid);
$existing_documents = $indexer->query("__TOPIC_GUID:{$root_node[MIDCOM_NAV_OBJECT]->guid}");
if ($existing_documents === false)
{
    $msg = "Query '__TOPIC_GUID:{$root_node[MIDCOM_NAV_OBJECT]->guid}' returned false, indicating problem with indexer";
    throw new midcom_error($msg);
}
unset($existing_documents, $root_node);
// Disable ob
while(@ob_end_flush());

echo "<pre>\n";

debug_dump_mem("Initial Memory Usage");

$reindex_topic_uri = midcom::get()->get_page_prefix() . 'midcom-exec-midcom/reindex_singlenode.php';

$http_client = new org_openpsa_httplib();
$http_client->set_param('timeout', 300);
if (   !empty($_SERVER['PHP_AUTH_USER'])
    && !empty($_SERVER['PHP_AUTH_PW']))
{
    $http_client->basicauth['user'] = $_SERVER['PHP_AUTH_USER'];
    $http_client->basicauth['password'] = $_SERVER['PHP_AUTH_PW'];
}

while (!is_null($nodeid))
{
    // Reindex the node...
    $node = $nap->get_node($nodeid);
    echo "Processing node #{$nodeid}, {$node[MIDCOM_NAV_FULLURL]}: ";
    flush();
    //pass the node-id & the language
    $post_variables = array('nodeid' => $nodeid, 'language' => $language);
    $post_string = 'nodeid=' . $nodeid . '&language=' . $language;
    $response = $http_client->post($reindex_topic_uri, $post_variables, array('User-Agent' => 'midcom-exec-midcom/reindex.php'));
    if ($response === false)
    {
        // returned with failure
        echo "failure.\n   Background processing failed, error: {$http_client->error}\n";
        echo "Url: " . $reindex_topic_uri . "?" . $post_string . "\n";
    }
    elseif (!preg_match("#(\n|\r\n)Reindex complete for node http.*\s*</pre>\s*$#", $response))
    {
        // Does not end with 'Reindex complete for node...'
        echo "failure.\n   Background reindex returned unexpected data:\n---\n{$response}\n---\n";
        echo "Url: " . $reindex_topic_uri . "?" . $post_string . "\n\n";
    }
    else
    {
        // Background reindex ok
        echo "OK.\n";
    }
    flush();

    debug_dump_mem("Mem usage after {$node[MIDCOM_NAV_RELATIVEURL]}; {$node[MIDCOM_NAV_COMPONENT]}");

    // Retrieve all child nodes and append them to $nodes:
    $childs = $nap->list_nodes($nodeid);
    if ($childs === false)
    {
        throw new midcom_error("Failed to list the child nodes of {$nodeid}. Aborting.");
    }
    $nodes = array_merge($nodes, $childs);
    $nodeid = array_shift($nodes);
}

debug_add('Enabling script abort through client again.');
ignore_user_abort(false);

if ($ip_sudo)
{
    midcom::get()->auth->drop_sudo();
}

//re-enable ob
ob_start();
?>

Reindex complete. Time elapsed: <?php echo round(microtime(true) - $start, 2) . 's'; ?>
</pre>
