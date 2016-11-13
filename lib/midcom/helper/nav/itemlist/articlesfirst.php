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
        return array_merge($this->get_leaves(), $this->get_nodes());
    }
}
