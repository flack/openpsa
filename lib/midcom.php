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
        ///////////////////////////////////////////////////////////
        // Ignore deprecation warnings on PHP 5.3 because they're caused by our PEAR dependencies
        if (version_compare(PHP_VERSION, '5.3.0', '>='))
        {
            error_reporting(E_ALL & ~E_STRICT & ~E_DEPRECATED);
        }

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

        //////////////////////////////////////////////////////////////
        // Set the MIDCOM_XDEBUG constant accordingly, if not yet set.

        if (! defined('MIDCOM_XDEBUG'))
        {
            if (function_exists('xdebug_start_profiling'))
            {
                define('MIDCOM_XDEBUG', 1);
            }
            else if (function_exists('xdebug_break'))
            {
                define('MIDCOM_XDEBUG', 2);
            }
            else
            {
                define('MIDCOM_XDEBUG', 0);
            }
        }

        // Register autoloader so we get all MidCOM classes loaded automatically
        spl_autoload_register(array('midcom', 'autoload'));

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
        self::$_services['cache']->initialize();

        require(MIDCOM_ROOT . '/midcom/services/_i18n_l10n.php');

        /////////////////////////////////////
        // Instantiate the MidCOM main class
        self::$_application = new midcom_application();

        require_once(MIDCOM_ROOT . '/compat/superglobal.php');

        $_MIDCOM = new midcom_compat_superglobal();

        $_MIDCOM->initialize();

        if (file_exists(MIDCOM_CONFIG_FILE_AFTER))
        {
            include(MIDCOM_CONFIG_FILE_AFTER);
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

        $path = MIDCOM_ROOT . '/' . str_replace('_', '/', $class_name) . '.php';
        $path = str_replace('//', '/_', $path);

        if (   basename($path) == 'dba.php'
            || basename($path) == 'db.php')
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

            $path = dirname($path) . '.php';
        }

        if (   preg_match('/^[^_]+?_[^_]+?_[^_]+?_interface$/', $class_name)
            && $class_name != 'midcom_baseclasses_components_interface')
        {
            // MidCOM component interfaces are named midcom/interface.php
            $_MIDCOM->dbclassloader->load_component_for_class($class_name);
            return;
        }

        if (!file_exists($path))
        {
            $alternative_path = str_replace('.php', '/main.php', $path);

            if (!file_exists($alternative_path))
            {
                /**
                 * Enable when debugging autoloading issues, otherwise it's just noise
                 *
                 debug_add("Autoloader got '{$path}' and tried {$alternative_path} but neither was not found, aborting");
                 debug_print_function_stack("Failed to autoload {$class_name}, called from");
                */
              return;
            }
            $path = $alternative_path;
        }

        require($path);
        $autoloaded++;
    }

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
}

midcom::init();
?>
