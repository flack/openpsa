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
    private static $_version = '9.0beta4+git';

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
        'dbclassloader' => 'midcom_services_dbclassloader',
        'dbfactory' => 'midcom_helper__dbfactory',
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
        ///////////////////////////////////
        // Try to be smart about the paths:
        // Define default constants
        if (! defined('MIDCOM_ROOT'))
        {
            define('MIDCOM_ROOT', dirname(__FILE__));
        }

        require(MIDCOM_ROOT . '/compat/environment.php');
        midcom_compat_environment::initialize();

        if (! defined('MIDCOM_STATIC_ROOT'))
        {
            $pos = strrpos(MIDCOM_ROOT, '/');
            if ($pos === false)
            {
                // No slash, this is strange
                _midcom_stop_request('MIDCOM_ROOT did not contain a slash, this should not happen and is most probably the cause of a configuration error.');
            }
            define('MIDCOM_STATIC_ROOT', substr(MIDCOM_ROOT, 0, $pos) . '/static');
        }
        if (! defined('MIDCOM_STATIC_URL'))
        {
            define('MIDCOM_STATIC_URL', '/midcom-static');
        }
        if (! defined('MIDCOM_CONFIG_FILE_BEFORE'))
        {
            define('MIDCOM_CONFIG_FILE_BEFORE', '/etc/midgard/midcom.conf');
        }
        if (! defined('MIDCOM_CONFIG_FILE_AFTER'))
        {
            define('MIDCOM_CONFIG_FILE_AFTER', '/etc/midgard/midcom-after.conf');
        }

        ///////////////////////////////////////
        //Constants, Globals and Configuration
        require(MIDCOM_ROOT . '/constants.php');
        require(MIDCOM_ROOT. '/midcom/connection.php');
        require(MIDCOM_ROOT. '/midcom/config/midcom_config.php');
        ini_set('track_errors', '1');
        require(MIDCOM_ROOT. '/errors.php');

        // Register autoloader so we get all MidCOM classes loaded automatically
        spl_autoload_register(array('midcom', 'autoload'));

        if (file_exists(MIDCOM_ROOT . '/../vendor/autoload.php'))
        {
            $loader = require MIDCOM_ROOT . '/../vendor/autoload.php';
            $loader->register();
        }

        /////////////////////
        // Start the Debugger
        require(MIDCOM_ROOT. '/midcom/debug.php');

        debug_add("Start of MidCOM run: {$_SERVER['REQUEST_URI']}");

        self::$_services['auth'] = new midcom_services_auth();
        self::$_services['auth']->initialize();

        /* Load and start up the cache system, this might already end the request
         * on a content cache hit. Note that the cache check hit depends on the i18n and auth code.
         */
        self::$_services['cache'] = new midcom_services_cache();

        /////////////////////////////////////
        // Instantiate the MidCOM main class
        self::$_application = new midcom_application();

        if (!empty($GLOBALS['midcom_config']['midcom_compat_ragnaroek']))
        {
            require_once MIDCOM_ROOT . '/compat/bootstrap.php';
        }

        // Current MidCOM configuration.
        // TODO: Replace this with midcom::get('config')
        $GLOBALS['midcom_config'] = new midcom_config;

        self::$_application->initialize();

        if (   !empty($GLOBALS['midcom_config']['midcom_compat_ragnaroek'])
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

        //PSR-0 part
        $class_name = ltrim($class_name, '\\');
        if ($last_ns_pos = strripos($class_name, '\\'))
        {
            $basedir = MIDCOM_ROOT . '/../vendor/';
            $namespace = substr($class_name, 0, $last_ns_pos);
            $class_name = substr($class_name, $last_ns_pos + 1);
            $file_name  = str_replace('\\', DIRECTORY_SEPARATOR, $namespace) . DIRECTORY_SEPARATOR;
            $file_name .= str_replace('_', DIRECTORY_SEPARATOR, $class_name) . '.php';

            if (!file_exists($basedir . $file_name))
            {
                return false;
            }
            require $basedir . $file_name;
            $autoloaded++;
            return true;
        }

        //MidCOM "Classic"
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
        if (is_null($name))
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
