<?php
/**
 * @package midcom.helper.reflector
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * The Grand Unified Reflector, Tree information
 *
 * @package midcom.helper.reflector
 */
class midcom_helper_reflector_tree extends midcom_helper_reflector
{
    /**
     * Creates a QB instance for root objects
     */
    public function _root_objects_qb()
    {
        $schema_type = $this->mgdschema_class;

        $qb = $this->_get_type_qb($schema_type, false);
        if (!$qb) {
            debug_add("Could not get QB for type '{$schema_type}'", MIDCOM_LOG_ERROR);
            return false;
        }

        // Only get top level objects
        if ($upfield = midgard_object_class::get_property_up($schema_type)) {
            $qb->add_constraint($upfield, '=', 0);
        }
        return $qb;
    }

    /**
     * Get rendered path for object
     *
     * @param midgard\portable\api\mgdobject $object The object to get path for
     * @param string $separator the string used to separate path components
     */
    public static function resolve_path($object, string $separator = ' &gt; ') : string
    {
        $parts = self::resolve_path_parts($object);
        return implode($separator, array_column($parts, 'label'));
    }

    /**
     * Get path components for object
     *
     * @param midgard\portable\api\mgdobject $object The object to get path for
     */
    public static function resolve_path_parts($object) : array
    {
        static $cache = [];
        if (isset($cache[$object->guid])) {
            return $cache[$object->guid];
        }

        $ret = [];
        $ret[] = [
            'object' => $object,
            'label' => parent::get($object)->get_object_label($object),
        ];

        $parent = $object->get_parent();
        while (is_object($parent)) {
            $ret[] = [
                'object' => $parent,
                'label' => parent::get($parent)->get_object_label($parent),
            ];
            $parent = $parent->get_parent();
        }

        $cache[$object->guid] = array_reverse($ret);
        return $cache[$object->guid];
    }

    private static function _check_permissions(bool $deleted) : bool
    {
        // PONDER: Check for some generic user privilege instead  ??
        if (   $deleted
            && !midcom_connection::is_admin()
            && !midcom::get()->auth->is_component_sudo()) {
            debug_add('Non-admins are not allowed to list deleted objects', MIDCOM_LOG_ERROR);
            return false;
        }
        return true;
    }

    /**
     * Get direct children of given object
     *
     * @param midgard\portable\api\mgdobject $object object to get children for
     * @param boolean $deleted whether to get (only) deleted or not-deleted objects
     * @return array multidimensional array (keyed by classname) of objects
     */
    public static function get_child_objects(object $object, bool $deleted = false) : array
    {
        if (!self::_check_permissions($deleted)) {
            return [];
        }
        $resolver = new self($object);

        $child_objects = [];
        foreach ($resolver->get_child_classes() as $schema_type) {
            $qb = $resolver->_child_objects_type_qb($schema_type, $object, $deleted);
            if (!$qb) {
                debug_add('Could not get QB instance', MIDCOM_LOG_ERROR);
                continue;
            }

            // Sort by title and name if available
            self::add_schema_sorts_to_qb($qb, $schema_type);

            if ($type_children = $qb->execute()) {
                $child_objects[$schema_type] = $type_children;
            }
        }
        return $child_objects;
    }

    private function _get_type_qb(string $schema_type, bool $deleted)
    {
        if (empty($schema_type)) {
            debug_add('Passed schema_type argument is empty, this is fatal', MIDCOM_LOG_ERROR);
            return false;
        }
        if ($deleted) {
            $qb = new midgard_query_builder($schema_type);
            $qb->include_deleted();
            $qb->add_constraint('metadata.deleted', '<>', 0);
            return $qb;
        }
        // Figure correct MidCOM DBA class to use and get midcom QB
        $midcom_dba_classname = midcom::get()->dbclassloader->get_midcom_class_name_for_mgdschema_object($schema_type);
        if (empty($midcom_dba_classname)) {
            debug_add("MidCOM DBA does not know how to handle {$schema_type}", MIDCOM_LOG_ERROR);
            return false;
        }

        return $midcom_dba_classname::new_query_builder();
    }

    /**
     * Figure out if $schema_type can be a child of $target_class
     */
    private function get_link_field(string $schema_type, string $target_class) : ?array
    {
        static $cache = [];
        $cache_key = $schema_type . '-' . $target_class;
        if (!array_key_exists($cache_key, $cache)) {
            $cache[$cache_key] = null;
            $ref = new midgard_reflection_property($schema_type);

            $candidates = array_filter([
                'up' => midgard_object_class::get_property_up($schema_type),
                'parent' => midgard_object_class::get_property_parent($schema_type)
            ]);

            foreach ($candidates as $type => $field) {
                $info = [
                    'type' => $type,
                    'name' => $field,
                    'target' => $ref->get_link_target($field),
                    'upfield' => $candidates['up'] ?? null
                ];

                if ($linked_class = $ref->get_link_name($field)) {
                    if (!self::is_same_class($linked_class, $target_class)) {
                        // This link points elsewhere
                        continue;
                    }
                } elseif ($ref->get_midgard_type($field) === MGD_TYPE_GUID && empty($info['target'])) {
                    // Guid link without class specification, valid for all classes
                    $info['target'] = 'guid';
                }
                $cache[$cache_key] = $info;
                break;
            }
        }
        return $cache[$cache_key];
    }

    /**
     * Creates a QB instance for get_child_objects
     */
    public function _child_objects_type_qb(string $schema_type, object $for_object, bool $deleted)
    {
        $qb = $this->_get_type_qb($schema_type, $deleted);
        if (!$qb) {
            debug_add("Could not get QB for type '{$schema_type}'", MIDCOM_LOG_ERROR);
            return false;
        }

        if ($info = $this->get_link_field($schema_type, get_class($for_object))) {
            $qb->add_constraint($info['name'], '=', $for_object->{$info['target']});
            // we only return direct children (otherwise they would turn up twice in recursive queries)
            if ($info['type'] == 'parent' && $info['upfield']) {
                $qb->add_constraint($info['upfield'], '=', 0);
            }
            return $qb;
        }

        debug_add("Class '{$schema_type}' has no valid link properties pointing to class '" . get_class($for_object) . "', this should not happen here", MIDCOM_LOG_ERROR);
        return false;
    }

    /**
     * Get the parent class of the class this reflector was instantiated for
     */
    public function get_parent_class() : ?string
    {
        $parent_property = midgard_object_class::get_property_parent($this->mgdschema_class);
        if (!$parent_property) {
            return null;
        }

        return $this->get_link_name($parent_property);
    }

    /**
     * Get the child classes of the class this reflector was instantiated for
     */
    public function get_child_classes() : array
    {
        static $cache = [];
        if (!isset($cache[$this->mgdschema_class])) {
            $cache[$this->mgdschema_class] = [];

            $types = array_diff(midcom_connection::get_schema_types(), $this->_config->get_array('child_class_exceptions_neverchild'));
            foreach ($types as $schema_type) {
                if ($this->get_link_field($schema_type, $this->mgdschema_class)) {
                    $cache[$this->mgdschema_class][] = $schema_type;
                }
            }

            //make sure children of the same type come out on top
            if ($key = array_search($this->mgdschema_class, $cache[$this->mgdschema_class])) {
                unset($cache[$this->mgdschema_class][$key]);
                array_unshift($cache[$this->mgdschema_class], $this->mgdschema_class);
            }
        }
        return $cache[$this->mgdschema_class];
    }

    /**
     * Get an array of "root level" classes
     */
    public static function get_root_classes() : array
    {
        static $root_classes = false;
        if (empty($root_classes)) {
            $root_classes = self::_resolve_root_classes();
        }
        return $root_classes;
    }

    /**
     * Resolves the "root level" DBA classes, used by get_root_classes()
     */
    private static function _resolve_root_classes() : array
    {
        $root_exceptions_notroot = midcom_baseclasses_components_configuration::get('midcom.helper.reflector', 'config')->get_array('root_class_exceptions_notroot');
        $root_classes = [];
        $types = array_diff(midcom_connection::get_schema_types(), $root_exceptions_notroot);
        foreach ($types as $schema_type) {
            // Class extensions mapping
            $schema_type = self::class_rewrite($schema_type);

            // Make sure we only add classes once
            if (in_array($schema_type, $root_classes)) {
                // Already listed
                continue;
            }

            if (midgard_object_class::get_property_parent($schema_type)) {
                // type has parent set, thus cannot be root type
                continue;
            }

            if (!midcom::get()->dbclassloader->get_midcom_class_name_for_mgdschema_object($schema_type)) {
                // Not a MidCOM DBA object, skip
                continue;
            }

            $root_classes[] = $schema_type;
        }

        usort($root_classes, 'strnatcmp');
        return $root_classes;
    }

    /**
     * Add default ("title" and "name") sorts to a QB instance
     *
     * @param midgard_query_builder $qb QB instance
     */
    public static function add_schema_sorts_to_qb($qb, string $schema_type)
    {
        // Sort by "title" and "name" if available
        $dummy = new $schema_type();
        if ($title_property = self::get_title_property($dummy)) {
            $qb->add_order($title_property);
        }
        if ($name_property = self::get_name_property($dummy)) {
            $qb->add_order($name_property);
        }
    }

    /**
     * List object children
     */
    public static function get_tree(midcom_core_dbaobject $parent) : array
    {
        $tree = [];

        foreach (self::get_child_objects($parent) as $class => $objects) {
            $reflector = parent::get($class);

            foreach ($objects as $object) {
                $leaf = [
                    'title' => $reflector->get_object_label($object),
                    'icon' => $reflector->get_object_icon($object),
                    'class' => $class
                ];
                if ($grandchildren = self::get_tree($object)) {
                    $leaf['children'] = $grandchildren;
                }
                $tree[] = $leaf;
            }
        }
        return $tree;
    }
}
