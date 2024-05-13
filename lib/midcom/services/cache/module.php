<?php
/**
 * @package midcom.services
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use Symfony\Component\Cache\Adapter\AdapterInterface;

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
    protected AdapterInterface $backend;

    /**
     * Initialize the module. This will initialize the class configuration
     * and call the corresponding event handler.
     */
    public function __construct(AdapterInterface $backend)
    {
        $this->backend = $backend;
    }

    /**
     * Invalidate the cache completely, dropping all entries. The default implementation will
     * drop all entries from the cache backend using AdapterInterface::clear().
     * Override this function if this behavior doesn't suit your needs.
     */
    public function invalidate_all()
    {
        $this->backend->clear();
    }

    /**
     * Invalidate all cache objects related to the given GUID.
     */
    abstract public function invalidate(string $guid, ?midcom_core_dbaobject $object = null);
}
