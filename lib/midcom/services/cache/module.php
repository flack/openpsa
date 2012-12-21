<?php
/**
 * @package midcom.services
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * This is the base class for the MidCOM cache modules. It provides a basic infrastructure
 * for building your own caching service, providing hooks for initialization and shutdown.
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
     * Current configuration.
     *
     * @var Array
     * @access protected
     */
    var $_config = null;

    /**
     * A list of all backends created by _create_backend(). They will be automatically
     * shut down when the module shuts down. They are indexed by their name.
     *
     * @var Array
     * @access protected
     */
    var $_backends = Array();

    /**
     * Initialize the module. This will initialize the class configuration
     * and call the corresponding event handler.
     *
     * @param array $config Configuration to use.
     */
    function initialize()
    {
        $this->_on_initialize();
    }

    /**
     * Shuts the module down. This will fist call the corresponding event handler, and will
     * close all registered backends <i>afterwards</i>.
     */
    function shutdown()
    {
        // First call the shutdown event.
        $this->_on_shutdown();

        // Now close all backends.
        foreach ($this->_backends as $key => $copy)
        {
            $this->_backends[$key]->shutdown();
        }
    }

    /**
     * Creates an instance of the handler described by the configuration passed to
     * the function. The backend instance returned should be assigned by reference,
     * to avoid handle duplication or the like.
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
     * @return midcom_services_cache_backend A reference to the new backend.
     * @access protected
     */
    function _create_backend($name, $config)
    {
        $name .= $this->_prefix .= $_SERVER['SERVER_NAME'];

        if (array_key_exists($name, $this->_backends))
        {
            throw new midcom_error("Cannot create backend driver instance {$name}: A backend with this name does already exist.");
        }

        if (! array_key_exists('driver', $config))
        {
            throw new midcom_error("Cannot create backend driver instance {$name}: The driver class is not specified in the configuration.");
        }
        $classname = "midcom_services_cache_backend_{$config['driver']}";

        if (! class_exists($classname))
        {
            throw new midcom_error("Cannot create backend driver instance {$name}: The class {$classname} was not found.");
        }
        $backend = new $classname();
        $backend->initialize($name, $config);

        $this->_backends[$name] = $backend;

        return $backend;
    }

    /**
     * Startup handler, called during service start up at the start of the request.
     * You may, as it is required for the content cache, intercept requests. Terminate
     * the requests with _midcom_stop_request() if you produce a complete output based on a previous request
     * (page cache) of midcom_application::finish() if you produce regular output that
     * might go into the content cache.
     */
    public function _on_initialize() {}

    /**
     * Shutdown handler, called during midcom_application::finish(). Note, that for example
     * the page cache will not use this cleanup handler, as it produces a complete html page
     * based on a previous request.
     */
    public function _on_shutdown() {}

    /**
     * Invalidate the cache completely, dropping all entries. The default implementation will
     * drop all entries from all registered cache backends using
     * midcom_services_cache_backend::remove_all(). Override this function, if this behavior
     * doesn't suit your needs.
     */
    function invalidate_all()
    {
        foreach ($this->_backends as $name => $copy)
        {
            debug_add("Invalidating cache backend {$name}...", MIDCOM_LOG_INFO);
            $this->_backends[$name]->remove_all();
        }
    }

    /**
     * Invalidate all cache objects related to the given GUID.
     *
     * @param GUID $guid The GUID that has to be invalidated.
     */
    abstract function invalidate($guid);
}
?>