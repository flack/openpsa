<?php
/**
 * @package midcom
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * @package midcom
 */
class midcom
{
    /**
     * MidCOM version
     *
     * @var string
     */
    private static $_version = '9.0beta5+git';

    /**
     * Main application singleton
     *
     * @var midcom_application
     */
    private static $_application;

    /**
     * This is the interface to MidCOMs Object Services.
     *
     * Each service is indexed by its string-name (for example "i18n"
     * for all i18n stuff).
     *
     * @var Array
     */
    private static $_services = array();

    /**
     * Mapping of service names to classes implementing the service
     */
    private static $_service_classes = array
    (
        'componentloader' => 'midcom_helper__componentloader',
        'cache' => 'midcom_services_cache',
        'config' => 'midcom_config',
        'dbclassloader' => 'midcom_services_dbclassloader',
        'dbfactory' => 'midcom_helper__dbfactory',
        'dispatcher' => '\\midcom\\events\\dispatcher',
        'debug' => 'midcom_debug',
        'head' => 'midcom_helper_head',
        'i18n' => 'midcom_services_i18n',
        'indexer' => 'midcom_services_indexer',
        'metadata' => 'midcom_services_metadata',
        'permalinks' => 'midcom_services_permalinks',
        'rcs' => 'midcom_services_rcs',
        'serviceloader' => 'midcom_helper_serviceloader',
        'session' => 'midcom_services__sessioning',
        'style' => 'midcom_helper__styleloader',
        'tmp' => 'midcom_services_tmp',
        'toolbars' => 'midcom_services_toolbars',
        'uimessages' => 'midcom_services_uimessages',
    );

    public static function init()
    {
        //Constants, Globals and Configuration
        require __DIR__ . '/constants.php';

        midcom_compat_environment::initialize();

        self::$_services['config'] = new midcom_config;

        require __DIR__ . '/errors.php';

        // Register autoloader so we get all MidCOM classes loaded automatically
        spl_autoload_register(array('midcom', 'autoload'));

        // Start the Debugger
        require __DIR__. '/midcom/debug.php';

        debug_add("Start of MidCOM run" . (isset($_SERVER['REQUEST_URI']) ? ": {$_SERVER['REQUEST_URI']}" : ''));

        self::$_services['auth'] = new midcom_services_auth();
        self::$_services['auth']->initialize();

        /* Load and start up the cache system, this might already end the request
         * on a content cache hit. Note that the cache check hit depends on the i18n and auth code.
         */
        self::$_services['cache'] = new midcom_services_cache();

        // Instantiate the MidCOM main class
        self::$_application = new midcom_application();

        if (self::$_services['config']->get('midcom_compat_ragnaroek'))
        {
            require_once __DIR__ . '/compat/bootstrap.php';
        }

        self::$_application->initialize();

        if (   self::$_services['config']->get('midcom_compat_ragnaroek')
            && file_exists(MIDCOM_CONFIG_FILE_AFTER))
        {
            include MIDCOM_CONFIG_FILE_AFTER;
        }
    }

    /**
     * Automatically load missing class files
     *
     * @param string $class_name Name of a missing PHP class
     */
    public static function autoload($class_name)
    {
        static $autoloaded = 0;

        if (preg_match('/_dba?$/', $class_name))
        {
            // DBA object files are named objectname.php

            // Ensure we have the component loaded
            if (!self::get('dbclassloader')->load_component_for_class($class_name))
            {
                // Failed to load the component
                return;
            }
            if (class_exists($class_name))
            {
                return;
            }

            $class_name = preg_replace('/_dba?$/', '', $class_name);
        }
        else if (   preg_match('/^[^_]+?_[^_]+?_[^_]+?_interface$/', $class_name)
                 && $class_name != 'midcom_baseclasses_components_interface')
        {
            // MidCOM component interfaces are named midcom/interface.php
            self::get('dbclassloader')->load_component_for_class($class_name);
            return;
        }

        $path = self::_resolve_path($class_name);

        if (!$path)
        {
            return;
        }

        require $path;
        $autoloaded++;
    }

    private static function _resolve_path($classname)
    {
        $path = str_replace('//', '/_', str_replace('_', '/', $classname)) . '.php';
        if (file_exists(MIDCOM_ROOT . '/' . $path))
        {
            return MIDCOM_ROOT . '/' . $path;
        }
        else
        {
            $alternative_path = str_replace('.php', '/main.php', $path);
            if (file_exists(MIDCOM_ROOT . '/' . $alternative_path))
            {
                return MIDCOM_ROOT . '/' . $alternative_path;
            }
        }
        // file was not found in-tree, let's look somewhere else
        $component = preg_replace('|^([a-z].+?)/(.+?)/([^/\.]+).*$|', '$1.$2.$3', $path);

        if (self::get('componentloader')->is_installed($component))
        {
            $component_path = self::get('componentloader')->path_to_snippetpath($component);
            $class_part = preg_replace('|^/|', '', substr($path, strlen($component)));
            $path = str_replace('/.php', '.php', $component_path . '/' . $class_part);

            if (file_exists($path))
            {
                return $path;
            }
            else
            {
                $alternative_path = str_replace('.php', '/main.php', $path);
                if (file_exists($alternative_path))
                {
                    return $alternative_path;
                }
            }
        }

        return false;
    }

    /**
     * Get service or midcom_application singletons. Services are automatically instantiated if they
     * were not used before
     *
     * @param string $name The service name as listed in the _service_classes array or null to get midcom_application
     * @return mixed The requested instance
     */
    public static function get($name = null)
    {
        if (null === $name)
        {
            return self::$_application;
        }

        if (isset(self::$_services[$name]))
        {
            return self::$_services[$name];
        }

        if (isset(self::$_service_classes[$name]))
        {
            $service_class = self::$_service_classes[$name];
            self::$_services[$name] = new $service_class;
            return self::$_services[$name];
        }

        throw new midcom_error("Requested service '$name' is not available.");
    }

    public static function get_version()
    {
        return self::$_version;
    }
}

midcom::init();
?>
