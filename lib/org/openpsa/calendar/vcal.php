<?php
/**
 * @package org.openpsa.calendar
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

use Sabre\VObject\Component;
use Sabre\VObject\Property\DateTime as VDateTime;
use Sabre\VObject\Component\VEvent;

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
     * @var Sabre\VObject\Component
     */
    private $_calendar;

    /**
     * @param string $method vCalendar method (defaults to "publish")
     */
    public function __construct($method = "PUBLISH")
    {
        $method = strtoupper($method);

        $this->_calendar = Component::create('VCALENDAR');
        $this->_calendar->VERSION = '2.0';
        $this->_calendar->PRODID = "-//OpenPSA//Calendar " . org_openpsa_core_version::get_version_number() . "//" . strtoupper(midcom::get()->i18n->get_current_language());
        $this->_calendar->METHOD = $method;
        //TODO: Determine server timezone and output correct header (we still send all times as UTC)
    }

    /**
     * Export event in vCalendar format
     *
     * @param org_openpsa_calendar_event_dba $event The event we're working on
     * @return string vCalendar data
     */
    public function add_event(org_openpsa_calendar_event_dba $event)
    {
        $vevent = Component::create('VEVENT');

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
                $person->email = preg_replace('/[^0-9_\x61-\x7a]/i', '_', strtolower($person->name)) . '_is_not@openpsa2.org';
            }
            $parameters = array
            (
                'ROLE' => 'REQ-PARTICIPANT',
                'CUTYPE' => 'INDIVIDUAL',
                'PARTSTAT' => 'ACCEPTED',
                'CN' => $person->rname,
            );
            $vevent->add('ATTENDEE', "mailto:{$person->email}", $parameters);
        }
    }

    private function _add_date_fields(VEvent $vevent, org_openpsa_calendar_event_dba $event)
    {
        $revised = new DateTime('@' . $event->metadata->revised);
        $created = new DateTime('@' . $event->metadata->created);
        $start = new DateTime('@' . $event->start);
        $end = new DateTime('@' . $event->end);

        $vevent->add(new VDateTime('CREATED'));
        $vevent->add(new VDateTime('LAST-MODIFIED'));
        $vevent->add(new VDateTime('DTSTAMP'));
        $vevent->add(new VDateTime('DTSTART'));
        $vevent->add(new VDateTime('DTEND'));

        $vevent->CREATED->setDateTime($created, VDateTime::UTC);
        /**
         * The real meaning of the DTSTAMP is fuzzy at best
         * http://www.kanzaki.com/docs/ical/dtstamp.html is less than helpful
         * http://lists.osafoundation.org/pipermail/ietf-calsify/2007-July/001750.html
         * seems to suggest that using the revision would be best
         */
        $vevent->DTSTAMP->setDateTime($revised, VDateTime::UTC);
        $vevent->{'LAST-MODIFIED'}->setDateTime($revised, VDateTime::UTC);
        $vevent->DTSTART->setDateTime($start, VDateTime::UTC);
        $vevent->DTEND->setDateTime($end, VDateTime::UTC);
    }

    public function __toString()
    {
        return $this->_calendar->serialize();
    }
}
