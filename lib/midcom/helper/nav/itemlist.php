<?php
/**
 * @package midcom.helper
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Itemlist abstract base class
 *
 * You have to implement the get_sorted_list member, it has to return an array
 * sorted by the constraints you establish.
 *
 * @todo complete documentation
 *
 * @package midcom.helper
 */
abstract class midcom_helper_nav_itemlist
{
    protected midcom_helper_nav $_nap;

    protected int $parent_node_id;

    /**
     * Initialize the object, used by the factory function.
     */
    public function __construct(midcom_helper_nav $nap, int $parent_topic_id)
    {
        $this->_nap = $nap;
        $this->parent_node_id = $parent_topic_id;
    }

    protected function get_nodes() : array
    {
        return $this->_nap->get_nodes($this->parent_node_id);
    }

    protected function get_leaves() : array
    {
        return $this->_nap->get_leaves($this->parent_node_id);
    }

    /**
     * Returns the sorted list for this topic according to our sorting criteria.
     *
     * It has to be overridden. Throw midcom_error on any critical failure.
     *
     * @return Array An array of all objects.
     */
    abstract public function get_sorted_list() : array;

    /**
     * Generate the object you want to use for getting a list of items for a certain topic.
     *
     * @param array $parent_node NAP node to base the list on.
     */
    public static function factory(midcom_helper_nav $nap, array $parent_node) : midcom_helper_nav_itemlist
    {
        $guid = $parent_node[MIDCOM_NAV_GUID];
        $navorder = (int) midcom_db_parameter::get_by_objectguid($guid, 'midcom.helper.nav', 'navorder');
        if ($navorder === MIDCOM_NAVORDER_ARTICLESFIRST) {
            $navorder = 'articlesfirst';
        } elseif ($navorder === MIDCOM_NAVORDER_SCORE) {
            $navorder = 'score';
        } else {
            $navorder = 'topicsfirst';
        }
        $class = "midcom_helper_nav_itemlist_{$navorder}";

        return new $class($nap, $parent_node[MIDCOM_NAV_ID]);
    }
}
