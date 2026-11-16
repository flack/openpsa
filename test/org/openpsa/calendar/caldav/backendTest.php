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
use Sabre\VObject\Reader;
use midgard_query_builder;

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class backendTest extends openpsa_testcase
{
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
        return new org_openpsa_calendar_event_dba($event->id);
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

    private function ics(string $uid, string $properties, string $extra_components = '') : string
    {
        return "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//Test//Client//EN\r\n"
            . "BEGIN:VEVENT\r\nUID:{$uid}\r\nDTSTAMP:20050101T000000Z\r\n{$properties}END:VEVENT\r\n"
            . $extra_components
            . "END:VCALENDAR\r\n";
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
        $principal = $principals->getPrincipalByPath($this->get_principal($user));
        $this->assertEquals($this->get_principal($user), $principal['uri']);
        $this->assertEquals($user->email, $principal['{http://sabredav.org/ns}email-address']);
        $this->assertStringContainsString('Caldav', $principal['{DAV:}displayname']);

        $this->assertNull($principals->getPrincipalByPath('principals/nonexistent-' . uniqid()));
        $this->assertNull($principals->getPrincipalByPath('other/' . $user->lastname));

        $result = $principals->searchPrincipals('principals', ['{http://sabredav.org/ns}email-address' => $user->email]);
        $this->assertEquals([$this->get_principal($user)], $result);
        $result = $principals->searchPrincipals('principals', ['{DAV:}displayname' => $user->lastname]);
        $this->assertEquals([$this->get_principal($user)], $result);
        $result = $principals->searchPrincipals('principals', ['{DAV:}displayname' => $user->lastname, '{DAV:}unknown' => 'x']);
        $this->assertEquals([], $result);
        $result = $principals->searchPrincipals('principals', ['{DAV:}displayname' => $user->lastname, '{DAV:}unknown' => 'x'], 'anyof');
        $this->assertEquals([$this->get_principal($user)], $result);

        $this->assertEquals($this->get_principal($user), $principals->findByUri('mailto:' . $user->email, 'principals'));

        $this->assertContains($this->get_principal($user), array_column($principals->getPrincipalsByPrefix('principals'), 'uri'));
    }

    public function test_calendar()
    {
        $user = $this->create_user(true);
        $other_user = $this->create_user();
        $event1 = $this->create_event($user, gmmktime(10, 0, 0, 1, 10, 2005));
        $event2 = $this->create_event($user, gmmktime(10, 0, 0, 3, 10, 2005), ['externalGuid' => 'external-uid-' . uniqid()]);
        $other_event = $this->create_event($other_user, gmmktime(12, 0, 0, 1, 10, 2005));

        $backend = new org_openpsa_calendar_caldav_backend(new org_openpsa_calendar_caldav_principals, 'Test Calendar');

        $calendars = $backend->getCalendarsForUser($this->get_principal($user));
        $this->assertCount(1, $calendars);
        $this->assertEquals($user->id, $calendars[0]['id']);
        $this->assertEquals('default', $calendars[0]['uri']);
        $this->assertEquals('Test Calendar', $calendars[0]['{DAV:}displayname']);
        $ctag = $calendars[0]['{http://calendarserver.org/ns/}getctag'];
        $this->assertNotEquals('0', $ctag);
        $this->assertEquals([], $backend->getCalendarsForUser('principals/nonexistent-' . uniqid()));

        $objects = $backend->getCalendarObjects($user->id);
        $this->assertEquals([$event1->guid . '.ics', $event2->guid . '.ics'], array_column($objects, 'uri'));

        $object = $backend->getCalendarObject($user->id, $event1->guid . '.ics');
        $this->assertStringContainsString('UID:' . $event1->guid . '-midgardGuid', $object['calendardata']);
        $this->assertStringNotContainsString('METHOD:', $object['calendardata']);
        $this->assertEquals('"' . md5($object['calendardata']) . '"', $object['etag']);
        $this->assertNull($backend->getCalendarObject($user->id, $other_event->guid . '.ics'));
        $this->assertNull($backend->getCalendarObject($user->id, 'invalid.ics'));

        $multiple = $backend->getMultipleCalendarObjects($user->id, [$event2->guid . '.ics', $other_event->guid . '.ics']);
        $this->assertEquals([$event2->guid . '.ics'], array_column($multiple, 'uri'));

        $this->assertEquals('default/' . $event1->guid . '.ics', $backend->getCalendarObjectByUID($this->get_principal($user), $event1->guid . '-midgardGuid'));
        $this->assertEquals('default/' . $event2->guid . '.ics', $backend->getCalendarObjectByUID($this->get_principal($user), $event2->externalGuid));
        $this->assertNull($backend->getCalendarObjectByUID($this->get_principal($user), $other_event->guid . '-midgardGuid'));

        // ctag changes when a participant is added
        sleep(1);
        $this->create_object(org_openpsa_calendar_event_member_dba::class, [
            'uid' => $other_user->id,
            'eid' => $event1->id
        ]);
        $calendars = $backend->getCalendarsForUser($this->get_principal($user));
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
        $this->assertEquals([$event1->guid . '.ics'], $backend->calendarQuery($user->id, $filters));

        // Overlapping the end of the event
        $filters['comp-filters'][0]['time-range'] = ['start' => new DateTime('2005-01-10 10:30:00Z'), 'end' => null];
        $this->assertEquals([$event1->guid . '.ics', $event2->guid . '.ics'], $backend->calendarQuery($user->id, $filters));

        // Falls back to the validator for property filters
        $filters['comp-filters'][0]['time-range'] = false;
        $filters['comp-filters'][0]['prop-filters'] = [[
            'name' => 'SUMMARY',
            'is-not-defined' => false,
            'param-filters' => [],
            'text-match' => ['negate-condition' => false, 'collation' => 'i;ascii-casemap', 'value' => 'Findme'],
            'time-range' => false,
        ]];
        $this->assertEquals([$event2->guid . '.ics'], $backend->calendarQuery($user->id, $filters));

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

        $properties = "SUMMARY:Created\r\nLOCATION:Room 1\r\n"
            . "DTSTART;TZID=Europe/Berlin:20050110T100000\r\nDTEND;TZID=Europe/Berlin:20050110T110000\r\n"
            . "ATTENDEE;PARTSTAT=TENTATIVE:mailto:" . strtoupper($other_user->email) . "\r\n"
            . "ATTENDEE;CN=External:mailto:external@example.com\r\n"
            . "X-CLIENT-PROP:keep me\r\n"
            . "BEGIN:VALARM\r\nACTION:DISPLAY\r\nDESCRIPTION:Reminder\r\nTRIGGER:-PT15M\r\nEND:VALARM\r\n";
        $this->assertNull($backend->createCalendarObject($user->id, $uid . '.ics', $this->ics($uid, $properties)));

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
        $this->assertNull($backend->getCalendarObject($user->id, $event->guid . '.ics'));
    }

    public function test_update()
    {
        $user = $this->create_person_with_email(true);
        $other_user = $this->create_person_with_email();
        $uid = 'caldav-update-' . uniqid();
        $backend = $this->get_backend();
        $times = "DTSTART;TZID=Europe/Berlin:20050210T100000\r\nDTEND;TZID=Europe/Berlin:20050210T110000\r\n";

        $backend->createCalendarObject($user->id, $uid . '.ics', $this->ics($uid, "SUMMARY:Before\r\n{$times}ATTENDEE:mailto:{$other_user->email}\r\n"));
        $event = $this->find_created_event($uid);
        $this->assertCount(2, $event->participants);

        // Removing the attendee removes the participant
        $backend->updateCalendarObject($user->id, $uid . '.ics', $this->ics($uid, "SUMMARY:After\r\nTRANSP:TRANSPARENT\r\n{$times}"));
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
        $backend->createCalendarObject($user->id, $uid . '.ics', $this->ics($uid, "SUMMARY:All day\r\nDTSTART;VALUE=DATE:20050310\r\nDTEND;VALUE=DATE:20050311\r\n"));
        $event = $this->find_created_event($uid);
        $this->assertEquals(mktime(0, 0, 1, 3, 10, 2005), $event->start);
        $this->assertEquals(mktime(23, 59, 0, 3, 10, 2005), $event->end);

        $this->assertStringContainsString('DTSTART;VALUE=DATE:20050310', $backend->getCalendarObject($user->id, $uid . '.ics')['calendardata']);

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
        $exception = "BEGIN:VEVENT\r\nUID:{$uid}\r\nDTSTAMP:20050101T000000Z\r\nRECURRENCE-ID:20050414T100000Z\r\n"
            . "SUMMARY:Moved\r\nDTSTART:20050414T140000Z\r\nDTEND:20050414T150000Z\r\nEND:VEVENT\r\n";
        $backend->createCalendarObject($user->id, $uid . '.ics', $this->ics($uid, "SUMMARY:Weekly\r\nDTSTART:20050407T100000Z\r\nDTEND:20050407T110000Z\r\nRRULE:FREQ=WEEKLY;COUNT=10\r\n", $exception));

        // The first occurrence is stored
        $event = $this->find_created_event($uid);
        $this->assertEquals('Weekly', $event->title);
        $this->assertEquals(gmmktime(10, 0, 1, 4, 7, 2005), $event->start);

        $data = $backend->getCalendarObject($user->id, $uid . '.ics')['calendardata'];
        $this->assertStringContainsString('RRULE:FREQ=WEEKLY;COUNT=10', $data);
        $this->assertStringContainsString('RECURRENCE-ID:20050414T100000Z', $data);
        $this->assertStringContainsString('SUMMARY:Moved', $data);

        // Later occurrences are found by time-range queries
        $filters = [
            'name' => 'VCALENDAR',
            'comp-filters' => [[
                'name' => 'VEVENT',
                'comp-filters' => [],
                'prop-filters' => [],
                'is-not-defined' => false,
                'time-range' => ['start' => new DateTime('2005-05-01 00:00:00Z'), 'end' => new DateTime('2005-05-08 00:00:00Z')],
            ]],
            'prop-filters' => [],
            'is-not-defined' => false,
            'time-range' => false,
        ];
        $this->assertEquals([$uid . '.ics'], $backend->calendarQuery($user->id, $filters));
        $filters['comp-filters'][0]['time-range'] = ['start' => new DateTime('2006-01-01 00:00:00Z'), 'end' => new DateTime('2006-01-08 00:00:00Z')];
        $this->assertEquals([], $backend->calendarQuery($user->id, $filters));
    }

    public function test_conflict()
    {
        $user = $this->create_person_with_email(true);
        $this->create_event($user, gmmktime(10, 0, 0, 6, 10, 2005), ['busy' => true]);
        $uid = 'caldav-conflict-' . uniqid();

        $this->expectException(Conflict::class);
        $this->get_backend()->createCalendarObject($user->id, $uid . '.ics', $this->ics($uid, "SUMMARY:Overlap\r\nDTSTART:20050610T103000Z\r\nDTEND:20050610T113000Z\r\n"));
    }

    public function test_notifications()
    {
        $user = $this->create_person_with_email(true);
        $other_user = $this->create_person_with_email();
        org_openpsa_mail_backend_unittest::flush();

        $uid = 'caldav-quiet-' . uniqid();
        $this->get_backend(false)->createCalendarObject($user->id, $uid . '.ics', $this->ics($uid, "SUMMARY:Quiet\r\nDTSTART:20050710T100000Z\r\nDTEND:20050710T110000Z\r\nATTENDEE:mailto:{$other_user->email}\r\n"));
        $this->find_created_event($uid);
        $this->assertCount(0, org_openpsa_mail_backend_unittest::$mails);

        $uid = 'caldav-loud-' . uniqid();
        $this->get_backend(true)->createCalendarObject($user->id, $uid . '.ics', $this->ics($uid, "SUMMARY:Loud\r\nDTSTART:20050711T100000Z\r\nDTEND:20050711T110000Z\r\nATTENDEE:mailto:{$other_user->email}\r\n"));
        $this->find_created_event($uid);
        $this->assertCount(1, org_openpsa_mail_backend_unittest::$mails);
    }

    public function test_delete()
    {
        $other_user = $this->create_person_with_email(true);
        $user = $this->create_person_with_email();
        $backend = $this->get_backend();

        // Created by someone else: only the user's membership is removed
        $uid = 'caldav-delete-' . uniqid();
        $backend->createCalendarObject($other_user->id, $uid . '.ics', $this->ics($uid, "SUMMARY:Shared\r\nDTSTART:20050810T100000Z\r\nDTEND:20050810T110000Z\r\nATTENDEE:mailto:{$user->email}\r\n"));
        $shared = $this->find_created_event($uid);

        midcom::get()->auth->logout();
        midcom::get()->auth->login($user->lastname, $user->extra);
        $backend->deleteCalendarObject($user->id, $uid . '.ics');
        $shared = new org_openpsa_calendar_event_dba($shared->id);
        $this->assertEquals([$other_user->id], array_keys($shared->participants));
        $this->assertNull($backend->getCalendarObject($user->id, $uid . '.ics'));

        // Only participant: the event is deleted
        $uid = 'caldav-delete-own-' . uniqid();
        $backend->createCalendarObject($user->id, $uid . '.ics', $this->ics($uid, "SUMMARY:Own\r\nDTSTART:20050811T100000Z\r\nDTEND:20050811T110000Z\r\n"));
        $own = $this->find_created_event($uid);
        $backend->deleteCalendarObject($user->id, $uid . '.ics');
        $qb = org_openpsa_calendar_event_dba::new_query_builder();
        $qb->add_constraint('id', '=', $own->id);
        $this->assertEquals(0, $qb->count());

        // Creator with other participants: the event is deleted
        $uid = 'caldav-delete-creator-' . uniqid();
        $backend->createCalendarObject($user->id, $uid . '.ics', $this->ics($uid, "SUMMARY:Mine\r\nDTSTART:20050812T100000Z\r\nDTEND:20050812T110000Z\r\nATTENDEE:mailto:{$other_user->email}\r\n"));
        $mine = $this->find_created_event($uid);
        $backend->deleteCalendarObject($user->id, $uid . '.ics');
        $qb = org_openpsa_calendar_event_dba::new_query_builder();
        $qb->add_constraint('id', '=', $mine->id);
        $this->assertEquals(0, $qb->count());
        $this->assertNull($backend->getCalendarObject($other_user->id, $uid . '.ics'));
    }
}
