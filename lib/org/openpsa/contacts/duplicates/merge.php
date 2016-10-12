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
     * @param string mode, currently valid modes are 'person' and 'group'
     */
    public function __construct($mode, midcom_helper_configuration $config)
    {
        $this->config = $config;
        $this->_object_mode = $mode;
        if ($this->_object_mode !== 'person' && $this->_object_mode !== 'group')
        {
            throw new midcom_error('invalid object mode');
        }
    }

    /**
     * Merges given objects
     *
     * Depending on modes either all or only future dependencies, this method
     * will go trough all components' interface classes and call a merge method there
     *
     * @param object Object that data will be merged to
     * @param object Object that data will be merged from
     */
    public function merge($obj1, $obj2, $merge_mode)
    {
        if (   $merge_mode !== 'all'
            && $merge_mode !== 'future')
        {
            debug_add("invalid mode {$merge_mode}", MIDCOM_LOG_ERROR);
            throw new midcom_error('invalid merge mode');
        }

        // TODO: Check that both objects are of valid class for object mode
        $config = $this->config->get($this->_object_mode . '_merge_configuration');

        $this->_process_dba_classes($obj1, $obj2, $config);

        if ($this->_object_mode == 'person')
        {
            $this->_merge_persons($obj1, $obj2);
        }
    }

    private function _process_dba_classes($obj1, $obj2, $config)
    {
        foreach (array_filter($config) as $classname => $fieldconfig)
        {
            $qb = midcom::get()->dbfactory->new_query_builder($classname);
            $qb->begin_group('OR');
            foreach ($fieldconfig as $field => $conf)
            {
                $qb->add_constraint($field, '=', $obj1->{$conf['target']});
                $qb->add_constraint($field, '=', $obj2->{$conf['target']});
            }
            $qb->end_group();
            $results = $qb->execute();
            $todelete = array();
            foreach ($results as $result)
            {
                $needs_update = false;
                foreach ($fieldconfig as $field => $conf)
                {
                    if ($result->$field == $obj2->{$conf['target']})
                    {
                        $result->$field = $obj1->{$conf['target']};
                        $needs_update = true;

                        if (!empty($conf['duplicate_check']))
                        {
                            $dup = $this->_check_duplicate($results, $result, $conf['duplicate_check']);

                            if (   is_object($dup)
                                || $dup === false)
                            {
                                if (   !is_object($dup)
                                    || !array_key_exists($dup->id, $todelete))
                                {
                                    $todelete[$result->id] = $result;
                                }
                                $needs_update = false;
                                continue 2;
                            }
                        }
                    }
                }
                if (   $needs_update
                    && !$result->update())
                {
                    throw new midcom_error("Failed to update {$classname} #{$result->id}, errstr: " . midcom_connection::get_error_string());
                }
                if ($this->_object_mode == 'person')
                {
                    $this->_merge_metadata($classname, $obj1, $obj2);
                }
            }
            foreach ($todelete as $object)
            {
                if (!$object->delete())
                {
                    throw new midcom_error("Failed to delete {$classname} #{$object->id}, errstr: " . midcom_connection::get_error_string());
                }
            }
        }
    }

    private function _check_duplicate(array $results, midcom_core_dbaobject $object, $field)
    {
        if (method_exists($object, $field))
        {
            return $object->$field();
        }

        foreach ($results as $result)
        {
            if (   $result->$field === $object->$field
                && $result->id !== $object->id)
            {
                return $result;
            }
        }
        return true;
    }

    private function _merge_persons($person1, $person2)
    {
        // Copy fields missing from person1 and present in person2 over
        $skip_properties = array
        (
            'id' => true,
            'guid' => true,
        );
        $changed = false;
        foreach ($person2 as $property => $value)
        {
            // Copy only simple properties not marked to be skipped missing from person1
            if (   empty($person2->$property)
                || !empty($person1->$property)
                || isset($skip_properties[$property])
                || !is_scalar($value))
            {
                continue;
            }
            $person1->$property = $value;
            $changed = true;
        }
        // Avoid unnecessary updates
        if ($changed)
        {
            if (!$person1->update())
            {
                throw new midcom_error("Error updating person #{$person1->id}, errstr: " . midcom_connection::get_error_string());
            }
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
        if (!$obj2->delete())
        {
            throw new midcom_error('Deletion failed: ' . midcom_connection::get_error_string());
        }
        $qb = new midgard_query_builder('midgard_parameter');
        $qb->add_constraint('domain', 'LIKE', 'org.openpsa.contacts.duplicates:%');
        $qb->add_constraint('name', '=', $obj2->guid);
        $results = $qb->execute();
        foreach ($results as $param)
        {
            if (!$param->delete())
            {
                debug_add("Failed to delete parameter {$param->guid}, errstr: " . midcom_connection::get_error_string(), MIDCOM_LOG_ERROR);
            }
        }
    }

    /**
     * Static method to handle components metadata dependencies
     */
    private function _merge_metadata($class, $person1, $person2)
    {
        $qb = call_user_func(array($class, 'new_query_builder'));
        $qb->begin_group('OR');
        $qb->add_constraint('metadata.approver', '=', $person2->guid);
        $qb->add_constraint('metadata.owner', '=', $person2->guid);
        $qb->end_group();
        $objects = $qb->execute();

        foreach ($objects as $object)
        {
            if ($object->metadata->approver == $person2->guid)
            {
                debug_add("Transferred approver to person #{$person1->id} on {$class} #{$object->id}");
                $object->metadata->approver = $person1->guid;
            }
            if ($object->metadata->owner == $person2->guid)
            {
                debug_add("Transferred owner to person #{$person1->id} on {$class} #{$object->id}");
                $object->metadata->owner = $person1->guid;
            }
            if (!$object->update())
            {
                throw new midcom_error("Could not update object {$class} #{$object->id}, errstr: " . midcom_connection::get_error_string());
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
    public function merge_needed()
    {
        $qb = new midgard_query_builder('midgard_parameter');
        $qb->add_constraint('domain', '=', 'org.openpsa.contacts.duplicates:possible_duplicate');
        $qb->add_order('name', 'ASC');
        $qb->set_limit(1);
        $results = @$qb->execute();
        return (!empty($results));
    }
}
