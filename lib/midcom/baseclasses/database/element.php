<?php
/**
 * @package midcom.baseclasses
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: element.php 23014 2009-07-27 15:44:43Z flack $
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * MidCOM level replacement for the Midgard Style Element record with framework support.
 *
 * Note, as with all MidCOM DB layer objects, you should not use the get_by*
 * operations directly, instead, you have to use the constructor's $id parameter.
 *
 * Also, all QueryBuilder operations need to be done by the factory class
 * obtainable through the statically callable new_query_builder() DBA methods.
 *
 * @package midcom.baseclasses
 * @see midcom_services_dbclassloader
 */
class midcom_baseclasses_database_element extends midcom_core_dbaobject
{
    var $__midcom_class_name__ = __CLASS__;
    var $__mgdschema_class_name__ = 'midgard_element';

    function __construct($id = null)
    {
        parent::__construct($id);
    }

    static function new_query_builder()
    {
        return $_MIDCOM->dbfactory->new_query_builder(__CLASS__);
    }

    static function new_collector($domain, $value)
    {
        return $_MIDCOM->dbfactory->new_collector(__CLASS__, $domain, $value);
    }

    static function &get_cached($src)
    {
        return $_MIDCOM->dbfactory->get_cached(__CLASS__, $src);
    }
    
    /**
     * Returns the Parent of the Element. This is the style the element is assigned to.
     *
     * @return MidgardObject Parent object or NULL if there is none.
     */
    function get_parent_guid_uncached()
    {
        if ($this->style == 0)
        {
            debug_push_class(__CLASS__, __FUNCTION__);
            debug_print_r('Current element is:', $this);
            debug_add("The Style Element {$this->id} has its style member set to 0, this is a critical database inconsistency.",
                MIDCOM_LOG_INFO);
            debug_pop();
            return null;
        }

        $parent = new midcom_baseclasses_database_style($this->style);
        if (! $parent)
        {
            debug_push_class(__CLASS__, __FUNCTION__);
            debug_add("Could not load Style ID {$this->up} from the database, aborting.",
                MIDCOM_LOG_INFO);
            debug_pop();
            return null;
        }

        return $parent->guid;
    }
}

?>