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
class org_openpsa_calendar_handler_createTest extends openpsa_testcase
{
    protected static $_user;

    public static function setUpBeforeClass()
    {
        self::$_user = self::create_user(true);
    }

    public function testHandler_new_event_with_time()
    {
        midcom::get()->auth->request_sudo('org.openpsa.calendar');

        $_GET = array('start' => '2012-12-10 20:30:00');
        $data = $this->run_handler('org.openpsa.calendar', array('event', 'new'));
        $this->assertEquals('new_event', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_new_event()
    {
        midcom::get()->auth->request_sudo('org.openpsa.calendar');

        $data = $this->run_handler('org.openpsa.calendar', array('event', 'new'));
        $this->assertEquals('new_event', $data['handler_id']);

        $title = uniqid(__CLASS__ . '::' . __FUNCTION__);

        $formdata = array(
            'title' => $title,
            'start_date' => '2009-10-11',
            'start_hours' => '10',
            'start_minutes' => '15',
            'end_date' => '2009-10-11',
            'end_hours' => '14',
            'end_minutes' => '15'
        );
        $this->set_dm2_formdata($data['controller'], $formdata);
        $data = $this->run_handler('org.openpsa.calendar', array('event', 'new'));

        $this->assertEquals(array(), $data['controller']->formmanager->form->_errors, 'Form validation failed');

        $this->assertEquals('new_event', $data['handler_id']);

        $qb = midcom_db_event::new_query_builder();
        $qb->add_constraint('title', '=', $title);
        $results = $qb->execute();
        $this->register_objects($results);

        $this->assertEquals(1, sizeof($results));

        $this->assertEquals('2009-10-11 10:15:01', date('Y-m-d h:i:s', $results[0]->start));

        midcom::get()->auth->drop_sudo();
    }
}
