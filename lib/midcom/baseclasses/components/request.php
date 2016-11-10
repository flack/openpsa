<?php
/**
 * @package midcom.baseclasses
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Base class to encapsulate a request to the component, instantiated by the MidCOM
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
 * request handling ("Handle Phase") and one for output ("Output Phase"). These handlers can
 * either be contained in this class or refer to another class which gets instantiated, if
 * necessary.
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
 * - <b>boolean no_cache:</b> For those cases where you want to prevent a certain "type" of request
 *   being cached. Set to false by default.
 * - <b>int expires:</b> Set the default expiration time of a given type of request. The default -1
 *   is used to indicate no expiration setting. Any positive integer will cause its value to
 *   be passed to the caching engine, indicating the expiration time in seconds.
 * - <b>mixed handler:</b> This is a definition of what method should be invoked to
 *   handle the request. You have two options here. First you can refer to a method of this
 *   request handler class, in that case you just supply the name of the method. Alternatively,
 *   you can refer to an external class for request processing using an array syntax. The
 *   first array member must either contain the name of an existing class or a reference to
 *   an already instantiated class. This value has
 *   no default and must be set. The actual methods called will have either an _handle_ or _show_
 *   prefixed to the exec_handler value, respectively. See below for automatic handler instances,
 *   the preferred way to set things up.
 *
 * Example:
 *
 * <code>
 * $this->_request_switch[] = Array
 * (
 *     'fixed_args' => Array ('registrations', 'view'),
 *     'variable_args' => 1,
 *     'no_cache' => false,
 *     'expires' => -1,
 *     'handler' => 'view_registration'
 *     //
 *     // Alternative, use a class with automatic instantiation:
 *     // 'handler' => Array('net_nemein_registrations_regadmin', 'view')
 *     //
 *     // Alternative, use existing class (first parameter must be a reference):
 *     // 'handler' => Array($regadmin, 'view')
 * );
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
 *  * can_handle example, with Docblock:
 *  * @param mixed $handler_id The ID of the handler.
 *  * @param array $args The argument list.
 *  * @param array &$data The local request data.
 *  * @return boolean True if the request can be handled, false otherwise.
 *  {@*}
 * public function _can_handle_xxx ($handler_id, array $args, array &$data) {}
 *
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
 * The three callbacks match the regular processing sequence of MidCOM.
 *
 * <i>_can_handle_xxx notes:</i> For ease of use,
 * the _can_handle_xxx callback is optional, it will only be called if the method actually
 * exists. Normally you want to override this only if you request handler can hide stuff
 * which is not under the control of your topic. A prominent example is a handler definition
 * which has only a single variable argument. It would hide all subtopics if you don't check
 * what objects actually belong to you, and what not.
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
 * Note that the request data, for ease of use, already contains references to the L10n
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
 *         // Add class initialization code here, all members have
 *         // been prepared, and the instance is already stable, so
 *         // you can safely work with references to $this here.
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
 * It must define an additional function, called get_plugin_handlers(). It has to return
 * an array of standard request handler declarations. Both handler identifiers and
 * argument lists are <i>relative</i> to the base URL of the plugin (see below),
 * not to the component running the problem. You are thus completely location independent.
 * The handler callback must be statically callable.
 *
 * <i>Example: Plugin handler callback</i>
 *
 * <code>
 * public function get_plugin_handlers()
 * {
 *     return Array
 *     (
 *          'metadata' => array
 *          (
 *              'handler' => array('midcom_admin_folder_handler_metadata', 'metadata'),
 *              'fixed_args' => array ('metadata'),
 *              'variable_args' => 1,
 *          ),
 *         // ...
 *     );
 * }
 * </code>
 *
 * As outlined above, plugins are managed in a two-level hierarchy. First, there is
 * the plugin identifier, second the class identifier. When registering a plugin,
 * these two are specified. The request handlers obtained by the above callback are
 * automatically expanded to match the plugin namespace.
 *
 * <i>Example: Plugin registration</i>
 *
 * <code>
 * $this->register_plugin_namespace
 * (
 *     '__ais',
 *     Array
 *     (
 *         'folder' => Array
 *         (
 *             'class' => 'midcom_admin_folder_management',
 *             'src' => 'file:/midcom/admin/folder/management.php',
 *             'name' => 'Folder administration',
 *             'config' => null,
 *         ),
 *     )
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
 * - string plugin_namespace: The plugin namespace defined when registering.
 * - string plugin_anchorprefix: A plugin-aware version of
 *   MIDCOM_CONTEXT_ANCHORPREFIX pointing to the root URL of the plugin.
 *
 * @package midcom.baseclasses
 */
abstract class midcom_baseclasses_components_request extends midcom_baseclasses_components_base
{
    /**#@+
     * Request state variable, set during startup. There should be no need to change it
     * in most cases.
     */

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
    public $_request_data = array();

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
    private static $_plugin_namespace_config = array();

    /**
     * The controlling class for the active plugin, if any
     *
     * @var midcom_baseclasses_components_plugin
     */
    private $_active_plugin;

    /**#@-*/

    /**
     * Request execution switch configuration.
     *
     * The main request switch data. You need to set this during construction,
     * it will be post-processed afterwards during initialize to provide a unified
     * set of data. Therefore you must not modify this switch after construction.
     *
     * @var array
     */
    public $_request_switch = array();

    /**#@+
     * Internal request handling state variable, these are considered read-only for
     * all purposes (except this base class).
     */

    /**
     * This is a reference to the handler which declared to be able to handle the
     * request. The array will contain the original index of the handler in the
     * 'id' member for backtracking purposes. The variable argument list will be
     * placed into 'args' for performance reasons.
     *
     * @var Array
     */
    public $_handler = null;

    /**#@-*/

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
        if (!midcom::get()->dbclassloader->is_midcom_db_object($topic)) {
            $this->_topic = midcom::get()->dbfactory->convert_midgard_to_midcom($topic);
        } else {
            $this->_topic = $topic;
        }
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

        $manifest = midcom::get()->componentloader->manifests[$this->_component];
        if (!empty($manifest->extends)) {
            $this->_request_switch = midcom_baseclasses_components_configuration::get($manifest->extends, 'routes');
        }

        $this->_request_switch = array_merge($this->_request_switch, midcom_baseclasses_components_configuration::get($this->_component, 'routes'));

        $this->_on_initialize();
    }

    /**
     * Post-process the initial information as set by the constructor.
     * It fills all missing fields with sensible defaults, see the class introduction for
     * details.
     */
    public function _prepare_request_switch()
    {
        foreach ($this->_request_switch as $key => &$value) {
            if (empty($value['fixed_args'])) {
                $value['fixed_args'] = array();
            } else {
                $value['fixed_args'] = (array) $value['fixed_args'];
            }

            if (!array_key_exists('variable_args', $value)) {
                $value['variable_args'] = 0;
            }

            if (is_string($value['handler'])) {
                $this->_request_switch[$key]['handler'] = array(&$this, $value['handler']);
            }

            if (   !array_key_exists('expires', $value)
                || !is_integer($value['expires'])
                || $value['expires'] < -1) {
                $this->_request_switch[$key]['expires'] = -1;
            }
            if (!array_key_exists('no_cache', $value)) {
                $this->_request_switch[$key]['no_cache'] = false;
            }
        }
    }

    /**
     * CAN_HANDLE Phase interface, checks against all registered handlers if a valid
     * one can be found. You should not need to override this, instead, use the
     * HANDLE Phase for further checks.
     *
     * If available, the function calls the _can_handle callback of the event handlers
     * which potentially match the argument declaration.
     *
     * @param int $argc The argument count
     * @param array $argv The argument list
     * @return boolean Indicating whether the request can be handled by the class, or not.
     */
    public function can_handle($argc, $argv)
    {
        // Call the general can_handle event handler
        $result = $this->_on_can_handle($argc, $argv);
        if (!$result) {
            return false;
        }

        // Check if we need to start up a plugin.
        if (   $argc > 1
            && array_key_exists($argv[0], self::$_plugin_namespace_config)
            && array_key_exists($argv[1], self::$_plugin_namespace_config[$argv[0]])) {
            $namespace = $argv[0];
            $plugin = $argv[1];
            debug_add("Loading the plugin {$namespace}/{$plugin}");
            $this->_load_plugin($namespace, $plugin);
        }

        $this->_prepare_request_switch();

        foreach ($this->_request_switch as $key => $request) {
            if (!$this->_validate_route($request, $argc, $argv)) {
                continue;
            }
            $fixed_args_count = count($request['fixed_args']);
            $this->_handler =& $this->_request_switch[$key];

            $this->_handler['id'] = $key;
            $this->_handler['args'] = array_slice($argv, $fixed_args_count);

            // Prepare the handler object
            $this->_prepare_handler();

            // If applicable, run the _can_handle check for the handler in question.
            $handler =& $this->_handler['handler'][0];
            $method = "_can_handle_{$this->_handler['handler'][1]}";

            if (   method_exists($handler, $method)
                && !$handler->$method($this->_handler['id'], $this->_handler['args'], $this->_request_data)) {
                // This can_handle failed, allow next one to take over if there is one
                continue;
            }
            return true;
        }
        // No match
        return false;
    }

    private function _validate_route(array $request, $argc, array $argv)
    {
        $fixed_args_count = count($request['fixed_args']);
        $variable_args_count = $request['variable_args'];
        $total_args_count = $fixed_args_count + $variable_args_count;

        if (   ($argc != $total_args_count && ($variable_args_count >= 0))
            || $fixed_args_count > $argc) {
            return false;
        }

        // Check the static parts
        if (array_slice($argv, 0, $fixed_args_count) != $request['fixed_args']) {
            return false;
        }

        // Validation for variable args
        for ($i = 0; $i < $variable_args_count; $i++) {
            // rule exists?
            if (!empty($request['validation'][$i])) {
                $param = $argv[$fixed_args_count + $i];
                // by default we use an OR condition
                // so as long as one rules succeeds, we are ok..
                $success = false;
                foreach ($request['validation'][$i] as $rule) {
                    // rule is a callable function, like mgd_is_guid or is_int
                    if (   is_callable($rule)
                        && $success = call_user_func($rule, $param)) {
                        break;
                    }
                }
                // validation failed, we can stop here
                if (!$success) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Handle the request using the handler determined by the can_handle check.
     *
     * Before doing anything, it will call the _on_handle event handler to allow for
     * generic request preparation.
     *
     * @return boolean Indicating whether the request was handled successfully.
     * @see _on_handle()
     */
    public function handle()
    {
        // Init
        $handler = $this->_handler['handler'][0];

        // Update the request data
        $this->_request_data['topic'] = $this->_topic;

        // Get the toolbars for both the main request object and the handler object.
        $this->_node_toolbar = midcom::get()->toolbars->get_node_toolbar();
        $this->_view_toolbar = midcom::get()->toolbars->get_view_toolbar();
        $handler->_node_toolbar = $this->_node_toolbar;
        $handler->_view_toolbar = $this->_view_toolbar;

        // Add the handler ID to request data
        $this->_request_data['handler_id'] = $this->_handler['id'];

        if (array_key_exists('plugin_namespace', $this->_request_data)) {
            // Prepend the plugin anchor prefix so that it is complete.
            $this->_request_data['plugin_anchorprefix'] =
            midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX)
            . $this->_request_data['plugin_anchorprefix'];
        } else {
            // We're not using a plugin handler, so call the general handle event handler
            $result = $this->_on_handle($this->_handler['id'], $this->_handler['args']);
            if ($result === false) {
                debug_add('_on_handle for ' . $this->_handler['id'] . ' returned false. This is deprecated, please use exceptions instead');
                return false;
            }
        }
        $method = "_handler_{$this->_handler['handler'][1]}";
        $result = $handler->$method($this->_handler['id'], $this->_handler['args'], $this->_request_data);

        if ($result === false) {
            debug_add($method . ' (' . $this->_handler['id'] . ') returned false. This is deprecated, please use exceptions instead');
            return false;
        }

        if (is_a($handler, 'midcom_baseclasses_components_handler')) {
            $handler->populate_breadcrumb_line();
        }

        // Check whether this request should not be cached by default:
        if ($this->_handler['no_cache'] == true) {
            midcom::get()->cache->content->no_cache();
        }
        if ($this->_handler['expires'] >= 0) {
            midcom::get()->cache->content->expires($this->_handler['expires']);
        }

        $this->_on_handled($this->_handler['id'], $this->_handler['args']);

        return $result;
    }

    /**
     * Prepares the handler callback for execution.
     * This will create the handler class instance if required.
     */
    public function _prepare_handler()
    {
        if (is_string($this->_handler['handler'][0])) {
            $classname = $this->_handler['handler'][0];
            if (!class_exists($classname)) {
                throw new midcom_error("Failed to create a class instance of the type {$classname}, the class is not declared.");
            }

            $this->_handler['handler'][0] = new $classname();
            if (!is_a($this->_handler['handler'][0], 'midcom_baseclasses_components_handler')) {
                throw new midcom_error("Failed to create a class instance of the type {$classname}, it is no subclass of midcom_baseclasses_components_handler.");
            }

            //For plugins, set the component name explicitly so that L10n and config can be found
            if (isset($this->_handler['plugin'])) {
                $this->_active_plugin->initialize($this);
                $this->_handler['handler'][0]->_component = $this->_handler['plugin'];
            }

            $this->_handler['handler'][0]->initialize($this);

            midcom_core_context::get()->set_key(MIDCOM_CONTEXT_HANDLERID, $this->_handler['id']);
        }
    }

    /**
     * Display the content, it uses the handler as determined by can_handle.
     *
     * Before doing anything, it will call the _on_show event handler to allow for
     * generic preparation. If this function returns false, the regular output
     * handler will not be called.
     *
     * @see _on_show()
     */
    public function show()
    {
        // Call the event handler
        if (!$this->_on_show($this->_handler['id'])) {
            debug_add('The _on_show event handler returned false, aborting.');
            return;
        }

        if (empty($this->_handler['handler'])) {
            return;
        }

        // Call the handler:
        $handler = $this->_handler['handler'][0];
        $method = "_show_{$this->_handler['handler'][1]}";

        $handler->$method($this->_handler['id'], $this->_request_data);

        $this->_on_shown($this->_handler['id']);
    }

    /**#@+
     * Event Handler callback.
     */

    /**
     * Initialization event handler, called at the end of the initialization process
     * immediately before the request handler configuration is read.
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
     * Component specific initialization code for the can_handle phase.
     *
     * This is run before the actual evaluation of the request switch. Components can use
     * this phase to load plugins that need registering in the request switch on demand.
     *
     * The advantage of this is that it is not necessary to load all plugins completely,
     * you just have to know the "root" URL space (f.x. "/plugins/$name/").
     *
     * If you discover that you cannot handle the request already at this stage, return false
     * The remainder of the can_handle phase is skipped then, returning the URL processing back
     * to MidCOM.
     *
     * @param int $argc The argument count as passed by the Core.
     * @param array $argv The argument list.
     * @return boolean Return false to abort the handle phase, true to continue normally.
     */
    public function _on_can_handle($argc, array $argv)
    {
        return true;
    }

    /**
     * Generic output initialization code. The return value lets you control whether the
     * output method associated with the handler declaration is called, return false to
     * override this automatism, true, the default, will call the output handler normally.
     *
     * @param mixed $handler The ID (array key) of the handler that is responsible to handle
     *   the request.
     * @return boolean Return false to override the regular component output.
     */
    public function _on_show($handler)
    {
        return true;
    }

    public function _on_shown($handler)
    {
    }

    /**#@-*/

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
     * @param string $plugin The plugin to load from the namespace.
     */
    public function _load_plugin($namespace, $plugin)
    {
        $this->_load_plugin_class($namespace, $plugin);

        $plugin_config = self::$_plugin_namespace_config[$namespace][$plugin];

        // Load the configuration into the request data, add the configured plugin name as
        // well so that URLs can be built.
        if (array_key_exists('config', $plugin_config)) {
            $this->_request_data['plugin_config'] = $plugin_config['config'];
        } else {
            $this->_request_data['plugin_config'] = null;
        }
        $this->_request_data['plugin_name'] = $plugin;
        $this->_request_data['plugin_namespace'] = $namespace;

        // This cannot be fully prepared at this point, as the ANCHORPREFIX is
        // undefined up until to the handle phase. It thus completed in handle
        // by prefixing the local anchorprefix.
        $this->_request_data['plugin_anchorprefix'] = "{$namespace}/{$plugin}/";

        // Load remaining configuration, and prepare the plugin,
        // errors are logged by the callers.
        $this->_active_plugin = new $plugin_config['class']();
        $this->_active_plugin->_component = preg_replace('/([a-z0-9]+)_([a-z0-9]+)_([a-z0-9]+).+/', '$1.$2.$3', $plugin_config['class']);

        $this->_prepare_plugin($namespace, $plugin);
    }

    /**
     * Loads the file/snippet necessary for a given plugin, according to its configuration.
     *
     * @param string $namespace The plugin namespace to use.
     * @param string $plugin The plugin to load from the namespace.
     */
    public function _load_plugin_class($namespace, $plugin)
    {
        $plugin_config = self::$_plugin_namespace_config[$namespace][$plugin];

        // If class can be autoloaded, we're done here
        if (class_exists($plugin_config['class'])) {
            return;
        }

        if ($i = strpos($plugin_config['src'], ':')) {
            $method = substr($plugin_config['src'], 0, $i);
            $src = substr($plugin_config['src'], $i + 1);
        } else {
            $method = 'snippet';
            $src = $plugin_config['src'];
        }

        switch ($method) {
            case 'file':
                require_once MIDCOM_ROOT . $src;
                break;

            case 'component':
                midcom::get()->componentloader->load($src);
                break;

            case 'snippet':
                midcom_helper_misc::include_snippet_php($src);
                break;

            default:
                throw new midcom_error("The plugin loader method {$method} is unknown, cannot continue.");
        }

        if (!class_exists($plugin_config['class'])) {
            throw new midcom_error("Failed to load the plugin {$namespace}/{$plugin}, implementation class not available.");
        }
    }

    /**
     * Prepares the actual plugin by adding all necessary information to the request
     * switch.
     *
     * @param string $namespace The plugin namespace to use.
     * @param string $plugin The plugin to load from the namespace.
     */
    public function _prepare_plugin($namespace, $plugin)
    {
        $handlers = $this->_active_plugin->get_plugin_handlers();

        foreach ($handlers as $identifier => $handler_config) {
            // First, update the fixed args list (be tolerant here)
            if (!array_key_exists('fixed_args', $handler_config)) {
                $handler_config['fixed_args'] = array($namespace, $plugin);
            } elseif (!is_array($handler_config['fixed_args'])) {
                $handler_config['fixed_args'] = array($namespace, $plugin, $handler_config['fixed_args']);
            } else {
                $handler_config['fixed_args'] = array_merge
                (
                    array($namespace, $plugin),
                    $handler_config['fixed_args']
                );
            }
            $handler_config['plugin'] = $this->_active_plugin->_component;

            $this->_request_switch["__{$namespace}-{$plugin}-{$identifier}"] = $handler_config;
        }
    }

    /**
     * Register the plugin namespaces provided from MidCOM core.
     */
    private function _register_core_plugin_namespaces()
    {
        $this->register_plugin_namespace
        (
            '__ais',
            array
            (
                'folder' => array
                (
                    'class' => 'midcom_admin_folder_management',
                    'name' => 'Folder administration',
                    'config' => null,
                ),
                'rcs' => array
                (
                    'class' => 'no_bergfald_rcs_handler',
                    'name' => 'Revision control',
                    'config' => null,
                ),
                'imagepopup' => array
                (
                    'class' => 'midcom_helper_imagepopup_viewer',
                    'name' => 'Image pop-up',
                    'config' => null,
                ),
                'help' => array
                (
                    'class' => 'midcom_admin_help_help',
                    'name' => 'On-site help',
                    'config' => null,
                ),
            )
        );

        // Centralized admin panel functionalities

        // Load plugins registered via component manifests
        $manifest_plugins = midcom::get()->componentloader->get_all_manifest_customdata('request_handler_plugin');
        $customdata = midcom::get()->componentloader->get_all_manifest_customdata('asgard_plugin');
        foreach ($customdata as $component => $plugin_config) {
            $manifest_plugins["asgard_{$component}"] = $plugin_config;
        }

        $hardcoded_plugins = array
        (
            'asgard' => array
            (
                'class' => 'midgard_admin_asgard_plugin',
                'name' => 'Asgard',
                'config' => null,
            ),
        );

        $this->register_plugin_namespace
        (
            '__mfa',
            array_merge($hardcoded_plugins, $manifest_plugins)
        );
    }
}
