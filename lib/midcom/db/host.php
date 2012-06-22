<?php
/**
 * @package midcom.db
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * MidCOM level replacement for the Midgard Host record with framework support.
 *
 * Hosts do not have a parent object.
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
class midcom_db_host extends midcom_core_dbaobject
{
    public $__midcom_class_name__ = __CLASS__;
    public $__mgdschema_class_name__ = 'midgard_host';

    function get_label()
    {
        if (   $this->port == 0
            || $this->port == 80)
        {
            return "{$this->name}{$this->prefix}";
        }
        return "{$this->name}:{$this->port}{$this->prefix}";
    }

    function get_icon()
    {
        return 'stock_internet.png';
    }
}
?>