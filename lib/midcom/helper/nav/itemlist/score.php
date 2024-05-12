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
     */
    public function get_sorted_list() : array
    {
        $result = array_merge($this->get_nodes(), $this->get_leaves());
        if (!uasort($result, $this->sort_cmp(...))) {
            throw new midcom_error('Failed to sort the navigation');
        }
        return $result;
    }

    /**
     * User defined sort comparison method
     *
     * @param array $a    Navigation item array
     * @param array $b    Navigation item array
     */
    private function sort_cmp($a, $b) : int
    {
        return $b[MIDCOM_NAV_SCORE] <=> $a[MIDCOM_NAV_SCORE];
    }
}
