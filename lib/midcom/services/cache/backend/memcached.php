<?php
/**
 * @package midcom.services
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Memcached caching backend.
 *
 * Requires the memcache PECL extension to work, uses persistent connections.
 *
 * <b>Configuration options:</b>
 *
 * - <i>string host</i> The host to connect to, defaults to localhost.
 * - <i>int port</i> The port to connect to, defaults to the default port 11211.
 *
 * <b>Important notes:</b>
 *
 * - This script does not synchronize multiple read/write accesses to the cache in the
 *   sense of a transaction.
 * - This subclass will override the automatic serialization setting you made, as the
 *   memcache extension does this automatically.
 * - Since this is about performance, (and memcached doesn't allow it in any other way),
 *   the get and exist calls are merged. Get will return false in case that the required
 *   key was not found. This effectively means that you cannot store "false" as a value.
 * - The class will automatically add the name of the cache instance to the cache keys.
 *
 * @package midcom.services
 * @see http://www.php.net/manual/en/ref.memcache.php
 */
class midcom_services_cache_backend_memcached extends midcom_services_cache_backend
{
    /**
     * The IP to connect to.
     *
     * @var string
     */
    private $_host = 'localhost';

    /**
     * The Port to connect to.
     *
     * @var int
     */
    private $_port = 11211;

    /**
     * The Memcache interface object.
     *
     * @var Memcache
     */
    private static $memcache = null;

    /**
     * Whether memcached is working
     */
    static $memcache_operational = true;

    /**
     * We use persistent connections, so we let midcom assume the read/write
     * connection is always open
     */
    public function __construct()
    {
        $this->_open_for_reading = true;
        $this->_open_for_writing = true;
    }

    /**
     * This handler completes the configuration.
     */
    public function _on_initialize()
    {
        if (array_key_exists('host', $this->_config))
        {
            $this->_host = $this->_config['host'];
        }
        if (array_key_exists('port', $this->_config))
        {
            $this->_port = $this->_config['port'];
        }

        // memcache does serialization automatically. no need for manual serialization
        $this->_auto_serialize = false;

        // Open the persistant connection.
        if (is_null(self::$memcache))
        {
            self::$memcache = new Memcache();
            try
            {
                self::$memcache_operational = @self::$memcache->pconnect($this->_host, $this->_port);
            }
            catch (Exception $e)
            {
                debug_add("memcache handler: Failed to connect to {$this->_host}:{$this->_port}. " . $e->getMessage() . ". Serving this request without cache.", MIDCOM_LOG_ERROR);
                self::$memcache_operational = false;
            }
        }
    }

    /**
     * This method is unused as we use persistent connections, letting memcached take care about synchronization.
     */
    function _open($write = false) {}

    /**
     * This method is unused as we use persistent connections, letting memcached take care about synchronization.
     */
    function _close() {}

    function _get($key)
    {
        if (!self::$memcache_operational)
        {
            return;
        }

        $key = "{$this->_name}-{$key}";
        return (@self::$memcache->get($key));
    }

    function _put($key, $data, $timeout = false)
    {
        if (!self::$memcache_operational)
        {
            return;
        }

        $key = "{$this->_name}-{$key}";
        if ($timeout !== false)
        {
            @self::$memcache->set($key, $data, 0, $timeout);
            return;
        }
        @self::$memcache->set($key, $data);
    }

    function _remove($key)
    {
        if (!self::$memcache_operational)
        {
            return;
        }

        $key = "{$this->_name}-{$key}";
        @self::$memcache->delete($key, 0);
    }

    function _remove_all()
    {
        if (!self::$memcache_operational)
        {
            return;
        }

        $stat = @self::$memcache->flush();
        debug_add("memcache->flush() returned " . (int) $stat);
    }

    /**
     * Exists maps to the getter function, as memcached does not support exists checks.
     */
    function _exists($key)
    {
        if (!self::$memcache_operational)
        {
            return false;
        }

        // using get() instead of _get() to let local caching kick-in
        return ($this->get($key) !== false);
    }
}
