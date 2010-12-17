<?php
/**
 * @package midcom.helper.reflector
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * Helper class for object name handling
 *
 * @package midcom.helper.reflector
 */
class midcom_helper_reflector_nameresolver
{
    /**
     * The object we're working with
     *
     * @var midcom_core_dbaobject
     */
    private $_object;

    public function __construct(&$object)
    {
        $this->_object = $object;
    }

    /**
     * Resolves the "name" of given object
     *
     * @param $name_property property to use as "name", if left to default (null), will be reflected
     * @return string value of name property or boolean false on failure
     */
    public function get_object_name($name_property = null)
    {
        if (is_null($name_property))
        {
            $name_property = midcom_helper_reflector::get_name_property($this->_object);
        }
        if (   empty($name_property)
            || !$_MIDCOM->dbfactory->property_exists($this->_object, $name_property))
        {
            // Could not resolve valid property
            return false;
        }
        // Make copy via typecast, very important or we might accidentally manipulate the given object
        $name_copy = (string)$this->_object->{$name_property};
        unset($name_property);
        return $name_copy;
    }

    /**
     * Checks for "clean" URL name
     *
     * @see http://trac.midgard-project.org/ticket/809
     * @param $name_property property to use as "name", if left to default (null), will be reflected
     * @return boolean indicating cleanlines (on missing property or other failure returns false)
     */
    public function name_is_clean($name_property = null)
    {
        $name_copy = $this->get_object_name($name_property);
        if ($name_copy === false)
        {
            //get_object_name failed
            return false;
        }
        if (empty($name_copy))
        {
            // empty name is not "clean"
            return false;
        }
        if ($name_copy !== midcom_helper_misc::generate_urlname_from_string($name_copy))
        {
            unset($name_copy);
            return false;
        }
        unset($name_copy);
        return true;
    }

    /**
     * Checks for URL-safe name
     *
     * @see http://trac.midgard-project.org/ticket/809
     * @param $name_property property to use as "name", if left to default (null), will be reflected
     * @return boolean indicating safety (on missing property or other failure returns false)
     */
    public function name_is_safe($name_property = null)
    {
        $name_copy = $this->get_object_name($name_property);
        if ($name_copy === false)
        {
            //get_object_name failed
            return false;
        }
        if (empty($name_copy))
        {
            // empty name is not url-safe
            return false;
        }
        if ($name_copy !== rawurlencode($name_copy))
        {
            unset($name_copy);
            return false;
        }
        unset($name_copy);
        return true;
    }

    /**
     * Checks for URL-safe name, this variant accepts empty name
     *
     * @see http://trac.midgard-project.org/ticket/809
     * @param $name_property property to use as "name", if left to default (null), will be reflected
     * @return boolean indicating safety (on missing property or other failure returns false)
     */
    public function name_is_safe_or_empty($name_property = null)
    {
        $name_copy = $this->get_object_name($name_property);
        if ($name_copy === false)
        {
            //get_object_name failed
            return false;
        }
        if (empty($name_copy))
        {
            return true;
        }
        return $this->name_is_safe($name_property);
    }

    /**
     * Checks for "clean" URL name, this variant accepts empty name
     *
     * @see http://trac.midgard-project.org/ticket/809
     * @param $name_property property to use as "name", if left to default (null), will be reflected
     * @return boolean indicating cleanlines (on missing property or other failure returns false)
     */
    public function name_is_clean_or_empty($name_property = null)
    {
        $name_copy = $this->get_object_name($name_property);
        if ($name_copy === false)
        {
            //get_object_name failed
            return false;
        }
        if (empty($name_copy))
        {
            return true;
        }
        return $this->name_is_clean($name_property);
    }

    /**
     * Check that none of given objects siblings have same name, or the name is empty.
     *
     * @return boolean indicating uniqueness
     */
    public function name_is_unique_or_empty()
    {
        $name_copy = $this->get_object_name();
        if (   empty($name_copy)
            && $name_copy !== false)
        {
            // Allow empty string name
            return true;
        }
        return $this->name_is_unique();
    }

    /**
     * Method to check that none of given objects siblings have same name.
     *
     * @return boolean indicating uniqueness
     */
    public function name_is_unique()
    {
        // Get current name and sanity-check
        $name_copy = $this->get_object_name();
        if (empty($name_copy))
        {
            // We do not check for empty names, and do not consider them to be unique
            unset($name_copy);
            return false;
        }

        // Start the magic
        $_MIDCOM->auth->request_sudo('midcom.helper.reflector');
        $parent = midcom_helper_reflector_tree::get_parent($this->_object);
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
            if (!$this->_name_is_unique_check_siblings($sibling_classes, $parent))
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
            $root_classes = midcom_helper_reflector_tree::get_root_classes();
            foreach($root_classes as $classname)
            {
                if ($_MIDCOM->dbfactory->is_a($this->_object, $classname))
                {
                    $is_root_class = true;
                    if (!$this->_name_is_unique_check_roots($root_classes))
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
                debug_add("Object #{$this->_object->guid} has no valid parent but is not listed in the root classes, don't know what to do, returning true and supposing user knows what he is doing", MIDCOM_LOG_ERROR);
                unset($is_root_class);
                return true;
            }
        }

        $_MIDCOM->auth->drop_sudo();
        // If we get this far we know we don't have name clashes
        return true;
    }

    /**
     * Helper for name_is_unique, checks uniqueness for each sibling
     *
     * @param array $sibling_classes array of classes to check
     * @return boolean true means no clashes, false means clash.
     */
    private function _name_is_unique_check_siblings($sibling_classes, &$parent)
    {
        $name_copy = $this->get_object_name();

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
                debug_add("Sibling class {$schema_type} does not have 'name' property, skipping from checks");
                */
                continue;
            }
            $resolver = midcom_helper_reflector_tree::get($schema_type);
            $qb =& $resolver->_child_objects_type_qb($schema_type, $parent, false);
            if (!is_object($qb))
            {
                debug_add("\$resolver->_child_objects_type_qb('{$schema_type}', \$parent, false) did not return object", MIDCOM_LOG_WARN);
                continue;
            }
            $qb->add_constraint($child_name_property, '=', $name_copy);
            // Do not include current object in results, this is the easiest way
            if (   isset($this->_object->guid)
                && !empty($this->_object->guid))
            {
                $qb->add_constraint('guid', '<>', $this->_object->guid);
            }
            // One result is enough to know we have a clash
            $qb->set_limit(1);
            $results = $qb->count();
            // Guard against QB failure
            if ($results === false)
            {
                debug_add("Querying for siblings of class {$schema_type} failed critically, last Midgard error: " . midcom_connection::get_error_string(), MIDCOM_LOG_ERROR);
                unset($sibling_classes, $schema_type, $qb, $results);
                return false;
            }
            if ($results > 0)
            {
                debug_add("Name clash in sibling class {$schema_type} for " . get_class($this->_object) . " #{$this->_object->id} (path '" . midcom_helper_reflector_tree::resolve_path($this->_object, '/') . "')" );
                unset($sibling_classes, $schema_type, $qb, $results);
                return false;
            }
            unset($qb, $results);
        }
        unset($name_copy, $schema_type, $sibling_classes);
        return true;
    }

    /**
     * Helper for name_is_unique, checks uniqueness for each root level class
     *
     * @param array $sibling_classes array of classes to check
     * @return boolean true means no clashes, false means clash.
     */
    private function _name_is_unique_check_roots($sibling_classes)
    {
        if (!$sibling_classes)
        {
            // We don't know about siblings, allow this to happen.
            // Note: This also happens with the "neverchild" types like midgard_attachment and midgard_parameter
            return true;
        }
        $name_copy = $this->get_object_name();

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
                debug_add("Sibling class {$schema_type} does not have 'name' property, skipping from checks");
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
            if (   isset($this->_object->guid)
                || !empty($this->_object->guid))
            {
                $qb->add_constraint('guid', '<>', $this->_object->guid);
            }
            // One result is enough to know we have a clash
            $qb->set_limit(1);
            $results = $qb->count();
            // Guard against QB failure
            if ($results === false)
            {
                $_MIDCOM->auth->drop_sudo();
                debug_add("Querying for siblings of class {$schema_type} failed critically, last Midgard error: " . midcom_connection::get_error_string(), MIDCOM_LOG_ERROR);
                unset($sibling_classes, $schema_type, $qb, $resolver);
                return false;
            }
            if ($results > 0)
            {
                debug_add("Name clash in sibling class {$schema_type} for " . get_class($this->_object) . " #{$this->_object->id} (path '" . midcom_helper_reflector_tree::resolve_path($this->_object, '/') . "')" );
                unset($sibling_classes, $schema_type, $qb, $resolver);
                return false;
            }
        }
        return true;
    }

    /**
     * Generates an unique name for the given object.
     *
     * 1st IF name is empty, we generate one from title (if title is empty too, we return false)
     * Then we check if it's unique, if not we add an incrementing
     * number to it (before this we make some educated guesses about a
     * good starting value)
     *
     * @param srting $title_property, property of the object to use at title, if null will be reflected (see  midcom_helper_reflector::get_object_title())
     * @return string string usable as name or boolean false on critical failures
     */
    public function generate_unique_name($title_property = null)
    {
        // Get current name and sanity-check
        $original_name = $this->get_object_name();
        if ($original_name === false)
        {
            // Fatal error with name resolution
            debug_add("Object " . get_class($this->_object) . " #{$this->_object->id} returned critical failure for name resolution, aborting", MIDCOM_LOG_WARN);
            return false;
        }

        // We need the name of the "name" property later
        $name_prop = midcom_helper_reflector::get_name_property($this->_object);

        if (!empty($original_name))
        {
            $current_name = (string)$original_name;
        }
        else
        {
            // Empty name, try to generate from title
            $title_copy = midcom_helper_reflector::get_object_title($this->_object, $title_property);
            if ($title_copy === false)
            {
                unset($title_copy);
                // Fatal error with title resolution
                debug_add("Object " . get_class($this->_object) . " #{$this->_object->id} returned critical failure for title resolution when name was empty, aborting", MIDCOM_LOG_WARN);
                return false;
            }
            if (empty($title_copy))
            {
                unset($title_copy);
                debug_add("Object " . get_class($this->_object) . " #{$this->_object->id} has empty name and title, aborting", MIDCOM_LOG_WARN);
                return false;
            }
            $current_name = midcom_helper_misc::generate_urlname_from_string($title_copy);
            unset($title_copy);
        }

        // incrementer, the number to add as suffix and the base name. see _generate_unique_name_resolve_i()
        list ($i, $base_name) = $this->_generate_unique_name_resolve_i($current_name);

        $this->_object->name = $base_name;
        // decrementer, do not try more than this many times (the incrementer can raise above this if we start high enough.
        $d = 100;

        // The loop, usually we *should* hit gold in first try
        do
        {
            if ($i > 1)
            {
                // Start suffixes from -002
                $this->_object->{$name_prop} = $base_name . sprintf('-%03d', $i);
            }

            // Handle the decrementer
            --$d;
            if ($d < 1)
            {
                // Decrementer undeflowed
                debug_add("Maximum number of tries exceeded, current name was: " . $this->_object->{$name_prop} , MIDCOM_LOG_ERROR);
                $this->_object->{$name_prop} = $original_name;
                unset($i, $d, $name_prop, $original_name, $base_name);
                return false;
            }
            // and the incrementer
            ++$i;
        }
        while (!$this->name_is_unique());
        unset($i, $d);

        // Get a copy of the current, usable name
        $ret = (string)$this->_object->{$name_prop};
        // Restore the original name
        $this->_object->{$name_prop} = $original_name;
        unset($name_prop, $original_name, $base_name);
        return $ret;
    }

    /**
     * Helper to resolve the base value for the incrementing suffix and for the name.
     *
     * @see midcom_helper_reflector_nameresolver::generate_unique_name()
     * @param string $current_name the "current name" of the object (might not be the actual name value see the title logic in generate_unique_name())
     * @return array first key is the resolved $i second is the $base_name, which is $current_name without numeric suffix
     */
    private function _generate_unique_name_resolve_i($current_name)
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
        $parent = midcom_helper_reflector_tree::get_parent($this->_object);
        // TODO: Refactor to reduce duplicate code with _name_is_unique_check_siblings
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
                $resolver = midcom_helper_reflector_tree::get($schema_type);
                $qb =& $resolver->_child_objects_type_qb($schema_type, $parent, false);
                if (!is_object($qb))
                {
                    continue;
                }
                $qb->add_constraint($child_name_property, 'LIKE', "{$base_name}-%");
                // Do not include current object in results, this is the easiest way
                if (   isset($this->_object->guid)
                    || !empty($this->_object->guid))
                {
                    $qb->add_constraint('guid', '<>', $this->_object->guid);
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
            $root_classes = midcom_helper_reflector_tree::get_root_classes();
            foreach($root_classes as $schema_type)
            {
                if ($_MIDCOM->dbfactory->is_a($this->_object, $schema_type))
                {
                    $is_root_class = true;
                }
            }
            if (!$is_root_class)
            {
                // This should not happen, logging error and returning true (even though it's potentially dangerous)
                $_MIDCOM->auth->drop_sudo();
                debug_add("Object #{$this->_object->guid} has no valid parent but is not listed in the root classes, don't know what to do, letting higher level decide", MIDCOM_LOG_ERROR);
                unset($root_classes, $is_root_class);
                return array($i, $base_name);
            }
            else
            {
                // TODO: Refactor to reduce duplicate code with _name_is_unique_check_roots
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
                    if (   isset($this->_object->guid)
                        || !empty($this->_object->guid))
                    {
                        $qb->add_constraint('guid', '<>', $this->_object->guid);
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
}
?>