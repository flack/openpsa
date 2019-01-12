<?php
/**
 * @package midcom
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

use Symfony\Component\HttpFoundation\Request;
use midcom\httpkernel\kernel;

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

        if (   $key === MIDCOM_CONTEXT_ROOTTOPIC
            && $this->_data[$key] === null) {
            $this->_initialize_root_topic();
        }

        return $this->_data[$key];
    }

    private function _initialize_root_topic()
    {
        $guid = midcom::get()->config->get('midcom_root_topic_guid');
        if (empty($guid)) {
            $component = midcom::get()->config->get('midcom_root_component');
            if ($component) {
                $root_node = new midcom_db_topic;
                $root_node->component = $component;
            } else {
                $setup = new midcom_core_setup("Root folder is not configured. Please log in as administrator");
                $root_node = $setup->find_topic(true);
            }
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
     * @return mixed      The requested value, which is returned by Reference!
     * @see get_key()
     * @see set_custom_key()
     */
    public function & get_custom_key($key)
    {
        if (!$this->has_custom_key($key)) {
            debug_add("Requested custom key {$key} is invalid.", MIDCOM_LOG_ERROR);
            $result = false;
            return $result;
        }

        return $this->_data[MIDCOM_CONTEXT_CUSTOMDATA][$key];
    }

    public function has_custom_key($key)
    {
        return (array_key_exists($key, $this->_data[MIDCOM_CONTEXT_CUSTOMDATA]));
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
     * @param mixed $value    The value to store. (This is stored by-reference!)
     * @see get_custom_key()
     */
    public function set_custom_key($key, &$value)
    {
        $this->_data[MIDCOM_CONTEXT_CUSTOMDATA][$key] =& $value;
    }

    /**
     * Handle a request.
     *
     * The URL of the component that is used to handle the request is obtained automatically.
     * If the handler hook returns false (i.e. handling failed), it will produce an error page.
     *
     * @param Request $request The request object
     * @return midcom_response
     */
    public function run(Request $request)
    {
        do {
            $object = $this->parser->get_current_object();
            if (empty($object)) {
                throw new midcom_error('Root node missing.');
            }
        } while ($this->parser->get_object() !== false);

        return $this->handle($object, $request);
    }

    /**
     * @param midcom_db_topic $topic The node that is currently being tested.
     * @param Request $request The request object
     * @return midcom_response
     */
    public function handle(midcom_db_topic $topic, Request $request)
    {
        // Initialize context
        $this->set_key(MIDCOM_CONTEXT_ANCHORPREFIX, $this->parser->get_url());
        $this->set_key(MIDCOM_CONTEXT_COMPONENT, $topic->component);
        $this->set_key(MIDCOM_CONTEXT_CONTENTTOPIC, $topic);
        $this->set_key(MIDCOM_CONTEXT_URLTOPICS, $this->parser->get_objects());

        $request->attributes->set('context', $this);

        return kernel::get()->handle($request);
    }

    public function show()
    {
        if (!midcom::get()->skip_page_style) {
            midcom_show_style('style-init');
        }

        $callback = $this->get_key(MIDCOM_CONTEXT_SHOWCALLBACK);
        call_user_func($callback);

        if (!midcom::get()->skip_page_style) {
            midcom_show_style('style-finish');
        }
    }
}
