<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace test\org\openpsa\calendar\caldav;

use openpsa_testcase;
use midcom;
use midcom_db_person;
use org_openpsa_calendar_caldav_backend;
use org_openpsa_calendar_caldav_principals;
use org_openpsa_calendar_event_dba;
use org_openpsa_calendar_event_member_dba;
use org_openpsa_calendar_interface;
use Sabre\DAV\Exception\Conflict;
use Sabre\DAV\Exception\NotFound;
use org_openpsa_mail_backend_unittest;
use DateTime;
use DateTimeZone;
use DateTimeImmutable;
use midgard_query_builder;
use Sabre\VObject\Reader;

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class backendTest extends openpsa_testcase
{
    use backdate;

    public static function setUpBeforeClass() : void
    {
        // Users need to be allowed to create events (usually granted by the admin)
        midcom::get()->auth->request_sudo('org.openpsa.calendar');
        org_openpsa_calendar_interface::find_root_event()->set_privilege('midgard:create', 'USERS', MIDCOM_PRIVILEGE_ALLOW);
        midcom::get()->auth->drop_sudo();
    }

    public static function tearDownAfterClass() : void
    {
        midcom::get()->auth->request_sudo('org.openpsa.calendar');
        org_openpsa_calendar_interface::find_root_event()->unset_privilege('midgard:create', 'USERS');
        midcom::get()->auth->drop_sudo();
        parent::tearDownAfterClass();
    }

    private function create_event(midcom_db_person $person, int $start, array $attributes = []) : org_openpsa_calendar_event_dba
    {
        $event = $this->create_object(org_openpsa_calendar_event_dba::class, $attributes + [
            'up' => org_openpsa_calendar_interface::find_root_event()->id,
            'start' => $start,
            'end' => $start + 3600,
            'title' => 'Event ' . $start,
        ]);
        $this->create_object(org_openpsa_calendar_event_member_dba::class, [
            'uid' => $person->id,
            'eid' => $event->id
        ]);
        return $event;
    }

    private function get_principal(midcom_db_person $person) : string
    {
        return org_openpsa_calendar_caldav_principals::get_principal_uri($person->lastname);
    }

    private function create_person_with_email(bool $login = false) : midcom_db_person
    {
        $person = $this->create_user($login);
        $person->email = 'caldav-' . $person->id . '-' . uniqid() . '@openpsa.test';
        $this->sudo($person->update(...));
        return $person;
    }

    private function get_backend(bool $send_notify = false) : org_openpsa_calendar_caldav_backend
    {
        return new org_openpsa_calendar_caldav_backend(new org_openpsa_calendar_caldav_principals, 'Test Calendar', $send_notify);
    }

    /**
     * @param string[] $properties Content lines of the VEVENT (UID and DTSTAMP are added)
     * @param string[] $extra_components Content lines added after the VEVENT
     */
    private function ics(string $uid, array $properties, array $extra_components = []) : string
    {
        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//Test//Client//EN',
            'BEGIN:VEVENT',
            'UID:' . $uid,
            'DTSTAMP:20050101T000000Z',
            ...$properties,
            'END:VEVENT',
            ...$extra_components,
            'END:VCALENDAR',
        ];
        return implode("\r\n", $lines) . "\r\n";
    }

    /**
     * Creates an event via CalDAV under the URI <uid>.ics
     *
     * @param string[] $properties
     * @param string[] $extra_components
     */
    private function create_via_caldav(
        org_openpsa_calendar_caldav_backend $backend,
        midcom_db_person $user,
        string $uid,
        array $properties,
        array $extra_components = []
    ) : ?string {
        $data = $this->ics($uid, $properties, $extra_components);
        return $backend->createCalendarObject($user->id, $uid . '.ics', $data);
    }

    private function uri(org_openpsa_calendar_event_dba $event) : string
    {
        return $event->guid . '.ics';
    }

    private function find_created_event(string $external_guid) : org_openpsa_calendar_event_dba
    {
        $qb = org_openpsa_calendar_event_dba::new_query_builder();
        $qb->add_constraint('externalGuid', '=', $external_guid);
        $results = $qb->execute();
        $this->assertCount(1, $results);
        // Deleting the event deletes its members as well
        $this->register_object($results[0]);
        return $results[0];
    }

    public function test_principals()
    {
        $user = $this->create_user();
        $user->firstname = 'Caldav';
        $user->email = 'caldav-' . $user->id . '@openpsa.test';
        $this->sudo($user->update(...));

        $principals = new org_openpsa_calendar_caldav_principals;
        $principal = $this->get_principal($user);
        $data = $principals->getPrincipalByPath($principal);
        $this->assertEquals($principal, $data['uri']);
        $this->assertEquals($user->email, $data['{http://sabredav.org/ns}email-address']);
        $this->assertStringContainsString('Caldav', $data['{DAV:}displayname']);

        $this->assertNull($principals->getPrincipalByPath('principals/nonexistent-' . uniqid()));
        $this->assertNull($principals->getPrincipalByPath('other/' . $user->lastname));

        $result = $principals->searchPrincipals('principals', ['{http://sabredav.org/ns}email-address' => $user->email]);
        $this->assertEquals([$principal], $result);
        $result = $principals->searchPrincipals('principals', ['{DAV:}displayname' => $user->lastname]);
        $this->assertEquals([$principal], $result);
        $result = $principals->searchPrincipals('principals', ['{DAV:}displayname' => $user->lastname, '{DAV:}unknown' => 'x']);
        $this->assertEquals([], $result);
        $result = $principals->searchPrincipals('principals', ['{DAV:}displayname' => $user->lastname, '{DAV:}unknown' => 'x'], 'anyof');
        $this->assertEquals([$principal], $result);

        $this->assertEquals($principal, $principals->findByUri('mailto:' . $user->email, 'principals'));

        $this->assertContains($principal, array_column($principals->getPrincipalsByPrefix('principals'), 'uri'));
    }

    public function test_calendar()
    {
        $user = $this->create_user(true);
        $other_user = $this->create_user();
        $event1 = $this->create_event($user, gmmktime(10, 0, 0, 1, 10, 2005));
        $event2 = $this->create_event($user, gmmktime(10, 0, 0, 3, 10, 2005), ['externalGuid' => 'external-uid-' . uniqid()]);
        $other_event = $this->create_event($other_user, gmmktime(12, 0, 0, 1, 10, 2005));

        $backend = new org_openpsa_calendar_caldav_backend(new org_openpsa_calendar_caldav_principals, 'Test Calendar');
        $principal = $this->get_principal($user);

        $calendars = $backend->getCalendarsForUser($principal);
        $this->assertCount(1, $calendars);
        $this->assertEquals($user->id, $calendars[0]['id']);
        $this->assertEquals('default', $calendars[0]['uri']);
        $this->assertEquals('Test Calendar', $calendars[0]['{DAV:}displayname']);
        $ctag = $calendars[0]['{http://calendarserver.org/ns/}getctag'];
        $this->assertNotEquals('0', $ctag);
        $this->assertEquals([], $backend->getCalendarsForUser('principals/nonexistent-' . uniqid()));

        $objects = $backend->getCalendarObjects($user->id);
        $this->assertEquals([$this->uri($event1), $this->uri($event2)], array_column($objects, 'uri'));

        $object = $backend->getCalendarObject($user->id, $this->uri($event1));
        $this->assertStringContainsString('UID:' . $event1->guid . '-midgardGuid', $object['calendardata']);
        $this->assertStringNotContainsString('METHOD:', $object['calendardata']);
        $this->assertEquals('"' . md5($object['calendardata']) . '"', $object['etag']);
        $this->assertNull($backend->getCalendarObject($user->id, $this->uri($other_event)));
        $this->assertNull($backend->getCalendarObject($user->id, 'invalid.ics'));

        $multiple = $backend->getMultipleCalendarObjects($user->id, [$this->uri($event2), $this->uri($other_event)]);
        $this->assertEquals([$this->uri($event2)], array_column($multiple, 'uri'));

        $this->assertEquals('default/' . $this->uri($event1), $backend->getCalendarObjectByUID($principal, $event1->guid . '-midgardGuid'));
        $this->assertEquals('default/' . $this->uri($event2), $backend->getCalendarObjectByUID($principal, $event2->externalGuid));
        $this->assertNull($backend->getCalendarObjectByUID($principal, $other_event->guid . '-midgardGuid'));

        // ctag changes when a participant is added
        $this->backdate($user);
        $ctag = $backend->getCalendarsForUser($principal)[0]['{http://calendarserver.org/ns/}getctag'];
        $this->create_object(org_openpsa_calendar_event_member_dba::class, [
            'uid' => $other_user->id,
            'eid' => $event1->id
        ]);
        $calendars = $backend->getCalendarsForUser($principal);
        $this->assertNotEquals($ctag, $calendars[0]['{http://calendarserver.org/ns/}getctag']);
    }

    public function test_calendarQuery()
    {
        $user = $this->create_user(true);
        $event1 = $this->create_event($user, gmmktime(10, 0, 0, 1, 10, 2005));
        $event2 = $this->create_event($user, gmmktime(10, 0, 0, 3, 10, 2005), ['title' => 'Findme']);

        $backend = new org_openpsa_calendar_caldav_backend(new org_openpsa_calendar_caldav_principals, 'Test Calendar');

        $filters = [
            'name' => 'VCALENDAR',
            'comp-filters' => [[
                'name' => 'VEVENT',
                'comp-filters' => [],
                'prop-filters' => [],
                'is-not-defined' => false,
                'time-range' => [
                    'start' => new DateTime('2005-01-01 00:00:00Z'),
                    'end' => new DateTime('2005-02-01 00:00:00Z'),
                ],
            ]],
            'prop-filters' => [],
            'is-not-defined' => false,
            'time-range' => false,
        ];
        $this->assertEquals([$this->uri($event1)], $backend->calendarQuery($user->id, $filters));

        // Overlapping the end of the event
        $filters['comp-filters'][0]['time-range'] = ['start' => new DateTime('2005-01-10 10:30:00Z'), 'end' => null];
        $this->assertEquals([$this->uri($event1), $this->uri($event2)], $backend->calendarQuery($user->id, $filters));

        // Falls back to the validator for property filters
        $filters['comp-filters'][0]['time-range'] = false;
        $filters['comp-filters'][0]['prop-filters'] = [[
            'name' => 'SUMMARY',
            'is-not-defined' => false,
            'param-filters' => [],
            'text-match' => ['negate-condition' => false, 'collation' => 'i;ascii-casemap', 'value' => 'Findme'],
            'time-range' => false,
        ]];
        $this->assertEquals([$this->uri($event2)], $backend->calendarQuery($user->id, $filters));

        // VTODO yields nothing
        $filters['comp-filters'][0] = ['name' => 'VTODO', 'comp-filters' => [], 'prop-filters' => [], 'is-not-defined' => false, 'time-range' => false];
        $this->assertEquals([], $backend->calendarQuery($user->id, $filters));
    }

    public function test_create()
    {
        $user = $this->create_person_with_email(true);
        $other_user = $this->create_person_with_email();
        $uid = 'caldav-create-' . uniqid();
        $backend = $this->get_backend();

        $this->assertNull($this->create_via_caldav($backend, $user, $uid, [
            'SUMMARY:Created',
            'LOCATION:Room 1',
            'DTSTART;TZID=Europe/Berlin:20050110T100000',
            'DTEND;TZID=Europe/Berlin:20050110T110000',
            'ATTENDEE;PARTSTAT=TENTATIVE:mailto:' . strtoupper($other_user->email),
            'ATTENDEE;CN=External:mailto:external@example.com',
            'X-CLIENT-PROP:keep me',
            'BEGIN:VALARM',
            'ACTION:DISPLAY',
            'DESCRIPTION:Reminder',
            'TRIGGER:-PT15M',
            'END:VALARM',
        ]));

        $event = $this->find_created_event($uid);
        $this->assertEquals('Created', $event->title);
        $this->assertEquals('Room 1', $event->location);
        $this->assertEquals($uid . '.ics', $event->caldavUri);
        $this->assertEquals(gmmktime(9, 0, 1, 1, 10, 2005), $event->start);
        $this->assertEquals(gmmktime(10, 0, 0, 1, 10, 2005), $event->end);
        $this->assertEquals([$user->id, $other_user->id], array_keys($event->participants));
        // Participants may change the event (checked in the DB, privileges are cached in-process)
        $qb = new midgard_query_builder('midcom_core_privilege_db');
        $qb->add_constraint('objectguid', '=', $event->guid);
        $qb->add_constraint('assignee', '=', midcom::get()->auth->get_user($other_user->id)->id);
        $qb->add_constraint('privilegename', '=', 'midgard:update');
        $this->assertEquals(1, $qb->count());

        $object = $backend->getCalendarObject($user->id, $uid . '.ics');
        $this->assertEquals($uid . '.ics', $object['uri']);
        $data = $object['calendardata'];
        // Client data survives, times are unchanged
        $this->assertStringContainsString('UID:' . $uid, $data);
        $this->assertStringContainsString('DTSTART;TZID=Europe/Berlin:20050110T100000', $data);
        $this->assertStringContainsString('BEGIN:VALARM', $data);
        $this->assertStringContainsString('X-CLIENT-PROP:keep me', $data);
        // Checked on the parsed data, since long lines get folded
        $attendees = [];
        foreach (Reader::read($data)->VEVENT->ATTENDEE as $attendee) {
            $attendees[strtolower((string) $attendee)] = (string) $attendee['PARTSTAT'];
        }
        $this->assertEquals([
            'mailto:' . strtolower($user->email) => 'ACCEPTED',
            'mailto:' . strtolower($other_user->email) => 'TENTATIVE',
            'mailto:external@example.com' => '',
        ], $attendees);

        // The event shows up in the other participant's calendar under the same URI
        $this->assertNotNull($backend->getCalendarObject($other_user->id, $uid . '.ics'));
        $this->assertEquals('default/' . $uid . '.ics', $backend->getCalendarObjectByUID($this->get_principal($user), $uid));
        $this->assertNull($backend->getCalendarObject($user->id, $this->uri($event)));
    }

    public function test_update()
    {
        $user = $this->create_person_with_email(true);
        $other_user = $this->create_person_with_email();
        $uid = 'caldav-update-' . uniqid();
        $backend = $this->get_backend();
        $times = ['DTSTART;TZID=Europe/Berlin:20050210T100000', 'DTEND;TZID=Europe/Berlin:20050210T110000'];

        $this->create_via_caldav($backend, $user, $uid, [
            'SUMMARY:Before',
            ...$times,
            "ATTENDEE:mailto:{$other_user->email}",
        ]);
        $event = $this->find_created_event($uid);
        $this->assertCount(2, $event->participants);

        // Removing the attendee removes the participant
        $data = $this->ics($uid, ['SUMMARY:After', 'TRANSP:TRANSPARENT', ...$times]);
        $backend->updateCalendarObject($user->id, $uid . '.ics', $data);
        $event = new org_openpsa_calendar_event_dba($event->id);
        $this->assertEquals('After', $event->title);
        $this->assertFalse($event->busy);
        $this->assertEquals([$user->id], array_keys($event->participants));
        $this->assertNull($backend->getCalendarObject($other_user->id, $uid . '.ics'));

        // Changes made in OpenPSA are exported in the client's timezone
        $event->start += 3600;
        $event->end += 3600;
        $this->sudo($event->update(...));
        $data = $backend->getCalendarObject($user->id, $uid . '.ics')['calendardata'];
        $this->assertStringContainsString('DTSTART;TZID=Europe/Berlin:20050210T110000', $data);
        $this->assertStringContainsString('DTEND;TZID=Europe/Berlin:20050210T120000', $data);
        $this->assertStringContainsString('SUMMARY:After', $data);

        $this->expectException(NotFound::class);
        $backend->updateCalendarObject($user->id, 'nonexistent.ics', $this->ics($uid, $times));
    }

    public function test_all_day()
    {
        $user = $this->create_person_with_email(true);
        $uid = 'caldav-allday-' . uniqid();
        $backend = $this->get_backend();
        $this->create_via_caldav($backend, $user, $uid, [
            'SUMMARY:All day',
            'DTSTART;VALUE=DATE:20050310',
            'DTEND;VALUE=DATE:20050311',
        ]);
        $event = $this->find_created_event($uid);
        $this->assertEquals(mktime(0, 0, 1, 3, 10, 2005), $event->start);
        $this->assertEquals(mktime(23, 59, 0, 3, 10, 2005), $event->end);

        $data = $backend->getCalendarObject($user->id, $uid . '.ics')['calendardata'];
        $this->assertStringContainsString('DTSTART;VALUE=DATE:20050310', $data);

        // Extended by a day in OpenPSA
        $event->end += 86400;
        $this->sudo($event->update(...));
        $data = $backend->getCalendarObject($user->id, $uid . '.ics')['calendardata'];
        $this->assertStringContainsString('DTSTART;VALUE=DATE:20050310', $data);
        $this->assertStringContainsString('DTEND;VALUE=DATE:20050312', $data);
    }

    public function test_recurrence()
    {
        $user = $this->create_person_with_email(true);
        $uid = 'caldav-rrule-' . uniqid();
        $backend = $this->get_backend();
        $this->create_via_caldav($backend, $user, $uid, [
            'SUMMARY:Weekly',
            'DTSTART:20050407T100000Z',
            'DTEND:20050407T110000Z',
            'RRULE:FREQ=WEEKLY;COUNT=10',
        ], [
            'BEGIN:VEVENT',
            'UID:' . $uid,
            'DTSTAMP:20050101T000000Z',
            'RECURRENCE-ID:20050414T100000Z',
            'SUMMARY:Moved',
            'DTSTART:20050414T140000Z',
            'DTEND:20050414T150000Z',
            'END:VEVENT',
        ]);

        // The master is the first occurrence, the others are its children
        $event = $this->find_created_event($uid);
        $this->assertTrue($event->is_master());
        $this->assertEquals('Weekly', $event->title);
        $this->assertEquals(gmmktime(10, 0, 1, 4, 7, 2005), $event->start);
        $this->assertEquals('RRULE:FREQ=WEEKLY;COUNT=10', $event->recurrence);
        $this->assertEquals('UTC', $event->timezone);
        $occurrences = $this->get_occurrences($event);
        $this->assertCount(9, $occurrences);
        $this->assertEquals('Moved', $occurrences[0]->title);
        $this->assertTrue($occurrences[0]->recurrenceDetached);
        $this->assertEquals(gmmktime(14, 0, 1, 4, 14, 2005), $occurrences[0]->start);
        $this->assertEquals('Weekly', $occurrences[1]->title);

        // One calendar object
        $this->assertCount(1, $backend->getCalendarObjects($user->id));
        $data = $backend->getCalendarObject($user->id, $uid . '.ics')['calendardata'];
        $this->assertEquals(2, substr_count($data, 'BEGIN:VEVENT'));
        $this->assertEquals(1, substr_count($data, 'RRULE:FREQ=WEEKLY;COUNT=10'));
        $this->assertStringContainsString('RECURRENCE-ID:20050414T100000Z', $data);
        $this->assertStringContainsString('SUMMARY:Moved', $data);

        // Later occurrences are found by time-range queries
        $filters = $this->timerange_filter('2005-05-01 00:00:00Z', '2005-05-08 00:00:00Z');
        $this->assertEquals([$uid . '.ics'], $backend->calendarQuery($user->id, $filters));
        $filters = $this->timerange_filter('2006-01-01 00:00:00Z', '2006-01-08 00:00:00Z');
        $this->assertEquals([], $backend->calendarQuery($user->id, $filters));
    }

    private function timerange_filter(string $start, string $end) : array
    {
        return [
            'name' => 'VCALENDAR',
            'comp-filters' => [[
                'name' => 'VEVENT',
                'comp-filters' => [],
                'prop-filters' => [],
                'is-not-defined' => false,
                'time-range' => ['start' => new DateTime($start), 'end' => new DateTime($end)]
            ]],
            'prop-filters' => [],
            'is-not-defined' => false,
            'time-range' => false
        ];
    }

    /**
     * @return org_openpsa_calendar_event_dba[]
     */
    private function get_occurrences(org_openpsa_calendar_event_dba $master) : array
    {
        $qb = org_openpsa_calendar_event_dba::new_query_builder();
        $qb->add_constraint('up', '=', $master->id);
        $qb->add_order('start');
        return $this->sudo($qb->execute(...));
    }

    private function get_member_ids(org_openpsa_calendar_event_dba $event) : array
    {
        $mc = org_openpsa_calendar_event_member_dba::new_collector('eid', $event->id);
        $ids = $this->sudo(fn() => $mc->get_values('uid'));
        sort($ids);
        return $ids;
    }

    /**
     * Local date (Ymd) of a week day after next week, in the timezone used by the recurrence tests
     */
    private function week(int $offset, string $day = 'monday') : string
    {
        return (new DateTimeImmutable($day . ' next week +' . $offset . ' weeks', new DateTimeZone('Europe/Berlin')))
            ->format('Ymd');
    }

    private function utc(string $date, string $time = '100000') : string
    {
        return gmdate('Ymd\THis\Z', $this->local($date, $time));
    }

    /**
     * Timestamp of a date and time in Europe/Berlin
     */
    private function local(string $date, string $time = '100000') : int
    {
        return (new DateTimeImmutable($date . 'T' . $time, new DateTimeZone('Europe/Berlin')))->getTimestamp();
    }

    public function test_recurrence_round_trip()
    {
        $user = $this->create_person_with_email(true);
        $other = $this->create_person_with_email();
        $uid = 'caldav-roundtrip-' . uniqid();
        $backend = $this->get_backend();
        $master_lines = [
            'SUMMARY:Series',
            'DTSTART;TZID=Europe/Berlin:' . $this->week(0) . 'T100000',
            'DTEND;TZID=Europe/Berlin:' . $this->week(0) . 'T110000',
            'RRULE:FREQ=WEEKLY;COUNT=4',
            'EXDATE;TZID=Europe/Berlin:' . $this->week(2) . 'T100000',
            "ATTENDEE:mailto:{$other->email}",
            'BEGIN:VALARM',
            'ACTION:DISPLAY',
            'TRIGGER:-PT15M',
            'END:VALARM'
        ];
        // The other participant doesn't attend the second date
        $override = [
            'BEGIN:VEVENT',
            'UID:' . $uid,
            'DTSTAMP:20050101T000000Z',
            'RECURRENCE-ID;TZID=Europe/Berlin:' . $this->week(1) . 'T100000',
            'SUMMARY:Special',
            'DTSTART;TZID=Europe/Berlin:' . $this->week(1) . 'T100000',
            'DTEND;TZID=Europe/Berlin:' . $this->week(1) . 'T110000',
            "ATTENDEE;PARTSTAT=DECLINED:mailto:{$other->email}",
            'X-CLIENT-PROP:kept',
            'END:VEVENT'
        ];
        $this->create_via_caldav($backend, $user, $uid, $master_lines, $override);

        $master = $this->find_created_event($uid);
        $this->assertEquals('Europe/Berlin', $master->timezone);
        $this->assertStringContainsString('EXDATE;TZID=Europe/Berlin:' . $this->week(2) . 'T100000', $master->recurrence);
        $occurrences = $this->get_occurrences($master);
        $this->assertCount(2, $occurrences);
        $both = [$user->id, $other->id];
        sort($both);
        $this->assertEquals('Special', $occurrences[0]->title);
        $this->assertTrue($occurrences[0]->recurrenceDetached);
        $this->assertEquals([$user->id], $this->get_member_ids($occurrences[0]));
        $this->assertEquals('Series', $occurrences[1]->title);
        $this->assertEquals($both, $this->get_member_ids($occurrences[1]));

        $data = $backend->getCalendarObject($user->id, $uid . '.ics')['calendardata'];
        $this->assertEquals(2, substr_count($data, 'BEGIN:VEVENT'));
        $this->assertEquals(1, substr_count($data, 'RRULE:'));
        $this->assertStringContainsString('EXDATE:' . $this->utc($this->week(2)), $data);
        $this->assertStringContainsString('RECURRENCE-ID;TZID=Europe/Berlin:' . $this->week(1) . 'T100000', $data);
        $this->assertStringContainsString('X-CLIENT-PROP:kept', $data);
        $this->assertStringContainsString('BEGIN:VALARM', $data);
        $vcalendar = Reader::read($data);
        foreach ($vcalendar->select('VEVENT') as $vevent) {
            if (isset($vevent->{'RECURRENCE-ID'})) {
                $this->assertEquals('Special', (string) $vevent->SUMMARY);
                $partstats = [];
                foreach ($vevent->select('ATTENDEE') as $attendee) {
                    $partstats[strtolower(substr((string) $attendee, 7))] = (string) $attendee['PARTSTAT'];
                }
                $this->assertEquals('DECLINED', $partstats[strtolower($other->email)]);
                $this->assertEquals('ACCEPTED', $partstats[strtolower($user->email)]);
            }
        }

        // The client drops the override: the occurrence returns to the series
        $backend->updateCalendarObject($user->id, $uid . '.ics', $this->ics($uid, $master_lines));
        $occurrences = $this->get_occurrences($master);
        $this->assertCount(2, $occurrences);
        $this->assertFalse($occurrences[0]->recurrenceDetached);
        $this->assertEquals('Series', $occurrences[0]->title);
        $this->assertEquals($both, $this->get_member_ids($occurrences[0]));
        $data = $backend->getCalendarObject($user->id, $uid . '.ics')['calendardata'];
        $this->assertEquals(1, substr_count($data, 'BEGIN:VEVENT'));
    }

    public function test_recurrence_conflict()
    {
        $user = $this->create_person_with_email(true);
        $other = $this->create_person_with_email();
        $busy_start = $this->local($this->week(1), '103000');
        $this->create_event($other, $busy_start, ['busy' => true]);
        $uid = 'caldav-series-conflict-' . uniqid();
        $backend = $this->get_backend();

        // No 409 for a series, the busy participant is left out of that date
        $this->create_via_caldav($backend, $user, $uid, [
            'SUMMARY:Series',
            'DTSTART;TZID=Europe/Berlin:' . $this->week(0) . 'T100000',
            'DTEND;TZID=Europe/Berlin:' . $this->week(0) . 'T110000',
            'RRULE:FREQ=WEEKLY;COUNT=3',
            "ATTENDEE:mailto:{$other->email}"
        ]);
        $master = $this->find_created_event($uid);
        $occurrences = $this->get_occurrences($master);
        $this->assertCount(2, $occurrences);
        $this->assertTrue($occurrences[0]->recurrenceDetached);
        $this->assertEquals([$user->id], $this->get_member_ids($occurrences[0]));

        // ... which clients see as declined
        $data = $backend->getCalendarObject($user->id, $uid . '.ics')['calendardata'];
        $this->assertEquals(2, substr_count($data, 'BEGIN:VEVENT'));
        $declined = '/ATTENDEE;[^\r\n]*PARTSTAT=DECLINED[^\r\n]*:mailto:' . preg_quote($other->email, '/') . '/i';
        $this->assertMatchesRegularExpression($declined, str_replace("\r\n ", '', $data));

        // A client sending it back doesn't change anything
        $backend->updateCalendarObject($user->id, $uid . '.ics', $data);
        $occurrences = $this->get_occurrences($master);
        $this->assertCount(2, $occurrences);
        $this->assertEquals([$user->id], $this->get_member_ids($occurrences[0]));
    }

    public function test_recurrence_first_occurrence()
    {
        $user = $this->create_person_with_email(true);
        $uid = 'caldav-first-' . uniqid();
        $backend = $this->get_backend();
        $this->create_via_caldav($backend, $user, $uid, [
            'SUMMARY:Series',
            'DTSTART;TZID=Europe/Berlin:' . $this->week(0) . 'T100000',
            'DTEND;TZID=Europe/Berlin:' . $this->week(0) . 'T110000',
            'RRULE:FREQ=WEEKLY;COUNT=3'
        ], [
            'BEGIN:VEVENT',
            'UID:' . $uid,
            'DTSTAMP:20050101T000000Z',
            'RECURRENCE-ID;TZID=Europe/Berlin:' . $this->week(0) . 'T100000',
            'SUMMARY:First',
            'DTSTART;TZID=Europe/Berlin:' . $this->week(0) . 'T140000',
            'DTEND;TZID=Europe/Berlin:' . $this->week(0) . 'T160000',
            'END:VEVENT'
        ]);

        // The master row holds the first occurrence, the series keeps its own values
        $master = $this->find_created_event($uid);
        $anchor = $this->local($this->week(0)) + 1;
        $this->assertTrue($master->recurrenceDetached);
        $this->assertEquals('First', $master->title);
        $this->assertEquals($anchor + 4 * 3600, $master->start);
        $this->assertEquals($anchor, $master->recurrenceId);
        $occurrences = $this->get_occurrences($master);
        $this->assertCount(2, $occurrences);
        $this->assertEquals('Series', $occurrences[0]->title);
        $this->assertEquals(3600 - 1, $occurrences[0]->end - $occurrences[0]->start);
        $this->assertEquals($this->local($this->week(1)) + 1, $occurrences[0]->start);

        $vcalendar = Reader::read($backend->getCalendarObject($user->id, $uid . '.ics')['calendardata']);
        $vevents = $vcalendar->select('VEVENT');
        $this->assertCount(2, $vevents);
        foreach ($vevents as $vevent) {
            if (isset($vevent->{'RECURRENCE-ID'})) {
                $this->assertEquals('First', (string) $vevent->SUMMARY);
                $this->assertEquals($anchor + 4 * 3600 - 1, $vevent->DTSTART->getDateTime()->getTimestamp());
            } else {
                $this->assertEquals('Series', (string) $vevent->SUMMARY);
                $this->assertEquals($anchor - 1, $vevent->DTSTART->getDateTime()->getTimestamp());
                $this->assertEquals($anchor - 1 + 3600, $vevent->DTEND->getDateTime()->getTimestamp());
            }
        }
    }

    public function test_recurrence_removed()
    {
        $user = $this->create_person_with_email(true);
        $uid = 'caldav-norule-' . uniqid();
        $backend = $this->get_backend();
        $lines = [
            'SUMMARY:Series',
            'DTSTART;TZID=Europe/Berlin:' . $this->week(0) . 'T100000',
            'DTEND;TZID=Europe/Berlin:' . $this->week(0) . 'T110000'
        ];
        $this->create_via_caldav($backend, $user, $uid, [...$lines, 'RRULE:FREQ=WEEKLY;COUNT=3']);
        $master = $this->find_created_event($uid);
        $this->assertCount(2, $this->get_occurrences($master));

        $backend->updateCalendarObject($user->id, $uid . '.ics', $this->ics($uid, $lines));
        $event = $this->find_created_event($uid);
        $this->assertFalse($event->is_master());
        $this->assertCount(0, $this->get_occurrences($event));
        $data = $backend->getCalendarObject($user->id, $uid . '.ics')['calendardata'];
        $this->assertStringNotContainsString('RRULE', $data);
    }

    public function test_recurrence_leave()
    {
        $user = $this->create_person_with_email(true);
        $other = $this->create_person_with_email();
        $uid = 'caldav-leave-series-' . uniqid();
        $backend = $this->get_backend();
        $this->create_via_caldav($backend, $user, $uid, [
            'SUMMARY:Series',
            'DTSTART;TZID=Europe/Berlin:' . $this->week(0) . 'T100000',
            'DTEND;TZID=Europe/Berlin:' . $this->week(0) . 'T110000',
            'RRULE:FREQ=WEEKLY;COUNT=3',
            "ATTENDEE:mailto:{$other->email}"
        ]);
        $master = $this->find_created_event($uid);

        midcom::get()->auth->logout();
        midcom::get()->auth->login($other->lastname, $other->extra);
        $backend->deleteCalendarObject($other->id, $uid . '.ics');

        $this->assertEquals([$user->id], $this->get_member_ids($master));
        foreach ($this->get_occurrences($master) as $occurrence) {
            $this->assertEquals([$user->id], $this->get_member_ids($occurrence));
        }
    }

    public function test_sync_recurrence()
    {
        $user = $this->create_person_with_email(true);
        $other = $this->create_person_with_email();
        $uid = 'caldav-sync-series-' . uniqid();
        $backend = $this->get_backend();
        $this->create_via_caldav($backend, $user, $uid, [
            'SUMMARY:Series',
            'DTSTART;TZID=Europe/Berlin:' . $this->week(0) . 'T100000',
            'DTEND;TZID=Europe/Berlin:' . $this->week(0) . 'T110000',
            'RRULE:FREQ=WEEKLY;COUNT=3'
        ]);
        $master = $this->find_created_event($uid);
        $occurrence = $this->get_occurrences($master)[1];

        $this->backdate($user);
        $token = $backend->getCalendarsForUser($this->get_principal($user))[0]['{http://sabredav.org/ns}sync-token'];

        // A member row on an occurrence changes the series object
        $this->create_object(org_openpsa_calendar_event_member_dba::class, [
            'uid' => $other->id,
            'eid' => $occurrence->id
        ]);
        $result = $backend->getChangesForCalendar($user->id, $token, 1);
        $this->assertEquals([$uid . '.ics'], $result['modified']);
        $this->assertEquals([], $result['deleted']);
        $this->assertGreaterThan($token, $result['syncToken']);
    }

    public function test_conflict()
    {
        $user = $this->create_person_with_email(true);
        $this->create_event($user, gmmktime(10, 0, 0, 6, 10, 2005), ['busy' => true]);
        $uid = 'caldav-conflict-' . uniqid();

        $this->expectException(Conflict::class);
        $this->create_via_caldav($this->get_backend(), $user, $uid, [
            'SUMMARY:Overlap',
            'DTSTART:20050610T103000Z',
            'DTEND:20050610T113000Z',
        ]);
    }

    public function test_notifications()
    {
        $user = $this->create_person_with_email(true);
        $other_user = $this->create_person_with_email();
        org_openpsa_mail_backend_unittest::flush();

        $attendee = "ATTENDEE:mailto:{$other_user->email}";

        $uid = 'caldav-quiet-' . uniqid();
        $this->create_via_caldav($this->get_backend(false), $user, $uid, [
            'SUMMARY:Quiet',
            'DTSTART:20050710T100000Z',
            'DTEND:20050710T110000Z',
            $attendee,
        ]);
        $this->find_created_event($uid);
        $this->assertCount(0, org_openpsa_mail_backend_unittest::$mails);

        $uid = 'caldav-loud-' . uniqid();
        $lines = [
            'SUMMARY:Loud',
            'DTSTART:20050711T100000Z',
            'DTEND:20050711T110000Z',
            $attendee,
        ];
        $backend = $this->get_backend(true);
        $this->create_via_caldav($backend, $user, $uid, $lines);
        $this->find_created_event($uid);
        $this->assertCount(1, org_openpsa_mail_backend_unittest::flush());

        // Clients send unchanged events again (e.g. when an alarm was changed)
        $backend->updateCalendarObject($user->id, $uid . '.ics', $this->ics($uid, [...$lines, 'BEGIN:VALARM', 'TRIGGER:-PT15M', 'ACTION:DISPLAY', 'END:VALARM']));
        $this->assertCount(0, org_openpsa_mail_backend_unittest::flush());

        $lines[0] = 'SUMMARY:Louder';
        $backend->updateCalendarObject($user->id, $uid . '.ics', $this->ics($uid, $lines));
        $this->assertCount(1, org_openpsa_mail_backend_unittest::flush());
    }

    public function test_delete()
    {
        $other_user = $this->create_person_with_email(true);
        $user = $this->create_person_with_email();
        $backend = $this->get_backend();

        // Created by someone else: only the user's membership is removed
        $uid = 'caldav-delete-' . uniqid();
        $this->create_via_caldav($backend, $other_user, $uid, [
            'SUMMARY:Shared',
            'DTSTART:20050810T100000Z',
            'DTEND:20050810T110000Z',
            "ATTENDEE:mailto:{$user->email}",
        ]);
        $shared = $this->find_created_event($uid);

        midcom::get()->auth->logout();
        midcom::get()->auth->login($user->lastname, $user->extra);
        $backend->deleteCalendarObject($user->id, $uid . '.ics');
        $shared = new org_openpsa_calendar_event_dba($shared->id);
        $this->assertEquals([$other_user->id], array_keys($shared->participants));
        $this->assertNull($backend->getCalendarObject($user->id, $uid . '.ics'));

        // Only participant: the event is deleted
        $uid = 'caldav-delete-own-' . uniqid();
        $this->create_via_caldav($backend, $user, $uid, [
            'SUMMARY:Own',
            'DTSTART:20050811T100000Z',
            'DTEND:20050811T110000Z',
        ]);
        $own = $this->find_created_event($uid);
        $backend->deleteCalendarObject($user->id, $uid . '.ics');
        $qb = org_openpsa_calendar_event_dba::new_query_builder();
        $qb->add_constraint('id', '=', $own->id);
        $this->assertEquals(0, $qb->count());

        // Creator with other participants: the event is deleted
        $uid = 'caldav-delete-creator-' . uniqid();
        $this->create_via_caldav($backend, $user, $uid, [
            'SUMMARY:Mine',
            'DTSTART:20050812T100000Z',
            'DTEND:20050812T110000Z',
            "ATTENDEE:mailto:{$other_user->email}",
        ]);
        $mine = $this->find_created_event($uid);
        $backend->deleteCalendarObject($user->id, $uid . '.ics');
        $qb = org_openpsa_calendar_event_dba::new_query_builder();
        $qb->add_constraint('id', '=', $mine->id);
        $this->assertEquals(0, $qb->count());
        $this->assertNull($backend->getCalendarObject($other_user->id, $uid . '.ics'));
    }

    public function test_sync()
    {
        $user = $this->create_user(true);
        $other_user = $this->create_user();
        $backend = $this->get_backend();
        $principal = $this->get_principal($user);
        $uri = $this->uri(...);

        $unchanged  = $this->create_event($user,       gmmktime(10, 0, 0, 9, 1, 2005));
        $updated    = $this->create_event($user,       gmmktime(10, 0, 0, 9, 2, 2005));
        $joined     = $this->create_event($user,       gmmktime(10, 0, 0, 9, 3, 2005));
        $left       = $this->create_event($user,       gmmktime(10, 0, 0, 9, 4, 2005));
        $deleted    = $this->create_event($user,       gmmktime(10, 0, 0, 9, 5, 2005));
        $foreign    = $this->create_event($other_user, gmmktime(10, 0, 0, 9, 6, 2005));

        // Initial sync
        $result = $backend->getChangesForCalendar($user->id, null, 1);
        $this->assertEqualsCanonicalizing(array_map($uri, [$unchanged, $updated, $joined, $left, $deleted]), $result['added']);
        $this->assertEquals([], $result['modified']);
        $this->assertEquals([], $result['deleted']);

        // Tokens are never the current second, so move the setup out of it
        $this->backdate($user);
        $calendar = $backend->getCalendarsForUser($principal)[0];
        $token = $calendar['{http://sabredav.org/ns}sync-token'];
        $this->assertEquals($token, $calendar['{http://calendarserver.org/ns/}getctag']);
        $this->assertEquals(['syncToken' => $token, 'added' => [], 'modified' => [], 'deleted' => []], $backend->getChangesForCalendar($user->id, $token, 1));

        // Invalid, future and expired tokens
        $this->assertNull($backend->getChangesForCalendar($user->id, 'invalid', 1));
        $this->assertNull($backend->getChangesForCalendar($user->id, (string) ($token + 1000), 1));
        $this->assertNull($backend->getChangesForCalendar($user->id, '1', 1));

        $this->sudo(function () use ($updated, $joined, $left, $deleted, $other_user, $user, $foreign) {
            $updated->title = 'Changed';
            $this->assertTrue($updated->update());
            // Another participant shows up as attendee, so the event changes
            $this->create_object(org_openpsa_calendar_event_member_dba::class, ['uid' => $other_user->id, 'eid' => $joined->id]);
            $qb = org_openpsa_calendar_event_member_dba::new_query_builder();
            $qb->add_constraint('eid', '=', $left->id);
            $qb->add_constraint('uid', '=', $user->id);
            $this->assertTrue($qb->get_result(0)->delete());
            $this->assertTrue($deleted->delete());
            $foreign->title = 'Not mine';
            $this->assertTrue($foreign->update());
        });
        $added = $this->create_event($user, gmmktime(10, 0, 0, 9, 7, 2005));

        $result = $backend->getChangesForCalendar($user->id, $token, 1);
        $this->assertEqualsCanonicalizing(array_map($uri, [$updated, $joined, $added]), $result['modified']);
        $this->assertEqualsCanonicalizing(array_map($uri, [$left, $deleted]), $result['deleted']);
        $this->assertEquals([], $result['added']);
        $this->assertGreaterThan($token, $result['syncToken']);
        $this->assertLessThan(time(), (int) $result['syncToken']);
    }
}
