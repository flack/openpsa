<?php
/**
 * @package org.openpsa.calendar
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: reporthours.php 25183 2010-02-23 22:19:32Z flack $
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Cron handler to report hours from events that have confirmed task links
 *
 * @package org.openpsa.calendar
 */
class org_openpsa_calendar_cron_reporthours extends midcom_baseclasses_components_cron_handler
{
    function _on_initialize()
    {
        return array_key_exists('org.openpsa.projects', $_MIDCOM->componentloader->manifests);
    }

    /**
     * Search for events withing configured timeframe and if
     * they have confirmed relatedtos to tasks reports hours
     * for each participant (who is task resource) towards
     * said task.
     */
    function _on_execute()
    {
        debug_push_class(__CLASS__, __FUNCTION__);
        debug_add('_on_execute called');
        
        $root_event = org_openpsa_calendar_interface::find_root_event();
        if ( !is_object($root_event))
        {
            debug_add('calendar root event not found', MIDCOM_LOG_WARN);
            debug_pop();
            return;
        }

        $_MIDCOM->load_library('org.openpsa.relatedto');
        if (!class_exists('org_openpsa_relatedto_dba'))
        {
            debug_add('relatedto library could not be loaded', MIDCOM_LOG_WARN);
            debug_pop();
            return;
        }

        $_MIDCOM->componentloader->load_graceful('org.openpsa.projects');
        if (!class_exists('org_openpsa_projects_task_dba'))
        {
            debug_add('org.openpsa.projects could not be loaded', MIDCOM_LOG_WARN);
            debug_pop();
            return;
        }
        if (!$_MIDCOM->auth->request_sudo('org.openpsa.calendar'))
        {
            $msg = "Could not get sudo, aborting operation, see error log for details";
            $this->print_error($msg);
            debug_add($msg, MIDCOM_LOG_ERROR);
            debug_pop();
            return;
        }

        $qb = org_openpsa_calendar_event_participant_dba::new_query_builder();
        // Event must be directly under openpsa calendar root event
        $qb->add_constraint('eid.up', '=', $root_event->id);
        // Member type must not be resource
        $qb->add_constraint('orgOpenpsaObtype', '<>', ORG_OPENPSA_OBTYPE_EVENTRESOURCE);
        // Event must have ended
        $qb->add_constraint('eid.end', '<', time());
        // Event can be at most week old
        // TODO: make max age configurable
        /* TODO: store a timestamp of last process in root event and use whichever
                 is nearer, though it has the issue with creating events after the fact
                 (which can happen when synchronizing from other systems for example)
        */
        $qb->add_constraint('eid.start', '>', time() - 24*3600*7);
        // Must not have hours reported already
        $qb->add_constraint('hoursReported', '=', 0);
        $eventmembers = $qb->execute();
        if (   !is_array($eventmembers)
            || count ($eventmembers) < 1)
        {
            $_MIDCOM->auth->drop_sudo();
            return;
        }

        // keyed by id
        $seen_events = array();
        // keyed by guid
        $seen_tasks = array();
        // keyed by guid
        $event_links = array();
        foreach($eventmembers as $member)
        {
            // Bulletproofing: prevent duplicating hour reports
            $member->hoursReported = time();
            if (!$member->update(false))
            {
                $msg = "Could not set hoursReported on member #{$member->id} (event #{$member->eid}), errstr: " . midcom_application::get_error_string() . " skipping this member";
                $this->print_error($msg);
                debug_add($msg, MIDCOM_LOG_ERROR);
                continue;
            }
            //Avoid multiple loads of same event
            if (!isset($seen_events[$member->eid]))
            {
                $seen_events[$member->eid] = new org_openpsa_calendar_event_dba($member->eid);
            }
            $event =& $seen_events[$member->eid];

            // Avoid multiple queries of events links
            if (!isset($event_links[$event->guid]))
            {
                $qb2 = org_openpsa_relatedto_dba::new_query_builder();
                $qb2->add_constraint('fromGuid', '=', $event->guid);
                $qb2->add_constraint('fromComponent', '=', 'org.openpsa.calendar');
                $qb2->add_constraint('toComponent', '=', 'org.openpsa.projects');
                $qb2->add_constraint('toClass', '=', 'org_openpsa_projects_task_dba');
                $qb2->add_constraint('status', '=', ORG_OPENPSA_RELATEDTO_STATUS_CONFIRMED);
                $event_links[$event->guid] = $qb2->execute();
            }
            $links =& $event_links[$event->guid];
            // These checks are done here (in stead of few lines above) on purpose
            if (   !is_array($links)
                || count ($links) < 1)
            {
                continue;
            }

            foreach($links as $link)
            {
                //Avoid multiple loads of same task
                if (!isset($seen_tasks[$link->toGuid]))
                {
                    $seen_tasks[$link->toGuid] = new org_openpsa_projects_task_dba($link->toGuid);
                }
                $task =& $seen_tasks[$link->toGuid];

                debug_add("processing task #{$task->id} ({$task->title}) for person #{$member->uid} from event #{$event->id} ({$event->title})");

                // Make sure the person we found is a resource in this particular task
                $task->get_members();
                if (!isset($task->resources[$member->uid]))
                {
                    debug_add("person #{$member->uid} is not a *resource* in task #{$task->id}, skipping");
                    continue;
                }

                if (!org_openpsa_projects_interface::create_hour_report($task, $member->uid, $event, 'org.openpsa.calendar'))
                {
                    // MidCOM error log is filled in the method, here we just display error
                    $this->print_error("Failed to create hour_report to task #{$task->id} for person #{$member->uid} from event #{$event->id}");
                    // Failed to create hour_report, unset hoursReported so that we might have better luck next time
                    // PONDER: This might be an issue in case be have multiple tasks linked and only one of them fails... figure out a more granular way to flag reported hours ?
                    $member->hoursReported = 0;
                    if (!$member->update(false))
                    {
                        $msg = "Could not UNSET hoursReported on member #{$member->id} (event #{$member->eid}), errstr: " . midcom_application::get_error_string();
                        $this->print_error($msg);
                        debug_add($msg, MIDCOM_LOG_WARN);
                    }
                    continue;
                }

            }
        }

        $_MIDCOM->auth->drop_sudo();
        debug_add('done');
        debug_pop();
        return;
    }
}
?>