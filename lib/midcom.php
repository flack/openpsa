<?php
/**
 * @package midcom
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use midcom\events\dispatcher;

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
    private static $_version = '9.3.0+git';

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
    private static $_services = [];

    /**
     * Mapping of service names to classes implementing the service
     */
    private static $_service_classes = [
        'auth' => midcom_services_auth::class,
        'componentloader' => midcom_helper__componentloader::class,
        'cache' => midcom_services_cache::class,
        'config' => midcom_config::class,
        'dbclassloader' => midcom_services_dbclassloader::class,
        'dbfactory' => midcom_helper__dbfactory::class,
        'dispatcher' => dispatcher::class,
        'debug' => midcom_debug::class,
        'head' => midcom_helper_head::class,
        'i18n' => midcom_services_i18n::class,
        'indexer' => midcom_services_indexer::class,
        'metadata' => midcom_services_metadata::class,
        'permalinks' => midcom_services_permalinks::class,
        'rcs' => midcom_services_rcs::class,
        'serviceloader' => midcom_helper_serviceloader::class,
        'session' => midcom_services__sessioning::class,
        'style' => midcom_helper__styleloader::class,
        'toolbars' => midcom_services_toolbars::class,
        'uimessages' => midcom_services_uimessages::class,
    ];

    public static function init()
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
        self::$_application = new midcom_application();
        self::$_application->initialize();
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
    public static function get($name = null)
    {
        if (!self::$_application) {
            self::init();
        }

        if (null === $name) {
            return self::$_application;
        }

        if (!isset(self::$_services[$name])) {
            if (!isset(self::$_service_classes[$name])) {
                throw new midcom_error("Requested service '$name' is not available.");
            }
            $service_class = self::$_service_classes[$name];
            self::$_services[$name] = new $service_class;
        }
        return self::$_services[$name];
    }

    public static function get_version()
    {
        return self::$_version;
    }
}
