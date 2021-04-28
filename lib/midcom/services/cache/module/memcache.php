<?php
/**
 * @package midcom.services
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use Doctrine\Common\Cache\CacheProvider;

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
     * @var array
     */
    private $_data_groups = [];

    public static function prepare_memcached(array $config) : ?Memcached
    {
        $host = $config['host'] ?? 'localhost';
        $port = $config['port'] ?? 11211;
        $memcached = new Memcached;
        if (!$memcached->addServer($host, $port)) {
            return null;
        }

        return $memcached;
    }

    public function __construct(midcom_config $config, CacheProvider $backend)
    {
        parent::__construct($backend);
        $this->_data_groups = $config->get_array('cache_module_memcache_data_groups');
    }

    /**
     * {@inheritDoc}
     */
    public function invalidate(string $guid, $object = null)
    {
        foreach ($this->_data_groups as $group) {
            if ($group == 'ACL') {
                $this->backend->delete("{$group}-SELF::{$guid}");
                $this->backend->delete("{$group}-CONTENT::{$guid}");
            } else {
                $this->backend->delete("{$group}-{$guid}");
            }
        }
    }

    /**
     * Looks up a value in the cache and returns it. Not existent
     * keys are caught in this call as well
     *
     * @return mixed The cached value on success, false on failure.
     */
    public function get(string $data_group, string $key)
    {
        return $this->backend->fetch("{$data_group}-{$key}");
    }

    /**
     * Sets a given key in the cache. If the data group is unknown, a Warning-Level error
     * is logged and putting is denied.
     */
    public function put(string $data_group, string $key, $data, int $timeout = 0)
    {
        if (!in_array($data_group, $this->_data_groups)) {
            debug_add("Tried to add data to the unknown data group {$data_group}, cannot do that.", MIDCOM_LOG_WARN);
            debug_print_r('Known data groups:', $this->_data_groups);
            debug_print_function_stack('We were called from here:');
            return;
        }

        $this->backend->save("{$data_group}-{$key}", $data, $timeout);
    }

    /**
     * This is a little helper that tries to look up a GUID in the memory
     * cache's PARENT data group. If it is not found, false is returned.
     * If the object has no parent, the array value is null
     *
     * @return array|false The classname => GUID pair or false when nothing is in cache
     */
    public function lookup_parent_data(string $guid)
    {
        return $this->get('PARENT', $guid);
    }

    /**
     * This is a little helper that saves a parent GUID and class in the memory
     * cache's PARENT data group.
     */
    public function update_parent_data(string $object_guid, array $parent_data)
    {
        $this->put('PARENT', $object_guid, $parent_data);
    }
}
