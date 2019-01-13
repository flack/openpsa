<?php
/**
 * @package org.openpsa.calendar
 * @author Nemein Oy, http://www.nemein.com/
 * @copyright Nemein Oy, http://www.nemein.com/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

use Doctrine\ORM\Query\Expr\Join;

/**
 * MidCOM wants this class present and QB etc use this, so keep logic here
 *
 * @property string $extra
 * @property integer $uid
 * @property integer $eid
 * @property integer $hoursReported
 * @package org.openpsa.calendar
 */
class org_openpsa_calendar_event_member_dba extends midcom_core_dbaobject
{
    public $__midcom_class_name__ = __CLASS__;
    public $__mgdschema_class_name__ = 'org_openpsa_eventmember';

    public $_use_rcs = false;

    public $notify_person = true;

    public function _on_created()
    {
        if ($this->notify_person) {
            $this->notify('add');
        }
    }

    public function _on_updating()
    {
        if ($this->notify_person) {
            $this->notify('update');
        }
        return true;
    }

    public function _on_deleted()
    {
        if ($this->notify_person) {
            $this->notify('remove');
        }
    }

    public function get_label()
    {
        $person = new midcom_db_person($this->uid);
        $event = new org_openpsa_calendar_event_dba($this->eid);
        return sprintf(midcom::get()->i18n->get_string('%s in %s', 'midcom'), $person->name, $event->title);
    }

    public function notify($type, org_openpsa_calendar_event_dba $event = null, $nl = "\n")
    {
        $recipient = $this->get_person_obj();

        if (!$recipient) {
            debug_add('recipient could not be gotten, aborting', MIDCOM_LOG_WARN);
            return false;
        }

        if (null === $event) {
            $event = new org_openpsa_calendar_event_dba($this->eid);
        }

        if (    $recipient->id == midcom_connection::get_user()
             && !$event->send_notify_me) {
            //Do not send notification to current user
            debug_add('event->send_notify_me is false and recipient is current user, aborting notify');
            return false;
        }

        $l10n = midcom::get()->i18n->get_l10n('org.openpsa.calendar');
        $message = [];
        $timeframe = $l10n->get_formatter()->timeframe($event->start, $event->end);
        $action = 'org.openpsa.calendar:event_' . $type;

        switch ($type) {
            //Event information was updated
            case 'update':
                //PONDER: This in theory should have the old event title
                $message['title'] = sprintf($l10n->get('event "%s" was updated'), $event->title);
                $message['abstract'] = sprintf($l10n->get('event "%s" (%s) was updated'), $event->title, $timeframe);
                $message['content'] = sprintf($l10n->get('event "%s" was modified, updated information below.') . "{$nl}{$nl}", $event->title);
                $message['content'] .= $event->details_text($nl);
                break;
                //Participant was added to the event
            case 'add':
                $message['title'] = sprintf($l10n->get('you have been added to event "%s"'), $event->title);
                $message['abstract'] = sprintf($l10n->get('you have been added to event "%s" (%s)'), $event->title, $timeframe);
                $message['content'] = sprintf($l10n->get('you have been added to event "%s" participants list, event information below.') . "{$nl}{$nl}", $event->title);
                $message['content'] .= $event->details_text($nl);
                break;
                //Participant was removed from event
            case 'remove':
                $message['title'] = sprintf($l10n->get('you have been removed from event "%s"'), $event->title);
                $message['abstract'] = sprintf($l10n->get('you have been removed from event "%s" (%s)'), $event->title, $timeframe);
                $message['content'] = sprintf($l10n->get('you have been removed from event "%s" (%s) participants list.'), $event->title, $timeframe);
                break;
                //Event was cancelled (=deleted)
            case 'cancel':
                $message['title'] = sprintf($l10n->get('event "%s" was cancelled'), $event->title);
                $message['abstract'] = sprintf($l10n->get('event "%s" (%s) was cancelled'), $event->title, $timeframe);
                $message['content'] = sprintf($l10n->get('event "%s" (%s) was cancelled.'), $event->title, $timeframe);
                break;
            default:
                debug_add("action '{$type}' is invalid, aborting notification", MIDCOM_LOG_ERROR);
                return false;
        }

        if (   $type == 'cancel'
            || $type == 'remove') {
            // TODO: Create iCal export with correct delete commands
        } else {
            $generator = midcom::get()->serviceloader->load(midcom_core_service_urlgenerator::class);
            $encoder = new org_openpsa_calendar_vcal;
            $encoder->add_event($event);
            $message['attachments'] = [
                [
                    'name' => $generator->from_string(sprintf('%s on %s', $event->title, date('Ymd_Hi', $event->start))) . '.ics',
                    'mimetype' => 'text/calendar',
                    'content' => (string) $encoder,
                ],
            ];
        }

        return org_openpsa_notifications::notify($action, $recipient->guid, $message);
    }

    /**
     * Returns the person this member points to if that person can be used for notifications
     */
    private function get_person_obj()
    {
        try {
            $person = org_openpsa_contacts_person_dba::get_cached($this->uid);

            //We need to have an email which to send to so if no email no point
            if (empty($person->email)) {
                debug_add('person #' . $person->id . 'has no email address, aborting');
                return false;
            }
        } catch (midcom_error $e) {
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
        $slots = [];

        // Get current events for person
        $qb = org_openpsa_calendar_event_dba::new_query_builder();
        $qb->get_doctrine()
            ->leftJoin('org_openpsa_eventmember', 'm', Join::WITH, 'm.eid = c.id')
            ->where('m.uid = :person')
            ->setParameter('person', $person->id);

        // All events that somehow overlap the given time.
        $qb->add_constraint('start', '<=', $end);
        $qb->add_constraint('end', '>=', $start);
        $qb->add_order('start', 'ASC');
        $qb->add_order('end', 'ASC');

        $events_by_date = [];
        foreach ($qb->execute() as $event) {
            $ymd = date('Ymd', $event->start);
            if (!array_key_exists($ymd, $events_by_date)) {
                $events_by_date[$ymd] = [];
            }
            $events_by_date[$ymd][] = $event;
        }

        // Make sure each date between start and end has at least a dummy event
        $stamp = strtotime('today', $start) + 1;
        while ($stamp <= $end) {
            $ymd = date('Ymd', $stamp);
            debug_add("making sure date {$ymd} has at least one event");
            $stamp = strtotime('tomorrow', $stamp) + 1;
            if (array_key_exists($ymd, $events_by_date)) {
                continue;
            }
            debug_add('none found, adding a dummy one');
            $dummy = new org_openpsa_calendar_event_dba();
            $dummy->start = $stamp;
            $dummy->end = $stamp + 1;
            $events_by_date[$ymd] = [$dummy];
        }

        foreach ($events_by_date as $ymd => $events) {
            preg_match('/([0-9]{4})([0-9]{2})([0-9]{2})/', $ymd, $ymd_matches);
            // TODO: get from person's data based on event's weekday
            // PONDER: What to do with persons that do not have this data defined ??
            $workday_starts = 8;
            $workday_ends = 16;

            $workday_starts_ts = mktime($workday_starts, 0, 0, (int)$ymd_matches[2], (int)$ymd_matches[3], (int)$ymd_matches[1]);
            $workday_ends_ts = mktime($workday_ends, 0, 0, (int)$ymd_matches[2], (int)$ymd_matches[3], (int)$ymd_matches[1]);
            $last_end_time = false;
            $last_event = false;
            foreach ($events as $event) {
                if (   $event->end <= $workday_starts_ts
                    || $event->start >= $workday_ends_ts) {
                    // We need not to consider this event, it is outside the defined workday
                    continue;
                }

                debug_add("checking event #{$event->id} ({$event->title})");
                if ($last_end_time === false) {
                    if ($event->start > $workday_starts_ts) {
                        // First event of the day starts after we have started working, use work start time as last end time.
                        $last_end_time = $workday_starts_ts;
                    } else {
                        // Make the first event of the day the last end time and skip rest of the checks
                        $last_end_time = $event->end;
                        $last_event = $event;
                        continue;
                    }
                }
                $diff = $event->start - $last_end_time;
                if ($diff >= $amount) {
                    // slot found
                    $slots[] = self::_create_slot($last_end_time, $event->start, $last_event, $event);
                }
                $last_end_time = $event->end;
                $last_event = $event;
            }
            // End of day slot
            if ($last_end_time === false) {
                $last_end_time = $workday_starts_ts;
            }
            if (   $last_end_time < $workday_ends_ts
                && (($workday_ends_ts - $last_end_time) >= $amount)) {
                $slots[] = self::_create_slot($last_end_time, $workday_ends_ts, $last_event);
            }
        }

        return $slots;
    }

    private static function _create_slot($start, $end, $previous, $next = false)
    {
        return [
            'start' => $start,
            'end' => $end,
            'previous' => $previous,
            'next' => $next,
        ];
    }
}
