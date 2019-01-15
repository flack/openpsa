<?php
/**
 * @package org.openpsa.contacts
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * @property string $fromComponent
 * @property string $objectGuid
 * @property integer $role
 * @property string $description
 * @property integer $person
 * @property integer $status
 * @package org.openpsa.contacts
 */
class org_openpsa_contacts_role_dba extends midcom_core_dbaobject
{
    public $__midcom_class_name__ = __CLASS__;
    public $__mgdschema_class_name__ = 'org_openpsa_role';

    public $_use_rcs = false;

    public static function add($object_guid, $person, $role)
    {
        $mc = self::new_collector('objectGuid', $object_guid);
        $mc->add_constraint('role', '=', $role);
        $mc->add_constraint('person', '=', $person);
        $mc->execute();
        if ($mc->count() > 0) {
            //Resource is already present, aborting silently
            return;
        }

        $new_role = new self();
        $new_role->person = $person;
        $new_role->role = $role;
        $new_role->objectGuid = $object_guid;
        return $new_role->create();
    }

    /**
     * Returns true for NO existing duplicates
     */
    public function check_duplicates()
    {
        $qb = new midgard_query_builder('org_openpsa_role');
        $qb->add_constraint('person', '=', $this->person);
        $qb->add_constraint('objectGuid', '=', $this->objectGuid);
        $qb->add_constraint('role', '=', $this->role);

        if ($this->id) {
            $qb->add_constraint('id', '<>', $this->id);
        }

        return $qb->count() == 0;
    }

    public function _on_creating()
    {
        return $this->check_duplicates();
    }

    public function _on_updating()
    {
        return $this->check_duplicates();
    }
}
