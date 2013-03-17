<?php
/**
 * @package org.openpsa.contacts
 * @author Nemein Oy http://www.nemein.com/
 * @copyright Nemein Oy http://www.nemein.com/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * Helper to merge duplicate objects
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
        switch($mode)
        {
            case 'person':
                $this->_object_mode = 'person';
                break;
            case 'group':
                $this->_object_mode = 'group';
                break;
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
     * @return boolean Indicating success/failure
     */
    function merge(&$obj1, &$obj2, $merge_mode)
    {
        switch ($merge_mode)
        {
            case 'all':
                break;
            case 'future':
                break;
            default:
                // Invalid mode
                $this->_errstr = 'invalid merge mode';
                return false;
                break;
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
                break;
        }

        $components = array_keys(midcom::get('componentloader')->manifests);
        //Check all installed components
        foreach ($components as $component)
        {
            if ($component == 'midcom')
            {
                //Skip midcom core
                continue;
            }
            $component_ret = $this->_call_component_merge($component, $obj1, $obj2, $merge_mode);
            if (!$component_ret)
            {
                $this->_errstr = "component {$component} reported failure";
                return false;
            }
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
    private function _call_component_merge($component, &$obj1, &$obj2, $merge_mode)
    {
        try
        {
            $interface = midcom::get('componentloader')->get_interface_class($component);
        }
        catch (midcom_error $e)
        {
            $e->log(MIDCOM_LOG_ERROR);
            // PONDER: false or true (false means the merge will be aborted...)
            return true;
        }

        $method = 'org_openpsa_contacts_duplicates_merge_' . $this->_object_mode;
        if (!method_exists($interface, $method))
        {
            // Component does not wish to merge our stuff
            debug_add("component {$component} does not support merging duplicate objects of type {$this->_object_mode}", MIDCOM_LOG_INFO);
            return true;
        }
        // Call component interface for merge
        $ret = $interface->$method($obj1, $obj2, $merge_mode);

        return $ret;
    }


    /**
     * Merges first object with second and then deletes the second
     *
     * @param object Object that will remain
     * @param object Object that will be deleted
     * @return boolean Indicating success/Failure
     */
    function merge_delete(&$obj1, &$obj2)
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

        $obj1->delete_parameter('org.openpsa.contacts.duplicates:possible_duplicate', $obj2->guid);

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
    static function person_metadata_dependencies_helper($class, &$person1, &$person2, $metadata_fields)
    {
        // Sanity check given class
        if (!class_exists($class))
        {
            return false;
        }
        $tmp = new $class();
        if (!method_exists($tmp, 'new_query_builder'))
        {
            return false;
        }
        if (empty($metadata_fields))
        {
            // Nothing to do
            return true;
        }
        // Get all instances of given class where metadata fields link to person2
        $qb = call_user_func(array($class, 'new_query_builder'));
        $qb->begin_group('OR');
        foreach ($metadata_fields as $field => $link_property)
        {
            if (   empty($link_property)
                || empty($person2->$link_property))
            {
                debug_print_r("Problem with link_property on field {$field}, skipping. metadata_fields:", $metadata_fields, MIDCOM_LOG_WARN);
                continue;
            }
            debug_add("About to add constraint: {$field}, '=', {$person2->$link_property} (class: {$class})");
            // TODO: Copy on purpose when upgrading to PHP5 force copy by value.
            $value = $person2->$link_property;
            // Make sure id is typecast properly
            if ($link_property == 'id')
            {
                $value = (int)$value;
            }
            if (!$qb->add_constraint('metadata.' . $field, '=', $value))
            {
                debug_add("Failure adding constraint, errstr: " . midcom_connection::get_error_string(), MIDCOM_LOG_ERROR);
                return false;
            }
        }
        $qb->end_group();
        $objects = $qb->execute();
        if ($objects === false)
        {
            // QB failure
            debug_add("QB failure for class {$class}, errstr: " . midcom_connection::get_error_string(), MIDCOM_LOG_ERROR);
            return false;
        }
        foreach ($objects as $object)
        {
            $changed = false;
            foreach ($metadata_fields as $field => $link_property)
            {
                if ($object->metadata->$field == $person2->$link_property)
                {
                    debug_add("Transferred {$field} to person {$person1->$link_property} on object {$class}:{$object->guid}");
                    $object->metadata->$field = $person1->$link_property;
                    $changed = true;
                }
            }
            // Avoid unnecessary updates (thought the QB should only feed us objects that need updating it's good to make sure)
            if (!$changed)
            {
                continue;
            }
            if (!$object->update())
            {
                // Failure updating object
                debug_add("Could not update object {$class}:{$object->guid}, errstr: " . midcom_connection::get_error_string(), MIDCOM_LOG_ERROR);
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
                break;
        }
        $qb = new midgard_query_builder('midgard_parameter');
        $qb->add_constraint('domain', '=', 'org.openpsa.contacts.duplicates:possible_duplicate');
        $qb->add_order('name', 'ASC');
        $qb->set_limit(1);
        $results = @$qb->execute();
        if (!empty($results))
        {
            return true;
        }
        return false;
    }
}
?>