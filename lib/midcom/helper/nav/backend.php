<?php
/**
 * @package midcom.helper
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * This class is the basic building stone of the Navigation Access Point
 * System of MidCOM.
 *
 * It is responsible for collecting the available
 * information and for building the navigational tree out of it. This
 * class is only the internal interface to the NAP System and is used by
 * midcom_helper_nav as a node cache. The framework should ensure that
 * only one class of this type is active at one time.
 *
 * It will give you a very abstract view of the content tree, modified
 * by the NAP classes of the components. You can retrieve a node/leaf tree
 * of the content, and for each element you can retrieve a URL name and a
 * long name for navigation display.
 *
 * Leaves and Nodes are both indexed by integer constants which are assigned
 * by the framework. The framework defines two starting points in this tree:
 * The root node and the "current" node. The current node defined through
 * the topic of the component that declared to be able to handle the request.
 *
 * The class will load the necessary information on demand to minimize
 * database traffic.
 *
 * The interface functions should enable you to build any navigation tree you
 * desire. The public nav class will give you some of those high-level
 * functions.
 *
 * <b>Node data interchange format</b>
 *
 * Node NAP data consists of a simple key => value array with the following
 * keys required by the component:
 *
 * - MIDCOM_NAV_NAME => The real (= displayable) name of the element
 *
 * Other keys delivered to NAP users include:
 *
 * - MIDCOM_NAV_URL  => The URL name of the element, which is automatically
 *   defined by NAP.
 *
 * <b>Leaf data interchange format</b>
 *
 * Basically for each leaf the usual meta information is returned:
 *
 * - MIDCOM_NAV_URL      => URL of the leaf element
 * - MIDCOM_NAV_NAME     => Name of the leaf element
 * - MIDCOM_NAV_GUID     => Optional argument denoting the GUID of the referred element
 * - MIDCOM_NAV_SORTABLE => Optional argument denoting whether the element is sortable
 *
 * The Datamanager will automatically transform (3) to the syntax described in
 * (1) by copying the values.
 *
 * @package midcom.helper
 */
class midcom_helper_nav_backend
{
    /**#@+
     * NAP data variable.
     */

    /**
     * The GUID of the MidCOM Root Content Topic
     *
     * @var int
     */
    private $_root;

    /**
     * The GUID of the currently active Navigation Node, determined by the active
     * MidCOM Topic or one of its uplinks, if the subtree in question is invisible.
     *
     * @var int
     */
    private $_current;

    /**
     * The GUID of the currently active leaf.
     *
     * @var string
     */
    private $_currentleaf = false;

    /**
     * Leaf cache. It is an array which contains elements indexed by
     * their leaf ID. The data is again stored in an associative array:
     *
     * - MIDCOM_NAV_NODEID => ID of the parent node (int)
     * - MIDCOM_NAV_URL => URL name of the leaf (string)
     * - MIDCOM_NAV_NAME => Textual name of the leaf (string)
     *
     * @todo Update the data structure documentation
     * @var midcom_helper_nav_leaf[]
     */
    private $_leaves = array();

    /**
     * Node cache. It is an array which contains elements indexed by
     * their node ID. The data is again stored in an associative array:
     *
     * - MIDCOM_NAV_NODEID => ID of the parent node (-1 for the root node) (int)
     * - MIDCOM_NAV_URL => URL name of the leaf (string)
     * - MIDCOM_NAV_NAME => Textual name of the leaf (string)
     *
     * @todo Update the data structure documentation
     * @var midcom_helper_nav_node[]
     */
    private static $_nodes = array();

    /**
     * List of all topics for which the leaves have been loaded.
     * If the id of the node is in this array, the leaves are available, otherwise,
     * the leaves have to be loaded.
     *
     * @var midcom_helper_nav_leaf[]
     */
    private $_loaded_leaves = array();

    /**#@-*/

    /**#@+
     * Internal runtime state variable.
     */

    /**
     * Reference to the systemwide component loader class.
     *
     * @var midcom_helper__componentloader
     */
    private $_loader;

    /**
     * Temporary storage where _loadNode can return the last known good
     * node in case the current node not visible. It is evaluated by the
     * constructor.
     *
     * @var int
     */
    private $_lastgoodnode = -1;

    /**
     * A reference to the NAP cache store
     *
     * @var midcom_services_cache_module_nap
     */
    private $_nap_cache = null;

    /**
     * This array holds the node path from the URL. First value at key 0 is
     * the root node ID, possible second value is the first subnode ID etc.
     * Contains only visible nodes (nodes which can be loaded).
     *
     * @var Array
     */
    private $_node_path = array();

    /**
     * User id for ACL checks. This is set when instantiating to avoid unnecessary overhead
     *
     * @var string
     */
    private $_user_id = false;

    /**
     * Constructor
     *
     * The only constructor of the Basicnav class. It will initialize Root-Topic,
     * Current-Topic and all cache arrays. The function will load all nodes
     * between root and current node.
     *
     * If the current node is behind an invisible or undescendable node, the last
     * known good node will be used instead for the current node.
     *
     * The constructor retrieves all initialization data from the component context.
     *
     * @param int $context    The Context ID for which to create NAP data for, defaults to 0
     */
    public function __construct($context = 0)
    {
        $this->_root = midcom_core_context::get($context)->get_key(MIDCOM_CONTEXT_ROOTTOPICID);

        $this->_nap_cache = midcom::get()->cache->nap;
        $this->_loader = midcom::get()->componentloader;

        if (!midcom::get()->auth->admin) {
            $this->_user_id = midcom::get()->auth->acl->get_user_id();
        }

        $up = null;
        $node_path_candidates = array($this->_root);
        $this->_current = $this->_root;
        foreach (midcom_core_context::get($context)->get_key(MIDCOM_CONTEXT_URLTOPICS) as $topic) {
            $id = $this->_nodeid($topic->id, $up);
            $node_path_candidates[] = $id;
            $this->_current = $id;
            if ($up || !empty($topic->symlink)) {
                $up = $id;
            }
        }

        $root_set = false;

        foreach ($node_path_candidates as $node_id) {
            switch ($this->_loadNode($node_id)) {
                case MIDCOM_ERROK:
                    if (!$root_set) {
                        // Reset the Root node's URL Parameter to an empty string.
                        self::$_nodes[$this->_root]->url = '';
                        $root_set = true;
                    }
                    $this->_node_path[] = $node_id;
                    $this->_lastgoodnode = $node_id;
                    break;

                case MIDCOM_ERRFORBIDDEN:
                    // Node is hidden behind an undescendable one, activate the last known good node as current
                    $this->_current = $this->_lastgoodnode;
                    break;

                default:
                    debug_add("_loadNode failed, see above error for details.", MIDCOM_LOG_ERROR);
                    return;
            }
        }
    }

    /**
     * This function is the controlling instance of the loading mechanism. It
     * is able to load the navigation data of any topic within MidCOM's topic
     * tree into memory. Any uplink nodes that are not loaded into memory will
     * be loaded until any other known topic is encountered. After the
     * necessary data has been loaded with calls to _loadNodeData.
     *
     * If all load calls were successful, MIDCOM_ERROK is returned. Any error
     * will be indicated with a corresponding return value.
     *
     * @param mixed $node_id    The node ID of the node to be loaded
     * @param mixed $up    The node ID of the parent node.    Optional and not normally needed.
     * @return int            MIDCOM_ERROK on success, one of the MIDCOM_ERR... constants upon an error
     */
    private function _loadNode($node_id, $up = null)
    {
        // Check if we have a cached version of the node already
        if (isset(self::$_nodes[$this->_nodeid($node_id, $up)])) {
            return MIDCOM_ERROK;
        }
        if (!$up) {
            $up = $this->_up($node_id);
        }

        $topic_id = (int) $node_id;

        // Load parent nodes also to cache
        $up_ids = array();
        if ($up) {
            $parent_id = $up;

            $up_ids = explode("_", $up);
            $up_ids = array_reverse($up_ids);
            array_pop($up_ids);
        } else {
            $parent_id = $this->_get_parent_id($topic_id);
        }

        $lastgoodnode = null;

        while (   $parent_id
               && !isset(self::$_nodes[$parent_id])) {
            try {
                self::$_nodes[$parent_id] = $this->_loadNodeData($parent_id);
            } catch (midcom_error_forbidden $e) {
                $log_level = MIDCOM_LOG_WARN;
                if (!$this->_get_parent_id($topic_id)) {
                    // This can happen only when a target for a symlink pointing outside the tree is tried to be accessed.
                    // It is normal then, so use info as log level in that case.
                    $log_level = MIDCOM_LOG_INFO;
                }
                debug_add("The Node {$parent_id} is invisible, could not satisfy the dependency chain to Node #{$node_id}", $log_level);
                return $e->getCode();
            } catch (midcom_error $e) {
                return $e->getCode();
            }

            if (null === $lastgoodnode) {
                $lastgoodnode = $parent_id;
            }

            $parent_id = $this->_get_parent_id($topic_id);

            if (   $up
                && $up_id = array_pop($up_ids)
                && $up_id != $parent_id) {
                $parent_id = $up_id;
            }
        }

        if (   !is_null($lastgoodnode)
            && (empty($this->_lastgoodnode) || $this->_lastgoodnode < 0)) {
            $this->_lastgoodnode = $lastgoodnode;
        }

        try {
            self::$_nodes[$node_id] = $this->_loadNodeData($topic_id);
        } catch (midcom_error $e) {
            return $e->getCode();
        }
        return MIDCOM_ERROK;
    }

    /**
     * Load the navigational information associated with the topic $param, which
     * can be passed as an ID or as a MidgardTopic object. This is differentiated
     * by the flag $idmode (true for id, false for MidgardTopic).
     *
     * This method does query the topic for all information and completes it to
     * build up a full NAP data structure
     *
     * It determines the URL_NAME of the topic automatically using the name of the
     * topic in question.
     *
     * The currently active leaf is only queried if and only if the currently
     * processed topic is equal to the current context's content topic. This should
     * prevent dynamically loaded components from disrupting active leaf information,
     * as this can happen if dynamic_load is called before showing the navigation.
     *
     * @param mixed $topic_id Topic ID to be processed
     * @return array The loaded node data
     */
    private function _loadNodeData($topic_id, $up = null)
    {
        $node = new midcom_helper_nav_node($this, $topic_id, $up);

        if (    !$node->is_object_visible()
             || (   $this->_user_id
                 && !midcom::get()->auth->acl->can_do_byguid('midgard:read', $node->guid, 'midcom_db_topic', $this->_user_id))) {
            throw new midcom_error_forbidden('Node cannot be read or is invisible');
        }

        // The node is visible, add it to the list.
        self::$_nodes[$node->id] = $node;

        // Load the current leaf, this does *not* load the leaves from the DB, this is done
        // during get_leaf now.
        if ($node->id === $this->_current) {
            $interface = $this->_get_component_interface($node->component);
            if (!$interface) {
                throw new midcom_error('Failed to load interface class for ' . $node->component);
            }
            $currentleaf = $interface->get_current_leaf();
            if ($currentleaf !== false) {
                $this->_currentleaf = "{$node->id}-{$currentleaf}";
            }
        }

        return $node;
    }

    /**
     * Loads the leaves for a given node from the cache or database.
     * It will relay the code to _get_leaves() and check the object visibility upon
     * return.
     *
     * @param midcom_helper_nav_node $node The NAP node data structure to load the nodes for.
     */
    private function _load_leaves(midcom_helper_nav_node $node)
    {
        if (array_key_exists($node->id, $this->_loaded_leaves)) {
            debug_add("Warning, tried to load the leaves of node {$node->id} more than once.", MIDCOM_LOG_INFO);
            return;
        }

        $this->_loaded_leaves[$node->id] = array();

        $leaves = array_filter($this->_get_leaves($node), function($leaf) {
            return $leaf->is_object_visible();
        });
        foreach ($leaves as $id => $leaf) {
            $this->_leaves[$id] = $leaf;
            $this->_loaded_leaves[$node->id][$id] =& $this->_leaves[$id];
        }
    }

    /**
     * Return the list of leaves for a given node. This helper will construct complete leaf
     * data structures for each leaf found. It will first check the cache for the leaf structures,
     * and query the database only if the corresponding objects have not been found there.
     *
     * No visibility checks are made at this point.
     *
     * @param midcom_helper_nav_node $node The node data structure for which to retrieve the leaves.
     * @return Array All leaves found for that node, in complete post processed leave data structures.
     */
    private function _get_leaves(midcom_helper_nav_node $node)
    {
        $entry_name = "{$node->id}-leaves";

        $leaves = $this->_nap_cache->get_leaves($entry_name);

        if (false === $leaves) {
            debug_add('The leaves have not yet been loaded from the database, we do this now.');

            //we always write all the leaves to cache and filter for ACLs after the fact
            midcom::get()->auth->request_sudo('midcom.helper.nav');
            $leaves = $this->_get_leaves_from_database($node);
            midcom::get()->auth->drop_sudo();

            $this->_write_leaves_to_cache($node, $leaves);
        }

        $result = array();
        $fullprefix = midcom::get()->config->get('midcom_site_url');
        $absoluteprefix = midcom_connection::get_url('self');

        foreach ($leaves as $id => $leaf) {
            if (   !empty($leaf->object)
                && $leaf->guid
                && $this->_user_id
                && !midcom::get()->auth->acl->can_do_byguid('midgard:read', $leaf->guid, $leaf->object->__midcom_class_name__, $this->_user_id)) {
                continue;
            }
            // Rewrite all host-dependent URLs based on the relative URL within our topic tree.
            $leaf->fullurl = $fullprefix . $leaf->relativeurl;
            $leaf->absoluteurl = $absoluteprefix . $leaf->relativeurl;

            if (is_null($leaf->guid)) {
                $leaf->permalink = $leaf->fullurl;
            } else {
                $leaf->permalink = midcom::get()->permalinks->create_permalink($leaf->guid);
            }

            $result[$id] = $leaf;
        }

        return $result;
    }

    /**
     * Load the leaves for a given node from the database. Will complete all default fields to
     * provide full blown nap structures, and also build the base relative URLs which will
     * later be completed by the _get_leaves() interface functions.
     *
     * Important note:
     * - The IDs constructed for the leaves are the concatenation of the ID delivered by the component
     *   and the topics' GUID.
     *
     * @param midcom_helper_nav_node $node The node data structure for which to retrieve the leaves.
     * @return midcom_helper_nav_leaf[] All leaves found for that node, in complete post processed leave data structures.
     */
    private function _get_leaves_from_database(midcom_helper_nav_node $node)
    {
        // Retrieve a NAP instance
        $interface = $this->_get_component_interface($node->component, $node->object);
        if (!$interface) {
            return null;
        }

        $leafdata = $interface->get_leaves();
        $leaves = array();

        foreach ($leafdata as $id => $leaf) {
            $leaf = new midcom_helper_nav_leaf($node, $leaf, $id);
            // The leaf is complete, add it.
            $leaves[$leaf->id] = $leaf;
        }

        return $leaves;
    }

    /**
     * Writes the passed leaves to the cache, assigning them to the specified node.
     *
     * The function will bail out on any critical error. Data inconsistencies will be
     * logged and overwritten silently otherwise.
     *
     * @param midcom_helper_nav_node $node The node data structure to which the leaves should be assigned.
     * @param midcom_helper_nav_leaf[] $leaves The leaves to store in the cache.
     */
    private function _write_leaves_to_cache(midcom_helper_nav_node $node, $leaves)
    {
        debug_add('Writing ' . count($leaves) . ' leaves to the cache.');

        $cached_node = $this->_nap_cache->get_node($node->id);

        if (!$cached_node) {
            debug_add("NAP Caching Engine: Tried to update the topic {$node->name} (#{$node->object->id}) "
                . 'which was supposed to be in the cache already, but failed to load the object from the database.
                  Aborting write_to_cache, this is a critical cache inconsistency.', MIDCOM_LOG_WARN);
            return;
        }
        $cachedata = array();
        foreach ($leaves as $leaf) {
            if (is_object($leaf->object)) {
                $leaf->object = new midcom_core_dbaproxy($leaf->object->guid, get_class($leaf->object));
                $this->_nap_cache->put_guid($leaf->object->guid, $leaf->get_data());
            }
            $cachedata[$leaf->id] = $leaf->get_data();
        }

        $this->_nap_cache->put_leaves("{$node->id}-leaves", $cachedata);
    }

    private function _get_subnodes($parent_node)
    {
        if (isset(self::$_nodes[$parent_node]->subnodes)) {
            return self::$_nodes[$parent_node]->subnodes;
        }

        // Use midgard_collector to get the subnodes
        $id = (int) $parent_node;
        if (midcom::get()->config->get('symlinks')) {
            $id = self::$_nodes[$parent_node]->object->id;
        }
        $mc = midcom_db_topic::new_collector('up', $id);

        $mc->add_constraint('name', '<>', '');

        $mc->add_order('metadata.score', 'DESC');
        $mc->add_order('metadata.created');

        //we always write all the subnodes to cache and filter for ACLs after the fact
        midcom::get()->auth->request_sudo('midcom.helper.nav');
        $subnodes = $mc->get_values('id');
        midcom::get()->auth->drop_sudo();

        self::$_nodes[$parent_node]->subnodes = $subnodes;
        $this->_nap_cache->put_node($parent_node, self::$_nodes[$parent_node]->get_data());

        return $subnodes;
    }

    /**
     * Lists all Sub-nodes of $parent_node. If there are no subnodes you will get
     * an empty array, if there was an error (for instance an unknown parent node
     * ID) you will get false.
     *
     * @param mixed $parent_node    The ID of the node of which the subnodes are searched.
     * @param boolean $show_noentry Show all objects on-site which have the noentry flag set.
     * @return Array            An array of node IDs or false on failure.
     */
    public function list_nodes($parent_node, $show_noentry)
    {
        static $listed = array();

        if ($this->_loadNode($parent_node) !== MIDCOM_ERROK) {
            debug_add("Unable to load parent node $parent_node", MIDCOM_LOG_ERROR);
            return array();
        }

        $cache_identifier = $parent_node . (($show_noentry) ? 'noentry' : '');
        if (isset($listed[$cache_identifier])) {
            return $listed[$cache_identifier];
        }

        $subnodes = $this->_get_subnodes($parent_node);

        // No results, return an empty array
        if (count($subnodes) === 0) {
            $listed[$cache_identifier] = array();
            return $listed[$cache_identifier];
        }

        $up = $this->_up($parent_node);
        $node = (int) $parent_node;

        if (   $up
            || (   midcom::get()->config->get('symlinks')
                && $node != self::$_nodes[$parent_node]->object->id)) {
            $up = $this->_nodeid($node, $up);
        }

        $result = array();

        foreach ($subnodes as $id) {
            $subnode_id = $this->_nodeid($id, $up);

            if ($this->_loadNode($id, $up) !== MIDCOM_ERROK) {
                continue;
            }

            if (   !$show_noentry
                && self::$_nodes[$subnode_id]->noentry) {
                // Hide "noentry" items
                continue;
            }

            $result[] = $subnode_id;
        }

        $listed[$cache_identifier] = $result;
        return $listed[$cache_identifier];
    }

    /**
     * Lists all leaves of $parent_node. If there are no leaves you will get an
     * empty array, if there was an error (for instance an unknown parent node ID)
     * you will get false.
     *
     * @param mixed $parent_node    The ID of the node of which the leaves are searched.
     * @param boolean $show_noentry Show all objects on-site which have the noentry flag set.
     * @return Array             A list of leaves found, or false on failure.
     */
    public function list_leaves($parent_node, $show_noentry)
    {
        static $listed = array();

        if ($this->_loadNode($parent_node) !== MIDCOM_ERROK) {
            return array();
        }

        if (!array_key_exists($parent_node, $this->_loaded_leaves)) {
            $this->_load_leaves(self::$_nodes[$parent_node]);
        }

        if (isset($listed[$parent_node])) {
            return $listed[$parent_node];
        }

        $result = array();
        foreach ($this->_loaded_leaves[self::$_nodes[$parent_node]->id] as $id => $leaf) {
            if ($show_noentry || !$leaf->noentry) {
                $result[] = $id;
            }
        }

        $listed[$parent_node] = $result;
        return $result;
    }

    /**
     * This is a helper function used by midcom_helper_nav::resolve_guid(). It
     * checks if the object denoted by the passed GUID is already loaded into
     * memory and returns it, if available. This should speed up GUID lookup heavy
     * code.
     *
     * @param string $guid The GUID to look up in the NAP cache.
     * @return Array A NAP structure if the GUID is known, null otherwise.
     */
    public function get_loaded_object_by_guid($guid)
    {
        $entry = $this->_nap_cache->get_guid($guid);
        if (empty($entry)) {
            return null;
        }
        if ($entry[MIDCOM_NAV_TYPE] == 'leaf') {
            return $this->get_leaf($entry[MIDCOM_NAV_ID]);
        }
        return $this->get_node($entry[MIDCOM_NAV_ID]);
    }

    /**
     * This will give you a key-value pair describing the node with the ID
     * $node_id. The defined keys are described above in Node data interchange
     * format. You will get false if the node ID is invalid.
     *
     * @param mixed $node_id    The node ID to be retrieved.
     * @return Array        The node data as outlined in the class introduction, false on failure
     */
    public function get_node($node_id)
    {
        $node = $node_id;
        if (!empty($node->guid)) {
            $node_id = $node->id;
        }
        if ($this->_loadNode($node_id) != MIDCOM_ERROK) {
            return false;
        }

        return self::$_nodes[$node_id]->get_data();
    }

    /**
     * This will give you a key-value pair describing the leaf with the ID
     * $node_id. The defined keys are described above in leaf data interchange
     * format. You will get false if the leaf ID is invalid.
     *
     * @param string $leaf_id    The leaf-id to be retrieved.
     * @return Array        The leaf-data as outlined in the class introduction, false on failure
     */
    public function get_leaf($leaf_id)
    {
        if (!$this->_check_leaf_id($leaf_id)) {
            debug_add("This leaf is unknown, aborting.", MIDCOM_LOG_INFO);
            return false;
        }

        return $this->_leaves[$leaf_id]->get_data();
    }

    /**
     * Retrieve the ID of the currently displayed node. Defined by the topic of
     * the component that declared able to handle the request.
     *
     * @return mixed    The ID of the node in question.
     */
    public function get_current_node()
    {
        return $this->_current;
    }

    /**
     * Retrieve the ID of the currently displayed leaf. This is a leaf that is
     * displayed by the handling topic. If no leaf is active, this function
     * returns false. (Remember to make a type sensitive check, e.g.
     * nav::get_current_leaf() !== false to distinguish "0" and "false".)
     *
     * @return string    The ID of the leaf in question or false on failure.
     */
    public function get_current_leaf()
    {
        return $this->_currentleaf;
    }

    /**
     * Retrieve the ID of the upper node of the currently displayed node.
     *
     * @return mixed    The ID of the node in question.
     */
    public function get_current_upper_node()
    {
        if (count($this->_node_path) > 1) {
            return $this->_node_path[count($this->_node_path) - 2];
        }
        return $this->_node_path[0];
    }

    /**
     * Retrieve the ID of the root node. Note that this ID is dependent from the
     * ID of the MidCOM Root topic and therefore will change as easily as the
     * root topic ID might. The MIDCOM_NAV_URL entry of the root node's data will
     * always be empty.
     *
     * @return int    The ID of the root node.
     */
    public function get_root_node()
    {
        return $this->_root;
    }

    /**
     * Retrieve the IDs of the nodes from the URL. First value at key 0 is
     * the root node ID, possible second value is the first subnode ID etc.
     * Contains only visible nodes (nodes which can be loaded).
     *
     * @return Array    The node path array.
     */
    public function get_node_path()
    {
        return $this->_node_path;
    }

    /**
     * Returns the ID of the node to which $leaf_id is associated to, false
     * on failure.
     *
     * @param string $leaf_id    The Leaf-ID to search an uplink for.
     * @return mixed             The ID of the Node for which we have a match, or false on failure.
     */
    function get_leaf_uplink($leaf_id)
    {
        if (!$this->_check_leaf_id($leaf_id)) {
            debug_add("This leaf is unknown, aborting.", MIDCOM_LOG_ERROR);
            return false;
        }

        return $this->_leaves[$leaf_id]->nodeid;
    }

    /**
     * Returns the ID of the node to which $node_id is associated to, false
     * on failure. The root node's uplink is -1.
     *
     * @param mixed $node_id    The node ID to search an uplink for.
     * @return mixed             The ID of the node for which we have a match, -1 for the root node, or false on failure.
     */
    public function get_node_uplink($node_id)
    {
        if ($this->_loadNode($node_id) !== MIDCOM_ERROK) {
            return false;
        }

        return self::$_nodes[$node_id]->nodeid;
    }

    /**
     * Retrieve the up part from the given node ID.
     * (To get the topic ID part, just cast the node ID to int with (int).
     *  That's why there's no method for that. :))
     *
     * @param mixed $nodeid    The node ID.
     * @return mixed    The up part.
     */
    private function _up($nodeid)
    {
        static $cache = array();

        if (!isset($cache[$nodeid])) {
            $ids = explode("_", $nodeid);
            array_shift($ids);
            $cache[$nodeid] = implode('_', $ids);
        }

        return $cache[$nodeid];
    }

    /**
     * Generate node ID from topic ID and up value.
     *
     * @param int $topic_id    Topic ID.
     * @param mixed $up    The up part.
     * @return mixed    The generated node ID.
     */
    private function _nodeid($topic_id, $up)
    {
        if ($up) {
            $topic_id .= "_" . $up;
        }
        return $topic_id;
    }

    /**
     * Verifies the existence of a given leaf. Call this before getting a leaf from the
     * $_leaves cache. It will load all necessary nodes/leaves as necessary.
     *
     * @param string $leaf_id A valid NAP leaf id ($nodeid-$leafid pattern).
     * @return boolean true if the leaf exists, false otherwise.
     */
    private function _check_leaf_id($leaf_id)
    {
        if (!$leaf_id) {
            debug_add("Tried to load a suspicious leaf id, probably a false from get_current_leaf.");
            return false;
        }

        if (array_key_exists($leaf_id, $this->_leaves)) {
            return true;
        }

        $id_elements = explode('-', $leaf_id);

        $node_id = $id_elements[0];

        if ($this->_loadNode($node_id) !== MIDCOM_ERROK) {
            debug_add("Tried to verify the leaf id {$leaf_id}, which should belong to node {$node_id}, but this node cannot be loaded, see debug level log for details.",
                MIDCOM_LOG_INFO);
            return false;
        }

        $this->_load_leaves(self::$_nodes[$node_id]);

        return (array_key_exists($leaf_id, $this->_leaves));
    }

    /**
     * Determine a topic's parent id without loading the full object
     *
     * @param integer $topic_id The topic ID
     * @return integer The parent ID or false
     */
    private function _get_parent_id($topic_id)
    {
        $mc = midcom_db_topic::new_collector('id', $topic_id);
        $result = $mc->get_values('up');
        if (empty($result)) {
            return false;
        }
        return array_shift($result);
    }

    /**
     * @param string $component
     * @param midcom_db_topic $topic
     * @return midcom_baseclasses_components_interface
     */
    private function _get_component_interface($component, $topic = null)
    {
        $interface = $this->_loader->get_interface_class($component);
        if (!$interface) {
            debug_add("Could not get interface class of '{$component}'", MIDCOM_LOG_ERROR);
            return null;
        }
        if (   $topic !== null
            && !$interface->set_object($topic)) {
            debug_add("Could not set the NAP instance of '{$component}' to the topic {$topic->id}.", MIDCOM_LOG_ERROR);
            return null;
        }
        return $interface;
    }
}
