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
 * which tries to auto-detect a sensible default. If it is set to the name of a caching module
 * it will actually start caching. Otherwise it will silently ignore
 * put requests, and reports all keys as not existent.
 *
 * Number Three, as at least memcache's contains() check isn't working on some machines, key
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
    /**
     * List of known data groups. See the class introduction for details.
     *
     * @var Array
     */
    private $_data_groups = [];

    /**
     * The cache backend instance to use.
     *
     * @var Doctrine\Common\Cache\CacheProvider
     */
    private $_cache;

    /**
     * Initialization event handler.
     *
     * It will load the cache backend.
     *
     * Initializes the backend configuration.
     */
    public function _on_initialize()
    {
        if ($driver = midcom::get()->config->get('cache_module_memcache_backend')) {
            $this->_data_groups = midcom::get()->config->get('cache_module_memcache_data_groups');
            $config = midcom::get()->config->get('cache_module_memcache_backend_config');
            $config['driver'] = $driver;
            $this->_cache = $this->_create_backend('module_memcache', $config);
        }
    }

    public function is_operational() : bool
    {
        return !empty($this->_cache->memcache_operational);
    }

    /**
     * {@inheritDoc}
     */
    public function invalidate($guid, $object = null)
    {
        if ($this->_cache !== null) {
            foreach ($this->_data_groups as $group) {
                if ($group == 'ACL') {
                    $this->_cache->delete("{$group}-SELF::{$guid}");
                    $this->_cache->delete("{$group}-CONTENT::{$guid}");
                } else {
                    $this->_cache->delete("{$group}-{$guid}");
                }
            }
        }
    }

    /**
     * Looks up a value in the cache and returns it. Not existent
     * keys are caught in this call as well
     *
     * @param string $data_group The Data Group to look in.
     * @param string $key The key to look up.
     * @return mixed The cached value on success, false on failure.
     */
    public function get($data_group, $key)
    {
        if ($this->_cache === null) {
            return false;
        }

        return $this->_cache->fetch("{$data_group}-{$key}");
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
    public function put($data_group, $key, $data, $timeout = 0)
    {
        if ($this->_cache === null) {
            return;
        }

        if (!in_array($data_group, $this->_data_groups)) {
            debug_add("Tried to add data to the unknown data group {$data_group}, cannot do that.", MIDCOM_LOG_WARN);
            debug_print_r('Known data groups:', $this->_data_groups);
            debug_print_function_stack('We were called from here:');
            return;
        }

        $this->_cache->save("{$data_group}-{$key}", $data, $timeout);
    }

    /**
     * This is a little helper that tries to look up a GUID in the memory
     * cache's PARENT data group. If it is not found, false is returned.
     * If the object has no parent, the array value is null
     *
     * @param string $guid The guid of which a parent is searched.
     * @return array|false The classname => GUID pair or false when nothing is in cache
     */
    public function lookup_parent_data($guid)
    {
        return $this->get('PARENT', $guid);
    }

    /**
     * This is a little helper that saves a parent GUID and class in the memory
     * cache's PARENT data group.
     *
     * @param string $object_guid The guid of which a parent is saved.
     * @param array $parent_data The guid and classname of the parent which is saved.
     */
    public function update_parent_data($object_guid, array $parent_data)
    {
        $this->put('PARENT', $object_guid, $parent_data);
    }
}
