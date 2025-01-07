<?php
/**
 * @package midcom.helper.reflector
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * The Grand Unified Reflector, copying helper class
 *
 * @package midcom.helper.reflector
 */
class midcom_helper_reflector_copy
{
    use midcom_baseclasses_components_base;

    public ?midcom_core_dbaobject $target = null;

    /**
     * List of GUIDs of objects that shall not be copied
     */
    public array $exclude = [];

    /**
     * Switch for attachments
     */
    public bool $attachments = true;

    /**
     * Switch for parameters
     */
    public bool $parameters = true;

    /**
     * Switch for privileges
     */
    public bool $privileges = true;

    /**
     * Switch for metadata
     */
    public bool $metadata = true;

    /**
     * Copy the whole tree
     */
    public bool $recursive = true;

    /**
     * Metadata fields that shall be copied
     */
    public array $copy_metadata_fields = [
        'owner',
        'authors',
        'schedulestart',
        'scheduleend',
        'navnoentry',
        'hidden',
        'score',
    ];

    /**
     * Encountered errors
     */
    public array $errors = [];

    /**
     * Get the parent property for overriding it
     */
    public static function get_parent_property(midcom_core_dbaobject $object) : ?string
    {
        return midgard_object_class::get_property_parent($object->__mgdschema_class_name__)
            ?? midgard_object_class::get_property_up($object->__mgdschema_class_name__);
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
     */
    public function copy_tree(midcom_core_dbaobject $source, ?midcom_core_dbaobject $parent) : ?midcom_core_dbaobject
    {
        // Copy the root object
        $root = $this->copy_object($source, $parent);

        if (!$root) {
            $this->errors[] = sprintf($this->_l10n->get('failed to copy object %s'), $source->guid);
            return null;
        }

        // Add the newly copied object to the exclusion list to prevent infinite loops
        $this->exclude[] = $source->guid;

        // Loop through the children and copy them to their corresponding parents
        foreach (midcom_helper_reflector_tree::get_child_objects($source) as $children) {
            // Get the children of each type
            foreach ($children as $child) {
                // Skip the excluded child
                if (!in_array($child->guid, $this->exclude)) {
                    $this->copy_tree($child, $root);
                }
            }
        }

        // Return the newly created root object
        return $root;
    }

    /**
     * Copy an object
     */
    public function copy_object(midcom_core_dbaobject $source, ?midcom_core_dbaobject $parent, array $defaults = []) : ?midcom_core_dbaobject
    {
        // Duplicate the object
        $class_name = $source::class;
        $target = new $class_name();

        // Copy the object properties
        foreach (midcom_helper_reflector::get_object_fieldnames($source) as $property) {
            // Skip certain fields
            if (!preg_match('/^(_|metadata|guid|id)/', $property)) {
                $target->$property = $source->$property;
            }
        }

        // Override with defaults
        foreach ($defaults as $name => $value) {
            $target->$name = $value;
        }

        if (   $this->recursive
            && $parent_property = self::get_parent_property($source)) {

            // Copy the link to parent
            if (!empty($parent->guid)) {
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
            return null;
        }

        foreach (['parameters', 'metadata', 'attachments', 'privileges'] as $type) {
            if (!$this->_copy_data($type, $source, $target)) {
                return null;
            }
        }

        return $target;
    }

    /**
     * Copy object data
     */
    private function _copy_data(string $type, midcom_core_dbaobject $source, midcom_core_dbaobject $target) : bool
    {
        if ($this->$type) {
            $method = 'copy_' . $type;
            if (!$this->$method($source, $target)) {
                $this->errors[] = $this->_l10n->get('failed to copy ' . $type);
                return false;
            }
        }

        return true;
    }

    /**
     * Copy parameters for the object
     */
    public function copy_parameters(midcom_core_dbaobject $source, midcom_core_dbaobject $target) : bool
    {
        // Loop through the parameters
        foreach ($source->list_parameters() as $domain => $parameters) {
            foreach ($parameters as $name => $value) {
                if (!$target->set_parameter($domain, $name, $value)) {
                    $this->errors[] = sprintf($this->_l10n->get('failed to copy parameters from %s to %s'), $source->guid, $target->guid);
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Copy metadata for the object
     */
    public function copy_metadata(midcom_core_dbaobject $source, midcom_core_dbaobject $target) : bool
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
     */
    public function copy_attachments(midcom_core_dbaobject $source, midcom_core_dbaobject $target) : bool
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
     */
    public function copy_privileges(midcom_core_dbaobject $source, midcom_core_dbaobject $target) : bool
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
     */
    public function execute(midcom_core_dbaobject $source) : ?midcom_core_dbaobject
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
            return null;
        }

        return $new_root_object;
    }
}
