<?php
/**
 * @package midcom.baseclasses
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * Configuration class for components.
 *
 * @package midcom.baseclasses
 */
class midcom_baseclasses_components_configuration
{
    /**
     * Component specific global data storage area, which should
     * be used for stuff like default configurations etc. thus avoiding the
     * pollution of the global namespace. Each component has its own array
     * in the global one, allowing storage of arbitrary data indexed by arbitrary
     * keys in there. The component-specific arrays are indexed by their
     * name.
     *
     * Note, that this facility is quite a different thing to the component
     * context from midcom_application, even if it has many similar applications.
     * The component context is only available and valid for components, which
     * are actually handling a request. This data storage area is static to the
     * complete component and shared over all subrequests and therefore suitable
     * to hold default configurations, -schemas and the like.
     */
    private static array $_data = [];

    public static function get(string $component, string $key = null)
    {
        self::_initialize($component);
        if ($key === null) {
            return self::$_data[$component];
        }
        return self::$_data[$component][$key];
    }

    public static function set(string $component, string $key, $value)
    {
        self::_initialize($component);
        self::$_data[$component][$key] = $value;
    }

    /**
     * Clear static cache
     *
     * @internal
     */
    public static function reset()
    {
        self::$_data = [];
    }

    /**
     * Initialize the global data storage
     */
    private static function _initialize(string $component)
    {
        if (!array_key_exists($component, self::$_data)) {
            self::$_data[$component] = [
                'active_leaf' => false,
                'config' => []
            ];
            self::_load_configuration($component);
        }
    }

    /**
     * Loads the configuration file specified by the component configuration
     * and constructs a midcom_helper_configuration object out of it. Both
     * Component defaults and sitegroup-configuration gets merged. The
     * resulting object is stored under the key 'config' in the
     * component's data storage area.
     *
     * Three files will be loaded in order:
     *
     * 1. The component's default configuration, placed in $prefix/config/config.inc
     * 2. Any systemwide default configuration, currently placed in midcom::get()->config->get('midcom_config_basedir')/midcom/$component/config.inc.
     * 3. Any site configuration in the snippet midcom::get()->config->get('midcom_sgconfig_basedir')/$component/config.
     *
     * @see midcom_helper_configuration
     */
    private static function _load_configuration(string $component)
    {
        $data = [];
        $component_path = midcom::get()->componentloader->path_to_snippetpath($component);
        // Load and parse the global config
        if ($component_data = self::read_array_from_file($component_path . '/config/config.inc')) {
            $data = array_merge($data, $component_data);
        }

        // Go for the sitewide default
        $data = array_merge($data, self::read_array_from_snippet("conf:/{$component}/config.inc"));

        // Finally, check the sitegroup config
        $data = array_merge($data, self::read_array_from_snippet(midcom::get()->config->get('midcom_sgconfig_basedir') . "/{$component}/config"));

        self::$_data[$component]['config'] = new midcom_helper_configuration($data);
    }

    /**
     * Read a file from disk and evaluate its content as array.
     * This is essentially a simple [$data\n] eval construct.
     */
    public static function read_array_from_file(string $filename) : array
    {
        if (!is_readable($filename)) {
            return [];
        }

        return midcom_helper_misc::parse_config(file_get_contents($filename), $filename);
    }

    /**
     * Read a snippet and evaluate its content as array.
     * This is essentially a simple [$data\n] eval construct.
     *
     * If the snippet does not exist, false is returned.
     * @see read_array_from_file()
     */
    public static function read_array_from_snippet(string $snippetpath) : array
    {
        $code = midcom_helper_misc::get_snippet_content_graceful($snippetpath);
        return midcom_helper_misc::parse_config($code, $snippetpath);
    }
}
