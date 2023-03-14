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
    private midcom_db_person $person;

    private ?array $contacts = null;

    public function __construct()
    {
        $this->person = midcom::get()->auth->user->get_storage();
    }

    private function load()
    {
        if ($this->contacts === null) {
            $this->contacts = [];
            if ($saved = $this->person->get_parameter('org.openpsa.contacts', 'mycontacts')) {
                $this->contacts = unserialize($saved) ?: [];
            }
        }
    }

    private function save()
    {
        $this->person->set_parameter('org.openpsa.contacts', 'mycontacts', serialize($this->contacts));
    }

    public function add(string $guid)
    {
        $this->load();
        $this->contacts[] = $guid;
        $this->contacts = array_unique($this->contacts);
        $this->save();
    }

    public function remove(string $guid)
    {
        $this->load();
        $key = array_search($guid, $this->contacts);
        if ($key !== false) {
            unset($this->contacts[$key]);
        }
        $this->save();
    }

    public function is_member(string $guid) : bool
    {
        $this->load();
        return in_array($guid, $this->contacts);
    }

    /**
     * @return org_openpsa_contacts_person_dba[]
     */
    public function list_members() : array
    {
        $this->load();
        $qb = org_openpsa_contacts_person_dba::new_query_builder();
        $qb->add_constraint('guid', 'IN', $this->contacts);
        return $qb->execute();
    }
}
