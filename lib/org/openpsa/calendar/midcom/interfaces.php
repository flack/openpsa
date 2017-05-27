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
implements midcom_services_permalinks_resolver
{
    /**
     * @return org_openpsa_calendar_event_dba
     */
    public static function create_root_event()
    {
        midcom::get()->auth->request_sudo('org.openpsa.calendar');
        $event = new org_openpsa_calendar_event_dba();
        $event->up = 0;
        $event->title = '__org_openpsa_calendar';
        //Fill in dummy dates to get around date range error
        $event->start = time();
        $event->end = time() + 1;
        $ret = $event->create();
        midcom::get()->auth->drop_sudo();
        if (!$ret) {
            debug_add('Failed to create OpenPSA root event, reason ' . midcom_connection::get_error_string(), MIDCOM_LOG_ERROR);
            throw new midcom_error('Failed to create the root event');
        }

        $siteconfig = org_openpsa_core_siteconfig::get_instance();
        $topic_guid = $siteconfig->get_node_guid('org.openpsa.calendar');

        if ($topic_guid) {
            $topic = new midcom_db_topic($topic_guid);
            $topic->set_parameter('org.openpsa.calendar', 'calendar_root_event', $event->guid);
        }

        return $event;
    }

    /**
     * Locates the root event
     *
     * @return org_openpsa_calendar_event_dba
     */
    public static function find_root_event()
    {
        $data = midcom_baseclasses_components_configuration::get('org.openpsa.calendar');

        //Check if we have already initialized
        if (!empty($data['calendar_root_event'])) {
            return $data['calendar_root_event'];
        }

        $root_guid = $data['config']->get('calendar_root_event');

        if (mgd_is_guid($root_guid)) {
            $root_event = org_openpsa_calendar_event_dba::get_cached($root_guid);
        } else {
            // Check for calendar event tree.
            $qb = org_openpsa_calendar_event_dba::new_query_builder();
            $qb->add_constraint('title', '=', '__org_openpsa_calendar');
            $qb->add_constraint('up', '=', 0);
            $ret = $qb->execute();
            if (!empty($ret)) {
                $root_event = $ret[0];
            } else {
                debug_add("OpenPSA Calendar root event could not be found", MIDCOM_LOG_ERROR);
                //Attempt to auto-initialize
                $root_event = self::create_root_event();
            }

            $siteconfig = org_openpsa_core_siteconfig::get_instance();
            $topic_guid = $siteconfig->get_node_guid('org.openpsa.calendar');
            if ($topic_guid) {
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
     * Returns button config for opening the new event popup
     */
    public static function get_create_button($node, $url)
    {
        if (empty($node[MIDCOM_NAV_FULLURL])) {
            throw new midcom_error('given node is not valid');
        }

        $workflow = new midcom\workflow\datamanager2;
        return $workflow->get_button($node[MIDCOM_NAV_ABSOLUTEURL] . "event/new/" . $url, array(
            MIDCOM_TOOLBAR_LABEL => midcom::get()->i18n->get_string('create event', 'org.openpsa.calendar'),
            MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/stock_new-event.png',
        ));
    }

    /**
     * Returns attribute string for opening the event popup
     */
    public static function get_viewer_attributes($guid, $node)
    {
        if (empty($node[MIDCOM_NAV_FULLURL])) {
            throw new midcom_error('given node is not valid');
        }
        $workflow = new midcom\workflow\viewer;
        return $workflow->render_attributes() . ' href="' . $node[MIDCOM_NAV_FULLURL] . 'event/' . $guid . '/"';
    }

    public function resolve_object_link(midcom_db_topic $topic, midcom_core_dbaobject $object)
    {
        if ($object instanceof org_openpsa_calendar_event_dba) {
            return "event/{$object->guid}/";
        }
        return null;
    }
}
