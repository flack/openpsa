<?php
/**
 * @package midcom.services
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use midcom\events\dbaevent;
use midgard\portable\storage\connection;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * This class is the central access point for all registered caching services. Currently
 * this includes the NAP, Memcache, Content and PHPscripts cache databases.
 *
 * The system is twofold:
 *
 * There are cache backends, which are responsible for the actual storage and retrieval of
 * cache information, and cache modules, which provide caching services to the application
 * developer.
 *
 * Check the documentation of the backends for configuring the cache on your live site
 * (as an administrator).
 *
 * Check the documentation of the cache modules to learn how to take advantage of the cache
 * services available (as a component/core author).
 *
 * The cache service is independent from the MidCOM Core, as it has to be started up at the
 * beginning of the request. Cache modules are loaded on-demand.
 *
 * This class will be available throughout he midcom service getter under the handle cache.
 * The content cache module, for backwards compatibility, will be available as $midcom->cache.
 *
 * All loaded modules will also be available as direct members of this class, you have to ensure
 * the module is loaded in advance though. The class will automatically load all modules which
 * are configured in the autoload_queue in the cache configuration.
 *
 * @package midcom.services
 */
class midcom_services_cache implements EventSubscriberInterface
{
    /**
     * @var midcom_services_cache_module_content
     */
    public $content;

    /**
     * @var midcom_services_cache_module_memcache
     */
    public $memcache;

    /**
     * @var midcom_services_cache_module_nap
     */
    public $nap;

    /**
     * List of all loaded modules, indexed by their class name.
     *
     * @var midcom_services_cache_module[]
     */
    private $_modules = [];

    public static function getSubscribedEvents()
    {
        return [
            dbaevent::CREATE => ['handle_create'],
            dbaevent::UPDATE => ['handle_update'],
            dbaevent::DELETE => ['handle_event'],
            dbaevent::APPROVE => ['handle_event'],
            dbaevent::UNAPPROVE => ['handle_event'],
        ];
    }

    public function handle_event(dbaevent $event)
    {
        $object = $event->get_object();
        $this->invalidate($object);
    }

    public function handle_create(dbaevent $event)
    {
        if ($parent = $event->get_object()->get_parent()) {
            // Invalidate parent from cache so content caches have chance to react
            $this->invalidate($parent);
        }
    }

    public function handle_update(dbaevent $event)
    {
        $object = $event->get_object();
        $this->invalidate($object);

        if (midcom::get()->config->get('attachment_cache_enabled')) {
            foreach ($object->list_attachments() as $att) {
                $this->invalidate($att->guid);
                // This basically ensures that attachment cache links are
                // deleted when their parent is no longer readable by everyone
                // @todo: The only question is: Does it even get triggered on privilege changes?
                $att->update_cache();
            }
        }
    }

    /**
     * Add the specified cache module (if not already present), add it to the _modules array
     * and assign it to a member variable named after the module.
     */
    public function add_module(string $name, midcom_services_cache_module $module)
    {
        if (!isset($this->_modules[$name])) {
            $this->_modules[$name] = $module;
            $this->$name =& $this->_modules[$name];
        }
    }

    /**
     * Invalidate all caches completely.
     *
     * Use this, if you have, f.x. changes in the layout. The URL function
     * midcom-cache-invalidate will trigger this function.
     */
    public function invalidate_all()
    {
        foreach ($this->_modules as $name => $module) {
            debug_add("Invalidating the cache module {$name} completely.");
            $module->invalidate_all();
        }
        midcom::get()->dispatcher->addListener(KernelEvents::TERMINATE, function() {
            $fs = new Filesystem;
            $fs->remove([midcom::get()->getCacheDir()]);
            // see https://github.com/symfony/symfony/pull/36540
            if (function_exists('opcache_reset')) {
                opcache_reset();
            }
        });

        connection::invalidate_cache();
    }

    /**
     * Invalidates all cache records associated with a given content object.
     *
     * @param mixed $guid This is either a GUID or a MidgardObject, in which case the Guid is auto-determined.
     */
    public function invalidate($guid)
    {
        $object = null;
        if (is_object($guid)) {
            $object = $guid;
            $guid = $object->guid;
        }
        if (empty($guid)) {
            debug_add("Called for empty GUID, ignoring invalidation request.");
            return;
        }

        foreach ($this->_modules as $name => $module) {
            debug_add("Invalidating the cache module {$name} for GUID {$guid}.");
            $module->invalidate($guid, $object);
        }
    }
}
