<pre>
<?php

/**
 * Legacy Attachment indexing script.
 * 
 * Indexes all attachments which are not obviously indexed by other means. This
 * includes:
 * 
 * - Any for of datamanager controlled attachments used for storage of long
 *   varibales, recognized by their data_ name prefix.
 * - Any blob/image type attachments, recognized by some of their parameters.
 * - Any leaves associated with net.siriux.photos topics are skipped completely.
 * 
 * NAP will used to traverse the registered topic tree, indexing all attachments
 * of each node and leaf found.
 * 
 * This may take some time.
 * 
 * <i>Handle with care!</i> This handler unconditionally indexes all attachments 
 * found and not filtered along the above conditions. This might lead to attachments
 * being indexed accidentially or, which is even worse, already indexed attachments
 * being overwritten, loosing additional information that might be present.
 * 
 * @package midcom
 * @author The Midgard Project, http://www.midgard-project.org 
 * @version $Id$
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

$_MIDCOM->auth->require_admin_user();

if ($GLOBALS['midcom_config']['indexer_backend'] === false)
{
    $_MIDCOM->generate_error(MIDCOM_ERRCRIT, 'No indexer backend has been defined. Aborting.');
}

/**
 * Indexes a given object using the indexer passed to the function.
 * 
 * If an attachment is passed to the function, it is ignored silently.
 * 
 * @param midcom_services_indexer &$indexer The indexer instance to use.
 * @param MidgardObject $object The object to reindex. 
 */
function index_object(&$indexer, $object)
{
    $attachments = $object->list_attachments();
    if (! $attachments)
    {
        debug_add('Failed to query attachments for the object: ' . midcom_application::get_error_string());
        return;
    }
    foreach ($attachments as $attachment)
    {
        if (substr($attachment->name, 0, 5) == 'data_')
        {
            debug_print_r("This looks like legacy a DM attachment stored field ('data_' name match), skipping:", $attachment);
            continue;
        }
        
        // Check only for a truth value, as we are looking only for a set parameter
        if (   $attachment->get_parameter('midcom.helper.datamanager.datatype.blob', 'fieldname')
            || $attachment->get_parameter('midcom.helper.datamanager.datatype.image', 'parent_guid'))
        {
            debug_print_r('This looks like a blob/image type, skipping:', $attachment);
            continue;
        }
        echo "\n...".$attachment->name;
        $document = new midcom_services_indexer_document_attachment($attachment, $object);
        $indexer->index($document);
    }
}

debug_push('exec-index-legacy-attachments');

debug_add('Disabling script abort through client.');
ignore_user_abort(true);

$nap = new midcom_helper_nav();
$nodes = Array();
$nodeid = $nap->get_root_node();
$indexer = $_MIDCOM->get_service('indexer');

while (! is_null($nodeid))
{
    // Index the node
    $node = $nap->get_node($nodeid);
    echo "Processing Node {$node[MIDCOM_NAV_FULLURL]}... ";
    
    debug_add("Processing node ID {$nodeid}");

    index_object($indexer, $node[MIDCOM_NAV_OBJECT]);

    if ($node[MIDCOM_NAV_COMPONENT] == 'net.siriux.photos')
    {
        debug_add("This is a photo gallery, not indexing any leaves.");
    }
    else
    {
        // Index the leaves
        $leaves = $nap->list_leaves($nodeid);
        if ($leaves === false)
        {
            debug_pop();
            $_MIDCOM->generate_error(MIDCOM_ERRCRIT, "Failed to list the leaves of {$nodeid}. Aborting.");
        }
        foreach($leaves as $leafid)
        {
            $leaf = $nap->get_leaf($leafid);
            if (is_null($leaf[MIDCOM_NAV_OBJECT]))
            {
                debug_add("The leaf {$leafid} does not have an associated MidgardObject, skipping it.", MIDCOM_LOG_INFO);
                debug_print_r('Leaf strucuture dump:', $leaf); 
                continue;
            }
            debug_add("Processing leaf GUID {$leaf[MIDCOM_NAV_GUID]}");
            index_object($indexer, $leaf[MIDCOM_NAV_OBJECT]);
        }
    }
    
    echo "\ndone\n";
    flush();
    
    // Retrieve all child nodes and append them to $nodes:
    $childs = $nap->list_nodes($nodeid);
    if ($childs === false)
    {
        debug_pop();
        $_MIDCOM->generate_error(MIDCOM_ERRCRIT, "Failed to list the child nodes of {$nodeid}. Aborting.");
    }
    $nodes = array_merge($nodes, $childs);
    $nodeid = array_shift($nodes);
    
    // Update script execution time
    set_time_limit(90);
}

debug_add('Enabling script abort through client again.');
ignore_user_abort(false);

debug_pop();

?>
Index run complete.
</pre>
