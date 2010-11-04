<?php
/**
 * @package midcom.baseclasses
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: pageelement.php 23014 2009-07-27 15:44:43Z flack $
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * MidCOM level replacement for the Midgard PageElement record with framework support.
 *
 * The uplink is the owning page.
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
class midcom_baseclasses_database_pageelement extends midcom_core_dbaobject
{
    var $__midcom_class_name__ = __CLASS__;
    var $__mgdschema_class_name__ = 'midgard_pageelement';

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
     * Returns the Parent of the Page.
     *
     * @return MidgardObject Parent object or NULL if there is none.
     */
    function get_parent_guid_uncached()
    {
        if ($this->page == 0)
        {
            return null;
        }

        $parent = new midcom_baseclasses_database_page($this->page);
        if (! $parent)
        {
            debug_push_class(__CLASS__, __FUNCTION__);
            debug_add("Could not load Page ID {$this->page} from the database, aborting.",
                MIDCOM_LOG_INFO);
            debug_pop();
            return null;
        }

        return $parent->guid;
    }
}

?>