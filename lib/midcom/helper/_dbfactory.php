<?php
/**
 * @package midcom.helper
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * This class contains various factory methods to retrieve objects from the database.
 * The only instance of this class you should ever use is available through
 * $midcom->dbfactory.
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
    private $_parent_mapping = array();

    /**
     * Cache for possible parent configurations per mgdschema class
     *
     * @var array
     */
    private $_parent_candidates = array();

    /**
     * This is a replacement for the original midgard_object_class::get_object_by_guid method, which takes
     * the MidCOM DBA system into account.
     *
     * @see http://www.midgard-project.org/documentation/php_midgard_object_class/
     *
     * @param string $guid The object GUID.
     * @return object A MidCOM DBA object if the set GUID is known
     */
    function get_object_by_guid($guid)
    {
        try
        {
            $tmp = midgard_object_class::get_object_by_guid($guid);
        }
        catch (midgard_error_exception $e)
        {
            debug_add('Loading object by GUID ' . $guid . ' failed, reason: ' . $e->getMessage(), MIDCOM_LOG_INFO);

            throw new midcom_error_midgard($e, $guid);
        }
        $person_class =  midcom::get('config')->get('person_class');
        if (   get_class($tmp) == 'midgard_person'
            && $person_class != 'midgard_person')
        {
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
    function &get_cached($classname, $src)
    {
        static $cache = array();

        if (empty($src))
        {
            throw new midcom_error('invalid source identifier');
        }

        if (!isset($cache[$classname]))
        {
            $cache[$classname] = array();
        }

        if (isset($cache[$classname][$src]))
        {
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
    function new_collector($classname, $domain, $value)
    {
        $mc = new midcom_core_collector($classname, $domain, $value);
        $mc->initialize();
        return $mc;
    }

    /**
     * This function will determine the correct type of midgard_query_builder that
     * has to be created. It will also call the _on_prepare_new_query_builder event handler.
     *
     * @param string $classname The name of the class for which you want to create a query builder.
     * @return midcom_core_querybuilder The initialized instance of the query builder.
     * @see midcom_core_querybuilder
     */
    function new_query_builder($classname)
    {
        $qb = new midcom_core_querybuilder($classname);
        $qb->initialize();
        return $qb;
    }

    /**
     * Helper function, it takes an MgdSchema object and converts it into a MidCOM DBA object.
     *
     * If the conversion cannot be done for some reason, the function returns null and logs
     * an error.
     *
     * We also ensure that the corresponding component has been loaded.
     *
     * @param MidgardObject &$object MgdSchema Object
     * @return MidCOMDBAObject
     */
    function convert_midgard_to_midcom(&$object)
    {
        if (! is_object($object))
        {
            debug_print_r("Object dump:", $object);
            throw new midcom_error("Cannot cast the object to a MidCOM DBA type, it is not an object.");
        }

        if (midcom::get('dbclassloader')->is_mgdschema_object($object))
        {
            $classname = midcom::get('dbclassloader')->get_midcom_class_name_for_mgdschema_object($object);

            if (! midcom::get('dbclassloader')->load_mgdschema_class_handler($classname))
            {
                throw new midcom_error("Failed to load the handling component for {$classname}, cannot convert.");
            }

            if (!class_exists($classname))
            {
                throw new midcom_error("Got non-existing DBA class {$classname} for object of type " . get_class($object) . ", cannot convert.");
            }

            $result = new $classname($object);
        }
        else
        {
            debug_print_r("Object dump:", $object);
            throw new midcom_error("Cannot cast the object to a MidCOM DBA type, it is not a regular MgdSchema object, we got this type:");
        }
        return $result;
    }

    /**
     * Helper function, it takes a MidCOM DBA object and converts it into an MgdSchema object.
     *
     * If the conversion cannot be done for some reason, the function returns null and logs
     * an error.
     *
     * @param MidCOMDBAObject &$object MidCOM DBA Object
     * @return MidgardObject MgdSchema Object
     */
    function convert_midcom_to_midgard(&$object)
    {
        if (! is_object($object))
        {
            debug_print_type("Cannot cast the object to an MgdSchema type, it is not an object, we got this type:",
                $object, MIDCOM_LOG_ERROR);
            debug_print_r("Object dump:", $object);
            return null;
        }

        if (!midcom::get('dbclassloader')->is_midcom_db_object($object))
        {
            if (midcom::get('dbclassloader')->is_mgdschema_object($object))
            {
                // Return it directly, it is already in the format we want
                return $object;
            }
            debug_print_type("Cannot cast the object to an MgdSchema type, it is not a MidCOM DBA object, we got this type:",
                $object, MIDCOM_LOG_ERROR);
            debug_print_r("Object dump:", $object);
            return null;
        }

        if (   !isset($object->__object)
            || !is_object($object->__object))
        {
            debug_print_type("Cannot cast the object to an MgdSchema type, as it doesn't contain it, we got this type:",
                $object, MIDCOM_LOG_ERROR);
            debug_print_r("Object dump:", $object);
            return null;
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
    public function is_a($object, $class)
    {
        if (is_a($object, $class))
        {
            // Direct match
            return true;
        }

        if (   isset($object->__object)
            && is_object($object->__object)
            && is_a($object->__object, $class))
        {
            // Decorator whose MgdSchema object matches
            return true;
        }

        if (   isset($object->__mgdschema_class_name__)
            && $object->__mgdschema_class_name__ == $class)
        {
            // Decorator without object instantiated, check class match
            return true;
        }

        return false;
    }

    /**
     * This is a helper for emulating property_exists() functionality with MidCOM DBA objects that are decorators.
     * This method can be used to check whether the original MgdSchema class has a given property.
     *
     * @param mixed $object MgdSchema or MidCOM DBA object or class
     * @param string $property Property to check for
     * @return boolean
     */
    public function property_exists($object, $property)
    {
        if (is_object($object))
        {
            // We're dealing with either MgdSchema or MidCOM DBA object
            if (   isset($object->__object)
                && is_object($object->__object))
            {
                // MidCOM DBA decorator
                return property_exists($object->__object, $property);
            }

            return property_exists($object, $property);
        }

        if (   !is_string($object)
            || !class_exists($object))
        {
            // TODO: Raise exception?
            debug_add("\$object is not string or class_exists() returned false", MIDCOM_LOG_WARN);
            debug_print_r('$object', $object);
            return false;
        }

        // Workaround for http://trac.midgard-project.org/ticket/942
        $instance = new $object();
        return $this->property_exists($instance, $property);
    }

    /**
     * Returns the the parent object. Tries to utilize the Memcache
     * data, loading the actual information only if it is not cached.
     *
     * @param midcom_core_dbaobject $object The DBA object we're working on
     * @return midcom_core_dbaobject|null Parent if found, otherwise null
     * @see get_parent_data()
     * @todo rethink this, IMO we should trust midgard core's get_parent and then just do the object conversion if neccessary since this can return stale objects and other nastiness
     */
    public function get_parent(midcom_core_dbaobject $object)
    {
        $parent_data = $this->_get_parent_guid_cached($object->guid, $object);

        $parent_guid = current($parent_data);
        if (   empty($parent_guid)
            || $parent_guid === $object->guid)
        {
            return null;
        }
        $classname = key($parent_data);

        if (empty($classname))
        {
            //This must be a GUID link (or wrongly configured schema)
            try
            {
                $parent = $this->get_object_by_guid($parent_guid);
                $parent_data = array
                (
                    $parent->__midcom_class_name__ => $parent_guid
                );
            }
            catch (midcom_error $e)
            {
                $parent_data = array
                (
                    '' => null
                );
                $parent = null;
            }
            // Cache the classname so that we can avoid get_object_by_guid calls the next time
            midcom::get('cache')->memcache->update_parent_data($object->guid, $parent_data);

            return $parent;
        }

        try
        {
            return $this->get_cached($classname, $parent_guid);
        }
        catch (midcom_error $e)
        {
            return null;
        }
    }

    /**
     * This is a helper function which determines the parent GUID for an existing
     * GUID according to the MidCOM content tree rules.
     *
     * It tries to look up the GUID in the memory cache, only if this fails, the regular
     * content getters are invoked.
     *
     * @param mixed $object Either a MidCOM DBA object instance, or a GUID string.
     * @param string $class class name of object if known (so we can use get_parent_guid_uncached_static and avoid instantiating full object)
     * @return array The parent GUID and class (value might be null, if this is a top level object).
     */
    function get_parent_data($object, $class = null)
    {
        if (is_object($object))
        {
            $object_guid = null;
            if (!empty($object->guid))
            {
                $object_guid = $object->guid;
            }
            else if (isset($object->__guid))
            {
                $object_guid = $object->__guid;
            }

            $the_object = $object;
        }
        else
        {
            $object_guid = $object;
            $the_object = null;
        }
        return $this->_get_parent_guid_cached($object_guid, $the_object, $class);
    }

    private function _get_parent_guid_cached($object_guid, $the_object, $class = null)
    {
        static $cached_parent_data = array();

        $parent_data = false;
        if (mgd_is_guid($object_guid))
        {
            if (array_key_exists($object_guid, $cached_parent_data))
            {
                // We already got this either via query or memcache
                return $cached_parent_data[$object_guid];
            }

            $parent_data = midcom::get('cache')->memcache->lookup_parent_data($object_guid);
        }
        else if ($the_object === null)
        {
            throw new midcom_error('Tried to resolve an invalid GUID without an object being present. This cannot be done.');
        }

        if (!is_array($parent_data))
        {
            // No cache hit, retrieve guid and update the cache
            if ($class)
            {
                // Class defined, we can use the static method for fetching parent and avoiding full object instantiate
                $parent_data = $this->_get_parent_guid_uncached_static($object_guid, $class);
            }
            else
            {
                // class not defined, retrieve the full object by guid
                if ($the_object === null)
                {
                    try
                    {
                        $the_object = $this->get_object_by_guid($object_guid);
                    }
                    catch (midcom_error $e)
                    {
                        return array('' => null);
                    }
                }

                $parent_data = $this->_get_parent_guid_uncached($the_object);
            }

            $parent_guid = current($parent_data);
            $classname = key($parent_data);
            $parent_data = array();
            if (!empty($classname))
            {
                $classname = midcom::get('dbclassloader')->get_midcom_class_name_for_mgdschema_object($classname);
            }
            if (!mgd_is_guid($parent_guid))
            {
                $parent_guid = null;
            }
            $parent_data[$classname] = $parent_guid;

            if (mgd_is_guid($object_guid))
            {
                midcom::get('cache')->memcache->update_parent_data($object_guid, $parent_data);
            }
        }

        // Remember this so we don't need to get it again
        $cached_parent_data[$object_guid] = $parent_data;

        return $parent_data;
    }

    private function _get_parent_guid_uncached(midcom_core_dbaobject $object)
    {
        if (method_exists($object, 'get_parent_guid_uncached'))
        {
            return array('' => $object->get_parent_guid_uncached());
        }

        $candidates = $this->_get_parent_candidates($object->__mgdschema_class_name__);

        foreach ($candidates as $data)
        {
            $parent_guid = $this->_load_guid($data['target_class'], $data['target_property'], $object->{$data['source_property']});
            if (null !== $parent_guid)
            {
                return array($data['target_class'] => $parent_guid);
            }
        }
        return array('' => null);
    }

    /**
     * Get the GUID of the object's parent. This is done by reading up or parent
     * property values, which will give us the parent's ID
     */
    private function _get_parent_guid_uncached_static($object_guid, $class_name)
    {
        if (method_exists($class_name, 'get_parent_guid_uncached_static'))
        {
            return array('', call_user_func(array($class_name, 'get_parent_guid_uncached_static'), $object_guid, $class_name));
        }

        $class_name = midcom::get('dbclassloader')->get_mgdschema_class_name_for_midcom_class($class_name);
        $candidates = $this->_get_parent_candidates($class_name);

        foreach ($candidates as $data)
        {
            $mc = new midgard_collector($class_name, 'guid', $object_guid);
            $mc->set_key_property($data['source_property']);
            $mc->execute();
            $link_values = $mc->list_keys();

            if (empty($link_values))
            {
                continue;
            }
            $link_value = key($link_values);
            unset($mc, $link_values);
            $parent_guid = $this->_load_guid($data['target_class'], $data['target_property'], $link_value);
            if (null !== $parent_guid)
            {
                return array($data['target_class'] => $parent_guid);
            }
        }
        return array('' => null);
    }

    private function _load_guid($target_class, $target_property, $link_value)
    {
        if (empty($link_value))
        {
            return null;
        }
        if (!array_key_exists($target_class, $this->_parent_mapping))
        {
            $this->_parent_mapping[$target_class] = array();
        }
        if (array_key_exists($link_value, $this->_parent_mapping[$target_class]))
        {
            return $this->_parent_mapping[$target_class][$link_value];
        }
        $this->_parent_mapping[$target_class][$link_value] = null;

        $mc2 = new midgard_collector($target_class, $target_property, $link_value);
        $mc2->set_key_property('guid');
        $mc2->execute();
        $guids = $mc2->list_keys();
        if (!empty($guids))
        {
            $this->_parent_mapping[$target_class][$link_value] = key($guids);
        }
        return $this->_parent_mapping[$target_class][$link_value];
    }

    private function _get_parent_candidates($classname)
    {
        if (!isset($this->_parent_candidates[$classname]))
        {
            $this->_parent_candidates[$classname] = array();
            $reflector = new midgard_reflection_property($classname);
            $up_property = midgard_object_class::get_property_up($classname);
            $parent_property = midgard_object_class::get_property_parent($classname);

            if ($up_property)
            {
                $this->_parent_candidates[$classname][] = array
                (
                    'source_property' => $up_property,
                    'target_property' => $reflector->get_link_target($up_property),
                    'target_class' => $classname,
                );
            }

            if (   $parent_property
                && $reflector->get_link_target($parent_property))
            {
                $target_class = $reflector->get_link_name($parent_property);
                if ($target_class == 'midgard_person')
                {
                    $person_class =  midcom::get('config')->get('person_class');
                    if ($person_class != 'midgard_person')
                    {
                        $target_class = $person_class;
                    }
                }
                $this->_parent_candidates[$classname][] = array
                (
                    'source_property' => $parent_property,
                    'target_property' => $reflector->get_link_target($parent_property),
                    'target_class' => $target_class,
                );
            }
            // FIXME: Handle GUID linking
        }
        return $this->_parent_candidates[$classname];
    }

    /**
     * Import object unserialized with midgard_replicator::unserialize()
     *
     * This method does ACL checks and triggers watchers etc.
     *
     * @param object $unserialized_object object gotten from midgard_replicator::unserialize()
     * @param boolean $use_force set use of force for the midcom_helper_replicator_import_object() call
     * @return boolean indicating success/failure
     * @todo refactor to smaller methods
     * @todo Add some magic to prevent importing of replication loops (see documentation/TODO for details about the potential problem)
     * @todo Verify support for the special cases of privilege
     * @todo Make sure older version is not imported over newer one (maybe configurable override ?)
     */
    function import(&$unserialized_object, $use_force = false)
    {
        if (is_a($unserialized_object, 'midgard_blob'))
        {
            debug_add("You must use import_blob method to import BLOBs", MIDCOM_LOG_ERROR);
            midcom_connection::set_error(MGD_ERR_ERROR);
            return false;
        }
        // We need this helper (workaround Zend bug)
        if (!function_exists('midcom_helper_replicator_import_object'))
        {
            midcom::get('componentloader')->load('midcom.helper.replicator');
        }

        if (!midcom::get('dbclassloader')->is_mgdschema_object($unserialized_object))
        {
            debug_add("Unserialized object " . get_class($unserialized_object) . " is not recognized as supported MgdSchema class.", MIDCOM_LOG_ERROR);
            midcom_connection::set_error(MGD_ERR_ERROR);
            return false;
        }

        // Load the required component for DBA class
        $midcom_dba_classname = midcom::get('dbclassloader')->get_midcom_class_name_for_mgdschema_object($unserialized_object);
        if (! midcom::get('dbclassloader')->load_mgdschema_class_handler($midcom_dba_classname))
        {
            debug_add("Failed to load the handling component for {$midcom_dba_classname}, cannot import.", MIDCOM_LOG_ERROR);
            midcom_connection::set_error(MGD_ERR_ERROR);
            return false;
        }

        // Get an object for ACL checks, use existing one if possible
        $acl_object = new $midcom_dba_classname($unserialized_object->guid);
        if ($acl_object->id)
        {
            if (!midcom::get('dbfactory')->is_a($acl_object, get_class($unserialized_object)))
            {
                $acl_class = get_class($acl_object);
                $unserialized_class = get_class($unserialized_object);
                debug_add("The local object we got is not of compatible type ({$acl_class} vs {$unserialized_class}), this means duplicate GUID", MIDCOM_LOG_ERROR);
                midcom_connection::set_error(MGD_ERR_DUPLICATE);
                return false;
            }
            // Got an existing object
            $acl_object_in_db = true;
            $actual_object_in_db = true;
            /* PONDER: should we copy values from unserialized here as well ?? likely we should (think moving article)
            midcom_baseclasses_core_dbobject::cast_object($acl_object, $unserialized_object)
            */
        }
        else
        {
            $error_code = midcom_connection::get_error(); // switch is a loop, get the value only once this way
            switch ($error_code)
            {
                case MGD_ERR_ACCESS_DENIED:
                    $actual_object_in_db = true;
                    debug_add("Could not instantiate ACL object due to ACCESS_DENIED error, this means we can abort early", MIDCOM_LOG_ERROR);
                    return false;

                case MGD_ERR_OBJECT_DELETED:
                case MGD_ERR_OBJECT_PURGED:
                    $actual_object_in_db = true;
                    break;
                default:
                    $actual_object_in_db = false;
                    break;
            }
            // Copy-construct
            $acl_object_in_db = false;
            $acl_object = new $midcom_dba_classname();
            if (!midcom_baseclasses_core_dbobject::cast_object($acl_object, $unserialized_object))
            {
                debug_add('Failed to cast MidCOM DBA object for ACL checks from $unserialized_object', MIDCOM_LOG_ERROR);
                debug_print_r('$unserialized_object: ', $unserialized_object);
                return false;
            }
        }

        // Magic to check for various corner cases to determine the action to take later on
        switch(true)
        {
            case ($unserialized_object->action == 'purged'):
                // Purges not supported yet
                debug_add("Purges not supported yet (they require extra special love)", MIDCOM_LOG_ERROR);
                midcom_connection::set_error(MGD_ERR_OBJECT_PURGED);
                return false;

            // action is created but object is already in db, cast to update
            case (   $unserialized_object->action == 'created'
                  && $actual_object_in_db):
                $handle_action = 'updated';
                break;
            case (   $unserialized_object->action == 'updated'
                  && !$actual_object_in_db):
                $handle_action = 'created';
                break;
            default:
                if ($unserialized_object->action)
                {
                    $handle_action = $unserialized_object->action;
                }
                else
                {
                    $handle_action = 'updated';
                }
                break;
        }

        if (!$acl_object->_on_importing())
        {
            debug_add("The _on_importing event handler returned false.", MIDCOM_LOG_ERROR);
            // set errno if not set
            if (midcom_connection::get_error() === MGD_ERR_OK)
            {
                midcom_connection::set_error(MGD_ERR_ERROR);
            }
            return false;
        }

        switch ($handle_action)
        {
            case 'deleted':
                if (!$actual_object_in_db)
                {
                    midcom_helper_replicator_import_object($unserialized_object, $use_force);
                    break;
                }
                if (!midcom_baseclasses_core_dbobject::delete_pre_checks($acl_object))
                {
                    debug_add('delete pre-flight check returned false', MIDCOM_LOG_ERROR);
                    return false;
                }
                // Actual import
                if (!midcom_helper_replicator_import_object($unserialized_object, $use_force))
                {
                    /**
                     * BEGIN workaround
                     * For http://trac.midgard-project.org/ticket/200
                     */
                    if (midcom_connection::get_error() === MGD_ERR_OBJECT_IMPORTED)
                    {
                        debug_add('Trying to workaround problem importing deleted action, calling $acl_object->delete()', MIDCOM_LOG_WARN);
                        if ($acl_object->delete())
                        {
                            debug_add('$acl_object->delete() succeeded, returning true early', MIDCOM_LOG_INFO);
                            return true;
                        }
                        debug_add("\$acl_object->delete() failed for {$acl_object->guid}, errstr: " . midcom_connection::get_error_string(), MIDCOM_LOG_ERROR);
                        // reset errno()
                        midcom_connection::set_error(MGD_ERR_OBJECT_IMPORTED);
                    }
                    /** END workaround */
                    debug_add('midcom_helper_replicator_import_object returned false, errstr: ' . midcom_connection::get_error_string(), MIDCOM_LOG_ERROR);
                    return false;
                }
                midcom_baseclasses_core_dbobject::delete_post_ops($acl_object);
                break;
            case 'updated':
                if (!midcom_baseclasses_core_dbobject::update_pre_checks($acl_object))
                {
                    debug_add('update pre-flight check returned false', MIDCOM_LOG_ERROR);
                    return false;
                }
                // Actual import
                if (!midcom_helper_replicator_import_object($unserialized_object, $use_force))
                {
                    debug_add('midcom_helper_replicator_import_object returned false, errstr: ' . midcom_connection::get_error_string(), MIDCOM_LOG_ERROR);
                    return false;
                }
                // "refresh" acl_object
                if (!midcom_baseclasses_core_dbobject::cast_object($acl_object, $unserialized_object))
                {
                    // this shouldn't happen, but shouldn't be fatal either...
                }
                midcom_baseclasses_core_dbobject::update_post_ops($acl_object);
                break;
            case 'created':
                if (!midcom_baseclasses_core_dbobject::create_pre_checks($acl_object))
                {
                    debug_add('creation pre-flight check returned false', MIDCOM_LOG_ERROR);
                    return false;
                }
                // Actual import
                if (!midcom_helper_replicator_import_object($unserialized_object, $use_force))
                {
                    debug_add('midcom_helper_replicator_import_object returned false, errstr: ' . midcom_connection::get_error_string(), MIDCOM_LOG_ERROR);
                    return false;
                }
                // refresh object to avoid issues with _on_created requiring ID
                $acl_object_refresh = new $midcom_dba_classname($unserialized_object->guid);
                if (   is_object($acl_object_refresh)
                    && $acl_object_refresh->id)
                {
                    $acl_object = $acl_object_refresh;
                    midcom_baseclasses_core_dbobject::create_post_ops($acl_object);
                }
                else
                {
                    // refresh failed (it really shouldn't), what to do ??
                }
                break;
            default:
                debug_add("Do not know how to handle action '{$handle_action}'", MIDCOM_LOG_ERROR);
                midcom_connection::set_error(MGD_ERR_ERROR);
                return false;
                break;
        }

        $acl_object->_on_imported();
        midcom::get('componentloader')->trigger_watches(MIDCOM_OPERATION_DBA_IMPORT, $acl_object);
        return true;
    }

    /**
     * Import midgard_blob unserialized with midgard_replicator::unserialize()
     *
     * This method does ACL checks and triggers watchers etc.
     *
     * @param midgard_blob $unserialized_object midgard_blob gotten from midgard_replicator::unserialize()
     * @param string $xml XML the midgard_blob was unserialized from
     * @param boolean $use_force set use of force for the midcom_helper_replicator_import_from_xml() call
     * @return boolean indicating success/failure
     */
    function import_blob(&$unserialized_object, &$xml, $use_force = false)
    {
        if (!is_a($unserialized_object, 'midgard_blob'))
        {
            debug_add("You should use the *import* method to import normal objects, passing control there", MIDCOM_LOG_WARNING);
            return $this->import($unserialized_object, $use_force);
        }
        // We need this helper (workaround Zend bug)
        if (!function_exists('midcom_helper_replicator_import_object'))
        {
            midcom::get('componentloader')->load('midcom.helper.replicator');
        }

        try
        {
            $acl_object = midcom::get('dbfactory')->get_object_by_guid($unserialized_object->parentguid);
        }
        catch (midcom_error $e)
        {
            debug_add("Could not get parent object (GUID: {$unserialized_object->parentguid}), aborting", MIDCOM_LOG_ERROR);
            return false;
        }

        // midgard_blob has no action, update is the best check for allowing import of blob data
        if (!midcom_baseclasses_core_dbobject::update_pre_checks($acl_object))
        {
            $parent_class = get_class($acl_object);
            debug_add("parent ({$parent_class} {$acl_object->guid}) update pre-flight check returned false", MIDCOM_LOG_ERROR);
            return false;
        }
        // Actual import
        // NOTE: midgard_replicator::import_from_xml returns void, which evaluates to false, check midcom_connection::get_error instead
        midcom_helper_replicator_import_from_xml($xml, $use_force);
        if (midcom_connection::get_error() !== MGD_ERR_OK)
        {
            debug_add('midcom_helper_replicator_import_from_xml returned false, errstr: ' . midcom_connection::get_error_string(), MIDCOM_LOG_ERROR);
            return false;
        }
        // Trigger parent updated
        midcom_baseclasses_core_dbobject::update_post_ops($acl_object);
        // And also imported
        midcom::get('componentloader')->trigger_watches(MIDCOM_OPERATION_DBA_IMPORT, $acl_object);

        return true;
    }
}
?>
