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

    public function __construct($object)
    {
        $this->_object = $object;
    }

    /**
     * Resolves the "name" of given object
     *
     * @param string $name_property property to use as "name", if left to default (null), will be reflected
     * @return string value of name property or null on failure
     */
    public function get_object_name(string $name_property = null) : ?string
    {
        if ($name_property === null) {
            $name_property = midcom_helper_reflector::get_name_property($this->_object);
        }
        if (    empty($name_property)
            || !midcom_helper_reflector::get($this->_object)->property_exists($name_property)) {
            // Could not resolve valid property
            return null;
        }
        return $this->_object->{$name_property};
    }

    /**
     * Checks for "clean" URL name
     *
     * @see http://trac.midgard-project.org/ticket/809
     * @param string $name_property property to use as "name", if left to default (null), will be reflected
     */
    public function name_is_clean(string $name_property = null) : bool
    {
        if ($name_copy = $this->get_object_name($name_property)) {
            return $name_copy === midcom_helper_misc::urlize($name_copy);
        }
        // empty name is not "clean"
        return false;
    }

    /**
     * Checks for URL-safe name
     *
     * @see http://trac.midgard-project.org/ticket/809
     * @param string $name_property property to use as "name", if left to default (null), will be reflected
     */
    public function name_is_safe(string $name_property = null) : bool
    {
        if ($name_copy = $this->get_object_name($name_property)) {
            return $name_copy === rawurlencode($name_copy);
        }
        // empty name is not url-safe
        return false;
    }

    /**
     * Check that none of given object's siblings have same name.
     */
    public function name_is_unique() : bool
    {
        // Get current name and sanity-check
        $name = $this->get_object_name();
        if (empty($name)) {
            // We do not check for empty names, and do not consider them to be unique
            return false;
        }

        // Start the magic
        midcom::get()->auth->request_sudo('midcom.helper.reflector');
        $parent = midcom_helper_reflector_tree::get_parent($this->_object);
        $sibling_classes = $this->get_sibling_classes($parent);
        if ($sibling_classes === null) {
            // This should not happen, logging error and returning true (even though it's potentially dangerous)
            debug_add("Object " . get_class($this->_object) . " #" . $this->_object->id . " has no valid parent but is not listed in the root classes, don't know what to do, returning true and supposing user knows what he is doing", MIDCOM_LOG_ERROR);
            return true;
        }
        $stat = $this->check_sibling_classes($name, $sibling_classes, $parent);

        midcom::get()->auth->drop_sudo();
        return $stat;
    }

    private function get_sibling_classes($parent = null) : ?array
    {
        if (!empty($parent->guid)) {
            // We have parent, check siblings
            $parent_resolver = new midcom_helper_reflector_tree($parent);
            $sibling_classes = $parent_resolver->get_child_classes();
            if (!in_array('midgard_attachment', $sibling_classes)) {
                $sibling_classes[] = 'midgard_attachment';
            }

            return $sibling_classes;
        }
        // No parent, we might be a root level class
        $is_root_class = false;
        $root_classes = midcom_helper_reflector_tree::get_root_classes();
        foreach ($root_classes as $classname) {
            if (midcom::get()->dbfactory->is_a($this->_object, $classname)) {
                $is_root_class = true;
                break;
            }
        }
        if (!$is_root_class) {
            return null;
        }
        return $root_classes;
    }

    private function check_sibling_classes(string $name, array $schema_types, $parent = null) : bool
    {
        foreach ($schema_types as $schema_type) {
            $qb = $this->get_sibling_qb($schema_type, $parent);
            if (!$qb) {
                continue;
            }
            $child_name_property = midcom_helper_reflector::get_name_property(new $schema_type);

            $qb->add_constraint($child_name_property, '=', $name);
            if ($qb->count()) {
                debug_add("Name clash in sibling class {$schema_type} for " . get_class($this->_object) . " #{$this->_object->id} (path '" . midcom_helper_reflector_tree::resolve_path($this->_object, '/') . "')" );
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
     * @param string $title_property Property of the object to use at title, if null will be reflected (see midcom_helper_reflector::get_object_title())
     * @param string $extension The file extension, when working with attachments
     * @return string string usable as name or boolean false on critical failures
     */
    public function generate_unique_name($title_property = null, $extension = '')
    {
        // Get current name and sanity-check
        $original_name = $this->get_object_name();
        if ($original_name === null) {
            // Fatal error with name resolution
            debug_add("Object " . get_class($this->_object) . " #{$this->_object->id} returned critical failure for name resolution, aborting", MIDCOM_LOG_WARN);
            return false;
        }

        // We need the name of the "name" property later
        $name_prop = midcom_helper_reflector::get_name_property($this->_object);

        if (!empty($original_name)) {
            $current_name = $original_name;
        } else {
            // Empty name, try to generate from title
            $title_copy = midcom_helper_reflector::get_object_title($this->_object, $title_property);
            if ($title_copy === false) {
                // Fatal error with title resolution
                debug_add("Object " . get_class($this->_object) . " #{$this->_object->id} returned critical failure for title resolution when name was empty, aborting", MIDCOM_LOG_WARN);
                return false;
            }
            if (empty($title_copy)) {
                debug_add("Object " . get_class($this->_object) . " #{$this->_object->id} has empty name and title, aborting", MIDCOM_LOG_WARN);
                return false;
            }
            $current_name = midcom_helper_misc::urlize($title_copy);
            unset($title_copy);
        }

        // incrementer, the number to add as suffix and the base name. see _generate_unique_name_resolve_i()
        [$i, $base_name] = $this->_generate_unique_name_resolve_i($current_name, $extension);

        $this->_object->name = $base_name;
        // decrementer, do not try more than this many times (the incrementer can raise above this if we start high enough.
        $d = 100;

        // The loop, usually we *should* hit gold in first try
        do {
            if ($i > 1) {
                // Start suffixes from -002
                $this->_object->{$name_prop} = $base_name . sprintf('-%03d', $i) . $extension;
            }

            // Handle the decrementer
            --$d;
            if ($d < 1) {
                // Decrementer underflowed
                debug_add("Maximum number of tries exceeded, current name was: " . $this->_object->{$name_prop}, MIDCOM_LOG_ERROR);
                $this->_object->{$name_prop} = $original_name;
                return false;
            }
            // and the incrementer
            ++$i;
        } while (!$this->name_is_unique());

        // Get a copy of the current, usable name
        $ret = (string)$this->_object->{$name_prop};
        // Restore the original name
        $this->_object->{$name_prop} = $original_name;
        return $ret;
    }

    private function get_sibling_qb(string $schema_type, $parent = null)
    {
        $dummy = new $schema_type();
        $child_name_property = midcom_helper_reflector::get_name_property($dummy);
        if (empty($child_name_property)) {
            // This sibling class does not use names
            return false;
        }
        if ($parent === null) {
            $qb = midcom_helper_reflector_tree::get($schema_type)->_root_objects_qb(false);
        } else {
            $resolver = midcom_helper_reflector_tree::get($schema_type);
            $qb = $resolver->_child_objects_type_qb($schema_type, $parent, false);
        }
        if (!$qb) {
            return false;
        }

        // Do not include current object in results, this is the easiest way
        if (!empty($this->_object->guid)) {
            $qb->add_constraint('guid', '<>', $this->_object->guid);
        }
        $qb->add_order($child_name_property, 'DESC');
        // One result should be enough
        $qb->set_limit(1);
        return $qb;

    }

    private function _parse_filename(string $name, string $extension, $default = 0) : array
    {
        if (preg_match('/(.*?)-([0-9]{3,})' . $extension . '$/', $name, $name_matches)) {
            // Name already has i and base parts, split them.
            return [(int) $name_matches[2], (string) $name_matches[1]];
        }
        // Defaults
        return [$default, $name];
    }

    /**
     * Resolve the base value for the incrementing suffix and for the name.
     *
     * @see midcom_helper_reflector_nameresolver::generate_unique_name()
     * @param string $current_name the "current name" of the object (might not be the actual name value see the title logic in generate_unique_name())
     * @param string $extension The file extension, when working with attachments
     * @return array first key is the resolved $i second is the $base_name, which is $current_name without numeric suffix
     */
    private function _generate_unique_name_resolve_i(string $current_name, string $extension) : array
    {
        [$i, $base_name] = $this->_parse_filename($current_name, $extension, 1);

        // Look for siblings with similar names and see if they have higher i.
        midcom::get()->auth->request_sudo('midcom.helper.reflector');
        $parent = midcom_helper_reflector_tree::get_parent($this->_object);
        $sibling_classes = $this->get_sibling_classes($parent);
        if ($sibling_classes === null) {
            // This should not happen, logging error and returning true (even though it's potentially dangerous)
            midcom::get()->auth->drop_sudo();
            debug_add("Object " . get_class($this->_object) . " #" . $this->_object->id . " has no valid parent but is not listed in the root classes, don't know what to do, letting higher level decide", MIDCOM_LOG_ERROR);
            return [$i, $base_name];
        }
        foreach ($sibling_classes as $schema_type) {
            $i = $this->process_schema_type($this->get_sibling_qb($schema_type, $parent), $i, $schema_type, $base_name, $extension);
        }
        midcom::get()->auth->drop_sudo();

        return [$i, $base_name];
    }

    private function process_schema_type($qb, $i, string $schema_type, string $base_name, string $extension) : int
    {
        if (!$qb) {
            return $i;
        }
        $child_name_property = midcom_helper_reflector::get_name_property(new $schema_type);

        $qb->add_constraint($child_name_property, 'LIKE', "{$base_name}-%" . $extension);
        $siblings = $qb->execute();
        if (!empty($siblings)) {
            $sibling = $siblings[0];
            $sibling_name = $sibling->{$child_name_property};

            $sibling_i = $this->_parse_filename($sibling_name, $extension)[0];
            if ($sibling_i >= $i) {
                $i = $sibling_i + 1;
            }
        }
        return $i;
    }
}
