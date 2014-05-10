<?php
/**
 * @package org.openpsa.calendar
 * @author Nemein Oy http://www.nemein.com/
 * @copyright Nemein Oy http://www.nemein.com/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * OpenPSA group calendar
 *
 *
 * @package org.openpsa.calendar
 */
class org_openpsa_calendar_interface extends midcom_baseclasses_components_interface
implements midcom_services_permalinks_resolver, org_openpsa_contacts_duplicates_support
{
    public static function create_root_event()
    {
        midcom::get('auth')->request_sudo('org.openpsa.calendar');
        $event = new midcom_db_event();
        $event->up = 0;
        $event->title = '__org_openpsa_calendar';
        //Fill in dummy dates to get around date range error
        $event->start = time();
        $event->end = time() + 1;
        $ret = $event->create();
        midcom::get('auth')->drop_sudo();
        if (!$ret)
        {
            debug_add('Failed to create OpenPSA root event, reason ' . midcom_connection::get_error_string(), MIDCOM_LOG_ERROR);
            throw new midcom_error('Failed to create the root event');
        }

        $siteconfig = org_openpsa_core_siteconfig::get_instance();
        $topic_guid = $siteconfig->get_node_guid('org.openpsa.calendar');

        if ($topic_guid)
        {
            $topic = new midcom_db_topic($topic_guid);
            $topic->set_parameter('org.openpsa.calendar', 'calendar_root_event', $event->guid);
        }

        return $event;
    }

    /**
     * Locates the root event
     */
    public static function find_root_event()
    {
        $data = midcom_baseclasses_components_configuration::get('org.openpsa.calendar');

        //Check if we have already initialized
        if (   isset($data['calendar_root_event'])
            && is_object($data['calendar_root_event']))
        {
            return $data['calendar_root_event'];
        }

        $root_event = false;
        $root_guid = $data['config']->get('calendar_root_event');

        if (mgd_is_guid($root_guid))
        {
            $root_event = org_openpsa_calendar_event_dba::get_cached($root_guid);
        }
        else
        {
            // Check for calendar event tree.
            $qb = org_openpsa_calendar_event_dba::new_query_builder();
            $qb->add_constraint('title', '=', '__org_openpsa_calendar');
            $qb->add_constraint('up', '=', 0);
            $ret = $qb->execute();
            if (!empty($ret))
            {
                $root_event = $ret[0];
            }
            else
            {
                debug_add("OpenPSA Calendar root event could not be found", MIDCOM_LOG_ERROR);
                //Attempt to auto-initialize
                $root_event = self::create_root_event();
            }

            $siteconfig = org_openpsa_core_siteconfig::get_instance();
            $topic_guid = $siteconfig->get_node_guid('org.openpsa.calendar');
            if ($topic_guid)
            {
                $topic = new midcom_db_topic($topic_guid);
                $topic->set_parameter('org.openpsa.calendar', 'calendar_root_event', $root_event->guid);
                $data['config']->set('calendar_root_event', $root_event->guid);
            }
        }
        $data['calendar_root_event'] = $root_event;
        return $root_event;
    }

    /**
     * Prepare the indexer client
     */
    public function _on_reindex($topic, $config, &$indexer)
    {
        $root_event = self::find_root_event();

        $qb = org_openpsa_calendar_event_dba::new_query_builder();
        $qb->add_constraint('up', '=',  $root_event->id);
        $schemadb = midcom_helper_datamanager2_schema::load_database($config->get('schemadb'));

        $indexer = new org_openpsa_calendar_midcom_indexer($topic, $indexer);
        $indexer->add_query('events', $qb, $schemadb);

        return $indexer;
    }

    /**
     * Returns string of JS code for opening the new event popup
     */
    public static function calendar_newevent_js($node, $start = false, $resource = false, $url_append = '')
    {
        if (!self::_popup_verify_node($node))
        {
            return false;
        }

        $height = $node[MIDCOM_NAV_CONFIGURATION]->get('calendar_popup_height');
        $width = $node[MIDCOM_NAV_CONFIGURATION]->get('calendar_popup_width');

        if (   $resource
            && $start)
        {
            $url = "{$node[MIDCOM_NAV_FULLURL]}event/new/{$resource}/{$start}/";
        }
        else if ($resource)
        {
            $url = "{$node[MIDCOM_NAV_FULLURL]}event/new/{$resource}/";
        }
        else
        {
            $url = "{$node[MIDCOM_NAV_FULLURL]}event/new/";
        }
        $url .= $url_append;
        return "window.open('{$url}', 'newevent', '" . self::_js_window_options($height, $width) . "'); return false;";
    }

    /**
     * Returns string of JS code for opening the edit event popup
     *
     * PONDER: In theory we should be able to get the node with just the event guid ?
     */
    public static function calendar_editevent_js($event, $node)
    {
        if (!self::_popup_verify_node($node))
        {
            return false;
        }

        $height = $node[MIDCOM_NAV_CONFIGURATION]->get('calendar_popup_height');
        $width = $node[MIDCOM_NAV_CONFIGURATION]->get('calendar_popup_width');

        $js = "window.open('{$node[MIDCOM_NAV_FULLURL]}event/{$event}/', ";
        $js .= "'event_{$event}', '" . self::_js_window_options($height, $width) . "'); return false;";

        return $js;
    }

    /**
     * Returns string of correct window options for JS
     */
    private static function _js_window_options($height, $width)
    {
        $ret = "toolbar=0,location=0,status=0,height={$height},width={$width},";
        $ret .= "dependent=1,alwaysRaised=1,scrollbars=1,resizable=1";
        return $ret;
    }

    /**
     * Verifies that given node has all we need to construct the popup
     */
    private static function _popup_verify_node($node)
    {
        if (   empty($node[MIDCOM_NAV_FULLURL])
            || empty($node[MIDCOM_NAV_CONFIGURATION]))
        {
            debug_add('given node is not valid', MIDCOM_LOG_ERROR);
            return false;
        }
        return true;
    }

    public function resolve_object_link(midcom_db_topic $topic, midcom_core_dbaobject $object)
    {
        if ($object instanceof org_openpsa_calendar_event_dba)
        {
            return "event/{$object->guid}/";
        }
        return null;
    }

    public function get_merge_configuration($object_mode, $merge_mode)
    {
        $config = array();
        if ($merge_mode == 'future')
        {
            // Contacts does not have future references so we have nothing to transfer...
            return $config;
        }
        if ($object_mode == 'person')
        {
            $config['org_openpsa_calendar_event_member_dba'] = array
            (
                'uid' => array
                (
                    'target' => 'id',
                    'duplicate_check' => 'eid'
                )
            );

            $config['org_openpsa_calendar_event_dba'] = array();

        }
        return $config;
    }
}
?>
