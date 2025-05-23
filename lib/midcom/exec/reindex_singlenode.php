<?php
use Symfony\Component\HttpFoundation\Request;

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

$ip_sudo = midcom::get()->auth->require_admin_or_ip('midcom.services.indexer');

$indexer = midcom::get()->indexer;

if (!$indexer->enabled()) {
    throw new midcom_error('No indexer backend has been defined. Aborting.');
}

$query = Request::createFromGlobals()->query;
$nodeid = $query->getInt('nodeid');
if (!$nodeid) {
    throw new midcom_error("\$_GET['nodeid'] must be set to valid node ID");
}

//check if language is passed & set language if needed
if ($language = $query->getAlpha('language')) {
    midcom::get()->i18n->set_language($language);
}

debug_add('Disabling script abort through client.');
ignore_user_abort(true);
midcom::get()->disable_limits();

$loader = midcom::get()->componentloader;

$nap = new midcom_helper_nav();
$node = $nap->get_node($nodeid);
if (!$node) {
    throw new midcom_error("Could not get node {$nodeid}");
}

echo "<pre>\n";
echo "Processing node {$node[MIDCOM_NAV_FULLURL]}\n";
debug_print_r("Processing node id {$nodeid}", $node);

$interface = $loader->get_interface_class($node[MIDCOM_NAV_COMPONENT]);

echo "Dropping existing documents in node... ";

if (!$indexer->delete_all("__TOPIC_GUID:{$node[MIDCOM_NAV_OBJECT]->guid}")) {
    debug_add("Failed to remove documents from index", MIDCOM_LOG_WARN);
} else {
    debug_add("Removed documents from index", MIDCOM_LOG_INFO);
}
echo "Done\n";

$stat = $interface->reindex($node[MIDCOM_NAV_OBJECT]);
if ($stat instanceof midcom_services_indexer_client) {
    $stat->reindex();
} elseif ($stat === false) {
    $msg = "Failed to reindex the node {$nodeid} which is of {$node[MIDCOM_NAV_COMPONENT]}.";
    debug_add($msg, MIDCOM_LOG_ERROR);
    debug_print_r('NAP record was:', $node);
    throw new midcom_error($msg);
}

debug_add('Enabling script abort through client again.');
ignore_user_abort(false);

if ($ip_sudo) {
    midcom::get()->auth->drop_sudo();
}

echo "Reindex complete for node {$node[MIDCOM_NAV_FULLURL]}\n</pre>";
