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
use Sabre\DAV\Exception\Forbidden;
use DateTime;

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class backendTest extends openpsa_testcase
{
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

    public function test_principals()
    {
        $user = $this->create_user();
        midcom::get()->auth->request_sudo('org.openpsa.calendar');
        $user->firstname = 'Caldav';
        $user->email = 'caldav-' . $user->id . '@openpsa.test';
        $user->update();
        midcom::get()->auth->drop_sudo();

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
        $other = $this->create_user();
        $event1 = $this->create_event($user, gmmktime(10, 0, 0, 1, 10, 2005));
        $event2 = $this->create_event($user, gmmktime(10, 0, 0, 3, 10, 2005), ['externalGuid' => 'external-uid-' . uniqid()]);
        $other_event = $this->create_event($other, gmmktime(12, 0, 0, 1, 10, 2005));

        $backend = new org_openpsa_calendar_caldav_backend(new org_openpsa_calendar_caldav_principals, 'Test Calendar');

        $calendars = $backend->getCalendarsForUser($this->get_principal($user));
        $this->assertCount(1, $calendars);
        $this->assertEquals($user->id, $calendars[0]['id']);
        $this->assertEquals('default', $calendars[0]['uri']);
        $this->assertEquals('Test Calendar', $calendars[0]['{DAV:}displayname']);
        $this->assertTrue($calendars[0]['{http://sabredav.org/ns}read-only']);
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
            'uid' => $other->id,
            'eid' => $event1->id
        ]);
        $calendars = $backend->getCalendarsForUser($this->get_principal($user));
        $this->assertNotEquals($ctag, $calendars[0]['{http://calendarserver.org/ns/}getctag']);

        $this->expectException(Forbidden::class);
        $backend->deleteCalendarObject($user->id, $event1->guid . '.ics');
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
}
