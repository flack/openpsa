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
class org_openpsa_calendar_handler_agendaTest extends openpsa_testcase
{
    public function testHandler_day()
    {
        $this->create_user(true);
        midcom::get()->auth->request_sudo('org.openpsa.calendar');

        $attributes = [
            'start' => 1144056938,
            'end' => 1144066938
        ];
        $event = $this->create_object('org_openpsa_calendar_event_dba', $attributes);

        $data = $this->run_handler('org.openpsa.calendar', ['agenda', 'day', '2006-04-03']);
        $this->assertEquals('agenda_day', $data['handler_id']);

        $this->show_handler($data);

        midcom::get()->auth->drop_sudo();
    }
}
