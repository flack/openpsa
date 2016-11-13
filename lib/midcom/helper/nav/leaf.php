<?php
/**
 * @package midcom.helper.nav
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * @package midcom.helper.nav
 */
class midcom_helper_nav_leaf extends midcom_helper_nav_item
{
    /**
     * @var midcom_helper_nav_node
     */
    private $node;

    private $leafid;

    public function __construct(midcom_helper_nav_node $node, array $data, $leafid)
    {
        $this->node = $node;
        $this->data = $data;
        $this->leafid = $leafid;
    }

    public function write_to_cache()
    {
        if (is_object($this->object)) {
            $this->object = new midcom_core_dbaproxy($this->object->guid, get_class($this->object));
            $this->get_cache()->put_guid($this->object->guid, $this->get_data());
        }
        return $this->get_data();
    }

    protected function prepare_data()
    {
        $topic = $this->node->object;

        if (!empty($this->data[MIDCOM_NAV_OBJECT])) {
            $this->data[MIDCOM_NAV_GUID] = $this->data[MIDCOM_NAV_OBJECT]->guid;
        } elseif (!empty($this->data[MIDCOM_NAV_GUID])) {
            try {
                $this->data[MIDCOM_NAV_OBJECT] = midcom::get()->dbfactory->get_object_by_guid($this->data[MIDCOM_NAV_GUID]);
            } catch (midcom_error $e) {
            }
        } else {
            debug_add("Warning: The leaf {$this->leafid} of topic {$topic->id} does set neither a GUID nor an object.");
            $this->data[MIDCOM_NAV_GUID] = null;
            $this->data[MIDCOM_NAV_OBJECT] = null;

            // Get the pseudo leaf score from the topic
            if (($score = $topic->get_parameter('midcom.helper.nav.score', "{$topic->id}-{$this->leafid}"))) {
                $this->data[MIDCOM_NAV_SCORE] = (int) $score;
            }
        }

        if (!isset($this->data[MIDCOM_NAV_SORTABLE])) {
            $this->data[MIDCOM_NAV_SORTABLE] = true;
        }

        // Score
        if (!isset($this->data[MIDCOM_NAV_SCORE])) {
            if (!empty($this->data[MIDCOM_NAV_OBJECT]->metadata->score)) {
                $this->data[MIDCOM_NAV_SCORE] = $this->data[MIDCOM_NAV_OBJECT]->metadata->score;
            } else {
                $this->data[MIDCOM_NAV_SCORE] = 0;
            }
        }

        // NAV_NOENTRY Flag
        if (!isset($this->data[MIDCOM_NAV_NOENTRY])) {
            $this->data[MIDCOM_NAV_NOENTRY] = false;
        }

        // complete NAV_NAMES where necessary
        if (trim($this->data[MIDCOM_NAV_NAME]) == '') {
            $this->data[MIDCOM_NAV_NAME] = midcom::get()->i18n->get_string('unknown', 'midcom');
        }

        // Some basic information
        $this->data[MIDCOM_NAV_TYPE] = 'leaf';
        $this->data[MIDCOM_NAV_ID] = "{$this->node->id}-{$this->leafid}";
        $this->data[MIDCOM_NAV_NODEID] = $this->node->id;
        $this->data[MIDCOM_NAV_RELATIVEURL] = $this->node->relativeurl . $this->data[MIDCOM_NAV_URL];
        if (!array_key_exists(MIDCOM_NAV_ICON, $this->data)) {
            $this->data[MIDCOM_NAV_ICON] = null;
        }

        // Save the original Leaf ID so that it is easier to query in topic-specific NAP code
        $this->data[MIDCOM_NAV_LEAFID] = $this->leafid;

        return $this->data;
    }
}