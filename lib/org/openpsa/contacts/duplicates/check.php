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
abstract class org_openpsa_contacts_duplicates_check
{
    /**
     * Used to store map of probabilities when seeking duplicates for given person/group
     */
    private array $p_map = [];

    /**
     * Minimum score to count as duplicate
     */
    private int $threshold = 1;

    /**
     * Calculates P for the given two candidates being duplicates
     */
    abstract protected function p_duplicate(array $candidate1, array $candidate2) : float;

    abstract protected function get_class() : string;

    abstract protected function get_fields() : array;

    /**
     * Find all duplicates and mark them
     */
    public function mark_all(bool $output)
    {
        $time_start = time();
        $this->output($output, 'Starting');

        $ret = $this->check_all();
        foreach ($ret as $guid1 => $duplicates) {
            $duplicate1 = $this->load($guid1);
            foreach ($duplicates as $guid2 => $p) {
                $duplicate2 = $this->load($guid2);
                $msg = "Marking {$guid1} (#{$duplicate1->id}) and {$guid2} (#{$duplicate2->id}) as duplicates with P {$p}";
                $duplicate1->set_parameter('org.openpsa.contacts.duplicates:possible_duplicate', $guid2, $p);
                $duplicate2->set_parameter('org.openpsa.contacts.duplicates:possible_duplicate', $guid1, $p);
                $this->output($output, $msg, '&nbsp;&nbsp;&nbsp;');
            }
        }

        $this->output($output, "DONE. Elapsed time " . (time() - $time_start) . " seconds");
    }

    /**
     * Find duplicates for given object
     *
     * @return midcom_core_dbaobject[] List of possible duplicates
     */
    public function find_duplicates(midcom_core_dbaobject $object, int $threshold = 1) : array
    {
        $ret = [];
        $fields = array_flip($this->get_fields());

        foreach ($fields as $name => &$val) {
            $val = $object->$name;
        }
        $normalized = $this->normalize_fields($fields, $object->guid);

        foreach ($this->get_candidates($object) as $candidate) {
            if ($this->p_duplicate($normalized, $candidate) >= $threshold) {
                $ret[] = $this->load($candidate['guid']);
            }
        }

        return $ret;
    }

    private function get_candidates(midcom_core_dbaobject $object = null) : array
    {
        $classname = $this->get_class();
        $fields = $this->get_fields();
        $results = [];
        $mc = $classname::new_collector();

        if ($object) {
            if ($object->id) {
                $mc->add_constraint('id', '<>', $object->id);
            }
            // TODO: Avoid objects marked as not_duplicate already in this phase.
            $mc->begin_group('OR');
            foreach ($fields as $field) {
                if ($field != 'id' && $object->$field) {
                    $mc->add_constraint($field, 'LIKE', $object->$field);
                }
            }
            $mc->end_group();
        }

        foreach ($mc->get_rows($fields) as $guid => $result) {
            $results[] = $this->normalize_fields($result, $guid);
        }
        return $results;
    }

    protected function match(string $property, array $data1, array $data2) : bool
    {
        if (   !empty($data1[$property])
            && $data1[$property] == $data2[$property]) {
            return true;
        }
        return false;
    }

    private function load(string $guid) : midcom_core_dbaobject
    {
        $classname = $this->get_class();
        return $classname::get_cached($guid);
    }

    /**
     * Prepare fields for easier comparison
     */
    private function normalize_fields(array $fields, string $guid) : array
    {
        $fields = array_map('strtolower', array_map('trim', $fields));
        $fields['guid'] = $guid;

        return $fields;
    }

    /**
     * Find duplicates in database
     *
     * @return array array of persons with their possible duplicates
     */
    protected function check_all(int $threshold = 1) : array
    {
        $this->p_map = []; //Make sure this is clean before starting
        $this->threshold = $threshold;
        midcom::get()->disable_limits();

        // PONDER: Can we do this in smaller batches using find_duplicated_person
        /*
         IDEA: Make an AT method for checking single persons duplicates, then another to batch
         register a check for every person in batches of say 500.
         */
        $candidates = $this->get_candidates();

        array_walk($candidates, $this->check_all_arraywalk(...), $candidates);

        return $this->p_map;
    }

    /**
     * Used by check_all() to walk the QB result and checking each against the rest
     */
    protected function check_all_arraywalk(array $arr1, $key1, array $objects)
    {
        foreach ($objects as $key2 => $arr2) {
            if ($arr1['guid'] == $arr2['guid']) {
                continue;
            }

            // we've already examined this combination from the other end
            if ($key2 < $key1) {
                if (isset($this->p_map[$arr2['guid']][$arr1['guid']])) {
                    $this->p_map[$arr1['guid']] ??= [];
                    $this->p_map[$arr1['guid']][$arr2['guid']] = $this->p_map[$arr2['guid']][$arr1['guid']];
                }
                continue;
            }

            $p = $this->p_duplicate($arr1, $arr2);

            if ($p < $this->threshold) {
                continue;
            }

            try {
                $obj1 = $this->load($arr1['guid']);
                $obj2 = $this->load($arr2['guid']);
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

            $this->p_map[$arr1['guid']][$arr2['guid']] = $p;
        }
    }

    protected function output($output, string $message, string $indent = '')
    {
        debug_add($message);
        if ($output) {
            echo $indent . 'INFO: ' . $message . "<br/>\n";
            flush();
        }
    }
}
