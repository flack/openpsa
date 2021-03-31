<?php
/**
 * @package org.openpsa.contacts
 * @author Nemein Oy http://www.nemein.com/
 * @copyright Nemein Oy http://www.nemein.com/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * Helper to merge duplicate objects
 *
 * @package org.openpsa.contacts
 */
class org_openpsa_contacts_duplicates_merge
{
    private $_object_mode = false;

    /**
     *
     * @var midcom_helper_configuration
     */
    private $config;

    /**
     * Constructor, the parameter governs which objects the instance works on
     *
     * @param string $mode currently valid modes are 'person' and 'group'
     */
    public function __construct(string $mode, midcom_helper_configuration $config)
    {
        $this->config = $config;
        $this->_object_mode = $mode;
        if (!in_array($this->_object_mode, ['person', 'group'])) {
            throw new midcom_error('invalid object mode');
        }
    }

    /**
     * Merges given objects
     *
     * Depending on modes either all or only future dependencies, this method
     * will go trough all components' interface classes and call a merge method there
     *
     * @param object $obj1 Object that data will be merged to
     * @param object $obj2 Object that data will be merged from
     */
    public function merge($obj1, $obj2, string $merge_mode)
    {
        if (!in_array($merge_mode, ['all', 'future'])) {
            debug_add("invalid mode {$merge_mode}", MIDCOM_LOG_ERROR);
            throw new midcom_error('invalid merge mode');
        }

        // TODO: Check that both objects are of valid class for object mode
        $config = $this->config->get($this->_object_mode . '_merge_configuration');

        $this->process_dba_classes($obj1, $obj2, $config);

        if ($this->_object_mode == 'person') {
            // if person never logged in (or had no account), we don't need to look for metadata
            if ($obj2->get_parameter('midcom', 'last_login')) {
                $this->merge_metadata($obj1, $obj2, $config);
            }
            $this->merge_persons($obj1, $obj2);
        }
    }

    private function process_dba_classes($obj1, $obj2, array $config)
    {
        foreach (array_filter($config) as $classname => $fieldconfig) {
            $qb = midcom::get()->dbfactory->new_query_builder($classname);
            $qb->begin_group('OR');
            foreach ($fieldconfig as $field => $conf) {
                $qb->add_constraint($field, '=', $obj1->{$conf['target']});
                $qb->add_constraint($field, '=', $obj2->{$conf['target']});
            }
            $qb->end_group();
            $results = $qb->execute();
            $todelete = [];
            foreach ($results as $result) {
                $needs_update = false;
                foreach ($fieldconfig as $field => $conf) {
                    if ($result->$field == $obj2->{$conf['target']}) {
                        $result->$field = $obj1->{$conf['target']};
                        $needs_update = true;

                        if (!empty($conf['duplicate_check'])) {
                            $dup = $this->check_duplicate($results, $result, $conf['duplicate_check']);

                            if ($dup !== true) {
                                if (   !is_object($dup)
                                    || !array_key_exists($dup->id, $todelete)) {
                                    $todelete[$result->id] = $result;
                                }
                                continue 2;
                            }
                        }
                    }
                }
                if (   $needs_update
                    && !$result->update()) {
                    throw new midcom_error("Failed to update {$classname} #{$result->id}, errstr: " . midcom_connection::get_error_string());
                }
            }
            foreach ($todelete as $object) {
                if (!$object->delete()) {
                    throw new midcom_error("Failed to delete {$classname} #{$object->id}, errstr: " . midcom_connection::get_error_string());
                }
            }
        }
    }

    /**
     * Handle metadata dependencies
     */
    private function merge_metadata($person1, $person2, array $config)
    {
        foreach (array_keys($config) as $class) {
            $qb = $class::new_query_builder();
            $qb->begin_group('OR');
            $qb->add_constraint('metadata.approver', '=', $person2->guid);
            $qb->add_constraint('metadata.owner', '=', $person2->guid);
            $qb->end_group();

            foreach ($qb->execute() as $object) {
                if ($object->metadata->approver == $person2->guid) {
                    debug_add("Transferred approver to person #{$person1->id} on {$class} #{$object->id}");
                    $object->metadata->approver = $person1->guid;
                }
                if ($object->metadata->owner == $person2->guid) {
                    debug_add("Transferred owner to person #{$person1->id} on {$class} #{$object->id}");
                    $object->metadata->owner = $person1->guid;
                }
                if (!$object->update()) {
                    throw new midcom_error("Could not update object {$class} #{$object->id}, errstr: " . midcom_connection::get_error_string());
                }
            }
        }
    }

    private function check_duplicate(array $results, midcom_core_dbaobject $object, string $field)
    {
        if (method_exists($object, $field)) {
            return $object->$field();
        }

        foreach ($results as $result) {
            if (   $result->$field === $object->$field
                && $result->id !== $object->id) {
                return $result;
            }
        }
        return true;
    }

    private function merge_persons($person1, $person2)
    {
        // Copy fields missing from person1 and present in person2 over
        $skip_properties = [
            'id' => true,
            'guid' => true,
        ];
        $changed = false;
        foreach ($person2 as $property => $value) {
            // Copy only simple properties not marked to be skipped missing from person1
            if (   !empty($person2->$property)
                && empty($person1->$property)
                && !isset($skip_properties[$property])
                && is_scalar($value)) {
                $person1->$property = $value;
                $changed = true;
            }
        }
        // Avoid unnecessary updates
        if ($changed && !$person1->update()) {
            throw new midcom_error("Error updating person #{$person1->id}, errstr: " . midcom_connection::get_error_string());
        }
        // PONDER: sensible way to do the same for parameters ??
    }

    /**
     * Merges first object with second and then deletes the second
     *
     * @param midcom_core_dbaobject $obj1 Object that will remain
     * @param midcom_core_dbaobject $obj2 Object that will be deleted
     */
    public function merge_delete(midcom_core_dbaobject $obj1, midcom_core_dbaobject $obj2)
    {
        $this->merge($obj1, $obj2, 'all');
        if (!$obj2->delete()) {
            throw new midcom_error('Deletion failed: ' . midcom_connection::get_error_string());
        }
        $qb = new midgard_query_builder('midgard_parameter');
        $qb->add_constraint('domain', 'LIKE', 'org.openpsa.contacts.duplicates:%');
        $qb->add_constraint('name', '=', $obj2->guid);
        foreach ($qb->execute() as $param) {
            if (!$param->delete()) {
                debug_add("Failed to delete parameter {$param->guid}, errstr: " . midcom_connection::get_error_string(), MIDCOM_LOG_ERROR);
            }
        }
    }

    /**
     * Checks if there are any objects (for the mode given in constructor) that need to be processed
     *
     * Note: does not check user's privileges or that the objects actually exist (the cleanup cronjob
     * handles dangling references)
     *
     * @return boolean indicating need for processing (merge/not duplicate)
     */
    public function merge_needed() : bool
    {
        $qb = new midgard_query_builder('midgard_parameter');
        $qb->add_constraint('domain', '=', 'org.openpsa.contacts.duplicates:possible_duplicate');
        $qb->add_order('name', 'ASC');
        $qb->set_limit(1);
        return $qb->count() > 0;
    }
}
