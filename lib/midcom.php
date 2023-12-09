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
    const VERSION = '9.10.0';

    /**
     * Main application singleton
     */
    private static ?midcom_application $_application = null;

    /**
     * Mapping of service names to classes implementing the service
     */
    private static array $_service_classes = [];

    /**
     * @throws midcom_error
     */
    public static function init(string $environment = 'prod', bool $debug = false) : midcom_application
    {
        // Instantiate the MidCOM main class
        self::$_application = new midcom_application($environment, $debug);

        // Define default constants
        if (!defined('MIDCOM_STATIC_URL')) {
            define('MIDCOM_STATIC_URL', '/midcom-static');
        }
        define('MIDCOM_STATIC_ROOT', self::$_application->getProjectDir() . '/web' . MIDCOM_STATIC_URL);
        return self::$_application;
    }

    /**
     * Get midcom_application instance
     *
     * Services can also be loaded this way by passing their name as an argument,
     * but this feature will likely be removed at some point
     *
     * @param string $name The service name as listed in the _service_classes array or null to get midcom_application
     * @return midcom_application The midcom application instance
     */
    public static function get(string $name = null)
    {
        if (!self::$_application) {
            self::init();
        }

        if (null === $name) {
            return self::$_application;
        }

        return self::$_application->getContainer()->get($name);
    }

    /**
     * Register a service class
     *
     * (Experimental, use with caution)
     */
    public static function register_service_class(string $name, string $class)
    {
        self::$_service_classes[$name] = $class;
    }

    /**
     * @internal
     */
    public static function get_registered_service_classes() : array
    {
        return self::$_service_classes;
    }
}
