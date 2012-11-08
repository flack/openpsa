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
{
    public function __construct()
    {
        $this->_autoload_libraries = array
        (
            'org.openpsa.core',
        );
    }

    public static function create_root_event()
    {
        midcom::get('auth')->request_sudo();
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

        $topic = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_CONTENTTOPIC);
        $topic->set_parameter('org.openpsa.calendar', 'calendar_root_event', $event->guid);

        return $event;
    }

    /**
     * Locates the root event
     */
    public static function find_root_event()
    {
        if (!midcom::get('componentloader')->is_loaded('org.openpsa.calendar'))
        {
            midcom::get('componentloader')->load_graceful('org.openpsa.calendar');
            //Doublecheck
            if (!midcom::get('componentloader')->is_loaded('org.openpsa.calendar'))
            {
                return false;
            }
        }

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
            $qb->add_constraint('up', '=', '0');
            $ret = $qb->execute();
            if (   is_array($ret)
                && count($ret) > 0)
            {
                $root_event = $ret[0];
                $siteconfig = org_openpsa_core_siteconfig::get_instance();
                $topic_guid = $siteconfig->get_node_guid('org.openpsa.calendar');
                if ($topic_guid)
                {
                    $topic = new midcom_db_topic($topic_guid);
                    $topic->set_parameter('org.openpsa.calendar', 'calendar_root_event', $root_event->guid);
                }
            }
            else
            {
                debug_add("OpenPSA Calendar root event could not be found", MIDCOM_LOG_ERROR);
                //Attempt to auto-initialize
                $root_event = self::create_root_event();
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
        $js = "window.open('{$url}', 'newevent', '" . self::_js_window_options($height, $width) . "'); return false;";

        return $js;
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
        $ret = "toolbar=0,";
        $ret .= "location=0,";
        $ret .= "status=0,";
        $ret .= "height={$height},";
        $ret .= "width={$width},";
        $ret .= "dependent=1,";
        $ret .= "alwaysRaised=1,";
        $ret .= "scrollbars=1,";
        $ret .= "resizable=1";
        return $ret;
    }

    /**
     * Verifies that given node has all we need to construct the popup
     */
    private static function _popup_verify_node($node)
    {
        if (   !is_array($node)
            || !array_key_exists(MIDCOM_NAV_FULLURL, $node)
            || !array_key_exists(MIDCOM_NAV_CONFIGURATION, $node)
            || empty($node[MIDCOM_NAV_FULLURL])
            || empty($node[MIDCOM_NAV_CONFIGURATION]))
        {
            debug_add('given node is not valid', MIDCOM_LOG_ERROR);
            return false;
        }
        return true;
    }

    public function _on_resolve_permalink($topic, $config, $guid)
    {
        try
        {
            $event = new org_openpsa_calendar_event_dba($guid);
            return "event/{$guid}/";
        }
        catch (midcom_error $e)
        {
            return null;
        }
    }

    /**
     * Support for contacts person merge
     */
    function org_openpsa_contacts_duplicates_merge_person(&$person1, &$person2, $mode)
    {
        switch($mode)
        {
            case 'all':
                break;
            /*
            case 'future':
                // Calendar should have future mode but we don't support it yet

                break;
            */
            default:
                // Mode not implemented
                debug_add("mode {$mode} not implemented", MIDCOM_LOG_ERROR);
                return false;
                break;
        }
        $qb = org_openpsa_calendar_event_member_dba::new_query_builder();
        $qb->begin_group('OR');
            // We need the remaining persons memberships later when we compare the two
            $qb->add_constraint('uid', '=', $person1->id);
            $qb->add_constraint('uid', '=', $person2->id);
        $qb->end_group();
        $members = $qb->execute();
        if ($members === false)
        {
            // Some error with QB
            debug_add('QB Error', MIDCOM_LOG_ERROR);
            return false;
        }
        // Transfer memberships
        $membership_map = array();
        foreach ($members as $member)
        {
            if ($member->uid != $person1->id)
            {
                debug_add("Transferred event membership #{$member->id} to person #{$person1->id} (from #{$member->uid})");
                $member->uid = $person1->id;
            }
            if (   !isset($membership_map[$member->eid])
                || !is_array($membership_map[$member->eid]))
            {
                $membership_map[$member->eid] = array();
            }
            $membership_map[$member->eid][] = $member;
        }
        unset($members);
        // Merge memberships
        foreach ($membership_map as $members)
        {
            foreach ($members as $member)
            {
                if (count($members) == 1)
                {
                    // We only have one membership in this event, skip rest of the logic
                    if (!$member->update())
                    {
                        // Failure updating member
                        debug_add("Failed to update eventmember #{$member->id}, errstr: " . midcom_connection::get_error_string(), MIDCOM_LOG_ERROR);
                        return false;
                    }
                    continue;
                }

                // TODO: Compare memberships to determine which of them are identical and thus not worth keeping
                if (!$member->update())
                {
                    // Failure updating member
                    debug_add("Failed to update eventmember #{$member->id}, errstr: " . midcom_connection::get_error_string(), MIDCOM_LOG_ERROR);
                    return false;
                }
            }
        }

        // Transfer metadata dependencies from classes that we drive
        $classes = array
        (
            'org_openpsa_calendar_event_dba',
            'org_openpsa_calendar_event_member_dba',
        );

        $metadata_fields = array
        (
            'creator' => 'guid',
            'revisor' => 'guid' // Though this will probably get touched on update we need to check it anyways to avoid invalid links
        );

        foreach ($classes as $class)
        {
            $ret = org_openpsa_contacts_duplicates_merge::person_metadata_dependencies_helper($class, $person1, $person2, $metadata_fields);
            if (!$ret)
            {
                // Failure updating metadata
                debug_add("Failed to update metadata dependencies in class {$class}, errsrtr: " . midcom_connection::get_error_string(), MIDCOM_LOG_ERROR);
                return false;
            }
        }
        return true;
    }
}
?>
