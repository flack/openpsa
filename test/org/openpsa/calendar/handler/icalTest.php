<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class org_openpsa_calendar_handler_icalTest extends openpsa_testcase
{
    public function testHandler_new_event()
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
        $event = $this->create_object('org_openpsa_calendar_event_dba', $attributes);

        $attributes = [
            'uid' => $user->id,
            'eid' => $event->id
        ];
        $eventmember = $this->create_object('org_openpsa_calendar_event_member_dba', $attributes);

        midcom::get()->auth->request_sudo('org.openpsa.calendar');
        $user->firstname = 'Firstname';
        $user->lastname = 'Lastname';


        $data = $this->run_handler('org.openpsa.calendar', ['ical', 'events', $account->get_username()]);
        $this->assertEquals('ical_user_feed', $data['handler_id']);

        $this->assertEquals(1, sizeof($data['events']));
        $this->assertEquals($event->guid, $data['events'][0]->guid);
        $output = $this->show_handler($data);

        midcom::get()->auth->drop_sudo();
    }
}
