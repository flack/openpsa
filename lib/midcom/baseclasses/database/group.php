<?php
/**
 * @package midcom.baseclasses
 * @author The Midgard Project, http://www.midgard-project.org 
 * @version $Id: group.php 23014 2009-07-27 15:44:43Z flack $
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * MidCOM level replacement for the Midgard Group record with framework support.
 * 
 * Note, as with all MidCOM DB layer objects, you should not use the GetBy*
 * operations directly, instead, you have to use the constructor's $id parameter.
 * 
 * Also, all QueryBuilder operations need to be done by the factory class 
 * obtainable as midcom_application::dbfactory.
 * 
 * @package midcom.baseclasses
 * @see midcom_services_dbclassloader
 */
class midcom_baseclasses_database_group extends midcom_core_dbaobject
{
    var $__midcom_class_name__ = __CLASS__;
    var $__mgdschema_class_name__ = 'midgard_group';

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

    function get_label()
    {
        return $this->official;
    }

    /**
     * Updates all computed members.
     *
     * @access protected
     */
    function _on_loaded()
    {
        if (empty($this->official))
        {
            $this->official = $this->name;
        }
        
        if (empty($this->official))
        {
            $this->official = "Group #{$this->id}";
        }
        return true;
    }
    
    /**
     * Gets the parent object of the current one. 
     * 
     * Groups that have an owner group return the owner group as a parent.
     * 
     * @return midcom_baseclasses_database_group Owner group or null if there is none.
     */
    function get_parent_guid_uncached()
    {
        if ($this->owner == 0)
        {
            return null;
        }
        
        $parent = new midcom_baseclasses_database_group($this->owner);
        if (! $parent)
        {
            debug_push_class(__CLASS__, __FUNCTION__);
            debug_add("Could not load Group ID {$this->owner} from the database, aborting.", 
                MIDCOM_LOG_INFO);
            debug_pop();
            return null;
        }
        
        return $parent->guid;
    }
}

?>