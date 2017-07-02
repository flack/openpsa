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
class org_openpsa_calendar_handler_adminTest extends openpsa_testcase
{
    public function testHandler_edit()
    {
        $this->create_user(true);
        midcom::get()->auth->request_sudo('org.openpsa.calendar');

        $event = $this->create_object('org_openpsa_calendar_event_dba', [
            'start' => time() - 60 * 60,
            'end' => time() + 60 * 60
        ]);

        $data = $this->run_handler('org.openpsa.calendar', ['event', 'edit', $event->guid]);
        $this->assertEquals('event_edit', $data['handler_id']);

        $formdata = [
            'start' => [
                'date' => '2009-10-11',
                'time' => '10:15'
            ],
            'end' => [
                'date' => '2009-10-11',
                'time' => '14:15',
            ]
        ];
        $this->set_dm_formdata($data['controller'], $formdata);
        $data = $this->run_handler('org.openpsa.calendar', ['event', 'edit', $event->guid]);
        $event->refresh();

        $this->assertEquals('event_edit', $data['handler_id']);
        $this->assertEquals('2009-10-11 10:15:01', date('Y-m-d h:i:s', $event->start));

        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_delete()
    {
        $this->create_user(true);
        midcom::get()->auth->request_sudo('org.openpsa.calendar');

        $event = $this->create_object('org_openpsa_calendar_event_dba', [
            'start' => time() - 60 * 60,
            'end' => time() + 60 * 60
        ]);

        $data = $this->run_handler('org.openpsa.calendar', ['event', 'delete', $event->guid]);
        $this->assertEquals('event_delete', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }
}
