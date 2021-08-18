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

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class icalTest extends openpsa_testcase
{
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
}
