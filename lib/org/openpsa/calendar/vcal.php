<?php
/**
 * @package org.openpsa.calendar
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * vCalendar helper function
 *
 * @package org.openpsa.calendar
 */
class org_openpsa_calendar_vcal
{
    /**
     * newline format, defaults to \r\n
     *
     * @var string
     */
    private $newline;

    public function __construct($newline = "\r\n")
    {
        $this->_newline = $newline;
    }

    /**
     * Method for exporting event in vCalendar format
     *
     * @param org_openpsa_calendar_event_dba $event The event we're working on
     * @param array compatibility options to override
     * @return string vCalendar data
     */
    public function export_event(org_openpsa_calendar_event_dba $event, $compatibility = array())
    {
        $encoder = new org_openpsa_helpers_vxparser();
        $encoder->merge_compatibility($compatibility);

        // Simple key/value pairs, for multiple occurrences of same key use array as value
        $vcal_keys = array();
        // For extended key data, like charset
        $vcal_key_parameters = array();

        // TODO: handle UID smarter
        $vcal_keys['UID'] = "{$event->guid}-midgardGuid";

        $revised = $event->metadata->revised;
        $created = $event->metadata->created;

        $vcal_keys['LAST-MODIFIED'] = $encoder->vcal_stamp($revised, array('TZID' => 'UTC')) . 'Z';
        $vcal_keys['CREATED'] = $encoder->vcal_stamp($created, array('TZID' => 'UTC')) . 'Z';
        /**
         * The real meaning of the DTSTAMP is fuzzy at best
         * http://www.kanzaki.com/docs/ical/dtstamp.html is less than helpful
         * http://lists.osafoundation.org/pipermail/ietf-calsify/2007-July/001750.html
         * seems to suggest that using the revision would be best
         */
        $vcal_keys['DTSTAMP'] =& $vcal_keys['LAST-MODIFIED'];
        // Type handling
        switch ($event->orgOpenpsaAccesstype)
        {
            case ORG_OPENPSA_ACCESSTYPE_PUBLIC:
                $vcal_keys['CLASS'] = 'PUBLIC';
                break;
            default:
            case ORG_OPENPSA_ACCESSTYPE_PRIVATE:
                $vcal_keys['CLASS'] = 'PRIVATE';
                break;
        }
        // "busy" or "transparency" as vCalendar calls it
        if ($event->busy)
        {
            $vcal_keys['TRANSP'] = 'OPAQUE';
        }
        else
        {
            $vcal_keys['TRANSP'] = 'TRANSPARENT';
        }
        // tentative vs confirmed
        $vcal_keys['STATUS'] = 'CONFIRMED';
        // we don't categorize events, at least yet
        $vcal_keys['CATEGORIES'] = 'MEETING';
        // we don't handle priorities
        $vcal_keys['PRIORITY'] = 1;
        // Basic fields
        $vcal_keys['SUMMARY'] = $encoder->escape_separators($event->title);
        $vcal_keys['DESCRIPTION'] = $encoder->escape_separators($event->description);
        $vcal_keys['LOCATION'] = $encoder->escape_separators($event->location);
        // Start & End in UTC
        $vcal_keys['DTSTART'] = $encoder->vcal_stamp($event->start, array('TZID' => 'UTC')) . 'Z';
        $vcal_keys['DTEND'] = $encoder->vcal_stamp($event->end, array('TZID' => 'UTC')) . 'Z';
        // Participants
        $vcal_keys['ATTENDEE'] = array();
        $vcal_key_parameters['ATTENDEE'] = array();
        // Safety, otherwise the notice will make output invalid
        if (!is_array($event->participants))
        {
            $event->participants = array();
        }
        foreach ($event->participants as $uid => $bool)
        {
            // Just a safety
            if (!$bool)
            {
                continue;
            }
            $person = midcom_db_person::get_cached($uid);
            if (empty($person->email))
            {
                // Attendee must have email address of valid format, these must also be unique.
                $person->email = preg_replace('/[^0-9_\x61-\x7a]/i', '_', strtolower($person->name)) . '_is_not@openpsa.org';
            }
            $vcal_keys['ATTENDEE'][] = "mailto:{$person->email}";
            $vcal_key_parameters['ATTENDEE'][] = array
            (
                'ROLE' => 'REQ-PARTICIPANT',
                'CUTYPE' => 'INDIVIDUAL',
                'PARTSTAT' => 'ACCEPTED',
                'CN' => $encoder->escape_separators($person->rname, true),
            );
        }
        $ret = "BEGIN:VEVENT{$this->_newline}";
        $ret .= $encoder->export_vx_variables_recursive($vcal_keys, $vcal_key_parameters, false, $this->_newline);
        $ret .= "END:VEVENT{$this->_newline}";
        return $ret;
    }

    /**
     * Method for getting correct vcal file headers
     *
     * @param string method vCalendar method (defaults to "publish")
     * @return string vCalendar data
     */
    public function get_headers($method = "publish")
    {
        $method = strtoupper($method);
        $ret = '';
        $ret .= "BEGIN:VCALENDAR{$this->_newline}";
        $ret .= "VERSION:2.0{$this->_newline}";
        $ret .= "PRODID:-//OpenPSA/Calendar V2.0.0//EN{$this->_newline}";
        $ret .= "METHOD:{$method}{$this->_newline}";
        //TODO: Determine server timezone and output correct header (we still send all times as UTC)
        return $ret;
    }

    /**
     * Method for getting correct vcal file footers
     *
     * @return string vCalendar data
     */
    public function get_footers()
    {
        $ret = '';
        $ret .= "END:VCALENDAR{$this->_newline}";
        return $ret;
    }
}
?>