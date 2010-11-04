<?php
/**
 * @package midcom
 * @author The Midgard Project, http://www.midgard-project.org 
 * @version $Id: itemlist.php 24753 2010-01-17 01:23:39Z adrenalin $
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
 * @package midcom
 */
abstract class midcom_helper_itemlist 
{
   /** 
    * A reference to the NAP instance we belong to.
    *
    * @access protected
    * @var midcom_helper_nav
    */
    var $_basicnav= null;

    /**
     * A list of standard Midgard sorting orders.
     * 
     * @todo This has yet to be translated using the l10n service
     * @access protected
     * @var Array
     */    
    var $_standard_midgard_sortparams = array
    (
        'alpha'           => 'Sort alphabetically', 
        'reverse alpha'   => 'Reverse of alpha', 
        'name'            => 'Sort by name (alphabetically', 
        'reverse name'    => 'Sort by reverse name', 
        'score'           => 'Sort by score', 
        'reverse score'   => 'Reverse score', 
        'created'         => 'Sort by createddate', 
        'reverse created' => 'Reverse createddate', 
        'revised'         => 'Sort by date revised', 
        'reverse revised' => 'reverse date revised'
    );
  
    var $parent_node_id = null;
    
    /** Initialize the object, used by the factory function.  
     * 
     * @param midcom_helper_nav $basicnav A reference to a NAP object to use.
     * @param integer $parent_topic_id A ID of the topic in which we operate.
     * @access private
     */
    function _init(&$basicnav, &$parent_topic_id) 
    {
        $this->_basicnav =& $basicnav;
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
     *     require_once 'itemlist.php';
     *     $nav_object = midcom_helper_itemlist::factory( $navorder, &$this, $parent_topic);
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
     * @param object _basicnav pointer pointer to the basicnav object.
     * @param integer parent_topic_id pointer to the topic to base the list on.
     * @return midcom_helper_itemlist sortobject
     */
    /** @ignore */
    static public function factory ($sorting, midcom_helper_nav &$_basicnav, &$parent_topic) 
    {
        $class = basename($sorting);
        $class = "midcom_helper_itemlist_{$class}";
        $sortclass = new $class();
        $sortclass->_init($_basicnav, $parent_topic);     
        return $sortclass;
    } 

   /**
    * get_config_parameters - get a list of which configuration parameters 
    * may be set for this object.
    *
    * @return array to be added to the configuration object.
    */
    /** @ignore */
    function get_config_parameters () 
    {
        return $this->_standard_midgard_sortparams;      
    }
}
?>