<?php
/**
 * @package midcom.helper
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
 * @package midcom.helper
 */
class midcom_helper_nav_itemlist_articlesfirst extends midcom_helper_nav_itemlist
{
    public function get_sorted_list()
    {
        $nodes_list = $this->_nap->list_nodes($this->parent_node_id);
        if ($nodes_list === false)
        {
            throw new midcom_error("Could not retrieve the subnode listing.");
        }

        $leaves_list = $this->_nap->list_leaves($this->parent_node_id);
        if ($leaves_list === false)
        {
            throw new midcom_error("Could not retrieve the leaf listing.");
        }

        $result = array();
        foreach ($leaves_list as $id)
        {
            $result[] = $this->_nap->get_leaf($id);
        }
        foreach ($nodes_list as $id)
        {
            $result[] = $this->_nap->get_node($id);
        }
        return $result;
    }
}
