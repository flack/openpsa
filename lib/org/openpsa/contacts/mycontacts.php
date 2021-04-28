<?php
/**
 * @package org.openpsa.contacts
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * Helper class for managing "My contacts" lists
 *
 * @package org.openpsa.contacts
 */
class org_openpsa_contacts_mycontacts
{
    /**
     * @var midcom_core_user
     */
    private $_user;

    /**
     * @var org_openpsa_contacts_list_dba
     */
    private $_group;

    public function __construct(midcom_core_user $user = null)
    {
        $this->_user = $user ?: midcom::get()->auth->user;
    }

    private function _get_group(bool $autocreate = false) : ?org_openpsa_contacts_list_dba
    {
        if (!$this->_group) {
            $qb = org_openpsa_contacts_list_dba::new_query_builder();
            $qb->add_constraint('person', '=', $this->_user->guid);
            $results = $qb->execute();
            if (!empty($results)) {
                $this->_group = $results[0];
            } elseif ($autocreate) {
                $this->_group = new org_openpsa_contacts_list_dba;
                $this->_group->person = $this->_user->guid;
                midcom::get()->auth->request_sudo('org.openpsa.contacts');
                $this->_group->create();
                $this->_group->set_privilege('midgard:owner', $this->_user->id, MIDCOM_PRIVILEGE_ALLOW);
                midcom::get()->auth->drop_sudo();
            }
        }

        return $this->_group;
    }

    public function add(string $guid)
    {
        $group = $this->_get_group(true);
        $group->add_member($guid);
    }

    public function remove(string $guid)
    {
        if ($group = $this->_get_group()) {
            $group->remove_member($guid);
        }
    }

    public function is_member(string $guid) : bool
    {
        if ($group = $this->_get_group()) {
            return $group->is_member($guid);
        }
        return false;
    }

    /**
     * @return org_openpsa_contacts_person_dba[]
     */
    public function list_members() : array
    {
        if ($group = $this->_get_group()) {
            $memberships = $group->list_members();
            if (!empty($memberships)) {
                $qb = org_openpsa_contacts_person_dba::new_query_builder();
                $qb->add_constraint('id', 'IN', $memberships);
                return $qb->execute();
            }
        }
        return [];
    }
}
