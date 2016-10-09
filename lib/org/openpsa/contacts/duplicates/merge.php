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
    private $_errstr = false;

    /**
     * Constructor, the parameter governs which objects the instance works on
     * @param string mode, currently valid modes are 'person' and 'group'
     */
    public function __construct($mode)
    {
        $this->_object_mode = $mode;
    }

    /**
     * Merges given objects
     *
     * Depending on modes either all or only future dependencies, this method
     * will go trough all components' interface classes and call a merge method there
     *
     * @param object Object that data will be merged to
     * @param object Object that data will be merged from
     * @return boolean Indicating success/failure
     */
    function merge($obj1, $obj2, $merge_mode)
    {
        if (   $merge_mode !== 'all'
            && $merge_mode !== 'future')
        {
            $this->_errstr = 'invalid merge mode';
            debug_add("invalid mode {$merge_mode}", MIDCOM_LOG_ERROR);
            return false;
        }

        // TODO: Check that both objects are of valid class for object mode
        switch ($this->_object_mode)
        {
            case 'person':
                break;
            case 'group':
                break;
            default:
                // object mode not set properly
                $this->_errstr = 'invalid object mode';
                return false;
        }

        $components = array_keys(midcom::get()->componentloader->manifests);
        //Check all installed components
        foreach ($components as $component)
        {
            if ($component == 'midcom')
            {
                //Skip midcom core
                continue;
            }
            if (!$this->_call_component_merge($component, $obj1, $obj2, $merge_mode))
            {
                $this->_errstr = "component {$component} reported failure";
                return false;
            }
        }
        if ($this->_object_mode == 'person')
        {
            return $this->_merge_persons($obj1, $obj2);
        }

        return true;
    }

    /**
     * Calls the given components interface method for merging duplicates
     * (if said method exists)
     *
     * @param string component name
     * @param object Object that data will be merged from
     * @param object Object that data will be merged to
     * @param string merge mode
     * @return boolean Indicating success/failure
     */
    private function _call_component_merge($component, $obj1, $obj2, $merge_mode)
    {
        try
        {
            $interface = midcom::get()->componentloader->get_interface_class($component);
        }
        catch (midcom_error $e)
        {
            $e->log(MIDCOM_LOG_ERROR);
            // PONDER: false or true (false means the merge will be aborted...)
            return true;
        }

        if (!($interface instanceof org_openpsa_contacts_duplicates_support))
        {
            // Component does not wish to merge our stuff
            debug_add("component {$component} does not support merging duplicate objects of type {$this->_object_mode}", MIDCOM_LOG_INFO);
            return true;
        }
        $config = $interface->get_merge_configuration($this->_object_mode, $merge_mode);

        if (empty($config))
        {
            return true;
        }
        return $this->_process_dba_classes($obj1, $obj2, $config);
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
                        if (!empty($conf['duplicate_check']))
                        {
                            $result->$field = $obj1->{$conf['target']};
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
                        $needs_update = true;
                    }
                }
                if (   $needs_update
                    && !$result->update())
                {
                    debug_add("Failed to update {$classname} #{$result->id}, errstr: " . midcom_connection::get_error_string(), MIDCOM_LOG_ERROR);
                    return false;
                }
                if (   $this->_object_mode == 'person'
                    && !$this->_merge_metadata($classname, $obj1, $obj2))
                {
                    return false;
                }
            }
            foreach ($todelete as $object)
            {
                if (!$object->delete())
                {
                    debug_add("Failed to delete {$classname} #{$object->id}, errstr: " . midcom_connection::get_error_string(), MIDCOM_LOG_ERROR);
                    return false;
                }
            }
        }
        return true;
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
                // Error updating person
                debug_add("Error updating person #{$person1->id}, errstr: " . midcom_connection::get_error_string(), MIDCOM_LOG_ERROR);
                return false;
            }
        }
        // PONDER: sensible way to do the same for parameters ??
        return true;
    }

    /**
     * Merges first object with second and then deletes the second
     *
     * @param midcom_core_dbaobject $obj1 Object that will remain
     * @param midcom_core_dbaobject $obj2 Object that will be deleted
     * @return boolean Indicating success/Failure
     */
    function merge_delete(midcom_core_dbaobject $obj1, midcom_core_dbaobject $obj2)
    {
        if (!$this->merge($obj1, $obj2, 'all'))
        {
            // Some error occurred during merges, abort
            return false;
        }
        $stat = $obj2->delete();
        if (!$stat)
        {
            $this->_errstr = midcom_connection::get_error_string();
        }
        else
        {
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
        return $stat;
    }

    /**
     * Returns somewhat descriptive error strings
     * @return string latest error
     */
    function errstr()
    {
        if ($this->_errstr === false)
        {
            return 'no error';
        }
        if (empty($this->_errstr))
        {
            return 'unknown error';
        }
        return $this->_errstr;
    }

    /**
     * Static method to handle components metadata dependencies
     */
    private function _merge_metadata($class, &$person1, &$person2)
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
                // Failure updating object
                debug_add("Could not update object {$class} #{$object->id}, errstr: " . midcom_connection::get_error_string(), MIDCOM_LOG_ERROR);
                return false;
            }
        }

        return true;
    }

    /**
     * Checks if there are any objects (for the mode given in constructor) that need to be processed
     *
     * Note: does not check user's privileges or that the objects actually exist (the cleanup cronjob
     * handles dangling references)
     * @return boolean indicating need for processing (merge/not duplicate)
     */
    function merge_needed()
    {
        switch ($this->_object_mode)
        {
            case 'person':
                break;
            case 'group':
                break;
            default:
                // object mode not set properly
                $this->_errstr = 'invalid object mode';
                return false;
        }
        $qb = new midgard_query_builder('midgard_parameter');
        $qb->add_constraint('domain', '=', 'org.openpsa.contacts.duplicates:possible_duplicate');
        $qb->add_order('name', 'ASC');
        $qb->set_limit(1);
        $results = @$qb->execute();
        return (!empty($results));
    }
}
