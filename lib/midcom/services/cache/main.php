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
 * @property midcom_services_cache_module_content $content
 * @package midcom.services
 */
class midcom_services_cache implements EventSubscriberInterface
{
    /**
     * List of all loaded modules, indexed by their class name.
     *
     * @var midcom_services_cache_module[]
     */
    private $_modules = [];

    /**
     * Cache service startup. It initializes all cache modules configured in the
     * global configuration as outlined in the class introduction.
     *
     * It will load the content cache module as the first one, the rest will be
     * loaded in their order of appearance in the Array.
     */
    public function __construct()
    {
        array_map([$this, 'load_module'], midcom::get()->config->get('cache_autoload_queue'));
        midcom::get()->dispatcher->addSubscriber($this);
    }

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
        $object = $event->get_object();
        $parent = $object->get_parent();
        if (!empty($parent->guid)) {
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
     * Load the specified cache module (if not already loaded), add it to the _modules array
     * and assign it to a member variable named after the module.
     *
     * @param string $name The name of the cache module to load.
     */
    private function load_module($name)
    {
        if (isset($this->_modules[$name])) {
            return;
        }

        $classname = "midcom_services_cache_module_{$name}";

        if (!class_exists($classname)) {
            throw new midcom_error("Tried to load the cache module {$name}, but the class {$classname} was not found");
        }

        $this->_modules[$name] = new $classname();
        $this->_modules[$name]->initialize();
        $this->$name =& $this->_modules[$name];
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
        array_map('unlink', glob(midcom::get()->config->get('cache_base_directory') . 'routing/*'));
        connection::invalidate_cache();
    }

    /**
     * Invalidates all cache records associated with a given content object.
     *
     * @param mixed $guid This is either a GUID or a MidgardObject, in which case the Guid is auto-determined.
     * @param string $skip_module If specified, the module mentioned here is skipped during invalidation.
     *     This option <i>should</i> be avoided by component authors at all costs, it is there for
     *     optimizations within the core cache modules (which sometimes need to invalidate only other
     *     modules, and invalidate themselves implicitly).
     */
    public function invalidate($guid, $skip_module = '')
    {
        $object = null;
        if (is_object($guid)) {
            $object = $guid;
            $guid = $guid->guid;
        }
        if (empty($guid)) {
            debug_add("Called for empty GUID, ignoring invalidation request.");
            return;
        }

        foreach ($this->_modules as $name => $module) {
            if ($name == $skip_module) {
                debug_add("We have to skip the cache module {$name}.");
                continue;
            }
            debug_add("Invalidating the cache module {$name} for GUID {$guid}.");
            $module->invalidate($guid, $object);
        }
    }
}
