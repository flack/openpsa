<?php
/**
 * @package org.openpsa.contacts
 * @author Nemein Oy http://www.nemein.com/
 * @copyright Nemein Oy http://www.nemein.com/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * Search for duplicate persons and groups in database
 *
 * @package org.openpsa.contacts
 */
class org_openpsa_contacts_duplicates_check
{
    /**
     * Used to store map of probabilities when seeking duplicates for given person/group
     */
    private $p_map = [];

    /**
     * Cache memberships when possible
     */
    private $membership_cache = [];

    /**
     * Minimum score to count as duplicate
     *
     * @param integer
     */
    private $threshold = 1;

    /**
     * Find duplicates for given org_openpsa_contacts_person_dba object
     *
     * @param org_openpsa_contacts_person_dba $person object (does not need id)
     * @return org_openpsa_contacts_person_dba[] array of possible duplicates
     */
    function find_duplicates_person(org_openpsa_contacts_person_dba $person, $threshold = 1)
    {
        $this->p_map = []; //Make sure this is clean before starting
        $ret = [];
        //Search for all potential duplicates (more detailed checking is done later)
        $check_persons = $this->get_person_candidates($person);

        $person = $this->normalize_fields([
            'firstname' => $person->firstname,
            'lastname' => $person->lastname,
            'email' => $person->email,
            'handphone' => $person->handphone,
            'city' => $person->city,
            'street' => $person->street,
            'homephone' => $person->homephone,
            'id' => $person->id
        ], $person->guid);

        foreach ($check_persons as $check_person) {
            $p_array = $this->p_duplicate_person($person, $check_person);
            $this->p_map[$check_person['guid']] = $p_array;
            if ($p_array['p'] >= $threshold) {
                $ret[] = org_openpsa_contacts_person_dba::get_cached($check_person['guid']);
            }
        }

        return $ret;
    }

    private function get_person_candidates(org_openpsa_contacts_person_dba $person = null)
    {
        $mc = org_openpsa_contacts_person_dba::new_collector();

        if ($person) {
            if ($person->id) {
                $mc->add_constraint('id', '<>', $person->id);
            }
            // TODO: Avoid persons marked as not_duplicate already in this phase.

            $mc->begin_group('OR');
            $this->add_constraint($mc, $person, 'firstname');
            $this->add_constraint($mc, $person, 'lastname');
            $this->add_constraint($mc, $person, 'email');
            $this->add_constraint($mc, $person, 'handphone');
            $this->add_constraint($mc, $person, 'city');
            $this->add_constraint($mc, $person, 'street');
            $this->add_constraint($mc, $person, 'homephone');
            $mc->end_group();
        }

        $results = $mc->get_rows(['firstname', 'id', 'lastname', 'email', 'handphone', 'homephone', 'city', 'street']);
        $persons = [];
        foreach ($results as $guid => $result) {
            $persons[] = $this->normalize_fields($result, $guid);
        }
        return $persons;
    }

    private function add_constraint(midcom_core_query $query, midcom_core_dbaobject $object, $field)
    {
        if ($object->$field) {
            $query->add_constraint($field, 'LIKE', $object->$field);
        }
    }

    /**
     * Calculates P for the given two persons being duplicates
     *
     * @param array $person1
     * @param array $person2
     * @return array with overall P and matched checks
     */
    function p_duplicate_person(array $person1, array $person2)
    {
        $ret = [
            'p' => 0,
            'email_match' => false,
            'handphone_match' => false,
            'fname_lname_city_match' => false,
            'fname_lname_street_match' => false,
            'fname_hphone_match' => false,
            'fname_lname_company_match' => false
        ];

        //TODO: read weight values from configuration
        if ($this->match('email', $person1, $person2)) {
            $ret['email_match'] = true;
            $ret['p'] += 1;
        } elseif (!empty($person1['lastname'])) {
            // if user's lastname is part of the email address, check to see if the difference is only in the domain part
            $email1 = preg_replace('/@.+/', '', $person1['email']);
            $email2 = preg_replace('/@.+/', '', $person2['email']);
            if (   strpos($email1, $person1['lastname']) !== false
                && $email1 == $email2) {
                $ret['email_match'] = true;
                $ret['p'] += 0.5;
            }
        }

        if ($this->match('handphone', $person1, $person2)) {
            $ret['handphone_match'] = true;
            $ret['p'] += 1;
        }

        if ($this->match('firstname', $person1, $person2)) {
            if ($this->match('homephone', $person1, $person2)) {
                $ret['fname_hphone_match'] = true;
                $ret['p'] += 0.7;
            }
            if ($this->match('lastname', $person1, $person2)) {
                if ($this->match('city', $person1, $person2)) {
                    $ret['fname_lname_city_match'] = true;
                    $ret['p'] += 0.5;
                }
                if ($this->match('street', $person1, $person2)) {
                    $ret['fname_lname_street_match'] = true;
                    $ret['p'] += 0.9;
                }

                // We cannot do this check if person1 hasn't been created yet...
                if (!empty($person1['guid'])) {
                    $person1_memberships = $this->load_memberships($person1['id']);
                    $person2_memberships = $this->load_memberships($person2['id']);
                    $matches = array_intersect($person1_memberships, $person2_memberships);
                    if (count($matches) > 0) {
                        $ret['fname_lname_company_match'] = true;
                        $ret['p'] += (count($matches) * 0.5);
                    }
                }
            }
        }

        // All checks done, return
        return $ret;
    }

    private function match($property, array $data1, array $data2)
    {
        if (   !empty($data1[$property])
            && $data1[$property] == $data2[$property]) {
            return true;
        }
        return false;
    }

    /**
     * Get membership maps
     */
    private function load_memberships($id)
    {
        if (!isset($this->membership_cache[$id])) {
            $this->membership_cache[$id] = [];
            $mc = midcom_db_member::new_collector('uid', $id);
            $mc->add_constraint('gid.orgOpenpsaObtype', '<>', org_openpsa_contacts_group_dba::MYCONTACTS);
            $memberships = $mc->get_values('gid');
            foreach ($memberships as $member) {
                $this->membership_cache[$id][$member] = $member;
            }
        }
        return $this->membership_cache[$id];
    }

    /**
     * Find duplicates for given org_openpsa_contacts_group_dba object
     *
     * @param org_openpsa_contacts_group_dba $group Group object (does not need id)
     * @return org_openpsa_contacts_group_dba[] List of possible duplicates
     */
    function find_duplicates_group(org_openpsa_contacts_group_dba $group, $threshold = 1)
    {
        $this->p_map = []; //Make sure this is clean before starting
        $ret = [];

        $check_groups = $this->get_group_candidates($group);

        $group = $this->normalize_fields([
            'official' => $group->official,
            'street' => $group->street,
            'phone' => $group->phone,
            'homepage' => $group->homepage,
            'city' => $group->city,
            'id' => $group->id
        ], $group->guid);

        foreach ($check_groups as $check_group) {
            $p_array = $this->p_duplicate_group($group, $check_group);
            $this->p_map[$check_group['guid']] = $p_array;
            if ($p_array['p'] >= $threshold) {
                $ret[] = org_openpsa_contacts_group_dba::get_cached($check_group['guid']);
            }
        }

        return $ret;
    }

    private function get_group_candidates(org_openpsa_contacts_group_dba $group = null)
    {
        $mc = org_openpsa_contacts_group_dba::new_collector();

        if ($group) {
            if ($group->id) {
                $mc->add_constraint('id', '<>', $group->id);
            }
            $mc->begin_group('OR');
            $this->add_constraint($mc, $group, 'official');
            $this->add_constraint($mc, $group, 'street');
            $this->add_constraint($mc, $group, 'phone');
            $this->add_constraint($mc, $group, 'homepage');
            $this->add_constraint($mc, $group, 'city');
            $mc->end_group();
        }
        $results = $mc->get_rows(['id', 'homepage', 'phone', 'official', 'street', 'city']);

        $groups = [];
        foreach ($results as $guid => $result) {
            $groups[] = $this->normalize_fields($result, $guid);
        }
        return $groups;
    }

    /**
     * Calculates P for the given two persons being duplicates
     *
     * @param array $group1
     * @param array $group2
     * @return array with overall P and matched checks
     */
    function p_duplicate_group(array $group1, array $group2)
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

    /**
     * Find duplicates for given all org_openpsa_contacts_person_dba objects in database
     *
     * @return array array of persons with their possible duplicates
     */
    function check_all_persons($threshold = 1)
    {
        $this->p_map = []; //Make sure this is clean before starting
        $this->threshold = $threshold;
        midcom::get()->disable_limits();

        // PONDER: Can we do this in smaller batches using find_duplicated_person
        /*
          IDEA: Make an AT method for checking single persons duplicates, then another to batch
          register a check for every person in batches of say 500.
        */
        $persons = $this->get_person_candidates();

        $params = [];
        $params['objects'] =& $persons;
        $params['mode'] = 'person';

        array_walk($persons, [$this, 'check_all_arraywalk'], $params);

        return $this->p_map;
    }

    /**
     * Prepare fields for easier comparison
     */
    private function normalize_fields(array $arr, $guid)
    {
        $arr = array_map('strtolower', array_map('trim', $arr));
        $arr['guid'] = $guid;

        return $arr;
    }

    /**
     * Used by check_all_xxx() -method to walk the QB result and checking each against the rest
     */
    private function check_all_arraywalk(array &$arr1, $key1, array &$params)
    {
        $objects = $params['objects'];
        $p_method = "p_duplicate_{$params['mode']}";
        if (!method_exists($this, $p_method)) {
            debug_add("method {$p_method} is not valid, invalid mode string ??", MIDCOM_LOG_ERROR);
            return false;
        }

        foreach ($objects as $key2 => $arr2) {
            if ($arr1['guid'] == $arr2['guid']) {
                continue;
            }

            //we've already examined this combination from the other end
            if ($key2 < $key1) {
                if (isset($this->p_map[$arr2['guid']][$arr1['guid']])) {
                    if (!isset($this->p_map[$arr1['guid']])) {
                        $this->p_map[$arr1['guid']] = [];
                    }
                    $this->p_map[$arr1['guid']][$arr2['guid']] = $this->p_map[$arr2['guid']][$arr1['guid']];
                }
                continue;
            }

            $p_arr = $this->$p_method($arr1, $arr2);

            if ($p_arr['p'] < $this->threshold) {
                continue;
            }

            $class = 'org_openpsa_contacts_' . $params['mode'] . '_dba';
            try {
                $obj1 = $class::get_cached($arr1['guid']);
                $obj2 = $class::get_cached($arr2['guid']);
            } catch (midcom_error $e) {
                $e->log();
                continue;
            }

            if (   $obj1->get_parameter('org.openpsa.contacts.duplicates:not_duplicate', $obj2->guid)
                || $obj2->get_parameter('org.openpsa.contacts.duplicates:not_duplicate', $obj1->guid)) {
                // Not-duplicate parameter found, returning zero probability
                continue;
            }

            if (!isset($this->p_map[$arr1['guid']])) {
                $this->p_map[$arr1['guid']] = [];
            }

            $this->p_map[$arr1['guid']][$arr2['guid']] = $p_arr;
        }
    }

    /**
     * Find duplicates for given all org_openpsa_contacts_group_dba objects in database
     *
     * @return array array of groups with their possible duplicates
     */
    function check_all_groups($threshold = 1)
    {
        $this->p_map = []; //Make sure this is clean before starting
        $this->threshold = $threshold;

        midcom::get()->disable_limits();

        $groups = $this->get_group_candidates();
        $params = [];
        $params['objects'] =& $groups;
        $params['mode'] = 'group';
        array_walk($groups, [$this, 'check_all_arraywalk'], $params);

        return $this->p_map;
    }


    public function mark_all($output = false)
    {
        $this->mark_all_persons($output);
        $this->mark_all_groups($output);
    }

    /**
     * Find all duplicate persons and mark them
     */
    function mark_all_persons($output = false)
    {
        $time_start = time();
        $this->output($output, 'Starting with persons');

        $ret_persons = $this->check_all_persons();
        foreach ($ret_persons as $p1guid => $duplicates) {
            $person1 = org_openpsa_contacts_person_dba::get_cached($p1guid);
            foreach ($duplicates as $p2guid => $details) {
                $person2 = org_openpsa_contacts_person_dba::get_cached($p2guid);
                $msg = "Marking persons {$p1guid} (#{$person1->id}) and {$p2guid} (#{$person2->id}) as duplicates with P {$details['p']}";
                $person1->set_parameter('org.openpsa.contacts.duplicates:possible_duplicate', $p2guid, $details['p']);
                $person2->set_parameter('org.openpsa.contacts.duplicates:possible_duplicate', $p1guid, $details['p']);
                $this->output($output, $msg, '&nbsp;&nbsp;&nbsp;');
            }
        }

        $this->output($output, "DONE with persons. Elapsed time " . (time() - $time_start) . " seconds");
    }

    /**
     * Find all duplicate groups and mark them
     */
    function mark_all_groups($output = false)
    {
        $time_start = time();
        $this->output($output, 'Starting with groups');

        $ret_groups = $this->check_all_groups();
        foreach ($ret_groups as $g1guid => $duplicates) {
            $group1 = org_openpsa_contacts_group_dba::get_cached($g1guid);
            foreach ($duplicates as $g2guid => $details) {
                $group2 = org_openpsa_contacts_group_dba::get_cached($g2guid);
                $msg = "Marking groups {$g1guid} (#{$group1->id}) and {$g2guid} (#{$group2->id}) as duplicates with P {$details['p']}";
                $group1->set_parameter('org.openpsa.contacts.duplicates:possible_duplicate', $g2guid, $details['p']);
                $group2->set_parameter('org.openpsa.contacts.duplicates:possible_duplicate', $g1guid, $details['p']);
                $this->output($output, $msg, '&nbsp;&nbsp;&nbsp;');
            }
        }

        $this->output($output, "DONE with groups. Elapsed time " . (time() - $time_start) . " seconds");
    }

    private function output($output, $message, $indent = '')
    {
        debug_add($message);
        if ($output) {
            echo $indent . 'INFO: ' . $message  . "<br/>\n";
            flush();
        }
    }
}
