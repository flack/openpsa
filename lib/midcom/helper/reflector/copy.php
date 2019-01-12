<?php
/**
 * @package midcom.helper.reflector
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use midgard\portable\api\mgdobject;

/**
 * The Grand Unified Reflector, copying helper class
 *
 * @package midcom.helper.reflector
 */
class midcom_helper_reflector_copy extends midcom_baseclasses_components_purecode
{
    /**
     * Target
     *
     * @var mixed        MgdSchema or MidCOM dba object
     */
    public $target;

    /**
     * Exclusion list
     *
     * @var array        List of GUIDs of objects that shall not be copied
     */
    public $exclude = [];

    /**
     * Override properties of the new root object. This feature is
     * directed for overriding e.g. parent information.
     *
     * @var array        Property-value pairs
     */
    public $root_object_values = [];

    /**
     * Switch for attachments
     *
     * @var boolean
     */
    public $attachments = true;

    /**
     * Switch for parameters
     *
     * @var boolean
     */
    public $parameters = true;

    /**
     * Switch for privileges
     *
     * @var boolean
     */
    public $privileges = true;

    /**
     * Switch for metadata
     *
     * @var boolean
     */
    public $metadata = true;

    /**
     * Copy the whole tree
     *
     * @var boolean
     */
    public $recursive = true;

    /**
     * Metadata fields that shall be copied
     */
    public $copy_metadata_fields = [
        'owner',
        'authors',
        'schedulestart',
        'scheduleend',
        'navnoentry',
        'hidden',
        'score',
    ];

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
    public $errors = [];

    /**
     * Get the parent property for overriding it
     *
     * @param mgdobject $object  MgdSchema object for resolving the parent property
     * @return string            Parent property
     */
    public static function get_parent_property(mgdobject $object)
    {
        $parent = midgard_object_class::get_property_parent($object);
        if (!$parent) {
            $parent = midgard_object_class::get_property_up($object);

            if (!$parent) {
                throw new midcom_error('Failed to get the parent property for copying');
            }
        }

        return $parent;
    }

    /**
     * Resolve MgdSchema object from midcom object
     *
     * @param mixed $object    MgdSchema or midcom object
     * @return mgdobject|false
     */
    private function resolve_object($object)
    {
        if (!is_object($object)) {
            return false;
        }

        $object_class = midcom_helper_reflector::resolve_baseclass(get_class($object));

        // Get the initial MgdSchema class
        if ($object_class !== get_class($object)) {
            $object = new $object_class($object->guid);
        }

        return $object;
    }

    /**
     * Copy an object tree. Both source and parent may be liberally filled. Source can be either
     * MgdSchema or MidCOM db object of the object and parent can be
     *
     * - MgdSchema object
     * - MidCOM db object
     * - left empty to copy as a parentless object
     *
     * This method is self-aware and will refuse to perform any infinite loops (e.g. to copy
     * itself to its descendant, copying itself again and again and again).
     *
     * Eventually this method will return the first root object that was created, i.e. the root
     * of the new tree.
     *
     * @param mixed $source        MidCOM db or MgdSchema object that will be copied
     * @param mixed $parent        MgdSchema or MidCOM db object
     * @return mixed               False on failure, newly created MgdSchema root object on success
     */
    public function copy_tree($source, $parent)
    {
        // Copy the root object
        $root = $this->copy_object($source, $parent);

        if (empty($root->guid)) {
            $this->errors[] = sprintf($this->_l10n->get('failed to copy object %s'), $source->guid);
            return false;
        }

        // Add the newly copied object to the exclusion list to prevent infinite loops
        $this->exclude[] = $source->guid;

        // Get the children
        $children = midcom_helper_reflector_tree::get_child_objects($source);

        if (empty($children)) {
            return $root;
        }

        // Loop through the children and copy them to their corresponding parents
        foreach ($children as $subchildren) {
            // Get the children of each type
            foreach ($subchildren as $child) {
                // Skip the excluded child
                if (in_array($child->guid, $this->exclude)) {
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
     * @param mixed $source     MgdSchema object for reading the parameters
     * @param mixed $parent      MgdSchema parent object (or null)
     * @param array $defaults
     * @return boolean Indicating success
     */
    public function copy_object($source, $parent, array $defaults = [])
    {
        // Resolve the source object
        $source = $this->resolve_object($source);
        if (!$source) {
            $this->errors[] = $this->_l10n->get('failed to get the source object');
            return false;
        }

        // Duplicate the object
        $class_name = get_class($source);
        $target = new $class_name();

        $properties = midcom_helper_reflector::get_object_fieldnames($source);

        // Copy the object properties
        foreach ($properties as $property) {
            // Skip certain fields
            if (preg_match('/^(_|metadata|guid|id)/', $property)) {
                continue;
            }

            $target->$property = $source->$property;
        }

        // Override requested root object properties
        if (   !empty($this->target->guid)
            && $target->guid === $this->target->guid) {
            foreach ($this->root_object_values as $name => $value) {
                $target->$name = $value;
            }
        }

        // Override with defaults
        foreach ($defaults as $name => $value) {
            $target->$name = $value;
        }

        if ($this->recursive) {
            $parent_property = self::get_parent_property($source);

            // Copy the link to parent
            if ($parent) {
                $parent = $this->resolve_object($parent);

                if (empty($parent->guid)) {
                    return false;
                }

                // @TODO: Is there a sure way to determine if the parent is
                // GUID or is it ID? If so, please change it here.
                $parent_key = (is_string($source->$parent_property)) ? 'guid' : 'id';
                $target->$parent_property = $parent->$parent_key;
            } else {
                $target->$parent_property = (is_string($source->$parent_property)) ? '' : 0;
            }
        }
        if ($name_property = midcom_helper_reflector::get_name_property($target)) {
            $resolver = new midcom_helper_reflector_nameresolver($target);
            $target->$name_property = $resolver->generate_unique_name();
        }

        // This needs to be here, otherwise it will be overridden
        $target->allow_name_catenate = true;
        if (!$target->create()) {
            $this->errors[] = $this->_l10n->get('failed to create object: ' . midcom_connection::get_error_string());
            return false;
        }

        if (   !$this->_copy_data('parameters', $source, $target)
            || !$this->_copy_data('metadata', $source, $target)
            || !$this->_copy_data('attachments', $source, $target)
            || !$this->_copy_data('privileges', $source, $target)) {
            return false;
        }

        return $target;
    }

    /**
     * Copy object data
     *
     * @param string $type        The type of data to copy
     * @param mixed $source      MgdSchema object for reading the parameters
     * @param mixed $target      MgdSchema object for storing the parameters
     * @return boolean Indicating success
     */
    private function _copy_data($type, $source, $target)
    {
        if ($this->$type) {
            $method = 'copy_' . $type;
            if (   !$this->$method($source, $target)
                && $this->halt_on_errors) {
                $this->errors[] = $this->_l10n->get('failed to copy ' . $type);
                return false;
            }
        }

        return true;
    }

    /**
     * Copy parameters for the object
     *
     * @param mixed $source      MgdSchema object for reading the parameters
     * @param mixed $target      MgdSchema object for storing the parameters
     * @return boolean Indicating success
     */
    public function copy_parameters($source, $target)
    {
        $params = $source->list_parameters();

        if (count($params) === 0) {
            return true;
        }

        // Loop through the parameters
        foreach ($params as $parameter) {
            if (!$target->set_parameter($parameter->domain, $parameter->name, $parameter->value)) {
                $this->errors[] = sprintf($this->_l10n->get('failed to copy parameters from %s to %s'), $source->guid, $target->guid);

                if ($this->halt_on_errors) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Copy metadata for the object
     *
     * @param mixed $source      MgdSchema object for reading the metadata
     * @param mixed $target      MgdSchema object for storing the metadata
     * @return boolean Indicating success
     */
    public function copy_metadata($source, $target)
    {
        foreach ($this->copy_metadata_fields as $property) {
            $target->metadata->$property = $source->metadata->$property;
        }

        if ($target->update()) {
            return true;
        }

        $this->errors[] = sprintf($this->_l10n->get('failed to copy metadata from %s to %s'), $source->guid, $target->guid);
        return false;
    }

    /**
     * Copy attachments
     *
     * @param mixed $source      MgdSchema object for reading the attachments
     * @param mixed $target      MgdSchema object for storing the attachments
     * @return boolean Indicating success
     */
    public function copy_attachments($source, $target)
    {
        $defaults = [
            'parentguid' => $target->guid,
        ];

        foreach ($source->list_attachments() as $attachment) {
            $this->copy_object($attachment, $target, $defaults);
        }

        return true;
    }

    /**
     * Copy privileges
     *
     * @param mixed $source      MgdSchema object for reading the privileges
     * @param mixed $target      MgdSchema object for storing the privileges
     * @return boolean Indicating success
     */
    public function copy_privileges($source, $target)
    {
        $qb = midcom_core_privilege_db::new_query_builder();
        $qb->add_constraint('objectguid', '=', $source->guid);

        foreach ($qb->execute() as $privilege) {
            $new = new midcom_core_privilege_db();
            $new->objectguid = $target->guid;

            $new->classname = $privilege->classname;
            $new->privilegename = $privilege->privilegename;
            $new->value = $privilege->value;
            $new->assignee = $privilege->assignee;

            if (!$new->create()) {
                $this->errors[] = 'privilege creation failed';
                return false;
            }
        }

        return true;
    }

    /**
     * Dispatches the copy command according to the attributes set
     *
     * @param midcom_core_dbaobject $source
     * @return mgdobject|false
     */
    public function execute(midcom_core_dbaobject $source)
    {
        if ($this->recursive) {
            // Disable execution timeout and memory limit, this can be very intensive
            midcom::get()->disable_limits();

            $new_root_object = $this->copy_tree($source, $this->target);
        } else {
            $new_root_object = $this->copy_object($source, $this->target);
        }

        if (empty($new_root_object->guid)) {
            $this->errors[] = $this->_l10n->get('failed to get the new root object');
            return false;
        }

        return $new_root_object;
    }
}
