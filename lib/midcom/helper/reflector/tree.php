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

        // Figure out constraint to use to get root level objects
        if ($upfield = midgard_object_class::get_property_up($schema_type)) {
            $uptype = $this->_mgd_reflector->get_midgard_type($upfield);
            switch ($uptype) {
                case MGD_TYPE_STRING:
                case MGD_TYPE_GUID:
                    $qb->add_constraint($upfield, '=', '');
                    break;
                case MGD_TYPE_INT:
                case MGD_TYPE_UINT:
                    $qb->add_constraint($upfield, '=', 0);
                    break;
                default:
                    debug_add("Do not know how to handle upfield '{$upfield}' has type {$uptype}", MIDCOM_LOG_ERROR);
                    return false;
            }
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
     * Get children of given object
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
     * Figure out constraint(s) to use to get child objects
     */
    private function _get_link_fields(string $schema_type, string $target_class) : array
    {
        static $cache = [];
        $cache_key = $schema_type . '-' . $target_class;
        if (empty($cache[$cache_key])) {
            $ref = new midgard_reflection_property($schema_type);

            $linkfields = array_filter([
                'up' => midgard_object_class::get_property_up($schema_type),
                'parent' => midgard_object_class::get_property_parent($schema_type)
            ]);
            $data = [];
            foreach ($linkfields as $link_type => $field) {
                $info = [
                    'name' => $field,
                    'type' => $ref->get_midgard_type($field),
                    'target' => $ref->get_link_target($field)
                ];

                if ($linked_class = $ref->get_link_name($field)) {
                    if (!self::is_same_class($linked_class, $target_class)) {
                        // This link points elsewhere
                        continue;
                    }
                } elseif ($info['type'] === MGD_TYPE_GUID && empty($info['target'])) {
                    // Guid link without class specification, valid for all classes
                    $info['target'] = 'guid';
                }
                $data[$link_type] = $info;
            }
            $cache[$cache_key] = $data;
        }
        return $cache[$cache_key];
    }

    /**
     * Creates a QB instance for _get_child_objects_type
     */
    public function _child_objects_type_qb(string $schema_type, object $for_object, bool $deleted)
    {
        $qb = $this->_get_type_qb($schema_type, $deleted);
        if (!$qb) {
            debug_add("Could not get QB for type '{$schema_type}'", MIDCOM_LOG_ERROR);
            return false;
        }

        $linkfields = $this->_get_link_fields($schema_type, get_class($for_object));

        if (empty($linkfields)) {
            debug_add("Class '{$schema_type}' has no valid link properties pointing to class '" . get_class($for_object) . "', this should not happen here", MIDCOM_LOG_ERROR);
            return false;
        }

        $multiple_links = count($linkfields) > 1;
        if ($multiple_links) {
            $qb->begin_group('OR');
        }

        foreach ($linkfields as $link_type => $field_data) {
            $field_target = $field_data['target'];
            $field_type = $field_data['type'];
            $field = $field_data['name'];

            if (   !$field_target
                || !isset($for_object->$field_target)) {
                // Why return false ???
                return false;
            }
            switch ($field_type) {
                case MGD_TYPE_STRING:
                case MGD_TYPE_GUID:
                    $qb->add_constraint($field, '=', (string) $for_object->$field_target);
                    break;
                case MGD_TYPE_INT:
                case MGD_TYPE_UINT:
                    if ($link_type == 'up') {
                        $qb->add_constraint($field, '=', (int) $for_object->$field_target);
                    } else {
                        if (!empty($linkfields['up']['name'])) {
                            //we only return direct children (otherwise they would turn up twice in recursive queries)
                            $qb->begin_group('AND');
                            $qb->add_constraint($field, '=', (int) $for_object->$field_target);
                            $qb->add_constraint($linkfields['up']['name'], '=', 0);
                            $qb->end_group();
                        } else {
                            $qb->add_constraint($field, '=', (int) $for_object->$field_target);
                        }
                    }
                    break;
                default:
                    debug_add("Do not know how to handle linked field '{$field}', has type {$field_type}", MIDCOM_LOG_INFO);

                    // Why return false ???
                    return false;
            }
        }

        if ($multiple_links) {
            $qb->end_group();
        }

        return $qb;
    }

    /**
     * Get the parent class of the class this reflector was instantiated for
     *
     * @return string class name (or false if the type has no parent)
     */
    public function get_parent_class() : ?string
    {
        $parent_property = midgard_object_class::get_property_parent($this->mgdschema_class);
        if (!$parent_property) {
            return null;
        }
        $ref = new midgard_reflection_property($this->mgdschema_class);
        return $ref->get_link_name($parent_property);
    }

    /**
     * Get the child classes of the class this reflector was instantiated for
     */
    public function get_child_classes() : array
    {
        static $cache = [];
        if (!isset($cache[$this->mgdschema_class])) {
            $cache[$this->mgdschema_class] = [];

            $neverchild = $this->_config->get('child_class_exceptions_neverchild');
            // Safety against misconfiguration
            if (!is_array($neverchild)) {
                debug_add("config->get('child_class_exceptions_neverchild') did not return array, invalid configuration ??", MIDCOM_LOG_ERROR);
                $neverchild = [];
            }
            $types = array_diff(midcom_connection::get_schema_types(), $neverchild);
            foreach ($types as $schema_type) {
                if ($this->_get_link_fields($schema_type, $this->mgdschema_class)) {
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
     * Resolves the "root level" classes, used by get_root_classes()
     */
    private static function _resolve_root_classes() : array
    {
        $root_exceptions_notroot = midcom_baseclasses_components_configuration::get('midcom.helper.reflector', 'config')->get('root_class_exceptions_notroot');
        // Safety against misconfiguration
        if (!is_array($root_exceptions_notroot)) {
            debug_add("config->get('root_class_exceptions_notroot') did not return array, invalid configuration ??", MIDCOM_LOG_ERROR);
            $root_exceptions_notroot = [];
        }
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
     * @param string $schema_type valid mgdschema class name
     */
    public static function add_schema_sorts_to_qb($qb, string $schema_type)
    {
        // Sort by "title" and "name" if available
        $ref = self::get($schema_type);
        $dummy = new $schema_type();
        if ($title_property = $ref->get_title_property($dummy)) {
            $qb->add_order($title_property);
        }
        if ($name_property = $ref->get_name_property($dummy)) {
            $qb->add_order($name_property);
        }
    }

    /**
     * List object children
     *
     * @param midcom_core_dbaobject $parent
     */
    public static function get_tree(midcom_core_dbaobject $parent) : array
    {
        static $shown_guids = [];
        $tree = [];
        try {
            $children = self::get_child_objects($parent);
        } catch (midcom_error $e) {
            return $tree;
        }

        foreach ($children as $class => $objects) {
            $reflector = parent::get($class);

            foreach ($objects as $object) {
                if (array_key_exists($object->guid, $shown_guids)) {
                    //we might see objects twice if they have both up and parent
                    continue;
                }
                $shown_guids[$object->guid] = true;

                $leaf = [
                    'title' => $reflector->get_object_label($object),
                    'icon' => $reflector->get_object_icon($object),
                    'class' => $class
                ];
                $grandchildren = self::get_tree($object);
                if (!empty($grandchildren)) {
                    $leaf['children'] = $grandchildren;
                }
                $tree[] = $leaf;
            }
        }
        return $tree;
    }
}
