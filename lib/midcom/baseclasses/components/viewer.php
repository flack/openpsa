<?php
/**
 * @package midcom.baseclasses
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use midcom\routing\loader;
use Symfony\Component\Routing\Router;
use midcom\routing\resolver;
use midcom\routing\plugin;

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
 *  * @param array $data The local request data.
 *  {@*}
 * public function _handler_xxx ($handler_id, array $args, array &$data) {}
 *
 * /**
 *  * Show handler example, with Docblock:
 *  * @param mixed $handler_id The ID of the handler.
 *  * @param array $data The local request data.
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
 * @package midcom.baseclasses
 */
class midcom_baseclasses_components_viewer extends midcom_baseclasses_components_base
{
    /**
     * The topic for which we are handling a request.
     *
     * @var midcom_db_topic
     */
    public $_topic;

    /**
     * The current configuration.
     *
     * @var midcom_helper_configuration
     */
    public $_config;

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
    public $_node_toolbar;

    /**
     * The view toolbar for the current request context. Not available during the can_handle
     * phase.
     *
     * @var midcom_helper_toolbar
     * @see midcom_services_toolbars
     */
    public $_view_toolbar;

    /**
     * @var midcom_baseclasses_components_plugin
     */
    private $active_plugin;

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
    private $_handler;

    /**
     * Initializes the class, only basic variable assignment.
     *
     * Put all further initialization work into the _on_initialize event handler.
     *
     * @param midcom_db_topic $topic The topic we are working on
     * @param midcom_helper_configuration $config The currently active configuration.
     * @param string $component The name of the component.
     */
    final public function __construct(midcom_db_topic $topic, midcom_helper_configuration $config, $component)
    {
        $this->_topic = $topic;
        $this->_config = $config;
        $this->_component = $component;

        $this->_request_data['config'] = $this->_config;
        $this->_request_data['topic'] = null;
        $this->_request_data['l10n'] = $this->_l10n;
        $this->_request_data['l10n_midcom'] = $this->_l10n_midcom;

        $loader = new loader;
        $this->_request_switch = $loader->get_legacy_routes($component);

        $this->_on_initialize();
        $this->router = resolver::get_router($this->_component, $this->_request_switch);
    }

    public function get_router() : Router
    {
        return $this->router;
    }

    /**
     * Prepares the handler callback for execution.
     * This will create the handler class instance if required.
     *
     * @param array $request
     * @throws midcom_error
     */
    public function prepare_handler(array &$request)
    {
        $this->_handler =& $request;

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
            $request['handler'][0]->_component = $this->active_plugin->_component;
        }

        $request['handler'][0]->initialize($this, $this->router);
        midcom_core_context::get()->set_custom_key('request_data', $this->_request_data);
    }

    /**
     * Handle the request using the handler determined by the can_handle check.
     *
     * Before doing anything, it will call the _on_handle event handler to allow for
     * generic request preparation.
     *
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
    public function register_plugin_namespace(string $namespace, array $config)
    {
        plugin::register_namespace($namespace, $config);
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
     */
    public function load_plugin(string $name, midcom_baseclasses_components_plugin $plugin, array $config)
    {
        // Load the configuration into the request data, add the configured plugin name as
        // well so that URLs can be built.
        $this->_request_data['plugin_config'] = $config['config'] ?? null;
        $this->_request_data['plugin_name'] = $name;

        // Load remaining configuration, and prepare the plugin,
        // errors are logged by the callers.
        $this->router = resolver::get_router($plugin->_component);
        $plugin->initialize($this, $this->router);
        $this->active_plugin = $plugin;
    }
}
