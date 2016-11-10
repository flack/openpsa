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

        $event = $this->create_object('org_openpsa_calendar_event_dba');

        $data = $this->run_handler('org.openpsa.calendar', array('event', 'edit', $event->guid));
        $this->assertEquals('event_edit', $data['handler_id']);

        $formdata = array(
            'start_date' => '2009-10-11',
            'start_hours' => '10',
            'start_minutes' => '15',
            'end_date' => '2009-10-11',
            'end_hours' => '14',
            'end_minutes' => '15'
        );
        $this->set_dm2_formdata($data['controller'], $formdata);
        $data = $this->run_handler('org.openpsa.calendar', array('event', 'edit', $event->guid));
        $event->refresh();

        $this->assertEquals('event_edit', $data['handler_id']);
        $this->assertEquals('2009-10-11 10:15:01', date('Y-m-d h:i:s', $event->start));

        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_delete()
    {
        $this->create_user(true);
        midcom::get()->auth->request_sudo('org.openpsa.calendar');

        $event = $this->create_object('org_openpsa_calendar_event_dba');

        $data = $this->run_handler('org.openpsa.calendar', array('event', 'delete', $event->guid));
        $this->assertEquals('event_delete', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }
}
