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

$ip_sudo = midcom::get()->auth->require_admin_or_ip('midcom.services.indexer');

if (midcom::get()->config->get('indexer_backend') === false) {
    throw new midcom_error('No indexer backend has been defined. Aborting.');
}
?>
<pre>
<?php
debug_add('Disabling script abort through client.');
ignore_user_abort(true);
ob_implicit_flush(true);
midcom::get()->disable_limits();

$nap = new midcom_helper_nav();
$nodes = [];
$nodeid = $nap->get_root_node();
$loader = midcom::get()->componentloader;
$indexer = midcom::get()->indexer;

echo "Dropping the index...\n";
$indexer->delete_all();

debug_dump_mem("Initial Memory Usage");

while (!is_null($nodeid)) {
    // Reindex the node...
    $node = $nap->get_node($nodeid);

    echo "Processing Node {$node[MIDCOM_NAV_FULLURL]}...\n";
    debug_print_r("Processing node id {$nodeid}", $node);
    $interface = $loader->get_interface_class($node[MIDCOM_NAV_COMPONENT]);
    if (!$interface->reindex($node[MIDCOM_NAV_OBJECT])) {
        debug_add("Failed to reindex the node {$nodeid} which is of {$node[MIDCOM_NAV_COMPONENT]}.", MIDCOM_LOG_WARN);
        debug_print_r('NAP record was:', $node);
    }

    debug_dump_mem("Mem usage after {$node[MIDCOM_NAV_RELATIVEURL]}; {$node[MIDCOM_NAV_COMPONENT]}");

    // Retrieve all child nodes and append them to $nodes:
    $childs = $nap->list_nodes($nodeid);
    if ($childs === false) {
        throw new midcom_error("Failed to list the child nodes of {$nodeid}. Aborting.");
    }
    $nodes = array_merge($nodes, $childs);
    $nodeid = array_shift($nodes);
}

debug_add('Enabling script abort through client again.');
ignore_user_abort(false);

if ($ip_sudo) {
    midcom::get()->auth->drop_sudo();
}
?>

Reindex complete
</pre>