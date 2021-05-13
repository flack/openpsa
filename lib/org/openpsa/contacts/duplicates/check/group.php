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
class org_openpsa_contacts_duplicates_check_group extends org_openpsa_contacts_duplicates_check
{
    protected function get_class() : string
    {
        return org_openpsa_contacts_group_dba::class;
    }

    protected function get_fields() : array
    {
        return ['official', 'street', 'phone', 'homepage', 'city', 'id'];
    }

    /**
     * Calculates P for the given two persons being duplicates
     */
    protected function p_duplicate(array $group1, array $group2) : float
    {
        $p = 0;

        //TODO: read weight values from configuration
        if ($this->match('homepage', $group1, $group2)) {
            $p += 0.2;
        }

        if ($this->match('phone', $group1, $group2)) {
            $p += 0.5;
            if ($this->match('street', $group1, $group2)) {
                $p += 1;
            }
        }

        if ($this->match('official', $group1, $group2)) {
            $p += 0.2;
            if ($this->match('street', $group1, $group2)) {
                $p += 1;
            }
            if ($this->match('city', $group1, $group2)) {
                $p += 0.5;
            }
        }

        return $p;
    }
}
