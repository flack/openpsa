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
     * @var org_openpsa_contacts_group_dba
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
            $this->_user = midcom::get('auth')->user;
        }
    }

    private function _get_group($autocreate = false)
    {
        if ($this->_group)
        {
            return $this->_group;
        }
        $qb = org_openpsa_contacts_list_dba::new_query_builder();
        $qb->add_constraint('person', '=', $this->_user->guid);
        $results = $qb->execute();
        if (sizeof($results) == 0)
        {
            if ($autocreate)
            {
                $this->_group = new org_openpsa_contacts_list_dba;
                $this->_group->person = $this->_user->guid;
                midcom::get('auth')->request_sudo('org.openpsa.contacts');
                $this->_group->create();
                $this->_group->set_privilege('midgard:owner', $this->_user->id, MIDCOM_PRIVILEGE_ALLOW);
                midcom::get('auth')->drop_sudo();
            }
            else
            {
                return false;
            }
        }
        else
        {
            $this->_group = $results[0];
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
        $group = $this->_get_group();
        if (!$group)
        {
            return false;
        }
        return $group->is_member($guid);
    }

    public function list_members()
    {
        $group = $this->_get_group();
        if (!$group)
        {
            return array();
        }
        $memberships = $group->list_members();
        if (sizeof($memberships) == 0)
        {
            return array();
        }
        $qb = org_openpsa_contacts_person_dba::new_query_builder();
        $qb->add_constraint('id', 'IN', $memberships);
        return $qb->execute();
    }
}
?>