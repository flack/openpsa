<?php
/**
 * Reindex script for single node.
 *
 * Reindexes a single node with id given in $_REQUEST['nodeid']
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

if (   !isset($_REQUEST['nodeid'])
    || empty($_REQUEST['nodeid']))
{
    $_MIDCOM->generate_error(MIDCOM_ERRCRIT, "\$_REQUEST['nodeid'] must be set to valid node ID");
}

//check if language is passed & set language if needed
if(isset($_REQUEST['language']))
{
    $_MIDCOM->i18n->set_language($_REQUEST['language']);
}

debug_add('Disabling script abort through client.');
ignore_user_abort(true);

debug_add("Setting Memorylimit to configured value of {$GLOBALS['midcom_config']['indexer_reindex_memorylimit']} MB");
ini_set('memory_limit', "{$GLOBALS['midcom_config']['indexer_reindex_memorylimit']}M");

$loader = $_MIDCOM->get_component_loader();
$indexer = $_MIDCOM->get_service('indexer');

$nap = new midcom_helper_nav();
$nodeid =& $_REQUEST['nodeid'];
$node = $nap->get_node($nodeid);
if (empty($node))
{
    $_MIDCOM->generate_error(MIDCOM_ERRCRIT, "Could not get node {$_REQUEST['nodeid']}");
}

debug_dump_mem("Initial Memory Usage");

// Update script execution time
// This should suffice for really large topics as well.
set_time_limit(5000);

// Disable ob
while(@ob_end_flush());
echo "<pre>\n";
echo "Processing node {$node[MIDCOM_NAV_FULLURL]}\n";
debug_print_r("Processing node id {$nodeid}", $node);

$interface = $loader->get_interface_class($node[MIDCOM_NAV_COMPONENT]);
if (is_null($interface))
{
    $msg = "Failed to retrieve an interface class for the node {$nodeid} which is of {$node[MIDCOM_NAV_COMPONENT]}.";
    debug_add($msg, MIDCOM_LOG_ERROR);
    debug_print_r('NAP record was:', $node);
    $_MIDCOM->generate_error(MIDCOM_ERRCRIT, $msg);
    // this will exit
}


// Query all documents where __TOPIC_GUID is this topic and delete them (ie, drop only this topic from index)
$existing_documents = $indexer->query("__TOPIC_GUID:{$node[MIDCOM_NAV_OBJECT]->guid}");
if ($existing_documents === false)
{
    $msg = "Query '__TOPIC_GUID:{$node[MIDCOM_NAV_OBJECT]->guid}' returned false, indicating problem with indexer";
    $_MIDCOM->generate_error(MIDCOM_ERRCRIT, $msg);
    // this will exit
}

if (   is_array($existing_documents)
    && !empty($existing_documents))
{
    echo "Dropping existing documents in node... ";
    flush();
    foreach($existing_documents as $document)
    {
        if (!$indexer->delete($document->RI))
        {
            debug_add("Failed to remove document {$document->RI} from index", MIDCOM_LOG_WARN);
        }
        debug_add("Removed document {$document->RI} from index", MIDCOM_LOG_INFO);
    }
    echo "Done\n";
    flush();
}

if (!$interface->reindex($node[MIDCOM_NAV_OBJECT]))
{
    $msg = "Failed to reindex the node {$nodeid} which is of {$node[MIDCOM_NAV_COMPONENT]}.";
    debug_add($msg, MIDCOM_LOG_ERROR);
    debug_print_r('NAP record was:', $node);
    $_MIDCOM->generate_error(MIDCOM_ERRCRIT, $msg);
    // this will exit
}
flush();

debug_dump_mem("Mem usage after {$node[MIDCOM_NAV_RELATIVEURL]}; {$node[MIDCOM_NAV_COMPONENT]}");

debug_add('Enabling script abort through client again.');
ignore_user_abort(false);

if ($ip_sudo)
{
    $_MIDCOM->auth->drop_sudo();
}

// re-enable ob
ob_start();
echo "Reindex complete for node {$node[MIDCOM_NAV_FULLURL]}\n</pre>";
?>