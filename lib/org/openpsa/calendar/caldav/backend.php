<?php
/**
 * @package org.openpsa.calendar
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

use Sabre\CalDAV\Backend\AbstractBackend;
use Sabre\CalDAV\Backend\SyncSupport;
use Sabre\CalDAV\Xml\Property\SupportedCalendarComponentSet;
use Sabre\DAV\Exception;

/**
 * CalDAV calendar backend
 *
 * Every user has one calendar ("default") containing all events they participate in.
 * The calendar ID is the person's ID. A recurring event is one calendar object (the master
 * with its rule and the overrides for occurrences edited on their own).
 *
 * Sync tokens (also used as ctag) are timestamps: A client holding token t has seen
 * all changes revised up to and including second t.
 *
 * @package org.openpsa.calendar
 */
class org_openpsa_calendar_caldav_backend extends AbstractBackend implements SyncSupport
{
    public const CALENDAR_URI = 'default';

    private ?int $root_event_id = null;

    public function __construct(private org_openpsa_calendar_caldav_principals $principals, private string $displayname, private bool $send_notify = true)
    {
    }

    public function getCalendarsForUser($principalUri)
    {
        if (!$person = $this->principals->get_person($principalUri)) {
            return [];
        }

        $token = $this->get_sync_token($person->id);
        return [[
            'id' => $person->id,
            'uri' => self::CALENDAR_URI,
            'principaluri' => $principalUri,
            '{DAV:}displayname' => $this->displayname,
            '{http://calendarserver.org/ns/}getctag' => $token,
            '{http://sabredav.org/ns}sync-token' => $token,
            '{urn:ietf:params:xml:ns:caldav}supported-calendar-component-set' => new SupportedCalendarComponentSet(['VEVENT']),
        ]];
    }

    public function createCalendar($principalUri, $calendarUri, array $properties)
    {
        throw new Exception\Forbidden('Creating calendars is not supported yet');
    }

    public function deleteCalendar($calendarId)
    {
        throw new Exception\Forbidden('Deleting calendars is not supported yet');
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
        return array_map($this->get_object_data(...), $this->find_events($calendarId, $uris));
    }

    public function calendarQuery($calendarId, array $filters)
    {
        if (!$qb = $this->get_events_qb($calendarId)) {
            return [];
        }
        [$validate_all, $validate_masters] = $this->apply_filters($qb, $filters);

        $result = [];
        foreach ($qb->execute() as $event) {
            $object = $this->get_object_data($event);
            if (   ($validate_all || ($validate_masters && $event->is_master()))
                && !$this->validateFilterForObject($object, $filters)) {
                continue;
            }
            $result[] = $object['uri'];
        }
        return $result;
    }

    /**
     * Adds what we can express as database constraints to the query, anything else is checked by sabre's validator
     *
     * @return bool[] Whether all events need validation, and whether masters of recurring events do
     */
    private function apply_filters(midcom_core_querybuilder $qb, array $filters) : array
    {
        $comp_filters = $filters['comp-filters'] ?? [];
        $plain = $this->is_plain_vevent_filter($comp_filters);
        $validate_all = !$plain || !empty($filters['prop-filters']) || !empty($filters['is-not-defined']);
        if (!$plain || !$timerange = $comp_filters[0]['time-range'] ?? null) {
            return [$validate_all, false];
        }
        $this->add_timerange_constraint($qb, $timerange['start']?->getTimestamp(), $timerange['end']?->getTimestamp());
        return [$validate_all, true];
    }

    /**
     * Whether the comp-filters are at most one VEVENT filter with nothing but a time-range
     */
    private function is_plain_vevent_filter(array $comp_filters) : bool
    {
        if (!$comp_filters) {
            return true;
        }
        $filter = $comp_filters[0];
        return count($comp_filters) === 1
            && $filter['name'] === 'VEVENT'
            && !$filter['is-not-defined']
            && empty($filter['comp-filters'])
            && empty($filter['prop-filters']);
    }

    /**
     * start and end only cover the first occurrence, so for recurring events, we look at the series' timespan.
     * Whether an occurrence actually falls into the range is checked by sabre's validator
     */
    private function add_timerange_constraint(midcom_core_querybuilder $qb, ?int $start, ?int $end)
    {
        $qb->begin_group('OR');
            // Overlap check
            $qb->begin_group('AND');
                if ($start) {
                    $qb->add_constraint('end', '>', $start);
                }
                if ($end) {
                    $qb->add_constraint('start', '<', $end);
                }
            $qb->end_group();
            $qb->begin_group('AND');
                $qb->add_constraint('recurrence', '<>', '');
                if ($end) {
                    $qb->add_constraint('start', '<', $end);
                }
                if ($start) {
                    $qb->begin_group('OR');
                        $qb->add_constraint('recurrenceUntil', '=', 0);
                        $qb->add_constraint('recurrenceUntil', '>', $start);
                    $qb->end_group();
                }
            $qb->end_group();
        $qb->end_group();
    }

    public function getCalendarObjectByUID($principalUri, $uid)
    {
        if (   !($person = $this->principals->get_person($principalUri))
            || !($qb = $this->get_events_qb($person->id))) {
            return null;
        }
        if (str_ends_with($uid, '-midgardGuid')) {
            $qb->add_constraint('guid', '=', substr($uid, 0, -12));
        } else {
            $qb->add_constraint('externalGuid', '=', $uid);
        }
        if ($event = $qb->get_result(0)) {
            return self::CALENDAR_URI . '/' . $this->get_uri($event);
        }
        return null;
    }

    public function createCalendarObject($calendarId, $objectUri, $calendarData)
    {
        $root_event = org_openpsa_calendar_interface::find_root_event();
        if (!$root_event->can_do('midgard:create')) {
            throw new Exception\Forbidden('You are not allowed to create events');
        }
        $event = new org_openpsa_calendar_event_dba;
        $event->up = $root_event->id;
        $event->caldavUri = $objectUri;
        $this->save($event, $calendarId, $calendarData);
        // No ETag, since what we return on GET differs from what the client sent
        return null;
    }

    public function updateCalendarObject($calendarId, $objectUri, $calendarData)
    {
        $event = $this->find_events($calendarId, [$objectUri])[0] ?? throw new Exception\NotFound('Event not found');
        if (!$event->can_do('midgard:update')) {
            throw new Exception\Forbidden('You are not allowed to change this event');
        }
        $this->save($event, $calendarId, $calendarData);
        return null;
    }

    /**
     * Deletes the event if the user is its only participant or its creator,
     * otherwise only removes the user from the participants
     */
    public function deleteCalendarObject($calendarId, $objectUri)
    {
        $event = $this->find_events($calendarId, [$objectUri])[0] ?? throw new Exception\NotFound('Event not found');
        $vevent = org_openpsa_calendar_vcal::get_stored_vevent($event);
        $notify = $this->send_notify && !($vevent && org_openpsa_calendar_vcal::has_external_organizer($vevent, $calendarId));
        $others = array_diff_key($event->participants, [$calendarId => true]);
        $is_creator = $event->metadata->creator === midcom::get()->auth->user?->guid;

        if (   ($others && !$is_creator)
            || !$event->can_do('midgard:delete')) {
            $this->remove_participant($event, $calendarId, $notify);
            return;
        }
        $event->send_notify = $notify;
        if (!$event->delete()) {
            throw org_openpsa_calendar_import::get_save_exception();
        }
    }

    private function save(org_openpsa_calendar_event_dba $event, int $owner_id, string $data)
    {
        $vcalendar = org_openpsa_calendar_vcal::read($data) ?? throw new Exception\BadRequest('Could not parse calendar data');
        (new org_openpsa_calendar_import($this->send_notify))->save($event, $owner_id, $vcalendar);
    }

    private function remove_participant(org_openpsa_calendar_event_dba $event, int $person_id, bool $notify)
    {
        if ($event->is_master()) {
            // Leaving a series
            (new org_openpsa_calendar_recurrence)->leave($event, $person_id, $notify);
            return;
        }
        $qb = org_openpsa_calendar_event_member_dba::new_query_builder();
        $qb->add_constraint('eid', '=', $event->id);
        $qb->add_constraint('uid', '=', $person_id);
        // Users can always leave events, even if they're not allowed to change them
        midcom::get()->auth->request_sudo('org.openpsa.calendar');
        foreach ($qb->execute() as $member) {
            $member->notify_person = $notify;
            $member->delete();
        }
        midcom::get()->auth->drop_sudo();
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
        $qb->add_constraint('id', 'IN', $ids);
        $qb->add_order('start');
        return $qb;
    }

    /**
     * @return org_openpsa_calendar_event_dba[]
     */
    private function find_events(int $person_id, array $uris) : array
    {
        if (!$uris || !$qb = $this->get_events_qb($person_id)) {
            return [];
        }
        $guids = [];
        foreach ($uris as $uri) {
            if (   str_ends_with($uri, '.ics')
                && mgd_is_guid($guid = substr($uri, 0, -4))) {
                $guids[] = $guid;
            }
        }
        // Same logic as get_uri(): An event with a client-chosen URI is only reachable under that one
        $qb->begin_group('OR');
        $qb->add_constraint('caldavUri', 'IN', $uris);
        if ($guids) {
            $qb->begin_group('AND');
                $qb->add_constraint('caldavUri', '=', '');
                $qb->add_constraint('guid', 'IN', array_unique($guids));
            $qb->end_group();
        }
        $qb->end_group();

        return $qb->execute();
    }

    private function get_uri(org_openpsa_calendar_event_dba $event) : string
    {
        return $event->caldavUri ?: $event->guid . '.ics';
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
            'uri' => $this->get_uri($event),
            'lastmodified' => $event->metadata->revised,
            // Participant changes don't touch the event's revised timestamp, so we hash the content
            'etag' => '"' . md5($data) . '"',
            'size' => strlen($data),
            'component' => 'vevent',
            'calendardata' => $data,
        ];
    }

    /**
     * Returns the changes since the given sync token
     *
     * An event counts as changed when it or one of its eventmember rows (of any participant,
     * since they show up as attendees) was revised after the token. Changed events that are
     * no longer in the calendar (deleted, user removed, no longer readable) are reported as deleted.
     */
    public function getChangesForCalendar($calendarId, $syncToken, $syncLevel, $limit = null)
    {
        $person_id = (int) $calendarId;
        $current = $this->get_sync_token($person_id);
        $result = ['syncToken' => $current, 'added' => [], 'modified' => [], 'deleted' => []];

        if (in_array($syncToken, [null, ''], true)) {
            if ($qb = $this->get_events_qb($person_id)) {
                $result['added'] = array_map($this->get_uri(...), $qb->execute());
            }
            return $result;
        }
        if (!$this->is_usable_token((string) $syncToken, $current)) {
            return null;
        }
        if (   $syncToken === $current
            || !$changed = $this->get_changed_event_ids($person_id, (int) $syncToken)) {
            return $result;
        }
        $current_events = [];
        if ($qb = $this->get_events_qb($person_id)) {
            $qb->add_constraint('id', 'IN', $changed);
            foreach ($qb->execute() as $event) {
                $current_events[$event->id] = $this->get_uri($event);
            }
        }
        $result['modified'] = array_values($current_events);
        $result['deleted'] = $this->get_deleted_uris(array_diff($changed, array_keys($current_events)));
        return $result;
    }

    /**
     * Whether the token is one of ours and still recent enough to report deletions since then
     */
    private function is_usable_token(string $token, string $current) : bool
    {
        if ($token === $current) {
            // Nothing changed since, even if that was long ago
            return true;
        }
        if (!ctype_digit($token) || (int) $token > (int) $current) {
            return false;
        }
        $purge_days = midcom::get()->config->get('cron_purge_deleted_after');
        return (int) $token >= time() - $purge_days * 86400;
    }

    /**
     * The URIs of events the user can't read anymore (since they need to be reported as deleted)
     */
    private function get_deleted_uris(array $ids) : array
    {
        if (!$ids) {
            return [];
        }
        midcom::get()->auth->request_sudo('org.openpsa.calendar');
        $qb = org_openpsa_calendar_event_dba::new_query_builder();
        $qb->include_deleted();
        $qb->add_constraint('id', 'IN', $ids);
        $uris = array_map($this->get_uri(...), $qb->execute());
        midcom::get()->auth->drop_sudo();
        return $uris;
    }

    /**
     * IDs of the person's calendar objects (single events and masters of recurring events),
     * including the ones they left or that were deleted
     */
    private function get_member_event_ids(int $person_id) : array
    {
        midcom::get()->auth->request_sudo('org.openpsa.calendar');
        $members = org_openpsa_calendar_event_member_dba::new_query_builder();
        $members->include_deleted();
        $members->add_constraint('uid', '=', $person_id);
        $members->add_constraint('eid.up', '=', $this->get_root_event_id());
        $ids = array_unique(array_column($members->execute(), 'eid'));
        midcom::get()->auth->drop_sudo();
        return $ids;
    }

    /**
     * Queries for the changes that affect the given calendar objects: the events and their eventmember
     * rows (of any participant, since they show up as attendees), including those of occurrences
     *
     * @return midcom_core_querybuilder[]
     */
    private function get_change_queries(array $ids) : array
    {
        $events = org_openpsa_calendar_event_dba::new_query_builder();
        $events->begin_group('OR');
            $events->add_constraint('id', 'IN', $ids);
            $events->add_constraint('up', 'IN', $ids);
        $events->end_group();

        $members = org_openpsa_calendar_event_member_dba::new_query_builder();
        $members->begin_group('OR');
            $members->add_constraint('eid', 'IN', $ids);
            $members->add_constraint('eid.up', 'IN', $ids);
        $members->end_group();

        foreach ([$events, $members] as $qb) {
            $qb->include_deleted();
        }
        return [$events, $members];
    }

    /**
     * @return int[] IDs of the calendar objects (changes to occurrences count for their master)
     */
    private function get_changed_event_ids(int $person_id, int $since) : array
    {
        if (!$ids = $this->get_member_event_ids($person_id)) {
            return [];
        }
        $changed = [];
        midcom::get()->auth->request_sudo('org.openpsa.calendar');
        foreach ($this->get_change_queries($ids) as $qb) {
            $qb->add_constraint('metadata.revised', '>', gmdate('Y-m-d H:i:s', $since));
            foreach ($qb->execute() as $object) {
                $changed[] = $object instanceof org_openpsa_calendar_event_member_dba ? $object->eid : $object->id;
            }
        }
        $result = array_intersect(array_unique($changed), $ids);
        if ($occurrences = array_diff($changed, $ids)) {
            $qb = org_openpsa_calendar_event_dba::new_query_builder();
            $qb->include_deleted();
            $qb->add_constraint('id', 'IN', array_unique($occurrences));
            $result = array_merge($result, array_intersect(array_column($qb->execute(), 'up'), $ids));
        }
        midcom::get()->auth->drop_sudo();
        return array_unique($result);
    }

    /**
     * The token changes whenever one of the person's events or its participants are changed
     * (deleted entries are included so that removals are noticed as well)
     *
     * It is never the current second, because further changes could still happen within it
     * and would then not be newer than the token
     */
    private function get_sync_token(int $person_id) : string
    {
        $revised = 0;
        if ($ids = $this->get_member_event_ids($person_id)) {
            midcom::get()->auth->request_sudo('org.openpsa.calendar');
            foreach ($this->get_change_queries($ids) as $qb) {
                $qb->add_order('metadata.revised', 'DESC');
                $qb->set_limit(1);
                if ($latest = $qb->get_result(0)) {
                    $revised = max($revised, $latest->metadata->revised);
                }
            }
            midcom::get()->auth->drop_sudo();
        }
        return (string) min($revised, time() - 1);
    }
}
