<?php
/**
 * @package org.openpsa.contacts
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * @package org.openpsa.contacts
 */
class org_openpsa_contacts_role_dba extends midcom_core_dbaobject
{
    public $__midcom_class_name__ = __CLASS__;
    public $__mgdschema_class_name__ = 'org_openpsa_role';

    public static function add($object_guid, $person, $role)
    {
        $mc = self::new_collector('objectGuid', $object_guid);
        $mc->add_constraint('role', '=', $role);
        $mc->add_constraint('person', '=', $person);
        $mc->execute();
        if ($mc->count() > 0)
        {
            //Resource is already present, aborting silently
            return;
        }

        $new_role = new self();
        $new_role->person = $person;
        $new_role->role = $role;
        $new_role->objectGuid = $object_guid;
        return $new_role->create();
    }
}
?>