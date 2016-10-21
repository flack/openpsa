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
     * The user whose list we're working on
     *
     * @var midcom_core_user
     */
    private $_user;

    /**
     * The list we're working on
     *
     * @var org_openpsa_contacts_list_dba
     */
    private $_group;

    public function __construct(midcom_core_user $user = null)
    {
        if (null !== $user)
        {
            $this->_user = $user;
        }
        else
        {
            $this->_user = midcom::get()->auth->user;
        }
    }

    /**
     *
     * @param boolean $autocreate
     * @return org_openpsa_contacts_list_dba|false
     */
    private function _get_group($autocreate = false)
    {
        if (!$this->_group)
        {
            $qb = org_openpsa_contacts_list_dba::new_query_builder();
            $qb->add_constraint('person', '=', $this->_user->guid);
            $results = $qb->execute();
            if (sizeof($results) > 0)
            {
                $this->_group = $results[0];
            }
            else if ($autocreate)
            {
                $this->_group = new org_openpsa_contacts_list_dba;
                $this->_group->person = $this->_user->guid;
                midcom::get()->auth->request_sudo('org.openpsa.contacts');
                $this->_group->create();
                $this->_group->set_privilege('midgard:owner', $this->_user->id, MIDCOM_PRIVILEGE_ALLOW);
                midcom::get()->auth->drop_sudo();
            }
            else
            {
                return false;
            }
        }

        return $this->_group;
    }

    public function add($guid)
    {
        $group = $this->_get_group(true);
        $group->add_member($guid);
    }

    public function remove($guid)
    {
        if ($group = $this->_get_group())
        {
            $group->remove_member($guid);
        }
    }

    public function is_member($guid)
    {
        if ($group = $this->_get_group())
        {
            return $group->is_member($guid);
        }
        return false;
    }

    public function list_members()
    {
        if ($group = $this->_get_group())
        {
            $memberships = $group->list_members();
            if (sizeof($memberships) > 0)
            {
                $qb = org_openpsa_contacts_person_dba::new_query_builder();
                $qb->add_constraint('id', 'IN', $memberships);
                return $qb->execute();
            }
        }
        return array();
    }
}
