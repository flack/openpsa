<?php
/**
 * @package midcom.routing
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace midcom\routing;

use midcom;
use midcom_admin_folder_management;
use midcom_admin_rcs_plugin;
use midcom_helper_imagepopup_viewer;
use midcom_admin_help_help;
use midgard_admin_asgard_plugin;
use midcom_error;

/**
 * <b>Plugin Interface</b>
 *
 * This class includes a plugin system which can be used to flexibly enhance the
 * functionality of the request classes by external sources. Your component does
 * not have to worry about this, you just have to provide a way to register plugins
 * to site authors.
 *
 * Plugins always come in "packages", which are assigned to a namespace. The namespace
 * is used to separate various plugins from each other, it is prepended before any
 * URL. Within a plugin you can register one or more handler classes. Each of this
 * classes can of course define more than one request handler.
 *
 * A plugin class must be a descendant of midcom_baseclasses_components_handler or at
 * least support its full interface.
 *
 * As outlined above, plugins are managed in a two-level hierarchy. First, there is
 * the plugin identifier, second the class identifier. When registering a plugin,
 * these two are specified. The request handlers obtained by the above callback are
 * automatically expanded to match the plugin namespace.
 *
 * <i>Example: Plugin registration</i>
 *
 * <code>
 * $this->register_plugin_namespace(
 *     '__ais', [
 *         'folder' => [
 *             'class' => 'midcom_admin_folder_management',
 *             'config' => null,
 *         ],
 *     ]
 * );
 * </code>
 *
 * The first argument of this call identifies the plugin namespace, the second
 * the list of classes associated with this plugin. Each class gets its own
 * identifier. The namespace and class identifier is used to construct the
 * final plugin URL: {$anchor_prefix}/{$namespace}/{$class_identifier}/...
 * This gives fully unique URL namespaces to all registered plugins.
 *
 * Plugin handlers always last in queue, so they won't override component handlers.
 * Their name is prefixed with __{$namespace}-{$class_identifier} to ensure
 * uniqueness.
 *
 * Each class must have these options:
 *
 * - class: The name of the class to use
 * - src: The source URL of the plugin class. This can be either a file:/...
 *   URL which is relative to MIDCOM_ROOT, snippet:/... which identifies an
 *   arbitrary snippet, or finally, component:...
 *   which will load the component specified. This is only used if the class
 *   is not yet available.
 * - name: This is the clear-text name of the plugin.
 * - config: This is an optional configuration argument, allows for customization.
 *   May be omitted, in which case it defaults to null.
 *
 * Once a plugin has been successfully initialized, its configuration is put
 * into the request data:
 *
 * - mixed plugin_config: The configuration passed to the plugin as outlined
 *   above.
 * - string plugin_name: The name of the plugin as defined in its config
 *
 * @package midcom.routing
 */
class plugin
{
    /**
     * This variable keeps track of the registered plugin namespaces. It maps namespace
     * identifiers against plugin config lists. This is used during can_handle startup
     * to determine whether the request has to be relayed to a plugin.
     *
     * You have to use the viewer's register_plugin_namespace() member function during the
     * _on_initialize event to register plugin namespaces.
     *
     * @var array
     */
    private static $registry = [];

    /**
     * Create a new plugin namespace and map the configuration to it.
     * It allows flexible, user-configurable extension of components.
     *
     * Only very basic testing is done to keep runtime up, currently the system only
     * checks to prevent duplicate namespace registrations. In such a case,
     * midcom_error will be thrown. Any further validation won't be done before
     * can_handle determines that a plugin is actually in use.
     *
     * @param string $namespace The plugin namespace, checked against $args[0] during
     *     URL parsing.
     * @param array $config The configuration of the plugin namespace as outlined in
     *     the class introduction
     */
    public static function register_namespace(string $namespace, array $config)
    {
        self::init();
        if (array_key_exists($namespace, self::$registry)) {
            throw new midcom_error("Tried to register the plugin namespace {$namespace}, but it is already registered.");
        }
        self::$registry[$namespace] = $config;
    }

    public static function get_config(string $namespace, string $name) : ?array
    {
        self::init();
        if (empty(self::$registry[$namespace][$name])) {
            return null;
        }
        if (empty(self::$registry[$namespace][$name]['class']) || !class_exists(self::$registry[$namespace][$name]['class'])) {
            throw new midcom_error("Failed to load the plugin {$namespace}/{$name}, implementation class not available.");
        }
        return self::$registry[$namespace][$name];
    }

    /**
     * Register the plugin namespaces provided from MidCOM core.
     */
    private static function init()
    {
        if (self::$registry) {
            return;
        }
        self::$registry = [
            '__ais' => [
                'folder' => [
                    'class' => midcom_admin_folder_management::class,
                ],
                'rcs' => [
                    'class' => midcom_admin_rcs_plugin::class,
                ],
                'imagepopup' => [
                    'class' => midcom_helper_imagepopup_viewer::class,
                ],
                'help' => [
                    'class' => midcom_admin_help_help::class,
                ],
            ]
        ];

        // Load plugins registered via component manifests
        $plugins = midcom::get()->componentloader->get_all_manifest_customdata('request_handler_plugin');
        $plugins['asgard'] = [
            'class' => midgard_admin_asgard_plugin::class,
        ];

        $customdata = midcom::get()->componentloader->get_all_manifest_customdata('asgard_plugin');
        foreach ($customdata as $component => $plugin_config) {
            $plugins["asgard_{$component}"] = $plugin_config;
        }

        self::register_namespace('__mfa', $plugins);
    }
}