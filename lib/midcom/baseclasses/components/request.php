<?php
/**
 * @package midcom.baseclasses
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use midcom\routing\loader;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Router;

/**
 * Base class to encapsulate the component's routing, instantiated by the MidCOM
 * component interface.
 *
 * It provides an automatic mechanism for URL processing and validation, minimizing
 * the required work to get a new component running.
 *
 * <b>Request switch configuration</b>
 *
 * The class uses an array which aids in URL-to-function mapping. Handlers are distinguished
 * by the "URL-space" they handle. For each handler three functions are needed, one for the
 * request handle decision ("Can Handle Phase"), one for the
 * request handling ("Handle Phase") and one for output ("Output Phase"). These handlers refer to
 * another class which gets instantiated if necessary.
 *
 * All request handlers are contained in a single array, whose keys identify the various switch
 * configurations. These identifiers are only for informational purposes (they appear in the
 * debug log), so you could just resort to automatic array index numbering using the []
 * operator.
 *
 * Each request handler definition in the switch must contain these key/value pairs:
 *
 * - <b>mixed fixed_args:</b> This is either a string or an array and defines the fixed
 *   arguments that have to be present at the beginning of the URL to be handled. A
 *   string denotes a single argument, an array is used if more than one fixed argument
 *   is needed. If you do not have any fixed arguments, set this parameter to null, which
 *   is the default.
 * - <b>int variable_args:</b> Usually, there are a number of variables in the URL, like
 *   article IDs, or article names. This can be 0, indicating that no variable arguments are
 *   required, which is the default. For an unlimited number of variable_args set it to -1.
 *
 * - <b>mixed handler:</b> This is a definition of what method should be invoked to
 *   handle the request using the callable array syntax. The first array member must contain
 *   the name of an existing class.
 *   This value has no default and must be set. The actual methods called will have either an
 *   _handler_ or _show_ prefix.
 *
 * Example:
 *
 * <code>
 * $this->_request_switch[] = [
 *     'fixed_args' => ['registrations', 'view'],
 *     'variable_args' => 1,
 *     'handler' => ['net_nemein_registrations_regadmin', 'view']
 * ];
 * </code>
 *
 * This definition is usually located in either in the routes.inc file (preferred)
 * or the _on_initialize event handler.
 *
 * The handlers are processed in the order which they have been added to the array. This has
 * several implications:
 *
 * First, if you have two handlers with similar signatures, the latter might be hidden by the
 * former, for example the handler 'view' with two variable arguments includes the urls that
 * could match 'view', 'registration' with a single variable argument if processed in this order.
 * In these cases you have to add the most specific handlers first.
 *
 * Second, for performance reasons, you should try to add the handler which will be accessed
 * most of the time first (unless it conflicts with the first rule above), as this will speed
 * up average request processing.
 *
 * It is recommended that you add string-based identifiers to your handlers. This makes
 * debugging of URL parsing much easier, as MidCOM logs which request handlers are checked
 * in debug mode. The above example could use something like
 * `$this->_request_switch['registrations-view']` to do so. Just never prefix one of your
 * handlers with one underscores, this namespace is reserved for MidCOM usage.
 *
 * <b>Callback method signatures</b>
 *
 * <code>
 * /**
 *  * Exec handler example, with Docblock:
 *  * @param mixed $handler_id The ID of the handler.
 *  * @param array $args The argument list.
 *  * @param array &$data The local request data.
 *  {@*}
 * public function _handler_xxx ($handler_id, array $args, array &$data) {}
 *
 * /**
 *  * Show handler example, with Docblock:
 *  * @param mixed $handler_id The ID of the handler.
 *  * @param array &$data The local request data.
 *  {@*}
 * public function _show_xxx ($handler_id, array &$data) {}
 * </code>
 *
 * The two callbacks match the regular processing sequence of MidCOM.
 *
 * The main callback _handle_xxx is mandatory, _show_xxx is optional since the handle method can
 * return a response directly.
 *
 * As you can see, the system provides you with an easy way to keep track of the data
 * of your request, without having dozens of members for trivial flags. This data array
 * is automatically registered in the custom component context under the name
 * 'request_data', making it easily available within style elements as $data
 *
 * The data array can also be accessed by using the $_request_data member of this class,
 * which is the original data storage location for the request data.
 *
 * Note that the request data, for ease of use, already contains the L10n
 * Databases of the Component and MidCOM itself located in this class. They are stored
 * as 'l10n' and 'l10n_midcom'. Also available as 'config' is the current component
 * configuration and 'topic' will hold the current content topic.
 *
 * <b>Automatic handler class instantiation</b>
 *
 * If you specify a class name instead of a class instance as an exec handler, MidCOM will
 * automatically create an instance of that class type and initialize it. These
 * so-called handler classes must be a subclass of midcom_baseclasses_components_handler.
 *
 * The subclasses you create should look about this:
 *
 * <code>
 * class my_handler extends midcom_baseclasses_components_handler
 * {
 *     public function _on_initialize()
 *     {
 *         // Add class initialization code here, all members have been prepared
 *     }
 * }
 * </code>
 *
 * The two methods for each handler have the same signature as if they were in the
 * same class.
 *
 *
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
 * @package midcom.baseclasses
 */
abstract class midcom_baseclasses_components_request extends midcom_baseclasses_components_base
{
    /**
     * The topic for which we are handling a request.
     *
     * @var midcom_db_topic
     */
    public $_topic = null;

    /**
     * The current configuration.
     *
     * @var midcom_helper_configuration
     */
    public $_config = null;

    /**
     * Request specific data storage area. Registered in the component context
     * as ''.
     *
     * @var array
     */
    public $_request_data = [];

    /**
     * The node toolbar for the current request context. Not available during the can_handle
     * phase.
     *
     * @var midcom_helper_toolbar
     * @see midcom_services_toolbars
     */
    public $_node_toolbar = null;

    /**
     * The view toolbar for the current request context. Not available during the can_handle
     * phase.
     *
     * @var midcom_helper_toolbar
     * @see midcom_services_toolbars
     */
    public $_view_toolbar = null;

    /**
     * This variable keeps track of the registered plugin namespaces. It maps namespace
     * identifiers against plugin config lists. This is used during can_handle startup
     * to determine whether the request has to be relayed to a plugin.
     *
     * You have to use the register_plugin_namespace() member function during the
     * _on_initialize event to register plugin namespaces.
     *
     * @var array
     */
    private static $_plugin_namespace_config = [];

    private $active_plugin;

    private $loader;

    /**
     * @var Router
     */
    protected $router;

    /**
     * Request execution switch configuration.
     *
     * The main request switch data. You need to set this during construction,
     * it will be post-processed afterwards during initialize to provide a unified
     * set of data. Therefore you must not modify this switch after construction.
     *
     * @var array
     */
    protected $_request_switch = [];

    /**
     * The handler which has been declared to be able to handle the
     * request. The array will contain the original index of the handler in the
     * '_route' member for backtracking purposes. The variable argument list will be
     * placed into 'args' for performance reasons.
     *
     * @var Array
     */
    public $_handler = null;

    /**
     * Initializes the class, only basic variable assignment.
     *
     * Put all further initialization work into the _on_initialize event handler.
     *
     * @param midcom_db_topic $topic The topic we are working on
     * @param midcom_helper_configuration $config The currently active configuration.
     */
    final public function __construct(midcom_db_topic $topic, $config)
    {
        $this->_topic = $topic;
        $this->_config = $config;
    }

    /**
     * Initializes the request handler class, called by the component interface after
     * instantiation.
     *
     * @param string $component The name of the component.
     */
    public function initialize($component)
    {
        $this->_component = $component;
        midcom_core_context::get()->set_custom_key('request_data', $this->_request_data);

        $this->_request_data['config'] = $this->_config;
        $this->_request_data['topic'] = null;
        $this->_request_data['l10n'] = $this->_l10n;
        $this->_request_data['l10n_midcom'] = $this->_l10n_midcom;

        if (empty(self::$_plugin_namespace_config)) {
            $this->_register_core_plugin_namespaces();
        }

        $loader = new loader;
        $this->_request_switch = $loader->get_legacy_routes($component);

        $this->_on_initialize();
        $this->router = $this->get_router();
    }

    /**
     * CAN_HANDLE Phase interface, checks against all registered handlers if a valid
     * one can be found. You should not need to override this, instead, use the
     * HANDLE Phase for further checks.
     *
     * @param array $argv The argument list
     * @return boolean Indicating whether the request can be handled by the class, or not.
     */
    public function can_handle(Request $request)
    {
        $argv = $request->attributes->get('argv', []);
        $prefix = midcom_core_context::get()->parser->get_url();

        // Check if we need to start up a plugin.
        if (   count($argv) > 1
            && array_key_exists($argv[0], self::$_plugin_namespace_config)
            && array_key_exists($argv[1], self::$_plugin_namespace_config[$argv[0]])) {
            $namespace = array_shift($argv);
            $name = array_shift($argv);
            $prefix .= $namespace . '/' . $name . '/';
            $this->_load_plugin($namespace, $name);
        }

        if (empty($argv)) {
            $url = '/';
        } else {
            $url = '/' . implode('/', $argv) . '/';
        }
        $this->router->getContext()->fromRequest($request);
        try {
            $result = $this->router->match($url);
            $this->router->getContext()->setBaseUrl(substr($prefix, 0, -1));
            $this->_prepare_handler($result);
            return true;
        } catch (ResourceNotFoundException $e) {
            // No match
            return false;
        }
    }

    /**
     * @param string $component
     * @return \Symfony\Component\Routing\Router
     */
    public function get_router($component = null)
    {
        $loader = new loader;
        $cache = true;
        $resource = $this->_component;
        if ($component) {
            $resource = $component;
        } elseif (!empty($this->_request_switch)) {
            $resource = $this->_request_switch;
            $cache = false;
        }
        if ($cache) {
            $config = [
                'cache_dir' => midcom::get()->config->get('cache_base_directory') . 'routing',
                'matcher_cache_class' => 'loader__' . str_replace('.', '_', $resource),
                'generator_cache_class' => 'generator__' . str_replace('.', '_', $resource)
            ];
        } else {
            $config = [];
        }

        return new Router($loader, $resource, $config);
    }


    /**
     * Prepares the handler callback for execution.
     * This will create the handler class instance if required.
     *
     * @param array $request
     * @throws midcom_error
     */
    private function _prepare_handler(array $request)
    {
        $this->_handler =& $request;
        $args = [];
        foreach ($request as $name => $value) {
            if (substr($name, 0, 1) !== '_') {
                $args[] = $value;
            }
        }
        $request['args'] = $args;

        if (strpos($request['_controller'], '::') === false) {
            // Support for handlers in request class (deprecated)
            $request['handler'] = [&$this, $request['handler']];
            return;
        }
        $request['handler'] = explode('::', $request['_controller'], 2);

        $classname = $request['handler'][0];
        if (!class_exists($classname)) {
            throw new midcom_error("Failed to create a class instance of the type {$classname}, the class is not declared.");
        }

        $request['handler'][0] = new $classname();
        if (!$request['handler'][0] instanceof midcom_baseclasses_components_handler) {
            throw new midcom_error("Failed to create a class instance of the type {$classname}, it is no subclass of midcom_baseclasses_components_handler.");
        }

        //For plugins, set the component name explicitly so that L10n and config can be found
        if (!empty($this->active_plugin)) {
            $request['handler'][0]->_component = $this->active_plugin;
        }

        $request['handler'][0]->initialize($this, $this->router);
    }

    /**
     * Handle the request using the handler determined by the can_handle check.
     *
     * Before doing anything, it will call the _on_handle event handler to allow for
     * generic request preparation.
     *
     * @return midcom_response|null The response object (or null in the case of old-style handlers)
     * @see _on_handle()
     */
    public function handle()
    {
        // Init
        $handler = $this->_handler['handler'][0];

        // Update the request data
        $this->_request_data['topic'] = $this->_topic;
        $this->_request_data['router'] = $this->router;

        // Get the toolbars for both the main request object and the handler object.
        $this->_node_toolbar = midcom::get()->toolbars->get_node_toolbar();
        $this->_view_toolbar = midcom::get()->toolbars->get_view_toolbar();
        $handler->_node_toolbar = $this->_node_toolbar;
        $handler->_view_toolbar = $this->_view_toolbar;

        // Add the handler ID to request data
        $this->_request_data['handler_id'] = $this->_handler['_route'];

        if (!array_key_exists('plugin_name', $this->_request_data)) {
            // We're not using a plugin handler, so call the general handle event handler
            $this->_on_handle($this->_handler['_route'], $this->_handler['args']);
        }
        $method = "_handler_{$this->_handler['handler'][1]}";
        $result = $handler->$method($this->_handler['_route'], $this->_handler['args'], $this->_request_data);

        if ($handler instanceof midcom_baseclasses_components_handler) {
            $handler->populate_breadcrumb_line();
        }

        $this->_on_handled($this->_handler['_route'], $this->_handler['args']);

        return $result;
    }

    /**
     * Display the content, it uses the handler as determined by can_handle.
     */
    public function show()
    {
        if (empty($this->_handler['handler'])) {
            return;
        }

        // Call the handler:
        $handler = $this->_handler['handler'][0];
        $method = "_show_{$this->_handler['handler'][1]}";

        $handler->$method($this->_handler['_route'], $this->_request_data);
    }

    /**
     * Initialization event handler, called at the end of the initialization process
     *
     * Use this function instead of the constructor for all initialization work. You
     * can safely populate the request switch from here.
     *
     * You should not do anything else then general startup work, as this callback
     * executes <i>before</i> the can_handle phase. You don't know at this point
     * whether you are even able to handle the request. Thus, anything that is specific
     * to your request (like HTML HEAD tag adds) must not be done here. Use _on_handle
     * instead.
     */
    public function _on_initialize()
    {
    }

    /**
     * Component specific initialization code for the handle phase. The name of the request
     * handler is passed as an argument to the event handler.
     *
     * Note, that while you have the complete information around the request (handler id,
     * args and request data) available, it is strongly discouraged to handle everything
     * here. Instead, stay with the specific request handler methods as far as sensible.
     *
     * @param mixed $handler The ID (array key) of the handler that is responsible to handle
     *   the request.
     * @param array $args The argument list.
     */
    public function _on_handle($handler, array $args)
    {
    }

    public function _on_handled($handler, array $args)
    {
    }

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
    public function register_plugin_namespace($namespace, array $config)
    {
        if (array_key_exists($namespace, self::$_plugin_namespace_config)) {
            throw new midcom_error("Tried to register the plugin namespace {$namespace}, but it is already registered.");
        }
        self::$_plugin_namespace_config[$namespace] = $config;
    }

    /**
     * Load the specified namespace/plugin combo.
     *
     * Any problem to load a plugin will be logged accordingly and false will be returned.
     * Critical errors will trigger midcom_error.
     *
     * @todo Allow for lazy plugin namespace configuration loading (using a callback)!
     *     This will make things more performant and integration with other components
     *     much easier.
     *
     * @param string $namespace The plugin namespace to use.
     * @param string $name The plugin to load from the namespace.
     */
    private function _load_plugin($namespace, $name)
    {
        debug_add("Loading the plugin {$namespace}/{$name}");
        $plugin_config = self::$_plugin_namespace_config[$namespace][$name];

        if (empty($plugin_config['class']) || !class_exists($plugin_config['class'])) {
            throw new midcom_error("Failed to load the plugin {$namespace}/{$name}, implementation class not available.");
        }

        // Load the configuration into the request data, add the configured plugin name as
        // well so that URLs can be built.
        if (array_key_exists('config', $plugin_config)) {
            $this->_request_data['plugin_config'] = $plugin_config['config'];
        } else {
            $this->_request_data['plugin_config'] = null;
        }
        $this->_request_data['plugin_name'] = $name;

        // Load remaining configuration, and prepare the plugin,
        // errors are logged by the callers.
        $plugin = new $plugin_config['class']();
        $this->router = $this->get_router($plugin->_component);
        $plugin->initialize($this, $this->router);
        $this->active_plugin = $plugin->_component;
    }

    /**
     * Register the plugin namespaces provided from MidCOM core.
     */
    private function _register_core_plugin_namespaces()
    {
        $this->register_plugin_namespace(
            '__ais', [
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
        );

        // Load plugins registered via component manifests
        $plugins = midcom::get()->componentloader->get_all_manifest_customdata('request_handler_plugin');
        $plugins['asgard'] = [
            'class' => midgard_admin_asgard_plugin::class,
        ];

        $customdata = midcom::get()->componentloader->get_all_manifest_customdata('asgard_plugin');
        foreach ($customdata as $component => $plugin_config) {
            $plugins["asgard_{$component}"] = $plugin_config;
        }

        $this->register_plugin_namespace('__mfa', $plugins);
    }
}
