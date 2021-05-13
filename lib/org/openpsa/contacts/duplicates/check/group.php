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
     *
     * @return array with overall P and matched checks
     */
    protected function p_duplicate(array $group1, array $group2) : array
    {
        $ret = [
            'p' => 0,
            'homepage_match' => false,
            'phone_match' => false,
            'phone_street_match' => false,
            'official_match' => false,
            'official_city_match' => false,
            'official_street_match' => false
        ];

        //TODO: read weight values from configuration
        if ($this->match('homepage', $group1, $group2)) {
            $ret['homepage_match'] = true;
            $ret['p'] += 0.2;
        }

        if ($this->match('phone', $group1, $group2)) {
            $ret['phone_match'] = true;
            $ret['p'] += 0.5;
            if ($this->match('street', $group1, $group2)) {
                $ret['phone_street_match'] = true;
                $ret['p'] += 1;
            }
        }

        if ($this->match('official', $group1, $group2)) {
            $ret['official_match'] = true;
            $ret['p'] += 0.2;
            if ($this->match('street', $group1, $group2)) {
                $ret['official_street_match'] = true;
                $ret['p'] += 1;
            }
            if ($this->match('city', $group1, $group2)) {
                $ret['city_street_match'] = true;
                $ret['p'] += 0.5;
            }
        }

        return $ret;
    }
}
