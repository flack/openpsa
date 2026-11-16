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
 * else (alarms, recurrence rules and exceptions, X-properties etc.) survives a round trip.
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
        $stored = self::read($event->vCalSerialized);
        if ($stored && $vevent = self::get_master($stored)) {
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

        /**
         * The real meaning of the DTSTAMP is fuzzy at best
         * http://www.kanzaki.com/docs/ical/dtstamp.html is less than helpful
         * http://lists.osafoundation.org/pipermail/ietf-calsify/2007-July/001750.html
         * seems to suggest that using the revision would be best
         */
        $revised = $this->to_utc($event->metadata->revised);
        $vevent->DTSTAMP = $revised;
        $vevent->{'LAST-MODIFIED'} = $revised;
        $this->set_times($vevent, $event, $stored !== null);

        $vevent->{'CLASS'} = ($event->orgOpenpsaAccesstype === org_openpsa_core_acl::ACCESS_PRIVATE) ? 'PRIVATE' : 'PUBLIC';
        // "busy" or "transparency" as vCalendar calls it
        $vevent->TRANSP = ($event->busy) ? 'OPAQUE' : 'TRANSPARENT';
        $this->set_text($vevent, 'SUMMARY', $event->title);
        $this->set_text($vevent, 'DESCRIPTION', $event->description);
        $this->set_text($vevent, 'LOCATION', $event->location);

        $this->set_attendees($vevent, $event->participants);
        return $vevent;
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
     * @return int[]
     */
    public static function get_attendee_ids(VEvent $vevent) : array
    {
        $emails = array_filter(array_map(self::get_email(...), $vevent->select('ATTENDEE')));
        return array_values(self::find_persons($emails));
    }

    /**
     * @return int[] Start and end timestamp
     */
    private static function get_times(VEvent $vevent) : array
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

    private function set_times(VEvent $vevent, org_openpsa_calendar_event_dba $event, bool $stored)
    {
        if (   $stored
            && isset($vevent->DTSTART)
            && $this->normalize_times(...self::get_times($vevent)) === $this->normalize_times($event->start, $event->end)) {
            // Unchanged since the client sent it, so we keep the original representation
            return;
        }
        $start = new DateTime('@' . ($event->start - $event->start % 60));
        $end = new DateTime('@' . $event->end);
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
            $person = midcom_db_person::get_cached($uid);
            $email = $person->email;
            if (empty($email)) {
                if (!$this->placeholder_emails) {
                    continue;
                }
                // Attendee must have email address of valid format, these must also be unique.
                $email = preg_replace('/[^0-9_a-z]/i', '_', strtolower($person->name)) . '_is_not@openpsa2.org';
            }
            if (isset($stored[strtolower($email)])) {
                $vevent->add($stored[strtolower($email)]);
                unset($stored[strtolower($email)]);
                continue;
            }
            $vevent->add('ATTENDEE', "mailto:{$email}", [
                'ROLE' => 'REQ-PARTICIPANT',
                'CUTYPE' => 'INDIVIDUAL',
                'PARTSTAT' => 'ACCEPTED',
                'CN' => $person->rname,
            ]);
        }

        // Persons who are no longer participants are dropped, external addresses are kept
        $known = self::find_persons(array_keys($stored));
        foreach (array_diff_key($stored, $known) as $attendee) {
            $vevent->add($attendee);
        }
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
        $mc->add_constraint('email', 'IN', array_values(array_unique($emails)));
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
