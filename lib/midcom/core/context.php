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
     * @var int
     */
    private static $counter = 0;

    /**
     * @var midcom_core_context[]
     */
    private static $stack = [];

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
        MIDCOM_CONTEXT_LASTMODIFIED => 0,
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
     * Create and prepare a new component context.
     *
     * @param midcom_db_topic $node Root node of the context
     */
    public function __construct(midcom_db_topic $node = null)
    {
        if (isset($_SERVER['REQUEST_URI'])) {
            $this->_data[MIDCOM_CONTEXT_URI] = $_SERVER['REQUEST_URI'];
        }
        if (is_object($node)) {
            $this->_data[MIDCOM_CONTEXT_ROOTTOPIC] = $node;
        }

        $this->id = self::$counter++;
    }

    /**
     * @param string $url
     * @param midcom_db_topic $topic
     * @return midcom_core_context
     */
    public static function enter($url = null, midcom_db_topic $topic = null)
    {
        $context = new static($topic);
        array_push(self::$stack, $context);
        if ($url !== null) {
            $context->set_key(MIDCOM_CONTEXT_URI, $url);
        }
        return $context;
    }

    public static function leave()
    {
        if (!empty(self::$stack)) {
            $left = array_pop(self::$stack);

            if (!empty(self::$stack)) {
                $entered = end(self::$stack);
                $lastmod_child = $left->get_key(MIDCOM_CONTEXT_LASTMODIFIED);
                if ($lastmod_child > $entered->get_key(MIDCOM_CONTEXT_LASTMODIFIED)) {
                    $entered->set_key(MIDCOM_CONTEXT_LASTMODIFIED, $lastmod_child);
                }
            }
        }
    }

    /**
     * Get the current context
     *
     * If it doesn't exist for some reason, it is automatically created
     *
     * @return midcom_core_context The requested context, or false if not found
     */
    public static function get()
    {
        if (empty(self::$stack)) {
            self::enter();
        }
        return end(self::$stack);
    }

    /**
     * Returns inherited style (if any)
     */
    public function get_inherited_style()
    {
        $to_check = array_reverse($this->get_key(MIDCOM_CONTEXT_URLTOPICS));
        $to_check[] = $this->get_key(MIDCOM_CONTEXT_ROOTTOPIC);
        foreach ($to_check as $object) {
            if ($object instanceof midcom_db_topic && $object->styleInherit && $object->style) {
                return $object->style;
            }
        }
        return false;
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
        return array_key_exists($key, $this->_data[MIDCOM_CONTEXT_CUSTOMDATA]);
    }

    /**
     * Store arbitrary, component-specific information in the component context
     *
     * This method allows you to get custom data to the current context.
     * The system will automatically associate this data with the component from the
     * currently active context. You cannot access the custom data of any other
     * component this way, it is private to the context. All calls honor references
     * of passed data, so you can use this for central controlling objects.
     *
     * Note, that if you are working from a library like the datamanager is, you
     * cannot override the component association done by the system. Instead you
     * should add your libraries name (like midcom.datamanager) as a prefix,
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
