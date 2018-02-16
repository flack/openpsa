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
class org_openpsa_calendar_handler_viewTest extends openpsa_testcase
{
    public static function setUpBeforeClass()
    {
        self::create_user(true);
    }

    public function testHandler_calendar()
    {
        midcom::get()->auth->request_sudo('org.openpsa.calendar');

        $data = $this->run_handler('org.openpsa.calendar', ['month', '2012-10-17']);
        $this->assertEquals('calendar_view_mode_date', $data['handler_id']);

        $this->show_handler($data);
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

        $this->show_handler($data);
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

        $this->show_handler($data);
        midcom::get()->auth->drop_sudo();
    }
}
