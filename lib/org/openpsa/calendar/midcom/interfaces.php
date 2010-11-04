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

    function __construct()
    {
        parent::__construct();

        $this->_component = 'org.openpsa.calendar';
        $this->_autoload_files = array();
        $this->_autoload_libraries = array
        (
            'org.openpsa.core',
        );

        /*
         * Calendar uses visibility permissions slightly differently than
         * midgard:read
         */
        $this->_acl_privileges['read'] = MIDCOM_PRIVILEGE_ALLOW;
    }

    function _on_initialize()
    {
        return true;
    }

    
    function create_root_event()
    {
        // Create the root event
        $event = new midcom_db_event();
        
        if (!$event->create())
        {
            $_MIDCOM->generate_error(MIDCOM_ERRCRIT, 'Failed to create the root event');
            // This will exit
        }
        
        $topic = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_CONTENTTOPIC);
        $topic->set_parameter($this->_component, 'calendar_root_event', $event->guid);
        
        return $event;
    }

    /**
     * Locates the root event
     */
    static function find_root_event()
    {
        if (!$_MIDCOM->componentloader->is_loaded('org.openpsa.calendar'))
        {
            $_MIDCOM->componentloader->load_graceful('org.openpsa.calendar');
            //Doublecheck
            if (!$_MIDCOM->componentloader->is_loaded('org.openpsa.calendar'))
            {
                return false;
            }
        }
        
        $data =& $GLOBALS['midcom_component_data']['org.openpsa.calendar'];
        
        //Check if we have already initialized
        if (isset($data['calendar_root_event'])
            && is_object($data['calendar_root_event']))
        {
            return $data['calendar_root_event'];
        }

        $root_event = false;
        $root_guid = $data['config']->get('calendar_root_event');
        
        if(mgd_is_guid($root_guid))
        {
            $root_event = new org_openpsa_calendar_event_dba($root_guid);
        }
        else
        {        
            // Check for calendar event tree.
            $qb = org_openpsa_calendar_event_dba::new_query_builder();
            $qb->add_constraint('title', '=', '__org_openpsa_calendar');
            $qb->add_constraint('up', '=', '0');
            $qb->add_constraint('sitegroup', '=', $_MIDGARD['sitegroup']);
            $ret = $qb->execute();
            if (   is_array($ret)
                && count($ret) > 0)
            {
                $root_event = $ret[0];
                $siteconfig = org_openpsa_core_siteconfig::get_instance();
                $topic_guid = $siteconfig->get_node_guid('org.openpsa.calendar');
                if($topic_guid)
                {
                    $topic = new midcom_db_topic($topic_guid);
                    $topic->set_parameter('org.openpsa.calendar', 'calendar_root_event', $root_event->guid);
                }
            }
            else
            {
                debug_push_class(__CLASS__, __FUNCTION__);
                debug_add("OpenPSA Calendar root event could not be found", MIDCOM_LOG_ERROR);
                //Attempt to auto-initialize
                $_MIDCOM->auth->request_sudo();
                $event = new midcom_db_event();
                $event->up = 0;
                $event->title = '__org_openpsa_calendar';
                //Fill in dummy dates to get around date range error 
                $event->start = time();
                $event->end = time() + 1;
                $ret = $event->create();
                $_MIDCOM->auth->drop_sudo();
                if (!$ret)
                {
                    $root_event = false;
                    debug_add('Failed to create OpenPSA root event, reason ' . midcom_application::get_error_string(), MIDCOM_LOG_ERROR);
                    //If we return false here ACL editor etc will choke
                    //return false;
                }
                else
                {
                    $root_event = $event;
                }
                debug_pop();
            }
        }
        $data['calendar_root_event'] = $root_event;
        return $root_event;
    }

    /**
     * Iterate over all events and create index record using the datamanager indexer
     * method.
     */
    function _on_reindex($topic, $config, &$indexer)
    {
        debug_push_class(__CLASS__, __FUNCTION__);

        $_MIDCOM->load_library('midcom.helper.datamanager2');
        $root_event = self::find_root_event();

        $qb = org_openpsa_calendar_event_dba::new_query_builder();
        $qb->add_constraint('up', '=',  $root_event->id);
        $ret = $qb->execute();
        if (   is_array($ret)
            && count($ret) > 0)
        {
            $schema = midcom_helper_datamanager2_schema::load_database($config->get('schemadb'));
            $datamanager = new midcom_helper_datamanager2_datamanager($schema);

            foreach ($ret as $event)
            {
                if (! $datamanager)
                {
                    debug_add('Warning, failed to create a datamanager instance with this schemapath:' . $this->_config->get('schemadb'),
                        MIDCOM_LOG_WARN);
                    continue;
                }

                if (! $datamanager->autoset_storage($event))
                {
                    debug_add("Warning, failed to initialize datamanager for Event {$event->id}. See Debug Log for details.", MIDCOM_LOG_WARN);
                    debug_print_r('Event dump:', $event);
                    continue;
                }

                $indexer->index($datamanager);
            }
        }
        debug_pop();
        return true;
    }

    /**
     * Returns string of JS code for opening the new event popup
     */
    function calendar_newevent_js($node, $start = false, $resource = false, $url_append = '')
    {
        if (!org_openpsa_calendar_interface::_popup_verify_node($node))
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
        $js = "window.open('{$url}', 'newevent', '" . org_openpsa_calendar_interface::_js_window_options($height, $width) . "'); return false;";

        return $js;
    }

    /**
     * Returns string of JS code for opening the edit event popup
     *
     * PONDER: In theory we should be able to get the node with just the event guid ?
     */
    function calendar_editevent_js($event, $node)
    {
        if (!org_openpsa_calendar_interface::_popup_verify_node($node))
        {
            return false;
        }

        $height = $node[MIDCOM_NAV_CONFIGURATION]->get('calendar_popup_height');
        $width = $node[MIDCOM_NAV_CONFIGURATION]->get('calendar_popup_width');

        $js = "window.open('{$node[MIDCOM_NAV_FULLURL]}event/{$event}/', ";
        $js .= "'event_{$event}', '" . org_openpsa_calendar_interface::_js_window_options($height, $width) . "'); return false;";

        return $js;
    }

    /**
     * Returns string of correct window options for JS
     */
    function _js_window_options($height, $width)
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
    function _popup_verify_node($node)
    {
        if (   !is_array($node)
            || !array_key_exists(MIDCOM_NAV_FULLURL, $node)
            || !array_key_exists(MIDCOM_NAV_CONFIGURATION, $node)
            || empty($node[MIDCOM_NAV_FULLURL])
            || empty($node[MIDCOM_NAV_CONFIGURATION]))
        {
            debug_push_class(__CLASS__, __FUNCTION__);
            debug_add('given node is not valid', MIDCOM_LOG_ERROR);
            debug_pop();
            return false;
        }
        return true;
    }

    function _on_resolve_permalink($topic, $config, $guid)
    {
        $event = new org_openpsa_calendar_event_dba($guid);

        if (   is_object($event)
            && $event->id)
        {
            return "event/{$guid}/";
            break;
        }
        return null;
    }

    /**
     * Support for contacts person merge
     */
    function org_openpsa_contacts_duplicates_merge_person(&$person1, &$person2, $mode)
    {
        debug_push_class(__CLASS__, __FUNCTION__);
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
                debug_pop();
                return false;
                break;
        }
        $qb = org_openpsa_calendar_event_member_dba::new_query_builder();
        // Make sure we stay in current SG even if we could see more
        $qb->add_constraint('sitegroup', '=', $_MIDGARD['sitegroup']);
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
            debug_pop();
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
        foreach ($membership_map as $eid => $members)
        {
            foreach ($members as $key => $member)
            {
                if (count($members) == 1)
                {
                    // We only have one membership in this event, skip rest of the logic
                    if (!$member->update())
                    {
                        // Failure updating member
                        debug_add("Failed to update eventmember #{$member->id}, errstr: " . midcom_application::get_error_string(), MIDCOM_LOG_ERROR);
                        debug_pop();
                        return false;
                    }
                    continue;
                }

                // TODO: Compare memberships to determine which of them are identical and thus not worth keeping

                if (!$member->update())
                {
                    // Failure updating member
                    debug_add("Failed to update eventmember #{$member->id}, errstr: " . midcom_application::get_error_string(), MIDCOM_LOG_ERROR);
                    debug_pop();
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

        foreach($classes as $class)
        {
            $ret = org_openpsa_contacts_duplicates_merge::person_metadata_dependencies_helper($class, $person1, $person2, $metadata_fields);
            if (!$ret)
            {
                // Failure updating metadata
                debug_add("Failed to update metadata dependencies in class {$class}, errsrtr: " . midcom_application::get_error_string(), MIDCOM_LOG_ERROR);
                debug_pop();
                return false;
            }
        }
        debug_pop();
        return true;
    }

}
?>