<?php
/**
 * @package org.openpsa.contacts
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * Search for duplicate persons and groups in database
 *
 * @package org.openpsa.contacts
 */
class org_openpsa_contacts_duplicates_check_person extends org_openpsa_contacts_duplicates_check
{
    /**
     * Cache memberships when possible
     */
    private $membership_cache = [];

    protected function get_class() : string
    {
        return org_openpsa_contacts_person_dba::class;
    }

    protected function get_fields() : array
    {
        return ['firstname', 'lastname', 'email', 'handphone', 'city', 'street', 'homephone', 'id'];
    }

    /**
     * Calculates P for the given two persons being duplicates
     */
    protected function p_duplicate(array $person1, array $person2) : float
    {
        $p = 0;

        //TODO: read weight values from configuration
        if ($this->match('email', $person1, $person2)) {
            $p += 1;
        } elseif (!empty($person1['lastname'])) {
            // if user's lastname is part of the email address, check to see if the difference is only in the domain part
            $email1 = preg_replace('/@.+/', '', $person1['email']);
            $email2 = preg_replace('/@.+/', '', $person2['email']);
            if (   str_contains($email1, $person1['lastname'])
                && $email1 == $email2) {
                $p += 0.5;
            }
        }

        if ($this->match('handphone', $person1, $person2)) {
            $p += 1;
        }

        if ($this->match('firstname', $person1, $person2)) {
            if ($this->match('homephone', $person1, $person2)) {
                $p += 0.7;
            }
            if ($this->match('lastname', $person1, $person2)) {
                if ($this->match('city', $person1, $person2)) {
                    $p += 0.5;
                }
                if ($this->match('street', $person1, $person2)) {
                    $p += 0.9;
                }

                // We cannot do this check if person1 hasn't been created yet...
                if (!empty($person1['guid'])) {
                    $person1_memberships = $this->load_memberships($person1['id']);
                    $person2_memberships = $this->load_memberships($person2['id']);
                    $matches = array_intersect($person1_memberships, $person2_memberships);
                    if (!empty($matches)) {
                        $p += (count($matches) * 0.5);
                    }
                }
            }
        }

        // All checks done, return
        return $p;
    }

    /**
     * Get membership maps
     */
    private function load_memberships(int $id) : array
    {
        if (!isset($this->membership_cache[$id])) {
            $this->membership_cache[$id] = [];
            $mc = midcom_db_member::new_collector('uid', $id);
            $this->membership_cache[$id] = $mc->get_values('gid');
        }
        return $this->membership_cache[$id];
    }
}
