<?php
/**
 * @package midcom
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: score.php 26464 2010-06-28 13:15:28Z flack $
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Itemlist sorter for navigation items sorted by score (leaves and nodes mixed)
 *
 * @package midcom
 */
class midcom_helper_itemlist_score extends midcom_helper_itemlist
{
    /**
     * get_sorted_list  - get a list objects ready for showing.
     *
     * @access public
     * @return mixed  False on failure or an array of navigation items on success
     */
    function get_sorted_list()
    {
        $nodes_list = $this->_basicnav->list_nodes($this->parent_node_id);
        if ($nodes_list === false)
        {
            return false;
        }
        $leaves_list = $this->_basicnav->list_leaves($this->parent_node_id);
        if ($leaves_list === false)
        {
            return false;
        }

        // If there are no child elements, return empty array
        if (   count($nodes_list) == 0
            && count($leaves_list) == 0)
        {
            return array();
        }

        $result = array();

        if ($nodes_list)
        {
            foreach ($nodes_list as $node_id)
            {
                $result[] = $this->_basicnav->get_node($node_id);
            }
        }
        if ($leaves_list)
        {
            foreach ($leaves_list as $leaf_id)
            {
                $result[] = $this->_basicnav->get_leaf($leaf_id);
            }
        }
        if (!uasort($result, array ('midcom_helper_itemlist_score', 'sort_cmp')))
        {
            $_MIDCOM->generate_error(MIDCOM_ERRCRIT, 'Failed to sort the navigation');
        }
        return $result;
    }

    /**
     * User defined sort comparison method
     *
     * @static
     * @access public
     * @param array $a    Navigation item array
     * @param array $b    Navigation item array
     * @return integer    Preferred order
     */
    function sort_cmp ($a, $b)
    {
        // This should also sort out the situation were score is not
        // set.
        if ($a[MIDCOM_NAV_SCORE] === $b[MIDCOM_NAV_SCORE])
        {
             return strcmp($a[MIDCOM_NAV_NAME], $b[MIDCOM_NAV_NAME]);
        }

        return (integer) (($a[MIDCOM_NAV_SCORE] < $b[MIDCOM_NAV_SCORE]) ? 1 : -1);
    }
}
?>