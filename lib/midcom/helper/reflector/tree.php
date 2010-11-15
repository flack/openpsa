<?php
/**
 * @package midcom.helper.reflector
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: tree.php 25203 2010-02-25 12:53:06Z bergie $
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * The Grand Unified Reflector, Tree information
 * @package midcom.helper.reflector
 */
class midcom_helper_reflector_tree extends midcom_helper_reflector
{
    function __construct($src)
    {
        parent::__construct($src);
    }

    function &get($src)
    {
        if (is_object($src))
        {
            $classname = get_class($src);
        }
        else
        {
            $classname = $src;
        }
        if (!isset($GLOBALS['midcom_helper_reflector_tree_singletons'][$classname]))
        {
            $GLOBALS['midcom_helper_reflector_tree_singletons'][$classname] =  new midcom_helper_reflector_tree($src);
        }
        return $GLOBALS['midcom_helper_reflector_tree_singletons'][$classname];
    }

    /**
     * Creates a QB instance for get_root_objects and count_root_objects
     *
     * @access private
     */
    function &_root_objects_qb(&$deleted)
    {
        $schema_type =& $this->mgdschema_class;
        $root_classes = midcom_helper_reflector_tree::get_root_classes();
        if (!in_array($schema_type, $root_classes))
        {
            debug_push_class(__CLASS__, __FUNCTION__);
            debug_add("Type {$schema_type} is not a \"root\" type", MIDCOM_LOG_ERROR);
            debug_pop();
            $x = false;
            return $x;
        }

        if ($deleted)
        {
            $qb = new midgard_query_builder($schema_type);
        }
        else
        {
            // Figure correct MidCOM DBA class to use and get midcom QB
            $qb = false;
            $midcom_dba_classname = $_MIDCOM->dbclassloader->get_midcom_class_name_for_mgdschema_object($this->_dummy_object);
            if (empty($midcom_dba_classname))
            {
                debug_push_class(__CLASS__, __FUNCTION__);
                debug_add("MidCOM DBA does not know how to handle {$schema_type}", MIDCOM_LOG_ERROR);
                debug_pop();
                $x = false;
                return $x;
            }
            if (!$_MIDCOM->dbclassloader->load_mgdschema_class_handler($midcom_dba_classname))
            {
                debug_push_class(__CLASS__, __FUNCTION__);
                debug_add("Failed to load the handling component for {$midcom_dba_classname}, cannot continue.", MIDCOM_LOG_ERROR);
                debug_pop();
                $x = false;
                return $x;
            }
            $qb_callback = array($midcom_dba_classname, 'new_query_builder');
            if (!is_callable($qb_callback))
            {
                debug_push_class(__CLASS__, __FUNCTION__);
                debug_add("Static method {$midcom_dba_classname}::new_query_builder() is not callable", MIDCOM_LOG_ERROR);
                debug_pop();
                $x = false;
                return $x;
            }
            $qb = call_user_func($qb_callback);
        }

        // Sanity-check
        if (!$qb)
        {
            debug_push_class(__CLASS__, __FUNCTION__);
            debug_add("Could not get QB for type '{$schema_type}'", MIDCOM_LOG_ERROR);
            debug_pop();
            $x = false;
            return $x;
        }
        // Deleted constraints
        if ($deleted)
        {
            $qb->include_deleted();
            $qb->add_constraint('metadata.deleted', '<>', 0);
        }

        // Figure out constraint to use to get root level objects
        $upfield = midgard_object_class::get_property_up($schema_type);
        if (!empty($upfield))
        {
            $ref =& $this->_mgd_reflector;
            $uptype = $ref->get_midgard_type($upfield);
            switch ($uptype)
            {
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
                    debug_pop();
                    return false;
            }
        }
        return $qb;
    }

    /**
     * Get count of "root" objects for the class this reflector was instantiated for
     *
     * @param boolean $deleted whether to count (only) deleted or not-deleted objects
     * @return array of objects or false on failure
     * @see get_root_objects
     */
    function count_root_objects($deleted = false)
    {
        // Check against static calling
        if (   !isset($this->mgdschema_class)
            || empty($this->mgdschema_class))
        {
            debug_push_class(__CLASS__, __FUNCTION__);
            debug_add('May not be called statically', MIDCOM_LOG_ERROR);
            debug_pop();
            return false;
        }
        // PONDER: Check for some generic user privilege instead  ??
        if (   $deleted
            && !$_MIDGARD['admin'])
        {
            debug_push_class(__CLASS__, __FUNCTION__);
            debug_add('Non-admins are not allowed to list deleted objects', MIDCOM_LOG_ERROR);
            debug_pop();
            return false;
        }

        $qb = $this->_root_objects_qb($deleted);
        if (!$qb)
        {
            debug_push_class(__CLASS__, __FUNCTION__);
            debug_add('Could not get QB instance', MIDCOM_LOG_ERROR);
            debug_pop();
            return false;
        }

        $count = $qb->count();
        return $count;
    }

    function has_root_objects()
    {
        // Check against static calling
        if (   !isset($this->mgdschema_class)
            || empty($this->mgdschema_class))
        {
            debug_push_class(__CLASS__, __FUNCTION__);
            debug_add('May not be called statically', MIDCOM_LOG_ERROR);
            debug_pop();
            return false;
        }
        // PONDER: Check for some generic user privilege instead  ??
        if (   $deleted
            && !$_MIDGARD['admin'])
        {
            debug_push_class(__CLASS__, __FUNCTION__);
            debug_add('Non-admins are not allowed to list deleted objects', MIDCOM_LOG_ERROR);
            debug_pop();
            return false;
        }

        $qb = $this->_root_objects_qb($deleted);
        if (!$qb)
        {
            debug_push_class(__CLASS__, __FUNCTION__);
            debug_add('Could not get QB instance', MIDCOM_LOG_ERROR);
            debug_pop();
            return false;
        }
        $qb->set_limit(1);
        if ($qb->count())
        {
            unset($qb);
            return true;
        }
        unset($qb);
        return false;
    }

    /**
     * Get "root" objects for the class this reflector was instantiated for
     *
     * NOTE: deleted objects can only be listed as admin, also: they do not come
     * MidCOM DBA wrapped (since you cannot normally instantiate such object)
     *
     * @param boolean $deleted whether to get (only) deleted or not-deleted objects
     * @return array of objects or false on failure
     */
    function get_root_objects($deleted = false, $all = false)
    {
        // Check against static calling
        if (   !isset($this->mgdschema_class)
            || empty($this->mgdschema_class))
        {
            debug_push_class(__CLASS__, __FUNCTION__);
            debug_add('May not be called statically', MIDCOM_LOG_ERROR);
            debug_pop();
            return false;
        }
        // PONDER: Check for some generic user privilege instead  ??
        if (   $deleted
            && !$_MIDGARD['admin'])
        {
            debug_push_class(__CLASS__, __FUNCTION__);
            debug_add('Non-admins are not allowed to list deleted objects', MIDCOM_LOG_ERROR);
            debug_pop();
            return false;
        }

        $qb = $this->_root_objects_qb($deleted);
        if (!$qb)
        {
            debug_push_class(__CLASS__, __FUNCTION__);
            debug_add('Could not get QB instance', MIDCOM_LOG_ERROR);
            debug_pop();
            return false;
        }
        midcom_helper_reflector_tree::add_schema_sorts_to_qb($qb, $this->mgdschema_class);

        $ref = $this->get($this->mgdschema_class);

        $label_property = $ref->get_label_property();

        if (   is_string($label_property)
            && $_MIDCOM->dbfactory->property_exists($this->mgdschema_class, $label_property))
        {
            $qb->add_order($label_property);
        }
        else
        {
            $title_property = $ref->get_title_property(new $this->mgdschema_class());
            if (   is_string($title_property)
                && $_MIDCOM->dbfactory->property_exists($this->mgdschema_class, $title_property))
            {
                $qb->add_order($title_property);
            }
        }
        $objects = $qb->execute();

        return $objects;
    }

    /**
     * Statically callable method to determine if given object has children
     *
     * @param midgard_object &$object object to get children for
     * @param boolean $deleted whether to count (only) deleted or not-deleted objects
     * @return array multidimensional array (keyed by classname) of objects or false on failure
     */
    function has_child_objects(&$object, $deleted = false)
    {
        // PONDER: Check for some generic user privilege instead  ??
        if (   $deleted
            && !$_MIDGARD['admin'])
        {
            debug_push_class(__CLASS__, __FUNCTION__);
            debug_add('Non-admins are not allowed to list deleted objects', MIDCOM_LOG_ERROR);
            debug_pop();
            return false;
        }
        $resolver = new midcom_helper_reflector_tree($object);
        if (!$resolver)
        {
            debug_push_class(__CLASS__, __FUNCTION__);
            debug_add("Could not instantiate midcom_helper_reflector_tree from \$object", MIDCOM_LOG_ERROR);
            debug_pop();
            return false;
        }
        $child_classes = $resolver->get_child_classes();
        if (!$child_classes)
        {
            debug_push_class(__CLASS__, __FUNCTION__);
            debug_add('resolver returned false (critical failure) from get_child_classes()', MIDCOM_LOG_ERROR);
            debug_pop();
            return false;
        }
        foreach ($child_classes as $schema_type)
        {
            $qb = $resolver->_child_objects_type_qb($schema_type, $object, $deleted);
            if (!$qb)
            {
                debug_push_class(__CLASS__, __FUNCTION__);
                debug_add('resolver returned false (critical failure) from _child_objects_type_qb()', MIDCOM_LOG_ERROR);
                debug_pop();
                return false;
            }
            $qb->set_limit(1);
            if ($qb->count())
            {
                unset($child_classes, $schema_type, $qb, $resolver);
                return true;
            }
        }
        unset($child_classes, $schema_type, $qb, $resolver);
        return false;
    }

    /**
     * Statically callable method to count children of given object
     *
     * @param midgard_object &$object object to get children for
     * @param boolean $deleted whether to count (only) deleted or not-deleted objects
     * @return array multidimensional array (keyed by classname) of objects or false on failure
     */
    function count_child_objects(&$object, $deleted = false)
    {
        // PONDER: Check for some generic user privilege instead  ??
        if (   $deleted
            && !$_MIDGARD['admin'])
        {
            debug_push_class(__CLASS__, __FUNCTION__);
            debug_add('Non-admins are not allowed to list deleted objects', MIDCOM_LOG_ERROR);
            debug_pop();
            return false;
        }
        $resolver = new midcom_helper_reflector_tree($object);
        if (!$resolver)
        {
            debug_push_class(__CLASS__, __FUNCTION__);
            debug_add("Could not instantiate midcom_helper_reflector_tree from \$object", MIDCOM_LOG_ERROR);
            debug_pop();
            return false;
        }
        $child_classes = $resolver->get_child_classes();
        if (!$child_classes)
        {
            debug_push_class(__CLASS__, __FUNCTION__);
            debug_add('resolver returned false (critical failure) from get_child_classes()', MIDCOM_LOG_ERROR);
            debug_pop();
            return false;
        }

        $child_counts = array();
        foreach ($child_classes as $schema_type)
        {
            $child_counts[$schema_type] = $resolver->_count_child_objects_type($schema_type, $object, $deleted);
        }
        return $child_counts;
    }

    /**
     * Statically callable method to get rendered path for object
     *
     * @param midgard_object $object, the object to get path for
     * @param string $separator the string used to separate path components
     * @param GUID $stop_at in case we wish to stop resolving at certain object give guid here
     * @return string resolved path
     */
    function resolve_path(&$object, $separator = ' &gt; ', $stop_at = null)
    {
        static $cache = array();
        $cache_key = $object->guid . $separator . $stop_at;
        if (isset($cache[$cache_key]))
        {
            return $cache[$cache_key];
        }
        $parts = midcom_helper_reflector_tree::resolve_path_parts($object, $stop_at);
        $d = count($parts);
        $ret = '';
        foreach ($parts as $part)
        {
            $ret .= $part['label'];
            --$d;
            if ($d)
            {
                $ret .= $separator;
            }
        }
        unset($part, $d, $parts);
        $cache[$cache_key] = $ret;
        unset($ret);
        return $cache[$cache_key];
    }

    /**
     * Statically callable method to get path components for object
     *
     * @param midgard_object $object, the object to get path for
     * @param GUID $stop_at in case we wish to stop resolving at certain object give guid here
     * @return array path components
     */
    function resolve_path_parts(&$object, $stop_at = null)
    {
        static $cache = array();
        $cache_key = $object->guid . $stop_at;
        if (isset($cache[$cache_key]))
        {
            return $cache[$cache_key];
        }

        $ret = array();
        $object_reflector =& midcom_helper_reflector::get($object);
        $part = array
        (
            'object' => $object,
            'label' => $object_reflector->get_object_label($object),
        );
        $ret[] = $part;
        unset($part, $object_reflector);

        $parent = midcom_helper_reflector_tree::get_parent($object);
        while (is_object($parent))
        {
            $parent_reflector =& midcom_helper_reflector::get($parent);
            $part = array
            (
                'object' => $parent,
                'label' => $parent_reflector->get_object_label($parent),
            );
            $ret[] = $part;
            unset($part, $parent_reflector);
            $parent = midcom_helper_reflector_tree::get_parent($parent);
        }
        unset($parent);

        $ret = array_reverse($ret);
        $cache[$cache_key] = $ret;
        unset($ret);
        return $cache[$cache_key];
    }

    /**
     * statically callable method to get the parent object of given object
     *
     * Tries to utilize MidCOM DBA features first but can fallback on pure MgdSchema
     * as necessary
     *
     * NOTE: since this might fall back to pure MgdSchema never trust that MidCOM DBA features
     * are available, check for is_callable/method_exists first !
     *
     * @param midgard_object &$object the object to get parent for
     */
    function get_parent(&$object)
    {
        $parent_object = false;
        $dba_parent_callback = array($object, 'get_parent');
        if (is_callable($dba_parent_callback))
        {
            $parent_object = $object->get_parent();
            /**
             * The object might have valid reasons for returning empty value here, but we can't know if it's
             * because it's valid or because the get_parent* methods have not been overridden in the actually
             * used class
             */
        }

        return $parent_object;
    }

    function _get_parent_objectresolver(&$object, &$property)
    {
        $ref =& $this->_mgd_reflector;
        $target_class = $ref->get_link_name($property);
        $dummy_object = new $target_class();
        $midcom_dba_classname = $_MIDCOM->dbclassloader->get_midcom_class_name_for_mgdschema_object($dummy_object);
        if (!empty($midcom_dba_classname))
        {
            if (!$_MIDCOM->dbclassloader->load_mgdschema_class_handler($midcom_dba_classname))
            {
                debug_push_class(__CLASS__, __FUNCTION__);
                debug_add("Failed to load the handling component for {$midcom_dba_classname}, cannot continue.", MIDCOM_LOG_ERROR);
                debug_pop();
                return false;
            }
            // DBA classes can supposedly handle their own typecasts correctly
            $parent_object = new $midcom_dba_classname($object->$property);
            return $parent_object;
        }
        debug_add("MidCOM DBA does not know how to handle {$schema_type}, falling back to pure MgdSchema", MIDCOM_LOG_WARN);

        $linktype = $ref->get_midgard_type($property);
        switch ($linktype)
        {
            case MGD_TYPE_STRING:
            case MGD_TYPE_GUID:
                $parent_object = new $target_class((string)$object->$property);
                break;
            case MGD_TYPE_INT:
            case MGD_TYPE_UINT:
                $parent_object = new $target_class((int)$object->$property);
                break;
            default:
                debug_push_class(__CLASS__, __FUNCTION__);
                debug_add("Do not know how to handle linktype {$linktype}", MIDCOM_LOG_ERROR);
                debug_pop();
                return false;
        }

        return $parent_object;
    }


    /**
     * Statically callable method to get children of given object
     *
     * @param midgard_object &$object object to get children for
     * @param boolean $deleted whether to get (only) deleted or not-deleted objects
     * @return array multidimensional array (keyed by classname) of objects or false on failure
     */
    function get_child_objects(&$object, $deleted = false)
    {
        // PONDER: Check for some generic user privilege instead  ??
        if (   $deleted
            && !$_MIDGARD['admin'])
        {
            debug_push_class(__CLASS__, __FUNCTION__);
            debug_add('Non-admins are not allowed to list deleted objects', MIDCOM_LOG_ERROR);
            debug_pop();
            return false;
        }
        $resolver = new midcom_helper_reflector_tree($object);
        if (!$resolver)
        {
            debug_push_class(__CLASS__, __FUNCTION__);
            debug_add("Could not instantiate midcom_helper_reflector_tree from \$object", MIDCOM_LOG_ERROR);
            debug_pop();
            return false;
        }
        $child_classes = $resolver->get_child_classes();
        if (!$child_classes)
        {
            if ($child_classes === false)
            {
                debug_push_class(__CLASS__, __FUNCTION__);
                debug_add('resolver returned false (critical failure) from get_child_classes()', MIDCOM_LOG_ERROR);
                debug_pop();
            }
            return false;
        }

        //make sure children of the same type come out on top
        $i = 0;
        foreach ($child_classes as $child_class)
        {
            if ($_MIDCOM->dbfactory->is_a($object, $child_class))
            {
                unset($child_classes[$i]);
                array_unshift($child_classes, $child_class);
                break;
            }
            $i++;
        }

        $child_objects = array();
        foreach ($child_classes as $schema_type)
        {
            $type_children = $resolver->_get_child_objects_type($schema_type, $object, $deleted);
            // PONDER: check for boolean false as result ??
            if (empty($type_children))
            {
                unset($type_children);
                continue;
            }
            $child_objects[$schema_type] = $type_children;
            unset($type_children);
        }
        return $child_objects;
    }

    /**
     * Creates a QB instance for _get_child_objects_type and _count_child_objects_type
     *
     * @access private
     */
    function &_child_objects_type_qb(&$schema_type, &$for_object, $deleted)
    {
        if (empty($schema_type))
        {
            debug_push_class(__CLASS__, __FUNCTION__);
            debug_add('Passed schema_type argument is empty, this is fatal', MIDCOM_LOG_ERROR);
            debug_pop();
            $x = false;
            return $x;
        }
        if (!is_object($for_object))
        {
            debug_push_class(__CLASS__, __FUNCTION__);
            debug_add('Passed for_object argument is not object, this is fatal', MIDCOM_LOG_ERROR);
            debug_pop();
            $x = false;
            return $x;
        }
        if ($deleted)
        {
            $qb = new midgard_query_builder($schema_type);
        }
        else
        {
            $qb = false;

            // Figure correct MidCOM DBA class to use and get midcom QB
            $midcom_dba_classname = $_MIDCOM->dbclassloader->get_midcom_class_name_for_mgdschema_object($schema_type);
            if (empty($midcom_dba_classname))
            {
                debug_push_class(__CLASS__, __FUNCTION__);
                debug_add("MidCOM DBA does not know how to handle {$schema_type}", MIDCOM_LOG_ERROR);
                debug_pop();
                return $qb;
            }

            if (!$_MIDCOM->dbclassloader->load_component_for_class($midcom_dba_classname))
            {
                debug_push_class(__CLASS__, __FUNCTION__);
                debug_add("Failed to load the handling component for {$midcom_dba_classname}, cannot continue.", MIDCOM_LOG_ERROR);
                debug_pop();
                return $qb;
            }

            $qb_callback = array($midcom_dba_classname, 'new_query_builder');
            if (!is_callable($qb_callback))
            {
                debug_push_class(__CLASS__, __FUNCTION__);
                debug_add("Static method {$midcom_dba_classname}::new_query_builder() is not callable", MIDCOM_LOG_ERROR);
                debug_pop();
                return $qb;
            }
            $qb = call_user_func($qb_callback);
        }

        // Sanity-check
        if (!$qb)
        {
            debug_push_class(__CLASS__, __FUNCTION__);
            debug_add("Could not get QB for type '{$schema_type}'", MIDCOM_LOG_ERROR);
            debug_pop();
            $x = false;
            return $x;
        }
        // Deleted constraints
        if ($deleted)
        {
            $qb->include_deleted();
            $qb->add_constraint('metadata.deleted', '<>', 0);
        }

        // Figure out constraint(s) to use to get child objects
        $ref = new midgard_reflection_property($schema_type);

        $multiple_links = false;
        $linkfields = array();
        $linkfields['up'] = midgard_object_class::get_property_up($schema_type);
        $linkfields['parent'] = midgard_object_class::get_property_parent($schema_type);

        $object_baseclass = midcom_helper_reflector::resolve_baseclass(get_class($for_object));

        foreach ($linkfields as $link_type => $field)
        {
            if (empty($field))
            {
                // No such field for the object
                unset($linkfields[$link_type]);
                continue;
            }


            $linked_class = $ref->get_link_name($field);
            if (   empty($linked_class)
                && $ref->get_midgard_type($field) === MGD_TYPE_GUID)
            {
                // Guid link without class specification, valid for all classes
                continue;
            }
            if ($linked_class != $object_baseclass)
            {
                // This link points elsewhere
                unset($linkfields[$link_type]);
                continue;
            }
        }

        if (count($linkfields) === 0)
        {
            debug_push_class(__CLASS__, __FUNCTION__);
            debug_add("Class '{$schema_type}' has no valid link properties pointing to class '" . get_class($for_object) . "', this should not happen here", MIDCOM_LOG_ERROR);
            debug_pop();
            $x = false;
            return $x;
        }

        if (count($linkfields) > 1)
        {
            $multiple_links = true;
            $qb->begin_group('OR');
        }

        foreach ($linkfields as $link_type => $field)
        {
            $field_type = $ref->get_midgard_type($field);
            $field_target = $ref->get_link_target($field);
            if (   empty($field_target)
                && $field_type === MGD_TYPE_GUID)
            {
                $field_target = 'guid';
            }

            if (   !$field_target
                || !isset($for_object->$field_target))
            {
                if ($multiple_links)
                {
                    $qb->end_group();
                }
                // Why return false ???
                $x = false;
                return $x;
            }
            switch ($field_type)
            {
                case MGD_TYPE_STRING:
                case MGD_TYPE_GUID:
                    $qb->add_constraint($field, '=', (string) $for_object->$field_target);
                    break;
                case MGD_TYPE_INT:
                case MGD_TYPE_UINT:
                    if ($link_type == 'up')
                    {
                        $qb->add_constraint($field, '=', (int) $for_object->$field_target);
                    }
                    else
                    {
                        $qb->begin_group('AND');
                            $qb->add_constraint($field, '=', (int) $for_object->$field_target);
                            // make sure we don't accidentally find other objects with the same id
                            $qb->add_constraint($field . '.guid', '=', (string) $for_object->guid);
                        $qb->end_group();
                    }
                    break;
                default:
                    debug_push_class(__CLASS__, __FUNCTION__);
                    debug_add("Do not know how to handle linked field '{$field}', has type {$field_type}", MIDCOM_LOG_INFO);
                    debug_pop();
                    if ($multiple_links)
                    {
                        $qb->end_group();
                    }
                    // Why return false ???
                    $x = false;
                    return $x;
            }
        }

        if ($multiple_links)
        {
            $qb->end_group();
        }

        return $qb;
    }

    /**
     * Used by get_child_objects
     *
     * @access private
     * @return array of objects
     */
    function _get_child_objects_type(&$schema_type, &$for_object, $deleted)
    {
        $qb = $this->_child_objects_type_qb($schema_type, $for_object, $deleted);
        if (!$qb)
        {
            debug_push_class(__CLASS__, __FUNCTION__);
            debug_add('Could not get QB instance', MIDCOM_LOG_ERROR);
            debug_pop();
            return false;
        }

        // Sort by title and name if available
        midcom_helper_reflector_tree::add_schema_sorts_to_qb($qb, $schema_type);

        $objects = $qb->execute();

        return $objects;
    }

    /**
     * Used by count_child_objects
     *
     * @access private
     * @return array of objects
     */
    function _count_child_objects_type(&$schema_type, &$for_object, $deleted)
    {
        $qb = $this->_child_objects_type_qb($schema_type, $for_object, $deleted);
        if (!$qb)
        {
            debug_push_class(__CLASS__, __FUNCTION__);
            debug_add('Could not get QB instance', MIDCOM_LOG_ERROR);
            debug_pop();
            return false;
        }
        $count = $qb->count();
        return $count;
    }

    /**
     * Get the parent class of the class this reflector was instantiated for
     *
     * @return string class name (or false if the type has no parent)
     */
    function get_parent_class()
    {
        $parent_property = midgard_object_class::get_property_parent($this->mgdschema_class);
        if (!$parent_property)
        {
            return false;
        }
        $ref = new midgard_reflection_property($this->mgdschema_class);
        return $ref->get_link_name($parent_property);
    }

    /**
     * Get the child classes of the class this reflector was instantiated for
     *
     * @return array of class names (or false on critical failure)
     */
    function get_child_classes()
    {
        // Check against static calling
        if (   !isset($this->mgdschema_class)
            || empty($this->mgdschema_class))
        {
            debug_push_class(__CLASS__, __FUNCTION__);
            debug_add('May not be called statically', MIDCOM_LOG_ERROR);
            debug_pop();
            return false;
        }
        static $child_classes_all = array();
        if (!isset($child_classes_all[$this->mgdschema_class]))
        {
            $child_classes_all[$this->mgdschema_class] = false;
        }
        $child_classes =& $child_classes_all[$this->mgdschema_class];
        if ($child_classes === false)
        {
            $child_classes = $this->_resolve_child_classes();
        }

        return $child_classes;
    }

    /**
     * Resolve the child classes of the class this reflector was instantiated for, used by get_child_classes()
     *
     * @return array of class names (or false on critical failure)
     */
    function _resolve_child_classes()
    {
        // Check against static calling
        if (   !isset($this->mgdschema_class)
            || empty($this->mgdschema_class))
        {
            debug_push_class(__CLASS__, __FUNCTION__);
            debug_add('May not be called statically', MIDCOM_LOG_ERROR);
            debug_pop();
            return false;
        }
        if (!$this->_sanity_check_config())
        {
            return false;
        }
        $child_class_exceptions_neverchild = $this->_config->get('child_class_exceptions_neverchild');
        // Safety against misconfiguration
        if (!is_array($child_class_exceptions_neverchild))
        {
            debug_add("config->get('child_class_exceptions_neverchild') did not return array, invalid configuration ??", MIDCOM_LOG_ERROR);
            $child_class_exceptions_neverchild = array();
        }
        debug_pop();
        $child_classes = array();
        foreach ($_MIDGARD['schema']['types'] as $schema_type => $dummy)
        {
            if (in_array($schema_type, $child_class_exceptions_neverchild))
            {
                // Special cases, don't treat as children for normal objects
                continue;
            }
            $parent_property = midgard_object_class::get_property_parent($schema_type);
            $up_property = midgard_object_class::get_property_up($schema_type);

            if (   !$this->_resolve_child_classes_links_back($parent_property, $schema_type, $this->mgdschema_class)
                && !$this->_resolve_child_classes_links_back($up_property, $schema_type, $this->mgdschema_class))
            {
                continue;
            }
            $child_classes[] = $schema_type;
        }

        // TODO: handle exceptions

        return $child_classes;
    }

    function _resolve_child_classes_links_back($property, $prospect_type, $schema_type)
    {
        if (empty($property))
        {
            return false;
        }

        $ref = new midgard_reflection_property($prospect_type);
        $link_class = $ref->get_link_name($property);
        if (   empty($link_class)
            && $ref->get_midgard_type($property) === MGD_TYPE_GUID)
        {
            return true;
        }
        if (midcom_helper_reflector::is_same_class($link_class, $schema_type))
        {
            return true;
        }
        return false;
    }

    /**
     * Get an array of "root level" classes, can (and should) be called statically
     *
     * @return array of classnames (or false on critical failure)
     */
    function get_root_classes()
    {
        static $root_classes = false;
        if (empty($root_classes))
        {
            $root_classes = midcom_helper_reflector_tree::_resolve_root_classes();
        }
        return $root_classes;
    }

    /**
     * Resolves the "root level" classes, used by get_root_classes()
     *
     * @access private
     * @return array of classnames (or false on critical failure)
     */
    function _resolve_root_classes()
    {
        $root_exceptions_notroot = $GLOBALS['midcom_component_data']['midcom.helper.reflector']['config']->get('root_class_exceptions_notroot');
        // Safety against misconfiguration
        if (!is_array($root_exceptions_notroot))
        {
            debug_push_class(__CLASS__, __FUNCTION__);
            debug_add("config->get('root_class_exceptions_notroot') did not return array, invalid configuration ??", MIDCOM_LOG_ERROR);
            debug_pop();
            $root_exceptions_notroot = array();
        }
        $root_classes = array();
        foreach ($_MIDGARD['schema']['types'] as $schema_type => $dummy)
        {
            if (substr($schema_type, 0, 2) == '__')
            {
                continue;
            }

            if (in_array($schema_type, $root_exceptions_notroot))
            {
                // Explicitly specified to not be root class, skip all heuristics
                continue;
            }

            // Class extensions mapping
            $schema_type = midcom_helper_reflector::class_rewrite($schema_type);

            // Make sure we only add classes once
            if (in_array($schema_type, $root_classes))
            {
                // Already listed
                continue;
            }

            $parent = midgard_object_class::get_property_parent($schema_type);
            if (!empty($parent))
            {
                // type has parent set, thus cannot be root type
                continue;
            }

            $dba_class = $_MIDCOM->dbclassloader->get_midcom_class_name_for_mgdschema_object($schema_type);
            if (!$dba_class)
            {
                // Not a MidCOM DBA object, skip
                continue;
            }

            // DBA types can provide 'noasgard' property for navigation hiding
            /*
             * Example:
             *
             * class pl_olga_test_dba extends midcom_core_dbaobject
             * {
             *
             *     var $noasgard = true;
             *
             *     function __construct($id = null)    {
             *         return parent::__construct($id);
             *     }
             *
             * }
             *
             */

            // FIXME: This is not only used by asgard, thus Asgard navi specific hacks must be done on asgard level
            if (class_exists($dba_class))
            {
                $test_class = new $dba_class();

                if (   isset($test_class->noasgard)
                    && $test_class->noasgard)
                {
                    continue;
                }
            }

            $root_classes[] = $schema_type;
        }
        unset($root_exceptions_notroot);
        $root_exceptions_forceroot = $GLOBALS['midcom_component_data']['midcom.helper.reflector']['config']->get('root_class_exceptions_forceroot');
        // Safety against misconfiguration
        if (!is_array($root_exceptions_forceroot))
        {
            debug_push_class(__CLASS__, __FUNCTION__);
            debug_add("config->get('root_class_exceptions_forceroot') did not return array, invalid configuration ??", MIDCOM_LOG_ERROR);
            debug_pop();
            $root_exceptions_forceroot = array();
        }
        if (!empty($root_exceptions_forceroot))
        {
            foreach($root_exceptions_forceroot as $schema_type)
            {
                if (!class_exists($schema_type))
                {
                    // Not a valid class
                    debug_push_class(__CLASS__, __FUNCTION__);
                    debug_add("Type {$schema_type} has been listed to always be root class, but the class does not exist", MIDCOM_LOG_WARN);
                    debug_pop();
                    continue;
                }
                if (in_array($schema_type, $root_classes))
                {
                    // Already listed
                    continue;
                }
                $root_classes[] = $schema_type;
            }
        }
        usort($root_classes, 'strnatcmp');
        return $root_classes;
    }

    /**
     * Statically callable method to check that none of given objects siblings have same name.
     *
     * Basically this is a proxy for instantiating full reflector class and calling name_is_unique_nonstatic()
     *
     * @param $object the object instance to check for.
     * @return boolean indicating uniqueness
     */
    function name_is_unique(&$object)
    {
        $resolver = new midcom_helper_reflector_tree($object);
        return $resolver->name_is_unique_nonstatic($object);
    }

    /**
     * Statically callable method to check that none of given objects siblings have same name, or the name is empty.
     *
     * @param $object the object instance to check for.
     * @return boolean indicating uniqueness
     */
    function name_is_unique_or_empty(&$object)
    {
        $name_copy = midcom_helper_reflector::get_object_name($object);
        if (   empty($name_copy)
            && $name_copy !== false)
        {
            // Allow empty string name
            return true;
        }
        return midcom_helper_reflector_tree::name_is_unique($object);
    }

    /**
     * Helper for name_is_unique_nonstatic, checks uniqueness for each sibling
     *
     * @param array $sibling_classes array of classes to check
     * @param object $object reference to the object to check for
     * @return boolean true means no clashes, false means clash.
     */
    function _name_is_unique_nonstatic_check_siblings($sibling_classes, &$object, &$parent)
    {
        $name_copy = midcom_helper_reflector::get_object_name($object);
        foreach ($sibling_classes as $schema_type)
        {
            $dummy = new $schema_type();
            $child_name_property = midcom_helper_reflector::get_name_property($dummy);
            unset($dummy);
            if (empty($child_name_property))
            {
                // This sibling class does not use names
                /**
                 * Noise, useful when something is going wrong in *weird* way
                 *
                debug_push_class(__CLASS__, __FUNCTION__);
                debug_add("Sibling class {$schema_type} does not have 'name' property, skipping from checks", MIDCOM_LOG_DEBUG);
                debug_pop();
                */
                continue;
            }
            $qb =& $this->_child_objects_type_qb($schema_type, $parent, false);
            if (!is_object($qb))
            {
                debug_push_class(__CLASS__, __FUNCTION__);
                debug_add("\$this->_child_objects_type_qb('{$schema_type}', \$parent, false) did not return object", MIDCOM_LOG_WARN);
                debug_pop();
                continue;
            }
            $qb->add_constraint($child_name_property, '=', $name_copy);
            // Do not include current object in results, this is the easiest way
            if (   isset($object->guid)
                && !empty($object->guid))
            {
                $qb->add_constraint('guid', '<>', $object->guid);
            }
            // One result is enough to know we have a clash
            $qb->set_limit(1);
            $results = $qb->count();
            // Guard against QB failure
            if ($results === false)
            {
                debug_add("Querying for siblings of class {$schema_type} failed critically, last Midgard error: " . midcom_application::get_error_string(), MIDCOM_LOG_ERROR);
                debug_pop();
                unset($sibling_classes, $schema_type, $qb, $results);
                return false;
            }
            if ($results > 0)
            {
                debug_push_class(__CLASS__, __FUNCTION__);
                debug_add("Name clash in sibling class {$schema_type} for " . get_class($object) . " #{$object->id} (path '" . midcom_helper_reflector_tree::resolve_path($object, '/') . "')" , MIDCOM_LOG_DEBUG);
                debug_pop();
                unset($sibling_classes, $schema_type, $qb, $results);
                return false;
            }
            unset($qb, $results);
        }
        unset($name_copy, $schema_type, $sibling_classes);
        return true;
    }

    /**
     * Helper for name_is_unique_nonstatic, checks uniqueness for each root level class
     *
     * @param array $sibling_classes array of classes to check
     * @param object $object reference to the object to check for
     * @return boolean true means no clashes, false means clash.
     */
    function _name_is_unique_nonstatic_check_roots($sibling_classes, &$object)
    {
        $name_copy = midcom_helper_reflector::get_object_name($object);
        if (!$sibling_classes)
        {
            // We don't know about siblings, allow this to happen.
            // Note: This also happens with the "neverchild" types like midgard_attachment and midgard_parameter
            return true;
        }

        foreach ($sibling_classes as $schema_type)
        {
            $dummy = new $schema_type();
            $child_name_property = midcom_helper_reflector::get_name_property($dummy);
            unset($dummy);
            if (empty($child_name_property))
            {
                // This sibling class does not use names
                /**
                 * Noise, useful when something is going wrong in *weird* way
                 *
                debug_push_class(__CLASS__, __FUNCTION__);
                debug_add("Sibling class {$schema_type} does not have 'name' property, skipping from checks", MIDCOM_LOG_DEBUG);
                debug_pop();
                */
                continue;
            }
            $resolver =& midcom_helper_reflector_tree::get($schema_type);
            $deleted = false;
            $qb =& $resolver->_root_objects_qb($deleted);
            unset($deleted);
            if (!is_object($qb))
            {
                continue;
            }
            $qb->add_constraint($child_name_property, '=', $name_copy);
            // Do not include current object in results, this is the easiest way
            if (   isset($object->guid)
                || !empty($object->guid))
            {
                $qb->add_constraint('guid', '<>', $object->guid);
            }
            // One result is enough to know we have a clash
            $qb->set_limit(1);
            $results = $qb->count();
            // Guard against QB failure
            if ($results === false)
            {
                $_MIDCOM->auth->drop_sudo();
                debug_add("Querying for siblings of class {$schema_type} failed critically, last Midgard error: " . midcom_application::get_error_string(), MIDCOM_LOG_ERROR);
                debug_pop();
                unset($sibling_classes, $schema_type, $qb, $resolver);
                return false;
            }
            if ($results > 0)
            {
                debug_push_class(__CLASS__, __FUNCTION__);
                debug_add("Name clash in sibling class {$schema_type} for " . get_class($object) . " #{$object->id} (path '" . midcom_helper_reflector_tree::resolve_path($object, '/') . "')" , MIDCOM_LOG_DEBUG);
                debug_pop();
                unset($sibling_classes, $schema_type, $qb, $resolver);
                return false;
            }
        }
        return true;
    }

    /**
     * Method to check that none of given objects siblings have same name.
     *
     * @param $object the object instance to check for.
     * @return boolean indicating uniqueness
     */
    function name_is_unique_nonstatic(&$object)
    {
        // Check against static calling
        if (   !isset($this->mgdschema_class)
            || empty($this->mgdschema_class))
        {
            debug_push_class(__CLASS__, __FUNCTION__);
            debug_add('May not be called statically', MIDCOM_LOG_ERROR);
            debug_pop();
            return false;
        }

        // Get current name and sanity-check
        $name_copy = midcom_helper_reflector::get_object_name($object);
        if (empty($name_copy))
        {
            // We do not check for empty names, and do not consider them to be unique
            unset($name_copy);
            return false;
        }

        // Start the magic
        $_MIDCOM->auth->request_sudo('midcom.helper.reflector');
        $parent = midcom_helper_reflector_tree::get_parent($object);
        if (   $parent
            && isset($parent->guid)
            && !empty($parent->guid))
        {
            // We have parent, check siblings
            $parent_resolver = new midcom_helper_reflector_tree($parent);
            $sibling_classes = $parent_resolver->get_child_classes();
            if (!in_array('midgard_attachment', $sibling_classes))
            {
                $sibling_classes[] = 'midgard_attachment';
            }
            if (!$this->_name_is_unique_nonstatic_check_siblings($sibling_classes, $object, $parent))
            {
                unset($parent, $parent_resolver, $sibling_classes);
                $_MIDCOM->auth->drop_sudo();
                return false;
            }
            unset($parent, $parent_resolver, $sibling_classes);
        }
        else
        {
            unset($parent);
            // No parent, we might be a root level class
            $is_root_class = false;
            $root_classes = $this->get_root_classes();
            foreach($root_classes as $classname)
            {
                if ($_MIDCOM->dbfactory->is_a($object, $classname))
                {
                    $is_root_class = true;
                    if (!$this->_name_is_unique_nonstatic_check_roots($root_classes, $object))
                    {
                        unset($is_root_class, $root_classes);
                        $_MIDCOM->auth->drop_sudo();
                        return false;
                    }
                }
            }
            unset($root_classes);
            if (!$is_root_class)
            {
                // This should not happen, logging error and returning true (even though it's potentially dangerous)
                $_MIDCOM->auth->drop_sudo();
                debug_push_class(__CLASS__, __FUNCTION__);
                debug_add("Object #{$object->guid} has no valid parent but is not listed in the root classes, don't know what to do, returning true and supposing user knows what he is doing", MIDCOM_LOG_ERROR);
                debug_pop();
                unset($is_root_class);
                return true;
            }
        }

        $_MIDCOM->auth->drop_sudo();
        // If we get this far we know we don't have name clashes
        return true;
    }


    /**
     * statically callable method to generates an unique name for the given object.
     *
     * 1st IF name is empty, we generate one from title (if title is empty too, we return false)
     * Then we check if it's unique, if not we add an incrementing
     * number to it (before this we make some educated guesses about a
     * good starting value)
     *
     * @see midcom_helper_reflector_tree::generate_unique_name_nonstatic()
     * @param object $object reference to the object to handle
     * @param srting $title_property, property of the object to use at title, if null will be reflected (see  midcom_helper_reflector::get_object_title())
     * @return string string usable as name or boolean false on critical failures
     */
    function generate_unique_name(&$object, $title_property=null)
    {
        $resolver = new midcom_helper_reflector_tree($object);
        return $resolver->generate_unique_name_nonstatic($object, $title_property);
    }


    /**
     * Generates an unique name for the given object.
     *
     * 1st IF name is empty, we generate one from title (if title is empty too, we return false)
     * Then we check if it's unique, if not we add an incrementing
     * number to it (before this we make some educated guesses about a
     * good starting value)
     *
     * @param object $object reference to the object to handle
     * @param srting $title_property, property of the object to use at title, if null will be reflected (see  midcom_helper_reflector::get_object_title())
     * @return string string usable as name or boolean false on critical failures
     */
    function generate_unique_name_nonstatic(&$object, $title_property=null)
    {
        // Check against static calling
        if (   !isset($this->mgdschema_class)
            || empty($this->mgdschema_class))
        {
            debug_push_class(__CLASS__, __FUNCTION__);
            debug_add('May not be called statically', MIDCOM_LOG_ERROR);
            debug_pop();
            return false;
        }

        // Get current name and sanity-check
        $original_name = midcom_helper_reflector::get_object_name($object);
        if ($original_name === false)
        {
            // Fatal error with name resolution
            debug_push_class(__CLASS__, __FUNCTION__);
            debug_add("Object " . get_class($object) . " #{$object->id} returned critical failure for name resolution, aborting", MIDCOM_LOG_WARN);
            debug_pop();
            return false;
        }

        // We need the name of the "name" property later
        $name_prop = midcom_helper_reflector::get_name_property($object);

        if (!empty($original_name))
        {
            $current_name = (string)$original_name;
        }
        else
        {
            // Empty name, try to generate from title
            $title_copy = midcom_helper_reflector::get_object_title($object, $title_property);
            if ($title_copy === false)
            {
                unset($title_copy);
                // Fatal error with title resolution
                debug_push_class(__CLASS__, __FUNCTION__);
                debug_add("Object " . get_class($object) . " #{$object->id} returned critical failure for title resolution when name was empty, aborting", MIDCOM_LOG_WARN);
                debug_pop();
                return false;
            }
            if (empty($title_copy))
            {
                unset($title_copy);
                debug_push_class(__CLASS__, __FUNCTION__);
                debug_add("Object " . get_class($object) . " #{$object->id} has empty name and title, aborting", MIDCOM_LOG_WARN);
                debug_pop();
                return false;
            }
            $current_name = midcom_generate_urlname_from_string($title_copy);
            unset($title_copy);
        }

        // incrementer, the number to add as suffix and the base name. see _generate_unique_name_nonstatic_resolve_i()
        list ($i, $base_name) = $this->_generate_unique_name_nonstatic_resolve_i($current_name, $object);

        $object->name = $base_name;
        // decementer, do not try more than this many times (the incrementer can raise above this if we start high enough.
        $d = 100;

        // The loop, usually we *should* hit gold in first try
        do
        {
            if ($i > 1)
            {
                // Start suffixes from -002
                $object->{$name_prop} = $base_name . sprintf('-%03d', $i);
            }

            // Handle the decrementer
            --$d;
            if ($d < 1)
            {
                // Decrementer undeflowed
                debug_push_class(__CLASS__, __FUNCTION__);
                debug_add("Maximum number of tries exceeded, current name was: " . $object->{$name_prop} , MIDCOM_LOG_ERROR);
                debug_pop();
                $object->{$name_prop} = $original_name;
                unset($i, $d, $name_prop, $original_name, $base_name);
                return false;
            }
            // and the incrementer
            ++$i;
        }
        while (!$this->name_is_unique_nonstatic($object));
        unset($i, $d);

        // Get a copy of the current, usable name
        $ret = (string)$object->{$name_prop};
        // Restore the original name
        $object->{$name_prop} = $original_name;
        unset($name_prop, $original_name, $base_name);
        return $ret;
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
     * @static
     * @access public
     * @param mixed $source        GUID or MgdSchema object that will be copied
     * @param mixed $parent        MgdSchema or MidCOM db object, predefined array or ID of the parent object
     * @param array $exclude       IDs that will be excluded from the copying
     * @param boolean $parameters  Switch to determine if the parameters should be copied
     * @param boolean $metadata    Switch to determine if the metadata should be copied (excluding created and published)
     * @param boolean $attachments Switch to determine if the attachments should be copied (creates only a new link, doesn't duplicate the content)
     * @return mixed               False on failure, newly created MgdSchema root object on success
     */
    static public function copy_object_tree($source, $parent, $exclude = array(), $parameters = true, $metadata = true, $attachments = true)
    {
        $copy = new midcom_helper_reflector_copy();
        $copy->source =& $source;
        $copy->target =& $parent;
        $copy->parameters = $parameters;
        $copy->metadata = $metadata;
        $copy->attachments = $attachments;

        if (!$copy->copy)
        {
            return false;
        }

        return $copy->get_object();
    }

    /**
     * Helper to resolve the base value for the incementing suffix and for the name.
     *
     * @see midcom_helper_reflector_tree::generate_unique_name_nonstatic()
     * @param string $current_name the "current name" of the object (might not be the actual name value see the title logic in generate_unique_name_nonstatic())
     * @param object $object reference to the object we're working on
     * @return array first key is the resolved $i second is the $base_name, which is $current_name without numeric suffix
     */
    function _generate_unique_name_nonstatic_resolve_i($current_name, &$object)
    {
        if (preg_match('/(.*?)-([0-9]{3,})$/', $current_name, $name_matches))
        {
            // Name already has i and base parts, split them.
            $i = (int)$name_matches[2];
            $base_name = (string)$name_matches[1];
            unset($name_matches);
        }
        else
        {
            // Defaults
            $i = 1;
            $base_name = $current_name;
        }

        // Look for siblings with similar names and see if they have higher i.
        $_MIDCOM->auth->request_sudo('midcom.helper.reflector');
        $parent = midcom_helper_reflector_tree::get_parent($object);
        // TODO: Refactor to reduce duplicate code with _name_is_unique_nonstatic_check_siblings
        if (   $parent
            && isset($parent->guid)
            && !empty($parent->guid))
        {
            // We have parent, check siblings
            $parent_resolver = new midcom_helper_reflector_tree($parent);
            $sibling_classes = $parent_resolver->get_child_classes();
            if (!in_array('midgard_attachment', $sibling_classes))
            {
                $sibling_classes[] = 'midgard_attachment';
            }
            foreach ($sibling_classes as $schema_type)
            {
                $dummy = new $schema_type();
                $child_name_property = midcom_helper_reflector::get_name_property($dummy);
                unset($dummy);
                if (empty($child_name_property))
                {
                    // This sibling class does not use names
                    continue;
                }
                $qb =& $this->_child_objects_type_qb($schema_type, $parent, false);
                if (!is_object($qb))
                {
                    continue;
                }
                $qb->add_constraint($child_name_property, 'LIKE', "{$base_name}-%");
                // Do not include current object in results, this is the easiest way
                if (   isset($object->guid)
                    || !empty($object->guid))
                {
                    $qb->add_constraint('guid', '<>', $object->guid);
                }
                $qb->add_order('name', 'DESC');
                // One result should be enough
                $qb->set_limit(1);
                $siblings = $qb->execute();
                if (empty($siblings))
                {
                    // we dont' care about fatal qb errors here
                    continue;
                }
                $sibling = $siblings[0];
                $sibling_name = $sibling->{$child_name_property};
                if (preg_match('/(.*?)-([0-9]{3,})$/', $sibling_name, $name_matches))
                {
                    // Name already has i and base parts, split them.
                    $sibling_i = (int)$name_matches[2];

                    if ($sibling_i >= $i)
                    {
                        $i = $sibling_i + 1;
                    }
                    unset($sibling_i, $name_matches);
                }
            }
            unset($parent, $parent_resolver, $sibling_classes, $schema_type, $child_name_property, $sibling, $sibling_name);
        }
        else
        {
            unset($parent);
            // No parent, we might be a root level class
            $is_root_class = false;
            $root_classes = $this->get_root_classes();
            foreach($root_classes as $schema_type)
            {
                if ($_MIDCOM->dbfactory->is_a($object, $schema_type))
                {
                    $is_root_class = true;
                }
            }
            if (!$is_root_class)
            {
                // This should not happen, logging error and returning true (even though it's potentially dangerous)
                $_MIDCOM->auth->drop_sudo();
                debug_push_class(__CLASS__, __FUNCTION__);
                debug_add("Object #{$object->guid} has no valid parent but is not listed in the root classes, don't know what to do, letting higher level decide", MIDCOM_LOG_ERROR);
                debug_pop();
                unset($root_classes, $is_root_class);
                return array($i, $base_name);
            }
            else
            {
                // TODO: Refactor to reduce duplicate code with _name_is_unique_nonstatic_check_roots
                foreach($root_classes as $schema_type)
                {
                    $dummy = new $schema_type();
                    $child_name_property = midcom_helper_reflector::get_name_property($dummy);
                    unset($dummy);
                    if (empty($child_name_property))
                    {
                        // This sibling class does not use names
                        continue;
                    }
                    $resolver =& midcom_helper_reflector_tree::get($schema_type);
                    $deleted = false;
                    $qb =& $resolver->_root_objects_qb($deleted);
                    if (!$qb)
                    {
                        continue;
                    }
                    unset($deleted);
                    $qb->add_constraint($child_name_property, 'LIKE', "{$base_name}-%");
                    // Do not include current object in results, this is the easiest way
                    if (   isset($object->guid)
                        || !empty($object->guid))
                    {
                        $qb->add_constraint('guid', '<>', $object->guid);
                    }
                    $qb->add_order($child_name_property, 'DESC');
                    // One result should be enough
                    $qb->set_limit(1);
                    $siblings = $qb->execute();
                    if (empty($siblings))
                    {
                        // we dont' care about fatal qb errors here
                        continue;
                    }
                    $sibling = $siblings[0];
                    $sibling_name = $sibling->{$child_name_property};
                    if (preg_match('/(.*?)-([0-9]{3,})$/', $sibling_name, $name_matches))
                    {
                        // Name already has i and base parts, split them.
                        $sibling_i = (int)$name_matches[2];
                        if ($sibling_i >= $i)
                        {
                            $i = $sibling_i + 1;
                        }
                        unset($sibling_i, $name_matches);
                    }
                }
                unset($root_classes, $schema_type, $child_name_property, $sibling, $sibling_name);
            }
        }
        $_MIDCOM->auth->drop_sudo();

        return array($i, $base_name);
    }

    /**
     * Static method to add default ("title" and "name") sorts to a QB instance
     *
     * @param midgard_query_builder $qb reference to QB instance
     * @param string $schema_type valid mgdschema class name
     */
    function add_schema_sorts_to_qb(&$qb, $schema_type)
    {
        // Sort by "title" and "name" if available
        $ref = midcom_helper_reflector_tree::get($schema_type);
        $dummy = new $schema_type();
        $title_property = $ref->get_title_property($dummy);
        if (   is_string($title_property)
            && $_MIDCOM->dbfactory->property_exists($schema_type, $title_property))
        {
            $qb->add_order($title_property);
        }
        $name_property = $ref->get_name_property($dummy);
        if (   is_string($name_property)
            && $_MIDCOM->dbfactory->property_exists($schema_type, $name_property))
        {
            $qb->add_order($name_property);
        }
        unset($title_property, $name_property, $ref, $dummy);
    }

}
?>
