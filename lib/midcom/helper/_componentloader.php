<?php
/**
 * @package midcom.helper
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
 * If you develop framework tools (like administration interfaces), you will also
 * need access to the component interface class, which can be obtained by
 * get_interface_class(). This class is derived from the component interface
 * baseclass and should give you everything you need to work with the component
 * and its information itself.
 *
 * Other than that, you should not have to deal with the components, perhaps with
 * the only exception of is_loaded() and load() to ensure other components are loaded
 * in case you need them and they are not a pure-code library.
 *
 * <b>Loading components</b>
 *
 * When the component loader receives a request it roughly works in
 * three stages:
 *
 * 1. Verify that the given component is valid in terms of the MidCOM Specification.
 * 2. Initialize the Component. Check whether all required concept classes exist.
 * 3. Return the various interface concepts upon each request
 *    from the framework.
 *
 * Stage 1 will do all basic sanity checking. If anything is missing, step 1
 * fails and the componentloader refuses to load the component.
 *
 * Stage 2 will then load the interfaces.php file from the midcom
 * directory. The existence of all required Interface classes is
 * then checked. If this check is successful, the concrete classes
 * of the various interface concepts are instantiated and stored
 * internally. The component is initialized by the call to
 * initialize() which should load everything necessary.
 *
 * Stage 3 is the final stage where the loader stays in memory in
 * order to return references (!) to the loaded component's
 * Interface Classes upon request.
 *
 * In case you need an instance of the component loader to verify or
 * transform component paths, use the function
 * midcom::get()->componentloader, which returns a
 * <i>reference</i> to the loader.
 *
 * @package midcom.helper
 */
class midcom_helper__componentloader
{
    /**
     * This indexed array stores the MidCOM paths of all loaded
     * components. Its elements are used as keys for the cache storage.
     *
     * @var Array
     */
    private $_loaded = array();

    /**
     * This array contains a list of components that were tried to be loaded.
     * The components are added to this list *even* if the system only tried
     * to load it and failed. This way we protect against duplicate class errors
     * and the like if a defective class is tried to be loaded twice.
     *
     * The array maps component names to loading results. The loading result is
     * either false or true as per the result of the load call.
     *
     * @var array
     */
    private $_tried_to_load = array();

    /**
     * This is a part of the component cache. It stores references to
     * the interface classes of the different loaded components, indexed by
     * their MidCOM Path.
     *
     * @var array
     * @see midcom_baseclasses_components_interface
     */
    private $_interface_classes = array();

    /**
     * This lists all available components in the systems in the form of their manifests,
     * indexed by the component name. Whenever possible you should refer to this listing
     * to gain information about the components available.
     *
     * This information is loaded during startup.
     *
     * @var midcom_core_manifest[]
     */
    public $manifests = array();

    /**
     * Invoke _load directly. If the loading process is unsuccessful, throw midcom_error.
     *
     * @param string $path    The component to load explicitly.
     */
    public function load($path)
    {
        if (!$this->_load($path)) {
            throw new midcom_error("Failed to load the component {$path}, see the debug log for more information");
        }
    }

    /**
     * Invoke _load directly. If the loading process is unsuccessful, false is returned.
     *
     * @param string $path    The component to load explicitly.
     * @return boolean Indicating success.
     */
    public function load_graceful($path)
    {
        return $this->_load($path);
    }

    /**
     * This will load the pure-code library denoted by $path. It will
     * return true if the component truly was a pure-code library, false otherwise.
     * If the component loader cannot load the component, midcom_error will be
     * thrown.
     *
     * Common example:
     *
     * <code>
     * midcom::get()->componentloader->load_library('midcom.helper.datamanager2');
     * </code>
     *
     * @param string $path    The name of the code library to load.
     * @return boolean            Indicates whether the library was successfully loaded.
     */
    public function load_library($path)
    {
        if (!array_key_exists($path, $this->manifests)) {
            debug_add("Cannot load component {$path} as library, it is not installed.", MIDCOM_LOG_ERROR);
            return false;
        }

        if (!$this->manifests[$path]->purecode) {
            debug_add("Cannot load component {$path} as library, it is a full-fledged component.", MIDCOM_LOG_ERROR);
            debug_print_r('Manifest:', $this->manifests[$path]);
            return false;
        }

        $this->load($path);

        return true;
    }

    /**
     * Load the component specified by $path. If the component could not be loaded
     * successfully due to integrity errors, it will return false.
     *
     * @param string $path    The component to load.
     * @return boolean Indicating success.
     */
    private function _load($path)
    {
        if (empty($path)) {
            debug_add("No component path given, aborting");
            return false;
        }

        // Check if this component is already loaded...
        if (array_key_exists($path, $this->_tried_to_load)) {
            debug_add("Component {$path} already loaded.");
            return $this->_tried_to_load[$path];
        }

        // Flag this path as loaded/failed, we'll set this flag to true when we reach
        // the end of this call.
        $this->_tried_to_load[$path] = false;

        // Check if the component is listed in the class manifest list. If not,
        // we immediately bail - anything went wrong while loading the component
        // (f.x. broken DBA classes).
        if (!array_key_exists($path, $this->manifests)) {
            debug_add("The component {$path} was not found in the manifest list. Cannot load it.",
                MIDCOM_LOG_WARN);
            return false;
        }

        // Validate and translate url
        if (!$this->validate_url($path)) {
            return false;
        }

        $classname = $this->path_to_prefix($path) . '_interface';
        if (!class_exists($classname)) {
            debug_add("Class {$classname} does not exist.", MIDCOM_LOG_CRIT);
            return false;
        }
        $this->_interface_classes[$path] = new $classname();

        midcom::get()->dbclassloader->load_classes($this->manifests[$path]->name, null, $this->manifests[$path]->class_mapping);

        if ($this->_interface_classes[$path]->initialize($path) == false) {
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
    public function is_loaded($path)
    {
        if ($path == 'midcom') {
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
    public function is_installed($path)
    {
        if (empty($this->manifests)) {
            $this->load_all_manifests();
        }
        if (!isset($this->manifests[$path])) {
            return ($path == 'midcom');
        }
        return true;
    }

    public function register_component($name, $path)
    {
        $filename = "{$path}/config/manifest.inc";
        if (!file_exists($filename)) {
            throw new midcom_error('Manifest not found for ' . $name);
        }
        if (empty($this->manifests)) {
            $this->load_all_manifests();
        }
        $this->_register_manifest(new midcom_core_manifest($filename));
    }

    /**
     * Returns a reference to an instance of the specified component's
     * interface class. The component is given in $path as a MidCOM path.
     * Such an instance will be cached by the framework so that only
     * one instance is always active for each component. Missing
     * components will be dynamically loaded into memory.
     *
     * @param string $path    The component name.
     * @return midcom_baseclasses_components_interface The concept class in question
     */
    public function get_interface_class($path)
    {
        if (!$this->is_loaded($path)) {
            $this->load($path);
            //This will exit on error
        }

        return $this->_interface_classes[$path];
    }

    /**
     * Convert a component path (net.nehmer.blog) to a snippetpath (/net/nehmer/blog).
     *
     * @param string $component_name    Input string.
     * @return string        Converted string.
     */
    public function path_to_snippetpath($component_name)
    {
        if (array_key_exists($component_name, $this->manifests)) {
            return dirname(dirname($this->manifests[$component_name]->filename));
        }
        if ($component_name == 'midcom') {
            return MIDCOM_ROOT . '/midcom';
        }
        debug_add("Component {$component_name} is not registered", MIDCOM_LOG_CRIT);
        return false;
    }

    /**
     * Convert a component path (net.nehmer.blog) to a class prefix (net_nehmer_blog).
     *
     * @param string $path    Input string.
     * @return string        Converted string.
     */
    public function path_to_prefix($path)
    {
        return strtr($path, ".", "_");
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
    public function validate_url($path)
    {
        if (!preg_match("/^[a-z][a-z0-9\.]*[a-z0-9]$/", $path)) {
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
    public function list_loaded_components()
    {
        return $this->_loaded;
    }

    /**
     * This function is called during system startup and loads all component manifests. The list
     * of manifests to load is determined using a find shell call and is cached using the memcache
     * cache module.
     *
     * This method is executed during system startup by the framework. Other parts of the system
     * must not access it.
     */
    public function load_all_manifests()
    {
        $manifests = midcom::get()->cache->memcache->get('MISC', 'midcom.componentloader.manifests');

        if (!is_array($manifests)) {
            debug_add('Cache miss, generating component manifest cache now.');
            $manifests = $this->get_manifests();
            midcom::get()->cache->memcache->put('MISC', 'midcom.componentloader.manifests', $manifests);
        }
        array_map(array($this, '_register_manifest'), $manifests);
    }

    /**
     * This function is called from the class manifest loader in case of a cache
     * miss.
     *
     * @todo investigate if we should unset the package.xml part of the arrays and serialize them
     */
    public function get_manifests()
    {
        $candidates = array();
        // First, we locate all manifest includes:
        // We use some find construct like find -follow -type d -name "config"
        // This does follow symlinks, which can be important when several
        // repositories are "merged" manually
        $directories = array();
        $manifests = array();
        exec('find ' . MIDCOM_ROOT . ' '  . dirname(MIDCOM_ROOT) . '/src -follow -type d -name "config"', $directories);
        foreach ($directories as $directory) {
            $candidates[] = "{$directory}/manifest.inc";
        }
        // now we look for extra components the user my have registered
        $config = midcom::get()->config;
        foreach ($config->get('midcom_components', array()) as $path) {
            $candidates[] = $path . '/config/manifest.inc';
        }
        foreach ($candidates as $filename) {
            if (file_exists($filename)) {
                $manifests[] = new midcom_core_manifest($filename);
            }
        }

        return $manifests;
    }

    /**
     * Register manifest data.
     *
     * All default privileges are made known to ACL, the watches are registered
     *
     * @param midcom_core_manifest $manifest the manifest object to load.
     */
    private function _register_manifest(midcom_core_manifest $manifest)
    {
        $this->manifests[$manifest->name] = $manifest;

        // Register Privileges
        midcom::get()->auth->acl->register_default_privileges($manifest->privileges);

        // Register watches
        if ($manifest->watches !== null) {
            midcom::get()->dispatcher->add_watches($manifest->watches, $manifest->name);
        }
    }

    /**
     * This is called by the framework whenever watchable events occur.
     * The object referenced by $object may be null where appropriate for
     * the operation in question, it is not taken by reference.
     *
     * Call this only if the operation in question has completed successfully.
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
     *     do is_a checks against any registered class restriction on the watch
     * @deprecated Use dispatcher directly instead
     */
    public function trigger_watches($operation, $object)
    {
        midcom::get()->dispatcher->trigger_watch($operation, $object);
    }

    /**
     * Build a complete set of custom data associated with a given component
     * identifier. In case a given component does not have the key set and the boolean parameter
     * is set to true, an empty array is added implicitly.
     *
     * @param string $component The custom data component index to look for.
     * @param boolean $showempty Set this flag to true to get an (empty) entry for all components which
     *     don't have customdata applicable to the component index given. This is disabled by default.
     * @return Array All found component data indexed by known components.
     */
    public function get_all_manifest_customdata($component, $showempty = false)
    {
        $result = array();
        foreach ($this->manifests as $manifest) {
            if (array_key_exists($component, $manifest->customdata)) {
                $result[$manifest->name] = $manifest->customdata[$component];
            } elseif ($showempty) {
                $result[$manifest->name] = array();
            }
        }
        return $result;
    }

    /**
     * Get list of component and its dependencies depend on
     *
     * @param string $component Name of a component
     * @param string $called_from When dependencies are recursively resolved, this is used to avoid circular calls
     * @return array List of dependencies
     */
    public function get_component_dependencies($component, $called_from = false)
    {
        static $checked = array();
        if (isset($checked[$component])) {
            return $checked[$component];
        }
        $checked[$component] = array();

        if (   !$this->is_installed($component)
            || empty($this->manifests[$component]->_raw_data['package.xml']['dependencies'])) {
            return $checked[$component];
        }

        foreach (array_keys($this->manifests[$component]->_raw_data['package.xml']['dependencies']) as $dependency) {
            if (   $dependency == 'midcom'
                || $dependency == $called_from) {
                // Ignore
                continue;
            }

            $checked[$component][] = $dependency;
            $subdependencies = $this->get_component_dependencies($dependency, $component);
            $checked[$component] = array_merge($checked[$component], $subdependencies);
        }
        $checked[$component] = array_unique($checked[$component]);
        return $checked[$component];
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
        if (is_array($core_components)) {
            return (in_array($component, $core_components));
        }

        $core_components = array(
            'fi.protie.navigation',
            'midcom.admin.folder',
            'midcom.admin.help',
            'midcom.admin.user',
            'midcom',
            'midcom.core.nullcomponent',
            'midcom.helper.datamanager2',
            'midcom.helper.imagepopup',
            'midcom.helper.reflector',
            'midcom.helper.replicator',
            'midcom.helper.search',
            'midcom.services.at',
            'midgard.admin.asgard',
            'net.nehmer.blog',
            'net.nehmer.comments',
            'net.nehmer.static',
            'net.nemein.redirector',
            'net.nemein.rss',
            'net.nemein.tag',
            'no.bergfald.rcs',
            'org.openpsa.widgets',
            'org.openpsa.httplib',
            'org.openpsa.mail',
            'org.openpsa.qbpager',
            'org.routamc.positioning',
        );

        // Gather dependencies too
        $dependencies = array();
        foreach ($core_components as $core_component) {
            $component_dependencies = $this->get_component_dependencies($core_component);
            $dependencies = array_merge($dependencies, $component_dependencies);
        }
        $core_components = array_unique(array_merge($core_components, $dependencies));

        return (in_array($component, $core_components));
    }

    public function get_component_version($component)
    {
        if ($component == 'midcom') {
            return midcom::get_version();
        }

        if (   !$this->is_installed($component)
            || !isset($this->manifests[$component]->version)) {
            return null;
        }

        return $this->manifests[$component]->version;
    }

    public function get_component_icon($component, $provide_fallback = true)
    {
        if ($component == 'midcom') {
            return 'stock-icons/logos/midgard-16x16.png';
        }

        if (!$this->is_installed($component)) {
            return null;
        }

        if (isset($this->manifests[$component]->_raw_data['icon'])) {
            return $this->manifests[$component]->_raw_data['icon'];
        }

        if (!$provide_fallback) {
            return null;
        }

        return 'stock-icons/16x16/component.png';
    }
}
