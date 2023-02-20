<?php
/**
 * @package midcom.db
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * MidCOM level replacement for the Midgard Style Element record with framework support.
 *
 * @property string $name Path name of the element
 * @property integer $style Style the element is in
 * @property string $value Contents of the style element
 * @package midcom.db
 */
class midcom_db_element extends midcom_core_dbaobject
{
    public string $__midcom_class_name__ = __CLASS__;
    public string $__mgdschema_class_name__ = 'midgard_element';
}
