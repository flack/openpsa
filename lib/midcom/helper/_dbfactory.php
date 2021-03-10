<?php
/**
 * @package midcom.helper
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use midgard\portable\api\error\exception as mgd_exception;
use midgard\portable\api\mgdobject;
use midgard\portable\storage\connection;

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
     */
    public function get_object_by_guid(string $guid) : midcom_core_dbaobject
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
     */
    public function &get_cached(string $classname, $src) : midcom_core_dbaobject
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
     * @param mixed $value Value match for the collector instance
     * @see midcom_core_collector
     */
    public function new_collector(string $classname, ?string $domain, $value) : midcom_core_collector
    {
        return new midcom_core_collector($classname, $domain, $value);
    }

    /**
     * This function will determine the correct type of midgard_query_builder that
     * has to be created. It will also call the _on_prepare_new_query_builder event handler.
     */
    public function new_query_builder(string $classname) : midcom_core_querybuilder
    {
        return new midcom_core_querybuilder($classname);
    }

    /**
     * Convert MgdSchema object into a MidCOM DBA object.
     */
    public function convert_midgard_to_midcom(mgdobject $object) : midcom_core_dbaobject
    {
        $classname = midcom::get()->dbclassloader->get_midcom_class_name_for_mgdschema_object($object);
        return new $classname($object);
    }

    /**
     * This is a helper for emulating is_a() functionality with MidCOM DBA objects that are decorators.
     * This method can be used to check whether an object is of a MidCOM DBA or MgdSchema type
     *
     * @param mixed $object MgdSchema or MidCOM DBA object
     * @param string $class Class to check the instance against
     */
    public function is_a(object $object, string $class, bool $allow_string = false) : bool
    {
        if (is_a($object, $class, $allow_string)) {
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
     * @see get_parent_data()
     * @todo rethink this, IMO we should trust midgard core's get_parent and then just do the object conversion if necessary since this can return stale objects and other nastiness
     */
    public function get_parent(midcom_core_dbaobject $object) : ?midcom_core_dbaobject
    {
        [$classname, $parent_guid] = $this->get_parent_data_cached($object->guid, function() use ($object) {
            return $this->get_parent_data_uncached($object);
        });

        if (   empty($parent_guid)
            || $parent_guid === $object->guid) {
            return null;
        }

        if (empty($classname)) {
            //This must be a GUID link (or wrongly configured schema)
            try {
                $parent = $this->get_object_by_guid($parent_guid);
                $parent_data = [$parent->__midcom_class_name__, $parent_guid];
            } catch (midcom_error $e) {
                $parent_data = ['', null];
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
     * @param string $guid A GUID string.
     * @param string $class class name of object (so we can use get_parent_guid_uncached_static and avoid instantiating full object)
     * @return array The parent GUID and class (value might be null, if this is a top level object).
     */
    public function get_parent_data(string $guid, string $class) : array
    {
        if (!mgd_is_guid($guid)) {
            throw new midcom_error('Tried to resolve an invalid GUID.');
        }
        return $this->get_parent_data_cached($guid, function() use ($guid, $class) {
            return $this->get_parent_data_uncached_static($guid, $class);
        });
    }

    private function get_parent_data_cached(string $guid, callable $callback) : array
    {
        static $cached_parent_data = [];

        if (mgd_is_guid($guid)) {
            if (array_key_exists($guid, $cached_parent_data)) {
                // We already got this either via query or memcache
                return $cached_parent_data[$guid];
            }
            $parent_data = midcom::get()->cache->memcache->lookup_parent_data($guid);
        }

        if (empty($parent_data)) {
            // No cache hit, retrieve guid and update the cache
            [$classname, $parent_guid] = $callback();

            if (!empty($classname)) {
                $classname = midcom::get()->dbclassloader->get_midcom_class_name_for_mgdschema_object($classname);
            }
            if (!mgd_is_guid($parent_guid)) {
                $parent_guid = null;
            }
            $parent_data = [$classname, $parent_guid];

            if (mgd_is_guid($guid)) {
                midcom::get()->cache->memcache->update_parent_data($guid, $parent_data);
            }
        }

        // Remember this so we don't need to get it again
        $cached_parent_data[$guid] = $parent_data;

        return $parent_data;
    }

    private function get_parent_data_uncached(midcom_core_dbaobject $object) : array
    {
        if (method_exists($object, 'get_parent_guid_uncached')) {
            return ['', $object->get_parent_guid_uncached()];
        }

        $candidates = $this->_get_parent_candidates($object->__mgdschema_class_name__);
        foreach ($candidates as $data) {
            $parent_guid = $this->_load_guid($data['target_class'], $data['target_property'], $object->{$data['source_property']});
            if (null !== $parent_guid) {
                return [$data['target_class'], $parent_guid];
            }
        }
        return ['', null];
    }

    /**
     * Get the GUID of the object's parent. This is done by reading up or parent
     * property values, which will give us the parent's ID
     */
    private function get_parent_data_uncached_static(string $object_guid, string $class_name) : array
    {
        if (!$class_name) {
            $class_name = connection::get_em()
                ->createQuery('SELECT r.typename from midgard:midgard_repligard r WHERE r.guid = ?1')
                ->setParameter(1, $object_guid)
                ->getSingleScalarResult();
            $class_name = midcom::get()->dbclassloader->get_midcom_class_name_for_mgdschema_object($class_name);
        }

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

            if (!empty($link_values)) {
                $parent_guid = $this->_load_guid($data['target_class'], $data['target_property'], key($link_values));
                if (null !== $parent_guid) {
                    return [$data['target_class'], $parent_guid];
                }
            }
        }
        return ['', null];
    }

    private function _load_guid(string $target_class, string $target_property, $link_value) : ?string
    {
        if (empty($link_value)) {
            return null;
        }
        if (!array_key_exists($target_class, $this->_parent_mapping)) {
            $this->_parent_mapping[$target_class] = [];
        }
        if (!array_key_exists($link_value, $this->_parent_mapping[$target_class])) {
            $mc2 = new midgard_collector($target_class, $target_property, $link_value);
            $mc2->set_key_property('guid');
            $mc2->execute();
            $this->_parent_mapping[$target_class][$link_value] = key($mc2->list_keys());
        }

        return $this->_parent_mapping[$target_class][$link_value];
    }

    private function _get_parent_candidates(string $classname) : array
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
