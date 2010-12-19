<?php
/**
 * Reindex script.
 *
 * Drops the index, then iterates over all existing topics, retrieves the corresponding
 * interface class and invokes the reindexing.
 *
 * This may take some time.
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
    if (! $_MIDCOM->auth->request_sudo('midcom.services.indexer'))
    {
        throw new midcom_error('Failed to acquire SUDO rights. Aborting.');
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
    throw new midcom_error('No indexer backend has been defined. Aborting.');
}
?>
<pre>
<?php
debug_add('Disabling script abort through client.');
ignore_user_abort(true);
// Disable ob
while(@ob_end_flush());

debug_add("Setting Memorylimit to configured value of {$GLOBALS['midcom_config']['indexer_reindex_memorylimit']} MB");
ini_set('memory_limit', "{$GLOBALS['midcom_config']['indexer_reindex_memorylimit']}M");

$nap = new midcom_helper_nav();
$nodes = Array();
$nodeid = $nap->get_root_node();
$loader = $_MIDCOM->get_component_loader();
$indexer = $_MIDCOM->get_service('indexer');

echo "Dropping the index...\n";
$indexer->delete_all();

debug_dump_mem("Initial Memory Usage");

while (! is_null($nodeid))
{
    // Update script execution time
    // This should suffice for really large topics as well.
    set_time_limit(5000);

    // Reindex the node...
    $node = $nap->get_node($nodeid);

    echo "Processing Node {$node[MIDCOM_NAV_FULLURL]}...\n";
    debug_print_r("Processing node id {$nodeid}", $node);
    $interface = $loader->get_interface_class($node[MIDCOM_NAV_COMPONENT]);
    if (! is_null($interface))
    {
        if (! $interface->reindex($node[MIDCOM_NAV_OBJECT]))
        {
            debug_add("Failed to reindex the node {$nodeid} which is of {$node[MIDCOM_NAV_COMPONENT]}.", MIDCOM_LOG_WARN);
            debug_print_r('NAP record was:', $node);
        }
    }
    else
    {
        debug_add("Failed to retrieve an interface class for the node {$nodeid} which is of {$node[MIDCOM_NAV_COMPONENT]}.", MIDCOM_LOG_WARN);
        debug_print_r('NAP record was:', $node);
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
    $_MIDCOM->auth->drop_sudo();
}

//re-enable OB
ob_start();
?>

Reindex complete
</pre>