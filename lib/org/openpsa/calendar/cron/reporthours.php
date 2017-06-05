<?php
/**
 * @package org.openpsa.calendar
 * @author The Midgard Project, http://www.midgard-project.org
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
    /**
     * keyed by event guid
     *
     * @var array
     */
    private $event_links = [];

    /**
     * @var org_openpsa_calendar_event_dba
     */
    private $root_event;

    public function _on_initialize()
    {
        $this->root_event = org_openpsa_calendar_interface::find_root_event();
        return true;
    }

    /**
     * Search for events withing configured timeframe and if
     * they have confirmed relatedtos to tasks reports hours
     * for each participant (who is task resource) towards
     * said task.
     */
    public function _on_execute()
    {
        if (!midcom::get()->auth->request_sudo('org.openpsa.calendar')) {
            $this->print_error("Could not get sudo, aborting operation, see error log for details");
            return;
        }

        $qb = org_openpsa_calendar_event_member_dba::new_query_builder();
        // Event must be directly under openpsa calendar root event
        $qb->add_constraint('eid.up', '=', $this->root_event->id);
        // Event must have ended
        $qb->add_constraint('eid.end', '<', time());
        // Event can be at most week old
        // TODO: make max age configurable
        /* TODO: store a timestamp of last process in root event and use whichever
                 is nearer, though it has the issue with creating events after the fact
                 (which can happen when synchronizing from other systems for example)
        */
        $qb->add_constraint('eid.start', '>', time() - 24 * 3600 * 7);
        // Must not have hours reported already
        $qb->add_constraint('hoursReported', '=', 0);
        $eventmembers = $qb->execute();

        foreach ($eventmembers as $member) {
            // Bulletproofing: prevent duplicating hour reports
            $member->hoursReported = time();
            $member->notify_person = false;
            if (!$member->update()) {
                $msg = "Could not set hoursReported on member #{$member->id} (event #{$member->eid}), errstr: " . midcom_connection::get_error_string() . " skipping this member";
                $this->print_error($msg);
                continue;
            }
            $event = org_openpsa_calendar_event_dba::get_cached($member->eid);
            $links = $this->get_event_links($event->guid);

            foreach ($links as $link) {
                $task = org_openpsa_projects_task_dba::get_cached($link->toGuid);

                debug_add("processing task #{$task->id} ({$task->title}) for person #{$member->uid} from event #{$event->id} ({$event->title})");

                // Make sure the person we found is a resource in this particular task
                $task->get_members();
                if (!isset($task->resources[$member->uid])) {
                    debug_add("person #{$member->uid} is not a *resource* in task #{$task->id}, skipping");
                    continue;
                }

                if (!$this->create_hour_report($task, $member->uid, $event)) {
                    // MidCOM error log is filled in the method, here we just display error
                    $this->print_error("Failed to create hour_report to task #{$task->id} for person #{$member->uid} from event #{$event->id}");
                    // Failed to create hour_report, unset hoursReported so that we might have better luck next time
                    // PONDER: This might be an issue in case be have multiple tasks linked and only one of them fails... figure out a more granular way to flag reported hours ?
                    $member->hoursReported = 0;
                    if (!$member->update()) {
                        $msg = "Could not UNSET hoursReported on member #{$member->id} (event #{$member->eid}), errstr: " . midcom_connection::get_error_string();
                        $this->print_error($msg);
                    }
                }
            }
        }

        midcom::get()->auth->drop_sudo();
    }

    private function get_event_links($guid)
    {
        if (!isset($this->event_links[$guid])) {
            $qb2 = org_openpsa_relatedto_dba::new_query_builder();
            $qb2->add_constraint('fromGuid', '=', $guid);
            $qb2->add_constraint('fromComponent', '=', 'org.openpsa.calendar');
            $qb2->add_constraint('toComponent', '=', 'org.openpsa.projects');
            $qb2->add_constraint('toClass', '=', 'org_openpsa_projects_task_dba');
            $qb2->add_constraint('status', '=', org_openpsa_relatedto_dba::CONFIRMED);
            $this->event_links[$guid] = $qb2->execute();
        }
        return $this->event_links[$guid];
    }

    private function create_hour_report(org_openpsa_projects_task_dba $task, $person_id, org_openpsa_calendar_event_dba $event)
    {
        //TODO: this should probably have privileges like midgard:owner set to $person_id
        $hr = new org_openpsa_projects_hour_report_dba();
        $hr->task = $task->id;
        $hr->person = $person_id;
        $hr->invoiceable = $task->hoursInvoiceableDefault;

        $hr->date = $event->start;
        $hr->hours = round((($event->end - $event->start) / 3600), 2);
        // TODO: Localize ? better indicator that this is indeed from event ??
        $hr->description = "event: {$event->title} " . $this->_l10n->get_formatter()->timeframe($event->start, $event->end) . ", {$event->location}\n";
        $hr->description .= "\n{$event->description}\n";

        if (!$hr->create()) {
            return false;
        }
        debug_add("created hour_report #{$hr->id}");

        // Create a relatedtolink from hour_report to the object it was created from
        org_openpsa_relatedto_plugin::create($hr, 'org.openpsa.projects', $event, 'org.openpsa.calendar');

        return true;
    }
}
