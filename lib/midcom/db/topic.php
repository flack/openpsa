<?php
/**
 * @package midcom.db
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * MidCOM level replacement for the Midgard Topic record with framework support.
 *
 * @package midcom.db
 */
class midcom_db_topic extends midcom_core_dbaobject
{
    public $__midcom_class_name__ = __CLASS__;
    public $__mgdschema_class_name__ = 'midgard_topic';

    public function get_label()
    {
        if ($this->extra)
        {
            return $this->extra;
        }
        if ($this->name)
        {
            return $this->name;
        }
        return '#' . $this->id;
    }
}
