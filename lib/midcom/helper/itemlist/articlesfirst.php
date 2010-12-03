<?php
/**
 * @package midcom
 * @author The Midgard Project, http://www.midgard-project.org 
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Itemlist Subclass: Topics, then articles in component sorting order
 * 
 * This sorting mode will keep the original components' sorting order intact, listing the
 * leaves first, then the subnodes. 
 * 
 * @package midcom
 */
class  midcom_helper_itemlist_articlesfirst extends midcom_helper_itemlist
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
        foreach ($leaves_list as $id)
        {
            $result[] = $this->_basicnav->get_leaf($id);
        }
        foreach ($nodes_list as $id)
        {
            $result[] = $this->_basicnav->get_node($id);
        }
        return $result;
    }
}
?>