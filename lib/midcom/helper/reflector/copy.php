<?php
/**
 * @package midcom.helper.reflector
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use midgard\introspection\helper;

/**
 * The Grand Unified Reflector, copying helper class
 *
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
     */
    public $new_root_object = null;

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
     * @param mixed $object
     * @return array
     */
    public function get_object_properties($object)
    {
        $mgdschema_class = midcom_helper_reflector::resolve_baseclass(get_class($object));

        if (!isset($this->properties[$mgdschema_class])) {
            // Get property list and start checking (or abort on error)
            if (midcom::get()->dbclassloader->is_midcom_db_object($object)) {
                $properties = $object->get_object_vars();
            } else {
                $helper = new helper;
                $properties = $helper->get_all_properties($object);
            }

            $return = array_diff($properties, array('id', 'guid', 'metadata'));

            // Cache them
            $this->properties[$mgdschema_class] = $return;
        }

        // ...and return
        return $this->properties[$mgdschema_class];
    }

    /**
     * Get the parent property for overriding it
     *
     * @param mixed $object     MgdSchema object for resolving the parent property
     * @return string            Parent property
     */
    public function get_parent_property($object)
    {
        $properties = self::get_target_properties($object);
        return $properties['parent'];
    }

    /**
     * Get the target properties and return an array that is used e.g. in copying
     *
     * @param mixed $object     MgdSchema object or MidCOM db object
     * @return array            id, parent property, class and label of the object
     */
    public static function get_target_properties($object)
    {
        $mgdschema_class = midcom_helper_reflector::resolve_baseclass($object);
        $mgdschema_object = new $mgdschema_class($object->guid);

        static $targets = array();

        // Return the cached results
        if (isset($targets[$mgdschema_class])) {
            return $targets[$mgdschema_class];
        }

        // Empty result set for the current class
        $target = array
        (
            'id' => null,
            'parent' => '',
            'class' => $mgdschema_class,
            'label' => '',
            'reflector' => new midcom_helper_reflector($object),
        );

        // Try to get the parent property for determining, which property should be
        // used to point the parent of the new object. Attachments are a special case.
        if (!midcom::get()->dbfactory->is_a($object, 'midcom_db_attachment')) {
            $parent_property = midgard_object_class::get_property_parent($mgdschema_object);
        } else {
            $parent_property = 'parentobject';
        }

        // Get the class label
        $target['label'] = $target['reflector']->get_label_property();

        // Try once more to get the parent property, but now try up as a backup
        if (!$parent_property) {
            $up_property = midgard_object_class::get_property_up($mgdschema_object);

            if (!$up_property) {
                throw new midcom_error('Failed to get the parent property for copying');
            }

            $target['parent'] = $up_property;
        } else {
            $target['parent'] = $parent_property;
        }

        // Cache the results
        $targets[$mgdschema_class] = $target;
        return $targets[$mgdschema_class];
    }

    /**
     * Resolve MgdSchema object from guid or miscellaneous extended object
     *
     * @param mixed &$object    MgdSchema object, GUID or ID
     * @return mixed MgdSchema object or false on failure
     */
    public static function resolve_object(&$object)
    {
        // Check the type of the requested parent
        if (mgd_is_guid($object)) {
            $object = midcom::get()->dbfactory->get_object_by_guid($object);
        }
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
     * MgdSchema or MidCOM db object or GUID of the object and parent can be
     *
     * - MgdSchema object
     * - MidCOM db object
     * - predefined target array (@see get_target_properties())
     * - ID or GUID of the object
     * - left empty to copy as a parentless object
     *
     * This method is self-aware and will refuse to perform any infinite loops (e.g. to copy
     * itself to its descendant, copying itself again and again and again).
     *
     * Eventually this method will return the first root object that was created, i.e. the root
     * of the new tree.
     *
     * @param mixed $source        GUID or MgdSchema object that will be copied
     * @param mixed $parent        MgdSchema or MidCOM db object, predefined array or ID of the parent object
     * @return mixed               False on failure, newly created MgdSchema root object on success
     */
    public function copy_tree(&$source, &$parent)
    {
        // Copy the root object
        $root = $this->copy_object($source, $parent);

        if (empty($root->guid)) {
            $this->errors[] = sprintf($this->_l10n->get('failed to copy object %s'), $source->guid);
            return false;
        }

        // Add the newly copied object to the exclusion list to prevent infinite loops
        $this->exclude[] = $this->source->guid;

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
     * @param mixed &$source     MgdSchema object for reading the parameters
     * @param mixed &$parent      MgdSchema parent object
     * @param array $defaults
     * @return boolean Indicating success
     */
    public function copy_object(&$source, &$parent = null, array $defaults = array())
    {
        // Resolve the source object
        self::resolve_object($source);

        // Duplicate the object
        $class_name = get_class($source);
        $target = new $class_name();

        $properties = $this->get_object_properties($source);

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

        $parent_property = $this->get_parent_property($source);

        // Copy the link to parent
        if ($parent) {
            self::resolve_object($parent);

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

        // Store for later use - if ever needed
        $this->new_objects[] = $target;

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
     * @param mixed &$source      MgdSchema object for reading the parameters
     * @param mixed &$target      MgdSchema object for storing the parameters
     * @return boolean Indicating success
     */
    private function _copy_data($type, $source, &$target)
    {
        $method = 'copy_' . $type;
        if (   !$this->$method($source, $target)
            && $this->halt_on_errors) {
            $this->errors[] = $this->_l10n->get('failed to copy ' . $type);
            return false;
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
        if (!$this->copy_parameters) {
            return true;
        }

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
        $qb = midcom_db_privilege::new_query_builder();
        $qb->add_constraint('objectguid', '=', $source->guid);

        $results = $qb->execute();

        static $privilege_fields = null;

        if (is_null($privilege_fields)) {
            $privilege_fields = array
            (
                'classname',
                'assignee',
                'name',
                'value',
            );
        }

        foreach ($results as $privilege) {
            $new = new midcom_db_privilege();
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
     * Copy an object tree. Both source and parent may be liberally filled. Source can be either
     * MgdSchema or MidCOM db object or GUID of the object and parent can be
     *
     * - MgdSchema object
     * - MidCOM db object
     * - predefined target array (@see get_target_properties())
     * - ID or GUID of the object
     * - left empty to copy as a parentless object
     *
     * This method is self-aware and will refuse to perform any infinite loops (e.g. to copy
     * itself to its descendant, copying itself again and again and again).
     *
     * Eventually this method will return the first root object that was created, i.e. the root
     * of the new tree.
     *
     * @param mixed $source        GUID or MgdSchema object that will be copied
     * @param mixed $parent        MgdSchema or MidCOM db object, predefined array or ID of the parent object
     * @param array $exclude       IDs that will be excluded from the copying
     * @param boolean $copy_parameters  Switch to determine if the parameters should be copied
     * @param boolean $copy_metadata    Switch to determine if the metadata should be copied (excluding created and published)
     * @param boolean $copy_attachments Switch to determine if the attachments should be copied (creates only a new link, doesn't duplicate the content)
     * @return mixed               False on failure, newly created MgdSchema root object on success
     */
    public static function copy_object_tree($source, $parent, $exclude = array(), $copy_parameters = true, $copy_metadata = true, $copy_attachments = true)
    {
        $copy = new midcom_helper_reflector_copy();
        $copy->source =& $source;
        $copy->target =& $parent;
        $copy->copy_parameters = $copy_parameters;
        $copy->copy_metadata = $copy_metadata;
        $copy->copy_attachments = $copy_attachments;

        if (!$copy->execute()) {
            return false;
        }

        return $copy->get_object();
    }

    /**
     * Dispatches the copy command according to the attributes set
     *
     * @return boolean Indicating success
     */
    public function execute()
    {
        if (!$this->resolve_object($this->source)) {
            $this->errors[] = $this->_l10n->get('failed to get the source object');
            return false;
        }

        if ($this->copy_tree) {
            // Disable execution timeout and memory limit, this can be very intensive
            midcom::get()->disable_limits();

            $this->new_root_object = $this->copy_tree($this->source, $this->target);
        } else {
            $this->new_root_object = $this->copy_object($this->source, $this->target);
        }

        if (empty($this->new_root_object->guid)) {
            $this->errors[] = $this->_l10n->get('failed to get the new root object');
            return false;
        }

        return true;
    }
}
