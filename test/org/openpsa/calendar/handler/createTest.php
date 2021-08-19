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

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class createTest extends openpsa_testcase
{
    protected static $_user;

    public static function setUpBeforeClass() : void
    {
        self::$_user = self::create_user(true);
    }

    public function testHandler_new_event_with_time()
    {
        midcom::get()->auth->request_sudo('org.openpsa.calendar');

        $_GET = ['start' => '2012-12-10 20:30:00'];
        $data = $this->run_handler('org.openpsa.calendar', ['event', 'new']);
        $this->assertEquals('new_event', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_new_event()
    {
        midcom::get()->auth->request_sudo('org.openpsa.calendar');

        $data = $this->run_handler('org.openpsa.calendar', ['event', 'new']);
        $this->assertEquals('new_event', $data['handler_id']);

        $title = uniqid(__CLASS__ . '::' . __FUNCTION__);

        $formdata = [
            'title' => $title,
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
        $data = $this->run_handler('org.openpsa.calendar', ['event', 'new']);

        $this->assertEquals([], $data['controller']->get_errors(), 'Form validation failed');

        $this->assertEquals('new_event', $data['handler_id']);

        $qb = org_openpsa_calendar_event_dba::new_query_builder();
        $qb->add_constraint('title', '=', $title);
        $results = $qb->execute();
        $this->register_objects($results);

        $this->assertCount(1, $results);

        $this->assertEquals('2009-10-11 10:15:01', date('Y-m-d h:i:s', $results[0]->start));

        midcom::get()->auth->drop_sudo();
    }
}
