<?php
/**
 * @package midcom.helper
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Itemlist sorter for navigation items sorted by score (leaves and nodes mixed)
 *
 * @package midcom.helper
 */
class midcom_helper_nav_itemlist_score extends midcom_helper_nav_itemlist
{
    /**
     * get_sorted_list  - get a list objects ready for showing.
     *
     * @return mixed  False on failure or an array of navigation items on success
     */
    public function get_sorted_list()
    {
        $nodes_list = $this->_nap->list_nodes($this->parent_node_id);
        $leaves_list = $this->_nap->list_leaves($this->parent_node_id);
        if (   $nodes_list === false
            || $leaves_list === false) {
            return false;
        }

        $result = array_map(array($this->_nap, 'get_node'), $nodes_list);
        $result = array_merge($result, array_map(array($this->_nap, 'get_leaf'), $leaves_list));
        if (!uasort($result, array('midcom_helper_nav_itemlist_score', 'sort_cmp'))) {
            throw new midcom_error('Failed to sort the navigation');
        }
        return $result;
    }

    /**
     * User defined sort comparison method
     *
     * @param array $a    Navigation item array
     * @param array $b    Navigation item array
     * @return integer    Preferred order
     */
    public static function sort_cmp($a, $b)
    {
        // This should also sort out the situation were score is not set.
        if ($a[MIDCOM_NAV_SCORE] === $b[MIDCOM_NAV_SCORE]) {
            return strcmp($a[MIDCOM_NAV_NAME], $b[MIDCOM_NAV_NAME]);
        }

        return (integer) (($a[MIDCOM_NAV_SCORE] < $b[MIDCOM_NAV_SCORE]) ? 1 : -1);
    }
}
