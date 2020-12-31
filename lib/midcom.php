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
    const VERSION = '9.8.0';

    /**
     * Main application singleton
     *
     * @var midcom_application
     */
    private static $_application;

    /**
     * Mapping of service names to classes implementing the service
     */
    private static $_service_classes = [];

    /**
     * @throws midcom_error
     */
    public static function init(string $environment = 'prod', bool $debug = false) : midcom_application
    {
        ///////////////////////////////////
        // Try to be smart about the paths:
        // Define default constants
        if (!defined('MIDCOM_ROOT')) {
            define('MIDCOM_ROOT', __DIR__);
        }

        if (!defined('MIDCOM_STATIC_ROOT')) {
            $pos = strrpos(MIDCOM_ROOT, '/');
            if ($pos === false) {
                // No slash, this is strange
                throw new midcom_error('MIDCOM_ROOT did not contain a slash, this should not happen and is most probably the cause of a configuration error.');
            }
            define('MIDCOM_STATIC_ROOT', substr(MIDCOM_ROOT, 0, $pos) . '/static');
        }
        if (!defined('MIDCOM_STATIC_URL')) {
            define('MIDCOM_STATIC_URL', '/midcom-static');
        }

        if (!defined('OPENPSA2_THEME_ROOT')) {
            define('OPENPSA2_THEME_ROOT', MIDCOM_ROOT . '/../var/themes/');
        }

        // Instantiate the MidCOM main class
        self::$_application = new midcom_application($environment, $debug);
        self::$_application->boot();
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
