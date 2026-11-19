<?php
/**
 * @package org.openpsa.calendar
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

use Sabre\DAV\Exception;
use Sabre\VObject\Component\VCalendar;
use Sabre\VObject\Component\VEvent;

/**
 * Saves a client's VCALENDAR (one event, with the overrides of a recurring event) to an event
 *
 * Used by CalDAV and the iCal feed. Errors are thrown as sabre/dav exceptions.
 *
 * - The master VEVENT's attendees become the participants (matched by email address)
 * - No notifications are sent for events organized by someone other than the owner, since the
 *   organizer informs the attendees themselves
 * - A copy of the VCALENDAR is kept in vCalSerialized, so that what OpenPSA doesn't use survives
 * - Recurring events: RRULE, RDATE and EXDATE go into $recurrence, override VEVENTs become
 *   occurrences edited on their own, with the participants from their attendees. Series are not
 *   refused because of conflicts, each occurrence is checked on its own instead
 *
 * @package org.openpsa.calendar
 */
class org_openpsa_calendar_import
{
    public function __construct(private bool $send_notify = true)
    {
    }

    public function save(org_openpsa_calendar_event_dba $event, int $owner_id, VCalendar $vcalendar)
    {
        $vevent = org_openpsa_calendar_vcal::get_master($vcalendar) ?? throw new Exception\BadRequest('No VEVENT found');
        $overrides = org_openpsa_calendar_vcal::get_overrides($vcalendar, $vevent);
        $was_master = $event->is_master();
        $notify = $this->send_notify && !org_openpsa_calendar_vcal::has_external_organizer($vevent, $owner_id);

        org_openpsa_calendar_vcal::vevent_to_event($vevent, $event);
        $uid = (string) $vevent->UID;
        if (!$event->id && !str_ends_with($uid, '-midgardGuid')) {
            $event->externalGuid = $uid;
        }
        // Keep the client's copy, so that what OpenPSA doesn't understand (alarms etc.) survives
        $event->vCalSerialized = $vcalendar->serialize();
        $this->set_recurrence($event, $vevent, $overrides);

        $participants = $this->get_participants($vevent, $event, $owner_id);
        $event->participants = $participants;
        if (!$event->recurrence) {
            $this->check_conflicts($event);
        }
        $event->ignorebusy_em = true;
        $event->send_notify = $notify;
        // Occurrences are handled below, once the participants are saved
        $event->sync_recurrence = false;

        if (!($event->id ? $event->update() : $event->create())) {
            throw self::get_save_exception();
        }
        if ($this->sync_participants($event, $participants, $notify)) {
            // Participants get their privileges when the event is updated
            $event->send_notify = false;
            $event->search_relatedtos = false;
            $event->update();
        }

        $recurrence = new org_openpsa_calendar_recurrence;
        if ($event->is_master()) {
            $this->save_occurrences($recurrence, $event, $overrides, $owner_id);
        } elseif ($was_master) {
            // No longer recurring
            $recurrence->delete_occurrences($event, false);
        }
    }

    private function set_recurrence(org_openpsa_calendar_event_dba $event, VEvent $vevent, array $overrides)
    {
        $tz = $this->get_timezone($vevent);
        $event->recurrence = org_openpsa_calendar_recurrence::rule_from_vevent($vevent, $tz);
        $event->recurrenceDetached = false;
        if ($event->recurrence === '') {
            return;
        }
        $event->timezone = $tz->getName();
        $anchor = org_openpsa_calendar_recurrence::round_start($event->start);
        if ($first = $overrides[$anchor] ?? null) {
            // The first occurrence was edited on its own: The master row holds it,
            // the series' values stay available in the stored copy
            org_openpsa_calendar_vcal::vevent_to_event($first, $event);
            $event->recurrenceId = $anchor;
            $event->recurrenceDetached = true;
        }
    }

    /**
     * The timezone the series is expanded in: DTSTART's, or the server's for floating times and dates
     */
    private function get_timezone(VEvent $vevent) : DateTimeZone
    {
        $start = $vevent->DTSTART;
        if ($start && $start->hasTime() && !$start->isFloating()) {
            try {
                return $start->getDateTime()->getTimezone();
            } catch (Throwable) {
            }
        }
        return new DateTimeZone(date_default_timezone_get());
    }

    /**
     * Generates the occurrences, then applies the overrides
     *
     * Occurrences whose override is gone return to the series
     */
    private function save_occurrences(org_openpsa_calendar_recurrence $recurrence, org_openpsa_calendar_event_dba $master, array $overrides, int $owner_id)
    {
        unset($overrides[$master->recurrenceId]);
        $recurrence->reset_detached($master, array_keys($overrides));
        $recurrence->sync($master);
        foreach ($overrides as $recurrence_id => $override) {
            $values = new org_openpsa_calendar_event_dba;
            org_openpsa_calendar_vcal::vevent_to_event($override, $values);
            $participants = $this->get_participants($override, $master, $owner_id, true);
            $recurrence->apply_override($master, $recurrence_id, $values, array_keys($participants));
        }
    }

    /**
     * Returns the participants as described by the VEVENT's attendees
     *
     * The owner always stays, as do participants without email address (they can't be attendees)
     */
    private function get_participants(VEvent $vevent, org_openpsa_calendar_event_dba $event, int $owner_id, bool $skip_declined = false) : array
    {
        $participants = array_fill_keys([$owner_id, ...org_openpsa_calendar_vcal::get_attendee_ids($vevent, $skip_declined)], true);
        foreach (array_keys($event->participants) as $id) {
            try {
                if (empty(midcom_db_person::get_cached($id)->email)) {
                    $participants[$id] = true;
                }
            } catch (midcom_error) {
            }
        }
        return $participants;
    }

    /**
     * Creates and deletes eventmembers so that they match the given participants
     *
     * @return bool Whether participants were added
     */
    private function sync_participants(org_openpsa_calendar_event_dba $event, array $participants, bool $notify) : bool
    {
        $qb = org_openpsa_calendar_event_member_dba::new_query_builder();
        $qb->add_constraint('eid', '=', $event->id);
        foreach ($qb->execute() as $member) {
            if (!isset($participants[$member->uid])) {
                $member->notify_person = $notify;
                $member->sync_series = false;
                $member->delete();
            }
            unset($participants[$member->uid]);
        }
        foreach (array_keys($participants) as $uid) {
            $member = new org_openpsa_calendar_event_member_dba;
            $member->eid = $event->id;
            $member->uid = $uid;
            $member->notify_person = $notify;
            $member->sync_series = false;
            if (!$member->create()) {
                throw self::get_save_exception();
            }
        }
        return !empty($participants);
    }

    private function check_conflicts(org_openpsa_calendar_event_dba $event)
    {
        $conflictmanager = new org_openpsa_calendar_conflictmanager($event);
        if ($conflictmanager->run($event->rob_tentative)) {
            return;
        }
        $names = [];
        foreach (array_keys($conflictmanager->busy_members) as $uid) {
            $names[] = midcom_db_person::get_cached($uid)->name;
        }
        foreach (array_keys($conflictmanager->busy_resources) as $id) {
            $names[] = org_openpsa_calendar_resource_dba::get_cached($id)->title;
        }
        throw new Exception\Conflict('The event conflicts with other events of: ' . implode(', ', $names));
    }

    public static function get_save_exception() : Exception
    {
        $message = midcom_connection::get_error_string();
        return match (midcom_connection::get_error()) {
            MGD_ERR_ACCESS_DENIED => new Exception\Forbidden($message),
            MGD_ERR_RANGE => new Exception\BadRequest($message),
            default => new Exception\Conflict($message)
        };
    }
}
