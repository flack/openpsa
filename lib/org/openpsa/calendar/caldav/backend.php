<?php
/**
 * @package org.openpsa.calendar
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

use Sabre\CalDAV\Backend\AbstractBackend;
use Sabre\CalDAV\Xml\Property\SupportedCalendarComponentSet;
use Sabre\DAV\Exception\Forbidden;

/**
 * CalDAV calendar backend
 *
 * Every user has one calendar ("default") containing all events they participate in.
 * The calendar ID is the person's ID. Calendars are read-only for now.
 *
 * @package org.openpsa.calendar
 */
class org_openpsa_calendar_caldav_backend extends AbstractBackend
{
    public const CALENDAR_URI = 'default';

    private ?int $root_event_id = null;

    public function __construct(private org_openpsa_calendar_caldav_principals $principals, private string $displayname)
    {
    }

    public function getCalendarsForUser($principalUri)
    {
        if (!$person = $this->principals->get_person($principalUri)) {
            return [];
        }

        return [[
            'id' => $person->id,
            'uri' => self::CALENDAR_URI,
            'principaluri' => $principalUri,
            '{DAV:}displayname' => $this->displayname,
            '{http://calendarserver.org/ns/}getctag' => $this->get_ctag($person->id),
            '{urn:ietf:params:xml:ns:caldav}supported-calendar-component-set' => new SupportedCalendarComponentSet(['VEVENT']),
            '{http://sabredav.org/ns}read-only' => true,
        ]];
    }

    public function createCalendar($principalUri, $calendarUri, array $properties)
    {
        throw new Forbidden('Creating calendars is not supported yet');
    }

    public function deleteCalendar($calendarId)
    {
        throw new Forbidden('Deleting calendars is not supported yet');
    }

    public function getCalendarObjects($calendarId)
    {
        if (!$qb = $this->get_events_qb($calendarId)) {
            return [];
        }
        return array_map($this->get_object_data(...), $qb->execute());
    }

    public function getCalendarObject($calendarId, $objectUri)
    {
        return $this->getMultipleCalendarObjects($calendarId, [$objectUri])[0] ?? null;
    }

    public function getMultipleCalendarObjects($calendarId, array $uris)
    {
        $guids = [];
        foreach ($uris as $uri) {
            if (   str_ends_with($uri, '.ics')
                && mgd_is_guid($guid = substr($uri, 0, -4))) {
                $guids[] = $guid;
            }
        }
        if (!$guids || !$qb = $this->get_events_qb($calendarId)) {
            return [];
        }
        $qb->add_constraint('guid', 'IN', array_values(array_unique($guids)));
        return array_map($this->get_object_data(...), $qb->execute());
    }

    public function calendarQuery($calendarId, array $filters)
    {
        if (!$qb = $this->get_events_qb($calendarId)) {
            return [];
        }
        // Anything we can't express as a database constraint is checked by sabre's validator
        $needs_validation = !empty($filters['prop-filters']) || !empty($filters['is-not-defined']);
        if (!empty($filters['comp-filters'])) {
            $comp_filter = $filters['comp-filters'][0];
            if (   count($filters['comp-filters']) > 1
                || $comp_filter['name'] !== 'VEVENT'
                || $comp_filter['is-not-defined']
                || !empty($comp_filter['comp-filters'])
                || !empty($comp_filter['prop-filters'])) {
                $needs_validation = true;
            } elseif ($comp_filter['time-range']) {
                // Overlap check (events don't recur yet, so start/end cover all instances)
                if ($comp_filter['time-range']['start']) {
                    $qb->add_constraint('end', '>', $comp_filter['time-range']['start']->getTimestamp());
                }
                if ($comp_filter['time-range']['end']) {
                    $qb->add_constraint('start', '<', $comp_filter['time-range']['end']->getTimestamp());
                }
            }
        }

        $result = [];
        foreach ($qb->execute() as $event) {
            $object = $this->get_object_data($event);
            if (!$needs_validation || $this->validateFilterForObject($object, $filters)) {
                $result[] = $object['uri'];
            }
        }
        return $result;
    }

    public function getCalendarObjectByUID($principalUri, $uid)
    {
        if (!$person = $this->principals->get_person($principalUri)) {
            return null;
        }
        if (str_ends_with($uid, '-midgardGuid')) {
            $object = $this->getCalendarObject($person->id, substr($uid, 0, -12) . '.ics');
            return $object ? self::CALENDAR_URI . '/' . $object['uri'] : null;
        }
        if (!$qb = $this->get_events_qb($person->id)) {
            return null;
        }
        $qb->add_constraint('externalGuid', '=', $uid);
        if ($event = $qb->get_result(0)) {
            return self::CALENDAR_URI . '/' . $event->guid . '.ics';
        }
        return null;
    }

    public function createCalendarObject($calendarId, $objectUri, $calendarData)
    {
        throw new Forbidden('This calendar is read-only');
    }

    public function updateCalendarObject($calendarId, $objectUri, $calendarData)
    {
        throw new Forbidden('This calendar is read-only');
    }

    public function deleteCalendarObject($calendarId, $objectUri)
    {
        throw new Forbidden('This calendar is read-only');
    }

    private function get_root_event_id() : int
    {
        return $this->root_event_id ??= org_openpsa_calendar_interface::find_root_event()->id;
    }

    /**
     * Returns a QB for the events the person participates in, or null if there are none
     */
    private function get_events_qb(int $person_id) : ?midcom_core_querybuilder
    {
        $mc = org_openpsa_calendar_event_member_dba::new_collector('uid', $person_id);
        if (!$ids = array_unique($mc->get_values('eid'))) {
            return null;
        }
        $qb = org_openpsa_calendar_event_dba::new_query_builder();
        $qb->add_constraint('up', '=', $this->get_root_event_id());
        $qb->add_constraint('id', 'IN', array_values($ids));
        $qb->add_order('start');
        return $qb;
    }

    /**
     * Converts the event to the array format sabre expects for calendar objects
     */
    private function get_object_data(org_openpsa_calendar_event_dba $event) : array
    {
        $encoder = new org_openpsa_calendar_vcal(null, false);
        $encoder->add_event($event);
        $data = (string) $encoder;

        return [
            'id' => $event->id,
            'uri' => $event->guid . '.ics',
            'lastmodified' => $event->metadata->revised,
            // Participant changes don't touch the event's revised timestamp, so we hash the content
            'etag' => '"' . md5($data) . '"',
            'size' => strlen($data),
            'component' => 'vevent',
            'calendardata' => $data,
        ];
    }

    /**
     * The ctag changes whenever one of the person's events or its participants are changed
     * (deleted entries are included so that removals are noticed as well)
     */
    private function get_ctag(int $person_id) : string
    {
        $revised = 0;
        $qb = org_openpsa_calendar_event_member_dba::new_query_builder();
        $qb->include_deleted();
        $qb->add_constraint('uid', '=', $person_id);
        if ($ids = array_values(array_unique(array_column($qb->execute(), 'eid')))) {
            $to_check = [
                org_openpsa_calendar_event_member_dba::class => 'eid',
                org_openpsa_calendar_event_dba::class => 'id'
            ];
            foreach ($to_check as $class => $field) {
                $qb = $class::new_query_builder();
                $qb->include_deleted();
                $qb->add_constraint($field, 'IN', $ids);
                $qb->add_order('metadata.revised', 'DESC');
                $qb->set_limit(1);
                if ($latest = $qb->get_result(0)) {
                    $revised = max($revised, $latest->metadata->revised);
                }
            }
        }
        return (string) $revised;
    }
}
