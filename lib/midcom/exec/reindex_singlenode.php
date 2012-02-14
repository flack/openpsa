<?php
/**
 * Reindex script for single node.
 *
 * Reindexes a single node with id given in $_REQUEST['nodeid']
 *
 * @package midcom
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

// IP Address Checks
$ips = $GLOBALS['midcom_config']['indexer_reindex_allowed_ips'];
$ip_sudo = false;
if (   $ips
    && in_array($_SERVER['REMOTE_ADDR'], $ips))
{
    if (! midcom::get('auth')->request_sudo('midcom.services.indexer'))
    {
        throw new midcom_error('Failed to acquire SUDO rights. Aborting.');
    }
    $ip_sudo = true;
}
else
{
    midcom::get('auth')->require_valid_user('basic');
    midcom::get('auth')->require_admin_user();
}

if ($GLOBALS['midcom_config']['indexer_backend'] === false)
{
    throw new midcom_error('No indexer backend has been defined. Aborting.');
}

if (   !isset($_REQUEST['nodeid'])
    || empty($_REQUEST['nodeid']))
{
    throw new midcom_error("\$_REQUEST['nodeid'] must be set to valid node ID");
}

//check if language is passed & set language if needed
if (isset($_REQUEST['language']))
{
    $_MIDCOM->i18n->set_language($_REQUEST['language']);
}

debug_add('Disabling script abort through client.');
ignore_user_abort(true);

debug_add("Setting memory limit to configured value of {$GLOBALS['midcom_config']['midcom_max_memory']}");
ini_set('memory_limit', $GLOBALS['midcom_config']['midcom_max_memory']);

$loader = $_MIDCOM->get_component_loader();
$indexer = $_MIDCOM->get_service('indexer');

$nap = new midcom_helper_nav();
$nodeid = $_REQUEST['nodeid'];
$node = $nap->get_node($nodeid);
if (empty($node))
{
    throw new midcom_error("Could not get node {$_REQUEST['nodeid']}");
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
    throw new midcom_error($msg);
}

echo "Dropping existing documents in node... ";
flush();

if (!$indexer->delete_all("__TOPIC_GUID:{$node[MIDCOM_NAV_OBJECT]->guid}"))
{
    debug_add("Failed to remove documents from index", MIDCOM_LOG_WARN);
}
else
{
    debug_add("Removed documents from index", MIDCOM_LOG_INFO);
}
echo "Done\n";
flush();

$stat = $interface->reindex($node[MIDCOM_NAV_OBJECT]);
if (is_a($stat, 'midcom_services_indexer_client'))
{
    $stat = $stat->reindex();
}

if ($stat === false)
{
    $msg = "Failed to reindex the node {$nodeid} which is of {$node[MIDCOM_NAV_COMPONENT]}.";
    debug_add($msg, MIDCOM_LOG_ERROR);
    debug_print_r('NAP record was:', $node);
    throw new midcom_error($msg);
}
flush();

debug_dump_mem("Mem usage after {$node[MIDCOM_NAV_RELATIVEURL]}; {$node[MIDCOM_NAV_COMPONENT]}");

debug_add('Enabling script abort through client again.');
ignore_user_abort(false);

if ($ip_sudo)
{
    midcom::get('auth')->drop_sudo();
}

echo "Reindex complete for node {$node[MIDCOM_NAV_FULLURL]}\n</pre>";
flush();
// re-enable ob
ob_start();
?>