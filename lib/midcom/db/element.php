<?php
/**
 * @package midcom.db
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: element.php 24475 2009-12-16 12:05:15Z flack $
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
 * @see midcom_services_dbclassloader
 * @package midcom.db
 */
class midcom_db_element extends midcom_core_dbaobject
{
    var $__midcom_class_name__ = __CLASS__;
    var $__mgdschema_class_name__ = 'midgard_element';

    /**
     * Overwrite the query builder getter with a version retrieving the right type.
     * We need a better solution here in DBA core actually, but it will be difficult to
     * do this as we cannot determine the current class in a polymorphic environment without
     * having a this (this call is static).
     */
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
            debug_print_r('Current element is:', $this);
            debug_add("The Style Element {$this->id} has its style member set to 0, this is a critical database inconsistency.",
                MIDCOM_LOG_INFO);
            return null;
        }

        $parent = new midcom_db_style($this->style);
        if (! $parent)
        {
            debug_add("Could not load Style ID {$this->up} from the database, aborting.",
                MIDCOM_LOG_INFO);
            return null;
        }

        return $parent->guid;
    }
}
?>