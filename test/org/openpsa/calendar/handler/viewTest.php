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
use midcom_db_person;
use org_openpsa_calendar_event_dba;
use org_openpsa_calendar_event_member_dba;
use org_openpsa_calendar_vcal;

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class viewTest extends openpsa_testcase
{
    public static function setUpBeforeClass() : void
    {
        self::create_user(true);
    }

    public function testHandler_calendar()
    {
        midcom::get()->auth->request_sudo('org.openpsa.calendar');

        $data = $this->run_handler('org.openpsa.calendar', ['month', '2012-10-17']);
        $this->assertEquals('calendar_view_mode_date', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_json()
    {
        midcom::get()->auth->request_sudo('org.openpsa.calendar');

        $_GET = [
            'start' => time(),
            'end' => time() + 3600,
        ];

        $data = $this->run_handler('org.openpsa.calendar', ['json']);
        $this->assertEquals('calendar_view_json', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_view_raw()
    {
        midcom::get()->auth->request_sudo('org.openpsa.calendar');
        $event = $this->create_object(org_openpsa_calendar_event_dba::class, [
            'start' => time() - 60 * 60,
            'end' => time() + 60 * 60
        ]);

        $data = $this->run_handler('org.openpsa.calendar', ['event', 'raw', $event->guid]);
        $this->assertEquals('event_view_raw', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_view()
    {
        midcom::get()->auth->request_sudo('org.openpsa.calendar');
        $event = $this->create_object(org_openpsa_calendar_event_dba::class, [
            'start' => time() - 60 * 60,
            'end' => time() + 60 * 60
        ]);

        $data = $this->run_handler('org.openpsa.calendar', ['event', $event->guid]);
        $this->assertEquals('event_view', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_view_external_participants()
    {
        midcom::get()->auth->request_sudo('org.openpsa.calendar');
        $person = $this->create_object(midcom_db_person::class, [
            'lastname' => 'Known',
            'email' => uniqid() . '@example.org',
        ]);
        $vcalendar = implode("\r\n", [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'BEGIN:VEVENT',
            'UID:' . uniqid(),
            'DTSTART:20261130T100000Z',
            'DTEND:20261130T110000Z',
            'SUMMARY:Test',
            'ATTENDEE;CN=Jane Doe;PARTSTAT=ACCEPTED:mailto:jane@example.com',
            'ATTENDEE:mailto:nocn@example.com',
            'ATTENDEE;CN=Declined;PARTSTAT=DECLINED:mailto:declined@example.com',
            'ATTENDEE;CN=Known Person:mailto:' . strtoupper($person->email),
            'END:VEVENT',
            'END:VCALENDAR',
        ]);
        $event = $this->create_object(org_openpsa_calendar_event_dba::class, [
            'start' => time() - 60 * 60,
            'end' => time() + 60 * 60,
            'vCalSerialized' => $vcalendar,
        ]);

        $this->create_object(org_openpsa_calendar_event_member_dba::class, [
            'uid' => $person->id,
            'eid' => $event->id,
        ]);

        $this->assertEquals([
            'jane@example.com' => 'Jane Doe',
            'nocn@example.com' => 'nocn@example.com',
        ], org_openpsa_calendar_vcal::get_external_attendees($event));

        $data = $this->run_handler('org.openpsa.calendar', ['event', $event->guid]);
        $content = $data['__openpsa_testcase_response']->getContent();
        $this->assertStringContainsString('Participants</div><div class="value">Known, <a href="mailto:jane@example.com">Jane Doe</a>, <a href="mailto:nocn@example.com">nocn@example.com</a></div>', $content);

        midcom::get()->auth->drop_sudo();
    }
}
