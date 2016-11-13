<?php
/**
 * @package midcom.helper.nav
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * @property array $subnodes
 * @package midcom.helper.nav
 */
class midcom_helper_nav_node extends midcom_helper_nav_item
{
    private $topic_id;

    private $up;

    /**
     * @var midcom_helper_nav_backend
     */
    private $backend;

    public function __construct(midcom_helper_nav_backend $backend, $topic_id, $up = null)
    {
        $this->backend = $backend;
        $this->topic_id = $topic_id;
        $this->up = $up;
    }

    public function get_subnodes()
    {
        if (!isset($this->subnodes)) {
            // Use midgard_collector to get the subnodes
            $id = (int) $this->topic_id;
            if (midcom::get()->config->get('symlinks')) {
                $id = $this->object->id;
            }
            $mc = midcom_db_topic::new_collector('up', $id);
            $mc->add_constraint('name', '<>', '');
            $mc->add_order('metadata.score', 'DESC');
            $mc->add_order('metadata.created');

            //we always write all the subnodes to cache and filter for ACLs after the fact
            midcom::get()->auth->request_sudo('midcom.helper.nav');
            $subnodes = $mc->get_values('id');
            midcom::get()->auth->drop_sudo();

            $this->subnodes = $subnodes;
            $this->get_cache()->put_node($this->topic_id, $this->get_data());
        }

        return $this->subnodes;
    }

    /**
     * @return midcom_helper_nav_leaf[]
     */
    public function get_leaves()
    {
        $entry_name = "{$this->id}-leaves";

        $leaves = $this->get_cache()->get_leaves($entry_name);

        if (false === $leaves) {
            debug_add('The leaves have not yet been loaded from the database, we do this now.');

            //we always write all the leaves to cache and filter for ACLs after the fact
            midcom::get()->auth->request_sudo('midcom.helper.nav');
            $leaves = $this->load_leaves();
            midcom::get()->auth->drop_sudo();

            $this->write_leaves_to_cache($leaves);
            return $leaves;
        }
        $result = array();
        foreach ($leaves as $id => $leaf) {
            $result[$id] = new midcom_helper_nav_leaf($this, $leaf, $id);
        }
        return $result;
    }


    /**
     * Load the leaves from the database
     *
     * Important note:
     * - The IDs constructed for the leaves are the concatenation of the ID delivered by the component
     *   and the topics' GUID.
     *
     * @return midcom_helper_nav_leaf[] All leaves found for this node
     */
    private function load_leaves()
    {
        // Retrieve a NAP instance
        $interface = $this->get_component_interface($this->object);
        if (!$interface) {
            return null;
        }

        $leafdata = $interface->get_leaves();
        $leaves = array();

        foreach ($leafdata as $id => $leaf) {
            $leaf = new midcom_helper_nav_leaf($this, $leaf, $id);
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
     * @param midcom_helper_nav_leaf[] $leaves The leaves to store in the cache.
     */
    private function write_leaves_to_cache($leaves)
    {
        debug_add('Writing ' . count($leaves) . ' leaves to the cache.');

        $cached_node = $this->get_cache()->get_node($this->id);

        if (!$cached_node) {
            debug_add("NAP Caching Engine: Tried to update the topic {$this->name} (#{$this->object->id}) "
            . 'which was supposed to be in the cache already, but failed to load the object from the database.
                  Aborting write_to_cache, this is a critical cache inconsistency.', MIDCOM_LOG_WARN);
            return;
        }
        $cachedata = array();
        foreach ($leaves as $leaf) {
            $cachedata[$leaf->id] = $leaf->write_to_cache();
        }

        $this->get_cache()->put_leaves("{$this->id}-leaves", $cachedata);
    }

    protected function prepare_data()
    {
        $data = false;

        if (!$this->up) {
            $data = $this->get_cache()->get_node($this->topic_id);
        }

        if (!$data) {
            midcom::get()->auth->request_sudo('midcom.helper.nav');
            $data = $this->load_data();
            midcom::get()->auth->drop_sudo();

            if (is_null($data)) {
                debug_add('We got null for this node, so we do not have any NAP information, returning null directly.');
                return null;
            }

            midcom::get()->cache->nap->put_node($data[MIDCOM_NAV_ID], $data);
            debug_add("Added the ID {$data[MIDCOM_NAV_ID]} to the cache.");
        }

        // Rewrite all host dependant URLs based on the relative URL within our topic tree.
        $data[MIDCOM_NAV_FULLURL] = midcom::get()->config->get('midcom_site_url') . $data[MIDCOM_NAV_RELATIVEURL];
        $data[MIDCOM_NAV_ABSOLUTEURL] = midcom_connection::get_url('self') . $data[MIDCOM_NAV_RELATIVEURL];
        $data[MIDCOM_NAV_PERMALINK] = midcom::get()->permalinks->create_permalink($data[MIDCOM_NAV_GUID]);

        return $data;
    }

    private function load_data()
    {
        $topic = new midcom_core_dbaproxy($this->topic_id, 'midcom_db_topic');
        if (!$topic->guid) {
            debug_add("Could not load Topic #{$this->topic_id}: " . midcom_connection::get_error_string(), MIDCOM_LOG_ERROR);
            return null;
        }

        $urltopic = $topic;

        if (   midcom::get()->config->get('symlinks')
            && !empty($urltopic->symlink)) {
            $topic = new midcom_core_dbaproxy($urltopic->symlink, 'midcom_db_topic');

            if (!$topic->guid) {
                debug_add("Could not load target for symlinked topic {$urltopic->id}: " . midcom_connection::get_error_string(), MIDCOM_LOG_ERROR);
                $topic = $urltopic;
            }
        }

        // Retrieve a NAP instance
        $interface = $this->get_component_interface($topic);
        if (!$interface) {
            return null;
        }

        // Get the node data and verify this is a node that actually has any relevant NAP
        // information. Internal components which don't have
        // a NAP interface yet return null here, to be exempt from any NAP processing.
        $data = $interface->get_node();
        if (is_null($data)) {
            debug_add("The component '{$topic->component}' did return null for the topic {$topic->id}, indicating no NAP information is available.");
            return null;
        }

        $id = $urltopic->id;
        if ($this->up) {
            $id .= "_" . $this->up;
        }
        // Now complete the node data structure

        $data[MIDCOM_NAV_URL] = $urltopic->name . '/';
        $data[MIDCOM_NAV_NAME] = trim($data[MIDCOM_NAV_NAME]) == '' ? $topic->name : $data[MIDCOM_NAV_NAME];
        $data[MIDCOM_NAV_GUID] = $urltopic->guid;
        $data[MIDCOM_NAV_ID] = $id;
        $data[MIDCOM_NAV_TYPE] = 'node';
        $data[MIDCOM_NAV_SCORE] = $urltopic->metadata->score;
        $data[MIDCOM_NAV_COMPONENT] = $topic->component;
        $data[MIDCOM_NAV_SORTABLE] = true;

        if (!isset($data[MIDCOM_NAV_CONFIGURATION])) {
            $data[MIDCOM_NAV_CONFIGURATION] = null;
        }

        if (empty($data[MIDCOM_NAV_NOENTRY])) {
            $data[MIDCOM_NAV_NOENTRY] = (bool) $urltopic->metadata->get('navnoentry');
        }
        $data[MIDCOM_NAV_OBJECT] = $topic;

        if ($urltopic->id == $this->backend->get_root_node()) {
            $data[MIDCOM_NAV_NODEID] = -1;
            $data[MIDCOM_NAV_RELATIVEURL] = '';
        } else {
            if (!$this->up || $this->backend->get_node($this->up) === false) {
                $this->up = $urltopic->up;
            }
            $data[MIDCOM_NAV_NODEID] = $this->up;

            if (!$data[MIDCOM_NAV_NODEID]) {
                return null;
            }
            $parent = $this->backend->get_node($data[MIDCOM_NAV_NODEID]);
            if ($parent === false) {
                return null;
            }

            $data[MIDCOM_NAV_RELATIVEURL] = $parent[MIDCOM_NAV_RELATIVEURL] . $data[MIDCOM_NAV_URL];
        }

        return $data;
    }

    /**
     * @param midcom_db_topic $topic
     * @return midcom_baseclasses_components_interface
     */
    private function get_component_interface($topic)
    {
        $interface = midcom::get()->componentloader->get_interface_class($topic->component);
        if (!$interface) {
            debug_add("Could not get interface class of '{$topic->component}'", MIDCOM_LOG_ERROR);
            return null;
        }
        if (!$interface->set_object($topic)) {
            debug_add("Could not set the NAP instance of '{$topic->component}' to the topic {$topic->id}.", MIDCOM_LOG_ERROR);
            return null;
        }
        return $interface;
    }
}