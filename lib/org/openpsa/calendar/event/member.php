<?php
/**
 * @package org.openpsa.calendar
 * @author Nemein Oy, http://www.nemein.com/
 * @copyright Nemein Oy, http://www.nemein.com/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * MidCOM wants this class present and QB etc use this, so keep logic here
 *
 * @package org.openpsa.calendar
 */
class org_openpsa_calendar_event_member_dba extends midcom_core_dbaobject
{
    const OBTYPE_EVENTPARTICIPANT = 5001;

    public $__midcom_class_name__ = __CLASS__;
    public $__mgdschema_class_name__ = 'org_openpsa_eventmember';

    public $notify_person = true;

    public function __construct($identifier = null)
    {
        parent::__construct($identifier);
        if (!$this->orgOpenpsaObtype)
        {
            $this->orgOpenpsaObtype = self::OBTYPE_EVENTPARTICIPANT;
        }
    }

    function get_parent_guid_uncached()
    {
        if ($this->eid)
        {
            $event = new org_openpsa_calendar_event_dba($this->eid);
            return $event->guid;
        }
        else
        {
            $root_event = org_openpsa_calendar_interface::find_root_event();
            return $root_event->guid;
        }
    }

    public function _on_created()
    {
        if ($this->notify_person)
        {
            $this->notify('add');
        }
    }

    public function _on_updating()
    {
        if ($this->notify_person)
        {
            $this->notify('update');
        }
        return true;
    }

    public function _on_deleted()
    {
        if ($this->notify_person)
        {
            $this->notify('remove');
        }
    }

    function notify($type, org_openpsa_calendar_event_dba $event = null, $nl = "\n")
    {
        $l10n = midcom::get('i18n')->get_l10n('org.openpsa.calendar');
        $recipient = $this->get_person_obj();

        if (!$recipient)
        {
            debug_add('recipient could not be gotten, aborting', MIDCOM_LOG_WARN);
            return false;
        }

        if (null === $event)
        {
            $event = new org_openpsa_calendar_event_dba($this->eid);
        }

        if (    $recipient->id == midcom_connection::get_user()
             && !$event->send_notify_me)
        {
            //Do not send notification to current user
            debug_add('event->send_notify_me is false and recipient is current user, aborting notify');
            return false;
        }

        $message = array();
        $action = 'org.openpsa.calendar:noevent';

        switch ($type)
        {
            //Event information was updated
            case 'update':
                //PONDER: This in theory should have the old event title
                $action = 'org.openpsa.calendar:event_update';
                $message['title'] = sprintf($l10n->get('event "%s" was updated'), $event->title);
                $message['abstract'] = sprintf($l10n->get('event "%s" (%s) was updated'), $event->title, $event->format_timeframe());
                $message['content'] = sprintf($l10n->get('event "%s" was modified, updated information below.') . "{$nl}{$nl}", $event->title);
                $message['content'] .= $event->details_text(false, $this, $nl);
                break;
                //Participant was added to the event
            case 'add':
                $action = 'org.openpsa.calendar:event_add';
                $message['title'] = sprintf($l10n->get('you have been added to event "%s"'), $event->title);
                $message['abstract'] = sprintf($l10n->get('you have been added to event "%s" (%s)'), $event->title, $event->format_timeframe());
                $message['content'] = sprintf($l10n->get('you have been added to event "%s" participants list, event information below.') . "{$nl}{$nl}", $event->title);
                $message['content'] .= $event->details_text(false, $this, $nl);
                break;
                //Participant was removed from event
            case 'remove':
                $action = 'org.openpsa.calendar:event_remove';
                $message['title'] = sprintf($l10n->get('you have been removed from event "%s"'), $event->title);
                $message['abstract'] = sprintf($l10n->get('you have been removed from event "%s" (%s)'), $event->title, $event->format_timeframe());
                $message['content'] = sprintf($l10n->get('you have been removed from event "%s" (%s) participants list.'), $event->title, $event->format_timeframe());
                break;
                //Event was cancelled (=deleted)
            case 'cancel':
                $action = 'org.openpsa.calendar:event_cancel';
                $message['title'] = sprintf($l10n->get('event "%s" was cancelled'), $event->title);
                $message['abstract'] = sprintf($l10n->get('event "%s" (%s) was cancelled'), $event->title, $event->format_timeframe());
                $message['content'] = sprintf($l10n->get('event "%s" (%s) was cancelled.'), $event->title, $event->format_timeframe());
                break;
            default:
                debug_add("action '{$type}' is invalid, aborting notification", MIDCOM_LOG_ERROR);
                return false;
        }

        if (   $type == 'cancel'
            || $type == 'remove')
        {
            // TODO: Create iCal export with correct delete commands
        }
        else
        {
            $generator = midcom::get('serviceloader')->load('midcom_core_service_urlgenerator');
            $encoder = new org_openpsa_calendar_vcal();
            $vcal_data = $encoder->get_headers();
            $vcal_data .= $encoder->export_event($event);
            $vcal_data .= $encoder->get_footers();
            $message['attachments'] = array
            (
                array
                (
                    'name' => $generator->from_string(sprintf('%s on %s', $event->title, date('Ymd_Hi', $event->start))) . '.ics',
                    'mimetype' => 'text/calendar',
                    'content' => $vcal_data,
                ),
            );
        }

        return org_openpsa_notifications::notify($action, $recipient->guid, $message);
    }

    /**
     * Returns the person this member points to if that person can be used for notifications
     */
    function get_person_obj()
    {
        try
        {
            $person = org_openpsa_contacts_person_dba::get_cached($this->uid);

            //We need to have an email which to send to so if no email no point
            if (empty($person->email))
            {
                debug_add('person #' . $person->id . 'has no email address, aborting');
                return false;
            }
        }
        catch (midcom_error $e)
        {
            return false;
        }

        return $person;
    }

    /**
     * Find amount (seconds) of free
     * time for person between start and end
     */
    public static function find_free_times($amount, org_openpsa_contacts_person_dba $person, $start, $end)
    {
        $slots = array();

        // Get current events for person
        $mc = self::new_collector('uid', $person->id);
        // All events that somehow overlap the given time.
        $mc->begin_group('OR');
            $mc->begin_group('AND');
                $mc->add_constraint('eid.start', '>=', $start);
                $mc->add_constraint('eid.start', '<=', $end);
            $mc->end_group();
            $mc->begin_group('AND');
                $mc->add_constraint('eid.end', '<=', $end);
                $mc->add_constraint('eid.end', '>=', $start);
            $mc->end_group();
            $mc->begin_group('AND');
                $mc->add_constraint('eid.start', '<=', $start);
                $mc->add_constraint('eid.end', '>=', $end);
            $mc->end_group();
        $mc->end_group();
        $mc->add_order('eid.start', 'ASC');
        $mc->add_order('eid.end', 'ASC');
        $eventmembers = $mc->get_values('eid');
        if (!is_array($eventmembers))
        {
            // QB error
            return $slots;
        }
        $events_by_date = array();
        foreach ($eventmembers as $eid)
        {
            try
            {
                $event = org_openpsa_calendar_event_dba::get_cached($eid);
            }
            catch (midcom_error $e)
            {
                continue;
            }
            $ymd = date('Ymd', $event->start);
            if (array_key_exists($ymd, $events_by_date))
            {
                $events_by_date[$ymd] = array();
            }
            $events_by_date[$ymd][] = $event;
        }
        // Make sure each date between start and end has at least a dummy event
        $stamp = mktime(0, 0, 1, date('m', $start), date('d', $start), date('Y', $start));
        while ($stamp <= $end)
        {
            $ymd = date('Ymd', $stamp);
            debug_add("making sure date {$ymd} has at least one event");
            $stamp = mktime(0, 0, 1, date('m', $stamp), date('d', $stamp)+1, date('Y', $stamp));
            if (array_key_exists($ymd, $events_by_date))
            {
                continue;
            }
            debug_add('none found, adding a dummy one');
            $dummy = new org_openpsa_calendar_event_dba();
            $dummy->start = $stamp;
            $dummy->end = $stamp + 1;
            $events_by_date[$ymd] = array($dummy);
        }
        foreach ($events_by_date as $ymd => $events)
        {
            preg_match('/([0-9]{4})([0-9]{2})([0-9]{2})/', $ymd, $ymd_matches);
            // TODO: get from person's data based on event's weekday
            // PONDER: What to do with persons that do not have this data defined ??
            $workday_starts = 8;
            $workday_ends = 16;

            $workday_starts_ts = mktime($workday_starts, 0, 0, (int)$ymd_matches[2], (int)$ymd_matches[3], (int)$ymd_matches[1]);
            $workday_ends_ts = mktime($workday_ends, 0, 0, (int)$ymd_matches[2], (int)$ymd_matches[3], (int)$ymd_matches[1]);
            $last_end_time = false;
            $last_event = false;
            foreach ($events as $event_key => $event)
            {
                if (   $event->end <= $workday_starts_ts
                    || $event->start >= $workday_ends_ts)
                {
                    // We need not to consider this event, it is outside the defined workday
                    unset($events[$event_key]);
                    continue;
                }

                debug_add("checking event #{$event->id} ({$event->title})");
                if ($last_end_time === false)
                {
                    if ($event->start > $workday_starts_ts)
                    {
                        // First event of the day starts after we have started working, use work start time as last end time.
                        $last_end_time = $workday_starts_ts;
                    }
                    else
                    {
                        // Make the first event of the day the last end time and skip rest of the checks
                        $last_end_time = $event->end;
                        // PHP5-TODO: Must be copy by value
                        $last_event = $event;
                        continue;
                    }
                }
                $diff = $event->start - $last_end_time;
                if ($diff >= $amount)
                {
                    // slot found
                    $slots[] = $this->_create_slot($last_end_time, $event->start, $last_event, $event);
                }
                $last_end_time = $event->end;
                $last_event = $event;
            }
            // End of day slot
            if ($last_end_time === false)
            {
                $last_end_time = $workday_starts_ts;
            }
            if (   $last_end_time < $workday_ends_ts
                && (($workday_ends_ts- $last_end_time) >= $amount))
            {
                $slots[] = $this->_create_slot($last_end_time, $workday_ends_ts, $last_event);
            }
        }

        return $slots;
    }

    private function _create_slot($start, $end, $previous, $next = false)
    {
        return array
        (
            'start' => $start,
            'end' => $end,
            'previous' => $previous,
            'next' => $next,
        );
    }
}
?>