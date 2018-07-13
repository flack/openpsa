<?php
/**
 * @package midcom
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * MidCOM context handling
 *
 * Provides a basic context mechanism that allows each independent component
 * invocation to access its own context information.
 *
 * @package midcom
 */
class midcom_core_context
{
    /**
     * Holds the component context information.
     *
     * This is an array of arrays, the outer one indexed by context IDs, the
     * inner one indexed by context keys. Only valid of the system has left
     * the code-init phase.
     *
     * @var midcom_core_context[]
     */
    private static $_contexts = [];

    /**
     * Contains the ID of the currently active context
     *
     * @var int
     */
    private static $_currentcontext = 0;

    /**
     * The context's data
     *
     * @var array
     */
    private $_data = [
        MIDCOM_CONTEXT_ANCHORPREFIX => '',
        MIDCOM_CONTEXT_URI => '',
        MIDCOM_CONTEXT_ROOTTOPIC => null,
        MIDCOM_CONTEXT_ROOTTOPICID => null,
        MIDCOM_CONTEXT_CONTENTTOPIC => null,
        MIDCOM_CONTEXT_COMPONENT => null,
        MIDCOM_CONTEXT_SUBSTYLE => null,
        MIDCOM_CONTEXT_PAGETITLE => "",
        MIDCOM_CONTEXT_LASTMODIFIED => null,
        MIDCOM_CONTEXT_PERMALINKGUID => null,
        MIDCOM_CONTEXT_CUSTOMDATA => [],
        MIDCOM_CONTEXT_URLTOPICS => [],
        MIDCOM_CONTEXT_SHOWCALLBACK => null
    ];

    /**
     * The context's ID
     *
     * @var int
     */
    public $id;

    /**
     * The context's URL parser instance
     *
     * @var midcom_core_service_urlparser
     */
    public $parser;

    /**
     * Create and prepare a new component context.
     *
     * @param int $id Explicitly specify the ID for context creation (used during construction), this parameter is usually omitted.
     * @param midcom_db_topic $node Root node of the context
     */
    public function __construct($id = null, midcom_db_topic $node = null)
    {
        if (isset($_SERVER['REQUEST_URI'])) {
            $this->_data[MIDCOM_CONTEXT_URI] = $_SERVER['REQUEST_URI'];
        }
        if (is_object($node)) {
            $this->_data[MIDCOM_CONTEXT_ROOTTOPIC] = $node;
            $this->_data[MIDCOM_CONTEXT_ROOTTOPICID] = $node->id;
        }

        if (is_null($id)) {
            $id = count(self::$_contexts);
        }
        $this->id = $id;
        self::$_contexts[$id] =& $this;
    }

    /**
     * Marks context as current
     */
    public function set_current()
    {
        self::$_currentcontext = $this->id;
    }

    /**
     * Get context, either the current one or one designated by ID
     *
     * If the current context is requested and doesn't exist for some reason, it is automatically created
     *
     * @param int $id The context ID, if any
     * @return midcom_core_context The requested context, or false if not found
     */
    public static function & get($id = null)
    {
        if (is_null($id)) {
            $id = self::$_currentcontext;
            if (!isset(self::$_contexts[$id])) {
                self::$_contexts[$id] = new self($id);
            }
            return self::$_contexts[$id];
        }

        if (   $id < 0
            || $id >= count(self::$_contexts)) {
            debug_add("Could not get invalid context $id.", MIDCOM_LOG_WARN);
            $ret = false;
            return $ret;
        }

        return self::$_contexts[$id];
    }

    /**
     * Returns the complete context data array
     *
     * @todo This should be removed and places using this rewritten
     * @return midcom_core_context[] All contexts
     */
    public static function get_all()
    {
        return self::$_contexts;
    }

    /**
     * Access the MidCOM component context
     *
     * Returns Component Context Information associated to the component with the
     * context identified by $key.
     *
     * @param int $key  The key requested
     * @return mixed    The content of the key being requested.
     */
    public function get_key($key)
    {
        if (!array_key_exists($key, $this->_data) || $key >= 1000) {
            debug_add("Requested Key ID $key invalid.", MIDCOM_LOG_ERROR);
            debug_print_function_stack('Called from here');

            return false;
        }

        if (   (   $key === MIDCOM_CONTEXT_ROOTTOPICID
                || $key === MIDCOM_CONTEXT_ROOTTOPIC)
            && $this->_data[$key] === null) {
            $this->_initialize_root_topic();
        }

        return $this->_data[$key];
    }

    private function _initialize_root_topic()
    {
        $guid = midcom::get()->config->get('midcom_root_topic_guid');
        if (empty($guid)) {
            $setup = new midcom_core_setup("Root folder is not configured. Please log in as administrator");
            $root_node = $setup->find_topic(true);
        } else {
            try {
                $root_node = midcom_db_topic::get_cached($guid);
            } catch (midcom_error $e) {
                if ($e instanceof midcom_error_forbidden) {
                    throw $e;
                }
                // Fall back to another topic so that admin has a chance to fix this
                $setup = new midcom_core_setup("Root folder is misconfigured. Please log in as administrator");
                $root_node = $setup->find_topic();
            }
        }
        $this->set_key(MIDCOM_CONTEXT_ROOTTOPIC, $root_node);
        $this->set_key(MIDCOM_CONTEXT_ROOTTOPICID, $root_node->id);
    }

    /**
     * Update the component context
     *
     * This function sets a variable of the current or the given component context.
     *
     * @param int $key  The key to use
     * @param mixed $value    The value to be stored
     * @see get_context_data()
     */
    public function set_key($key, $value)
    {
        $this->_data[$key] = $value;
    }

    /**
     * Retrieve arbitrary, component-specific information in the component context
     *
     * The set call defaults to the current context, the get call's semantics are as
     * with get_key().
     *
     * Note, that if you are working from a library like the datamanager is, you
     * cannot override the component association done by the system. Instead you
     * should add your libraries name (like midcom.helper.datamanager) as a prefix,
     * separated by a dot. I know, that this is not really an elegant solution and
     * that it actually breaks with the encapsulation I want, but I don't have a
     * better solution yet.
     *
     * A complete example can be found with set_custom_key.
     *
     * @param int $key    The requested key
     * @param string $component    The component name
     * @return mixed      The requested value, which is returned by Reference!
     * @see get_key()
     * @see set_custom_key()
     */
    public function & get_custom_key($key, $component = null)
    {
        if (null === $component) {
            $component = $this->_data[MIDCOM_CONTEXT_COMPONENT];
        }

        if (!$this->has_custom_key($key, $component)) {
            debug_add("Requested Key ID {$key} for the component {$component} is invalid.", MIDCOM_LOG_ERROR);
            $result = false;
            return $result;
        }

        return $this->_data[MIDCOM_CONTEXT_CUSTOMDATA][$component][$key];
    }

    public function has_custom_key($key, $component = null)
    {
        if (null === $component) {
            $component = $this->_data[MIDCOM_CONTEXT_COMPONENT];
        }

        return (   array_key_exists($component, $this->_data[MIDCOM_CONTEXT_CUSTOMDATA])
                && array_key_exists($key, $this->_data[MIDCOM_CONTEXT_CUSTOMDATA][$component]));
    }

    /**
     * Store arbitrary, component-specific information in the component context
     *
     * This method allows you to get custom data to a given context.
     * The system will automatically associate this data with the component from the
     * currently active context. You cannot access the custom data of any other
     * component this way, it is private to the context. You may attach information
     * to other contexts, which will be associated with the current component, so
     * you have a clean namespace independently from which component or context you
     * are operating of. All calls honor references of passed data, so you can use
     * this for central controlling objects.
     *
     * Note, that if you are working from a library like the datamanager is, you
     * cannot override the component association done by the system. Instead you
     * should add your libraries name (like midcom.helper.datamanager) as a prefix,
     * separated by a dot. I know, that this is not really an elegant solution and
     * that it actually breaks with the encapsulation I want, but I don't have a
     * better solution yet.
     *
     * Be aware, that this function works by-reference instead of by-value.
     *
     * A complete example could look like this:
     *
     * <code>
     * class my_component_class_one {
     *     public function init () {
     *         midcom_core_context::get()->set_custom_key('classone', $this);
     *     }
     * }
     *
     * class my_component_class_two {
     *     public $one;
     *     public function __construct() {
     *         $this->one =& midcom_core_context::get()->get_custom_key('classone');
     *     }
     * }
     * </code>
     *
     * @param mixed $key        The key associated to the value.
     * @param mixed &$value    The value to store. (This is stored by-reference!)
     * @param string $component The component associated to the key.
     * @see get_custom_key()
     */
    public function set_custom_key($key, &$value, $component = null)
    {
        if (null === $component) {
            $component = $this->_data[MIDCOM_CONTEXT_COMPONENT];
        }

        $this->_data[MIDCOM_CONTEXT_CUSTOMDATA][$component][$key] =& $value;
    }

    /**
     * Handle a request.
     *
     * The URL of the component that is used to handle the request is obtained automatically.
     * If the handler hook returns false (i.e. handling failed), it will produce an error page.
     *
     * @return midcom_response
     */
    public function run()
    {
        do {
            $object = $this->parser->get_current_object();
            if (empty($object->guid)) {
                throw new midcom_error('Root node missing.');
            }

            if ($object instanceof midcom_db_attachment) {
                midcom::get()->serve_attachment($object);
                // This will exit
            }

        } while ($this->parser->get_object() !== false);

        // Check whether the component can handle the request.
        $response = $this->load_component_interface($object)->handle();
        if (!is_object($response)) {
            $response = new midcom_response_styled($this);
        }
        return $response;
    }

    /**
     * Check whether a given component is able to handle the current request.
     *
     * After the local configuration is retrieved from the object in question the
     * component will be asked if it can handle the request. If so, the interface class will be returned to the caller
     *
     * @param midcom_db_topic $object The node that is currently being tested.
     * @return midcom_baseclasses_components_interface
     */
    public function load_component_interface(midcom_db_topic $object)
    {
        $path = $object->component;
        $this->set_key(MIDCOM_CONTEXT_COMPONENT, $path);

        // Get component interface class
        $component_interface = midcom::get()->componentloader->get_interface_class($path);

        // Load configuration
        $config = $this->_loadconfig($this->id, $object)->get_all();
        $component_interface->configure($config, $this->id);

        // Make can_handle check
        if (!$component_interface->can_handle($object, $this->parser->argv, $this->id)) {
            debug_add("Component {$path} in {$object->name} declared unable to handle request.", MIDCOM_LOG_INFO);

            // We couldn't fetch a node due to access restrictions
            if (midcom_connection::get_error() == MGD_ERR_ACCESS_DENIED) {
                throw new midcom_error_forbidden($this->i18n->get_string('access denied', 'midcom'));
            }
            throw new midcom_error_notfound("This page is not available on this server.");
        }

        // Initialize context
        $prefix = $this->parser->get_url();
        $this->set_key(MIDCOM_CONTEXT_ANCHORPREFIX, $prefix);

        $this->set_key(MIDCOM_CONTEXT_CONTENTTOPIC, $this->parser->get_current_object());
        $this->set_key(MIDCOM_CONTEXT_URLTOPICS, $this->parser->get_objects());
        $this->set_key(MIDCOM_CONTEXT_SHOWCALLBACK, [$component_interface, 'show_content']);
        return $component_interface;
    }

    public function show()
    {
        if (!midcom::get()->skip_page_style) {
            midcom_show_style('style-init');
        }

        $callback = $this->get_key(MIDCOM_CONTEXT_SHOWCALLBACK);
        call_user_func($callback, $this->id);

        if (!midcom::get()->skip_page_style) {
            midcom_show_style('style-finish');
        }
    }

    /**
     * Load the configuration for a given object.
     *
     * This is a small wrapper function that retrieves all local configuration data
     * attached to $object. The assigned component is used to determine which
     * parameter domain has to be used.
     *
     * @param int $context_id The context ID
     * @param midcom_db_topic $object      The node from which to load the configuration.
     * @return midcom_helper_configuration The newly constructed configuration object.
     */
    private function _loadconfig($context_id, midcom_db_topic $object)
    {
        static $configs = [];
        $cache_key = $context_id . '::' . $object->guid;

        if (!isset($configs[$cache_key])) {
            $path = $this->get_key(MIDCOM_CONTEXT_COMPONENT);
            $configs[$cache_key] = new midcom_helper_configuration($object, $path);
        }

        return $configs[$cache_key];
    }
}
