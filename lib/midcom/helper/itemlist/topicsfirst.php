<?php
/**
 * @package midcom
 * @author The Midgard Project, http://www.midgard-project.org 
 * @version $Id: topicsfirst.php 22991 2009-07-23 16:09:46Z flack $
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Itemlist Subclass: Topics, then articles in component sorting order
 * 
 * This sorting mode will keep the original components' sorting order intact, listing the
 * subnodes first, then the leaves. 
 * 
 * @package midcom
 */
class midcom_helper_itemlist_topicsfirst extends midcom_helper_itemlist
{
    function get_sorted_list ()
    {
        $nodes_list = $this->_basicnav->list_nodes($this->parent_node_id);
        if ($nodes_list === false)
        {
            $_MIDCOM->generate_error(MIDCOM_LOG_ERROR,
                "Could not retrieve the subnode listing, this is fatal.");
            // This will exit.
        }

        $leaves_list = $this->_basicnav->list_leaves($this->parent_node_id);
        if ($leaves_list === false)
        {
            $_MIDCOM->generate_error(MIDCOM_LOG_ERROR,
                "Could not retrieve the leaf listing, this is fatal.");
            // This will exit.
        }

        $result = Array();
        foreach ($nodes_list as $id)
        {
            $result[] = $this->_basicnav->get_node($id);
        }
        foreach ($leaves_list as $id)
        {
            $result[] = $this->_basicnav->get_leaf($id);
        }
        return $result;
    }
}
?>