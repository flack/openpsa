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
 * order to return the loaded component's Interface instances upon request.
 *
 * In case you need an instance of the component loader to verify or
 * transform component paths, use midcom::get()->componentloader
 *
 * @package midcom.helper
 */
class midcom_helper__componentloader
{
    /**
     * This stores the interface instances of the different loaded components,
     * indexed by their MidCOM Path.
     *
     * @var midcom_baseclasses_components_interface[]
     */
    private array $_interface_classes = [];

    /**
     * @var midcom_core_manifest[]
     */
    private array $manifests = [];

    private array $components;

    public function __construct(array $components)
    {
        $this->components = $components;
    }

    /**
     * Load the component specified by $path
     */
    private function load(string $path)
    {
        if (empty($path)) {
            throw new midcom_error("No component path given, aborting");
        }

        // Check if the component is listed in the class manifest list. If not,
        // we immediately bail - anything went wrong while loading the component
        // (f.x. broken DBA classes).
        if (!array_key_exists($path, $this->components)) {
            throw new midcom_error("The component {$path} was not found in the manifest list. Cannot load it.");
        }

        $classname = midcom_baseclasses_components_interface::get_classname($path);
        $this->_interface_classes[$path] = new $classname;
        $this->_interface_classes[$path]->initialize($path);
    }

    /**
     * Returns true if the component identified by the MidCOM path $url
     * is installed.
     */
    public function is_installed(string $path) : bool
    {
        return array_key_exists($path, $this->components);
    }

    /**
     * Returns an instance of the specified component's
     * interface class. The component is given in $path as a MidCOM path.
     * Such an instance will be cached by the framework so that only
     * one instance is always active for each component. Missing
     * components will be dynamically loaded into memory.
     */
    public function get_interface_class(string $path) : midcom_baseclasses_components_interface
    {
        if (!array_key_exists($path, $this->_interface_classes)) {
            $this->load($path);
        }

        return $this->_interface_classes[$path];
    }

    /**
     * Convert a component path (net.nehmer.blog) to a snippetpath (/net/nehmer/blog).
     */
    public function path_to_snippetpath(string $component_name) : string
    {
        if (array_key_exists($component_name, $this->components)) {
            return dirname($this->components[$component_name], 2);
        }
        throw new midcom_error("Component {$component_name} is not registered");
    }

    /**
     * Convert a component path (net.nehmer.blog) to a class prefix (net_nehmer_blog).
     */
    public function path_to_prefix(string $path) : string
    {
        return strtr($path, ".", "_");
    }

    public function get_manifest(string $name) : ?midcom_core_manifest
    {
        if (!$this->is_installed($name)) {
            return null;
        }
        if (!array_key_exists($name, $this->manifests)) {
            $this->manifests[$name] = new midcom_core_manifest($this->components[$name]);
        }
        return $this->manifests[$name];
    }

    /**
     * This lists all available components in the systems in the form of their manifests,
     * indexed by the component name. Whenever possible you should refer to this listing
     * to gain information about the components available.
     *
     * @return midcom_core_manifest[]
     */
    public function get_manifests() : array
    {
        array_map([$this, 'get_manifest'], array_keys($this->components));
        return $this->manifests;
    }

    /**
     * Build a complete set of custom data associated with a given component
     * identifier.
     */
    public function get_all_manifest_customdata(string $component) : array
    {
        $result = [];
        foreach ($this->get_manifests() as $manifest) {
            if (array_key_exists($component, $manifest->customdata)) {
                $result[$manifest->name] = $manifest->customdata[$component];
            }
        }
        return $result;
    }

    public function get_component_icon(string $component, bool $provide_fallback = true) : ?string
    {
        if (!$this->is_installed($component)) {
            return null;
        }

        if (!empty($this->get_manifest($component)->icon)) {
            return $this->get_manifest($component)->icon;
        }

        if (!$provide_fallback) {
            return null;
        }

        return 'puzzle-piece';
    }
}
