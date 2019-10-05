<?php
/**
 * @package midcom.helper
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Main Navigation interface class.
 *
 * Basically, this class proxies all requests to a midcom_helper_nav_backend
 * class. See the interface definition of it for further details.
 *
 * Additionally this class implements a couple of helper functions to make
 * common NAP tasks easier.
 *
 * <b>Important note:</b> Whenever you add new code to this class, or extend it through
 * inheritance, never call the proxy-functions of the backend directly, this is strictly
 * forbidden.
 *
 * @todo End-User documentation of node and leaf data, as the one in the backend is incomplete too.
 * @package midcom.helper
 * @see midcom_helper_nav_backend
 */
class midcom_helper_nav
{
    /**
     * The backend instance in use.
     *
     * @var midcom_helper_nav_backend
     */
    private $_backend;

    /**
     * The cache of instantiated NAP backends
     *
     * @var array
     */
    private static $_backends = [];

    /**
     * The context ID we're associated with.
     *
     * @var midcom_core_context
     */
    private $context;

    /**
     * Create a NAP instance for the currently active context
     */
    public function __construct()
    {
        $this->context = midcom_core_context::get();
        $this->_backend = $this->_get_backend();
    }

    /**
     * This function maintains one NAP Class per context. Usually this is enough,
     * since you mostly will access it in context 0, the default. The problem is, that
     * this is not 100% efficient: If you instantiate two different NAP Classes in
     * different contexts both referring to the same root node, you will get two
     * different instances.
     *
     * @see midcom_helper_nav
     */
    private function _get_backend() : midcom_helper_nav_backend
    {
        if (!isset(self::$_backends[$this->context->id])) {
            $root = $this->context->get_key(MIDCOM_CONTEXT_ROOTTOPIC);
            $urltopics = $this->context->get_key(MIDCOM_CONTEXT_URLTOPICS);
            self::$_backends[$this->context->id] = new midcom_helper_nav_backend($root, $urltopics);
        }

        return self::$_backends[$this->context->id];
    }

    /* The following methods are just interfaces to midcom_helper_nav_backend */

    /**
     * Retrieve the ID of the currently displayed node. Defined by the topic of
     * the component that declared able to handle the request.
     *
     * @return int    The ID of the node in question.
     * @see midcom_helper_nav_backend::get_current_node()
     */
    public function get_current_node()
    {
        return $this->_backend->get_current_node();
    }

    /**
     * Retrieve the ID of the currently displayed leaf. This is a leaf that is
     * displayed by the handling topic. If no leaf is active, this function
     * returns false. (Remember to make a type sensitive check, e.g.
     * nav::get_current_leaf() !== false to distinguish '0' and 'false'.)
     *
     * @return int    The ID of the leaf in question or false on failure.
     * @see midcom_helper_nav_backend::get_current_leaf()
     */
    public function get_current_leaf()
    {
        return $this->_backend->get_current_leaf();
    }

    /**
     * Retrieve the ID of the root node. Note that this ID is dependent from the
     * ID of the MidCOM Root topic and therefore will change as easily as the
     * root topic ID might. The MIDCOM_NAV_URL entry of the root node's data will
     * always be empty.
     *
     * @see midcom_helper_nav_backend::get_root_node()
     */
    public function get_root_node() : int
    {
        return $this->_backend->get_root_node();
    }

    /**
     * Lists all Sub-nodes of $parent_node. If there are no subnodes you will get
     * an empty array, if there was an error (for instance an unknown parent node
     * ID) you will get false.
     *
     * @param int $parent_node    The id of the node of which the subnodes are searched.
     * @param boolean $show_noentry Show all objects on-site which have the noentry flag set.
     *     This defaults to false.
     * @see midcom_helper_nav_backend::list_nodes()
     */
    public function list_nodes($parent_node, bool $show_noentry = false) : array
    {
        return $this->_backend->list_nodes($parent_node, $show_noentry);
    }

    /**
     * Lists all leaves of $parent_node. If there are no leaves you will get an
     * empty array, if there was an error (for instance an unknown parent node ID)
     * you will get false.
     *
     * @param int $parent_node    The ID of the node of which the leaves are searched.
     * @param boolean $show_noentry Show all objects on-site which have the noentry flag set.
     *     This defaults to false.
     * @see midcom_helper_nav_backend::list_leaves()
     */
    public function list_leaves($parent_node, $show_noentry = false) : array
    {
        return $this->_backend->list_leaves($parent_node, $show_noentry);
    }

    /**
     * This will give you a key-value pair describing the node with the ID
     * $node_id. The defined keys are described above in Node data interchange
     * format. You will get false if the node ID is invalid.
     *
     * @param int $node_id    The node ID to be retrieved.
     * @return Array        The node data as outlined in the class introduction, false on failure
     * @see midcom_helper_nav_backend::get_node()
     */
    public function get_node($node_id)
    {
        return $this->_backend->get_node($node_id);
    }

    /**
     * This will give you a key-value pair describing the leaf with the ID
     * $node_id. The defined keys are described above in leaf data interchange
     * format. You will get false if the leaf ID is invalid.
     *
     * @param string $leaf_id    The leaf-id to be retrieved.
     * @return Array        The leaf-data as outlined in the class introduction, false on failure
     * @see midcom_helper_nav_backend::get_leaf()
     */
    public function get_leaf($leaf_id)
    {
        return $this->_backend->get_leaf($leaf_id);
    }

    /**
     * Returns the ID of the node to which $leaf_id is associated to, false
     * on failure.
     *
     * @param string $leaf_id    The Leaf-ID to search an uplink for.
     * @return int             The ID of the Node for which we have a match, or false on failure.
     * @see midcom_helper_nav_backend::get_leaf_uplink()
     */
    function get_leaf_uplink($leaf_id)
    {
        return $this->_backend->get_leaf_uplink($leaf_id);
    }

    /**
     * Returns the ID of the node to which $node_id is associated to, false
     * on failure. The root node's uplink is -1.
     *
     * @param int $node_id    The Leaf-ID to search an uplink for.
     * @return int             The ID of the Node for which we have a match, -1 for the root node, or false on failure.
     * @see midcom_helper_nav_backend::get_node_uplink()
     */
    public function get_node_uplink($node_id)
    {
        return $this->_backend->get_node_uplink($node_id);
    }

    /**
     * Checks if the given node is within the tree of another node.
     *
     * @param int    $node_id    The node in question.
     * @param int    $root_id    The root node to use.
     */
    public function is_node_in_tree($node_id, $root_id) : bool
    {
        $uplink = $this->get_node_uplink($node_id);
        if ($uplink == $root_id) {
            return true;
        }
        if (in_array($uplink, [false, -1])) {
            return false;
        }
        return $this->is_node_in_tree($uplink, $root_id);
    }

    /**
     * List all child elements, nodes and leaves alike, of the node with ID
     * $parent_node_id. For every child element, an array of ID and type (node/leaf)
     * is given as
     *
     * - MIDCOM_NAV_ID => 0,
     * - MIDCOM_NAV_TYPE => 'node'
     *
     * If there are no child elements at all the method will return an empty array,
     * in case of an error false.  NOTE: This method should be quite slow, there's
     * room for improvement... :-)
     *
     * @param int $parent_node_id    The ID of the parent node.
     * @return Array                A list of found elements, or false on failure.
     */
    public function list_child_elements($parent_node_id)
    {
        $parent_node = $this->get_node($parent_node_id);
        if (!$parent_node) {
            return false;
        }

        $guid = $parent_node[MIDCOM_NAV_GUID];
        $navorder = (int) midcom_db_parameter::get_by_objectguid($guid, 'midcom.helper.nav', 'navorder');
        if ($navorder == MIDCOM_NAVORDER_ARTICLESFIRST) {
            $navorder = 'articlesfirst';
        } elseif ($navorder == MIDCOM_NAVORDER_SCORE) {
            $navorder = 'score';
        } else {
            $navorder = 'topicsfirst';
        }

        $nav_object = midcom_helper_nav_itemlist::factory($navorder, $this, $parent_node_id);
        return $nav_object->get_sorted_list();
    }

    /**
     * Try to resolve a guid into a NAP object.
     *
     * The code is optimized trying to avoid a full-scan if possible. To do this it
     * will treat topic and article guids specially: In both cases the system will
     * translate it using the topic id into a node id and scan only that part of the
     * tree non-recursively.
     *
     * A full scan of the NAP data is only done if another MidgardObject is used.
     *
     * Note: If you want to resolve a GUID you got from a Permalink, use the Permalinks
     * service within MidCOM, as it covers more objects than the NAP listings.
     *
     * @param string $guid The GUID of the object to be looked up.
     * @param boolean $node_is_sufficient if we could return a good guess of correct parent node but said node does not list the $guid in leaves return the node or try to do a full (and very expensive) NAP scan ?
     * @return mixed Either a node or leaf structure, distinguishable by MIDCOM_NAV_TYPE, or false on failure.
     * @see midcom_services_permalinks
     */
    public function resolve_guid($guid, $node_is_sufficient = false)
    {
        // First, check if the GUID is already known by the backend:
        if ($cached_result = $this->_backend->get_loaded_object_by_guid($guid)) {
            debug_add('The GUID was already known by the backend instance, returning the cached copy directly.');
            return $cached_result;
        }

        // Fetch the object in question for a start, so that we know what to do (tm)
        // Note, that objects that cannot be resolved will still be processed using a full-scan of
        // the tree. This is, for example, used by the on-delete cache invalidation.
        try {
            $object = midcom::get()->dbfactory->get_object_by_guid($guid);
        } catch (midcom_error $e) {
            debug_add("Could not load GUID {$guid}, trying to continue anyway. Last error was: " . $e->getMessage(), MIDCOM_LOG_WARN);
        }
        if (!empty($object)) {
            if ($object instanceof midcom_db_topic) {
                // Ok. This topic should be within the content tree,
                // we check this and return the node if everything is ok.
                if (!$this->is_node_in_tree($object->id, $this->get_root_node())) {
                    debug_add("The GUID {$guid} leads to an unknown topic not in our tree.", MIDCOM_LOG_WARN);
                    return false;
                }

                return $this->get_node($object->id);
            }

            if ($object instanceof midcom_db_article) {
                // Ok, let's try to find the article using the topic in the tree.
                if (!$this->is_node_in_tree($object->topic, $this->get_root_node())) {
                    debug_add("The GUID {$guid} leads to an unknown topic not in our tree.", MIDCOM_LOG_WARN);
                    return false;
                }
                if ($leaf = $this->_find_leaf_in_topic($object->topic, $guid)) {
                    return $leaf;
                }

                debug_add("The Article GUID {$guid} is somehow hidden from the NAP data in its topic, no results shown.", MIDCOM_LOG_INFO);
                return false;
            }

            // Ok, unfortunately, this is not an immediate topic. We try to traverse
            // upwards in the object chain to find a topic.
            if ($topic = $this->find_closest_topic($object)) {
                debug_add("Found topic #{$topic->id}, searching the leaves");
                if ($leaf = $this->_find_leaf_in_topic($topic->id, $guid)) {
                    return $leaf;
                }
                if ($node_is_sufficient) {
                    debug_add("Could not find guid in leaves (maybe not listed?), but node is sufficient, returning node");
                    return $this->get_node($topic->id);
                }
            }
        }

        // this is the rest of the lot, we need to traverse everything, unfortunately.
        // First, we traverse a list of nodes to be checked on by one, avoiding a recursive
        // function call.
        $unprocessed_node_ids = [$this->get_root_node()];

        while (!empty($unprocessed_node_ids)) {
            $node_id = array_shift($unprocessed_node_ids);

            // Check leaves of this node first.
            if ($leaf = $this->_find_leaf_in_topic($node_id, $guid)) {
                return $leaf;
            }

            // Ok, append all subnodes to the queue.
            $unprocessed_node_ids = array_merge($unprocessed_node_ids, $this->list_nodes($node_id));
        }

        debug_add("We were unable to find the GUID {$guid} in the MidCOM tree even with a full scan.", MIDCOM_LOG_INFO);
        return false;
    }

    private function _find_leaf_in_topic($topic, string $guid)
    {
        foreach ($this->list_leaves($topic, true) as $leafid) {
            $leaf = $this->get_leaf($leafid);
            if ($leaf[MIDCOM_NAV_GUID] == $guid) {
                return $leaf;
            }
        }
        return false;
    }

    public function find_closest_topic($object)
    {
        if (!is_object($object)) {
            return null;
        }
        debug_add('Looking for a topic to use via get_parent()');
        while ($parent = $object->get_parent()) {
            if (is_a($parent, midcom_db_topic::class)) {
                // Verify that this topic is within the current sites tree, if it is not,
                // we ignore it.
                if ($this->is_node_in_tree($parent->id, $this->get_root_node())) {
                    return $parent;
                }
            }
            $object = $parent;
        }
        return null;
    }

    /* The more complex interface methods starts here */

    /**
     * Construct a breadcrumb line.
     *
     * Gives you a line like 'Start > Topic1 > Topic2 > Article' using NAP to
     * traverse upwards till the root node. $separator is inserted between the
     * pairs, $class, if non-null, will be used as CSS-class for the A-Tags.
     *
     * The parameter skip_levels indicates how much nodes should be skipped at
     * the beginning of the current path. Default is to show the complete path. A
     * value of 1 will skip the home link, 2 will skip the home link and the first
     * subtopic and so on. If a leaf or node is selected, that normally would be
     * hidden, only its name will be shown.
     *
     * @param string    $separator        The separator to use between the elements.
     * @param string    $class            If not-null, it will be assigned to all A tags.
     * @param int       $skip_levels      The number of topic levels to skip before starting to work (use this to skip 'Home' links etc.).
     * @param string    $current_class    The class that should be assigned to the currently active element.
     * @param array     $skip_guids       Array of guids that are skipped.
     */
    public function get_breadcrumb_line($separator = ' &gt; ', $class = null, $skip_levels = 0, $current_class = null, $skip_guids = []) : string
    {
        $breadcrumb_data = $this->get_breadcrumb_data();
        $result = '';

        // We traverse this list using the iterator of the array, since this allows
        // us direct treatment of the final element.
        reset($breadcrumb_data);

        // Detect real starting Node
        if ($skip_levels > 0) {
            if ($skip_levels >= count($breadcrumb_data)) {
                debug_add('We were asked to skip all breadcrumb elements that were present (or even more). Returning an empty breadcrumb line therefore.', MIDCOM_LOG_INFO);
                return '';
            }
            $breadcrumb_data = array_slice($breadcrumb_data, $skip_levels);
        }

        $class = $class === null ? '' : ' class="' . $class . '"';
        while (current($breadcrumb_data) !== false) {
            $data = current($breadcrumb_data);
            $entry = htmlspecialchars($data[MIDCOM_NAV_NAME]);

            // Add the next element sensitive to the fact whether we are at the end or not.
            if (next($breadcrumb_data) === false) {
                if ($current_class !== null) {
                    $entry = "<span class=\"{$current_class}\">{$entry}</span>";
                }
            } else {
                if (   !empty($data['napobject'][MIDCOM_NAV_GUID])
                    && in_array($data['napobject'][MIDCOM_NAV_GUID], $skip_guids)) {
                    continue;
                }

                $entry = "<a href=\"{$data[MIDCOM_NAV_URL]}\"{$class}>{$entry}</a>{$separator}";
            }
            $result .= $entry;
        }

        return $result;
    }

    /**
     * Construct source data for a breadcrumb line.
     *
     * Gives you the data needed to construct a line like
     * 'Start > Topic1 > Topic2 > Article' using NAP to
     * traverse upwards till the root node. The components custom breadcrumb
     * data is inserted at the end of the computed breadcrumb line after any
     * set NAP leaf.
     *
     * See get_breadcrumb_line for a more end-user oriented way of life.
     *
     * <b>Return Value</b>
     *
     * The breadcrumb data will be returned as a list of associative arrays each
     * containing these keys:
     *
     * - MIDCOM_NAV_URL The fully qualified URL to the node.
     * - MIDCOM_NAV_NAME The clear-text name of the node.
     * - MIDCOM_NAV_TYPE One of 'node', 'leaf', 'custom' indicating what type of entry
     *   this is.
     * - MIDCOM_NAV_ID The Identifier of the structure used to build this entry, this is
     *   either a NAP node/leaf ID or the list key set by the component for custom data.
     * - 'napobject' This contains the original NAP object retrieved by the function.
     *   Just in case you need more information than is available directly.
     *
     * The entry of every level is indexed by its MIDCOM_NAV_ID, where custom keys preserve
     * their original key (as passed by the component) and prefixing it with 'custom-'. This
     * allows you to easily check if a given node/leave is within the current breadcrumb-line
     * by checking with array_key_exists.
     *
     * <b>Adding custom data</b>
     *
     * Custom elements are added to this array by using the MidCOM custom component context
     * at this time. You need to add a list with the same structure as above into the
     * custom component context key <i>midcom.helper.nav.breadcrumb</i>. (This needs
     * to be an array always, even if you return only one element.)
     *
     * Note, that the URL you pass in that list is always prepended with the current anchor
     * prefix. It is not possible to specify absolute URLs there. No leading slash is required.
     *
     * Example:
     *
     * <code>
     * $tmp = [
     *     [
     *         MIDCOM_NAV_URL => "list/{$this->_category}/{$this->_mode}/1/",
     *         MIDCOM_NAV_NAME => $this->_category_name,
     *     ],
     * ];
     * midcom_core_context::get()->set_custom_key('midcom.helper.nav.breadcrumb', $tmp);
     * </code>
     */
    public function get_breadcrumb_data($id = null) : array
    {
        $prefix = $this->context->get_key(MIDCOM_CONTEXT_ANCHORPREFIX);
        $result = [];

        if (!$id) {
            $curr_leaf = $this->get_current_leaf();
            $curr_node = $this->get_current_node();
        } else {
            $curr_leaf = $this->get_leaf($id);
            $curr_node = -1;

            if (!$curr_leaf) {
                if ($node = $this->get_node($id)) {
                    $curr_node = $node[MIDCOM_NAV_ID];
                }
            } else {
                $curr_node = $this->get_node($curr_leaf[MIDCOM_NAV_NODEID]);
            }
        }
        foreach ($this->get_node_path($curr_node) as $node_id) {
            $node = $this->get_node($node_id);
            $result[$node[MIDCOM_NAV_ID]] = [
                MIDCOM_NAV_URL => $node[MIDCOM_NAV_ABSOLUTEURL],
                MIDCOM_NAV_NAME => $node[MIDCOM_NAV_NAME],
                MIDCOM_NAV_TYPE => 'node',
                MIDCOM_NAV_ID => $node_id,
                'napobject' => $node,
            ];
        }
        if ($curr_leaf !== false) {
            $leaf = $this->get_leaf($curr_leaf);

            // Ignore Index Article Leaves
            if ($leaf[MIDCOM_NAV_URL] != '') {
                $result[$leaf[MIDCOM_NAV_ID]] = [
                    MIDCOM_NAV_URL => $leaf[MIDCOM_NAV_ABSOLUTEURL],
                    MIDCOM_NAV_NAME => $leaf[MIDCOM_NAV_NAME],
                    MIDCOM_NAV_TYPE => 'leaf',
                    MIDCOM_NAV_ID => $curr_leaf,
                    'napobject' => $leaf,
                ];
            }
        }

        if (midcom_core_context::get()->has_custom_key('midcom.helper.nav.breadcrumb')) {
            $customdata = midcom_core_context::get()->get_custom_key('midcom.helper.nav.breadcrumb');
            if (is_array($customdata)) {
                foreach ($customdata as $key => $entry) {
                    $id = "custom-{$key}";

                    $url = "{$prefix}{$entry[MIDCOM_NAV_URL]}";
                    if (   substr($entry[MIDCOM_NAV_URL], 0, 1) == '/'
                        || preg_match('|^https?://|', $entry[MIDCOM_NAV_URL])) {
                        $url = $entry[MIDCOM_NAV_URL];
                    }

                    $result[$id] = [
                        MIDCOM_NAV_URL => $url,
                        MIDCOM_NAV_NAME => $entry[MIDCOM_NAV_NAME],
                        MIDCOM_NAV_TYPE => 'custom',
                        MIDCOM_NAV_ID => $id,
                        'napobject' => $entry,
                    ];
                }
            }
        }
        return $result;
    }

    /**
     * Retrieve the IDs of the nodes from the URL. First value at key 0 is
     * the root node ID, possible second value is the first subnode ID etc.
     * Contains only visible nodes (nodes which can be loaded).
     */
    public function get_node_path($node_id = null) : array
    {
        if ($node_id === null) {
            return $this->_backend->get_node_path();
        }
        $path = [];
        $node = $this->get_node($node_id);
        while ($node) {
            $path[] = $node[MIDCOM_NAV_ID];
            if ($node[MIDCOM_NAV_NODEID] === -1) {
                break;
            }
            $node = $this->get_node($node[MIDCOM_NAV_NODEID]);
        }
        return array_reverse($path);
    }

    /**
     * Retrieve the ID of the upper node of the currently displayed node.
     *
     * @return mixed    The ID of the node in question.
     */
    public function get_current_upper_node()
    {
        return $this->_backend->get_current_upper_node();
    }
}
