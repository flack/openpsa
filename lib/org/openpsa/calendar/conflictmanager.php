<?php
/**
 * @package org.openpsa.calendar
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * Eventmember conflict manager
 *
 * @package org.openpsa.calendar
 */
class org_openpsa_calendar_conflictmanager
{
    /**
     * In case of busy eventmembers this is an array
     *
     * @var mixed
     */
    var $busy_members = false;

    /**
     * In case of busy event resources this is an array
     *
     * @var mixed
     */
    var $busy_resources = false;

    /**
     * The event we're working on
     *
     * @var org_openpsa_calendar_event_dba
     */
    private $_event;

    public function __construct(org_openpsa_calendar_event_dba $event)
    {
        $this->_event = $event;
    }

    private function _add_event_constraints(&$qb, $fieldname = 'eid')
    {
        $qb->add_constraint($fieldname . '.busy', '<>', false);
        if ($this->_event->id)
        {
            $qb->add_constraint($fieldname . '.id', '<>', (int) $this->_event->id);
        }
        //Target event starts or ends inside this events window or starts before and ends after
        $qb->add_constraint($fieldname . '.start', '<=', (int) $this->_event->end);
        $qb->add_constraint($fieldname . '.end', '>=', (int) $this->_event->start);
    }

    /**
     * Check for potential busy conflicts to allow more graceful handling of those conditions
     *
     * Also allows normal events to "rob" resources from tentative ones.
     * NOTE: return false for *no* (or resolved automatically) conflicts and true for unresolvable conflicts
     */
    function run($rob_tentative = false)
    {
        //If we're not busy it's not worth checking
        if (!$this->_event->busy)
        {
            debug_add('we allow overlapping, so there is no point in checking others');
            return true;
        }
        //If this event is tentative always disallow robbing resources from other tentative events
        if ($this->_event->tentative)
        {
            $rob_tentative = false;
        }
        //We need sudo to see busys in events we normally don't see and to rob resources from tentative events
        midcom::get('auth')->request_sudo('org.openpsa.calendar');

        //Storage for events that have been modified due the course of this method
        $modified_events = array();

        /*
         * Look for duplicate events only if we have participants or resources, otherwise we incorrectly get all events at
         * the same timeframe as duplicates since there are no participant constraints to narrow things down
         */
        $ret_ev = $this->_load_participants();
        $ret_ev2 = $this->_load_resources();

        // TODO: Shared tasks need a separate check (different member object)

        // Both QBs returned empty sets
        if (   empty($ret_ev)
            && empty($ret_ev2))
        {
            //No busy events found within the timeframe
            midcom::get('auth')->drop_sudo();
            debug_add('no overlaps found');
            return true;
        }

        foreach ($ret_ev as $member)
        {
            $this->_process_participant($member, $modified_events, $rob_tentative);
        }

        foreach ($ret_ev2 as $member)
        {
            $this->_process_resource($member, $modified_events, $rob_tentative);
        }

        if (   is_array($this->busy_members)
            || is_array($this->busy_resources))
        {
            //Unresolved conflicts (note return value is for conflicts not lack of them)
            midcom::get('auth')->drop_sudo();
            debug_print_r('unresolvable conflicts found', $this->busy_members);
            midcom_connection::set_error(MGD_ERR_ERROR);
            return false;
        }

        foreach ($modified_events as $event)
        {
            //These events have been robbed of (some of) their resources
            $creator = midcom_db_person::get_cached($event->metadata->creator);
            if (   (   count($event->participants) == 0
                    || (   count($event->participants) == 1
                        && array_key_exists($creator->id, $event->participants)
                       ))
                &&  count($event->resources) == 0)
            {
                /* If modified event has no-one or only creator as participant and no resources
                   then delete it (as it's unlikely the stub event is useful anymore) */
                debug_add("event {$event->title} (#{$event->id}) has been robbed of all of its resources, calling delete");
                //TODO: take notifications and repeats into account
                $event->delete();
            }
            else
            {
                //Otherwise just commit the changes
                //TODO: take notifications and repeats into account
                debug_add("event {$event->title} (#{$event->id}) has been robbed of some its resources, calling update");
                $event->update();
            }
        }

        midcom::get('auth')->drop_sudo();
        //No conflicts found or they could be automatically resolved
        $this->busy_members = false;
        $this->busy_resources = false;
        return true;
    }

    private function _load_participants()
    {
        $ret = array();
        if (!empty($this->_event->participants))
        {
            //We attack this "backwards" in the sense that in the end we need the events but this is faster way to filter them
            $qb = org_openpsa_calendar_event_member_dba::new_query_builder();
            $this->_add_event_constraints($qb, 'eid');
            //Shared eventmembers
            reset ($this->_event->participants);
            $qb->add_constraint('uid', 'IN', array_keys($this->_event->participants));
            $ret = $qb->execute();
        }
        return $ret;
    }

    private function _load_resources()
    {
        $ret = array();
        if (!empty($this->_event->resources))
        {
            $qb = org_openpsa_calendar_event_resource_dba::new_query_builder();
            $this->_add_event_constraints($qb, 'event');
            reset ($this->_event->resources);
            $qb->add_constraint('resource', 'IN', array_keys($this->_event->resources));
            $ret = $qb->execute();
        }
        return $ret;
    }

    private function _process_resource($member, &$modified_events, $rob_tentative)
    {
        //We might get multiple matches for same event/resource
        static $processed_events_resources = array();

        //Check if we have processed this resource/event combination already
        if (   array_key_exists($member->event, $processed_events_resources)
            && array_key_exists($member->resource, $processed_events_resources[$member->event]))
        {
            continue;
        }
        if (   !array_key_exists($member->event, $processed_events_resources)
            || !is_array($processed_events_resources[$member->event]))
        {
            $processed_events_resources[$member->event] = array();
        }
        $processed_events_resources[$member->event][$member->resource] = true;

        if (array_key_exists($member->event, $modified_events))
        {
            $event =& $modified_events[$member->event];
            $set_as_modified = false;
        }
        else
        {
            try
            {
                $event = new org_openpsa_calendar_event_dba($member->event);
                $set_as_modified = true;
            }
            catch (midcom_error $e)
            {
                debug_add("event_resource #{$member->id} links ot bogus event #{$member->event}, skipping and removing", MIDCOM_LOG_WARN);
                $member->delete();
                continue;
            }
        }
        debug_add("overlap found in event {$event->title} (#{$event->id})");

        if (   $event->tentative
            && $rob_tentative)
        {
            debug_add('event is tentative, robbing resources');
            //"rob" resources from tentative event
            $event = new org_openpsa_calendar_event_dba($event->id);

            //resources
            reset($this->_event->resources);
            foreach ($this->_event->resources as $id => $bool)
            {
                if (array_key_exists($id, $event->resources))
                {
                    unset($event->resources[$id]);
                }
            }
            if ($set_as_modified)
            {
                $modified_events[$event->id] = $event;
            }
        }
        else
        {
            debug_add('event is normal, flagging busy');
            //Non tentative event, flag busy resources
            if (!is_array($this->busy_resources))
            {
                //this is false under normal circumstances
                $this->busy_resources = array();
            }
            if (   !array_key_exists($member->guid, $this->busy_resources)
                || !is_array($this->busy_resources[$member->resource]))
            {
                //for mapping
                $this->busy_resources[$member->resource] = array();
            }
            //PONDER: The display end might have issues with event guid that they cannot see without sudo...
            $this->busy_resources[$member->resource][] = $event->guid;
        }
    }

    private function _process_participant($member, &$modified_events, $rob_tentative)
    {
        //We might get multiple matches for same event/person
        static $processed_events_participants = array();

        //Check if we have processed this participant/event combination already
        if (   array_key_exists($member->eid, $processed_events_participants)
            && array_key_exists($member->uid, $processed_events_participants[$member->eid]))
        {
            return;
        }
        if (   !array_key_exists($member->eid, $processed_events_participants)
            || !is_array($processed_events_participants[$member->eid]))
        {
            $processed_events_participants[$member->eid] = array();
        }
        $processed_events_participants[$member->eid][$member->uid] = true;

        try
        {
            $event = new org_openpsa_calendar_event_dba($member->eid);
        }
        catch (midcom_error $e)
        {
            debug_add("eventmember #{$member->id} links to bogus event #{$member->eid}, skipping and removing", MIDCOM_LOG_WARN);
            $member->delete();
            return;
        }
        debug_add("overlap found in event {$event->title} (#{$event->id})");

        if (   $event->tentative
            && $rob_tentative)
        {
            debug_add('event is tentative, robbing resources');
            //"rob" resources from tentative event
            $event = new org_openpsa_calendar_event_dba($event->id);

            //participants
            reset($this->_event->participants);
            foreach ($this->_event->participants as $id => $bool)
            {
                if (array_key_exists($id, $event->participants))
                {
                    unset($event->participants[$id]);
                }
            }
            $modified_events[$event->id] = $event;
        }
        else
        {
            debug_add('event is normal, flagging busy');
            //Non tentative event, flag busy resources
            if (!is_array($this->busy_members))
            {
                //this is false under normal circumstances
                $this->busy_members = array();
            }
            if (   !array_key_exists($member->guid, $this->busy_members)
                || !is_array($this->busy_members[$member->uid]))
            {
                //for mapping
                $this->busy_members[$member->uid] = array();
            }
            //PONDER: The display end might have issues with event guid that they cannot see without sudo...
            $this->busy_members[$member->uid][] = $event->guid;
        }
    }
}
?>