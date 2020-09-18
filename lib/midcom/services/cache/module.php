<?php
/**
 * @package midcom.services
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use Doctrine\Common\Cache\CacheProvider;

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
     * Cache backend instance.
     *
     * @var Doctrine\Common\Cache\CacheProvider
     */
    protected $backend;

    /**
     * Initialize the module. This will initialize the class configuration
     * and call the corresponding event handler.
     */
    public function __construct(CacheProvider $backend)
    {
        $this->backend = $backend;
        $this->backend->setNamespace(get_class($this) . $_SERVER['SERVER_NAME']);
    }

    /**
     * Invalidate the cache completely, dropping all entries. The default implementation will
     * drop all entries from the cache backend using CacheProvider::flushAll().
     * Override this function if this behavior doesn't suit your needs.
     */
    public function invalidate_all()
    {
        $this->backend->flushAll();
    }

    /**
     * Invalidate all cache objects related to the given GUID.
     *
     * @param object $object The object that has to be invalidated (if available).
     */
    abstract public function invalidate(string $guid, $object = null);
}
