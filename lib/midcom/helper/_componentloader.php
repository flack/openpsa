<?php
/**
 * @package midcom
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * This class is a Factory that is responsible for loading and
 * establishing the interface to a MidCOM Component.
 *
 * <b>Working with components</b>
 *
 * Normally, two things are important when you deal with other components:
 *
 * First, if you want to list other components, or for example check whether they
 * are available, you should use the component manifest listing, known as $manifests.
 * It gives you all meta-information about the components.
 *
 * This should actually suffice for most normal operations.
 *
 * If you develop framework tools (like administration interfaces, you will also
 * need access to the component interface class, which can be obtained by
 * get_component_class(). This class is derived from the component interface
 * baseclass and should give you everything you need to work with the component
 * and its information itself.
 *
 * Other then that, you should not have to deal with the components, perhaps with
 * the only exception of is_loaded() and load() to ensure other components are loaded
 * in case you need them and they are not a pure-code library.
 *
 *
 * <b>Loading components</b>
 *
 * When the component loader receives a request it roughly works in
 * three stages:
 *
 * 1. Verify that the given component is valid in terms of the
 *    MidCOM Specification. This will check the existence of all
 *    required SnippetDirs.
 * 2. Load all Snippets related with the MidCOM Interface Concept
 *    Classes and instantiate the MidCOM and Component concept
 *    classes, initialize the Component. Check whether all
 *    required concept classes exist.
 * 3. Return the various interface concepts upon each request
 *    from the framework.
 *
 * Stage 1 will do all basic sanity checking possible before
 * loading any snippets. It will check for the existence of all
 * defined sub-SnippetDirs that are required for the system to
 * work. If anything is missing, step 1 fails and the
 * componentloader refuses to load the component.
 *
 * Stage 2 will then load the interfaces.php file from the midcom
 * directory. The existence of all required Interface classes is
 * then checked. If this check is successful, the concrete classes
 * of the various interface concepts are instantiated and stored
 * internally. The component is initialized by the call to
 * MIDCOM::initialize() which should load everything necessary.
 *
 * Stage 3 is the final stage where the loader stays in memory in
 * order to return references (!) to the loaded component's
 * Interface Classes upon request.
 *
 * In case you need an instance of the component loader to verify or
 * transform component paths, use the function
 * midcom_application::get_component_loader, which returns a
 * <i>reference</i> to the loader.
 *
 * @package midcom
 * @see midcom_application::get_component_loader()
 */
class midcom_helper__componentloader
{
    /**
     * This indexed array stores the MidCOM paths of all loaded
     * components. Its elements are used as keys for the cache storage.
     *
     * @var Array
     */
    private $_loaded = Array();

    /**
     * This array contains a list of components that were tried to be loaded.
     * The components are added to this list *even* if the system only tried
     * to load it and failed. This way we protect against duplicate class errors
     * and the like if a defective class is tried to be loaded twice.
     *
     * The array maps component names to loading results. The loading result is
     * either false or true as per the result of the load call.
     *
     * @var Array
     */
    private $_tried_to_load = Array();

    /**
     * This is a part of the component cache. It stores references to
     * the interface classes of the different loaded components, indexed by
     * their MidCOM Path.
     *
     * @var Array
     * @see midcom_baseclasses_components_interface
     */
    private $_interface_classes = Array();

    /**
     * This lists all available components in the systems in the form of their manifests,
     * indexed by the component name. Whenever possible you should refer to this listing
     * to gain information about the components available.
     *
     * This information is loaded during startup.
     *
     * @var array
     * @see midcom_core_manifest
     */
    var $manifests = Array();

    /**
     * This array contains all registered MidCOM operation watches. They are indexed by
     * operation and map to components / libraries which have registered to classes.
     * Values consist of an array whose first element is the component and subsequent
     * elements are the types involved (so a single count means all objects).
     *
     * @var array
     */
    private $_watches = array
    (
        MIDCOM_OPERATION_DBA_CREATE => Array(),
        MIDCOM_OPERATION_DBA_UPDATE => Array(),
        MIDCOM_OPERATION_DBA_DELETE => Array(),
        MIDCOM_OPERATION_DBA_IMPORT => Array(),
    );

    /**
     * This is an array containing a list of watches that need to be executed at the end
     * of any given request. The array is indexed by artificial keys constructed out of the
     * watched object's class type and guid values. The array always contain the object
     * instance in the first element, and all components that need to be notified in the
     * subsequent keys.
     *
     * @var array
     */
    private $_watch_notifications = array
    (
        MIDCOM_OPERATION_DBA_CREATE => Array(),
        MIDCOM_OPERATION_DBA_UPDATE => Array(),
        MIDCOM_OPERATION_DBA_DELETE => Array(),
        MIDCOM_OPERATION_DBA_IMPORT => Array(),
    );

    /**
     * This function will invoke _load directly. If the loading process
     * is unsuccessful, it will call generate_error.
     *
     * @param string $path    The component to load explicitly.
     */
    function load($path)
    {
        if (! $this->_load($path))
        {
            throw new midcom_error("Failed to load the component {$path}, see the debug log for more information");
        }
    }

    /**
    * This function will invoke _load directly. If the loading process
    * is unsuccessful, false is returned.
    *
    * @param string $path    The component to load explicitly.
    * @return boolean Indicating success.
    */
    function load_graceful($path)
    {
        return $this->_load($path);
    }

    /**
     * This function will load the component specified by the MidCOM
     * path $path. If the component could not be loaded successfully due
     * to integrity errors (missing SnippetDirs, Classes, etc.), it will
     * return false.
     *
     * @param string $path    The component to load.
     * @return boolean Indicating success.
     */
    private function _load($path)
    {
        if (empty($path))
        {
            debug_add("No component path given, aborting");
            return false;
        }

        // Check if this component is already loaded...
        if (array_key_exists($path, $this->_tried_to_load))
        {
            debug_add("Component {$path} already loaded.");
            return $this->_tried_to_load[$path];
        }

        // Flag this path as loaded/failed, we'll set this flag to true when we reach
        // the end of this call.
        $this->_tried_to_load[$path] = false;

        // Check if the component is listed in the class manifest list. If not,
        // we immediately bail - anything went wrong while loading the component
        // (f.x. broken DBA classes).
        if (! array_key_exists($path, $this->manifests))
        {
            debug_add("The component {$path} was not found in the manifest list. Cannot load it.",
                MIDCOM_LOG_WARN);
            return false;
        }

        // Validate and translate url
        if (! $this->validate_url($path))
        {
            return false;
        }
        $snippetpath = $this->path_to_snippetpath($path);

        if (! $this->validate_path($snippetpath))
        {
            return false;
        }

        // Load Snippets
        $directory = MIDCOM_ROOT . "{$snippetpath}/midcom";
        if (! is_dir($directory))
        {
            debug_add("Failed to access Snippetdir {$directory}: Directory not found.", MIDCOM_LOG_CRIT);
            return false;
        }

        // Load the interfaces.php snippet, abort if that file is not available.
        if (! file_exists("{$directory}/interfaces.php"))
        {
            debug_add("File {$directory}/interfaces.php is not present.", MIDCOM_LOG_CRIT);
            return false;
        }
        require("{$directory}/interfaces.php");

        // Load the component interface, try to be backwards-compatible
        $prefix = $this->snippetpath_to_prefix($snippetpath);

        if (class_exists("{$prefix}_interface"))
        {
            $classname = "{$prefix}_interface";
            $this->_interface_classes[$path] = new $classname();
        }
        else
        {
            debug_add("Class {$prefix}_interface does not exist.", MIDCOM_LOG_CRIT);
            return false;
        }

        // Make DBA Classes known, bail out if we encounter an invalid class
        if (! $_MIDCOM->dbclassloader->load_classes($this->manifests[$path]->name, null, $this->manifests[$path]->class_definitions))
        {
            debug_add("Failed to load the component manifest for {$this->manifests[$path]->name}: The DBA classes failed to load.", MIDCOM_LOG_WARN);
            return false;
        }

        $init_class =& $this->_interface_classes[$path];
        if ($init_class->initialize($path) == false)
        {
            debug_add("Initialize of Component {$path} failed.", MIDCOM_LOG_CRIT);
            return false;
        }

        $this->_loaded[] = $path;

        $this->_tried_to_load[$path] = true;

        return true;
    }

    /**
     * Returns true if the component identified by the MidCOM path $url
     * is already loaded and available for usage.
     *
     * @param string $path    The component to be queried.
     * @return boolean            true if it is loaded, false otherwise.
     */
    function is_loaded($path)
    {
        if ($path == 'midcom')
        {
            // MidCOM is "always loaded"
            return true;
        }
        return in_array($path, $this->_loaded);
    }

    /**
     * Returns true if the component identified by the MidCOM path $url
     * is installed.
     *
     * @param string $path    The component to be queried.
     * @return boolean            true if it is loaded, false otherwise.
     */
    function is_installed($path)
    {
        if (!isset($this->manifests[$path]))
        {
            return false;
        }
        return true;
    }

    /**
     * Returns a reference to an instance of the specified component's
     * interface class. The component is given in $path as a MidCOM path.
     * Such an instance will be cached by the framework so that only
     * one instance is always active for each component. Missing
     * components will be dynamically loaded into memory.
     *
     * @param string $path    The component name.
     * @return midcom_baseclasses_components_interface A reference to the concept class in question or null if
     *     the class in question does not yet support the new Interface system.
     */
    function get_interface_class($path)
    {
        if (! $this->is_loaded($path))
        {
            $this->load($path);
            //This will exit on error
        }

        return $this->_interface_classes[$path];
    }

    /**
     * Helper, converting a component path (net.nehmer.blog)
     * to a snippetpath (/net/nehmer/blog).
     *
     * @param string $path    Input string.
     * @return string        Converted string.
     */
    function path_to_snippetpath($path)
    {
        return "/" . strtr($path, ".", "/");
    }

    /**
     * Helper, converting a snippetpath (/net/nehmer/blog)
     * to a class prefix (net_nehmer_blog).
     *
     * @param string $snippetpath    Input string.
     * @return string                Converted string.
     */
    function snippetpath_to_prefix ($snippetpath)
    {
        return substr(strtr($snippetpath, "/", "_"), 1);
    }

    /**
     * Helper, converting a component path (net.nehmer.blog)
     * to a class prefix (net_nehmer_blog).
     *
     * @param string $path    Input string.
     * @return string        Converted string.
     */
    function path_to_prefix ($path)
    {
        return strtr($path, ".", "_");
    }

    /**
     * validate_path is used to validate the component located at the
     * snippetdir Path $snippetpath. This is a fully qualified snippetdir path
     * to the component in question.
     *
     * @param string $snippetpath    The path to be checked.
     * @return boolean                 True if valid, false otherwise.
     */
    function validate_path($snippetpath)
    {
        $directory = MIDCOM_ROOT . $snippetpath;

        if (! is_dir($directory))
        {
            debug_add("Failed to validate the component path {$directory}: It is no directory.", MIDCOM_LOG_CRIT);
            return false;
        }

        return true;
    }

    /**
     * Will validate the given MidCOM Path $path for syntactical
     * correctness. Currently this is a RegEx check that checks for
     * invalid characters in $path, so validate_url does explicitly
     * <i>not</i> check whether the referenced component does exist and
     * whether it is structurally valid.
     *
     * @param string $path    The path to be checked.
     * @return boolean         True if valid, false otherwise.
     */
    function validate_url($path)
    {
        if (!preg_match("/^[a-z][a-z0-9\.]*[a-z0-9]$/", $path))
        {
            debug_add("Invalid URL: " . $path, MIDCOM_LOG_CRIT);
            return false;
        }

        return true;
    }

    /**
     * Retrieve a list of all loaded components. The Array will contain an
     * unsorted collection of MidCOM Paths.
     *
     * @return Array    List of loaded components
     */
    function list_loaded_components()
    {
        return $this->_loaded;
    }

    /**
     * This function is called during system startup and loads all component manifests from
     * the disk. The list of manifests to load is determined using a find shell call and is cached
     * using the phpscripts cache module.
     *
     * This method is executed during system startup by the framework. Other parts of the system
     * must not access it.
     */
    function load_all_manifests()
    {
        $cache_identifier = $_MIDCOM->cache->phpscripts->create_identifier('midcom.componentloader', 'manifests');

        if (! $cache_identifier)
        {
            $cache_hit = false;
        }
        else
        {
            $cache_hit = $_MIDCOM->cache->phpscripts->load($cache_identifier, filemtime(__FILE__));
        }

        if (! $cache_hit)
        {
            debug_add('Cache miss, generating component manifest cache now.');
            $this->_generate_class_manifest_cache($cache_identifier);
        }
    }

    /**
     * This function is called from the class manifest loader in case of a cache
     * miss.
     *
     * @param string $cache_identifier The cache identifier to use to cache the manifest
     *     loading queue.
     * @todo investigate if we should unset the package.xml part of the arrays and serialize them
     */
    private function _generate_class_manifest_cache($cache_identifier)
    {
        // First, we locate all manifest includes:
        // We use some find construct like find -follow -type d -name "config"
        // This does follow symlinks, which can be important when several
        // repositories are "merged" manually
        $directories = Array();
        exec('find ' . MIDCOM_ROOT . ' -follow -type d -name "config"', $directories);
        $code = "";
        foreach ($directories as $directory)
        {
            $filename = "{$directory}/manifest.inc";
            if (file_exists($filename))
            {
                $manifest_data = file_get_contents($filename);

                $code .= "\$_MIDCOM->componentloader->load_manifest(
                    new midcom_core_manifest(
                    '{$filename}', array({$manifest_data})));\n";
            }
        }

        if (! $_MIDCOM->cache->phpscripts->add($cache_identifier, $code))
        {
            debug_add("Failed to add the manifest loading queue to the cache using the identifier {$cache_identifier}.",
                MIDCOM_LOG_WARN);
            debug_add('Continuing uncached.');
            eval($code);
        }
        return;
    }

    /**
     * Load a manifest from the disk and add it to the $manifests list.
     *
     * It already does all necessary registration work:
     *
     * - All default privileges are made known
     * - All defined DBA class sets are loaded. If an error occurs here,
     *   the manifest will be ignored and an error is logged.
     *
     *  @param object $manifest the manifest object to load.
     */
    function load_manifest($manifest)
    {
        $this->manifests[$manifest->name] = $manifest;

        // Register Privileges
        $_MIDCOM->auth->acl->register_default_privileges($manifest->privileges);

        // Register watches
        if ($manifest->watches !== null)
        {
            foreach ($manifest->watches as $watch)
            {
                // Check for every operation we know and register the watches.
                // We make shortcuts for less typing.
                $operations = $watch['operations'];
                $watch_info = $watch['classes'];
                if ($watch_info === null)
                {
                    $watch_info = Array();
                }

                // Add the component name into the watch information, it is
                // required for later processing of the watch.
                array_unshift($watch_info, $manifest->name);

                foreach ($this->_watches as $operation_id => $ignore)
                {
                    // Check whether the operations flag list from the component
                    // contains the operation_id we're checking a watch for.
                    if ($operations & $operation_id)
                    {
                        $this->_watches[$operation_id][] = $watch_info;
                    }
                }
            }
        }
    }

    /**
     * This is called by the framework whenever watchable events occur.
     * The object referenced by $object may be null where appropriate for
     * the operation in question, it is not taken by reference.
     *
     * Call this only if the operation in question has completed successfully.
     *
     * The component handlers can safely assume that it is only called once per object
     * and operation at the end of the request.
     *
     * This latter fact is important to understand: Watches are not executed immediately,
     * instead, they are collected throughout the request and executed during
     * midcom_application::finish() exactly once per instance. The instance is refreshed
     * before it is actually sent to the watchers using a refresh member function unless
     * the object has been deleted, then there will be no refresh attempt.
     *
     * An instance in this respect is a unique combination of class type and guid values.
     *
     * A watchable object must therefore have the following properties:
     *
     * - <i>string $guid</i> The guid identifying the object.
     * - <i>boolean refresh()</i> A method used to refresh the object against its datasource.
     *
     * So, two instances are equal <i>if and only if</i> they are of the same class and
     * have the same $guid property value.
     *
     * @param int $operation The operation that has occurred.
     * @param mixed $object The object on which the operation occurred. The system will
     *     do is_a checks against any registered class restriction on the watch. The object
     *     is not taken by-reference but refreshed before actually executing the hook at the
     *     end of the request.
     */
    function trigger_watches($operation, $object)
    {
        // We collect the components of all watches here, so that we can
        // unique-out all duplicates before actually calling the handler.
        $components = Array();
        foreach ($this->_watches[$operation] as $watch)
        {
            if (count($watch) == 1)
            {
                $components[] = $watch[0];
            }
            else
            {
                $component = array_shift($watch);
                foreach ($watch as $classname)
                {
                    if (is_a($object, $classname))
                    {
                        $components[] = $component;
                        break;
                    }
                }
            }
        }

        $components = array_unique($components);
        $object_key = get_class($object) . $object->guid;
        debug_add("Adding notification for operation {$operation} on {$object_key}");
        if (! array_key_exists($object_key, $this->_watch_notifications[$operation]))
        {
            $this->_watch_notifications[$operation][$object_key] = Array(clone $object);
        }
        foreach ($components as $component)
        {
            // FIXME: This causes a PHP notice
            error_reporting(E_WARNING);
            if (! in_array($component, $this->_watch_notifications[$operation][$object_key]))
            {
                $this->_watch_notifications[$operation][$object_key][] = $component;
            }
            error_reporting(E_ALL);
        }
    }

    /**
     * This function processes all pending notifies and flushes the pending list.
     * It is called automatically during MidCOM shutdown at the end of the request.
     *
     * All Notifies for objects which can't be refreshed will be ignored silently
     * (but logged of course). Deleted objects are of course not refreshed.
     *
     * This function can only be called once during a request.
     */
    function process_pending_notifies()
    {
        if ($this->_watch_notifications === null)
        {
            debug_add('Pending notifies should only be processed once at the end of the request, aborting.', MIDCOM_LOG_WARN);
            return;
        }

        foreach ($this->_watch_notifications as $operation => $operation_data)
        {
            foreach ($operation_data as $object_key => $data)
            {
                debug_add("Processing operation {$operation} for {$object_key}");
                $object = array_shift($data);
                if (   $operation != MIDCOM_OPERATION_DBA_DELETE
                    && $operation != MIDCOM_OPERATION_DBA_IMPORT)
                {
                    // Only refresh when we haven't deleted/imported the record.
                    if (! $object->refresh())
                    {
                        debug_add('Failed to refresh an object before notification, skipping it. see the debug level log for a dump.', MIDCOM_LOG_WARN);
                        continue;
                    }
                }
                foreach ($data as $component)
                {
                    if (! $this->is_loaded($component))
                    {
                        // Try to load the component, fail silently if we can't load the component
                        if (! $this->_load($component))
                        {
                            debug_add("Failed to load the component {$component} required for handling the current watch set, skipping watch.", MIDCOM_LOG_INFO);
                            continue;
                        }
                    }
                    debug_add("Calling \$this->_interface_classes[{$component}]->trigger_watch({$operation}, \$object)");
                    $this->_interface_classes[$component]->trigger_watch($operation, $object);
                }
            }
        }
        $this->_watch_notifications = null;
    }

    /**
     * This small helper builds a complete set of custom data associated with a given component
     * identifier. In case a given component does not have the key set and the boolean parameter
     * is set to true, an empty array is added implicitly.
     *
     * @param string $component The custom data component index to look for.
     * @param boolean $showempty Set this flag to true to get an (empty) entry for all components which
     *     don't have customdata applicable to the component index given. This is disabled by default.
     * @return Array All found component data indexed by known components.
     */
    function get_all_manifest_customdata($component, $showempty = false)
    {
        $result = Array();
        foreach ($this->manifests as $manifest)
        {
            if (array_key_exists($component, $manifest->customdata))
            {
                $result[$manifest->name] = $manifest->customdata[$component];
            }
            else if ($showempty)
            {
                $result[$manifest->name] = Array();
            }
        }
        return $result;
    }

    /**
     * Get list of component and its dependencies depend on
     *
     * @param string $component Name of a component
     * @return array List of dependencies
     */
    public function get_component_dependencies($component)
    {
        static $checked = null;
        if (is_null($checked))
        {
            $checked = array();
        }
        if (isset($checked[$component]))
        {
            return array();
        }
        $checked[$component] = true;

        if (!$this->is_installed($component))
        {
            return array();
        }

        $dependencies = array();

        if (   !isset($this->manifests[$component]->_raw_data['package.xml'])
            || !isset($this->manifests[$component]->_raw_data['package.xml']['dependencies']))
        {
            return $dependencies;
        }

        foreach ($this->manifests[$component]->_raw_data['package.xml']['dependencies'] as $dependency => $dependency_data)
        {
            if (   isset($dependency_data['channel'])
                && $dependency_data['channel'] != $GLOBALS['midcom_config']['pear_channel'])
            {
                // We can ignore non-component dependencies
                continue;
            }

            if ($dependency == 'midcom')
            {
                // Ignore
                continue;
            }

            $dependencies[] = $dependency;
            $subdependencies = $this->get_component_dependencies($dependency);
            $dependencies = array_merge($dependencies, $subdependencies);
        }

        return array_unique($dependencies);
    }

    /**
     * Checks if component is a part of the default MidCOM distribution
     * or an external component
     *
     * @param string $component Component to check
     */
    public function is_core_component($component)
    {
        static $core_components = null;
        if (is_array($core_components))
        {
            if (in_array($component, $core_components))
            {
                return true;
            }
            return false;
        }

        // This is the list as agreed in Midgard Gathering 2008-11-08
        $core_components = array
        (
            'de.bitfolge.feedcreator',
            'de.linkm.sitemap',
            'fi.protie.navigation',
            'midcom.admin.babel',
            'midcom.admin.folder',
            'midcom.admin.help',
            'midcom.admin.settings',
            'midcom.admin.user',
            'midcom',
            'midcom.core.nullcomponent',
            'midcom.helper.datamanager2',
            'midcom.helper.imagepopup',
            'midcom.helper.reflector',
            'midcom.helper.replicator',
            'midcom.helper.search',
            'midcom.helper.xml',
            'midcom.helper.xsspreventer',
            'midcom.services.at',
            'midgard.admin.asgard',
            'midgard.admin.wizards',
            'net.nehmer.blog',
            'net.nehmer.comments',
            'net.nehmer.markdown',
            'net.nehmer.static',
            'net.nemein.calendar',
            'net.nemein.personnel',
            'net.nemein.redirector',
            'net.nemein.rss',
            'net.nemein.tag',
            'no.bergfald.rcs',
            'org.openpsa.calendarwidget',
            'org.openpsa.contactwidget',
            'org.openpsa.httplib',
            'org.openpsa.mail',
            'org.openpsa.qbpager',
            'org.routamc.gallery',
            'org.routamc.photostream',
            'org.routamc.positioning',
        );

        // Gather dependencies too
        $dependencies = array();
        foreach ($core_components as $core_component)
        {
            $component_dependencies = $this->get_component_dependencies($core_component);
            $dependencies = array_merge($dependencies, $component_dependencies);
        }
        $core_components = array_unique(array_merge($core_components, $dependencies));

        if (in_array($component, $core_components))
        {
            return true;
        }

        return false;
    }

    public function get_component_version($component)
    {
        if ($component == 'midcom')
        {
            return $GLOBALS['midcom_version'];
        }

        if (   !$this->is_installed($component)
            || !isset($this->manifests[$component]->version))
        {
            return null;
        }

        return $this->manifests[$component]->version;
    }

    public function get_component_icon($component, $provide_fallback = true)
    {
        if ($component == 'midcom')
        {
            return 'stock-icons/logos/midgard-16x16.png';
        }

        if (!$this->is_installed($component))
        {
            return null;
        }

        if (isset($this->manifests[$component]->_raw_data['icon']))
        {
            return $this->manifests[$component]->_raw_data['icon'];
        }

        if (!$provide_fallback)
        {
            return null;
        }

        return 'stock-icons/16x16/component.png';
    }
}
?>