<?php
/**
 * @package midcom.helper
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use midgard\portable\api\error\exception as mgd_exception;

/**
 * This class contains various factory methods to retrieve objects from the database.
 * The only instance of this class you should ever use is available through
 * midcom::get()->dbfactory.
 *
 * @package midcom.helper
 */
class midcom_helper__dbfactory
{
    /**
     * ID => GUID cache for parents
     *
     * @var array
     */
    private $_parent_mapping = [];

    /**
     * Cache for possible parent configurations per mgdschema class
     *
     * @var array
     */
    private $_parent_candidates = [];

    /**
     * This is a replacement for the original midgard_object_class::get_object_by_guid method, which takes
     * the MidCOM DBA system into account.
     *
     * @param string $guid The object GUID.
     * @return midcom_core_dbaobject A MidCOM DBA object if the set GUID is known
     */
    public function get_object_by_guid($guid)
    {
        try {
            $tmp = midgard_object_class::get_object_by_guid($guid);
        } catch (mgd_exception $e) {
            debug_add('Loading object by GUID ' . $guid . ' failed, reason: ' . $e->getMessage(), MIDCOM_LOG_INFO);

            throw new midcom_error_midgard($e, $guid);
        }
        $person_class = midcom::get()->config->get('person_class');
        if (   get_class($tmp) == 'midgard_person'
            && $person_class != 'midgard_person') {
            $tmp = new $person_class($guid);
        }
        return $this->convert_midgard_to_midcom($tmp);
    }

    /**
     * Retrieve a reference to an object, uses in-request caching
     *
     * @param string $classname Which DBA are we dealing with
     * @param mixed $src GUID of object (ids work but are discouraged)
     * @return midcom_core_dbaobject reference to object
     */
    public function &get_cached($classname, $src)
    {
        static $cache = [];

        if (empty($src)) {
            throw new midcom_error('invalid source identifier');
        }

        if (!isset($cache[$classname])) {
            $cache[$classname] = [];
        }

        if (isset($cache[$classname][$src])) {
            return $cache[$classname][$src];
        }
        $object = new $classname($src);
        $cache[$classname][$object->guid] = $object;
        $cache[$classname][$object->id] =& $cache[$classname][$object->guid];
        return $cache[$classname][$object->guid];
    }

    /**
     * This function will determine the correct type of midgard_collector that
     * has to be created. It will also call the _on_prepare_new_collector event handler.
     *
     * @param string $classname The name of the class for which you want to create a collector.
     * @param string $domain The domain property of the collector instance
     * @param mixed $value Value match for the collector instance
     * @return midcom_core_collector The initialized instance of the collector.
     * @see midcom_core_collector
     */
    public function new_collector($classname, $domain, $value) : midcom_core_collector
    {
        return new midcom_core_collector($classname, $domain, $value);
    }

    /**
     * This function will determine the correct type of midgard_query_builder that
     * has to be created. It will also call the _on_prepare_new_query_builder event handler.
     *
     * @param string $classname The name of the class for which you want to create a query builder.
     * @return midcom_core_querybuilder The initialized instance of the query builder.
     * @see midcom_core_querybuilder
     */
    public function new_query_builder($classname) : midcom_core_querybuilder
    {
        return new midcom_core_querybuilder($classname);
    }

    /**
     * Convert MgdSchema object into a MidCOM DBA object.
     *
     * If the conversion cannot be done for some reason, the function returns null and logs
     * an error. We also ensure that the corresponding component has been loaded.
     *
     * @param midgard\portable\api\mgdobject $object MgdSchema Object
     * @return midcom_core_dbaobject
     */
    public function convert_midgard_to_midcom($object) : midcom_core_dbaobject
    {
        if (!is_object($object)) {
            debug_print_r("Object dump:", $object);
            throw new midcom_error("Cannot cast the object to a MidCOM DBA type, it is not an object.");
        }

        if (!midcom::get()->dbclassloader->is_mgdschema_object($object)) {
            debug_print_r("Object dump:", $object);
            throw new midcom_error("Cannot cast the object to a MidCOM DBA type, it is not a regular MgdSchema object");
        }
        $classname = midcom::get()->dbclassloader->get_midcom_class_name_for_mgdschema_object($object);

        if (!class_exists($classname)) {
            throw new midcom_error("Got non-existing DBA class {$classname} for object of type " . get_class($object) . ", cannot convert.");
        }

        return new $classname($object);
    }

    /**
     * Helper function, it takes a MidCOM DBA object and converts it into an MgdSchema object.
     *
     * If the conversion cannot be done for some reason, the function throws an error.
     *
     * @param midcom_core_dbaobject $object MidCOM DBA Object
     * @return midgard\portable\api\mgdobject MgdSchema Object
     */
    public function convert_midcom_to_midgard($object)
    {
        if (!is_object($object)) {
            debug_print_r("Object dump:", $object);
            throw new midcom_error("Cannot cast the object to an MgdSchema type, it is not an object");
        }

        if (!midcom::get()->dbclassloader->is_midcom_db_object($object)) {
            if (midcom::get()->dbclassloader->is_mgdschema_object($object)) {
                // Return it directly, it is already in the format we want
                return $object;
            }
            debug_print_r("Object dump:", $object);
            throw new midcom_error("Cannot cast the object to an MgdSchema type, it is not a MidCOM DBA object");
        }

        return $object->__object;
    }

    /**
     * This is a helper for emulating is_a() functionality with MidCOM DBA objects that are decorators.
     * This method can be used to check whether an object is of a MidCOM DBA or MgdSchema type
     *
     * @param mixed $object MgdSchema or MidCOM DBA object
     * @param string $class Class to check the instance against
     * @return boolean
     */
    public function is_a($object, $class) : bool
    {
        if (is_a($object, $class)) {
            // Direct match
            return true;
        }

        if (   isset($object->__object)
            && is_object($object->__object)
            && $object->__object instanceof $class) {
            // Decorator whose MgdSchema object matches
            return true;
        }

        if (   isset($object->__mgdschema_class_name__)
            && $object->__mgdschema_class_name__ == $class) {
            // Decorator without object instantiated, check class match
            return true;
        }

        return false;
    }

    /**
     * Returns the parent object. Tries to utilize the Memcache
     * data, loading the actual information only if it is not cached.
     *
     * @param midcom_core_dbaobject $object The DBA object we're working on
     * @return midcom_core_dbaobject|null Parent if found, otherwise null
     * @see get_parent_data()
     * @todo rethink this, IMO we should trust midgard core's get_parent and then just do the object conversion if necessary since this can return stale objects and other nastiness
     */
    public function get_parent(midcom_core_dbaobject $object)
    {
        $parent_data = $this->_get_parent_guid_cached($object->guid, $object);

        $parent_guid = current($parent_data);
        if (   empty($parent_guid)
            || $parent_guid === $object->guid) {
            return null;
        }
        $classname = key($parent_data);

        if (empty($classname)) {
            //This must be a GUID link (or wrongly configured schema)
            try {
                $parent = $this->get_object_by_guid($parent_guid);
                $parent_data = [
                    $parent->__midcom_class_name__ => $parent_guid
                ];
            } catch (midcom_error $e) {
                $parent_data = [
                    '' => null
                ];
                $parent = null;
            }
            // Cache the classname so that we can avoid get_object_by_guid calls the next time
            midcom::get()->cache->memcache->update_parent_data($object->guid, $parent_data);

            return $parent;
        }

        try {
            return $this->get_cached($classname, $parent_guid);
        } catch (midcom_error $e) {
            return null;
        }
    }

    /**
     * Determines the parent GUID for an existing GUID according to the MidCOM content tree rules.
     *
     * It tries to look up the GUID in the memory cache, only if this fails, the regular
     * content getters are invoked.
     *
     * @param mixed $object Either a MidCOM DBA object instance, or a GUID string.
     * @param string $class class name of object if known (so we can use get_parent_guid_uncached_static and avoid instantiating full object)
     * @return array The parent GUID and class (value might be null, if this is a top level object).
     */
    public function get_parent_data($object, $class = null) : array
    {
        if (is_object($object)) {
            $object_guid = null;
            if (!empty($object->guid)) {
                $object_guid = $object->guid;
            } elseif (isset($object->__guid)) {
                $object_guid = $object->__guid;
            }

            $the_object = $object;
        } else {
            $object_guid = $object;
            $the_object = null;
        }
        return $this->_get_parent_guid_cached($object_guid, $the_object, $class);
    }

    private function _get_parent_guid_cached($object_guid, $the_object, $class = null) : array
    {
        static $cached_parent_data = [];

        $parent_data = false;
        if (mgd_is_guid($object_guid)) {
            if (array_key_exists($object_guid, $cached_parent_data)) {
                // We already got this either via query or memcache
                return $cached_parent_data[$object_guid];
            }

            $parent_data = midcom::get()->cache->memcache->lookup_parent_data($object_guid);
        } elseif ($the_object === null) {
            throw new midcom_error('Tried to resolve an invalid GUID without an object being present. This cannot be done.');
        }

        if (!is_array($parent_data)) {
            // No cache hit, retrieve guid and update the cache
            if ($class) {
                // Class defined, we can use the static method for fetching parent and avoiding full object instantiate
                $parent_data = $this->_get_parent_guid_uncached_static($object_guid, $class);
            } else {
                // class not defined, retrieve the full object by guid
                if ($the_object === null) {
                    try {
                        $the_object = $this->get_object_by_guid($object_guid);
                    } catch (midcom_error $e) {
                        return ['' => null];
                    }
                }

                $parent_data = $this->_get_parent_guid_uncached($the_object);
            }

            $parent_guid = current($parent_data);
            $classname = key($parent_data);
            $parent_data = [];
            if (!empty($classname)) {
                $classname = midcom::get()->dbclassloader->get_midcom_class_name_for_mgdschema_object($classname);
            }
            if (!mgd_is_guid($parent_guid)) {
                $parent_guid = null;
            }
            $parent_data[$classname] = $parent_guid;

            if (mgd_is_guid($object_guid)) {
                midcom::get()->cache->memcache->update_parent_data($object_guid, $parent_data);
            }
        }

        // Remember this so we don't need to get it again
        $cached_parent_data[$object_guid] = $parent_data;

        return $parent_data;
    }

    private function _get_parent_guid_uncached(midcom_core_dbaobject $object) : array
    {
        if (method_exists($object, 'get_parent_guid_uncached')) {
            return ['' => $object->get_parent_guid_uncached()];
        }

        $candidates = $this->_get_parent_candidates($object->__mgdschema_class_name__);

        foreach ($candidates as $data) {
            $parent_guid = $this->_load_guid($data['target_class'], $data['target_property'], $object->{$data['source_property']});
            if (null !== $parent_guid) {
                return [$data['target_class'] => $parent_guid];
            }
        }
        return ['' => null];
    }

    /**
     * Get the GUID of the object's parent. This is done by reading up or parent
     * property values, which will give us the parent's ID
     */
    private function _get_parent_guid_uncached_static($object_guid, $class_name) : array
    {
        if (method_exists($class_name, 'get_parent_guid_uncached_static')) {
            return ['', $class_name::get_parent_guid_uncached_static($object_guid)];
        }

        $class_name = midcom::get()->dbclassloader->get_mgdschema_class_name_for_midcom_class($class_name);
        $candidates = $this->_get_parent_candidates($class_name);

        foreach ($candidates as $data) {
            $mc = new midgard_collector($class_name, 'guid', $object_guid);
            $mc->set_key_property($data['source_property']);
            $mc->execute();
            $link_values = $mc->list_keys();

            if (empty($link_values)) {
                continue;
            }
            $link_value = key($link_values);
            $parent_guid = $this->_load_guid($data['target_class'], $data['target_property'], $link_value);
            if (null !== $parent_guid) {
                return [$data['target_class'] => $parent_guid];
            }
        }
        return ['' => null];
    }

    private function _load_guid($target_class, $target_property, $link_value)
    {
        if (empty($link_value)) {
            return null;
        }
        if (!array_key_exists($target_class, $this->_parent_mapping)) {
            $this->_parent_mapping[$target_class] = [];
        }
        if (array_key_exists($link_value, $this->_parent_mapping[$target_class])) {
            return $this->_parent_mapping[$target_class][$link_value];
        }
        $this->_parent_mapping[$target_class][$link_value] = null;

        $mc2 = new midgard_collector($target_class, $target_property, $link_value);
        $mc2->set_key_property('guid');
        $mc2->execute();
        $guids = $mc2->list_keys();
        if (!empty($guids)) {
            $this->_parent_mapping[$target_class][$link_value] = key($guids);
        }
        return $this->_parent_mapping[$target_class][$link_value];
    }

    private function _get_parent_candidates($classname) : array
    {
        if (!isset($this->_parent_candidates[$classname])) {
            $this->_parent_candidates[$classname] = [];
            $reflector = new midgard_reflection_property($classname);
            $up_property = midgard_object_class::get_property_up($classname);
            $parent_property = midgard_object_class::get_property_parent($classname);

            if ($up_property) {
                $this->_parent_candidates[$classname][] = [
                    'source_property' => $up_property,
                    'target_property' => $reflector->get_link_target($up_property),
                    'target_class' => $reflector->get_link_name($up_property),
                ];
            }

            if (   $parent_property
                && $reflector->get_link_target($parent_property)) {
                $target_class = $reflector->get_link_name($parent_property);
                if ($target_class == 'midgard_person') {
                    $person_class = midcom::get()->config->get('person_class');
                    if ($person_class != 'midgard_person') {
                        $target_class = $person_class;
                    }
                }
                $this->_parent_candidates[$classname][] = [
                    'source_property' => $parent_property,
                    'target_property' => $reflector->get_link_target($parent_property),
                    'target_class' => $target_class,
                ];
            }
            // FIXME: Handle GUID linking
        }
        return $this->_parent_candidates[$classname];
    }
}
