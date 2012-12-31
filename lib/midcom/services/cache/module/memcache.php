<?php
/**
 * @package midcom.services
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * The Memory caching system is geared to hold needed information available quickly.
 * There are a number of limitations you have to deal with, when working with the
 * Memory Cache.
 *
 * Number One, you cannot put arbitrary keys into the cache. Since the memcached
 * php extension does not support key listings, you are bound to use MidCOM object
 * GUIDs as cache keys, whatever you do. To allow for different subsystems of the
 * Framework to share the cache, I have introduce "Data groups", which are suffixes
 * for the actual cache information. Thus, all keys in the cache follow a
 * "{$datagroup}-{$guid}" naming scheme. These groups need to be registered in the
 * MidCOM configuration key <i>cache_module_memcache_data_groups</i>.
 *
 * Number Two, it is entirely possible (as it is the default), that the memcache
 * is actually not available, as no memcache daemon has been found.  This is
 * controlled by the <i>cache_module_memcache_backend</i> configuration option,
 * which defaults to null. If it is set to the name of a caching module (normally
 * memcached) it will actually start caching. Otherwise it will silently ignore
 * put requests, and reports all keys as not existent.
 *
 * Number Three, as at least memcached does not provide key_exists check, key
 * values of false are forbidden, as they are used to check a keys existence
 * during the get cycle. You should also avoid null and 0 members, if possible,
 * they could naturally be error prone if you start forgetting about the typed
 * comparisons.
 *
 * <b>Special functionality</b>
 *
 * - Interface to the PARENT caching group, has a few simple shortcuts to the
 *   access the available information.
 *
 * @package midcom.services
 */
class midcom_services_cache_module_memcache extends midcom_services_cache_module
{
    /**#@+
     * Internal runtime state variable.
     */

    /**
     * The configuration to use to start up the backend drivers. Initialized during
     * startup from the MidCOM configuration key cache_module_nap_backend.
     *
     * @var Array
     */
    private $_backend = null;

    /**
     * List of known data groups. See the class introduction for details.
     *
     * @var Array
     */
    private $_data_groups = null;

    /**
     * The cache backend instance to use.
     *
     * @var midcom_services_cache_backend
     */
    private $_cache = null;

    /**#@-*/

    /**
     * Initialization event handler.
     *
     * It will load the cache backend.
     *
     * Initializes the backend configuration.
     */
    public function _on_initialize()
    {
        $this->_backend = $GLOBALS['midcom_config']['cache_module_memcache_backend'];

        if ($this->_backend)
        {
            $this->_data_groups = $GLOBALS['midcom_config']['cache_module_memcache_data_groups'];
            $config = $GLOBALS['midcom_config']['cache_module_memcache_backend_config'];
            $config['driver'] = $this->_backend;
            $this->_cache = $this->_create_backend('module_memcache', $config);
        }
    }

    /**
     * Invalidates the GUID for all known data groups.
     *
     * @param string $guid The GUID to invalidate.
     */
    function invalidate($guid)
    {
        if ($this->_cache !== null)
        {
            foreach ($this->_data_groups as $group)
            {
                if ($group == 'ACL')
                {
                    $this->_cache->_remove("{$group}-SELF::{$guid}");
                    $this->_cache->_remove("{$group}-CONTENT::{$guid}");
                }
                else
                {
                    $this->_cache->_remove("{$group}-{$guid}");
                }
            }
        }
    }

    /**
     * Looks up a value in the cache and returns it. Not existent
     * keys are caught in this call as well, so you do not need
     * to call exists first.
     *
     * @param string $data_group The Data Group to look in.
     * @param string $key The key to look up.
     * @return mixed The cached value on success, false on failure.
     */
    function get($data_group, $key)
    {
        if ($this->_cache === null)
        {
            return false;
        }

        return $this->_cache->get("{$data_group}-{$key}");
    }

    /**
     * Checks for the existence of a key in the cache.
     *
     * @param string $data_group The Data Group to look in.
     * @param string $key The key to look up.
     * @return boolean Indicating existence
     */
    function exists($data_group, $key)
    {
        if ($this->_cache === null)
        {
            return false;
        }

        return $this->_cache->exists("{$data_group}-{$key}");
    }

    /**
     * Sets a given key in the cache. If the data group is unknown, a Warning-Level error
     * is logged and putting is denied.
     *
     * @param string $data_group The Data Group to look in.
     * @param string $key The key to look up.
     * @param mixed $data The data to store.
     * @param int $timeout how long the data should live in the cache.
     */
    function put($data_group, $key, $data, $timeout = false)
    {
        if ($this->_cache === null)
        {
            return;
        }

        if (! in_array($data_group, $this->_data_groups))
        {
            debug_add("Tried to add data to the unknown data group {$data_group}, cannot do that.", MIDCOM_LOG_WARN);
            debug_print_r('Known data groups:', $this->_data_groups);
            debug_print_function_stack('We were called from here:');
            return;
        }

        if (false !== $timeout)
        {
            // if a timeout is specified, we have to pass to the driver directly, since
            // the memory cache in the baseclass would drop the timeout information
            // TODO: This needs to be solved in a more general way at some point
            $this->_cache->_put("{$data_group}-{$key}", $data, $timeout);
        }
        else
        {
            $this->_cache->put("{$data_group}-{$key}", $data);
        }
    }

    /**
     * This is a little helper that tries to look up a parent GUID in the memory
     * cache's PARENT data group. If it is not found, false is returned.
     * If the object has no parent (null was passed originally as parent object),
     * the method returns -1 to avoid storage problems.
     *
     * @param string $guid The guid of which a parent is searched.
     * @return string The parent GUID, -1 if there is no parent object or false on failure.
     */
    function lookup_parent_guid($guid)
    {
        return $this->get('PARENT', $guid);
    }

    /**
     * This is a little helper that tries to look up a parent GUID in the memory
     * cache's PARENT data group. If it is not found, false is returned.
     *
     * @param string $object_guid The guid of which a parent is saved.
     * @param string $parent_guid The guid of the parent which is saved.
     * @return string The parent GUID, -1 if there is no parent object or false on failure.
     */
    function update_parent_guid($object_guid, $parent_guid)
    {
        if ($parent_guid === null)
        {
            $parent_guid = -1;
        }
        $this->put('PARENT', $object_guid, $parent_guid);
    }
}
?>