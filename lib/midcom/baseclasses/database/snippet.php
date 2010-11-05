<?php
/**
 * @package midcom.baseclasses
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: snippet.php 23014 2009-07-27 15:44:43Z flack $
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * MidCOM level replacement for the Midgard Snippet record with framework support.
 *
 * The uplink is the owning snippetdir.
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
class midcom_baseclasses_database_snippet extends midcom_core_dbaobject
{
    var $__midcom_class_name__ = __CLASS__;
    var $__mgdschema_class_name__ = 'midgard_snippet';

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

    public function __set($property, $value) 
    {
        if (   $property == 'up'
            && extension_loaded('midgard2'))
        {
            $property = 'snippetdir';
        }
        return parent::__set($property, $value);
    }

    public function __get($property) 
    {
        if (   $property == 'up'
            && extension_loaded('midgard2'))
        {
            $property = 'snippetdir';
        }
        return parent::__get($property);
    }

    /**
     * Returns the Parent of the Snippet.
     *
     * @return MidgardObject Parent object or NULL if there is none.
     */
    function get_parent_guid_uncached()
    {
        if ($this->up == 0)
        {
            return null;
        }

        $parent = new midcom_baseclasses_database_snippetdir($this->up);
        if (! $parent)
        {
            debug_push_class(__CLASS__, __FUNCTION__);
            debug_add("Could not load Snippetdir ID {$this->up} from the database, aborting.",
                MIDCOM_LOG_INFO);
            debug_pop();
            return null;
        }

        return $parent->guid;
    }
}

?>
