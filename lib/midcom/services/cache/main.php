<?php
/**
 * @package midcom.services
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use midcom\events\dbaevent;

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
     * @var Array
     */
    private $_modules = Array();

    /**
     * List of all modules in the order they need to be unloaded. This is a FILO queue, the
     * module loaded first is unloaded last.
     *
     * @var Array
     */
    private $_unload_queue = Array();

    /**
     * Cache service startup. It initializes all cache modules configured in the
     * global configuration as outlined in the class introduction.
     *
     * It will load the content cache module as the first one, the rest will be
     * loaded in their order of appearance in the Array.
     */
    public function __construct()
    {
        array_map(array($this, 'load_module'), midcom::get()->config->get('cache_autoload_queue'));
        midcom::get()->dispatcher->addSubscriber($this);
    }

    public static function getSubscribedEvents()
    {
        return array
        (
            dbaevent::CREATE => array('handle_create'),
            dbaevent::UPDATE => array('handle_update'),
            dbaevent::DELETE => array('handle_event'),
            dbaevent::APPROVE => array('handle_event'),
            dbaevent::UNAPPROVE => array('handle_event'),
        );
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
        if (   $parent
            && $parent->guid)
        {
            // Invalidate parent from cache so content caches have chance to react
            $this->invalidate($parent);
        }
    }

    public function handle_update(dbaevent $event)
    {
        $object = $event->get_object();
        $this->invalidate($object);

        if (midcom::get()->config->get('attachment_cache_enabled'))
        {
            foreach ($object->list_attachments() as $att)
            {
                $this->invalidate($att->guid);
                // This basically ensures that attachment cache links are
                // deleted when their parent is no longer readable by everyone
                // @todo: The only question is: Does it even get triggered on privilege changes?
                $att->update_cache();
            }
        }
    }

    /**
     * Shuts all cache modules down. The content module is explicitly stopped as the
     * last module for clear content cache handling (it might _midcom_stop_request()).
     */
    public function shutdown()
    {
        foreach ($this->_unload_queue as $name)
        {
            $this->_modules[$name]->shutdown();
        }
        midcom::get()->dispatcher->removeSubscriber($this);
    }

    /**
     * Load the specified cache module (if not already loaded), add it to the _modules array
     * and assign it to a member variable named after the module.
     *
     * @param string $name The name of the cache module to load.
     */
    function load_module($name)
    {
        if (isset($this->_modules[$name]))
        {
            return;
        }

        $classname = "midcom_services_cache_module_{$name}";

        if (!class_exists($classname))
        {
            throw new midcom_error("Tried to load the cache module {$name}, but the class {$classname} was not found");
        }

        $this->_modules[$name] = new $classname();
        $this->_modules[$name]->initialize();
        $this->$name =& $this->_modules[$name];
        array_unshift($this->_unload_queue, $name);
    }

    /**
     * Invalidate all caches completely.
     *
     * Use this, if you have, f.x. changes in the layout. The URL function
     * midcom-cache-invalidate will trigger this function.
     */
    public function invalidate_all()
    {
        foreach ($this->_unload_queue as $name)
        {
            debug_add("Invalidating the cache module {$name} completely.");
            $this->_modules[$name]->invalidate_all();
        }

        // Invalidate Midgard cache, too
        if (extension_loaded('midgard'))
        {
            mgd_cache_invalidate();
        }
    }

    /**
     * Invalidates all cache records associated with a given content object.
     *
     * @param mixed $guid This is either a GUID or a MidgardObject, in which case the Guid is auto-dtermined.
     * @param string $skip_module If specified, the module mentioned here is skipped during invalidation.
     *     This option <i>should</i> be avoided by component authors at all costs, it is there for
     *     optimizations within the core cache modules (which sometimes need to invalidate only other
     *     modules, and invalidate themselves implicitly).
     */
    public function invalidate($guid, $skip_module = '')
    {
        $object = null;
        if (is_object($guid))
        {
            $object = $guid;
            $guid = $guid->guid;
        }
        if (empty($guid))
        {
            debug_add("Called for empty GUID, ignoring invalidation request.");
            return;
        }

        foreach ($this->_unload_queue as $name)
        {
            if ($name == $skip_module)
            {
                debug_add("We have to skip the cache module {$name}.");
                continue;
            }
            debug_add("Invalidating the cache module {$name} for GUID {$guid}.");
            $this->_modules[$name]->invalidate($guid, $object);
        }
    }
}
