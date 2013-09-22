<?php
/**
 * @package midcom.db
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * MidCOM DBA class for MidCOM privileges
 *
 * @package midcom.db
 */
class midcom_db_privilege extends midcom_core_dbaobject
{
    public $__midcom_class_name__ = __CLASS__;
    public $__mgdschema_class_name__ = 'midcom_core_privilege_db';

    public $_use_activitystream = false;
    public $_use_rcs = false;
}
?>