<?php
/**
 * @package org.openpsa.contacts
 * @author Nemein Oy http://www.nemein.com/
 * @copyright Nemein Oy http://www.nemein.com/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * Search for duplicate persons and groups in database
 * @package org.openpsa.contacts
 */
class org_openpsa_contacts_duplicates
{
    /**
     * Used to store map of probabilities when seeking duplicates for given person/group
     */
    var $p_map = array();

    /**
     * Pointer to component configuration object.
     */
    var $config = null;

    /**
     * Cache memberships when possible
     */
    private $_membership_cache = array();

    /**
     * Minimum score to count as duplicate
     *
     * @param integer
     */
    private $_threshold = 1;

    /**
     * Find duplicates for given org_openpsa_contacts_person_dba object
     *
     * @param org_openpsa_contacts_person_dba $person object (does not need id)
     * @return org_openpsa_contacts_person_dba[] array of possible duplicates
     */
    function find_duplicates_person(org_openpsa_contacts_person_dba $person, $threshold = 1)
    {
        $this->p_map = array(); //Make sure this is clean before starting
        $ret = array();
        //Search for all potential duplicates (more detailed checking is done later)
        $qb = org_openpsa_contacts_person_dba::new_query_builder();
        if ($person->id)
        {
            $qb->add_constraint('id', '<>', $person->id);
        }
        // TODO: Avoid persons marked as not_duplicate already in this phase.

        $qb->begin_group('OR');
            //Shared firstname
            $this->_add_constraint($qb, $person, 'firstname');
            //Shared lastname
            $this->_add_constraint($qb, $person, 'lastname');
            //Shared email
            $this->_add_constraint($qb, $person, 'email');
            //Shared handphone
            $this->_add_constraint($qb, $person, 'handphone');
            //Shared city
            $this->_add_constraint($qb, $person, 'city');
            //Shared street
            $this->_add_constraint($qb, $person, 'street');
            //Shared homephone
            $this->_add_constraint($qb, $person, 'homephone');
        $qb->end_group();

        $check_persons = $qb->execute();

        foreach ($check_persons as $check_person)
        {
            $p_array = $this->p_duplicate_person($person, $check_person);
            $this->p_map[$check_person->guid] = $p_array;
            if ($p_array['p'] >= $threshold)
            {
                $ret[] = $check_person;
            }
        }

        return $ret;
    }

    private function _add_constraint(midcom_core_query $qb, midcom_core_dbaobject $object, $field)
    {
        if ($object->$field)
        {
            $qb->add_constraint($field, 'LIKE', $object->$field);
        }
    }

    /**
     * Calculates P for the given two persons being duplicates
     *
     * @param array person1
     * @param array person2
     * @return array with overall P and matched checks
     */
    function p_duplicate_person(array $person1, array $person2)
    {
        $ret = array
        (
            'p' => 0,
            'email_match' => false,
            'handphone_match' => false,
            'fname_lname_city_match' => false,
            'fname_lname_street_match' => false,
            'fname_hphone_match' => false,
            'fname_lname_company_match' => false
        );

        //TODO: read weight values from configuration
        if ($this->_match('email', $person1, $person2))
        {
            $ret['email_match'] = true;
            $ret['p'] += 1;
        }

        if ($this->_match('handphone', $person1, $person2))
        {
            $ret['handphone_match'] = true;
            $ret['p'] += 1;
        }

        if ($this->_match('firstname', $person1, $person2))
        {
            if ($this->_match('homephone', $person1, $person2))
            {
                $ret['fname_hphone_match'] = true;
                $ret['p'] += 0.7;
            }
            if ($this->_match('lastname', $person1, $person2))
            {
                if ($this->_match('city', $person1, $person2))
                {
                    $ret['fname_lname_city_match'] = true;
                    $ret['p'] += 0.5;
                }
                if ($this->_match('street', $person1, $person2))
                {
                    $ret['fname_lname_street_match'] = true;
                    $ret['p'] += 0.9;
                }

                // We cannot do this check if person1 hasn't been created yet...
                if (!empty($person1['guid']))
                {
                    $person1_memberships = $this->_load_memberships($person1['id']);
                    $person2_memberships = $this->_load_memberships($person2['id']);

                    foreach ($person1_memberships as $gid)
                    {
                        if (!empty($person2_memberships[$gid]))
                        {
                            $ret['fname_lname_company_match'] = true;
                            $ret['p'] += 0.5;
                            break;
                        }
                    }
                }
            }
        }

        // All checks done, return
        return $ret;
    }

    private function _match($property, array $data1, array $data2)
    {
        if (   !empty($data1[$property])
            && $data1[$property] == $data2[$property])
        {
            return true;
        }
        return false;
    }

    /**
     * Get membership maps
     */
    private function _load_memberships($id)
    {
        if (!isset($this->_membership_cache[$id]))
        {
            $this->_membership_cache[$id] = array();
            $mc = midcom_db_member::new_collector('uid', $id);
            $mc->add_constraint('gid.orgOpenpsaObtype', '<>', org_openpsa_contacts_group_dba::MYCONTACTS);
            $memberships = $mc->get_values('gid');
            foreach ($memberships as $member)
            {
                $this->_membership_cache[$id][$member] = $member;
            }
        }
        return $this->_membership_cache[$id];
    }

    /**
     * Find duplicates for given org_openpsa_contacts_group_dba object
     * @param org_openpsa_contacts_group_dba $group org_openpsa_contacts_group_dba object (does not need id)
     * @return org_openpsa_contacts_group_dba[] array of possible duplicates
     */
    function find_duplicates_group(org_openpsa_contacts_group_dba $group, $threshold = 1)
    {
        $this->p_map = array(); //Make sure this is clean before starting
        $ret = array();
        $qb = org_openpsa_contacts_group_dba::new_query_builder();
        if ($group->id)
        {
            $qb->add_constraint('id', '<>', $group->id);
        }
        $qb->begin_group('OR');
            //Shared official
            $this->_add_constraint($qb, $group, 'official');
            //Shared street
            $this->_add_constraint($qb, $group, 'street');
            //Shared phone
            $this->_add_constraint($qb, $group, 'phone');
            //Shared homepage
            $this->_add_constraint($qb, $group, 'homepage');
            //Shared city
            $this->_add_constraint($qb, $group, 'city');
        $qb->end_group();

        $check_groups = $qb->execute();

        foreach ($check_groups as $check_group)
        {
            $p_array = $this->p_duplicate_group($group, $check_group);
            $this->p_map[$check_group->guid] = $p_array;
            if ($p_array['p'] >= $threshold)
            {
                $ret[] = $check_group;
            }
        }

        return $ret;
    }

    /**
     * Calculates P for the given two persons being duplicates
     *
     * @param array group1
     * @param array group2
     * @return array with overall P and matched checks
     */
    function p_duplicate_group(array $group1, array $group2)
    {
        $ret = array
        (
            'p' => 0,
            'homepage_match' => false,
            'phone_match' => false,
            'phone_street_match' => false,
            'official_match' => false,
            'official_city_match' => false,
            'official_street_match' => false
        );

        //TODO: read weight values from configuration
        if ($this->_match('homepage', $group1, $group2))
        {
            $ret['homepage_match'] = true;
            $ret['p'] += 0.2;
        }

        if ($this->_match('phone', $group1, $group2))
        {
            $ret['phone_match'] = true;
            $ret['p'] += 0.5;
            if ($this->_match('street', $group1, $group2))
            {
                $ret['phone_street_match'] = true;
                $ret['p'] += 1;
            }
        }

        if ($this->_match('official', $group1, $group2))
        {
            $ret['official_match'] = true;
            $ret['p'] += 0.2;
            if ($this->_match('street', $group1, $group2))
            {
                $ret['official_street_match'] = true;
                $ret['p'] += 1;
            }
            if ($this->_match('city', $group1, $group2))
            {
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
        $this->p_map = array(); //Make sure this is clean before starting
        $this->_threshold = $threshold;
        midcom::get()->disable_limits();

        // PONDER: Can we do this in smaller batches using find_duplicated_person
        /*
          IDEA: Make an AT method for checking single persons duplicates, then another to batch
          register a check for every person in batches of say 500.
        */

        $persons = array();

        $mc = org_openpsa_contacts_person_dba::new_collector('metadata.deleted', false);
        $mc->add_value_property('firstname');
        $mc->add_value_property('id');
        $mc->add_value_property('lastname');
        $mc->add_value_property('email');
        $mc->add_value_property('handphone');
        $mc->add_value_property('homephone');
        $mc->add_value_property('city');
        $mc->add_value_property('street');

        $mc->execute();
        $results = $mc->list_keys();
        if (empty($results))
        {
            return $persons;
        }

        foreach (array_keys($results) as $guid)
        {
            $person = $mc->get($guid);
            $persons[] = self::_normalize_person_fields($person, $guid);
        }

        $params = array();
        $params['objects'] =& $persons;
        $params['mode'] = 'person';

        array_walk($persons, array($this, '_check_all_arraywalk'), $params);

        return $this->p_map;
    }

    /**
     * Prepare person fields for easier comparison
     */
    private static function _normalize_person_fields(array $arr, $guid)
    {
        $arr = array_map('strtolower', array_map('trim', $arr));
        $arr['guid'] = $guid;

        return $arr;
    }

    /**
     * Used by check_all_xxx() -method to walk the QB result and checking each against the rest
     */
    private function _check_all_arraywalk(array &$arr1, $key1, array &$params)
    {
        $objects = $params['objects'];
        $p_method = "p_duplicate_{$params['mode']}";
        if (!method_exists($this, $p_method))
        {
            debug_add("method {$p_method} is not valid, invalid mode string ??", MIDCOM_LOG_ERROR);
            return false;
        }

        foreach ($objects as $key2 => $arr2)
        {
            if ($arr1['guid'] == $arr2['guid'])
            {
                continue;
            }

            //we've already examined this combination from the other end
            if ($key2 < $key1)
            {
                if (isset($this->p_map[$arr2['guid']][$arr1['guid']]))
                {
                    if (!isset($this->p_map[$arr1['guid']]))
                    {
                        $this->p_map[$arr1['guid']] = array();
                    }
                    $this->p_map[$arr1['guid']][$arr2['guid']] = $this->p_map[$arr2['guid']][$arr1['guid']];
                }
                continue;
            }

            $p_arr = $this->$p_method($arr1, $arr2);

            if ($p_arr['p'] < $this->_threshold)
            {
                continue;
            }

            $class = 'org_openpsa_contacts_' . $params['mode'] . '_dba';
            try
            {
                $obj1 = $class::get_cached($arr1['guid']);
                $obj2 = $class::get_cached($arr2['guid']);
            }
            catch (midcom_error $e)
            {
                $e->log();
                continue;
            }

            if (   $obj1->get_parameter('org.openpsa.contacts.duplicates:not_duplicate', $obj2->guid)
                || $obj2->get_parameter('org.openpsa.contacts.duplicates:not_duplicate', $obj1->guid))
            {
                // Not-duplicate parameter found, returning zero probability
                continue;
            }

            if (!isset($this->p_map[$arr1['guid']]))
            {
                $this->p_map[$arr1['guid']] = array();
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
        $this->p_map = array(); //Make sure this is clean before starting
        $this->_threshold = $threshold;

        midcom::get()->disable_limits();

        $groups = array();
        $mc = org_openpsa_contacts_group_dba::new_collector('metadata.deleted', false);
        $mc->add_value_property('id');
        $mc->add_value_property('homepage');
        $mc->add_value_property('phone');
        $mc->add_value_property('official');
        $mc->add_value_property('street');
        $mc->add_value_property('city');

        $mc->execute();

        $results = $mc->list_keys();
        if (empty($results))
        {
            return $this->p_map;
        }

        foreach ($results as $guid => $result)
        {
            $group = $mc->get($guid);
            $groups[] = self::_normalize_group_fields($group, $guid);
        }

        $params = array();
        $params['objects'] =& $groups;
        $params['mode'] = 'group';
        array_walk($groups, array($this, '_check_all_arraywalk'), $params);

        return $this->p_map;
    }

    /**
     * Prepare person fields for easier comparison
     */
    private static function _normalize_group_fields(array $arr, $guid)
    {
        $arr = array_map('strtolower', array_map('trim', $arr));
        $arr['guid'] = $guid;

        return $arr;
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
        debug_add("Called on {$time_start}");
        if ($output)
        {
            echo "INFO: Starting with persons<br/>\n";
            flush();
        }
        $ret_persons = $this->check_all_persons();

        foreach ($ret_persons as $p1guid => $duplicates)
        {
            $person1 = org_openpsa_contacts_person_dba::get_cached($p1guid);
            foreach ($duplicates as $p2guid => $details)
            {
                $person2 = org_openpsa_contacts_person_dba::get_cached($p2guid);
                $msg = "Marking persons {$p1guid} (#{$person1->id}) and {$p2guid} (#{$person2->id}) as duplicates with P {$details['p']}";
                debug_add($msg, MIDCOM_LOG_INFO);
                $person1->set_parameter('org.openpsa.contacts.duplicates:possible_duplicate', $p2guid, $details['p']);
                $person2->set_parameter('org.openpsa.contacts.duplicates:possible_duplicate', $p1guid, $details['p']);
                if ($output)
                {
                    echo "&nbsp;&nbsp;&nbsp;INFO: {$msg}<br/>\n";
                    flush();
                }
            }
        }
        debug_add("Done on " . time() . ", took: " . (time() - $time_start) .  " seconds");
        if ($output)
        {
            echo "INFO: DONE with persons. Elapsed time " . (time() - $time_start) . " seconds<br/>\n";
            flush();
        }
    }

    /**
     * Find all duplicate groups and mark them
     */
    function mark_all_groups($output = false)
    {
        $time_start = time();
        debug_add("Called on {$time_start}");
        if ($output)
        {
            echo "INFO: Starting with groups<br/>\n";
            flush();
        }

        $ret_groups = $this->check_all_groups();
        foreach ($ret_groups as $g1guid => $duplicates)
        {
            $group1 = org_openpsa_contacts_group_dba::get_cached($g1guid);
            foreach ($duplicates as $g2guid => $details)
            {
                $group2 = org_openpsa_contacts_group_dba::get_cached($g2guid);
                $msg = "Marking groups {$g1guid} (#{$group1->id}) and {$g2guid} (#{$group2->id}) as duplicates with P {$details['p']}";
                debug_add($msg);
                $group1->set_parameter('org.openpsa.contacts.duplicates:possible_duplicate', $g2guid, $details['p']);
                $group2->set_parameter('org.openpsa.contacts.duplicates:possible_duplicate', $g1guid, $details['p']);
                if ($output)
                {
                    echo "&nbsp;&nbsp;&nbsp;INFO: {$msg}<br/>\n";
                    flush();
                }
            }
        }

        debug_add("Done on " . time() . ", took: " . (time()-$time_start) .  " seconds");
        if ($output)
        {
            echo "INFO: DONE with groups. Elapsed time: " . (time()-$time_start) ." seconds<br/>\n";
            flush();
        }
    }
}
