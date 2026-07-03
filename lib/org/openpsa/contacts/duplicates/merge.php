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
    private string $_object_mode;

    private midcom_helper_configuration $config;

    /**
     * @param string $mode governs which objects the instance works on.
     *                     currently valid modes are 'person' and 'group'
     */
    public function __construct(string $mode, midcom_helper_configuration $config)
    {
        if (!in_array($mode, ['person', 'group'])) {
            throw new midcom_error('invalid object mode');
        }
        $this->config = $config;
        $this->_object_mode = $mode;
    }

    /**
     * Merges given objects
     *
     * Depending on modes either all or only future dependencies, this method
     * will go trough all components' interface classes and call a merge method there
     */
    public function merge(midcom_core_dbaobject $to, midcom_core_dbaobject $from, string $merge_mode)
    {
        if (!in_array($merge_mode, ['all', 'future'])) {
            debug_add("invalid mode {$merge_mode}", MIDCOM_LOG_ERROR);
            throw new midcom_error('invalid merge mode');
        }

        // TODO: Check that both objects are of valid class for object mode
        $config = $this->config->get_array($this->_object_mode . '_merge_configuration');

        $this->process_dba_classes($to, $from, $config);

        if ($this->_object_mode == 'person') {
            // if person never logged in (or had no account), we don't need to look for metadata
            if ($from->get_parameter('midcom', 'last_login')) {
                $this->merge_metadata($to, $from, $config);
            }
            $this->merge_persons($to, $from);
        }
    }

    private function process_dba_classes(midcom_core_dbaobject $to, midcom_core_dbaobject $from, array $config)
    {
        foreach (array_filter($config) as $classname => $fieldconfig) {
            $qb = midcom::get()->dbfactory->new_query_builder($classname);
            $qb->begin_group('OR');
            foreach ($fieldconfig as $field => $conf) {
                $qb->add_constraint($field, '=', $to->{$conf['target']});
                $qb->add_constraint($field, '=', $from->{$conf['target']});
            }
            $qb->end_group();
            $results = $qb->execute();
            $todelete = [];
            foreach ($results as $result) {
                $needs_update = false;
                foreach ($fieldconfig as $field => $conf) {
                    if ($result->$field == $from->{$conf['target']}) {
                        $result->$field = $to->{$conf['target']};
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
    private function merge_metadata(midcom_db_person $to, midcom_db_person $from, array $config)
    {
        foreach (array_keys($config) as $class) {
            $qb = $class::new_query_builder();
            $qb->begin_group('OR');
            $qb->add_constraint('metadata.approver', '=', $from->guid);
            $qb->add_constraint('metadata.owner', '=', $from->guid);
            $qb->end_group();

            foreach ($qb->execute() as $object) {
                if ($object->metadata->approver == $from->guid) {
                    debug_add("Transferred approver to person #{$to->id} on {$class} #{$object->id}");
                    $object->metadata->approver = $to->guid;
                }
                if ($object->metadata->owner == $from->guid) {
                    debug_add("Transferred owner to person #{$to->id} on {$class} #{$object->id}");
                    $object->metadata->owner = $to->guid;
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

    private function merge_persons(midcom_db_person $to, midcom_db_person $from)
    {
        // Copy fields missing from $to and present in $from over
        $skip_properties = [
            'id' => true,
            'guid' => true,
        ];
        $changed = false;
        foreach ($from as $property => $value) {
            // Copy only simple properties not marked to be skipped missing from person1
            if (   !empty($from->$property)
                && empty($to->$property)
                && !isset($skip_properties[$property])
                && is_scalar($value)) {
                    $to->$property = $value;
                $changed = true;
            }
        }
        // Avoid unnecessary updates
        if ($changed && !$to->update()) {
            throw new midcom_error("Error updating person #{$to->id}, errstr: " . midcom_connection::get_error_string());
        }
        // PONDER: sensible way to do the same for parameters ??
    }

    /**
     * Merges first object with second and then deletes the second
     */
    public function merge_delete(midcom_core_dbaobject $to, midcom_core_dbaobject $from)
    {
        $this->merge($to, $from, 'all');
        if (!$from->delete()) {
            throw new midcom_error('Deletion failed: ' . midcom_connection::get_error_string());
        }
        $qb = new midgard_query_builder('midgard_parameter');
        $qb->add_constraint('domain', 'LIKE', 'org.openpsa.contacts.duplicates:%');
        $qb->add_constraint('name', '=', $from->guid);
        foreach ($qb->execute() as $param) {
            if (!$param->delete()) {
                debug_add("Failed to delete parameter {$param->guid}, errstr: " . midcom_connection::get_error_string(), MIDCOM_LOG_ERROR);
            }
        }
    }

    /**
     * Checks if there are any objects (for the mode given in constructor) that need to be processed
     *
     * Note: does not check user's privileges or that the objects actually exist (or whether it is a person or a group)
     *
     * @return boolean indicating need for processing (merge/not duplicate)
     */
    public function merge_needed() : bool
    {
        $qb = new midgard_query_builder('midgard_parameter');
        $qb->add_constraint('domain', '=', 'org.openpsa.contacts.duplicates:possible_duplicate');
        $qb->set_limit(1);
        return $qb->count() > 0;
    }
}
