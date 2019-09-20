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
    /**
     * The NAP instance we belong to.
     *
     * @var midcom_helper_nav
     */
    protected $_nap;

    protected $parent_node_id;

    /**
     * Initialize the object, used by the factory function.
     *
     * @param midcom_helper_nav $nap NAP object to use.
     * @param integer $parent_topic_id An ID of the topic in which we operate.
     */
    public function __construct(midcom_helper_nav $nap, $parent_topic_id)
    {
        $this->_nap = $nap;
        $this->parent_node_id = $parent_topic_id;
    }

    protected function get_nodes() : array
    {
        $nodes_list = $this->_nap->list_nodes($this->parent_node_id);
        if ($nodes_list === false) {
            throw new midcom_error("Could not retrieve the subnode listing.");
        }
        return array_map([$this->_nap, 'get_node'], $nodes_list);
    }

    protected function get_leaves() : array
    {
        $leaves_list = $this->_nap->list_leaves($this->parent_node_id);
        if ($leaves_list === false) {
            throw new midcom_error("Could not retrieve the leaf listing.");
        }
        return array_map([$this->_nap, 'get_leaf'], $leaves_list);
    }

    /**
     * Returns the sorted list for this topic according to our sorting criteria.
     *
     * It has to be overridden. Throw midcom_error on any critical failure.
     *
     * @return Array An array of all objects.
     */
    abstract public function get_sorted_list();

    /**
     * Generate the object you want to use for getting a list of items for a certain topic.
     * Use this function to create sorted lists. Example:
     *     $nav_object = midcom_helper_nav_itemlist::factory($navorder, $this, $parent_topic);
     *     $result = $nav_object->get_sorted_list();
     *     print_r($result);
     *     // shows:
     *     array (1 => array (
     *                    MIDCOM_NAV_ID => someid,
     *                    MIDCOM_NAV_NAME => somename,
     *                    MIDCOM_NAV_STYLE => false
     *                    )
     *                    );
     *     Note that most searchstyles do not bother with styles. But it is useful for custom classes.
     *
     *
     * @param string $sorting sorttype (e.g. topicsfirst)
     * @param midcom_helper_nav $nap pointer to the NAP object.
     * @param integer $parent_topic pointer to the topic to base the list on.
     */
    public static function factory($sorting, midcom_helper_nav $nap, $parent_topic) : midcom_helper_nav_itemlist
    {
        $class = "midcom_helper_nav_itemlist_{$sorting}";
        return new $class($nap, $parent_topic);
    }
}
