<?php
/**
 * @package midcom.db
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: host.php 24475 2009-12-16 12:05:15Z flack $
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * MidCOM Legacy Database Abstraction Layer
 *
 * This class encapsulates a classic MidgardHost with its original features.
 *
 * <i>Preliminary Implementation:</i>
 *
 * Be aware that this implementation is incomplete, and grows on a is-needed basis.
 *
 * @package midcom.db
 */
class midcom_db_host extends midcom_baseclasses_database_host
{

    /**
     * The default constructor will create an empty object. Optionally, you can pass
     * an object ID or GUID to the object which will then initialize the object with
     * the corresponding DB instance.
     *
     * @param mixed $id A valid object ID or GUID, omit for an empty object.
     */
    function __construct($id = null)
    {
        parent::__construct($id);
    }

    /**
     * Overwrite the query builder getter with a version retrieving the right type.
     * We need a better solution here in DBA core actually, but it will be difficult to
     * do this as we cannot determine the current class in a polymorphic environment without
     * having a this (this call is static).
     * 
     * @static
     */
    static function new_query_builder()
    {
        return $_MIDCOM->dbfactory->new_query_builder(__CLASS__);
    }

    static function &get_cached($src)
    {
        return $_MIDCOM->dbfactory->get_cached(__CLASS__, $src);
    }

    function get_label()
    {
        if (   $this->port == 0
            || $this->port == 80)
        {
            return "{$this->name}{$this->prefix}";
        }
        return "{$this->name}:{$this->port}{$this->prefix}";
    }

}


?>