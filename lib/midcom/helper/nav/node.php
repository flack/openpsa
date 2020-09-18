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
    private $topic;

    private $topic_id;

    public function __construct($topic)
    {
        if (is_a($topic, midcom_db_topic::class)) {
            $this->topic = $topic;
            $this->topic_id = $topic->id;
        } else {
            $this->topic_id = $topic;
        }
    }

    public function is_readable_by(string $user_id) : bool
    {
        return (   !$user_id
                || !$this->guid
                || midcom::get()->auth->acl->can_do_byguid('midgard:read', $this->guid, midcom_db_topic::class, $user_id));
    }

    public function get_subnodes() : array
    {
        if (!isset($this->subnodes)) {
            if ((int) $this->topic_id == 0) {
                $this->subnodes = [];
            } else {
                // Use midgard_collector to get the subnodes
                $mc = midcom_db_topic::new_collector('up', (int) $this->topic_id);
                $mc->add_constraint('name', '<>', '');
                $mc->add_order('metadata.score', 'DESC');
                $mc->add_order('metadata.created');

                //we always write all the subnodes to cache and filter for ACLs after the fact
                midcom::get()->auth->request_sudo('midcom.helper.nav');
                $subnodes = $mc->get_values('id');
                midcom::get()->auth->drop_sudo();

                $this->subnodes = $subnodes;
            }
            $this->get_cache()->put_node($this->topic_id, $this->get_data());
        }

        return $this->subnodes;
    }

    /**
     * @return midcom_helper_nav_leaf[]
     */
    public function get_leaves() : array
    {
        $leaves = $this->get_cache()->get_leaves("{$this->id}-leaves");
        $from_cache = (false !== $leaves);
        if (!$from_cache) {
            debug_add('The leaves have not yet been loaded from the database, we do this now.');

            // we always write all the leaves to cache and filter for ACLs after the fact
            midcom::get()->auth->request_sudo('midcom.helper.nav');
            if ($nap = $this->get_component_nap($this->object)) {
                $leaves = $nap->get_leaves();
            }
            midcom::get()->auth->drop_sudo();
        }

        $result = [];
        foreach ($leaves as $id => $leaf) {
            $leaf = new midcom_helper_nav_leaf($this, $leaf, $id, $from_cache);
            $result[$leaf->id] = $leaf;
        }
        if (!$from_cache) {
            $this->write_leaves_to_cache($result);
        }
        return $result;
    }

    /**
     * Writes the passed leaves to the cache, assigning them to the specified node.
     *
     * The function will bail out on any critical error. Data inconsistencies will be
     * logged and overwritten silently otherwise.
     *
     * @param midcom_helper_nav_leaf[] $leaves The leaves to store in the cache.
     */
    private function write_leaves_to_cache(array $leaves)
    {
        if (!$this->get_cache()->get_node($this->id)) {
            debug_add("NAP Caching Engine: Tried to update the topic {$this->name} (#{$this->object->id}) "
            . 'which was supposed to be in the cache already, but failed to load the object from the database.
                  Aborting write_to_cache, this is a critical cache inconsistency.', MIDCOM_LOG_WARN);
            return;
        }
        $cachedata = [];
        foreach ($leaves as $leaf) {
            $cachedata[$leaf->id] = $leaf->write_to_cache();
        }

        debug_add('Writing ' . count($cachedata) . ' leaves to the cache.');
        $this->get_cache()->put_leaves("{$this->id}-leaves", $cachedata);
    }

    protected function prepare_data() : array
    {
        $data = $this->get_cache()->get_node($this->topic_id);

        if (!$data) {
            midcom::get()->auth->request_sudo('midcom.helper.nav');
            $data = $this->load_data();
            midcom::get()->auth->drop_sudo();

            if ($data === null) {
                debug_add('We got null for this node, so we do not have any NAP information, returning directly.');
                return [];
            }

            $this->get_cache()->put_node($data[MIDCOM_NAV_ID], $data);
            debug_add("Added the ID {$data[MIDCOM_NAV_ID]} to the cache.");
        }

        return $data;
    }

    private function load_data() : ?array
    {
        if (empty($this->topic)) {
            $topic = new midcom_core_dbaproxy($this->topic_id, midcom_db_topic::class);
        } else {
            $topic = $this->topic;
        }

        // Retrieve a NAP instance
        $nap = $this->get_component_nap($topic);
        if (!$nap) {
            return null;
        }

        // Get the node data and verify this is a node that actually has any relevant NAP
        // information. Internal components which don't have
        // a NAP interface yet return null here, to be exempt from any NAP processing.
        $data = $nap->get_node();
        if ($data === null) {
            debug_add("The component '{$topic->component}' did return null for the topic {$topic->id}, indicating no NAP information is available.");
            return null;
        }

        // Now complete the node data structure
        $data[MIDCOM_NAV_NAME] = trim($data[MIDCOM_NAV_NAME]) == '' ? $topic->name : $data[MIDCOM_NAV_NAME];
        $data[MIDCOM_NAV_URL] = $topic->name . '/';
        $data[MIDCOM_NAV_GUID] = $topic->guid;
        $data[MIDCOM_NAV_ID] = $topic->id;
        $data[MIDCOM_NAV_NODEID] = $topic->up;
        $data[MIDCOM_NAV_TYPE] = 'node';
        $data[MIDCOM_NAV_SCORE] = $topic->metadata->score;
        $data[MIDCOM_NAV_COMPONENT] = $topic->component;
        $data[MIDCOM_NAV_SORTABLE] = true;

        if (!isset($data[MIDCOM_NAV_CONFIGURATION])) {
            $data[MIDCOM_NAV_CONFIGURATION] = null;
        }

        if (empty($data[MIDCOM_NAV_NOENTRY])) {
            $data[MIDCOM_NAV_NOENTRY] = (bool) $topic->metadata->get('navnoentry');
        }
        $data[MIDCOM_NAV_OBJECT] = $topic;

        return $data;
    }

    /**
     * @param midcom_db_topic $topic
     * @return midcom_baseclasses_components_navigation
     */
    private function get_component_nap($topic) : ?midcom_baseclasses_components_navigation
    {
        if (!$topic->component) {
            return null;
        }
        $interface = midcom::get()->componentloader->get_interface_class($topic->component);
        $nap = $interface->get_nap_instance();
        if (!$nap->set_object($topic)) {
            debug_add("Could not set the NAP instance of '{$topic->component}' to the topic {$topic->id}.", MIDCOM_LOG_ERROR);
            return null;
        }
        return $nap;
    }
}
