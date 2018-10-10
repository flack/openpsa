<?php
/**
 * @package org.openpsa.contacts
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * DBA class for contact lists
 *
 * @property string $name Path name of the group
 * @property string $official Official name of the group
 * @property string $extra Additional information about the group
 * @property integer $owner Group the group is under
 * @property string $customerId
 * @property integer $orgOpenpsaAccesstype
 * @property integer $orgOpenpsaObtype
 * @package org.openpsa.contacts
 */
class org_openpsa_contacts_list_dba extends midcom_core_dbaobject
{
    public $__midcom_class_name__ = __CLASS__;
    public $__mgdschema_class_name__ = 'org_openpsa_contacts_list';

    const MYCONTACTS = 500;

    public static function _on_prepare_new_query_builder(&$qb)
    {
        $qb->add_constraint('orgOpenpsaObtype', '=', self::MYCONTACTS);
    }

    public function _on_creating()
    {
        $this->orgOpenpsaObtype = self::MYCONTACTS;
        if (!$this->owner) {
            $root_group = org_openpsa_contacts_interface::find_root_group('__org_openpsa_contacts_list');
            $this->owner = $root_group->id;
        }
        if (!$this->name) {
            $this->name = 'mycontacts_' . $this->person;
        }
        if (!$this->official) {
            $person = new org_openpsa_contacts_person_dba($this->person);
            $l10n = midcom::get()->i18n->get_l10n('org.openpsa.contacts');
            $this->official = sprintf($l10n->get('contacts of %s'), $person->name);
        }

        return true;
    }

    public function add_member($guid)
    {
        if ($this->is_member($guid)) {
            debug_add('Person ' . $guid . ' is already on the user\'s contact list, skipping');
            return;
        }
        $person = new midcom_db_person($guid);

        $member = new midcom_db_member();
        $member->gid = $this->id;
        $member->uid = $person->id;
        if (!$member->create()) {
            throw new midcom_error('Failed to add new member: ' . midcom_connection::get_error_string());
        }
    }

    public function remove_member($guid)
    {
        $qb = midcom_db_member::new_query_builder();
        $qb->add_constraint('gid', '=', $this->id);
        $qb->add_constraint('uid.guid', '=', $guid);
        foreach ($qb->execute() as $result) {
            if (!$result->delete()) {
                throw new midcom_error('Failed to remove member: ' . midcom_connection::get_error_string());
            }
        }
    }

    public function is_member($guid)
    {
        $qb = midcom_db_member::new_query_builder();
        $qb->add_constraint('gid', '=', $this->id);
        $qb->add_constraint('uid.guid', '=', $guid);
        return ($qb->count() > 0);
    }

    public function list_members()
    {
        $mc = midcom_db_member::new_collector('gid', $this->id);
        $mc->add_order('uid.lastname');
        $mc->add_order('uid.firstname');
        return $mc->get_values('uid');
    }
}
