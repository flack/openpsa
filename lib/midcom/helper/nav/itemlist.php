<?php
/**
 * @package midcom.helper
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Itemlist Interface
 *
 * You have to override the get_sorted_list member, it has to return an array
 * sorted by the constraints you establish.
 *
 * @todo complete documentation
 *
 * @package midcom.helper
 */
abstract class midcom_helper_nav_itemlist
{
   /**
    * A reference to the NAP instance we belong to.
    *
    * @var midcom_helper_nav
    */
    protected $_nap = null;

    var $parent_node_id = null;

    /** Initialize the object, used by the factory function.
     *
     * @param midcom_helper_nav $nap A reference to a NAP object to use.
     * @param integer $parent_topic_id A ID of the topic in which we operate.
     * @access private
     */
    function _init(&$nap, &$parent_topic_id)
    {
        $this->_nap =& $nap;
        $this->parent_node_id = $parent_topic_id;
    }

   /**
    * Returns the sorted list for this topic according to our sorting criteria.
    *
    * It has to be overridden. Call generate_error on any critical failure.
    *
    * @return Array An array of all objects.
    */
    abstract function get_sorted_list();

   /**
    * Get style. If the elements should use a special style, return that here.
    * if not. use default.
    *
    *
    * @return string MidCOM stylename.
    */

    /** @ignore */
    function get_style()
    {
        return false;
    }

    /**
     * factory generate the object you want to use for getting a list of items for a certain topic.
     * Use this function to create sorted lists. Example:
     *     $nav_object = midcom_helper_nav_itemlist::factory( $navorder, &$this, $parent_topic);
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
     * @param string sorting sorttype (eks topicsfirst)
     * @param object pointer to the NAP object.
     * @param integer parent_topic_id pointer to the topic to base the list on.
     * @return midcom_helper_nav_itemlist sortobject
     */
    /** @ignore */
    static public function factory ($sorting, midcom_helper_nav &$nap, &$parent_topic)
    {
        $class = basename($sorting);
        $class = "midcom_helper_nav_itemlist_{$class}";
        $sortclass = new $class();
        $sortclass->_init($nap, $parent_topic);
        return $sortclass;
    }
}
?>