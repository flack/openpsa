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
     * This is a component specific global data storage area, which should
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
     *
     * @var array
     */
    private static $_data = array();

    public static function get($component, $key = null)
    {
        if (!array_key_exists($component, self::$_data))
        {
            self::_initialize($component);
        }

        if (is_null($key))
        {
            return self::$_data[$component];
        }
        else
        {
            return self::$_data[$component][$key];
        }
    }

    public static function set($component, $key, $value)
    {
        if (!array_key_exists($component, self::$_data))
        {
            self::_initialize($component);
        }

        self::$_data[$component][$key] = $value;
    }

    /**
     * Initialize the global data storage
     */
    private static function _initialize($component)
    {
        self::$_data[$component] = array
        (
            'active_leaf' => false,
            'config' => array()
        );
        self::_load_configuration($component);
    }

    /**
     * Loads the configuration file specified by the component configuration
     * and constructs a midcom_helper_configuration object out of it. Both
     * Component defaults and sitegroup-configuration gets merged. The
     * resulting object is stored under the key 'config' in the
     * components' data storage area.
     *
     * Errors will be logged as MIDCOM_LOG_WARN but silently ignored. This
     * should be viable, since as of MidCOM 2.4 the configuration class is
     * more flexible when local and global configurations do not match.
     *
     * Three files will be loaded in order:
     *
     * 1. The components default configuration, placed in $prefix/config/$name.inc
     * 2. Any systemwide default configuration, currently placed in /etc/midgard/midcom/$component/$name.inc.
     * 3. Any site configuration in the snippet $GLOBALS['midcom_config']['midcom_sgconfig_basedir']/$component/$name.
     *
     * If $_config_snippet_name is set to null, no configuration will be done.
     *
     * @access private
     * @see midcom_helper_configuration
     * @see $_config_snippet_name
     */
    private static function _load_configuration($component)
    {
        $loader = $_MIDCOM->get_component_loader();
        $component_path = MIDCOM_ROOT . $loader->path_to_snippetpath($component);
        // Load and parse the global config
        $data = self::read_array_from_file($component_path . '/config/config.inc');
        if (! $data)
        {
            // Empty defaults
            $data = Array();
        }
        $config = new midcom_helper_configuration($data);

        // Go for the sitewide default
        $data = self::read_array_from_file("/etc/midgard/midcom/{$component}/config.inc");
        if ($data !== false)
        {
            $config->store($data, false);
        }

        // Finally, check the sitegroup config
        $data = self::read_array_from_snippet("{$GLOBALS['midcom_config']['midcom_sgconfig_basedir']}/{$component}/config");
        if ($data !== false)
        {
            $config->store($data, false);
        }

        self::$_data[$component]['config'] = new midcom_helper_configuration($config->get_all());
    }

    /**
     * This helper function reads a file from disk and evaluates its content as array.
     * This is essentially a simple Array($data\n) eval construct.
     *
     * If the file does not exist, false is returned.
     *
     * This function may be called statically.
     *
     * @param string $filename The name of the file that should be parsed.
     * @return Array The read data or false on failure.
     * @see read_array_from_snippet()
     */
    public static function read_array_from_file($filename)
    {
        if (!file_exists($filename))
        {
            return array();
        }

        try
        {
            $data = file_get_contents($filename);
        }
        catch (Exception $e)
        {
            return false;
        }
        $result = eval("\$data = array({$data}\n);");
        if ($result === false)
        {
            $_MIDCOM->generate_error(MIDCOM_ERRCRIT,
                "Failed to parse content loaded from file '{$filename}', see above for PHP errors.");
            // This will exit.
        }
        return $data;
    }

    /**
     * This helper function reads a snippet and evaluates its content as array.
     * This is essentially a simple Array($data\n) eval construct.
     *
     * If the snippet does not exist, false is returned.
     *
     * This function may be called statically.
     *
     * @param string $snippetpath The full path to the snippet that should be returned.
     * @return Array The read data or false on failure.
     * @see read_array_from_file()
     */
    public static function read_array_from_snippet($snippetpath)
    {
        $code = midcom_get_snippet_content_graceful($snippetpath);
        $result = eval("\$data = Array({$code}\n);");
        if ($result === false)
        {
            $_MIDCOM->generate_error(MIDCOM_ERRCRIT,
                "Failed to parse content loaded from snippet '{$snippetpath}', see above for PHP errors.");
            // This will exit.
        }
        return $data;
    }
}
?>
