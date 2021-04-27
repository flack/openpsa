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
    /**
     * The calendar object
     *
     * @var Sabre\VObject\Component\VCalendar
     */
    private $_calendar;

    /**
     * @param string $method vCalendar method (defaults to "publish")
     */
    public function __construct(string $method = "PUBLISH")
    {
        $this->_calendar = new VCalendar;
        $this->_calendar->VERSION = '2.0';
        $this->_calendar->PRODID = "-//OpenPSA//Calendar " . org_openpsa_core_version::get_version_number() . "//" . strtoupper(midcom::get()->i18n->get_current_language());
        $this->_calendar->METHOD = strtoupper($method);
    }

    /**
     * Export event in vCalendar format
     */
    public function add_event(org_openpsa_calendar_event_dba $event)
    {
        $vevent = $this->_calendar->createComponent('VEVENT');

        // TODO: handle UID smarter
        $vevent->UID = "{$event->guid}-midgardGuid";

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
    }

    private function _add_participants(VEvent $vevent, array $participants)
    {
        $participants = array_filter($participants);
        foreach (array_keys($participants) as $uid) {
            $person = midcom_db_person::get_cached($uid);
            if (empty($person->email)) {
                // Attendee must have email address of valid format, these must also be unique.
                $person->email = preg_replace('/[^0-9_a-z]/i', '_', strtolower($person->name)) . '_is_not@openpsa2.org';
            }
            $parameters = [
                'ROLE' => 'REQ-PARTICIPANT',
                'CUTYPE' => 'INDIVIDUAL',
                'PARTSTAT' => 'ACCEPTED',
                'CN' => $person->rname,
            ];
            $vevent->add('ATTENDEE', "mailto:{$person->email}", $parameters);
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

    public function __toString()
    {
        return $this->_calendar->serialize();
    }
}
