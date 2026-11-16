<?php
/**
 * @package org.openpsa.calendar
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

use Sabre\VObject\Component\VEvent;
use Sabre\VObject\Component\VCalendar;

/**
 * vCalendar helper class
 *
 * @package org.openpsa.calendar
 */
class org_openpsa_calendar_vcal
{
    private VCalendar $_calendar;

    private bool $placeholder_emails;

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
     */
    public function event_to_vevent(org_openpsa_calendar_event_dba $event) : VEvent
    {
        $vevent = $this->_calendar->createComponent('VEVENT');

        $vevent->UID = self::get_uid($event);

        $this->_add_date_fields($vevent, $event);

        // Type handling
        if ($event->orgOpenpsaAccesstype === org_openpsa_core_acl::ACCESS_PUBLIC) {
            $vevent->{'CLASS'} = 'PUBLIC';
        } else {
            $vevent->{'CLASS'} = 'PRIVATE';
        }
        // "busy" or "transparency" as vCalendar calls it
        $vevent->TRANSP = ($event->busy) ? 'OPAQUE' : 'TRANSPARENT';
        // tentative vs confirmed
        $vevent->STATUS = 'CONFIRMED';
        // we don't categorize events, at least yet
        $vevent->CATEGORIES = 'MEETING';
        // we don't handle priorities
        $vevent->PRIORITY = 1;
        // Basic fields
        $vevent->SUMMARY = $event->title;
        $vevent->DESCRIPTION = $event->description;
        $vevent->LOCATION = $event->location;

        $this->_add_participants($vevent, $event->participants);
        $this->_calendar->add($vevent);
        return $vevent;
    }

    public static function get_uid(org_openpsa_calendar_event_dba $event) : string
    {
        return $event->externalGuid ?: "{$event->guid}-midgardGuid";
    }

    /**
     * Copies the values of a VEVENT to the event (does not save it)
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

        $event->start = $start->getTimestamp();
        $event->end = $end->getTimestamp();
    }

    private function _add_participants(VEvent $vevent, array $participants)
    {
        $participants = array_filter($participants);
        foreach (array_keys($participants) as $uid) {
            $person = midcom_db_person::get_cached($uid);
            $email = $person->email;
            if (empty($email)) {
                if (!$this->placeholder_emails) {
                    continue;
                }
                // Attendee must have email address of valid format, these must also be unique.
                $email = preg_replace('/[^0-9_a-z]/i', '_', strtolower($person->name)) . '_is_not@openpsa2.org';
            }
            $parameters = [
                'ROLE' => 'REQ-PARTICIPANT',
                'CUTYPE' => 'INDIVIDUAL',
                'PARTSTAT' => 'ACCEPTED',
                'CN' => $person->rname,
            ];
            $vevent->add('ATTENDEE', "mailto:{$email}", $parameters);
        }
    }

    private function _add_date_fields(VEvent $vevent, org_openpsa_calendar_event_dba $event)
    {
        $tz = new DateTimeZone('UTC');
        $revised = new DateTime('@' . $event->metadata->revised);
        $revised->setTimezone($tz);
        $created = new DateTime('@' . $event->metadata->created);
        $created->setTimezone($tz);
        $start = new DateTime('@' . $event->start);
        $start->setTimezone($tz);
        $end = new DateTime('@' . $event->end);
        $end->setTimezone($tz);

        $vevent->CREATED = $created;
        /**
         * The real meaning of the DTSTAMP is fuzzy at best
         * http://www.kanzaki.com/docs/ical/dtstamp.html is less than helpful
         * http://lists.osafoundation.org/pipermail/ietf-calsify/2007-July/001750.html
         * seems to suggest that using the revision would be best
         */
        $vevent->DTSTAMP = $revised;
        $vevent->{'LAST-MODIFIED'} = $revised;
        $vevent->DTSTART = $start;
        $vevent->DTEND = $end;
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
