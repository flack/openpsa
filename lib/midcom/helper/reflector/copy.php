<?php
/**
 * @package midcom.helper.reflector
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * The Grand Unified Reflector, copying helper class
 * @package midcom.helper.reflector
 */
class midcom_helper_reflector_copy extends midcom_baseclasses_components_purecode
{
    /**
     * Source
     *
     * @var mixed        GUID, MgdSchema or MidCOM dba object
     */
    public $source = null;

    /**
     * Target
     *
     * @var mixed        GUID, MgdSchema or MidCOM dba object
     */
    public $target = null;

    /**
     * Exclusion list
     *
     * @var array        List of GUIDs of objects that shall not be copied
     */
    public $exclude = array();

    /**
     * Override properties of the new root object. This feature is
     * directed for overriding e.g. parent information.
     *
     * @var array        Property-value pairs
     */
    public $root_object_values = array();

    /**
     * Switch for attachments
     *
     * @var boolean
     */
    public $copy_attachments = true;

    /**
     * Switch for parameters
     *
     * @var boolean
     */
    public $copy_parameters = true;

    /**
     * Switch for privileges
     *
     * @var boolean
     */
    public $copy_privileges = true;

    /**
     * Switch for metadata
     *
     * @var boolean
     */
    public $copy_metadata = true;

    /**
     * Copy the whole tree
     *
     * @var boolean
     */
    public $copy_tree = true;

    /**
     * Switch for name catenating
     *
     * @var boolean
     */
    public $allow_name_catenate = true;

    /**
     * Metadata fields that shall be copied
     */
    public $copy_metadata_fields = array
    (
        'owner',
        'authors',
        'schedulestart',
        'scheduleend',
        'navnoentry',
        'hidden',
        'score',
    );

    /**
     * Switch for halt on error. If this is set to false, errors will be
     * reported, but will not stop executing
     *
     * @var boolean        Set to false to continue on errors
     */
    public $halt_on_errors = true;

    /**
     * Encountered errors
     *
     * @var array
     */
    public $errors = array();

    /**
     * Newly created objects
     *
     * @var array
     */
    public $new_objects = array();

    /**
     * New root object
     *
     */
    public $new_root_object = null;

    /**
     * Reflectors for different MgdSchema object types
     *
     * @var array         class_name => midcom_helper_reflector
     */
    private $reflectors = array();

    /**
     * Properties for each encountered MgdSchema object
     *
     * @var array         class_name => array of properties
     */
    private $properties = array();

    /**
     * Get the newly created root object
     *
     * @return mixed     Lowest level new MgdSchema object
     */
    public function get_object()
    {
        return $this->new_root_object;
    }

    /**
     * Get object properties
     *
     * @param mixed &$object
     * @return array
     */
    public function get_object_properties(&$object)
    {
        $mgdschema_class = midcom_helper_reflector::resolve_baseclass(get_class($object));

        if (isset($this->properties[$mgdschema_class]))
        {
            return $this->properties[$mgdschema_class];
        }

        // Get property list and start checking (or abort on error)
        if ($_MIDCOM->dbclassloader->is_midcom_db_object($object))
        {
            $properties = $object->get_object_vars();
        }
        else
        {
            $properties = array_keys(get_object_vars($object));
        }

        $return = array();

        $invalid = array
        (
            'id',
            'guid',
            'metadata',
        );

        foreach ($properties as $property)
        {
            // Remove properties that should not be copied
            if (in_array($property, $invalid))
            {
                continue;
            }

            $return[] = $property;
        }

        // Cache them
        $this->properties[$mgdschema_class] = $return;

        // ...and return
        return $return;
    }

    /**
     * Get the parent property for overriding it
     *
     * @param mixed &$object     MgdSchema object for resolving the parent property
     * @return string            Parent property
     */
    public function get_parent_property(&$object)
    {
        $mgdschema_class = midcom_helper_reflector::resolve_baseclass(get_class($object));

        if (isset($this->parent_properties[$mgdschema_class]))
        {
            return $this->parent_properties[$mgdschema_class];
        }

        if (!isset($this->reflectors[$mgdschema_class]))
        {
            $this->reflectors[$mgdschema_class] = new midcom_helper_reflector($mgdschema_class);
        }

        // Get the parent property
        $properties = $this->reflectors[$mgdschema_class]->get_target_properties($object);
        $parent_property = $properties['parent'];
        $this->parent_properties[$mgdschema_class] = $parent_property;

        return $parent_property;
    }

    /**
     * Resolve MgdSchema object from guid or miscellaneous extended object
     *
     * @param mixed &$object    MgdSchema object, GUID or ID
     * @param string $class     MgdSchema class for resolving ID
     * @return mixed MgdSchema object or false on failure
     */
    static public function resolve_object(&$object, $class = null)
    {
        // Check the type of the requested parent
        switch (true)
        {
            case (is_object($object)):
                break;

            case (mgd_is_guid($object)):
                $object = $_MIDCOM->dbfactory->get_object_by_guid($object);
                break;

            case ($class):
                $id = (int) $object;
                $object = new $class($id);

                // Bulletproof the attempt
                if (!$object->guid)
                {
                    return false;
                }

                break;

            default:
                return false;
        }

        $object_class = midcom_helper_reflector::resolve_baseclass(get_class($object));

        // This is an original MgdSchema class
        if ($object_class === get_class($object))
        {
            return $object;
        }

        // Get the initial MgdSchema class
        $object = new $object_class($object->guid);
        return $object;
    }

    /**
     * Copy object tree
     */
    public function copy_tree(&$source, &$parent)
    {
        // Copy the root object
        $root = $this->copy_object(&$source, &$parent);

        if (   !$root
            || !$root->guid)
        {
            $this->errors[] = sprintf($this->_l10n->get('failed to copy object %s'), $source->guid);
            return false;
        }

        // Add the newly copied object to the exclusion list to prevent infinite loops
        $this->exclude[] = $this->source->guid;

        // Get the children
        $children = midcom_helper_reflector_tree::get_child_objects($source);

        if (   !$children
            || count($children) === 0)
        {
            return $root;
        }

        // Loop through the children and copy them to their corresponding parents
        foreach ($children as $subchildren)
        {
            // Get the children of each type
            foreach ($subchildren as $child)
            {
                // Skip the excluded child
                if (in_array($child->guid, $this->exclude))
                {
                    continue;
                }

                $this->copy_tree($child, $root);
            }
        }

        // Return the newly created root object
        return $root;
    }

    /**
     * Copy an object
     *
     * @param mixed &$source     MgdSchema object for reading the parameters
     * @param mixed $target      MgdSchema object for storing the parameters
     * @param mixed $parent      MgdSchema parent object
     * @param array $defaults
     * @return boolean Indicating success
     */
    public function copy_object(&$source, $parent = null, $defaults = array())
    {
        // Resolve the source object
        self::resolve_object(&$source);

        // Duplicate the object
        $class_name = get_class($source);
        $target = new $class_name();

        $properties = $this->get_object_properties(&$source);

        // Copy the object properties
        foreach ($properties as $property)
        {
            // Skip certain fields
            if (preg_match('/^(_|metadata|guid|id)/', $property))
            {
                continue;
            }

            $target->$property = $source->$property;
        }

        // Override requested root object properties
        if (   isset($this->target->guid)
            && $target->guid === $this->target->guid)
        {
            foreach ($this->root_object_values as $name => $value)
            {
                $target->$name = $value;
            }
        }

        // Override with defaults
        if ($defaults)
        {
            foreach ($defaults as $name => $value)
            {
                $target->$name = $value;
            }
        }

        $parent_property = $this->get_parent_property($source);

        // Copy the link to parent
        if ($parent)
        {
            self::resolve_object(&$parent);

            if (   !$parent
                || !$parent->guid)
            {
                return false;
            }
            /**/
            // @TODO: Is there a sure way to determine if the parent is
            // GUID or is it ID? If so, please change it here.
            if (is_string($source->$parent_property))
            {
                $parent_key = 'guid';
            }
            else
            {
                $parent_key = 'id';
            }

            $target->$parent_property = $parent->$parent_key;
        }
        else
        {
            if (is_string($source->$parent_property))
            {
               $target->$parent_property = '';
            }
            else
            {
               $target->$parent_property = 0;
            }
        }

        $name_property = midcom_helper_reflector::get_name_property($target);

        if (   !empty($name_property)
            && !midcom_helper_reflector::name_is_safe_or_empty($target, $name_property))
        {
            debug_add('Source object ' . get_class($source) . " {$source->guid} has unsafe name, rewriting to safe form for the target", MIDCOM_LOG_WARN);
            $name_property = midcom_helper_reflector::get_name_property($target);

            if (empty($name_property))
            {
                $this->errors[] = sprintf($this->_l10n->get('cannot fix unsafe name for source object %s, skipping'), get_class($source) . " {$source->guid}");
                return false;
            }

            $name_parts = explode('.', $target->$name_property, 2);
            if (isset($name_parts[1]))
            {
                $target->$name_property = midcom_helper_misc::generate_urlname_from_string($name_parts[0]) . ".{$name_parts[1]}";
                // Doublecheck safety and fall back if needed
                if (!midcom_helper_reflector::name_is_safe_or_empty($target))
                {
                    $target->$name_property = midcom_helper_misc::generate_urlname_from_string($target->$name_property);
                }
            }
            else
            {
                $target->$name_property = midcom_helper_misc::generate_urlname_from_string($target->$name_property);
            }
            unset($name_parts, $name_property);
        }

        if (   $this->allow_name_catenate
            && $name_property)
        {
            $name = midcom_helper_reflector_tree::generate_unique_name($target);

            if ($name !== $target->$name_property)
            {
                $target->$name_property = $name;
            }
        }

        // This needs to be here, otherwise it will be overridden
        $target->allow_name_catenate = true;
        if (!$target->create())
        {
            $this->errors[] = $this->_l10n->get('failed to create object: ' . mgd_errstr());
            return false;
        }

        // Store for later use - if ever needed
        $this->new_objects[] = $target;

        unset($name_property);

        // Copy parameters
        if (   !$this->copy_parameters(&$source, &$target)
            && $this->halt_on_errors)
        {
            $this->errors[] = $this->_l10n->get('failed to copy parameters');
            return false;
        }

        // Copy metadata
        if (   !$this->copy_metadata(&$source, &$target)
            && $this->halt_on_errors)
        {
            $this->errors[] = $this->_l10n->get('failed to copy metadata');
            return false;
        }

        // Copy attachments
        if (   !$this->copy_attachments(&$source, &$target)
            && $this->halt_on_errors)
        {
            $this->errors[] = $this->_l10n->get('failed to copy attachments');
            return false;
        }

        // Copy privileges
        if (   !$this->copy_privileges(&$source, &$target)
            && $this->halt_on_errors)
        {
            $this->errors[] = $this->_l10n->get('failed to copy privileges');
            return false;
        }

        return $target;
    }

    /**
     * Copy parameters for the object
     *
     * @param mixed &$source      MgdSchema object for reading the parameters
     * @param mixed &$target      MgdSchema object for storing the parameters
     * @return boolean Indicating success
     */
    public function copy_parameters(&$source, &$target)
    {
        if (!$this->copy_parameters)
        {
            return true;
        }

        $params = $source->list_parameters();

        if (count($params) === 0)
        {
            return true;
        }

        // Loop through the parameters
        foreach ($params as $parameter)
        {
            if (!$target->set_parameter($parameter->domain, $parameter->name, $parameter->value))
            {
                $this->errors[] = sprintf($this->_l10n->get('failed to copy parameters from %s to %s'), $source->guid, $target->guid);

                if ($this->halt_on_errors)
                {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Copy metadata for the object
     *
     * @param mixed &$source      MgdSchema object for reading the metadata
     * @param mixed &$target      MgdSchema object for storing the metadata
     * @return boolean Indicating success
     */
    public function copy_metadata(&$source, &$target)
    {
        foreach ($this->copy_metadata_fields as $property)
        {
            $target->metadata->$property = $source->metadata->$property;
        }

        if ($target->update())
        {
            return true;
        }

        $this->errors[] = sprintf($this->_l10n->get('failed to copy metadata from %s to %s'), $source->guid, $target->guid);
        return false;
    }

    /**
     * Copy attachments
     *
     * @param mixed &$source      MgdSchema object for reading the attachments
     * @param mixed &$target      MgdSchema object for storing the attachments
     * @return boolean Indicating success
     */
    public function copy_attachments(&$source, &$target)
    {
        $defaults = array
        (
            'parentguid' => $target->guid,
        );

        foreach ($source->list_attachments() as $attachment)
        {
            $this->copy_object(&$attachment, &$target, $defaults);
        }

        return true;
    }

    /**
     * Copy privileges
     *
     * @param mixed &$source      MgdSchema object for reading the privileges
     * @param mixed &$target      MgdSchema object for storing the privileges
     * @return boolean Indicating success
     */
    public function copy_privileges(&$source, &$target)
    {
        $qb = midcom_db_privilege::new_query_builder();
        $qb->add_constraint('objectguid', '=', $source->guid);

        $results = $qb->execute();

        static $privilege_fields = null;

        if (is_null($privilege_fields))
        {
            $privilege_fields = array
            (
                'classname',
                'assignee',
                'name',
                'value',
            );
        }

        foreach ($results as $privilege)
        {
            $new = new midcom_db_privilege();
            $new->objectguid = $target->guid;

            $new->classname = $privilege->classname;
            $new->privilegename = $privilege->privilegename;
            $new->value = $privilege->value;
            $new->assignee = $privilege->assignee;

            if (!$new->create())
            {
                $this->errors[] = 'privilege creation failed';
                return false;
            }
        }

        return true;
    }

    /**
     * Copy an object
     *
     * @return boolean Indicating success
     */
    public function copy()
    {
        if (!$this->resolve_object(&$this->source))
        {
            $this->errors[] = $this->_l10n->get('failed to get the source object');
            return false;
        }

        if ($this->copy_tree)
        {
            // Disable execution timeout and memory limit, this can be very intensive
            ini_set('max_execution_time', -1);
            ini_set('memory_limit', -1);

            $this->new_root_object = $this->copy_tree(&$this->source, &$this->target);
        }
        else
        {
            $this->new_root_object = $this->copy_object(&$this->source, &$this->target);
        }

        if (   !$this->new_root_object
            || !$this->new_root_object->guid)
        {
            $this->errors[] = $this->_l10n->get('failed to get the new root object');
            return false;
        }

        return true;
    }
}
?>