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
use Sabre\VObject\Component\VEvent;

/**
 * CalDAV calendar backend
 *
 * Every user has one calendar ("default") containing all events they participate in.
 * The calendar ID is the person's ID.
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
        // Anything we can't express as a database constraint is checked by sabre's validator
        $needs_validation = !empty($filters['prop-filters']) || !empty($filters['is-not-defined']);
        $time_range = false;
        if (!empty($filters['comp-filters'])) {
            $comp_filter = $filters['comp-filters'][0];
            if (   count($filters['comp-filters']) > 1
                || $comp_filter['name'] !== 'VEVENT'
                || $comp_filter['is-not-defined']
                || !empty($comp_filter['comp-filters'])
                || !empty($comp_filter['prop-filters'])) {
                $needs_validation = true;
            } elseif ($time_range = $comp_filter['time-range']) {
                $qb->begin_group('OR');
                // Overlap check
                $qb->begin_group('AND');
                if ($time_range['start']) {
                    $qb->add_constraint('end', '>', $time_range['start']->getTimestamp());
                }
                if ($time_range['end']) {
                    $qb->add_constraint('start', '<', $time_range['end']->getTimestamp());
                }
                $qb->end_group();
                // start and end only cover the first occurrence, so recurring events are checked by the validator
                $qb->add_constraint('vCalSerialized', 'LIKE', '%RRULE%');
                $qb->add_constraint('vCalSerialized', 'LIKE', '%RDATE%');
                $qb->end_group();
            }
        }

        $result = [];
        foreach ($qb->execute() as $event) {
            $object = $this->get_object_data($event);
            if (   ($needs_validation || ($time_range && $this->is_recurring($event)))
                && !$this->validateFilterForObject($object, $filters)) {
                continue;
            }
            $result[] = $object['uri'];
        }
        return $result;
    }

    /**
     * Whether the client's copy of the event has recurrences (OpenPSA itself only stores the first occurrence)
     */
    private function is_recurring(org_openpsa_calendar_event_dba $event) : bool
    {
        return (bool) preg_match('/^(RRULE|RDATE)[:;]/m', (string) $event->vCalSerialized);
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
        $others = array_diff_key(array_filter($event->participants), [$calendarId => true]);
        $is_creator = $event->metadata->creator === midcom::get()->auth->user?->guid;

        if (   ($others && !$is_creator)
            || !$event->can_do('midgard:delete')) {
            $this->remove_participant($event, $calendarId);
            return;
        }
        $event->send_notify = $this->send_notify;
        if (!$event->delete()) {
            throw $this->get_save_exception();
        }
    }

    private function save(org_openpsa_calendar_event_dba $event, int $owner_id, string $data)
    {
        $vcalendar = org_openpsa_calendar_vcal::read($data) ?? throw new Exception\BadRequest('Could not parse calendar data');
        $vevent = org_openpsa_calendar_vcal::get_master($vcalendar) ?? throw new Exception\BadRequest('No VEVENT found');

        org_openpsa_calendar_vcal::vevent_to_event($vevent, $event);
        $uid = (string) $vevent->UID;
        if (!$event->id && !str_ends_with($uid, '-midgardGuid')) {
            $event->externalGuid = $uid;
        }
        // Keep the client's copy, so that what OpenPSA doesn't understand (alarms, recurrences etc.) survives
        $event->vCalSerialized = $vcalendar->serialize();

        $participants = $this->get_participants($vevent, $event, $owner_id);
        $event->participants = $participants;
        $conflictmanager = new org_openpsa_calendar_conflictmanager($event);
        if (!$conflictmanager->run($event->rob_tentative)) {
            throw new Exception\Conflict($this->get_conflict_message($conflictmanager));
        }
        $event->ignorebusy_em = true;
        $event->send_notify = $this->send_notify;

        if (!($event->id ? $event->update() : $event->create())) {
            throw $this->get_save_exception();
        }
        if ($this->sync_participants($event, $participants)) {
            // Participants get their privileges when the event is updated
            $event->send_notify = false;
            $event->search_relatedtos = false;
            $event->update();
        }
    }

    /**
     * Returns the participants as described by the VEVENT's attendees
     *
     * The owner always stays, as do participants without email address (they can't be attendees)
     */
    private function get_participants(VEvent $vevent, org_openpsa_calendar_event_dba $event, int $owner_id) : array
    {
        $participants = array_fill_keys([$owner_id, ...org_openpsa_calendar_vcal::get_attendee_ids($vevent)], true);
        foreach (array_keys(array_filter($event->participants)) as $id) {
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
    private function sync_participants(org_openpsa_calendar_event_dba $event, array $participants) : bool
    {
        $qb = org_openpsa_calendar_event_member_dba::new_query_builder();
        $qb->add_constraint('eid', '=', $event->id);
        foreach ($qb->execute() as $member) {
            if (!isset($participants[$member->uid])) {
                $member->notify_person = $this->send_notify;
                $member->delete();
            }
            unset($participants[$member->uid]);
        }
        foreach (array_keys($participants) as $uid) {
            $member = new org_openpsa_calendar_event_member_dba;
            $member->eid = $event->id;
            $member->uid = $uid;
            $member->notify_person = $this->send_notify;
            if (!$member->create()) {
                throw $this->get_save_exception();
            }
        }
        return !empty($participants);
    }

    private function remove_participant(org_openpsa_calendar_event_dba $event, int $person_id)
    {
        $qb = org_openpsa_calendar_event_member_dba::new_query_builder();
        $qb->add_constraint('eid', '=', $event->id);
        $qb->add_constraint('uid', '=', $person_id);
        // Users can always leave events, even if they're not allowed to change them
        midcom::get()->auth->request_sudo('org.openpsa.calendar');
        foreach ($qb->execute() as $member) {
            $member->notify_person = $this->send_notify;
            $member->delete();
        }
        midcom::get()->auth->drop_sudo();
    }

    private function get_conflict_message(org_openpsa_calendar_conflictmanager $conflictmanager) : string
    {
        $names = [];
        foreach (array_keys($conflictmanager->busy_members) as $uid) {
            $names[] = midcom_db_person::get_cached($uid)->name;
        }
        foreach (array_keys($conflictmanager->busy_resources) as $id) {
            $names[] = org_openpsa_calendar_resource_dba::get_cached($id)->title;
        }
        return 'The event conflicts with other events of: ' . implode(', ', $names);
    }

    private function get_save_exception() : Exception
    {
        $message = midcom_connection::get_error_string();
        return match (midcom_connection::get_error()) {
            MGD_ERR_ACCESS_DENIED => new Exception\Forbidden($message),
            MGD_ERR_RANGE => new Exception\BadRequest($message),
            default => new Exception\Conflict($message)
        };
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

        if ($syncToken === null || $syncToken === '') {
            $qb = $this->get_events_qb($person_id);
            $result['added'] = $qb ? array_map($this->get_uri(...), $qb->execute()) : [];
            return $result;
        }
        if (!ctype_digit((string) $syncToken) || (int) $syncToken > (int) $current) {
            return null;
        }
        if ($syncToken === $current) {
            return $result;
        }
        // Deleted objects get purged eventually, after which their removal can't be reported anymore
        $purge_days = midcom::get()->config->get('cron_purge_deleted_after');
        if ((int) $syncToken < time() - $purge_days * 86400) {
            return null;
        }

        if (!$changed = $this->get_changed_event_ids($person_id, (int) $syncToken)) {
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

        if ($missing = array_diff($changed, array_keys($current_events))) {
            // The user can't read these anymore, but needs to know their URIs
            midcom::get()->auth->request_sudo('org.openpsa.calendar');
            $qb = org_openpsa_calendar_event_dba::new_query_builder();
            $qb->include_deleted();
            $qb->add_constraint('id', 'IN', $missing);
            $result['deleted'] = array_map($this->get_uri(...), $qb->execute());
            midcom::get()->auth->drop_sudo();
        }
        return $result;
    }

    /**
     * IDs of all events the person participates or participated in
     */
    private function get_member_event_ids(int $person_id) : array
    {
        $qb = org_openpsa_calendar_event_member_dba::new_query_builder();
        $qb->include_deleted();
        $qb->add_constraint('uid', '=', $person_id);
        return array_unique(array_column($qb->execute(), 'eid'));
    }

    /**
     * @return array<string, string> classname => field holding the event ID
     */
    private function get_change_sources() : array
    {
        return [
            org_openpsa_calendar_event_member_dba::class => 'eid',
            org_openpsa_calendar_event_dba::class => 'id'
        ];
    }

    private function get_changed_event_ids(int $person_id, int $since) : array
    {
        if (!$ids = $this->get_member_event_ids($person_id)) {
            return [];
        }
        $changed = [];
        foreach ($this->get_change_sources() as $class => $field) {
            $qb = $class::new_query_builder();
            $qb->include_deleted();
            $qb->add_constraint($field, 'IN', $ids);
            $qb->add_constraint('metadata.revised', '>', gmdate('Y-m-d H:i:s', $since));
            $changed = array_merge($changed, array_column($qb->execute(), $field));
        }
        return array_unique($changed);
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
            foreach ($this->get_change_sources() as $class => $field) {
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
        return (string) min($revised, time() - 1);
    }
}
