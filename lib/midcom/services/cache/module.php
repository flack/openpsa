<?php
/**
 * @package midcom.services
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use Doctrine\Common\Cache;

/**
 * This is the base class for the MidCOM cache modules. It provides a basic infrastructure
 * for building your own caching service, providing hooks for initialization.
 *
 * It provides convenience methods to start up the cache module, for example for the creation
 * of a cache backend instance. There is no specific initialization done during startup, to
 * allow the modules to do their own magic during startup (it is difficult to generalize such
 * stuff).
 *
 * @package midcom.services
 */
abstract class midcom_services_cache_module
{
    /**
     * A list of all backends created by _create_backend(). They will be automatically
     * shut down when the module shuts down. They are indexed by their name.
     *
     * @var Doctrine\Common\Cache\CacheProvider[]
     */
    protected $_backends = [];

    /**
     * The cache key prefix.
     *
     * @var string
     */
    protected $_prefix;

    /**
     * Initialize the module. This will initialize the class configuration
     * and call the corresponding event handler.
     */
    public function initialize()
    {
        $this->_prefix = get_class($this) . $_SERVER['SERVER_NAME'];
        $this->_on_initialize();
    }

    /**
     * Creates an instance of the handler described by the configuration passed to
     * the function.
     *
     * The configuration array must include the configuration parameters driver and
     * directory, as outlined in the midcom_services_cache_backend class documentation.
     *
     * All backends will be collected in the $_backends array, indexed by their name.
     *
     * Any duplicate instantiation will be intercepted, throwing a critical error.
     *
     * @param string $name The name of the backend, must be unique throughout the system.
     * @param array $config The configuration of the backend to create. It must contain
     *     the key 'driver', which indicates which backend to use.
     * @return Doctrine\Common\Cache\CacheProvider The new backend.
     */
    protected function _create_backend($name, array $config)
    {
        $name = $this->_prefix . $name;

        if (array_key_exists($name, $this->_backends)) {
            throw new midcom_error("Cannot create backend driver instance {$name}: A backend with this name does already exist.");
        }

        if (!array_key_exists('driver', $config)) {
            throw new midcom_error("Cannot create backend driver instance {$name}: The driver class is not specified in the configuration.");
        }

        if (is_string($config['driver'])) {
            $backend = $this->prepare_backend($config, $name);
        } else {
            $backend = $config['driver'];
        }
        $backend->setNamespace($name);

        $this->_backends[$name] = $backend;

        return $backend;
    }

    private function prepare_backend(array $config, $name)
    {
        $directory = midcom::get()->config->get('cache_base_directory');
        if (!empty($config['directory'])) {
            $directory .= $config['directory'];
        }
        $memcache_operational = false;
        switch ($config['driver']) {
            case 'apc':
                $backend = new Cache\ApcuCache();
                break;
            case 'memcached':
                $host = !empty($config['host']) ? $config['host'] : 'localhost';
                $port = !empty($config['port']) ? $config['port'] : 11211;
                $memcached = new Memcached;
                if (@$memcached->addServer($host, $port)) {
                    $backend = new Cache\MemcachedCache();
                    $backend->setMemcached($memcached);
                    $memcache_operational = true;
                    break;
                } else {
                    midcom::get()->debug->log_php_error(MIDCOM_LOG_ERROR);
                    debug_add("memcache: Failed to connect to {$host}:{$port}. Falling back to filecache", MIDCOM_LOG_ERROR);
                }
                // fall-through
            case 'dba':
            case 'flatfile':
                $backend = new Cache\FilesystemCache($directory . $name);
                break;
            case 'sqlite':
                $sqlite = new SQLite3("{$directory}/{$name}.db");
                $table = str_replace(['.', '-'], '_', $name);
                $backend = new Cache\SQLite3Cache($sqlite, $table);
                break;
            case 'null':
            default:
                $backend = new Cache\VoidCache();
                break;
        }

        $cache = new Cache\ChainCache([new Cache\ArrayCache, $backend]);
        $cache->memcache_operational = $memcache_operational;
        return $cache;
    }

    /**
     * Startup handler, called during service start up at the start of the request.
     * You may, as it is required for the content cache, intercept requests. Terminate
     * the requests with _midcom_stop_request() if you produce a complete output based on a previous request
     * (page cache) of midcom_application::finish() if you produce regular output that
     * might go into the content cache.
     */
    public function _on_initialize()
    {
    }

    /**
     * Invalidate the cache completely, dropping all entries. The default implementation will
     * drop all entries from all registered cache backends using CacheProvider::flushAll().
     * Override this function if this behavior doesn't suit your needs.
     */
    public function invalidate_all()
    {
        foreach ($this->_backends as $name => $backend) {
            debug_add("Invalidating cache backend {$name}...", MIDCOM_LOG_INFO);
            $backend->flushAll();
        }
    }

    /**
     * Invalidate all cache objects related to the given GUID.
     *
     * @param string $guid The GUID that has to be invalidated.
     * @param object $object The object that has to be invalidated (if available).
     */
    abstract public function invalidate($guid, $object = null);
}
