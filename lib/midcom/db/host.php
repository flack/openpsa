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
 * @package midcom.db
 */
class midcom_db_host extends midcom_core_dbaobject
{
    public $__midcom_class_name__ = __CLASS__;
    public $__mgdschema_class_name__ = 'midgard_host';

    public function get_label()
    {
        if (   $this->port == 0
            || $this->port == 80) {
            return "{$this->name}{$this->prefix}";
        }
        return "{$this->name}:{$this->port}{$this->prefix}";
    }

    public function get_icon()
    {
        return 'stock_internet.png';
    }
}
