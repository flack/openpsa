<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace test\org\openpsa\calendar\handler;

use openpsa_testcase;
use midcom;
use org_openpsa_calendar_event_dba;
use midcom_core_account;
use org_openpsa_calendar_interface;
use org_openpsa_calendar_event_member_dba;
use Symfony\Component\HttpFoundation\Request;

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class icalTest extends openpsa_testcase
{
    const UPDATE = "BEGIN:VCALENDAR\r\n
PRODID:-//Mozilla.org/NONSGML Mozilla Calendar V1.1//EN\r\n
VERSION:2.0\r\n
CALSCALE:GREGORIAN\r\n
METHOD:PUBLISH\r\n
X-WR-CALNAME:openpsatest\r\n
X-WR-TIMEZONE:Europe/Berlin\r\n
BEGIN:VTIMEZONE\r\n
TZID:Europe/Berlin\r\n
BEGIN:DAYLIGHT\r\n
TZOFFSETFROM:+0100\r\n
TZOFFSETTO:+0200\r\n
TZNAME:CEST\r\n
DTSTART:19700329T020000\r\n
RRULE:FREQ=YEARLY;BYDAY=-1SU;BYMONTH=3\r\n
END:DAYLIGHT\r\n
BEGIN:STANDARD\r\n
TZOFFSETFROM:+0200\r\n
TZOFFSETTO:+0100\r\n
TZNAME:CET\r\n
DTSTART:19701025T030000\r\n
RRULE:FREQ=YEARLY;BYDAY=-1SU;BYMONTH=10\r\n
END:STANDARD\r\n
END:VTIMEZONE\r\n
BEGIN:VEVENT\r\n
CREATED:20210908T152018Z\r\n
LAST-MODIFIED:20210908T152018Z\r\n
DTSTAMP:20210908T152018Z\r\n
UID:353975f8-48dd-4a9d-bb08-07e2bfbf0d98\r\n
DTSTART;TZID=Europe/Berlin:20210907T101500\r\n
DTEND;TZID=Europe/Berlin:20210907T121500\r\n
TRANSP:OPAQUE\r\n
END:VEVENT\r\n
END:VCALENDAR\r\n
";

    public function testHandler_user_events()
    {
        $user = $this->create_user(true);
        $account = new midcom_core_account($user);

        $attributes = [
            'up' => org_openpsa_calendar_interface::find_root_event()->id,
            'start' => time() - 10,
            'end' => time() + 8000,
            'title' => 'Event Title',
            'description' => "Event \nDescription",
            'location' => 'Event Location',
        ];
        $event = $this->create_object(org_openpsa_calendar_event_dba::class, $attributes);

        $attributes = [
            'uid' => $user->id,
            'eid' => $event->id
        ];
        $this->create_object(org_openpsa_calendar_event_member_dba::class, $attributes);

        midcom::get()->auth->request_sudo('org.openpsa.calendar');
        $user->firstname = 'Firstname';
        $user->lastname = 'Lastname';

        $data = $this->run_handler('org.openpsa.calendar', ['ical', 'events', $account->get_username()]);
        $this->assertEquals('ical_user_feed', $data['handler_id']);
        $content = $data['__openpsa_testcase_response']->getContent();

        $this->assertEquals(1, substr_count($content, 'BEGIN:VEVENT'));
        $this->assertStringContainsString('UID:' . $event->guid . '-midgardGuid', $content);

        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_user_events_update()
    {
        $user = $this->create_user(true);
        $account = new midcom_core_account($user);

        midcom::get()->auth->request_sudo('org.openpsa.calendar');

        $request = new Request([], [], [], [], [], [], self::UPDATE);
        $request->setMethod('PUT');

        $data = $this->run_handler('org.openpsa.calendar', ['ical', 'events', $account->get_username()], $request);
        $this->assertEquals('ical_user_feed', $data['handler_id']);
        $content = $data['__openpsa_testcase_response']->getContent();

        $this->assertEquals(1, substr_count($content, 'BEGIN:VEVENT'));

        $qb = org_openpsa_calendar_event_member_dba::new_query_builder();
        $qb->add_constraint('uid', '=', $user->id);
        $results = $qb->execute();
        $this->register_objects($results);
        $this->assertCount(1, $results);

        $event = new org_openpsa_calendar_event_dba($results[0]->eid);
        $this->register_object($event);
        $this->assertEquals('353975f8-48dd-4a9d-bb08-07e2bfbf0d98', $event->externalGuid);

        midcom::get()->auth->drop_sudo();
    }
}
