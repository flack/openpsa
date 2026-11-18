<?php
/**
 * @package org.openpsa.calendar
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

use Sabre\VObject\Component\VEvent;
use Sabre\VObject\Component\VCalendar;
use Sabre\VObject\ParseException;
use Sabre\VObject\Property;
use Sabre\VObject\Reader;

/**
 * vCalendar helper class
 *
 * If an event has a stored copy of the client's VCALENDAR (in vCalSerialized), the export
 * starts from that copy and only overwrites the properties OpenPSA owns, so that everything
 * else (alarms, X-properties etc.) survives a round trip.
 *
 * For recurring events, OpenPSA also owns RRULE, RDATE and EXDATE (from $recurrence) and the
 * override VEVENTs (from the occurrences edited on their own).
 *
 * @package org.openpsa.calendar
 */
class org_openpsa_calendar_vcal
{
    private VCalendar $_calendar;

    private bool $placeholder_emails;

    /**
     * TZIDs of the VTIMEZONEs already added from stored copies
     */
    private array $timezones = [];

    /**
     * @param string|null $method vCalendar method (defaults to "publish"), null to omit (e.g. for CalDAV objects)
     * @param bool $placeholder_emails Whether to generate fake addresses for participants without email
     */
    public function __construct(?string $method = "PUBLISH", bool $placeholder_emails = true)
    {
        $this->placeholder_emails = $placeholder_emails;
        $this->_calendar = new VCalendar;
        $this->_calendar->VERSION = '2.0';
        $this->_calendar->PRODID = "-//OpenPSA//Calendar " . org_openpsa_core_version::get_version_number() . "//" . strtoupper(midcom::get()->i18n->get_current_language());
        if ($method !== null) {
            $this->_calendar->METHOD = strtoupper($method);
        }
    }

    /**
     * Export event in vCalendar format
     */
    public function add_event(org_openpsa_calendar_event_dba $event)
    {
        $this->event_to_vevent($event);
    }

    /**
     * Converts the event to a VEVENT and adds it to the calendar
     *
     * @return VEvent The main VEVENT (stored recurrence exceptions are added as well)
     */
    public function event_to_vevent(org_openpsa_calendar_event_dba $event) : VEvent
    {
        $is_master = $event->is_master();
        $stored = self::read($event->vCalSerialized);
        $stored_overrides = [];
        if ($stored && $vevent = self::get_master($stored)) {
            foreach ($stored->select('VEVENT') as $component) {
                if ($component !== $vevent && isset($component->{'RECURRENCE-ID'})) {
                    $stored_overrides[self::get_recurrence_id($component)] = $component;
                    $stored->remove($component);
                }
            }
            $this->add_stored_components($stored);
        } else {
            $stored = null;
            $vevent = $this->_calendar->createComponent('VEVENT');
            $vevent->UID = $this->get_uid($event);
            $vevent->CREATED = $this->to_utc($event->metadata->created);
            // tentative vs confirmed
            $vevent->STATUS = 'CONFIRMED';
            // we don't categorize events, at least yet
            $vevent->CATEGORIES = 'MEETING';
            // we don't handle priorities
            $vevent->PRIORITY = 1;
            $this->_calendar->add($vevent);
        }

        $values = $this->get_values($event);
        if ($is_master && $event->recurrenceDetached) {
            // The master row holds the first occurrence, which is exported as override
            $series = (new org_openpsa_calendar_recurrence)->get_series($event);
            $values = [
                'start' => $series['start'],
                'end' => $series['start'] + $series['duration'],
                'title' => $series['title'],
                'description' => $series['description'],
                'location' => $series['location']
            ];
        }
        $this->set_properties($vevent, $event, $values, $stored !== null);
        $this->set_attendees($vevent, $event->participants);

        org_openpsa_calendar_recurrence::rule_to_vevent($event->recurrence, $vevent);
        if ($is_master) {
            $this->add_overrides($vevent, $event, $stored_overrides);
        }
        return $vevent;
    }

    private function get_values(org_openpsa_calendar_event_dba $event) : array
    {
        return [
            'start' => $event->start,
            'end' => $event->end,
            'title' => $event->title,
            'description' => $event->description,
            'location' => $event->location
        ];
    }

    /**
     * Sets the properties OpenPSA owns (except attendees)
     */
    private function set_properties(VEvent $vevent, org_openpsa_calendar_event_dba $event, array $values, bool $stored)
    {
        /**
         * The real meaning of the DTSTAMP is fuzzy at best
         * http://www.kanzaki.com/docs/ical/dtstamp.html is less than helpful
         * http://lists.osafoundation.org/pipermail/ietf-calsify/2007-July/001750.html
         * seems to suggest that using the revision would be best
         */
        $revised = $this->to_utc($event->metadata->revised);
        $vevent->DTSTAMP = $revised;
        $vevent->{'LAST-MODIFIED'} = $revised;
        $this->set_times($vevent, $values['start'], $values['end'], $stored);

        $vevent->{'CLASS'} = ($event->orgOpenpsaAccesstype === org_openpsa_core_acl::ACCESS_PRIVATE) ? 'PRIVATE' : 'PUBLIC';
        // "busy" or "transparency" as vCalendar calls it
        $vevent->TRANSP = ($event->busy) ? 'OPAQUE' : 'TRANSPARENT';
        $this->set_text($vevent, 'SUMMARY', $values['title']);
        $this->set_text($vevent, 'DESCRIPTION', $values['description']);
        $this->set_text($vevent, 'LOCATION', $values['location']);
    }

    /**
     * Adds an override VEVENT for each occurrence edited on its own
     *
     * Participants of the series who don't take part in an occurrence are listed as declined
     *
     * @param VEvent[] $stored Overrides from the client's copy, by original start
     */
    private function add_overrides(VEvent $vevent, org_openpsa_calendar_event_dba $master, array $stored)
    {
        $qb = org_openpsa_calendar_event_dba::new_query_builder();
        $qb->add_constraint('up', '=', $master->id);
        $qb->add_constraint('recurrenceDetached', '=', true);
        $qb->add_order('start');
        $occurrences = $qb->execute();
        if ($master->recurrenceDetached) {
            array_unshift($occurrences, $master);
        }

        foreach ($occurrences as $occurrence) {
            $override = $stored[$occurrence->recurrenceId] ?? null;
            $is_stored = $override !== null;
            if (!$is_stored) {
                $override = $this->_calendar->createComponent('VEVENT');
                // So that the times get the same format as the master's
                $override->DTSTART = clone $vevent->DTSTART;
            }
            $override->UID = (string) $vevent->UID;
            $this->set_recurrence_id($override, $vevent, $occurrence->recurrenceId);
            $this->set_properties($override, $occurrence, $this->get_values($occurrence), $is_stored);
            $this->set_attendees($override, $occurrence->participants);
            $declined = array_diff_key(array_filter($master->participants), array_filter($occurrence->participants));
            foreach (array_keys($declined) as $uid) {
                $this->add_attendee($override, $uid, 'DECLINED');
            }
            $this->_calendar->add($override);
        }
    }

    /**
     * RECURRENCE-ID gets the same format as the master's DTSTART
     */
    private function set_recurrence_id(VEvent $override, VEvent $master, int $recurrence_id)
    {
        unset($override->{'RECURRENCE-ID'});
        $time = new DateTime('@' . ($recurrence_id - $recurrence_id % 60));
        if (!$master->DTSTART->hasTime()) {
            $time->setTimezone(new DateTimeZone(date_default_timezone_get()));
            $override->add('RECURRENCE-ID', $time, ['VALUE' => 'DATE']);
            return;
        }
        $tz = new DateTimeZone('UTC');
        if (isset($master->DTSTART['TZID'])) {
            try {
                $tz = $master->DTSTART->getDateTime()->getTimezone();
            } catch (Exception) {
            }
        }
        $override->add('RECURRENCE-ID', $time->setTimezone($tz));
    }

    /**
     * The original start of the occurrence an override VEVENT is for
     */
    public static function get_recurrence_id(VEvent $override) : int
    {
        $time = $override->{'RECURRENCE-ID'}->getDateTime(new DateTimeZone(date_default_timezone_get()));
        return org_openpsa_calendar_recurrence::round_start($time->getTimestamp());
    }

    private function get_uid(org_openpsa_calendar_event_dba $event) : string
    {
        return $event->externalGuid ?: "{$event->guid}-midgardGuid";
    }

    public static function read(?string $input) : ?VCalendar
    {
        if (empty($input)) {
            return null;
        }
        try {
            $vcalendar = Reader::read($input);
        } catch (ParseException $e) {
            debug_add('Could not parse vCalendar: ' . $e->getMessage(), MIDCOM_LOG_WARN);
            return null;
        }
        return $vcalendar instanceof VCalendar ? $vcalendar : null;
    }

    /**
     * Returns the main VEVENT, i.e. the one that is not a recurrence exception
     */
    public static function get_master(VCalendar $vcalendar) : ?VEvent
    {
        $vevents = $vcalendar->select('VEVENT');
        foreach ($vevents as $vevent) {
            if (!isset($vevent->{'RECURRENCE-ID'})) {
                return $vevent;
            }
        }
        return $vevents[0] ?? null;
    }

    /**
     * Copies the values of a VEVENT to the event (does not save it)
     *
     * For recurring events, start and end are those of the first occurrence
     */
    public static function vevent_to_event(VEvent $vevent, org_openpsa_calendar_event_dba $event)
    {
        $event->title = (string) $vevent->SUMMARY;
        $event->description = (string) $vevent->DESCRIPTION;
        $event->location = (string) $vevent->LOCATION;
        // OPAQUE is the default if TRANSP is missing
        $event->busy = (string) $vevent->TRANSP !== 'TRANSPARENT';
        if (isset($vevent->{'CLASS'})) {
            $event->orgOpenpsaAccesstype = (string) $vevent->{'CLASS'} === 'PRIVATE' ? org_openpsa_core_acl::ACCESS_PRIVATE : org_openpsa_core_acl::ACCESS_PUBLIC;
        }
        [$event->start, $event->end] = self::get_times($vevent);
    }

    /**
     * Returns the IDs of the persons whose email addresses are listed as attendees
     *
     * @param bool $skip_declined Leave out attendees with PARTSTAT=DECLINED
     *
     * @return array<string, int> email => person ID
     */
    public static function get_attendee_ids(VEvent $vevent, bool $skip_declined = false) : array
    {
        $attendees = $vevent->select('ATTENDEE');
        if ($skip_declined) {
            $attendees = array_filter($attendees, fn($attendee) => strtoupper((string) $attendee['PARTSTAT']) !== 'DECLINED');
        }
        $emails = array_filter(array_map(self::get_email(...), $attendees));
        return self::find_persons($emails);
    }

    /**
     * Returns the attendees from the client's copy that are not in our DB
     *
     * For occurrences, these are the ones from the override VEVENT if there is one, otherwise
     * the series' attendees. Declined attendees are left out
     *
     * @return array<string, string> email => name (or email, if there is no name)
     */
    public static function get_external_attendees(org_openpsa_calendar_event_dba $event) : array
    {
        $master = $event;
        if ($event->is_recurring()) {
            try {
                $master = $event->get_master();
            } catch (midcom_error $e) {
                $e->log();
                return [];
            }
        }
        if (   !($stored = self::read($master->vCalSerialized))
            || !($vevent = self::get_master($stored))) {
            return [];
        }
        if ($event->recurrenceId) {
            foreach ($stored->select('VEVENT') as $override) {
                if (   isset($override->{'RECURRENCE-ID'})
                    && self::get_recurrence_id($override) === $event->recurrenceId) {
                    $vevent = $override;
                    break;
                }
            }
        }

        $attendees = [];
        foreach ($vevent->select('ATTENDEE') as $attendee) {
            if (   ($email = self::get_email($attendee))
                && strtoupper((string) $attendee['PARTSTAT']) !== 'DECLINED') {
                $attendees[$email] = trim((string) $attendee['CN']) ?: $email;
            }
        }
        return array_diff_key($attendees, self::find_persons(array_keys($attendees)));
    }

    /**
     * @return int[] Start and end timestamp
     */
    public static function get_times(VEvent $vevent) : array
    {
        // floating times and dates are interpreted in the server's timezone
        $tz = new DateTimeZone(date_default_timezone_get());
        $start = $vevent->DTSTART->getDateTime($tz);
        $all_day = !$vevent->DTSTART->hasTime();

        if (isset($vevent->DTEND)) {
            $end = $vevent->DTEND->getDateTime($tz);
        } elseif (isset($vevent->DURATION)) {
            $end = $start->add($vevent->DURATION->getDateInterval());
        } elseif ($all_day) {
            $end = $start->modify('+1 day');
        } else {
            $end = $start;
        }
        if ($all_day) {
            // DTEND is exclusive, so a one-day event ends at 23:59 the same day
            $end = max($start, $end->modify('-1 minute'));
        }
        return [$start->getTimestamp(), $end->getTimestamp()];
    }

    /**
     * Applies the same rounding as the event's save routine (start at second 1, end at second 0)
     */
    private function normalize_times(int $start, int $end) : array
    {
        return [$start - ($start % 60) + 1, $end - ($end % 60)];
    }

    private function set_times(VEvent $vevent, int $start_time, int $end_time, bool $stored)
    {
        if (   $stored
            && isset($vevent->DTSTART)
            && $this->normalize_times(...self::get_times($vevent)) === $this->normalize_times($start_time, $end_time)) {
            // Unchanged since the client sent it, so we keep the original representation
            return;
        }
        $start = new DateTime('@' . ($start_time - $start_time % 60));
        $end = new DateTime('@' . $end_time);
        unset($vevent->DURATION);

        if (isset($vevent->DTSTART) && !$vevent->DTSTART->hasTime()) {
            // All-day events stay all-day events
            $tz = new DateTimeZone(date_default_timezone_get());
            $start->setTimezone($tz);
            $end->setTimezone($tz)->modify('+1 day');
            unset($vevent->DTSTART, $vevent->DTEND);
            $vevent->add('DTSTART', $start, ['VALUE' => 'DATE']);
            $vevent->add('DTEND', $end, ['VALUE' => 'DATE']);
            return;
        }

        // Keep the client's timezone if there is one, recurrences depend on it (e.g. for DST)
        $tz = new DateTimeZone('UTC');
        if (isset($vevent->DTSTART['TZID'])) {
            try {
                $tz = $vevent->DTSTART->getDateTime()->getTimezone();
            } catch (Exception) {
            }
        }
        $vevent->DTSTART = $start->setTimezone($tz);
        $vevent->DTEND = $end->setTimezone($tz);
    }

    private function set_text(VEvent $vevent, string $name, ?string $value)
    {
        if ((string) $value === '') {
            unset($vevent->$name);
        } else {
            $vevent->$name = $value;
        }
    }

    /**
     * Adds the components of the stored copy (except the VTIMEZONEs we already have)
     */
    private function add_stored_components(VCalendar $stored)
    {
        foreach ($stored->getComponents() as $component) {
            if ($component->name === 'VTIMEZONE') {
                $tzid = (string) $component->TZID;
                if (isset($this->timezones[$tzid])) {
                    continue;
                }
                $this->timezones[$tzid] = true;
            }
            $this->_calendar->add($component);
        }
    }

    /**
     * Adds the participants as attendees
     *
     * Attendees from the stored copy are kept with their parameters (e.g. PARTSTAT) if they are
     * still participants, or if they are not known to OpenPSA at all
     */
    private function set_attendees(VEvent $vevent, array $participants)
    {
        $stored = [];
        foreach ($vevent->select('ATTENDEE') as $attendee) {
            if ($email = self::get_email($attendee)) {
                $stored[$email] = $attendee;
            }
        }
        unset($vevent->ATTENDEE);

        foreach (array_keys(array_filter($participants)) as $uid) {
            $email = $this->get_attendee_email($uid);
            if ($email && isset($stored[strtolower($email)])) {
                $vevent->add($stored[strtolower($email)]);
                unset($stored[strtolower($email)]);
                continue;
            }
            $this->add_attendee($vevent, $uid, 'ACCEPTED');
        }

        // Persons who are no longer participants are dropped, external addresses are kept
        $known = self::find_persons(array_keys($stored));
        foreach (array_diff_key($stored, $known) as $attendee) {
            $vevent->add($attendee);
        }
    }

    /**
     * The person's address, or a generated one if they have none (unless disabled)
     */
    private function get_attendee_email(int $uid) : ?string
    {
        $person = midcom_db_person::get_cached($uid);
        if (!empty($person->email)) {
            return $person->email;
        }
        if (!$this->placeholder_emails) {
            return null;
        }
        // Attendee must have email address of valid format, these must also be unique.
        return preg_replace('/[^0-9_a-z]/i', '_', strtolower($person->name)) . '_is_not@openpsa2.org';
    }

    private function add_attendee(VEvent $vevent, int $uid, string $partstat)
    {
        if (!$email = $this->get_attendee_email($uid)) {
            return;
        }
        $vevent->add('ATTENDEE', "mailto:{$email}", [
            'ROLE' => 'REQ-PARTICIPANT',
            'CUTYPE' => 'INDIVIDUAL',
            'PARTSTAT' => $partstat,
            'CN' => midcom_db_person::get_cached($uid)->rname
        ]);
    }

    private static function get_email(Property $attendee) : ?string
    {
        $value = trim((string) $attendee->getValue());
        if (stripos($value, 'mailto:') !== 0) {
            return null;
        }
        return strtolower(substr($value, 7)) ?: null;
    }

    /**
     * @param string[] $emails lowercase email addresses
     * @return array email => person ID
     */
    private static function find_persons(array $emails) : array
    {
        if (empty($emails)) {
            return [];
        }
        midcom::get()->auth->request_sudo('org.openpsa.calendar');
        $mc = midcom_db_person::new_collector();
        $mc->add_constraint('email', 'IN', array_unique($emails));
        $mc->add_order('id');
        $found = $mc->get_values('email', 'id');
        midcom::get()->auth->drop_sudo();

        $result = [];
        foreach ($found as $id => $email) {
            $result[strtolower($email)] ??= $id;
        }
        return $result;
    }

    private function to_utc(int $timestamp) : DateTime
    {
        return (new DateTime('@' . $timestamp))->setTimezone(new DateTimeZone('UTC'));
    }

    public function get_vcalendar() : VCalendar
    {
        return $this->_calendar;
    }

    public function __toString()
    {
        return $this->_calendar->serialize();
    }
}
