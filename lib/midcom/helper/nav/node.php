<?php
/**
 * @package midcom.helper.nav
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * @property string $guid
 * @property mixed $id
 * @property string $name
 * @property string $component
 * @property string $url
 * @property string $relativeurl
 * @property array $subnodes
 * @property mixed $object
 * @property boolean $noentry
 * @property int $nodeid
 * @package midcom.helper.nav
 */
class midcom_helper_nav_node
{
    private $topic_id;

    private $up;

    /**
     * @var midcom_helper_nav_backend
     */
    private $backend;

    private $data;

    private $loaded = false;

    public function __construct(midcom_helper_nav_backend $backend, $topic_id, $up = null)
    {
        $this->backend = $backend;
        $this->topic_id = $topic_id;
        $this->up = $up;
    }

    public function __get($name)
    {
        $name = $this->translate_name($name);
        $data = $this->get_data();
        if (!array_key_exists($name, $data)) {
            return null;
        }
        return $data[$name];
    }

    public function __set($name, $value)
    {
        $name = $this->translate_name($name);
        if (!$this->loaded) {
            $this->data = $this->prepare_data();
        }
        $this->data[$name] = $value;
    }

    public function  __isset($name)
    {
        $data = $this->get_data();
        return array_key_exists($name, $data);
    }

    private function translate_name($name)
    {
        $const = 'MIDCOM_NAV_' . strtoupper($name);
        if (defined($const)) {
            $name = constant($const);
        }
        return $name;
    }

    public function get_data()
    {
        if (!$this->loaded) {
            $this->data = $this->prepare_data();
            $this->loaded = true;
        }
        return $this->data;
    }

    private function prepare_data()
    {
        $data = false;

        if (!$this->up) {
            $data = midcom::get()->cache->nap->get_node($this->topic_id);
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